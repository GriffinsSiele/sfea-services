"""
Модуль содержит обработчики экранов.

Каждый обработчик возвращает словарь с данными формата
ReceivedDataPart = dict[str, list | bool] | None

Возможные ответы обработчиков (могут содержать несколько ключей):
"found": True - пользователь найден;
"devices": ["устройство 1", "устройство 2", ...] - список устройств;
"emails": ["email 1", "email 2", ...] - список e-mail;
"phones": ["номер 1", "номер 2", ...] - список телефонных номеров (частично);
"phone_notification": True - пользователь получил уведомление на телефон;
"email_notification": True - пользователь получил уведомление на почту;
"android": True - пользователь владеет Android устройством;
"other": True - пользователь владеет устройством не под управлением Android;
"backup_code": True - пользователь имеет 8 значный резервный код восстановления;
"aborted_by_user": True - пользователь прервал поиск, нажав отмену на своем устройстве;
"many_failed_attempts": True - много неудачных попыток, гугл блокирует восстановление
данных и как следствие сбор данных;
"recaptcha": True - обнаружена re-captcha.

"""

import logging
import re

from isphere_exceptions.session import SessionCaptchaDetected
from isphere_exceptions.source import SourceIncorrectDataDetected
from isphere_exceptions.success import NoDataEvent
from selenium.webdriver.common.by import By

from src.exceptions import GoogleSessionBlocked, GoogleSessionCaptchaDecodeError
from src.interfaces.abstract_browser import AbstractBrowser
from src.interfaces.abstract_screen_dispatcher import (
    AbstractScreenDispatcher,
    ReceivedDataPart,
)


class UserFoundDispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        return {"found": True}


