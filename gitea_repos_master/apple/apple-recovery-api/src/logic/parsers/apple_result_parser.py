import re
from typing import Type

from isphere_exceptions.source import SourceParseError
from isphere_exceptions.success import NoDataEvent
from pydash import get, omit
from requests import JSONDecodeError, Response  # type: ignore
from worker_classes.utils import short

from src.interfaces import AbstractPageParser
from src.logger.context_logger import logging
from src.logic.apple.exceptions import SessionCaptchaDecodeWarning, SourceWarning


class AppleResultParser(AbstractPageParser):
    """
    Парсер ответов от сайта apple.
    """

    not_found_code = "appleIdNotSupported"
    not_found_message = "недействителен или не поддерживается."
    not_active_message = "неактивен."
    captcha_not_accepted_code = "captchaAnswer.Invalid"
    captcha_not_accepted_message = (
        "Для продолжения введите символы, которые видите или слышите."
    )
    error_code = "web.generic.error.message"
    error_message = "Ваш запрос не может быть выполнен из-за возникшей ошибки. Повторите попытку позже."

    def parse(self, response: Response) -> dict:
        """Запускает парсинг ответа.

        :param response: Экземпляр класса `requests.Response`.
        :response: Словарь с результатами парсинга или исключение SourceParseError.
        """
        try:
            input_data = response.json()
        except JSONDecodeError as e:
            logging.warning(f"AppleResultParser JSONDecodeError: {short(e)}")
            raise SourceWarning("AppleResultParser JSONDecodeError")

        status_code = response.status_code
        input_data = omit(input_data, "sstt")
        logging.info(f"Apple response: {input_data}, status code: {status_code}")

        if not input_data:
            raise SourceWarning("Empty response from source")
        self._check_not_found(status_code, input_data)
        self._check_captcha_not_accepted(status_code, input_data)
        self._check_for_errors(status_code, input_data)
        if result := self._get_payload(input_data):
            return result
        logging.warning(f'Input data: "{input_data}", status code: {status_code}')
        raise SourceParseError()

    def _check_not_found(self, status_code: int, input_data: dict) -> None:
        """Проверяет ответ на результат пользователь не найден.

        :param status_code: Статус код HTTP ответа.
        :param input_data: Тело HTTP ответа.
        :response: None или исключение NoDataEvent.
        """
        if status_code != 200:
            return None
        return self._raise_exception_or_not(
            input_data,
            self.not_found_code,
            self.not_found_message,
            "serviceErrors",
            NoDataEvent,
        )

    def _check_captcha_not_accepted(self, status_code: int, input_data: dict) -> None:
        """Проверяет ответ на результат капча решена не верно и не принята.

        :param status_code: Статус код HTTP ответа.
        :param input_data: Тело HTTP ответа.
        :response: None или исключение SessionCaptchaDecodeWarning.
        """
        if status_code != 400:
            return None
        return self._raise_exception_or_not(
            input_data,
            self.captcha_not_accepted_code,
            self.captcha_not_accepted_message,
            "service_errors",
            SessionCaptchaDecodeWarning,
        )

    def _check_for_errors(self, status_code: int, input_data: dict) -> None:
        """Проверяет ответ на наличие ошибок при отработке запроса на стороне apple.

        :param status_code: Статус код HTTP ответа.
        :param input_data: Тело HTTP ответа.
        :response: None или исключение SourceWarning.
        """
        if status_code != 400:
            return None
        return self._raise_exception_or_not(
            input_data,
            self.error_code,
            self.error_message,
            "service_errors",
            SourceWarning,
        )

    @staticmethod
    def _raise_exception_or_not(
        input_data: dict,
        code_expected: str,
        message_expected: str,
        key_prefix: str,
        exception: Type[Exception],
    ) -> None:
        """Проверяет на совпадение входных данных и в случае совпадения возбуждает исключение
        (Вспомогательный метод, обеспечивает DRY).

        :param input_data: Тело HTTP ответа.
        :param code_expected: Код, который должен присутствовать в теле HTTP ответа для возбуждения исключения.
        :param message_expected: Сообщение, которое должно присутствовать в теле HTTP ответа для возбуждения исключения.
        :param key_prefix: Префикс ключа, для поиска нужных сообщений в теле HTTP ответа.
        :param exception: Исключение, которое будет возбуждено, в случае совпадения.
        :response: None или переданное исключение.
        """
        code = get(input_data, f"{key_prefix}.0.code", "")
        message = get(input_data, f"{key_prefix}.0.message", "").replace("\xa0", " ")
        if code_expected in code and message_expected in message:
            raise exception()
        return None

    def _get_payload(self, input_data: dict) -> dict:
        """Извлекает данные из тела HTTP ответа и на их основании формирует результат поиска

        :param input_data: Тело HTTP ответа.
        :response: None или исключение SessionCaptchaDecodeWarning.
        """
        result: dict[str, str | bool | list] = {}

        name = get(input_data, "name", "") or get(input_data, "account.name", "")
        not_active = self.not_active_message in get(
            input_data, "service_errors.0.message", ""
        )
        birthday_check = get(input_data, "dateLayout")
        reset_password = "reset_password" in get(input_data, "recoveryOptions.0", "")
        if name or not_active or birthday_check or reset_password:
            result["found"] = True
            result["result"] = "Найден"
            result["result_code"] = "FOUND"
            result["not_active"] = AppleResultParser._cast_data(not_active)

        if AppleResultParser._is_hidden_phone(name):
            result["phone"] = AppleResultParser._prepare_phone_or_email(name)

        if AppleResultParser._is_hidden_email(name):
            result["email"] = AppleResultParser._prepare_phone_or_email(name)

        if phone := get(input_data, "trustedPhones.0.number"):
            prepared_phone = AppleResultParser._prepare_phone_or_email(phone)
            result["phone"] = (
                f'{result["phone"]}", "{prepared_phone}'
                if get(result, "phone")
                else prepared_phone
            )

        if email := get(input_data, "emailAddress"):
            prepared_email = AppleResultParser._prepare_phone_or_email(email)
            if get(result, "email"):
                result["email"] = (
                    f'{result["email"]}", "{prepared_email}'
                    if get(result, "email")
                    else prepared_email
                )

        multi_email = bool(get(input_data, "multipleEmails"))
        result["multi_email"] = AppleResultParser._cast_data(multi_email)

        locked = bool(get(input_data, "supportsUnlock"))
        result["locked"] = AppleResultParser._cast_data(locked)

        auth = get(input_data, "is2FAEligible")
        result["auth"] = "Двухфакторная" if auth else "Только пароль"

        paid = bool(get(input_data, "paidAccount"))
        result["paid"] = AppleResultParser._cast_data(paid)

        return result

    @staticmethod
    def _cast_data(data: bool) -> str:
        """Преобразует True, False в "Да" и "Нет" соответственно.

        :param data: Булево значение.
        :response: "Да" или "Нет".
        """
        return "Да" if data else "Нет"

    @staticmethod
    def _prepare_phone_or_email(phone_or_email: str) -> str:
        """Преобразует телефонный номер и адрес электронной почты к единому формату.
        Удаляет лишние символы, заменяет "•" на "*".

        :param phone_or_email: Телефон или адрес электронной почты.
        :response: Преобразованный телефон или адрес электронной почты.
        """
        prepared = re.sub(r"[\(\)\-\ ]", "", phone_or_email)
        return re.sub(r"•", "*", prepared)

    @staticmethod
    def _is_hidden_phone(phone: str) -> bool:
        """Проверяет телефонный номер скрыт или нет.

        :param phone: Телефонный номер.
        :response: Результат проверки.
        """
        if "•" not in phone:
            return False
        return bool(re.findall(r"[\d•] \([\d•]{3}\) [\d•]{3}-[\d•]{2}-[\d•]{2}", phone))

    @staticmethod
    def _is_hidden_email(email: str) -> bool:
        """Проверяет адрес электронной почты скрыт или нет.

        :param email: Адрес электронной почты.
        :response: Результат проверки.
        """
        if "•" not in email:
            return False
        return bool(re.findall(r"[\w.+-•]+@[\w.+-•]+", email))
