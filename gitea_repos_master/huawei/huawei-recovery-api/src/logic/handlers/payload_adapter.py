import re

from isphere_exceptions.worker import InternalWorkerError


class PayloadAdapter:
    """Обрабатывает информацию со страницы найденного пользователя."""

    @staticmethod
    def adapt(search_key: str, search_val: str, payload: str) -> dict:
        """Обрабатывает информацию со страницы найденного пользователя.
        Проверяет на совпадение с данными поиска, если совпадает -
        информация не отображаются.

        :param search_key: Ключ поиска ("email" или "phone").
        :param search_val: Данные для поиска (значение "email" или "phone").
        :param payload: Данные со страницы найденного пользователя.
        :return: Обработанная информация.
        """
        if search_key == "email":
            if not PayloadAdapter._is_email(payload):
                return {"phones": [PayloadAdapter._prepare_phone(payload)]}

            if PayloadAdapter._is_emails_matched(search_val, payload):
                return {}
            return {"emails": [payload]}

        if search_key == "phone":
            if PayloadAdapter._is_email(payload):
                return {"emails": [payload]}

            prepared_phone = PayloadAdapter._prepare_phone(payload)
            if PayloadAdapter._is_phones_matched(search_val, prepared_phone):
                return {}
            return {"phones": [prepared_phone]}

        raise InternalWorkerError(
            f'invalid search key "{search_key}", allowed keys "phone" and "email"'
        )

    @staticmethod
    def _is_emails_matched(full_email: str, hidden_email: str) -> bool:
        """Проверяет почту на совпадение с частично скрытой символами "*" почтой.

        :param full_email: Полная запись почты (Например "some@yandex.ru").
        :param hidden_email: Скрытая запись почты (Например "so****@ya**ex.ru").
        :return: Результат совпадения True или False.
        """
        pattern = re.sub(r"\*+", r"[\\w\.-]+", hidden_email)
        return bool(re.match(pattern, full_email))

    @staticmethod
    def _is_phones_matched(full_phone: str, hidden_phone: str) -> bool:
        """Проверяет телефон на совпадение с частично скрытым символами "*" телефоном.

        :param full_phone: Полная запись телефона (Например "79281234567").
        :param hidden_phone: Скрытая запись телефона (Например "007928*****67").
        :return: Результат совпадения True или False.
        """
        if full_phone.startswith("+"):
            full_phone = full_phone[1:]

        start_slice = len(hidden_phone) - len(full_phone)
        hidden_phone = hidden_phone[start_slice:]

        pattern = re.sub(r"\*+", r"[\\d]+", hidden_phone)
        return bool(re.match(pattern, full_phone))

    @staticmethod
    def _prepare_phone(phone: str) -> str:
        """Подготавливает полученный при поиске телефон к сравнению с искомым.
        Подготовленный телефон возвращается в результатах поиска, если
        он не совпадает с искомым.

        :param phone: Телефонный номер (Например "007928*****67" или "+7 968*****94").
        :return: Подготовленный номер телефона (Например "7928*****67" или "7968*****94").
        """
        prepared_phone = PayloadAdapter._remove_zeros(phone)
        prepared_phone = prepared_phone.replace(" ", "")
        if prepared_phone.startswith("+"):
            return prepared_phone[1:]
        return prepared_phone

    @staticmethod
    def _remove_zeros(phone: str) -> str:
        """Удаляет нули ("0") в начале телефонного номера и возвращает результат.

        :param phone: Телефонный номер (Например "007928*****67").
        :return: Очищенный номер телефона (Например "7928*****67").
        """
        if phone.startswith("0"):
            return PayloadAdapter._remove_zeros(phone[1:])
        return phone

    @staticmethod
    def _is_email(data: str) -> bool:
        """Проверка являются данные адресом электронной почты или нет.

        :param data: Проверяемые данные.
        :return: Результат проверки True или False.
        """
        return "@" in data
