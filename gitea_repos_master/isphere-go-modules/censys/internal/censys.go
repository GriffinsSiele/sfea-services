package internal

import (
	"context"
	"fmt"
	"net"
	"strconv"
	"strings"
	"time"
)

type Censys struct {
	client *Client
}

func NewCensys(client *Client) *Censys {
	return &Censys{
		client: client,
	}
}

func (t *Censys) Find(ctx context.Context, ip net.IP) (*CensysResponse, error) {
	var resp CensysResponse
	if err := t.client.GET(ctx, fmt.Sprintf("/api/v2/hosts/%s", ip), &resp); err != nil {
		return nil, fmt.Errorf("failed to execute CensysResponse: %w", err)
	}

	if resp.Status != CensysResponseStatusOK {
		return nil, fmt.Errorf("unexpected status: %s", resp.Status)
	}

	return &resp, nil
}

func (t *Censys) Normalize(response *CensysResponse) ([]Result, error) {
	var results []Result

	resultIP := &ResultIP{
		IP: response.Result.IP,
	}

	if location := response.Result.Location; location != nil {
		resultIP.Country = location.Country
		resultIP.CountryCode = location.CountryCode
		resultIP.Province = location.Province
		resultIP.City = location.City
		resultIP.Timezone = location.Timezone

		if coordinates := location.Coordinates; coordinates != nil {
			var locationTextComponents []string

			if resultIP.Country != "" {
				locationTextComponents = append(locationTextComponents, resultIP.Country)
			}

			if resultIP.Province != "" {
				locationTextComponents = append(locationTextComponents, resultIP.Province)
			}

			if resultIP.City != "" {
				locationTextComponents = append(locationTextComponents, resultIP.City)
			}

			locationText := strings.Join(locationTextComponents, ", ")

			resultIP.Location = &Location{
				Coords: []float64{
					coordinates.Latitude,
					coordinates.Longitude,
				},
				Text: locationText,
			}
		}

		if autonomousSystem := response.Result.AutonomousSystem; autonomousSystem != nil {
			if autonomousSystem.ASN != 0 {
				resultIP.ASN = strconv.Itoa(autonomousSystem.ASN)
			}

			resultIP.Organization = autonomousSystem.Name
		}
	}

	if !resultIP.IsEmpty() {
		results = append(results, resultIP)
	}

	for _, service := range response.Result.Services {
		resultService := &ResultService{
			Service:   service.ServiceName,
			Port:      service.Port,
			Transport: service.TransportProtocol,
		}

		if !resultService.IsEmpty() {
			results = append(results, resultService)
		}
	}

	return results, nil
}

// ---

type CensysResponse struct {
	Code   int                   `json:"code"`
	Status CensysResponseStatus  `json:"status"`
	Result *CensysResponseResult `json:"result"`
}

type CensysResponseResult struct {
	IP                        string                                `json:"ip"`
	Services                  []*CensysResponseResultService        `json:"services"`
	Location                  *CensysResponseResultLocation         `json:"location"`
	LocationUpdatedAt         *time.Time                            `json:"location_updated_at"`
	AutonomousSystem          *CensysResponseResultAutonomousSystem `json:"autonomous_system"`
	AutonomousSystemUpdatedAt *time.Time                            `json:"autonomous_system_updated_at"`
}

type CensysResponseResultService struct {
	Port              int    `json:"port"`
	ServiceName       string `json:"service_name"`
	TransportProtocol string `json:"transport_protocol"`
}

type CensysResponseResultLocation struct {
	Continent   string                                   `json:"continent"`
	Country     string                                   `json:"country"`
	CountryCode string                                   `json:"country_code"`
	City        string                                   `json:"city"`
	PostalCode  string                                   `json:"postal_code"`
	Timezone    string                                   `json:"timezone"`
	Province    string                                   `json:"province"`
	Coordinates *CensysResponseResultLocationCoordinates `json:"coordinates"`
}

type CensysResponseResultLocationCoordinates struct {
	Latitude  float64 `json:"latitude"`
	Longitude float64 `json:"longitude"`
}

type CensysResponseResultAutonomousSystem struct {
	ASN         int    `json:"asn"`
	Description string `json:"description"`
	BGPPrefix   string `json:"bgp_prefix"`
	Name        string `json:"name"`
	CountryCode string `json:"country_code"`
}

// ---

type CensysResponseStatus string

const CensysResponseStatusOK CensysResponseStatus = "OK"
