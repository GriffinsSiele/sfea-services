package commands

import (
	"crypto/rand"
	"crypto/rsa"
	"crypto/x509"
	"crypto/x509/pkix"
	"encoding/pem"
	"fmt"
	"math/big"
	"os"
	"time"

	"github.com/charmbracelet/log"
	"github.com/urfave/cli/v2"
)

type GenerateCertificate struct {
	log *log.Logger
}

func NewGenerateCertificate() *GenerateCertificate {
	return &GenerateCertificate{
		log: log.WithPrefix("commands.GenerateCertificate"),
	}
}

func (t *GenerateCertificate) NewCommand() *cli.Command {
	return &cli.Command{
		Category: "generators",
		Name:     "generate-certificate",
		Action:   t.Start,
	}
}

func (t *GenerateCertificate) Start(c *cli.Context) error {
	//goland:noinspection GoUnhandledErrorResult
	os.MkdirAll("var/ssl", os.ModePerm)

	err := t.generateCA(c)
	if err != nil {
		return fmt.Errorf("failed to generate CA: %w", err)
	}

	err = t.generateServerCertificate(c)
	if err != nil {
		return fmt.Errorf("failed to generate server certificate: %w", err)
	}

	t.log.Info("Generated TLS certificates")

	return nil
}

func (t *GenerateCertificate) generateCA(c *cli.Context) error {
	carPrivateKey, err := rsa.GenerateKey(rand.Reader, 2048)
	if err != nil {
		return fmt.Errorf("failed to generate CA private key: %w", err)
	}

	caTemplate := &x509.Certificate{
		SerialNumber: big.NewInt(1),
		Subject: pkix.Name{
			CommonName: os.Getenv("TLS_CA_COMMON_NAME"),
			Organization: []string{
				os.Getenv("TLS_CA_COMMON_ORG"),
			},
		},
		NotBefore:             time.Now(),
		NotAfter:              time.Now().AddDate(1, 0, 0),
		KeyUsage:              x509.KeyUsageCertSign | x509.KeyUsageCRLSign,
		BasicConstraintsValid: true,
		IsCA:                  true,
	}

	caCert, err := x509.CreateCertificate(rand.Reader, caTemplate, caTemplate, &carPrivateKey.PublicKey, carPrivateKey)
	if err != nil {
		return fmt.Errorf("failed to generate CA certificate: %w", err)
	}

	err = t.savePrivateKeyToFile(os.Getenv("TLS_CA_PRIVATE_KEY_FILE"), carPrivateKey)
	if err != nil {
		return fmt.Errorf("failed to save CA private key to file: %w", err)
	}

	err = t.saveCertificateToFile(os.Getenv("TLS_CA_CERT_FILE"), caCert)
	if err != nil {
		return fmt.Errorf("failed to save CA certificate to file: %w", err)
	}

	return nil
}

func (t *GenerateCertificate) generateServerCertificate(c *cli.Context) error {
	privateKey, err := rsa.GenerateKey(rand.Reader, 2048)
	if err != nil {
		return fmt.Errorf("failed to generate private key: %w", err)
	}

	template := &x509.Certificate{
		SerialNumber: big.NewInt(2),
		Subject: pkix.Name{
			CommonName: c.String(os.Getenv("TLS_COMMON_NAME")),
			Organization: []string{
				os.Getenv("TLS_COMMON_ORG"),
			},
		},
		NotBefore:             time.Now(),
		NotAfter:              time.Now().AddDate(1, 0, 0),
		KeyUsage:              x509.KeyUsageDigitalSignature,
		ExtKeyUsage:           []x509.ExtKeyUsage{x509.ExtKeyUsageServerAuth},
		BasicConstraintsValid: true,
	}

	template.DNSNames = []string{
		os.Getenv("TLS_HOST"),
	}

	caPrivateKey, err := t.loadPrivateKeyFromFile(os.Getenv("TLS_CA_PRIVATE_KEY_FILE"))
	if err != nil {
		return fmt.Errorf("failed to load CA private key from file: %w", err)
	}

	caCert, err := t.loadCertificateFromFile(os.Getenv("TLS_CA_CERT_FILE"))
	if err != nil {
		return fmt.Errorf("failed to load CA certificate from file: %w", err)
	}

	cert, err := x509.CreateCertificate(rand.Reader, template, caCert, &privateKey.PublicKey, caPrivateKey)
	if err != nil {
		return fmt.Errorf("failed to generate certificate: %w", err)
	}

	err = t.savePrivateKeyToFile(os.Getenv("TLS_SERVER_PRIVATE_KEY_FILE"), privateKey)
	if err != nil {
		return fmt.Errorf("failed to save private key to file: %w", err)
	}

	err = t.saveCertificateToFile(os.Getenv("TLS_SERVER_CERT_FILE"), cert)
	if err != nil {
		return fmt.Errorf("failed to save certificate to file: %w", err)
	}

	return nil
}