class UserFoundEmailDispatcher(UserFoundDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        if data := super().get_data(web_browser):
            return {**data, "email": True}
        return {"email": True}


class UserFoundPhoneDispatcher(UserFoundDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        if data := super().get_data(web_browser):
            return {**data, "phone": True}
        return {"phone": True}


class AnotherWayPage2andRecoveryPage27Dispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        return {"devices": ["Ключ доступа"]}


class PageElementTextDispatcher:
    """Получает данные из элемента."""

    tag = ("", "")
    data_key = ""

    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        if element := web_browser.get_loaded_element(*self.tag):
            return {self.data_key: [element.text]}
        logging.warning(f"Element not found {self.tag}.")
        return None


class PageMultiElementTextDispatcher:
    """Получает данные из элемента."""

    tags = [
        ("", ""),
    ]
    data_key = ""

    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        result = []
        for tag in self.tags:
            if element := web_browser.get_loaded_element(*tag):
                result.append(element.text[41:])
        return {self.data_key: result} if result else None


class RecoveryPage3and16and28and33and34Dispatcher(AbstractScreenDispatcher):
    tag = (By.XPATH, "//section[1]/div/div/div/div/ul/li[1]/div/strong")
    data_key = "devices"
    list_tags = [
        "/html/body/div[1]/div[1]/div[2]/div[2]/div/div/span[1]/div[2]/div",
        "/html/body/div[1]/div[1]/div[2]/div[2]/div/div/span[2]/div[2]/div",
        "/html/body/div[1]/div[1]/div[2]/div[2]/div/div/span[3]/div[2]/div",
    ]

    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        result: dict = {self.data_key: []}
        if element := web_browser.get_loaded_element(*self.tag):
            result[self.data_key].append(element.text)
            web_browser.get_element_and_click(*self.tag)
            for tag in self.list_tags:
                if element := web_browser.get_loaded_element(By.XPATH, tag):
                    result[self.data_key].append(element.text)
                    continue
                break
        return {**result, "android": True}


class RecoveryPage4Dispatcher(PageElementTextDispatcher, AbstractScreenDispatcher):
    tag = (By.XPATH, "//form/span/section[2]/div/div/div[1]/span/span")
    data_key = "emails"


class RecoveryPage5and14Dispatcher(PageElementTextDispatcher, AbstractScreenDispatcher):
    tag = (By.XPATH, "//form/span/section[1]/div/div/div/span/span")
    data_key = "phones"


class RecoveryPage47Dispatcher(PageElementTextDispatcher, AbstractScreenDispatcher):
    tag = (
        By.XPATH,
        '//*[@id="yDmH0d"]/c-wiz/div/div[2]/div/div/div/form/span/section[3]/div/div/div[1]/span/span',
    )
    data_key = "phones"


class RecoveryPage48and49Dispatcher(PageElementTextDispatcher, AbstractScreenDispatcher):
    tag = (
        By.XPATH,
        '//*[@id="yDmH0d"]/c-wiz/div/div[2]/div/div/div/form/span/section[2]/div/div/div[1]/span/span',
    )
    data_key = "emails"


class RecoveryPage12Dispatcher(PageElementTextDispatcher, AbstractScreenDispatcher):
    tag = (By.XPATH, "//form/span/section[3]/div/div/div[1]/span/span")
    data_key = "phones"


class RecoveryPage9Dispatcher(PageElementTextDispatcher, AbstractScreenDispatcher):
    tag = (By.XPATH, "//form/span/section[2]/div/div/div[1]/span/span")
    data_key = "emails"

    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        if result := super().get_data(web_browser):
            result["email_notification"] = True
        return result


class RecoveryPage8Dispatcher(PageMultiElementTextDispatcher, AbstractScreenDispatcher):
    tags = [
        (By.XPATH, "//ul/li[1]/div/div[2]"),
        (By.XPATH, "//ul/li[2]/div/div[2]"),
        (By.XPATH, "//ul/li[3]/div/div[2]"),
    ]
    data_key = "emails"


class RecoveryPage26Dispatcher(PageMultiElementTextDispatcher, AbstractScreenDispatcher):
    tags = [
        (By.XPATH, "//ul/li[1]/div/div[2]"),
        (By.XPATH, "//ul/li[2]/div/div[2]"),
    ]
    data_key = "emails"


class RecoveryPage36Dispatcher(PageMultiElementTextDispatcher, AbstractScreenDispatcher):
    tags = [
        (By.XPATH, "//section/div/div/div/ul/li[1]/div/div[2]/div[1]/span/span"),
        (By.XPATH, "//section/div/div/div/ul/li[2]/div/div[2]/div[1]/span/span"),
        (By.XPATH, "//section/div/div/div/ul/li[3]/div/div[2]/div[1]/span/span"),
    ]
    data_key = "phones"


class RecoveryPage37Dispatcher(PageMultiElementTextDispatcher, AbstractScreenDispatcher):
    tags = [
        (By.XPATH, "//ul/li[1]/div/div[2]"),
        (By.XPATH, "//ul/li[2]/div/div[2]"),
        (By.XPATH, "//ul/li[3]/div/div[2]"),
        (By.XPATH, "//ul/li[4]/div/div[2]"),
    ]
    data_key = "emails"


class RecoveryPage20Dispatcher(PageElementTextDispatcher, AbstractScreenDispatcher):
    tag = (By.XPATH, "//section/div/div/div/ul/li[1]/div/div[2]/div[1]/span/span")
    data_key = "phones"


class RecoveryPage21Dispatcher(PageElementTextDispatcher, AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        tags = [
            (By.XPATH, "//section/div/div/div/ul/li[1]/div/div[2]/div[1]/span/span"),
            (By.XPATH, "//section/div/div/div/ul/li[2]/div/div[2]/div[1]/span/span"),
        ]
        result = []
        for tag in tags:
            if element := web_browser.get_loaded_element(*tag):
                result.append(element.text)
        return {"phones": result} if result else None


class RecoveryPage32Dispatcher(PageElementTextDispatcher, AbstractScreenDispatcher):
    tag = (
        By.XPATH,
        '//*[@id="yDmH0d"]/c-wiz/div/div[2]/div/div[1]/div/form/span/section[3]/div/div/div[1]/div/span/span',
    )
    data_key = "phones"


class PageReExpressionDispatcher:
    tag = ("", "")
    data_key = ""
    re_expression = ""

    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        element = web_browser.get_loaded_element(*self.tag)
        if not element:
            logging.warning(f"Element not found {self.tag}.")
            return None
        matched_variants = re.findall(self.re_expression, element.text)
        return {
            self.data_key: matched_variants,
            "phone_notification": True,
            "android": True,
        }


class RecoveryPage2and17and46Dispatcher(
    PageReExpressionDispatcher, AbstractScreenDispatcher
):
    tag = (By.XPATH, '//section/div/div/div[contains(text(), "На устройстве")]')
    data_key = "devices"
    re_expression = r'"([\w -]*)"'


class RecoveryPage15Dispatcher(PageReExpressionDispatcher, AbstractScreenDispatcher):
    tag = (By.XPATH, '//h2/span[contains(text(), "Проверьте устройство ")]')
    data_key = "devices"
    re_expression = r"Проверьте устройство ([\w -]*)"


class RecoveryPage44Dispatcher(PageReExpressionDispatcher, AbstractScreenDispatcher):
    tag = (By.XPATH, '//h1/span[contains(text(), "Проверьте устройство")]')
    data_key = "devices"
    re_expression = r"Проверьте устройство ([)(\w -]*)"


class RecoveryPage18Dispatcher(PageReExpressionDispatcher, AbstractScreenDispatcher):
    tag = (By.XPATH, '//h2/span[contains(text(), "Откройте приложение")]')
    data_key = "devices"
    re_expression = r'на устройстве "([)(\w’ -]*)"'

    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        if result := super().get_data(web_browser):
            result["android"] = False
            result["other"] = True
        return result


class RecoveryPage45Dispatcher(RecoveryPage18Dispatcher):
    tag = (By.XPATH, '//h1/span[contains(text(), "Откройте приложение")]')


class PageReExtraExpressionDispatcher:
    tag = ("", "")
    data_key = ""
    re_expression = ""

    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        element = web_browser.get_loaded_element(*self.tag)
        if not element:
            logging.warning(f"Element not found {self.tag}.")
            return None
        if matched_variants := re.findall(self.re_expression, element.text):
            matched_variants = list(matched_variants[0])
        return {self.data_key: matched_variants}


class RecoveryPage22and50Dispatcher(
    PageReExtraExpressionDispatcher, AbstractScreenDispatcher
):
    tag = (
        By.XPATH,
        '//section/div/div/div[contains(text(), "Уведомление отправлено на устройства ")]',
    )
    data_key = "devices"
    re_expression = r"на устройства ([()\w -]*) и ([()\w -]*)."

    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        if result := super().get_data(web_browser):
            result["phone_notification"] = True
            result["other"] = True
        return result


class RecoveryPage24Dispatcher(PageReExtraExpressionDispatcher, AbstractScreenDispatcher):
    tag = (
        By.XPATH,
        '//section/div/div/div[contains(text(), "Уведомление отправлено на устройства ")]',
    )
    data_key = "devices"
    re_expression = (
        r"на устройства ([\w ,.()-]*?)(?:, | и )([\w ,.()-]*?)(?: и ещё 1.|.) Чтобы"
    )

    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        if result := super().get_data(web_browser):
            result["phone_notification"] = True
            result["android"] = True
        return result


class RecoveryPage11and13Dispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> dict:
        return {"android": True, "phone_notification": True}


class RecoveryPage6and7and23and30Dispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> dict:
        return {"other": True}


class RecoveryPage25and31and39Dispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> dict:
        return {"other": True, "phone_notification": True}


class RecoveryPage19Dispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> dict:
        return {"backup_code": True}  # eight digit backup code


class NotFoundErrorDispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> None:
        raise NoDataEvent()


class SourceIncorrectDataDispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> None:
        raise SourceIncorrectDataDetected()


class TooManyFailedAttemptsDispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> dict:
        # Возникает, когда много раз с одного прокси проверяется один и тот же номер.
        # Если изменить прокси, ошибка пройдет и данные можно получить.
        return {"found": True, "many_failed_attempts": True}


class AbortedByUserDispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> dict:
        # Прервано пользователем аккаунта (нажал отмену на своем устройства)
        logging.warning("Aborted by user.")
        return {"aborted_by_user": True}


class SessionBlockedDispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> None:
        # Гугл забраковал браузер и не позволяет работать
        # или произошла ошибка при получении страницы - "ErrorPage"
        raise GoogleSessionBlocked()


class CaptchaDispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> None:
        raise SessionCaptchaDetected()  # "CAPTCHA founded"


class CaptchaNotSolvedDispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> None:
        raise GoogleSessionCaptchaDecodeError()


class ReCaptchaDispatcher(AbstractScreenDispatcher):
    def get_data(self, web_browser: AbstractBrowser) -> dict:
        return {"found": True, "recaptcha": True}
