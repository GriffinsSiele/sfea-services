package internal

import (
	"bytes"
	"fmt"
	"reflect"
	"strconv"

	"github.com/graphql-go/graphql"
	"github.com/graphql-go/graphql/language/ast"
	"github.com/graphql-go/graphql/language/kinds"
	"github.com/nyaruka/phonenumbers"
	"github.com/sirupsen/logrus"
)

type TelType struct {
	*graphql.Scalar
}

func NewTelType() *TelType {
	var t TelType

	t.Scalar = graphql.NewScalar(graphql.ScalarConfig{
		Name:         "Tel",
		Serialize:    t.serialize,
		ParseValue:   t.parseValue,
		ParseLiteral: t.parseLiteral,
	})

	return &t
}

func (t *TelType) serialize(value any) any {
	if phoneCoerce := t.parseValue(value); phoneCoerce != nil {
		if phone, ok := phoneCoerce.(*phonenumbers.PhoneNumber); ok {
			return phonenumbers.Format(phone, phonenumbers.E164)
		}
	}

	return nil
}

func (t *TelType) parseValue(value any) any {
	switch v := value.(type) {
	case string:
		number, err := phonenumbers.Parse(v, "RU")

		if err != nil {
			logrus.WithError(err).Error("failed to parse phone number: %w", err)

			return nil
		}

		return number

	case *string:
		return t.parseValue(*v)

	case int:
		return t.parseValue(strconv.Itoa(v))

	case *int:
		return t.parseValue(*v)

	case *Tel:
		return v.PhoneNumber

	case *phonenumbers.PhoneNumber:
		return v

	default:
		logrus.WithField("value_type", reflect.TypeOf(value)).WithField("value", value).Warn("cannot coerce tel by known types")

		return nil
	}
}

func (t *TelType) parseLiteral(valueAST ast.Value) any {
	kind := valueAST.GetKind()

	switch kind {
	case kinds.StringValue:
		numberString := valueAST.GetValue().(string)
		number, err := phonenumbers.Parse(numberString, "RU")

		if err != nil {
			logrus.WithError(err).WithField("number", numberString).Error("cannot parse phone number")

			return nil
		}

		if !phonenumbers.IsValidNumber(number) {
			logrus.WithField("number", numberString).Warnf("phone number is invalid")

			return nil
		}

		return number

	default:
		return nil
	}
}

// ---

type Tel struct {
	*phonenumbers.PhoneNumber
}

func (t *Tel) UnmarshalJSON(numberBytes []byte) error {
	numberBytes = bytes.Trim(numberBytes, `"`)
	
	numberString := string(numberBytes)
	if numberString == "" {
		return nil
	}

	number, err := phonenumbers.Parse(numberString, "RU")
	if err != nil {
		return fmt.Errorf("parse tel: %w", err)
	}

	t.PhoneNumber = number

	return nil
}