func (t *GenerateCertificate) savePrivateKeyToFile(filePath string, privateKey *rsa.PrivateKey) error {
	privateKeyFile, err := os.Create(filePath)
	if err != nil {
		return fmt.Errorf("failed to create private key file: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer privateKeyFile.Close()

	err = pem.Encode(privateKeyFile, &pem.Block{
		Type:  "RSA PRIVATE KEY",
		Bytes: x509.MarshalPKCS1PrivateKey(privateKey),
	})
	if err != nil {
		return fmt.Errorf("failed to encode private key: %w", err)
	}

	return nil
}

func (t *GenerateCertificate) saveCertificateToFile(filePath string, cert []byte) error {
	certFile, err := os.Create(filePath)
	if err != nil {
		return fmt.Errorf("failed to create certificate file: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer certFile.Close()

	err = pem.Encode(certFile, &pem.Block{
		Type:  "CERTIFICATE",
		Bytes: cert,
	})
	if err != nil {
		return fmt.Errorf("failed to encode certificate: %w", err)
	}

	return nil
}

func (t *GenerateCertificate) loadPrivateKeyFromFile(filePath string) (*rsa.PrivateKey, error) {
	privateKeyFile, err := os.Open(filePath)
	if err != nil {
		return nil, fmt.Errorf("failed to open private key file: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer privateKeyFile.Close()

	fileInfo, err := privateKeyFile.Stat()
	if err != nil {
		return nil, fmt.Errorf("failed to get private key file info: %w", err)
	}

	fileSize := fileInfo.Size()
	fileContent := make([]byte, fileSize)
	_, err = privateKeyFile.Read(fileContent)
	if err != nil {
		return nil, fmt.Errorf("failed to read private key file: %w", err)
	}

	block, _ := pem.Decode(fileContent)
	if block == nil {
		return nil, fmt.Errorf("failed to decode private key PEM block")
	}

	privateKey, err := x509.ParsePKCS1PrivateKey(block.Bytes)
	if err != nil {
		return nil, fmt.Errorf("failed to parse private key: %w", err)
	}

	return privateKey, nil
}

func (t *GenerateCertificate) loadCertificateFromFile(filePath string) (*x509.Certificate, error) {
	certFile, err := os.Open(filePath)
	if err != nil {
		return nil, fmt.Errorf("failed to open certificate file: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer certFile.Close()

	fileInfo, err := certFile.Stat()
	if err != nil {
		return nil, fmt.Errorf("failed to get certificate file info: %w", err)
	}

	fileSize := fileInfo.Size()
	fileContent := make([]byte, fileSize)
	_, err = certFile.Read(fileContent)
	if err != nil {
		return nil, fmt.Errorf("failed to read certificate file: %w", err)
	}

	block, _ := pem.Decode(fileContent)
	if block == nil {
		return nil, fmt.Errorf("failed to decode certificate PEM block")
	}

	cert, err := x509.ParseCertificate(block.Bytes)
	if err != nil {
		return nil, fmt.Errorf("failed to parse certificate: %w", err)
	}

	return cert, nil
}
