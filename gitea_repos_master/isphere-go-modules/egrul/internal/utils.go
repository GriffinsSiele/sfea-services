package internal

func CleanSlice[T comparable](elems []T) []T {
	cleaned := make([]T, 0, len(elems))
	var cleanVal T

	for _, elem := range elems {
		if cleanVal != elem {
			cleaned = append(cleaned, elem)
		}
	}

	return cleaned
}
