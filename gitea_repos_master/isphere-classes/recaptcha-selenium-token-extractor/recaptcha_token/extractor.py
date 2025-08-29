import logging
from enum import Enum

from recaptcha_token.detector import SitekeyDetector


class CaptchaVersion(Enum):
    V2 = 'V2'
    V3 = 'V3'


_scripts_by_version = {
    CaptchaVersion.V2: 'return await grecaptcha.getResponse()',
    CaptchaVersion.V3: "return await grecaptcha.execute('{}', {{action: '{}'}})",
}


class TokenExtractor:
    def __init__(self, selenium_driver, version=CaptchaVersion.V3, debug=False):
        self.selenium_driver = selenium_driver
        self.version = version

        self.debug = debug

        self._sitekey = None
        self._action = None

    @property
    def sitekey(self):
        return self._sitekey

    @sitekey.setter
    def sitekey(self, new_sitekey):
        self._sitekey = new_sitekey

    @property
    def action(self):
        return self._action

    @action.setter
    def action(self, new_action):
        self._action = new_action

    def extract(self):
        if self.version == CaptchaVersion.V3 and not self._sitekey:
            self._sitekey = SitekeyDetector.find(self.selenium_driver.page_source)
            if not self._sitekey:
                raise Exception('Sitekey is not set')
        if self.version == CaptchaVersion.V3 and not self.action:
            raise Exception('Action is not set')

        script = _scripts_by_version[self.version].format(self.sitekey, self.action)
        if self.debug:
            logging.debug(f'Script generated: [{script}]')

        try:
            return self.selenium_driver.execute_script(script)
        except Exception as e:
            message = f'Exception occurred while executing script: {e}'
            if self.debug:
                logging.debug(message)
            raise Exception(message)