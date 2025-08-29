from isphere_exceptions.source import SourceIncorrectDataDetected


class InputDataHandler:
    """Выполняет подготовку входных данных для использования в поиске на сайте Huawei"""

    @staticmethod
    def handle_search_data(search_data: dict | str) -> tuple[str, str]:
        """Проверяет и обрабатывает входные данные, возвращает кортеж из ключа и значения.

        :param search_data: Данные для поиска, словарь с ключами "phone" или "email".
        :return: Кортеж, где первый параметр ключ, второй - данные для поиска.
        """
        if isinstance(search_data, dict):
            if phone := search_data.get("phone"):
                return "phone", InputDataHandler.prepare_phone(phone)

            if email := search_data.get("email"):
                return "email", email

        raise SourceIncorrectDataDetected()

    @staticmethod
    def prepare_phone(phone: str) -> str:
        """Подготавливает номер телефона к поиску.
        Для этого добавляет знак "+" перед номером, если его нет.

        :param phone: Номер телефона.
        :return: Подготовленный для поиска номер телефона.
        """
        if phone.startswith("+"):
            return phone
        return "+" + phone
