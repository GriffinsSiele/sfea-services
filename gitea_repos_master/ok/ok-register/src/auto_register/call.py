import io
import logging
import os
import time
from PIL import Image

from datetime import datetime
from os.path import exists
from time import sleep

import yaml
from pydash import get, find, without
from selenium.webdriver.common.by import By
from twocaptcha import TwoCaptcha

from settings import RU_CAPTCHA
from src.auto_register.config import AutoRegisterConfig
from src.auto_register.selenium import SeleniumDriver

selenium_logger = logging.getLogger('seleniumwire')
selenium_logger.setLevel(logging.ERROR)

from selenium.webdriver.remote.remote_connection import LOGGER
LOGGER.setLevel(logging.WARNING)


class SeleniumRegister:
    def create_selenium(self):
        return SeleniumDriver(executable_path=AutoRegisterConfig.SELENIUM_DRIVER_PATH)

    def register(self, row, dump_path=None, dump_prefix=''):
        SLEEP_MULTIPLIER = AutoRegisterConfig.AVERAGE_TIMEOUT_MULTIPLIER

        now = datetime.now().strftime('%Y_%d_%m')
        DUMP_PATH = os.path.join(AutoRegisterConfig.DUMP_PATH_TEMPLATE.format(
            dump_prefix if dump_prefix else now)) if not dump_path else dump_path

        if exists(DUMP_PATH):
            with open(DUMP_PATH, 'r') as f:
                devices = yaml.safe_load(f)
        else:
            devices = []

        login = row.split(';')[0].strip()
        password = row.split(';')[1].strip()

        logging.info(f'Account: login={login}, password={password}')

        existed = find(devices, lambda x: x['login'] == login)
        if existed and existed['locked'] is False:
            logging.info('Pass account, because it was found in yaml')
            return

        selenium = self.create_selenium()

        logging.info(f'Selenium browser created: {selenium}')

        try:
            skip_captcha, locked = False, False
            selenium.driver.get('https://m.ok.ru/')
            sleep(2)

            selenium.click(selenium.get_tag(By.CLASS_NAME, "base-button_target"), delay_after=0.5 * SLEEP_MULTIPLIER)

            login_field = selenium.get_tag(By.ID, 'field_login')
            login_field.send_keys(login)

            password_field = selenium.get_tag(By.ID, 'field_password')
            password_field.send_keys(password)

            selenium.click(selenium.get_tag(By.CLASS_NAME, "base-button_target"), delay_after=0.5 * SLEEP_MULTIPLIER)

            text_page = selenium.get_tag(By.TAG_NAME, 'body')
            if 'Вы посмотрели все новые публикации' in text_page.text:
                skip_captcha = True
                locked = False

            if not skip_captcha:
                selenium.click(selenium.get_tag(By.CLASS_NAME, "base-button_target"),
                               delay_after=0.5 * SLEEP_MULTIPLIER)

                locked = False
                is_ok = self.solve_captcha(selenium, SLEEP_MULTIPLIER)
                logging.info(f'Try 1, status: {is_ok}')
                if not is_ok:
                    is_ok = self.solve_captcha(selenium, SLEEP_MULTIPLIER)
                    logging.info(f'Try 2, status: {is_ok}')
                    if not is_ok:
                        is_ok = self.solve_captcha(selenium, SLEEP_MULTIPLIER)
                        logging.info(f'Try 3, status: {is_ok}')
                        if not is_ok:
                            locked = True

                selenium.click(selenium.get_tag(By.CLASS_NAME, "base-button_target"),
                               delay_after=0.5 * SLEEP_MULTIPLIER)

            new_data = {
                'login': login,
                'password': password,
                'created_at': str(int(time.time())),
                'locked': locked,
            }

        except Exception as e:
            logging.error(e)
            new_data = {
                'login': login,
                'password': password,
                'created_at': str(int(time.time())),
                'locked': True,
            }

        selenium.driver.close()

        if existed:
            devices = without(devices, existed)

        devices.append(new_data)

        with open(DUMP_PATH, 'w') as file:
            yaml.dump(devices, file)

    def solve_captcha(self, selenium, SLEEP_MULTIPLIER):
        directory_captcha = 'data/captcha'
        if not os.path.exists(directory_captcha):
            os.makedirs(directory_captcha)

        path = f"{directory_captcha}/captcha_{datetime.now().strftime('%s')}.jpg"

        captcha = selenium.get_tag(By.ID, 'captcha')
        image = captcha.screenshot_as_png

        image_stream = io.BytesIO(image)
        im = Image.open(image_stream)
        rgb_im = im.convert('RGB')
        rgb_im.save(path)

        captcha_field = selenium.get_tag(By.ID, 'field_code')
        captcha_field.clear()

        logging.info('Send request to rucaptcha')
        solver = TwoCaptcha(RU_CAPTCHA)
        result = solver.normal(path)
        logging.info(f'Code: {result}')

        code = get(result, 'code')
        captcha_field.send_keys(code)

        selenium.click(selenium.get_tag(By.CLASS_NAME, "base-button_target"), delay_after=0.5 * SLEEP_MULTIPLIER)

        src = selenium.driver.page_source

        return not ('Invalid code. Please try again' in src or 'Неверный код' in src)
