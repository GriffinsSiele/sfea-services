package validator

import (
	"fmt"
	"regexp"

	"git.i-sphere.ru/isphere-go-modules/framework/pkg/runtime"
	"github.com/go-playground/validator/v10"
	"github.com/nyaruka/phonenumbers"
)

type Validator struct {
	*validator.Validate
}

func NewValidator() (*Validator, error) {
	t := &Validator{
		validator.New(),
	}

	if err := t.RegisterValidation("phone", t.validatePhone); err != nil {
		return nil, fmt.Errorf("failed to bind validator: %w", err)
	}

	return t, nil
}

var matchValidatorPhoneFromEnv = regexp.MustCompile(`(?m)^%env\((\w+)\)%$`)

func (t *Validator) validatePhone(fl validator.FieldLevel) bool {
	var (
		phoneNumberString = fl.Field().String()
		defaultRegion     = fl.Param()
	)

	if matchValidatorPhoneFromEnv.MatchString(defaultRegion) {
		environmentVariable := matchValidatorPhoneFromEnv.FindStringSubmatch(defaultRegion)
		defaultRegion = runtime.String(environmentVariable[1])
	}

	phoneNumber, err := phonenumbers.Parse(phoneNumberString, defaultRegion)
	if err != nil {
		return false
	}

	return phonenumbers.IsValidNumber(phoneNumber)
}
