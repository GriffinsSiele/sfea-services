package oauth2

import (
	"os"

	"github.com/go-oauth2/oauth2/v4/generates"
	"github.com/golang-jwt/jwt"
)

func NewJWTAccessGenerate() *generates.JWTAccessGenerate {
	return generates.NewJWTAccessGenerate("", []byte(os.Getenv("APP_SECRET")), jwt.SigningMethodHS512)
}
