package dto

type CallbackReq struct {
	Fmt          string `json:"fmt"`
	Phone        string `json:"phone"`
	Status       string `json:"status"`
	Timestamp    string `json:"ts"`
	ID           string `json:"id"`
	Count        string `json:"cnt"`
	Err          string `json:"err"`
	Time         string `json:"time"`
	IMSI         string `json:"imsi"`
	MSC          string `json:"msc"`
	MCC          string `json:"mcc"`
	MNC          string `json:"mnc"`
	Cnt          string `json:"cn"`
	Net          string `json:"net"`
	RCN          string `json:"rcn"`
	RNet         string `json:"rnet"`
	PNet         string `json:"pnet"`
	Type         string `json:"type"`
	Cost         string `json:"cost"`
	Flag         string `json:"flag"`
	Sender       string `json:"sender"`
	MCCMNC       string `json:"mccmnc"`
	Message      string `json:"message"`
	Country      string `json:"country"`
	Operator     string `json:"operator"`
	OperatorOrig string `json:"operator_orig"`
	Region       string `json:"region"`
}
