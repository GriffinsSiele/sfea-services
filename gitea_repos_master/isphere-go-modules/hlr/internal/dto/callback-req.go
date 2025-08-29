package dto

import "github.com/google/uuid"

type CallbackReq struct {
	UUID        uuid.UUID         `json:"uuid"`
	ClientField string            `json:"clientField"`
	Status      CallbackReqStatus `json:"status"`
	StatusTime  string            `json:"status_time"`
	To          string            `json:"to"`
	ErrorCode   string            `json:"errCode"`
	Operator    string            `json:"operator"`
	Country     string            `json:"country"`
}

func (t *CallbackReq) StatusCompleted() bool {
	switch t.Status {
	case CallbackReqStatusCreated,
		CallbackReqStatusModeration,
		CallbackReqStatusProgress:
		return false
	default:
		return true
	}
}

type CallbackReqStatus string

const (
	CallbackReqStatusCreated       CallbackReqStatus = "created"        // Ожидает отправки
	CallbackReqStatusModeration    CallbackReqStatus = "moderation"     // На модерации
	CallbackReqStatusReject        CallbackReqStatus = "reject"         // Запрещено модерацией
	CallbackReqStatusDelivered     CallbackReqStatus = "delivered"      // Доставлено
	CallbackReqStatusRead          CallbackReqStatus = "read"           // Прочитано
	CallbackReqStatusReply         CallbackReqStatus = "reply"          // Есть ответ
	CallbackReqStatusUndelivered   CallbackReqStatus = "undelivered"    // Не доставлено
	CallbackReqStatusTimeout       CallbackReqStatus = "timeout"        // Просрочено
	CallbackReqStatusProgress      CallbackReqStatus = "progress"       // В процессе
	CallbackReqStatusNoMoney       CallbackReqStatus = "no_money"       // Недостаточно средств
	CallbackReqStatusNoDoubled     CallbackReqStatus = "doubled"        // Дублирование
	CallbackReqStatusLimitExceeded CallbackReqStatus = "limit_exceeded" // Превышен лимит
	CallbackReqStatusBadNumber     CallbackReqStatus = "bad_number"     // Неверный номер
	CallbackReqStatusStopList      CallbackReqStatus = "stop_list"      // Запрещено стоп листом
	CallbackReqStatusRouteClosed   CallbackReqStatus = "route_closed"   // Направление запрещено
	CallbackReqStatusError         CallbackReqStatus = "error"          // Ошибка
)
