from isphere_exceptions.worker import InternalWorkerError
from selenium.webdriver.common.by import By

WebElementDef = tuple[By, str]


class Screen:
    """Содержит описание экрана и его элементов."""

    def __init__(
        self,
        definitions: list[WebElementDef],
        input_fields: list[WebElementDef] | None = None,
        payloads: list[WebElementDef] | None = None,
        buttons: list[WebElementDef] | None = None,
    ) -> None:
        """Конструктор класса. Определяет описание экрана и его элементов.

        :param definitions: Определения экрана, по которым его можно однозначно идентифицировать.
        :param input_fields: Поля ввода экрана (не обязательный параметр).
        :param payloads: Полезная информация на экране (например информация о найденном пользователе,
            не обязательный параметр).
        :param buttons: Кнопки экрана (не обязательный параметр).
        """
        self._definitions = definitions
        self._input_fields = input_fields
        self._payloads = payloads
        self._buttons = buttons

    @property
    def definitions(self) -> list[WebElementDef]:
        if not self._definitions:
            raise InternalWorkerError("Definitions not defined")
        return self._definitions

    @definitions.setter
    def definitions(self, value: list[WebElementDef]) -> None:
        self._definitions = value

    @property
    def input_fields(self) -> list[WebElementDef]:
        if not self._input_fields:
            raise InternalWorkerError("Input fields not defined")
        return self._input_fields

    @input_fields.setter
    def input_fields(self, value: list[WebElementDef]) -> None:
        self._input_fields = value

    @property
    def payloads(self) -> list[WebElementDef]:
        if not self._payloads:
            raise InternalWorkerError("Payloads fields not defined")
        return self._payloads

    @payloads.setter
    def payloads(self, value: list[WebElementDef]) -> None:
        self._payloads = value

    @property
    def buttons(self) -> list[WebElementDef]:
        if not self._buttons:
            raise InternalWorkerError("Buttons fields not defined")
        return self._buttons

    @buttons.setter
    def buttons(self, value: list[WebElementDef]) -> None:
        self._buttons = value
