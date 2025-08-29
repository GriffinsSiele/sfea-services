from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "name": FieldDescription(
        title="Имя",
        type="string",
    ),
    "is_spam": FieldDescription(
        title="Является спам-номером",
        type="string",
    ),
    "type": FieldDescription(
        title="Тип",
        type="string",
    ),
    "image": FieldDescription(
        title="Аватар",
        type="image",
    ),
}
