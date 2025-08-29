package command

import (
	"archive/zip"
	"bytes"
	"encoding/xml"
	"fmt"
	"os"
	"sync/atomic"
	"time"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/util"
	"github.com/jackc/pgx/v5"
	"github.com/jackc/pgx/v5/pgtype"
	"github.com/schollz/progressbar/v3"
	"github.com/urfave/cli/v2"
)

type Registry struct{}

func NewRegistry() *Registry {
	return &Registry{}
}

func (t *Registry) Describe() *cli.Command {
	return &cli.Command{
		Category: "rosobrnadzor",
		Name:     "rosobrnadzor:registry",
		Action:   t.Execute,
		Usage:    "Реестр лицензий на осуществление образовательной деятельности",
		Flags: cli.FlagsByName{
			&cli.StringFlag{Name: "database", Required: true, EnvVars: []string{"ISPHERE_GRABBER_DATABASE"}},
			&cli.StringFlag{Name: "schema", Value: "rosobrnadzor"},
			&cli.StringFlag{Name: "table", Value: "registry"},
			&cli.StringFlag{Name: "table-licenses", Value: "supplements"},
			&cli.BoolFlag{Name: "no-cache", Value: false},
			&cli.StringFlag{Name: "url", Value: "https://islod.obrnadzor.gov.ru/rlic/opendata/"},
		},
	}
}

