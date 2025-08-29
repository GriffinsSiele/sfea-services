from selenium.webdriver.common.by import By

from src.captcha.captcha_service import CaptchaService
from src.interfaces.abstract_google_captcha_service import AbstractGoogleCaptchaService


class GoogleCaptcha(CaptchaService, AbstractGoogleCaptchaService):
    image_tag: tuple[By, str] = (By.XPATH, '//*[@id="captchaimg"]')
    input_tag: tuple[By, str] = (By.XPATH, '//input[@type="text"]')
