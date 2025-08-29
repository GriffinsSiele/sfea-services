package command

import (
	"context"
	"crypto/md5"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"os"

	"github.com/google/uuid"
	"github.com/sirupsen/logrus"
)

func Status(ctx context.Context) error {
	uri, err := url.Parse(os.Getenv("REDSMS_CLIENT_INFO"))
	if err != nil {
		return fmt.Errorf("failed to parse REDSMS_CLIENT_INFO: %w", err)
	}

	ts := uuid.NewString()
	secret := md5.Sum([]byte(ts + os.Getenv("REDSMS_PASSWORD")))

	uri.RawQuery = url.Values{
		"login":  []string{os.Getenv("REDSMS_LOGIN")},
		"ts":     []string{ts},
		"secret": []string{hex.EncodeToString(secret[:])},
	}.Encode()

	res, err := http.Get(uri.String())
	if err != nil {
		return fmt.Errorf("failed to get info: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer res.Body.Close()

	var infoResp InfoResp
	if err = json.NewDecoder(res.Body).Decode(&infoResp); err != nil {
		return fmt.Errorf("failed to decode resp body: %w", err)
	}

	logrus.WithFields(logrus.Fields{
		"success":        infoResp.Success,
		"info.login":     infoResp.Info.Login,
		"info.balance":   infoResp.Info.Balance,
		"info.active":    infoResp.Info.Active,
		"info.overdraft": infoResp.Info.Overdraft,
		"error_message":  infoResp.ErrorMessage,
	}).Info("account info")

	if !infoResp.Success {
		return fmt.Errorf("failed status: %v", infoResp.ErrorMessage)
	}

	if !infoResp.Info.Active {
		return fmt.Errorf("account is inactive")
	}

	return nil
}

type InfoResp struct {
	Success bool `json:"success"`
	Info    struct {
		Login     string  `json:"login"`
		Balance   float64 `json:"balance"`
		Active    bool    `json:"active"`
		Overdraft float64 `json:"overdraft"`
	} `json:"info"`
	ErrorMessage string `json:"error_message"`
}
