import logging

from appium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.wait import WebDriverWait

from src.logic.appium_logic.config import APPIUM_CONFIG, APPIUM_URL


class AppiumDriver:
    def __init__(self):
        self.driver = self.create_driver()
        logging.info(f"Created driver: {self.driver}")

    def create_driver(self):
        return webdriver.Remote(APPIUM_URL, desired_capabilities=APPIUM_CONFIG)

    def quit(self):
        self.driver.quit()

    def element_by_name(self, name, timeout=1, by=None):
        if not by:
            by = By.ID
        logging.info(f'Searching for element "{name}". Timeout: {timeout}')
        element = WebDriverWait(self.driver, timeout=timeout).until(
            lambda d: d.find_element(by, name)
        )
        logging.info(f"Element found: {element}")
        return element

    def click(self, name, timeout=None, by=None, raise_exception=True):
        try:
            element = self.element_by_name(name, timeout, by)
            element.click()
        except Exception as e:
            logging.error(e)
            if raise_exception:
                raise e
