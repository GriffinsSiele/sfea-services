from time import sleep

from pydash import find
from selenium.webdriver.common.by import By
from selenium.webdriver.support.wait import WebDriverWait
from seleniumwire import webdriver
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.firefox.options import Options


class SeleniumDriver:
    def __init__(self,
                 executable_path,
                 user_agent,
                 window_sizes=None,
                 serviceworker=True,
                 webnotifications=True,
                 accept_languages=None,
                 proxy=None):
        self.user_agent = user_agent
        self.window_sizes = window_sizes if window_sizes else (384, 854)
        self.serviceworker = serviceworker
        self.webnotifications = webnotifications
        self.accept_languages = accept_languages if accept_languages else 'ru-RU'
        self.proxy = proxy

        self.executable_path = executable_path

        self.profile = self._create_profile()
        self.driver = self._create_driver()

        import logging
        logger = logging.getLogger(
            'selenium.webdriver.remote.remote_connection')
        logger.setLevel(logging.WARNING)

    def _create_profile(self):
        profile = webdriver.FirefoxProfile()
        profile.set_preference("general.useragent.override", self.user_agent)
        profile.set_preference("dom.webnotifications.serviceworker.enabled",
                               self.serviceworker)
        profile.set_preference("dom.webnotifications.enabled",
                               self.webnotifications)
        profile.set_preference("intl.accept_languages", self.accept_languages)

        return profile

    def _create_options(self):
        options = Options()
        if self.proxy:
            options.proxy = self.proxy
        return options

    def _create_driver(self):
        driver = webdriver.Firefox(self.profile,
                                   options=self._create_options(),
                                   executable_path=self.executable_path)
        driver.set_window_size(self.window_sizes[0], self.window_sizes[1])

        return driver

    def get_tag(self, option, identificator, multiple=False, data_tag=None):
        if option == By.ID or option == By.TAG_NAME or option == By.CLASS_NAME or option == By.XPATH:
            return self.driver.find_elements(option, identificator) \
                if multiple else \
                self.driver.find_element(option, identificator)

        if isinstance(option, str) and option.startswith('data-'):
            divs = self.get_tag(By.TAG_NAME, data_tag, multiple=True)
            return find(divs,
                        lambda div: div.get_attribute(option) == identificator)

    def click(self, tag, delay_before=0, delay_after=0):
        if not delay_before:
            tag.click()
        else:
            element = WebDriverWait(self.driver, delay_before).until(
                EC.element_to_be_clickable(tag))
            self.driver.execute_script("arguments[0].click();", element)

        if delay_after:
            sleep(delay_after)

    def driver(self):
        return self.driver

    def __str__(self):
        return f'SeleniumDriver(user_agent: {self.user_agent}, window_sizes: {self.window_sizes}, accept_languages={self.accept_languages}, proxy={self.proxy})'
