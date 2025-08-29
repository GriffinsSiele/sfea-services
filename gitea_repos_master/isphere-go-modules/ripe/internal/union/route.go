package union

import "time"

type Route struct {
	Created      *time.Time `mapstructure:"created"`
	Descr        string     `mapstructure:"descr"`
	LastModified *time.Time `json:"last_modified" mapstructure:"last-modified"`
	MntBy        string     `json:"mnt_by" mapstructure:"mnt-by"`
	Origin       string     `mapstructure:"origin"`
	Route        string     `mapstructure:"route"`
	Source       string     `mapstructure:"source"`
}

func (t Route) Type() ItemType {
	return ItemTypeRoute
}
