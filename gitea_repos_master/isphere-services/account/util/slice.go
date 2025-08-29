package util

func Contains[T comparable](actual []T, expected /* OR */ ...T) bool {
	for _, actualElement := range actual {
		for _, expectedElement := range expected {
			if expectedElement == actualElement {
				return true
			}
		}
	}

	return false
}
