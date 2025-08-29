import logging
import random
from time import sleep

from selenium.webdriver import ActionChains, Keys
from selenium.webdriver.common.by import By

from src.misc.spline import SplineGenerator


class HumanBehaviour:
    def __init__(self, driver):
        self.driver = driver

    def random_move(self, element, delay=8, with_hold=False):
        action = ActionChains(self.driver, int(delay * random.uniform(0.8, 1.2)))

        try:
            action.move_to_element(element)
        except Exception as e:
            logging.error(f'Error in move_to_element: {e}')

        action.click_and_hold() if with_hold else action.click()

        try:
            points_x, points_y = SplineGenerator.generate_points()
            for x, y in zip(points_x, points_y):
                action.move_by_offset(x, y)

            action.release()
            action.perform()
        except Exception as e:
            e = str(e).replace("\n", "")
            logging.error(f'Error while moving mouse: {e}')

    def type(self, element, text):
        for char in text:
            sleep(random.uniform(0.1, 0.3))
            element.send_keys(char)

    def random_click(self, double_click=False):
        action = ActionChains(self.driver, 10)

        points_x, points_y = SplineGenerator.generate_offsets()
        size = random.randint(2, 4)
        for x, y in zip(points_x[:size], points_y[:size]):
            action.move_by_offset(x, y)

        action.click()

        if double_click:
            sleep(random.uniform(0.2, 0.5))
            action.click()

    def click(self, element):
        ActionChains(self.driver).move_to_element(element).click().perform()

    def wait(self, delay, delta=0.2):
        d = delay + random.uniform(-delta, delta)
        sleep(d if d > 0 else 0.1)

    def scroll(self, is_down=True):
        html = self.driver.find_element(By.TAG_NAME, 'html')
        if html:
            key = Keys.PAGE_DOWN if is_down else Keys.PAGE_UP
            logging.debug(f'Scrolling by key: {key}')
            html.send_keys(key)
