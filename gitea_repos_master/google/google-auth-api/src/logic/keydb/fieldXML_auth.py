from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "result": FieldDescription(title="Результат", type="string"),
    "result_code": FieldDescription(title="Код результата", type="string"),
    "devices": FieldDescription(title="Устройства", type="string"),
    "phones": FieldDescription(title="Телефонные номера", type="string"),
    "emails": FieldDescription(title="E-mail", type="string"),
    "android": FieldDescription(
        title="Зарегистрирован на устройстве c Android", type="string"
    ),
    "other": FieldDescription(title="Зарегистрирован на ином устройстве", type="string"),
    "online": FieldDescription(
        title="Пользователь онлайн и получил уведомление", type="string"
    ),
    "phone_notification": FieldDescription(
        title="Пользователь получил уведомление на телефон", type="string"
    ),
    "email_notification": FieldDescription(
        title="Пользователь получил уведомление на почту", type="string"
    ),
    "many_failed_attempts": FieldDescription(
        title="Достигнут лимит попыток получить данные", type="string"
    ),
    "aborted_by_user": FieldDescription(
        title="Поиск прерван владельцем аккаунта", type="string"
    ),
    "backup_code": FieldDescription(
        title="Пользователь имеет 8 значный код восстановления", type="string"
    ),
    "external_auth": FieldDescription(
        title="Авторизация через внешний сайт", type="string"
    ),
    "external_url": FieldDescription(
        title="URL внешнего сайта для авторизации", type="string"
    ),
}
