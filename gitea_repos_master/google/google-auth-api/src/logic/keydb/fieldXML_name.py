from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "Result": FieldDescription(title="Результат", type="string", description="Результат"),
    "ResultCode": FieldDescription(
        title="Код результата", type="string", description="Код результата"
    ),
}
