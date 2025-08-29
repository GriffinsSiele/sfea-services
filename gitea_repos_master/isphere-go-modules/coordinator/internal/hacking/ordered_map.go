package hacking

import (
	"fmt"
	"reflect"

	"github.com/ake-persson/mapslice-json"
	"github.com/iancoleman/strcase"
	"gopkg.in/yaml.v2"
)

func CastMapSliceAsRecordsYAML(mapSlices *[]yaml.MapSlice) ([]any, error) {
	records := make([]any, len(*mapSlices))

	for i, mapSlice := range *mapSlices {
		mapSlice := mapSlice
		record, err := castMapSliceAsRecordYAML(&mapSlice)

		if err != nil {
			return nil, fmt.Errorf("failed to cast map slice as record: %w", err)
		}

		records[i] = record
	}

	return records, nil
}

func CastMapSliceAsRecordsJSON(mapSlices *[]mapslice.MapSlice) ([]any, error) {
	records := make([]any, len(*mapSlices))

	for i, mapSlice := range *mapSlices {
		mapSlice := mapSlice
		record, err := castMapSliceAsRecordJSON(&mapSlice)

		if err != nil {
			return nil, fmt.Errorf("failed to cast map slice as record: %w", err)
		}

		records[i] = record
	}

	return records, nil
}

func castMapSliceAsRecordYAML(mapSlice *yaml.MapSlice) (any, error) {
	var (
		fields        = make([]reflect.StructField, len(*mapSlice))
		mappingValues = make([]any, len(*mapSlice))
	)

	for i, mapItem := range *mapSlice {
		mappingValues[i] = mapItem.Value

		if v, ok := mappingValues[i].([]any); ok && len(v) > 0 {
			if _, ok := v[0].(yaml.MapSlice); ok {
				newValues := make([]any, len(v))

				for j, item := range v {
					if itemV, ok := item.(yaml.MapSlice); ok {
						newValue, err := castMapSliceAsRecordYAML(&itemV)
						if err != nil {
							return nil, fmt.Errorf("failed to cast nested value as new value: %w", err)
						}

						newValues[j] = newValue
					}
				}

				mappingValues[i] = newValues
			}
		} else if v, ok := mappingValues[i].(yaml.MapSlice); ok {
			newValue, err := castMapSliceAsRecordYAML(&v)
			if err != nil {
				return nil, fmt.Errorf("failed to cast single nested value: %w", err)
			}

			mappingValues[i] = newValue
		}

		if mappingValues[i] == nil {
			mappingValues[i] = ""
		}

		fields[i] = reflect.StructField{
			Name: strcase.ToCamel(mapItem.Key.(string)),
			Tag:  reflect.StructTag(fmt.Sprintf(`json:"%[1]s" yaml:"%[1]s"`, mapItem.Key.(string))),
			Type: reflect.TypeOf(mappingValues[i]),
		}
	}

	var (
		typ   = reflect.StructOf(fields)
		value = reflect.New(typ).Elem()
	)

	for i, mappingValue := range mappingValues {
		value.Field(i).Set(reflect.ValueOf(mappingValue))
	}

	return value.Addr().Interface(), nil
}

func castMapSliceAsRecordJSON(mapSlice *mapslice.MapSlice) (any, error) {
	var (
		fields        = make([]reflect.StructField, len(*mapSlice))
		mappingValues = make([]any, len(*mapSlice))
	)

	for i, mapItem := range *mapSlice {
		mappingValues[i] = mapItem.Value

		if v, ok := mappingValues[i].([]any); ok && len(v) > 0 {
			if _, ok := v[0].(mapslice.MapSlice); ok {
				newValues := make([]any, len(v))

				for j, item := range v {
					if itemV, ok := item.(mapslice.MapSlice); ok {
						newValue, err := castMapSliceAsRecordJSON(&itemV)
						if err != nil {
							return nil, fmt.Errorf("failed to cast nested value as new value: %w", err)
						}

						newValues[j] = newValue
					}
				}

				mappingValues[i] = newValues
			}
		} else if v, ok := mappingValues[i].(mapslice.MapSlice); ok {
			newValue, err := castMapSliceAsRecordJSON(&v)
			if err != nil {
				return nil, fmt.Errorf("failed to cast single nested value: %w", err)
			}

			mappingValues[i] = newValue
		}

		fields[i] = reflect.StructField{
			Name: strcase.ToCamel(mapItem.Key.(string)),
			Tag:  reflect.StructTag(fmt.Sprintf(`json:"%[1]s" yaml:"%[1]s"`, mapItem.Key.(string))),
			Type: reflect.TypeOf(mappingValues[i]),
		}
	}

	var (
		typ   = reflect.StructOf(fields)
		value = reflect.New(typ).Elem()
	)

	for i, mappingValue := range mappingValues {
		value.Field(i).Set(reflect.ValueOf(mappingValue))
	}

	return value.Addr().Interface(), nil
}
