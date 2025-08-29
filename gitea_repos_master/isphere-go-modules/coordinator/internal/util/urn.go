package util

import "fmt"

func QueueURN(scope string) string {
	return fmt.Sprintf("urn:upstream:amqp:exchange:%s", scope)
}

func StorageURN(scope string) string {
	return fmt.Sprintf("urn:upstream:keydb:database:key:%s", scope)
}
