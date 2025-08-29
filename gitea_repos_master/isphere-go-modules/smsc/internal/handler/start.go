package handler

import (
	"bytes"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"os"

	"git.i-sphere.ru/isphere-go-modules/ripe/internal/dto"
	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"
)

func Start(c *gin.Context) {
	var msg dto.StartReq

	if err := c.ShouldBindJSON(&msg); err != nil {
		_ = c.AbortWithError(http.StatusBadRequest, fmt.Errorf("failed to bind input: %w", err))

		return
	}

	resData := url.Values{
		"login":  []string{os.Getenv("SMSC_LOGIN")},
		"psw":    []string{"***"},
		"phones": []string{msg.Tel},
		"hlr":    []string{"1"},
		"format": []string{"json"},
	}

	logrus.WithFields(logrus.Fields{
		"headers": c.Request.Header,
		"data":    resData,
		"url":     os.Getenv("SMSC_ENDPOINT"),
	}).Info("send request")

	resData["psw"] = []string{os.Getenv("SMSC_PASSWORD")}

	res, err := http.PostForm(os.Getenv("SMSC_ENDPOINT"), resData)
	if err != nil {
		_ = c.AbortWithError(http.StatusBadGateway, fmt.Errorf("failed to send gateway request: %w", err))

		return
	}

	//goland:noinspection GoUnhandledErrorResult
	defer res.Body.Close()

	content, err := io.ReadAll(res.Body)
	if err != nil {
		_ = c.AbortWithError(http.StatusInternalServerError, fmt.Errorf("failed to read gateway response: %w", err))

		return
	}

	logrus.WithFields(logrus.Fields{
		"response": string(content),
	}).Info("received response")

	var (
		status string
		count  int
		id     string
	)

	if _, err = fmt.Sscanf(string(content), "%s - %d SMS, ID - %s", &status, &count, &id); err != nil {
		_ = c.AbortWithError(http.StatusConflict, fmt.Errorf("gateway error: %s", content))

		return
	}

	if status != "OK" {
		_ = c.AbortWithError(http.StatusConflict, errors.New("gateway response is not an OK"))

		return
	}

	if messageID := c.GetHeader("X-Message-Id"); messageID != "" {
		if err := escape(id, messageID); err != nil {
			_ = c.AbortWithError(http.StatusInternalServerError, fmt.Errorf("cannot save id mapping: %w", err))

			return
		}
	}

	c.JSON(http.StatusAccepted, gin.H{"id": id})
}

func escape(key, value string) error {
	// language=GraphQL
	query := `
mutation($scope: String!, $key: String!, $value: String!) {
  insert_kv(objects: [{
    scope: $scope, 
    key: $key, 
    value: $value
  }]) {
    returning {
      id
    }
  }
}
`
	data := map[string]any{
		"query": query,
		"variables": map[string]any{
			"scope": "smsc",
			"key":   key,
			"value": value,
		},
	}

	serialized, err := json.Marshal(data)
	if err != nil {
		return fmt.Errorf("failed to marshal escaped data: %w", err)
	}

	req, err := http.NewRequest(http.MethodPost, os.Getenv("HASURA_ENDPOINT"), bytes.NewReader(serialized))
	if err != nil {
		return fmt.Errorf("failed to create http request: %w", err)
	}

	req.Header.Set("X-Hasura-Access-Key", os.Getenv("HASURA_TOKEN"))

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return fmt.Errorf("failed to save key/value: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return errors.New("unexpected status code")
	}

	return nil
}

func capture(key string) (string, error) {
	// language=GraphQL
	query := `
query($scope: String!, $key: String!) {
  kv(where: {
    _and: [
      {
        scope: {_eq: $scope}
      }, 
      {
        key: {_eq: $key}
      }
    ]
  }, order_by: [
    {created_at: desc}
  ], limit: 1) {
    value
  }
}

`
	data := map[string]any{
		"query": query,
		"variables": map[string]any{
			"scope": "smsc",
			"key":   key,
		},
	}

	serialized, err := json.Marshal(data)
	if err != nil {
		return "", fmt.Errorf("failed to marshal captured data: %w", err)
	}

	req, err := http.NewRequest(http.MethodPost, os.Getenv("HASURA_ENDPOINT"), bytes.NewReader(serialized))
	if err != nil {
		return "", fmt.Errorf("failed to create http request: %w", err)
	}

	req.Header.Set("X-Hasura-Access-Key", os.Getenv("HASURA_TOKEN"))

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return "", fmt.Errorf("failed to save key/value: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return "", errors.New("unexpected status code")
	}

	var res CaptureResp
	if err := json.NewDecoder(resp.Body).Decode(&res); err != nil {
		return "", fmt.Errorf("failed to unmarshal body: %w", err)
	}

	for _, kv := range res.Data.KV {
		return kv.Value, nil
	}

	return "", fmt.Errorf("nothing results found")
}

type CaptureResp struct {
	Data struct {
		KV []struct {
			Value string `json:"value"`
		} `json:"kv"`
	} `json:"data"`
}
