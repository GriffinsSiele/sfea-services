import logging
from time import sleep

from pydash import map_, filter_
from selenium.webdriver.common.by import By

from src.config import AutoRegisterConfig
from src.selenium import SeleniumDriver


class SeleniumRegister:
    def register(self):
        SLEEP_MULTIPLIER = AutoRegisterConfig.AVERAGE_TIMEOUT_MULTIPLIER

        selenium = SeleniumDriver()

        logging.info(f"Selenium browser created: {selenium}")

        selenium.driver.get("https://m.avito.ru")
        # Ждем пока загрузится страница
        sleep(0.5 * SLEEP_MULTIPLIER)
        # Перезагружаемся, т.к. в первый раз падает ошибка "Доступ по IP заблокирован" из-за инкогнито
        selenium.driver.refresh()
        # Ждем пока загрузится страница
        sleep(0.5 * SLEEP_MULTIPLIER)

        # Клик на закрытие баннера
        selenium.click(
            selenium.get_tag(By.ID, "splash-banner-click-negative"),
            delay_after=0.5 * SLEEP_MULTIPLIER,
        )

        # Клик на открытие бокового меню
        selenium.click(
            selenium.get_tag("data-marker", "search-bar/menu"),
            delay_before=0.5 * SLEEP_MULTIPLIER,
            delay_after=0.5 * SLEEP_MULTIPLIER,
        )

        # Клик на кнопку "Войти"
        selenium.click(
            selenium.get_tag("data-marker", "menu/signin"),
            delay_before=0.5 * SLEEP_MULTIPLIER,
            delay_after=0.5 * SLEEP_MULTIPLIER,
        )

        # Клик на кнопку войти по логину/паролю
        selenium.click(
            selenium.get_tag("data-marker", "login-button"),
            delay_before=0.5 * SLEEP_MULTIPLIER,
            delay_after=0.5 * SLEEP_MULTIPLIER,
        )

        all_cookies = selenium.driver.get_cookies()["cookies"]
        logging.info(
            f"Selenium browser intercept {len(all_cookies)} cookies: {all_cookies}"
        )

        selenium.driver.close()

        cookies = {
            x["name"]: x["value"]
            for x in map_(
                filter_(
                    all_cookies, lambda x: x["name"] in AutoRegisterConfig.DUMP_COOKIES
                )
            )
        }

        device = {"user_agent": AutoRegisterConfig.USER_AGENT_TEMPLATE}
        new_data = {"device": device, "cookies": cookies}

        return new_data
