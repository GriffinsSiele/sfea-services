package handshake

import (
	"encoding/base64"
	"strconv"
)

type Random [32]byte

func (r *Random) MarshalJSON() ([]byte, error) {
	return []byte(strconv.Quote(base64.StdEncoding.EncodeToString((*r)[:]))), nil
}
