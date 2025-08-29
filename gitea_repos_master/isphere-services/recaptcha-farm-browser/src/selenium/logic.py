import logging
import random
import re
import time

from pydash import get
from recaptcha_token.extractor import TokenExtractor, CaptchaVersion
from request_logic.exceptions import ProxyBlocked
from selenium.common import TimeoutException, WebDriverException
from selenium.webdriver import ActionChains, Keys
from selenium.webdriver.common.by import By

from src.selenium.human_behaviour import HumanBehaviour
from src.selenium.selenium import SeleniumDriver


class SeleniumLogic:
    def __init__(self,
                 url,
                 mongoserver,
                 known_tags=None,
                 scenarios=None,
                 tokens=None,
                 profile=None,
                 headless=True,
                 proxy=None):
        if tokens is None:
            tokens = []
        if known_tags is None:
            known_tags = []
        if scenarios is None:
            scenarios = {}

        self.url = url
        self.proxy = proxy
        self.headless = headless
        self.profile = profile

        self.tokens = tokens

        self.mongoserver = mongoserver
        self.known_tags = known_tags
        self.scenarios = scenarios

        self.create_driver()

    def create_driver(self):
        self.selenium_driver = SeleniumDriver(headless=self.headless,
                                              proxy=self.proxy,
                                              randomize_client=False,
                                              profile=self.profile)
        self.human_behaviour = HumanBehaviour(self.selenium_driver.driver)

    def prepare(self):
        start_time = time.time()
        try:
            self.selenium_driver.driver.get(self.url)
            logging.info('Page loaded')
        except TimeoutException:
            raise ProxyBlocked('Нет доступа к сайту. Вероятно, прокси заблокирован')
        except WebDriverException:
            raise ProxyBlocked('Нет доступа к сайту. Прокси заблокирован точно')

        if '502 Bad Gateway' in self.selenium_driver.driver.page_source or \
                len(self.selenium_driver.driver.page_source) < 500:
            raise ProxyBlocked('Нет доступа к сайту. Прокси заблокирован точно')

        self.human_behaviour.wait(1)

        end_time = time.time()
        logging.info(f'Preparing done: {round(end_time - start_time, 2)} sec.')

    def simulate(self):
        actions = [{
            'text': 'Extract token',
            'function': self.simulate_0,
            'weight': 40,
        }, {
            'text': 'Random scroll',
            'function': self.simulate_1,
            'weight': 10,
        }, {
            'text': 'Simulation of search string on page',
            'function': self.simulate_2,
            'weight': 10,
        }, {
            'text': 'Random mouse moves',
            'function': self.simulate_3,
            'weight': 10,
        }, {
            'text': 'Delay',
            'function': self.simulate_4,
            'weight': 10,
        }, {
            'text': 'Random click',
            'function': self.simulate_5,
            'weight': 10,
        }, {
            'text': 'Random double click',
            'function': self.simulate_6,
            'weight': 10,
        }, {
            'text': 'Execution of prepared scenarios',
            'function': self.simulate_7,
            'weight': 15,
        }]

        weights = list(map(lambda x: x['weight'], actions))
        action = random.choices(actions, weights=weights)[0]
        logging.info(f'Action: {action["text"]}')
        try:
            action['function']()
            self.human_behaviour.wait(2)
        except Exception as e:
            logging.error(f'Exception occurred while action [{action["text"]}]: {e}')

    def simulate_0(self):
        for token in self.tokens:
            start_time = time.time()
            try:
                extractor = TokenExtractor(self.selenium_driver.driver, version=CaptchaVersion.V3, debug=True)
                extractor.action = token['action']
                extractor.sitekey = token['sitekey']

                regex = get(token, 'regex')

                token = extractor.extract()

                # Если есть токен и (нет регулярки для него или (она есть и токен совпадает))
                if token and ((not regex) or (regex and re.match(regex, token))):
                    self.mongoserver.add_v3(token, extractor.sitekey, extractor.action)

                end_time = time.time()
                logging.info(f'Extracting token done: {round(end_time - start_time, 2)} sec.')
            except Exception as e:
                logging.error(f'Error during getting token: {e}')

            self.human_behaviour.wait(2)

    def simulate_1(self):
        self.human_behaviour.scroll(is_down=random.choice([True, False]))
        self.human_behaviour.wait(3)

    def simulate_2(self):
        form = self.selenium_driver.get_tag(By.TAG_NAME, 'html')
        ActionChains(self.selenium_driver.driver).key_down(Keys.CONTROL).perform()
        self.human_behaviour.wait(0.1)
        form.send_keys('f')
        self.human_behaviour.wait(0.1)
        ActionChains(self.selenium_driver.driver).key_up(Keys.CONTROL).perform()
        self.human_behaviour.wait(0.1)
        self.human_behaviour.type(form, random.choice(['Запре', 'ГАЗ', 'Страх', 'Кат']))
        self.human_behaviour.wait(2)

    def simulate_3(self):
        tag = random.choice(self.known_tags)
        if tag:
            form = self.selenium_driver.get_tag(tag[0], tag[1])
            self.human_behaviour.random_move(form)
        self.human_behaviour.wait(2)

    def simulate_4(self):
        self.human_behaviour.wait(3)

    def simulate_5(self):
        self.human_behaviour.random_click()
        self.human_behaviour.wait(2)

    def simulate_6(self):
        self.human_behaviour.random_click(double_click=True)
        self.human_behaviour.wait(2)

    def simulate_7(self):
        if not self.scenarios:
            return
        try:
            scenario_key = random.choice(list(self.scenarios.keys()))
            logging.info(f'Pick scenario #{scenario_key}')
            scenario = self.scenarios[scenario_key]
            for part in scenario:
                self.scenario_part(part)
                self.human_behaviour.wait(0.8)
            self.human_behaviour.wait(2)
        except Exception as e:
            raise e
        finally:
            self.selenium_driver.driver.get(self.url)

    def scenario_part(self, part):
        action, value = part.get('action'), part.get('value')
        logging.info(f'Scenario: {action}=[{value}]')
        if action == 'click':
            tag = self.selenium_driver.get_tag(value[0], value[1])
            self.human_behaviour.click(tag)
        if action == 'wait':
            self.human_behaviour.wait(value)
        if action == 'type':
            tag = self.selenium_driver.get_tag(value[0][0], value[0][1])
            self.human_behaviour.type(tag, random.choice(value[1]))
        if action == 'move':
            tag = self.selenium_driver.get_tag(value[0], value[1])
            self.human_behaviour.random_move(tag)
        if action == 'scroll':
            self.human_behaviour.scroll()
        if action == 'reload':
            self.selenium_driver.driver.get(self.url)
