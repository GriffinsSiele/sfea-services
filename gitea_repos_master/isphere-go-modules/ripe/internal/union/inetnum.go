package union

import "time"

type InetNum struct {
	AdminC       string     `json:"admin_c" mapstructure:"admin-c"`
	Country      string     `mapstructure:"country"`
	Created      *time.Time `mapstructure:"created"`
	Descr        string     `mapstructure:"descr"`
	InetNum      string     `mapstructure:"inetnum"`
	LastModified *time.Time `json:"last_modified" mapstructure:"last-modified"`
	NetName      string     `mapstructure:"netname"`
	Remarks      string     `mapstructure:"remarks"`
	Source       string     `mapstructure:"source"`
	Status       string     `mapstructure:"status"`
	TechC        string     `json:"tech_c" mapstructure:"tech-c"`
}

func (t *InetNum) Type() ItemType {
	return ItemTypeInetNum
}
