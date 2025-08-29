import logging
from time import time

from selenium.common import (
    NoSuchElementException,
    StaleElementReferenceException,
    TimeoutException,
)
from selenium.webdriver.common.by import By
from selenium.webdriver.support.wait import WebDriverWait

from src.browser.mixins import SaveScreenMixin
from src.browser.selenium_browser import SeleniumBrowser
from src.browser.selenium_utils import webdriver_exception_handler
from src.config.settings import MULTIPLE_WAIT


class GoogleSeleniumBrowser(SeleniumBrowser, SaveScreenMixin):
    @webdriver_exception_handler
    def _script_completed_waiting(self) -> None:
        """Ожидает завершения работы js скрипта для страницы Google."""

        self.driver.implicitly_wait(0)

        total_time = time()

        multiple_wait = int(MULTIPLE_WAIT) if MULTIPLE_WAIT else 1
        while multiple_wait > 0:
            try:
                # для всех страниц используем, поведение, что на время отработки скрипта
                # у тега с id="initialView" добавляется атрибут aria-busy="true",
                # а когда страница обновилась - атрибут удаляется
                WebDriverWait(self.driver, self.explicit_wait_delay).until_not(
                    lambda res: self.get_element(
                        # By.XPATH, '//*[@id="initialView" and @aria-busy="true"]'
                        By.XPATH,
                        '/html/body/div[1]/div[1][@aria-busy="true"]',
                    )
                )
                break
            except TimeoutException:
                multiple_wait -= 1
                if (
                    self.get_element(
                        # By.XPATH, '//*[@id="initialView" and @aria-busy="true"]'
                        By.XPATH,
                        '/html/body/div[1]/div[1][@aria-busy="true"]',
                    )
                    and multiple_wait > 0
                ):
                    logging.info(f"Additional wait, {multiple_wait} iterations left.")
                    continue
                self.driver.refresh()
                logging.warning("Timed out waiting for script completed (AllPages)")

        try:
            # дополнительное ожидание для страниц AnotherWayPage_1 и AnotherWayPage_2
            # так как при обновлении этих страниц атрибут aria-busy="true" не появляется
            # используем изменение заголовка страницы
            if self.get_element(
                By.XPATH, '//h2/span/span[contains(text(), "Выберите способ входа:")]'
            ):
                # сохраняем текущий заголовок страницы
                selector = (By.XPATH, "/html/body/div[2]/div[@aria-atomic]")
                element = self.get_loaded_element(*selector)
                # ждем изменения заголовка
                WebDriverWait(self.driver, self.explicit_wait_delay).until_not(
                    lambda res: self.get_element(*selector) == element
                )

        except TimeoutException:
            logging.warning("Timed out waiting for script completed (AnotherWayPages)")

        except (NoSuchElementException, StaleElementReferenceException):
            logging.warning("Timed out waiting for script terminated (AnotherWayPages)")

        self.driver.implicitly_wait(self.implicitly_wait_delay)
        logging.debug(f"Waiting for script function spend {time() - total_time} sec.")
