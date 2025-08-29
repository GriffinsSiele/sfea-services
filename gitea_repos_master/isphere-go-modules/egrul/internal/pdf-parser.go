package internal

import (
	"io"
	"math"
	"os"
	"strconv"
	"strings"

	"github.com/dslipak/pdf"
	"github.com/pkg/errors"
	"github.com/sirupsen/logrus"
	"gopkg.in/yaml.v3"
)

type PDFParser struct {
	hierarchyBuilder  *HierarchyBuilder
	hierarchyTemplate HierarchyTemplate
}

func NewPDFParser(hierarchyBuilder *HierarchyBuilder) (*PDFParser, error) {
	fh, err := os.Open("config/hierarchy-template.yaml")
	if err != nil {
		return nil, errors.Wrap(err, "failed to open template file")
	}
	//goland:noinspection GoUnhandledErrorResult
	defer fh.Close()

	var hierarchyTemplate HierarchyTemplate
	if err = yaml.NewDecoder(fh).Decode(&hierarchyTemplate); err != nil {
		return nil, errors.Wrap(err, "failed to decode template file")
	}

	return &PDFParser{
		hierarchyBuilder:  hierarchyBuilder,
		hierarchyTemplate: hierarchyTemplate,
	}, nil
}

func (p *PDFParser) Parse(f io.ReaderAt, size int64) (map[string]any, error) {
	pdfReader, err := pdf.NewReader(f, size)
	if err != nil {
		return nil, errors.Wrap(err, "failed to create pdf reader")
	}

	rows := make([][]string, 0, 1000)

	for i := 1; i <= pdfReader.NumPage(); i++ {
		page := pdfReader.Page(i)

		var prevX, prevY float64
		var prevFont string
		var sb strings.Builder

		for _, text := range page.Content().Text {
			xSize, ySize := math.Abs(text.X-prevX), math.Abs(text.Y-prevY)

			if xSize > 0 {
				sb.WriteString("!!!")
			}

			if (ySize > text.FontSize+1) || (prevFont != text.Font) {
				str := strings.TrimSpace(sb.String())

				if str != "" {
					cols := strings.Split(str, "!!!")
					for j, col := range cols {
						cols[j] = strings.TrimSpace(col)
					}

					rows = append(rows, CleanSlice(cols))
				}

				sb.Reset()
			} else if ySize > 0 {
				sb.WriteRune(' ')
			}

			sb.WriteString(text.S)

			prevX, prevY, prevFont = text.X, text.Y, text.Font
		}
	}

	skipHeaderChecks := map[int]any{}
	nodes := make([]*Node, 0, len(rows))

	for i, row := range rows {
		if len(row) == 0 {
			continue
		}

		_, err1 := strconv.Atoi(row[0])
		if err1 == nil && len(row) == 2 && len(rows[i+1]) == 1 {
			row = append(row, rows[i+1][0])
			skipHeaderChecks[i+1] = struct{}{}
		}

		if len(row) == 2 {
			row = []string{strings.Join(row, " ")}
		}

		if len(row) > 1 {
			_, err1 := strconv.Atoi(row[0])
			_, err2 := strconv.Atoi(row[1])
			if err1 != nil || err2 == nil {
				row = []string{strings.Join(row, " ")}
			}
		}

		if _, ok := skipHeaderChecks[i]; !ok && len(row) == 1 {
			if strings.HasPrefix(row[0], "(") {
				logrus.WithField("row", row).Debug("skip line by open brakes")
				continue
			}

			nodes = append(nodes, &Node{
				Type:  NodeTypeSection,
				Title: row[0],
			})
			continue
		}

		if len(row) != 3 {
			logrus.WithField("row", row).Debug("skip line by row count")
			continue
		}

		nodes = append(nodes, &Node{
			Type:  NodeTypeRecord,
			Title: row[1],
			Value: &row[2],
		})
	}

	result, err := p.hierarchyBuilder.BuildWithTemplate(p.hierarchyTemplate, nodes)
	if err != nil {
		return nil, errors.Wrap(err, "failed to build hierarchy")
	}

	return result, nil
}
