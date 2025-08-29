package main

import (
	"context"
	"fmt"
	"os"

	"git.i-sphere.ru/isphere-go-modules/grabber/internal/announcements"
	bik "git.i-sphere.ru/isphere-go-modules/grabber/internal/bik/command"
	cbr "git.i-sphere.ru/isphere-go-modules/grabber/internal/cbr/command"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/config"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/console"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/contract"
	facebook "git.i-sphere.ru/isphere-go-modules/grabber/internal/facebook/command"
	fedsfm "git.i-sphere.ru/isphere-go-modules/grabber/internal/fedsfm/command"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fmsdb"
	fns "git.i-sphere.ru/isphere-go-modules/grabber/internal/fns/command"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/fsin"
	minjust "git.i-sphere.ru/isphere-go-modules/grabber/internal/minjust/command"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/regions"
	rosobrnadzor "git.i-sphere.ru/isphere-go-modules/grabber/internal/rosobrnadzor/command"
	rosstat "git.i-sphere.ru/isphere-go-modules/grabber/internal/rosstat/command"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/rossvyaz"
	"git.i-sphere.ru/isphere-go-modules/grabber/internal/vk"
	"github.com/urfave/cli/v2"
	"go.uber.org/fx"
)

func main() {
	fx.New(
		fx.Provide(announcements.NewAnnouncements),
		fx.Provide(bik.NewBik),
		fx.Provide(cbr.NewCbr),
		fx.Provide(config.NewConfig),
		fx.Provide(console.NewApplication),
		fx.Provide(facebook.NewFacebook),
		fx.Provide(facebook.NewFacebookIndex),
		fx.Provide(fedsfm.NewFedsfm),
		fx.Provide(fmsdb.NewFsin),
		fx.Provide(fns.NewDebtam),
		fx.Provide(fns.NewDisqualifiedpersons),
		fx.Provide(fns.NewMassAddress),
		fx.Provide(fns.NewMassfounders),
		fx.Provide(fns.NewMassleaders),
		fx.Provide(fns.NewPaytax),
		fx.Provide(fns.NewRegisterdisqualified),
		fx.Provide(fns.NewRevexp),
		fx.Provide(fns.NewRsmp),
		fx.Provide(fns.NewSnr),
		fx.Provide(fns.NewSshr),
		fx.Provide(fns.NewTaxoffence),
		fx.Provide(fsin.NewFsin),
		fx.Provide(minjust.NewMinJust),
		fx.Provide(regions.NewRegions),
		fx.Provide(regions.NewRegionsV2),
		fx.Provide(rosobrnadzor.NewRegistry),
		fx.Provide(rosstat.NewRosStat),
		fx.Provide(rossvyaz.NewRossvyaz),
		fx.Provide(vk.NewEmails),
		fx.Provide(vk.NewPhones),

		fx.Provide(func(
			announcements *announcements.Announcements,
			bik *bik.Bik,
			cbr *cbr.Cbr,
			facebook *facebook.Facebook,
			facebookIndex *facebook.FacebookIndex,
			fedsfm *fedsfm.Fedsfm,
			fmsdb *fmsdb.FMSDB,
			fnsDebtam *fns.Debtam,
			fnsDisqualifiedpersons *fns.Disqualifiedpersons,
			fnsMasaddress *fns.Masaddress,
			fnsMassfounders *fns.Massfounders,
			fnsMassleaders *fns.Massleaders,
			fnsPaytax *fns.Paytax,
			fnsRegisterdisqualified *fns.Registerdisqualified,
			fnsRevexp *fns.Revexp,
			fnsRsmp *fns.Rsmp,
			fnsSnr *fns.Snr,
			fnsSshr *fns.Sshr,
			fnsTaxoffence *fns.Taxoffence,
			fsin *fsin.Fsin,
			minJust *minjust.MinJust,
			regions *regions.Regions,
			regionsv2 *regions.RegionsV2,
			rosobrnadzorRegistry *rosobrnadzor.Registry,
			rosstat *rosstat.RosStat,
			rossvyaz *rossvyaz.Rossvyaz,
			vkEmails *vk.Emails,
			vkPhones *vk.Phones,
		) []contract.Commander {
			return []contract.Commander{
				announcements,
				bik,
				cbr,
				facebook,
				facebookIndex,
				fedsfm,
				fmsdb,
				fnsDebtam,
				fnsDisqualifiedpersons,
				fnsMasaddress,
				fnsMassfounders,
				fnsMassleaders,
				fnsPaytax,
				fnsRegisterdisqualified,
				fnsRevexp,
				fnsRsmp,
				fnsSnr,
				fnsSshr,
				fnsTaxoffence,
				fsin,
				minJust,
				regions,
				regionsv2,
				rosobrnadzorRegistry,
				rosstat,
				rossvyaz,
				vkEmails,
				vkPhones,
			}
		}),

		fx.Invoke(func(application *cli.App, _ *config.Config) error {
			if err := application.RunContext(context.Background(), os.Args); err != nil {
				return fmt.Errorf("failed to run console application: %w", err)
			}

			return nil
		}),
	).Run()
}
