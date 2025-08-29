package configuration

type HTTPS struct {
	Server
	CertFile            string
	KeyFile             string
	LetsEncryptCacheDir string
}
