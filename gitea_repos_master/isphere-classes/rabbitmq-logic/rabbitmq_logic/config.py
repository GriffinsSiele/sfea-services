class MessageType:
    REGISTRY = "registry"
    INNER_MESSAGE = "inner_message"
    WEB_INTERFACE = "web_interface"
    API = "api"


class RabbitMQConfig:
    MAX_PRIORITY_VALUE = 10

    message_type_to_priority = {
        MessageType.REGISTRY: 1,
        MessageType.INNER_MESSAGE: 3,
        MessageType.WEB_INTERFACE: 5,
        MessageType.API: 7,
    }
