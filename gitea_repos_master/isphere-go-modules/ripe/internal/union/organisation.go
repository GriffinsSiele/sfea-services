package union

import "time"

type Organisation struct {
	AbuseC       string     `json:"abuse_c" mapstructure:"abuse-c"`
	Address      string     `mapstructure:"address"`
	AdminC       string     `json:"admin_c" mapstructure:"admin-c"`
	Country      string     `mapstructure:"country"`
	Created      *time.Time `mapstructure:"created"`
	FaxNo        string     `json:"fax_no" mapstructure:"fax-no"`
	LastModified *time.Time `json:"last_modified" mapstructure:"last-modified"`
	MntBy        string     `json:"mnt_by" mapstructure:"mnt-by"`
	MntRef       string     `json:"mnt_ref" mapstructure:"mnt-ref"`
	Organisation string     `mapstructure:"organisation"`
	OrgName      string     `json:"org_name" mapstructure:"org-name"`
	OrgType      string     `json:"org_type" mapstructure:"org-type"`
	Phone        string     `mapstructure:"phone"`
	Source       string     `mapstructure:"source"`
	TechC        string     `json:"tech_c" mapstructure:"tech-c"`
}

func (t Organisation) Type() ItemType {
	return ItemTypeOrganisation
}
