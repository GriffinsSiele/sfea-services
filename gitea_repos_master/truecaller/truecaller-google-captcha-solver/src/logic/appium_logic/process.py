import logging
from datetime import datetime
from time import sleep

from putils_logic.putis import PUtils
from selenium.webdriver.common.by import By

from src.logic.appium_logic.driver import AppiumDriver
from src.logic.microphone.record import RecordAudio
from src.logic.whisper_logic.whisper_solver import Whisper


class AppiumProcess:
    def __init__(self):
        self.appium = AppiumDriver()

    @property
    def driver(self):
        return self.appium.driver

    def activate(self):
        if "needs to verify your account" not in self.driver.page_source:
            logging.info("Not banned")
            return True

        self.appium.click("com.truecaller:id/suspensionActionButton", timeout=2)
        self.appium.click(
            "//android.widget.Button[@text='Get an audio challenge']",
            timeout=3,
            by=By.XPATH,
        )

        self.appium.click(
            "//android.widget.Button[@text='Press PLAY to listen']",
            timeout=3,
            by=By.XPATH,
        )

        if not PUtils.is_dir_exists("audio"):
            PUtils.mkdir("audio")

        filename = RecordAudio.record(PUtils.bp("audio", str(datetime.now()) + ".wav"))
        text = Whisper.decode(filename)
        logging.info(f"text: {text}")

        element = self.appium.element_by_name(
            "//android.widget.EditText[@resource-id='audio-response']",
            by=By.XPATH,
            timeout=3,
        )
        element.send_keys(text)

        self.appium.click(
            "//android.widget.Button[@resource-id='recaptcha-verify-button']",
            timeout=3,
            by=By.XPATH,
        )

        sleep(5)

        try:
            self.appium.element_by_name(
                "//android.widget.Button[@resource-id='recaptcha-verify-button']",
                timeout=3,
                by=By.XPATH,
            )
            return False
        except Exception:
            return True
