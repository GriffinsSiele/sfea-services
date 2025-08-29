package util

func OneOf[T comparable](a, b T) T {
	var empty T
	if b != empty {
		return b
	}
	return a
}
