package types

import (
	"crypto/tls"
	"fmt"
	"strconv"
)

type CipherSuite uint16

func (s *CipherSuite) String() string {
	for _, c := range tls.CipherSuites() {
		if c.ID == uint16(*s) {
			return c.Name
		}
	}
	for _, c := range tls.InsecureCipherSuites() {
		if c.ID == uint16(*s) {
			return c.Name
		}
	}
	return fmt.Sprintf("0x%04x", *s)
}

func (s *CipherSuite) MarshalJSON() ([]byte, error) {
	return []byte(strconv.Quote(s.String())), nil
}
