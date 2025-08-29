package clients

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"os"

	http "github.com/Danny-Dasilva/fhttp"
)

type Kafka struct {
}

func NewKafka() (*Kafka, error) {
	return &Kafka{}, nil
}

func (k *Kafka) Produce(ctx context.Context, topic string, messages ...string) error {
	u := fmt.Sprintf("%s/topics/%s", os.Getenv("KAFKA_REST_ADDR"), topic)

	var request requestStruct
	for _, message := range messages {
		request.Records = append(request.Records, recordStruct{Value: message})
	}

	bodyBytes, err := json.Marshal(request)
	if err != nil {
		return fmt.Errorf("failed to marshal request: %w", err)
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, u, bytes.NewReader(bodyBytes))
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("Content-Type", "application/vnd.kafka.json.v2+json")
	req.Header.Set("Accept", "application/vnd.kafka.v2+json")

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return fmt.Errorf("failed to send request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("unexpected status code on kafka: %d", resp.StatusCode)
	}

	return nil
}

type requestStruct struct {
	Records []recordStruct `json:"records"`
}
type recordStruct struct {
	Value string `json:"value"`
}
