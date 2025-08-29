package phonenumbers

import (
	"bytes"
	"context"
	"errors"
	"fmt"
	"log/slog"
	"strconv"
	"strings"

	"git.i-sphere.ru/isphere-go-modules/phone/pkg/models"
	"git.i-sphere.ru/isphere-go-modules/phone/pkg/rossvyaz"
	"github.com/nyaruka/phonenumbers"
	"golang.org/x/text/language"
	"golang.org/x/text/language/display"
)

type Phonenumbers struct {
	rossvyaz *rossvyaz.Rossvyaz
}

func NewPhonenumbers(rossvyaz *rossvyaz.Rossvyaz) *Phonenumbers {
	return &Phonenumbers{
		rossvyaz: rossvyaz,
	}
}

func (p *Phonenumbers) Parse(ctx context.Context, rawData string, source []byte) (*models.Output, error) {
	num, err := phonenumbers.Parse(rawData, "")
	if err != nil {
		return nil, fmt.Errorf("failed to parse phone: %w", err)
	}

	num.RawInput = &rawData

	if !phonenumbers.IsValidNumber(num) {
		return nil, errors.New("number is not a valid")
	}

	var (
		log    = slog.With("source", rawData)
		output models.Output
	)

	if source, err := p.resolveSource(num); err != nil {
		log.With("error", err).WarnContext(ctx, "cannot resolve source")
	} else if source != "" {
		output.Source = &source
	}

	if typ, err := p.resolveType(num); err != nil {
		log.With("error", err).WarnContext(ctx, "cannot resolve type")
	} else if typ != "" {
		output.Type = &typ
	}

	if provider, err := p.resolveProvider(num); err != nil {
		log.With("error", err).WarnContext(ctx, "cannot resolve provider")
	} else if provider != "" {
		output.Provider = &provider
	}

	if phone, err := p.resolvePhone(num); err != nil {
		log.With("error", err).WarnContext(ctx, "cannot resolve phone")
	} else if phone != "" {
		output.Phone = &phone
	}

	if countryCode, err := p.resolveCountryCode(num); err != nil {
		log.With("error", err).WarnContext(ctx, "cannot resolve country code")
	} else if countryCode != "" {
		output.CountryCode = &countryCode
	}

	if country, err := p.resolveCountry(num); err != nil {
		log.With("error", err).WarnContext(ctx, "cannot resolve country")
	} else if country != "" {
		output.Country = &country
	}

	if cityCode, number, err := p.resolveCityCodeWithNumber(num); err != nil {
		log.With("error", err).WarnContext(ctx, "cannot resolve city code with number")
	} else {
		if cityCode != "" {
			output.CityCode = &cityCode
		}
		if number != "" {
			output.Number = &number
		}
	}

	if city, err := p.resolveCity(num); err != nil {
		log.With("error", err).WarnContext(ctx, "cannot resolve city")
	} else if city != "" {
		output.City = &city
	}

	if extension, err := p.resolveExtension(num); err != nil {
		log.With("error", err).WarnContext(ctx, "cannot resolve extension")
	} else if extension != "" {
		output.Extension = &extension
	}

	if timezone, err := p.resolveTimezone(num); err != nil {
		log.With("error", err).WarnContext(ctx, "cannot resolve timezone")
	} else if timezone != "" {
		output.Timezone = &timezone
	}

	if bytes.Compare(source, []byte("rossvyaz")) == 0 && output.Phone != nil {
		if result, err := p.rossvyaz.FindOneByPhone(ctx, *output.Phone); err != nil {
			log.With("error", err).WarnContext(ctx, "cannot find by phone")
		} else {
			if result.Operator != "" {
				output.Provider = &result.Operator
			}
			if len(result.Regions) > 0 {
				output.Region = result.Regions
				city := strings.Join(result.Regions, ", ")
				output.City = &city
			}
			if result.RegionCode > 0 {
				output.RegionCode = &result.RegionCode
			}
		}
	}

	return &output, nil
}

