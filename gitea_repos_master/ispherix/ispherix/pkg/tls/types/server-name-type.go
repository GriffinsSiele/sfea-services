package types

import (
	"fmt"
	"strconv"
)

type ServerNameType int

const ServerNameTypeHostname ServerNameType = 0

func (s *ServerNameType) String() string {
	switch *s {
	case ServerNameTypeHostname:
		return "hostname"
	default:
		return fmt.Sprintf("0x%02x", *s)
	}
}

func (s *ServerNameType) MarshalJSON() ([]byte, error) {
	return []byte(strconv.Quote(s.String())), nil
}
