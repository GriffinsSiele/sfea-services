from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "result": FieldDescription(title="Результат", type="string"),
    "result_code": FieldDescription(title="Код результата", type="string"),
    "phones": FieldDescription(title="Телефонные номера", type="string"),
    "emails": FieldDescription(title="E-mail", type="string"),
}
