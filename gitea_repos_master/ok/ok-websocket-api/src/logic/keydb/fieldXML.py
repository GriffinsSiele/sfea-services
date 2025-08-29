from worker_classes.keydb.interfaces import FieldDescription, FieldXMLDescriptor

field_XML_description: FieldXMLDescriptor = {
    "user_name": FieldDescription(
        title="Имя",
        field="Name",
        type="string",
    ),
    "city": FieldDescription(
        title="Город",
        type="string",
    ),
    "register_date": FieldDescription(
        title="Зарегистрирован",
        type="datetime",
    ),
    "register_year": FieldDescription(
        title="Год регистрации",
        type="string",
    ),
    "avatar": FieldDescription(
        title="Аватар",
        field="Avatar",
        type="image",
    ),
    "avatar_cropped": FieldDescription(
        title="Аватар (сжатый)",
        field="Avatar_cropped",
        type="image",
    ),
    "email": FieldDescription(
        title="e-mail",
        type="email",
    ),
    "phone_number": FieldDescription(
        title="Телефон",
        field="Phone",
        type="phone",
    ),
}
