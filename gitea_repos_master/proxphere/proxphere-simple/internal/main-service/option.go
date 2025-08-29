package main_service

type Option struct {
	ID         int    `json:"id,string"`
	Name       string `json:"name"`
	Server     string `json:"server"`
	Port       int    `json:"port,string"`
	Login      string `json:"login"`
	Password   string `json:"password"`
	Country    string `json:"country"`
	ProxyGroup int    `json:"proxygroup,string"`
	Status     int    `json:"status,string"`
}
