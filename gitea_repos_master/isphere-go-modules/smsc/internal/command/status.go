package command

import (
	"context"
	"errors"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"os"
	"strconv"

	"github.com/sirupsen/logrus"
)

func Status(ctx context.Context) error {
	uri, err := url.Parse(os.Getenv("SMSC_CLIENT_INFO"))
	if err != nil {
		return fmt.Errorf("failed to parse SMSC_CLIENT_INFO: %w", err)
	}

	uri.RawQuery = url.Values{
		"login": []string{os.Getenv("SMSC_LOGIN")},
		"psw":   []string{os.Getenv("SMSC_PASSWORD")},
	}.Encode()

	resp, err := http.Get(uri.String())
	if err != nil {
		return fmt.Errorf("failed to get info: %w", err)
	}

	defer resp.Body.Close()

	balanceStr, err := io.ReadAll(resp.Body)
	if err != nil {
		return fmt.Errorf("failed to read balance representation: %w", err)
	}

	balance, err := strconv.ParseFloat(string(balanceStr), 64)
	if err != nil {
		return fmt.Errorf("failed to cast balance to float: %w", err)
	}

	logrus.WithFields(logrus.Fields{
		"login":   os.Getenv("SMSC_LOGIN"),
		"balance": balance,
	}).Info("account info")

	if balance < 0.0 {
		return errors.New("balance is to low")
	}

	return nil
}
