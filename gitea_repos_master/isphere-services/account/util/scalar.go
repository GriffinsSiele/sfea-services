package util

func Any[T any](v T) *T {
	return &v
}

func AnyValue[T any](v *T) T {
	var res T

	if v != nil {
		res = *v
	}

	return res
}

func BoolValue(v interface{}) bool {
	if v == nil {
		return false
	}

	boolean, ok := v.(bool)
	if !ok {
		return false
	}

	return boolean
}

func ByteSliceValue(v interface{}) []byte {
	if v == nil {
		return nil
	}

	bytes, ok := v.([]byte)
	if !ok {
		return nil
	}

	return bytes
}

func StringValue(v interface{}) string {
	if v == nil {
		return ""
	}

	chars, ok := v.(string)
	if !ok {
		return ""
	}

	return chars
}
