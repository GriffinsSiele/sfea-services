package internal

import "time"

type Date time.Time

func (d *Date) UnmarshalJSON(data []byte) error {
	if len(data) == 0 {
		return nil
	}

	t, err := time.Parse(`"02.01.2006"`, string(data))
	if err != nil {
		return err
	}

	*d = Date(t)
	return nil
}
