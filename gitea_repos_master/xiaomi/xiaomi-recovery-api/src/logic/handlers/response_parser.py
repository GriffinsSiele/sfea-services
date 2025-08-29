import logging
import re

from isphere_exceptions.source import SourceError
from isphere_exceptions.success import NoDataEvent

from src.exceptions.exceptions import SessionCaptchaDecodeWarning
from src.logic.repository.screens_repository import ScreensRepository


class ResponseParser:
    """Парсит ответ сайта xiaomi"""

    @staticmethod
    def parse(data: str, data_type: str, screen_repository: ScreensRepository) -> dict:
        """Определяет текущий экран и на его основе подготавливает и возвращает результат поиска.

        :param data: Данные по которым осуществляется поиск (номер телефона или адрес электронной почты).
        :param data_type: Тип данных по которым осуществляется поиск ("phone" или "email").
        :param screen_repository: Экземпляр класса ScreensRepository, который содержит все известные экраны.
        :return: Результат поиска.
        """
        screen_name = ResponseParser._identify_screen(screen_repository)
        return ResponseParser.parse_founded_screen(
            data, data_type, screen_name, screen_repository
        )

    @staticmethod
    def parse_founded_screen(
        data, data_type, screen_name: str, screen_repository: ScreensRepository
    ) -> dict:
        """Подготавливает и возвращает результат поиска в соответствии с найденным экраном.

        :param data: Данные по которым осуществляется поиск (номер телефона или адрес электронной почты).
        :param data_type: Тип данных по которым осуществляется поиск ("phone" или "email").
        :param screen_name: Имя найденного экрана.
        :param screen_repository: Экземпляр класса ScreensRepository, который содержит все известные экраны.
        :return: Результат поиска.
        """
        if screen_name == "captcha_not_solved_page":
            raise SessionCaptchaDecodeWarning()

        if screen_name == "not_found_page":
            raise NoDataEvent()

        if screen_name == "found_page":
            extra_info = ResponseParser._get_extra_info(
                data, data_type, screen_repository
            )
            return {"result": "Найден", "result_code": "FOUND", **extra_info}

        raise SourceError("Source response not parsed")

    @staticmethod
    def _identify_screen(screen_repository: ScreensRepository) -> str:
        """Определяет имя текущего экрана и возвращает в качестве результата.
        Возбуждает исключение SourceError, если экран не определен.

        :param screen_repository: Экземпляр класса ScreensRepository, который содержит все известные экраны.
        :return: Имя определенного экрана.
        """
        if screen_repository.get_page("captcha_not_solved_page").is_current_screen():
            return "captcha_not_solved_page"

        if screen_repository.get_page("not_found_page").is_current_screen():
            return "not_found_page"

        if screen_repository.get_page("found_page").is_current_screen():
            return "found_page"

        raise SourceError("Source response not parsed")

    @staticmethod
    def _get_extra_info(
        data: str, data_type: str, screen_repository: ScreensRepository
    ) -> dict:
        """Извлекает дополнительную информацию о пользователе, проверяет на совпадение
        с ключом поиска, если совпадает, эти данные уже известны и они удаляются из результатов поиска.

        :param data: Данные по которым осуществляется поиск (номер телефона или адрес электронной почты).
        :param data_type: Тип данных по которым осуществляется поиск ("phone" или "email").
        :param screen_repository: Экземпляр класса ScreensRepository, который содержит все известные экраны.
        :return: Результат совпадения True или False.
        """
        found_page = screen_repository.get_page("found_page")
        extra_info = found_page.get_extra_info()
        if not extra_info:
            return {}
        logging.info(f"Extra user info: {extra_info}")
        ResponseParser._handle_email_extra_info(data, data_type, extra_info)

        return extra_info

    @staticmethod
    def _handle_email_extra_info(data: str, data_type: str, extra_info: dict) -> None:
        emails = extra_info.get("emails")
        if data_type != "email" or not emails:
            return

        for email in emails:
            if ResponseParser._is_email_matched(data, email):
                try:
                    extra_info["emails"].remove(email)
                except ValueError:
                    logging.error(
                        f"Failed to remove {email} from {extra_info.get('emails')}"
                    )

        if not extra_info["emails"]:
            try:
                del extra_info["emails"]
            except KeyError:
                logging.error('Failed to remove key "emails" from "extra_info"')

    @staticmethod
    def _is_email_matched(full_email: str, hidden_email: str) -> bool:
        """Проверяет почту на совпадение с частично скрытой символами "*" почтой.

        :param full_email: Полная запись почты (Например "some@yandex.ru").
        :param hidden_email: Скрытая запись почты (Например "so****@ya**ex.ru").
        :return: Результат совпадения True или False.
        """
        pattern = re.sub(r"\*+", r"[\\w\.-]+", hidden_email)
        return bool(re.match(pattern, full_email))
