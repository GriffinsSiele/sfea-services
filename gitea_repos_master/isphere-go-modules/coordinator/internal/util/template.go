package util

import (
	"bytes"
	"encoding/hex"
	"encoding/json"
	"fmt"
	htmltemplate "html/template"
	"net/http"
	"net/url"
	"os"
	"reflect"
	"strconv"
	"strings"
	"text/template"
	"time"

	"github.com/Masterminds/sprig/v3"
	"github.com/davecgh/go-spew/spew"
	"github.com/nyaruka/phonenumbers"
	"github.com/sirupsen/logrus"
	"github.com/ttacon/libphonenumber"
	"golang.org/x/text/language"
	"gopkg.in/yaml.v2"
)

func NewTemplate() *template.Template {
	tmpl := template.New("")

	f := template.FuncMap{
		// globals
		"AsDate":            asDate,
		"AsDateLayout":      asDateLayout,
		"AsTime":            asTime,
		"Base64FromPG":      base64FromPG,
		"Defined":           defined,
		"Encode":            encode,
		"EncodeJSON":        encodeJSON,
		"Env":               envFunc,
		"FmtInterval":       fmtInterval,
		"FromYAML":          fromYAML,
		"GetSafe":           getSafe,
		"GetSafeMap":        getSafeMap,
		"In":                inFn,
		"IsEmpty":           isEmpty,
		"Phone":             phone,
		"PhoneNum":          phoneNum,
		"PhoneNumber":       phoneNumber,
		"PhoneNumberNumber": phoneNumberNumber,
		"PhoneNumberRegion": phoneNumberRegion,
		"Print":             printFn,
		"QueryArguments":    queryArguments,
		"Quote":             quote,
		"Region":            region,
		"RegionByCode":      regionByCode,
		"ResponseStatus":    responseStatus,
		"ResponseMessage":   responseMessage,
		"Root":              root,
		"Sprintf":           fmt.Sprintf,
		"Int":               integerValue,
		"StringsJoin":       stringsJoinFn,
		"StringsJoinInline": stringsJoinInlineFn,
		"StringsSubstring":  stringsSubstring,
		"ToJSON":            toJSON,
		"UnsafeField":       unsafeField,
		"UnsafeProperty":    unsafeProperty,

		// debug
		"DD": ddFn,
	}

	for k, v := range sprig.FuncMap() {
		f[k] = v
	}

	tmpl.Funcs(f)

	return tmpl
}

// globals

func unsafeField(k string, v any) string {
	if v == nil {
		return ""
	}
	quoted, _ := json.Marshal(v)
	return fmt.Sprintf("%s: %s", k, string(quoted))
}

func unsafeProperty(obj map[string]any, key string, propertyPath ...string) string {
	if obj == nil {
		return ""
	}

	var result string
	cursor := obj

	for _, property := range propertyPath {
		switch v := cursor[property].(type) {
		case map[string]any:
			cursor = v
		case string:
			result = v
		case float64:
			result = strconv.FormatFloat(v, 'f', -1, 64)
		default:
			logrus.WithFields(logrus.Fields{
				"type":  fmt.Sprintf("%T", v),
				"value": v,
			}).Warning("unsupported nested property")
		}
	}

	if result == "" {
		return ""
	}

	return fmt.Sprintf("%s: %s", key, strconv.Quote(result))
}

func envFunc(k string) string {
	return os.Getenv(k)
}

func queryArguments(params ...string) string {
	args := make([]string, 0, len(params)/2)
	for i := 0; i < len(params); i += 2 {
		args = append(args, fmt.Sprintf("%s=%s", params[i], params[i+1]))
	}
	return strings.Join(args, "&")
}

func asDate(v string) (string, error) {
	t, err := time.Parse("2006-01-02T15:04:05", v)
	if err != nil {
		return "", err
	}

	return t.Format("2006-01-02"), nil
}

func asDateLayout(v, layout string) (string, error) {
	t, err := time.Parse("2006-01-02", v)
	if err != nil {
		return "", err
	}

	return t.Format(layout), nil
}

func asTime(v string) (string, error) {
	t, err := time.Parse("2006-01-02T15:04:05", v)
	if err != nil {
		return "", err
	}

	return t.Format("15:04:05"), nil
}

func defined(name string, data any) bool {
	v := reflect.ValueOf(data)

	if v.Kind() == reflect.Ptr {
		v = v.Elem()
	}

	if v.Kind() != reflect.Struct {
		return false
	}

	return v.FieldByName(name).IsValid()
}

func encode(args ...string) string {
	v := url.Values{}
	for i := 0; i < len(args); i += 2 {
		v.Add(args[i], args[i+1])
	}

	return v.Encode()
}

