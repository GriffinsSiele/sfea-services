import logging
import random

from selenium.common import TimeoutException
from selenium.webdriver.support import expected_conditions as EC

from selenium.webdriver.support.wait import WebDriverWait
from seleniumwire.undetected_chromedriver.v2 import Chrome, ChromeOptions

from src.selenium.screens import screen_sizes


class SeleniumDriver:
    def __init__(self, headless=True, proxy=None, randomize_client=True, profile=None):
        self.headless = headless
        self.proxy = proxy
        self.randomize_client = randomize_client
        self.profile = profile

        self._create_options()
        self._create_seleniumwire_options()

        self._create_driver()
        self._after_create_driver()

    def _create_driver(self):
        self._driver = Chrome(options=self.options,
                              seleniumwire_options=self.seleniumwire_options,
                              suppress_welcome=True)

    def _after_create_driver(self):
        self.driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")

        self.driver.set_page_load_timeout(40)

    def _create_options(self):
        self.options = ChromeOptions()

        self._set_headless()
        self._set_screen_size()
        self._set_extra_options()

    def _set_headless(self):
        if self.headless:
            self.options.add_argument('--headless')

    def _set_screen_size(self):
        screen_size = (1270, 800)
        if self.randomize_client:
            screen_size = random.choice(screen_sizes)

        self.options.add_argument(f"--window-size={screen_size[0]},{screen_size[1]}")

    def _set_extra_options(self):
        self.options.add_argument('--no-sandbox')
        self.options.add_argument('--ignore-certificate-errors-spki-list')
        self.options.add_argument('--ignore-ssl-errors')
        self.options.add_argument('--lang=ru-RU')
        self.options.add_argument('--ssl-insecure')
        self.options.add_argument("--disable-popup-blocking")
        self.options.add_argument("--disable-notifications")
        self.options.add_argument("--disable-gpu")

        if self.profile:
            self.options.add_argument(f"--user-data-dir={self.profile}")

    def _create_seleniumwire_options(self):
        self.seleniumwire_options = {}
        if self.proxy:
            self.seleniumwire_options = {
                'proxy': {
                    'http': self.proxy['http'],
                    'https': self.proxy['http'],
                }
            }

    def get_tag(self, option, identifier, limit=1, log_error=True):
        try:
            return WebDriverWait(self.driver, limit).until(EC.presence_of_element_located((option, identifier)))
        except TimeoutException as e:
            if log_error:
                logging.error(f'Not found element [{option}={identifier}] after {limit} sec. {e}')

    def get_multiple_tags(self, option1, id1, option2, id2, limit=1):
        try:

            def condition(driver):
                return driver.find_element(option1, id1) or \
                       driver.find_element(option2, id2)

            return WebDriverWait(self.driver, limit).until(condition)
        except TimeoutException as e:
            logging.error(f'Not found element after {limit} sec. {e}')

    @property
    def driver(self):
        return self._driver
