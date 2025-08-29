package internal

import "github.com/shopspring/decimal"

type Input struct {
	Email string `json:"email"`
	Phone string `json:"phone"`
}

type Error struct {
	Message     string `json:"error_message"`
	Description string `json:"error_description"`
}

type YoomoneyRequest struct {
	WithCredentials bool            `json:"withCredentials"`
	Params          Params          `json:"params"`
	Amount          decimal.Decimal `json:"amount"`
}

type Params struct {
	Origin    ParamsOrigin `json:"origin"`
	Recipient Recipient    `json:"recipient"`
}

type ParamsOrigin string

const (
	ParamsOriginWithdraw ParamsOrigin = "withdraw"
)

type Recipient struct {
	Email string `json:"email,omitempty"`
	Phone string `json:"phone,omitempty"`
}

type Source struct {
	SchemeType     string          `json:"schemeType"`
	AllowedAmounts AllowedAmounts  `json:"allowedAmounts"`
	CardBind       string          `json:"cardBind"`
	FailureReason  string          `json:"failureReason"`
	WalletBalances *WalletBalances `json:"walletBalances,omitempty"`
}

type AllowedAmounts struct {
	MinAmounts []struct {
		Value              int    `json:"value"`
		AlphabeticCurrency string `json:"alphabeticCurrency"`
	} `json:"minAmounts"`
	MaxAmounts []struct {
		Value              int    `json:"value"`
		AlphabeticCurrency string `json:"alphabeticCurrency"`
	} `json:"maxAmounts"`
}

type WalletBalances struct {
	Balances []interface{} `json:"balances"`
}

type RecipientInfo struct {
	AccountInfo AccountInfo `json:"accountInfo"`
	OwnTransfer bool        `json:"ownTransfer"`
}

type AccountInfo struct {
	Account        string   `json:"account"`
	Phone          string   `json:"phone,omitempty"`
	Email          string   `json:"email,omitempty"`
	ResolveType    string   `json:"resolveType"`
	Identification string   `json:"identification"`
	Restrictions   []string `json:"restrictions"`
}

type Root struct {
	Sources       []Source      `json:"sources"`
	RecipientInfo RecipientInfo `json:"recipientInfo"`
}
