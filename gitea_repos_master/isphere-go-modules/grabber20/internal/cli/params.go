package cli

import "flag"

type Params struct {
	ChromeDriverPath string
	ChromeDriverPort uint
}

func NewParams() *Params {
	p := new(Params)

	flag.StringVar(&p.ChromeDriverPath, "chrome-driver-path", "/usr/local/bin/chromedriver", "path to chrome driver")
	flag.UintVar(&p.ChromeDriverPort, "chrome-driver-port", 4444, "port for chrome driver")
	flag.Parse()

	return p
}
