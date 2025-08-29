import logging

import phonenumbers
from isphere_exceptions.source import SourceIncorrectDataDetected
from phonenumbers import NumberParseException


class InputDataHandler:
    """Выполняет подготовку входных данных для использования в поиске на сайте Xiaomi"""

    @staticmethod
    def handle_search_data(search_data: dict | str) -> tuple[str, str]:
        """Обрабатывает входные данные, определяет какой обработчик должен выполнять поиск.

        :param search_data: Данные для поиска, строка или словарь с ключами "phone" или "email".
        :return: Кортеж, где первый параметр имя обработчика, второй - данные для поиска.
        """
        if isinstance(search_data, str):
            return "phone", search_data

        if isinstance(search_data, dict):
            if phone := search_data.get("phone"):
                return "phone", phone

            if email := search_data.get("email"):
                return "email", email

        raise SourceIncorrectDataDetected()

    @staticmethod
    def parse_phone(phone: str) -> tuple[str, str]:
        """Подготавливает телефонный номер в формат пригодный для поиска на сайте Xiaomi.
        Форма сайта Xiaomi предлагает ввести номер телефона в формате "+7 9876543210",
        при поиске код страны выбирается их списка доступных стран, а номер телефона
        вводится в поле input. От RabbitMQ приходит телефонный номер в формате 79876543210,
        данный метод парсит телефонный номер и возвращает кортеж, где первый элемент
        код страны, который используется для переключения формы поиска, второй элемент -
        телефонный номер, используется для заполнения поля input.
        Если в поле input ввести номер телефона с кодом страны, сайт Xiaomi вернет результат
        "не найден", что не верно.

        :param phone: Телефонный номер.
        :return: Код страны, телефонный номер.

        Example:
        -------
        ``prepare_phone('79876543210') -> '+7', '9876543210'``
        """
        country_code = number = ""

        if not phone.startswith("+"):
            phone = "+" + phone
        try:
            parsed = phonenumbers.parse(phone)
        except NumberParseException:
            logging.warning(f"Error parsing phone number {phone}")
            return country_code, number

        if parsed.country_code:
            country_code = "+" + str(parsed.country_code)
        if parsed.national_number:
            number = str(parsed.national_number)
        return country_code, number
