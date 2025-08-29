import logging
import os
import time
from datetime import datetime
from os.path import exists
from time import sleep

import yaml
from faker import Faker
from pydash import get
from selenium.webdriver.common.by import By

from settings import SMS_ACTIVATE_TOKEN
from src.auto_register.config import AutoRegisterConfig
from src.auto_register.selenium import SeleniumDriver
from src.misc.smsactivate import SMSActivateAPI


class SeleniumRegister:
    def __init__(self, user_agent_version):
        self.user_agent_version = user_agent_version

    def create_selenium(self):
        user_agent = AutoRegisterConfig.USER_AGENT_TEMPLATE.format(
            self.user_agent_version, self.user_agent_version)

        return SeleniumDriver(
            executable_path=AutoRegisterConfig.SELENIUM_DRIVER_PATH,
            user_agent=user_agent)

    def register(self, dump_path=None):
        SLEEP_MULTIPLIER = AutoRegisterConfig.AVERAGE_TIMEOUT_MULTIPLIER

        now = datetime.now().strftime('%Y_%d_%m')
        DUMP_PATH = os.path.join(
            AutoRegisterConfig.DUMP_PATH_TEMPLATE.format(
                now)) if not dump_path else dump_path

        selenium = self.create_selenium()

        logging.info(f'Selenium browser created: {selenium}')

        sms_activate_api = SMSActivateAPI({'token': SMS_ACTIVATE_TOKEN})
        phone_info = sms_activate_api.get_icq()
        phone = get(phone_info, 'short_number', '')

        def sms_callback():
            logging.info('Wait for SMS')
            try:
                sms_code = sms_activate_api.get_sms()
            except Exception as e:
                logging.error(f'TimeoutException: {e}')
                sms_code = '000000'
            logging.info(f'SMS code: {sms_code}')
            return sms_code

        selenium.driver.get('http://web.icq.com/')
        sleep(2)

        # Принять и продолжить
        selenium.click(selenium.get_tag('data-action',
                                        "acceptAgreement",
                                        data_tag='button'),
                       delay_after=0.5 * SLEEP_MULTIPLIER)

        input_phone = selenium.get_tag(By.CLASS_NAME, 'imAuthPhone')

        input_phone.clear()
        input_phone.send_keys(phone)

        selenium.click(selenium.get_tag('data-action',
                                        "submit",
                                        data_tag='button'),
                       delay_after=0.5 * SLEEP_MULTIPLIER)

        verification_code = selenium.get_tag(By.CLASS_NAME, 'imAuthCode')

        try:
            code = sms_callback()
        except Exception as e:
            logging.error(e)
            sms_activate_api.cancel()
            selenium.driver.close()
            return

        for c in code:
            verification_code.send_keys(c)
            sleep(1)

        sleep(1)

        faker = Faker()
        first_name = faker.first_name()
        try:
            name_input = selenium.get_tag(By.CLASS_NAME, 'imField0')
        except Exception as e:
            logging.error(f'Неверный код из смс: {e}')
            selenium.driver.close()
            return

        name_input.send_keys(first_name)

        last_name = faker.last_name()
        surname_input = selenium.get_tag(By.CLASS_NAME, 'imField1')
        surname_input.send_keys(last_name)

        sleep(2)

        next = selenium.get_tag(By.CLASS_NAME, 'im-auth-submit')
        next.click()

        sleep(5)

        token = selenium.driver.execute_script(
            "return JSON.parse(window.localStorage.getItem('_AIM_connectData'));"
        )

        # try:
        #     password = self.create_password(selenium, phone, sms_callback)
        # except Exception as e:
        #     print(e)
        #     password = None

        selenium.driver.close()

        new_data = {
            'token': token['aimsid'],
            'phone_number': phone,
            'first_name': first_name,
            'last_name': last_name,
            'created_at': str(int(time.time())),
        }

        if exists(DUMP_PATH):
            with open(DUMP_PATH, 'r') as f:
                devices = yaml.safe_load(f)
        else:
            devices = []

        devices.append(new_data)

        with open(DUMP_PATH, 'w') as file:
            yaml.dump(devices, file)


    def create_password(self, selenium, phone, sms_callback):
        faker = Faker()
        password = faker.password()

        selenium.driver.get('https://icq.com/password/ru#')
        sleep(2)

        phone_field = selenium.get_tag(By.ID, 'contact')
        phone_field.send_keys(phone)

        verification_code = selenium.get_tag(By.ID, 'code')
        verification_code.send_keys(input('Captcha: '))

        submit = selenium.get_tag(By.XPATH, "//button[text()='Подтвердить']")
        submit.click()

        sms_field = selenium.get_tag(By.ID, 'sms')
        sms_field.send_keys(sms_callback())

        password_new = selenium.get_tag(By.ID, 'passwordnew')
        password_new.send_keys(password)
        password_conf = selenium.get_tag(By.ID, 'password')
        password_conf.send_keys(password)

        submit = selenium.get_tag(By.XPATH, "//button[text()='Подтвердить']")
        submit.click()

        sleep(2)

        return password
