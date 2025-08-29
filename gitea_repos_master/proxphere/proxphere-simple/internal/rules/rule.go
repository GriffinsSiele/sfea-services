package rules

type Rule struct {
	Username string    `yaml:"username"`
	Password string    `yaml:"password"`
	Proxy    RuleProxy `yaml:"proxy"`
}

type RuleProxy struct {
	Enabled bool  `yaml:"enabled"`
	Groups  []int `yaml:"groups"`
}