func encodeJSON(args ...string) string {
	out := make(map[string]any)
	for i := 0; i < len(args); i += 2 {
		out[fmt.Sprintf("%v", args[i])] = args[i+1]
	}
	serialized, _ := json.Marshal(out)
	return string(serialized)
}

func inFn(v string, values ...string) bool {
	for _, value := range values {
		if value == v {
			return true
		}
	}

	return false
}

func isEmpty(t any) bool {
	if t == nil {
		return true
	}
	switch v := t.(type) {
	case string:
		return v == ""
	case map[string]any:
		return len(v) == 0
	default:
		logrus.WithFields(logrus.Fields{
			"type": fmt.Sprintf("%T", v),
		}).Warn("default empty strategy used")
		return v == nil
	}
}

func printFn(v float64) string {
	return strconv.Itoa(int(v))
}

func stringsJoinInlineFn(sep string, elems ...string) string {
	slice := make([]any, len(elems))
	for i, elem := range elems {
		slice[i] = elem
	}

	return stringsJoinFn(slice, sep)
}

func stringsJoinFn(elems []any, sep string) string {
	var elemsStr []string

	for _, elem := range elems {
		switch v := elem.(type) {
		case string:
			elemsStr = append(elemsStr, v)
		case int:
			elemsStr = append(elemsStr, strconv.Itoa(v))
		default:
			continue
		}
	}

	return strings.Join(elemsStr, sep)
}

func phone(v float64) string {
	return fmt.Sprintf("+%.0f", v)
}

func phoneNumber(input any) string {
	var in string
	switch v := input.(type) {
	case string:
		in = v
	case float64:
		in = strconv.FormatFloat(v, 'f', -1, 64)
	default:
		return ""
	}

	num, err := libphonenumber.Parse(in, "RU")
	if err != nil {
		return ""
	}

	return libphonenumber.Format(num, libphonenumber.E164)
}

func phoneNumberNumber(input any) string {
	var in string
	switch v := input.(type) {
	case string:
		in = v
	case float64:
		in = strconv.FormatFloat(v, 'f', -1, 64)
	default:
		return ""
	}

	num, err := libphonenumber.Parse(in, "RU")
	if err != nil {
		return ""
	}

	countryCode := strconv.Itoa(libphonenumber.GetCountryCodeForRegion(libphonenumber.GetRegionCodeForNumber(num)))
	formatted := libphonenumber.Format(num, libphonenumber.E164)
	return strings.TrimPrefix(formatted, "+"+countryCode)
}

func phoneNumberRegion(input any) string {
	var in string
	switch v := input.(type) {
	case string:
		in = v
	case float64:
		in = strconv.FormatFloat(v, 'f', -1, 64)
	default:
		return ""
	}

	num, err := libphonenumber.Parse(in, "RU")
	if err != nil {
		return ""
	}

	return strconv.Itoa(libphonenumber.GetCountryCodeForRegion(libphonenumber.GetRegionCodeForNumber(num)))
}

func phoneNum(input any) string {
	number := phoneNumber(input)
	return strings.TrimPrefix(number, "+")
}

func regionByCode(code any) (*regionType, error) {
	var codeInt int
	switch v := code.(type) {
	case string:
		codeInt, _ = strconv.Atoi(v)
	case float64:
		codeInt = int(v)
	default:
		return nil, fmt.Errorf("invalid code: %v", code)
	}

	if code == 0 {
		return nil, nil
	}

	reqData := struct {
		Query     string            `json:"query"`
		Variables map[string]string `json:"variables"`
	}{
		// language=GraphQL
		Query: `query ($series: smallint!) {
    fmsdb_region_codes_by_pk(series: $series) {
        code
		region
    }
}
`,
		Variables: map[string]string{
			"series": strconv.Itoa(codeInt),
		},
	}
	reqBytes, err := json.Marshal(reqData)
	if err != nil {
		return nil, fmt.Errorf("failed to marshal: %w", err)
	}
	req, err := http.NewRequest(http.MethodPost, os.Getenv("MODULE_HASURA_ENDPOINT"), bytes.NewReader(reqBytes))
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-Hasura-Admin-Secret", os.Getenv("MODULE_HEADERS_X_HASURA_ADMIN_SECRET"))
	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, fmt.Errorf("failed to perform request: %w", err)
	}
	defer resp.Body.Close()
	var respData struct {
		Data struct {
			Regions regionType `json:"fmsdb_region_codes_by_pk"`
		} `json:"data"`
	}
	if err = json.NewDecoder(resp.Body).Decode(&respData); err != nil {
		return nil, fmt.Errorf("failed to unmarshal: %w", err)
	}
	return &respData.Data.Regions, nil
}

