package extension

type Content interface {
	Parse([]byte) error
}
