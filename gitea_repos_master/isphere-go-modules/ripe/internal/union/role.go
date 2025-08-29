package union

import "time"

type Role struct {
	AdminC       string     `json:"admin_c" mapstructure:"admin-c"`
	Created      *time.Time `mapstructure:"created"`
	LastModified *time.Time `json:"last_modified" mapstructure:"last-modified"`
	MntBy        string     `json:"mnt_by" mapstructure:"mnt-by"`
	NicHDL       string     `json:"nic_hdl" mapstructure:"nic-hdl"`
	Remarks      string     `mapstructure:"remarks"`
	Role         string     `mapstructure:"role"`
	Source       string     `mapstructure:"source"`
	TechC        string     `json:"tech_c" mapstructure:"tech-c"`
}

func (t *Role) Type() ItemType {
	return ItemTypeRole
}