func (p *Phonenumbers) resolveSource(num *phonenumbers.PhoneNumber) (string, error) {
	return num.GetRawInput(), nil
}

func (p *Phonenumbers) resolveType(num *phonenumbers.PhoneNumber) (string, error) {
	switch phonenumbers.GetNumberType(num) {
	case phonenumbers.FIXED_LINE:
		return "fixed line", nil
	case phonenumbers.MOBILE:
		return "mobile", nil
	case phonenumbers.FIXED_LINE_OR_MOBILE:
		return "fixed line|mobile", nil
	case phonenumbers.TOLL_FREE:
		return "toll free", nil
	case phonenumbers.PREMIUM_RATE:
		return "premium rate", nil
	case phonenumbers.SHARED_COST:
		return "shared cost", nil
	case phonenumbers.VOIP:
		return "voip", nil
	case phonenumbers.PERSONAL_NUMBER:
		return "personal number", nil
	case phonenumbers.PAGER:
		return "pager", nil
	case phonenumbers.UAN:
		return "uan", nil
	case phonenumbers.VOICEMAIL:
		return "voicemail", nil
	case phonenumbers.UNKNOWN:
		return "unknown", nil
	default:
		return "", fmt.Errorf("unsupported phone type: %v", phonenumbers.GetNumberType(num))
	}
}

func (p *Phonenumbers) resolveProvider(num *phonenumbers.PhoneNumber) (string, error) {
	return phonenumbers.GetCarrierForNumber(num, "en")
}

func (p *Phonenumbers) resolvePhone(num *phonenumbers.PhoneNumber) (string, error) {
	return phonenumbers.Format(num, phonenumbers.E164), nil
}

func (p *Phonenumbers) resolveCountryCode(num *phonenumbers.PhoneNumber) (string, error) {
	return strconv.Itoa(phonenumbers.GetCountryCodeForRegion(phonenumbers.GetRegionCodeForNumber(num))), nil
}

func (p *Phonenumbers) resolveCountry(num *phonenumbers.PhoneNumber) (string, error) {
	if reg, err := language.ParseRegion(phonenumbers.GetRegionCodeForNumber(num)); err == nil {
		return reg.String(), nil
	} else {
		return "", fmt.Errorf("failed to parse region: %w", err)
	}
}

func (p *Phonenumbers) resolveCityCodeWithNumber(num *phonenumbers.PhoneNumber) (string, string, error) {
	var (
		national       = phonenumbers.GetNationalSignificantNumber(num)
		cityCode       string
		number         string
		cityCodeLength = phonenumbers.GetLengthOfNationalDestinationCode(num)
	)

	if cityCodeLength > 0 {
		cityCode = national[:cityCodeLength]
		number = national[cityCodeLength:]
	} else {
		cityCode = ""
		number = national
	}

	return cityCode, number, nil
}

func (p *Phonenumbers) resolveCity(num *phonenumbers.PhoneNumber) (string, error) {
	geocoded, err := phonenumbers.GetGeocodingForNumber(num, "en")
	if err != nil {
		return "", fmt.Errorf("failed to geocoding: %w", err)
	}

	reg, err := language.ParseRegion(phonenumbers.GetRegionCodeForNumber(num))
	if err != nil {
		return "", fmt.Errorf("failed to parse region: %w", err)
	}

	langT, err := language.Parse("en")
	if err != nil {
		return "", fmt.Errorf("failed to parse en: %w", err)
	}

	if display.Regions(langT).Name(reg) != geocoded {
		return geocoded, nil
	}

	return "", nil
}

func (p *Phonenumbers) resolveExtension(num *phonenumbers.PhoneNumber) (string, error) {
	return num.GetExtension(), nil
}

func (p *Phonenumbers) resolveTimezone(num *phonenumbers.PhoneNumber) (string, error) {
	if timezones, err := phonenumbers.GetTimezonesForNumber(num); err == nil && len(timezones) > 0 {
		return timezones[0], nil
	} else {
		return "", fmt.Errorf("failed to detect timezone: %w", err)
	}
}
