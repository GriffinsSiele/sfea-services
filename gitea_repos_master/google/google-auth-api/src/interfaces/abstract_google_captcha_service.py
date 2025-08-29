from selenium.webdriver.common.by import By

from src.interfaces.abstract_captcha_service import AbstractCaptchaService


class AbstractGoogleCaptchaService(AbstractCaptchaService):
    image_tag: tuple[By, str]
    input_tag: tuple[By, str]
