from time import sleep

from pydash import find
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.wait import WebDriverWait
from selenium_profiles.driver import driver as driver
from selenium_profiles.profiles import profiles


class SeleniumDriver:
    def __init__(self):
        self.driver = self._create_driver()

    def _create_driver(self):
        driver_ = driver()
        return driver_.start(profiles.Android(), uc_driver=True)

    def get_tag(self, option, identificator, multiple=False):
        if option == By.ID or option == By.TAG_NAME:
            return (
                self.driver.find_elements(option, identificator)
                if multiple
                else self.driver.find_element(option, identificator)
            )

        if option == "data-marker":
            divs = self.get_tag(By.TAG_NAME, "div", multiple=True)
            return find(divs, lambda div: div.get_attribute(option) == identificator)

    def click(self, tag, delay_before=0, delay_after=0):
        if not delay_before:
            tag.click()
        else:
            element = WebDriverWait(self.driver, delay_before).until(
                EC.element_to_be_clickable(tag)
            )
            self.driver.execute_script("arguments[0].click();", element)

        if delay_after:
            sleep(delay_after)

    def driver(self):
        return self.driver
