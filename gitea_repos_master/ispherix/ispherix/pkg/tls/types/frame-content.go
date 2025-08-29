package types

type FrameContent interface {
	Parse([]byte) error
}