func (t *Registry) Execute(c *cli.Context) error {
	var (
		ctx            = c.Context
		database       = c.String("database")
		schema         = c.String("schema")
		primaryTable   = c.String("table")
		secondaryTable = c.String("table-licenses")
		noCache        = c.Bool("no-cache")
		url            = c.String("url")
	)

	filename, err := util.PgCache(ctx, url, noCache)
	if err != nil {
		return fmt.Errorf("failed to attach cache: %w", err)
	}

	data, err := os.ReadFile(filename)
	if err != nil {
		return fmt.Errorf("failed to open source file: %w", err)
	}

	zh, err := zip.NewReader(bytes.NewReader(data), int64(len(data)))
	if err != nil {
		return fmt.Errorf("failed to open archive: %w", err)
	}

	var (
		primaryIDSeq, secondaryIDSeq atomic.Uint64
		primaryRows, secondaryRows   [][]any
	)

	for _, f := range zh.File {
		fh, err := f.Open()
		if err != nil {
			return fmt.Errorf("failed to open archive file: %w", err)
		}

		//goland:noinspection GoUnhandledErrorResult,GoDeferInLoop
		defer fh.Close()

		dec := xml.NewDecoder(fh)
		bar := progressbar.DefaultBytes(int64(f.UncompressedSize64), f.Name)

		for tok, _ := dec.Token(); tok != nil; tok, _ = dec.Token() {
			_ = bar.Set64(dec.InputOffset())

			switch tok := tok.(type) {
			case xml.StartElement:
				if tok.Name.Local != "license" {
					continue
				}

				var license License
				if err = dec.DecodeElement(&license, &tok); err != nil {
					return fmt.Errorf("failed to decode license: %w", err)
				}

				primaryID := primaryIDSeq.Add(1)

				primaryRows = append(primaryRows, []any{
					primaryID,
					license.SysGUID,
					license.SchoolGUID,
					license.StatusName,
					license.SchoolName,
					license.ShortName,
					license.INN,
					license.OGRN,
					license.SchoolTypeName,
					license.LawAddress,
					license.OrgName,
					license.RegNum,
					asDate(license.DateLicDoc),
					asDate(license.DateEnd),
				})

				for _, supplement := range license.Supplements.Supplement {
					secondaryID := secondaryIDSeq.Add(1)

					secondaryRows = append(secondaryRows, []any{
						secondaryID,
						primaryID,
						supplement.LicenseFK,
						supplement.Number,
						supplement.StatusName,
						supplement.SchoolGUID,
						supplement.SchoolName,
						supplement.LawAddress,
						supplement.OrgName,
						supplement.NumLicDoc,
						asDate(supplement.DateLicDoc),
						supplement.SysGUID,
					})
				}
			}
		}

		_ = bar.Finish()
	}

	pool, err := util.PgConnect(ctx, database)
	if err != nil {
		return fmt.Errorf("failed to attach pg: %w", err)
	}

	defer pool.Close()

	conn, err := pool.Acquire(ctx)
	if err != nil {
		return fmt.Errorf("failed to acquie pg: %w", err)
	}

	defer conn.Release()

	if err = util.PgTruncate(ctx, conn, schema, primaryTable, secondaryTable); err != nil {
		return fmt.Errorf("failed to trucate tables: %w", err)
	}

	if err = util.PgDisableTriggers(ctx, conn, schema, primaryTable, secondaryTable); err != nil {
		return fmt.Errorf("failed to disable db triggers: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer util.PgEnableTriggers(ctx, conn, schema, primaryTable, secondaryTable) //nolint:errcheck

	bar := progressbar.Default(2, "flushing data")

	if _, err = conn.CopyFrom(ctx, pgx.Identifier{schema, primaryTable}, []string{
		"id", "sys_guid", "school_guid", "status_name", "school_name", "short_name", "inn",
		"ogrn", "school_type_name", "law_address", "org_name", "reg_num", "date_lic_doc", "date_end"},
		pgx.CopyFromRows(primaryRows)); err != nil {
		return fmt.Errorf("failed to copy primary data: %w", err)
	}

	_ = bar.Add(1)

	if _, err = conn.CopyFrom(ctx, pgx.Identifier{schema, secondaryTable}, []string{
		"id", "registry_id", "license_fk", "number", "status_name", "school_guid", "school_name",
		"law_address", "org_name", "num_lic_doc", "date_lic_doc", "sys_guid"},
		pgx.CopyFromRows(secondaryRows)); err != nil {
		return fmt.Errorf("failed to copy secondary data: %w", err)
	}

	_ = bar.Finish()

	return nil
}

type Date struct {
	value *time.Time
}

func (t *Date) UnmarshalXML(d *xml.Decoder, start xml.StartElement) error {
	var v string
	if err := d.DecodeElement(&v, &start); err != nil {
		return fmt.Errorf("failed to decode element: %s", err)
	}

	if v == "" {
		return nil
	}

	parsed, err := time.Parse(time.DateOnly, v)
	if err != nil {
		return fmt.Errorf("failed to parse date: %w", err)
	}

	*t = Date{value: &parsed}

	return nil
}

type License struct {
	SysGUID        string       `xml:"sysGuid" json:"sys_guid"`
	SchoolGUID     string       `xml:"schoolGuid" json:"school_guid"`
	StatusName     string       `xml:"statusName" json:"status_name"`
	SchoolName     string       `xml:"schoolName" json:"school_name"`
	ShortName      string       `xml:"shortName" json:"short_name"`
	INN            string       `xml:"Inn" json:"inn"`
	OGRN           string       `xml:"Ogrn" json:"ogrn"`
	SchoolTypeName string       `xml:"schoolTypeName" json:"school_type_name"`
	LawAddress     string       `xml:"lawAddress" json:"law_address"`
	OrgName        string       `xml:"orgName" json:"org_name"`
	RegNum         string       `xml:"regNum" json:"reg_num"`
	DateLicDoc     *Date        `xml:"dateLicDoc" json:"date_lic_doc"`
	DateEnd        *Date        `xml:"dateEnd" json:"date_end"`
	Supplements    *Supplements `xml:"supplements" json:"supplements"`
}

type LicensedProgram struct {
	SupplementFK      string `xml:"supplementFk"`
	EduProgramType    string `xml:"eduProgramType"`
	Code              string `xml:"code"`
	Name              string `xml:"name"`
	EduLevelName      string `xml:"eduLevelName"`
	EduProgramKind    string `xml:"eduProgramKind"`
	QualificationCode string `xml:"qualificationCode"`
	QualificationName string `xml:"qualificationName"`
	SysGUID           string `xml:"sysGuid"`
}

type LicensedPrograms struct {
	LicensedProgram []*LicensedProgram `xml:"licensedProgram"`
}

type Supplement struct {
	LicenseFK        string            `xml:"licenseFK" json:"license_fk"`
	Number           string            `xml:"number" json:"number"`
	StatusName       string            `xml:"statusName" json:"status_name"`
	SchoolGUID       string            `xml:"schoolGuid" json:"school_guid"`
	SchoolName       string            `xml:"schoolName" json:"school_name"`
	ShortName        string            `xml:"shortName" json:"short_name"`
	LawAddress       string            `xml:"lawAddress" json:"law_address"`
	OrgName          string            `xml:"orgName" json:"org_name"`
	NumLicDoc        string            `xml:"numLicDoc" json:"num_lic_doc"`
	DateLicDoc       *Date             `xml:"dateLicDoc" json:"date_lic_doc"`
	SysGUID          string            `xml:"sysGuid" json:"sys_guid"`
	LicensedPrograms *LicensedPrograms `xml:"licensedPrograms" json:"-"`
}

type Supplements struct {
	Supplement []*Supplement `xml:"supplement"`
}

func asDate(t *Date) any {
	if t == nil || t.value == nil {
		tmp := pgtype.Date{Time: time.Now()}
		tmp.Valid = false
		return tmp
	}

	return t.value.Format(time.DateOnly)
}