func responseStatus(s string) string {
	switch s {
	case "200":
		return "ok"
	case "201":
		return "incomplete"
	default:
		return "error"
	}
}

func responseMessage(s string) string {
	switch s {
	case "200":
		return "found"
	case "204":
		return "nodata"
	default:
		return "error"
	}
}

type regionType struct {
	Code   int    `json:"code"`
	Region string `json:"region"`
}

func region(phone string) (string, error) {
	if !strings.HasPrefix(phone, "+") {
		phone = "+" + phone
	}
	num, err := phonenumbers.Parse("+"+phone, "")
	if err != nil {
		return "", fmt.Errorf("failed to parse phone number: %w", err)
	}
	num.RawInput = &phone
	if !phonenumbers.IsValidNumber(num) {
		return "", fmt.Errorf("invalid phone number: %s", phone)
	}
	country, err := language.ParseRegion(phonenumbers.GetRegionCodeForNumber(num))
	if err != nil {
		return "", fmt.Errorf("failed to parse region for phone number: %w", err)
	}
	return strings.ToLower(country.String()), nil
}

func stringsSubstring(str string, offset, length int) string {
	runes := []rune(str)

	if offset > len(runes) {
		return ""
	}

	if offset+length > len(runes) {
		length = len(runes) - offset
	}

	return string(runes[offset : offset+length])
}

func quote(v any) htmltemplate.JS {
	if vStr, ok := v.(string); ok {
		vStr = strings.TrimSpace(vStr)

		if vStr != "" {
			// unquote slashes
			v = strings.ReplaceAll(vStr, "\\", "")
		}
	}

	serialized, err := json.Marshal(v)
	if err != nil {
		logrus.WithError(err).Errorf("marshal quoted value: %v: %v", v, err)
	}

	return htmltemplate.JS(serialized)
}

func root(v any, expectedKey string) any {
	if m, ok := v.(map[string]any); ok {
		for k, v := range m {
			if k == "" || k == expectedKey {
				return v
			}
		}
	}

	return nil
}

func toJSON(v any) (htmltemplate.JS, error) {
	serialized, err := json.Marshal(v)
	if err != nil {
		return "", fmt.Errorf("failed to marshal obj: %w", err)
	}

	return htmltemplate.JS(serialized), nil
}

func fromYAML(v string) (map[string]any, error) {
	var res map[string]any
	if err := yaml.Unmarshal([]byte(v), &res); err != nil {
		return nil, fmt.Errorf("failed to unmarshal value: %w", err)
	}

	return res, nil
}

func getSafe(where map[string]any, k string) any {
	if v, ok := where[k]; ok {
		return v
	}

	return ""
}

func getSafeMap(where any, k string) any {
	whereMap, ok := where.(map[string]any)
	if !ok {
		return nil
	}
	if v, ok := whereMap[k]; ok {
		return v
	}
	return nil
}

func fmtInterval(v string) string {
	var (
		periodBuilder strings.Builder
		periodYears,
		periodMonths,
		periodDays int
	)

	//goland:noinspection GoUnhandledErrorResult
	fmt.Sscanf(v, "%d years", &periodYears)
	periodBuilder.WriteString(fmt.Sprintf("%d г ", periodYears))

	//goland:noinspection GoUnhandledErrorResult
	fmt.Sscanf(v, "%d mons", periodMonths)
	periodBuilder.WriteString(fmt.Sprintf("%d м ", periodMonths))

	//goland:noinspection GoUnhandledErrorResult
	fmt.Sscanf(v, "%d days", periodDays)
	periodBuilder.WriteString(fmt.Sprintf("%d д", periodDays))

	return periodBuilder.String()
}

func base64FromPG(data, mimeType string) (string, error) {
	if len(data) < 2 || mimeType == "" {
		return "", nil
	}

	data = data[2:]
	dataBytes := make([]byte, len(data)/2)
	if _, err := hex.Decode(dataBytes, []byte(data)); err != nil {
		return "", fmt.Errorf("failed to decode hex: %w", err)
	}

	return fmt.Sprintf("data:%s;base64,%s", mimeType, dataBytes), nil
}

func integerValue(val any) int {
	switch v := val.(type) {
	case int:
		return v
	case int32:
		return int(v)
	case int64:
		return int(v)
	case float32:
		return int(v)
	case float64:
		return int(v)
	case string:
		vInt, _ := strconv.Atoi(v)
		return vInt
	default:
		return 0
	}
}

// debug

func ddFn(v ...any) any {
	spew.Dump(v...)
	os.Exit(-1)

	return nil
}
