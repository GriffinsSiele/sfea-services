package hydrator

import (
	"fmt"
	"net/http"
	"net/url"
	"strings"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/util"
	"github.com/sirupsen/logrus"
)

type CheckType struct {
	cfg *config.Config
}

func NewCheckType(cfg *config.Config) *CheckType {
	return &CheckType{
		cfg: cfg,
	}
}

func (c *CheckType) Hydrate(name string, checkType *config.CheckType, req *http.Request) (*Item, error) {
	//goland:noinspection HttpUrlsUsage
	selfSchema := "http://"
	if req.TLS != nil {
		selfSchema = "https://"
	}

	item := &Item{
		Code: name,
		Schema: &ItemSchema{
			Consume: c.unwrapSchema(checkType.Schema),
			Produce: checkType.Produce,
		},
		Links: &ItemLinks{
			Self: fmt.Sprintf("%s%s/api/v1/check-types/%s", selfSchema, req.Host, name),
		},
	}

	if source := checkType.Source; source != nil {
		item.Source = &ItemSource{
			Code: source.Code,
		}
	}

	provider := c.cfg.Providers[checkType.Upstream.Provider]
	if provider == nil {
		return nil, fmt.Errorf("check_type required for non-existing provider: %s", checkType.Upstream.Provider)
	}

	providerURL, err := url.Parse(provider.Endpoint)
	if err != nil {
		logrus.WithError(err).Errorf("failed to parse provider url: %v", err)
	}

	item.Provider = &ItemProvider{
		Code: checkType.Upstream.Provider,
		Links: &ItemProviderLinks{
			Self: fmt.Sprintf("%s://%s", providerURL.Scheme, providerURL.Host),
		},
	}

	if checkType.Upstream.KeyDB.Enabled {
		item.Links.Storage = util.StorageURN(checkType.Upstream.KeyDB.Scope)
	}

	if checkType.Upstream.RabbitMQ.Enabled {
		item.Links.Queue = util.QueueURN(checkType.Upstream.RabbitMQ.Scope)
	}

	return item, nil
}

func (c *CheckType) unwrapSchema(schema map[string]any) map[string]any {
	out := make(map[string]any, len(schema))

	for k, v := range schema {
		if vMap, ok := v.(map[string]any); ok {
			out[k] = c.unwrapSchema(vMap)
		} else if vArr, ok := v.([]any); ok {
			res := make([]any, len(vArr))

			for i, item := range vArr {
				if itemMap, ok := item.(map[string]any); ok {
					res[i] = c.unwrapSchema(itemMap)
				} else {
					res[i] = item
				}
			}

			out[k] = res
		} else if vStr, ok := v.(string); ok && k == "$ref" {
			var (
				names      = strings.Split(vStr, "/")
				name       = names[len(names)-1]
				s          = c.cfg.Definitions["schema"].(map[string]any)
				definition = s[name].(map[string]any)
			)

			return definition
		} else {
			out[k] = v
		}
	}

	return out
}

// ---

type Item struct {
	Code     string        `json:"code" yaml:"code"`
	Provider *ItemProvider `json:"provider" yaml:"provider"`
	Source   *ItemSource   `json:"source" yaml:"source"`
	Schema   *ItemSchema   `json:"@schema" yaml:"_schema"`
	Links    *ItemLinks    `json:"@links" yaml:"_links"`
}

type ItemSchema struct {
	Consume map[string]any `json:"consume" yaml:"consume"`
	Produce map[string]any `json:"produce" yaml:"produce"`
}

type ItemLinks struct {
	Self    string `json:"self" yaml:"self"`
	Queue   string `json:"queue" yaml:"queue"`
	Storage string `json:"storage" yaml:"storage"`
}

type ItemSource struct {
	Code string `yaml:"code" json:"code"`
}

type ItemProvider struct {
	Code  string             `json:"code" yaml:"code"`
	Links *ItemProviderLinks `json:"@links" yaml:"_links"`
}

type ItemProviderLinks struct {
	Self string `json:"self" yaml:"self"`
}

type Scope struct {
	Scope string `json:"scope" yaml:"scope"`
}
