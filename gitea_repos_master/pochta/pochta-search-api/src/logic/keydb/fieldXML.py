from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "Result": FieldDescription(
        title="Результат",
        type="string",
    ),
    "ResultCode": FieldDescription(
        title="Код результата",
        type="string",
    ),
    "Name": FieldDescription(
        title="Имя",
        type="string",
    ),
    "Address": FieldDescription(
        title="Адрес",
        type="string",
    ),
    "Index": FieldDescription(
        title="Индекс",
        type="string",
    ),
    "PosteRestanteAddress": FieldDescription(
        title="Адрес до востребования",
        type="string",
    ),
}
