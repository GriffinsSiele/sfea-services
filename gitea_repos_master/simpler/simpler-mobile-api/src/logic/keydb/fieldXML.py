from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "full_name": FieldDescription(
        title="Имя",
        type="string",
    ),
    "company_name": FieldDescription(
        title="Название организации",
        type="string",
    ),
    "job_title": FieldDescription(
        title="Должность",
        type="string",
    ),
    "email": FieldDescription(
        title="Почта",
        type="email",
    ),
    "spam": FieldDescription(
        title="Является спам-номером",
        type="string",
    ),
}
