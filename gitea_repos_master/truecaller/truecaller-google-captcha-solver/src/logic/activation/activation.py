from src.logic.activation.template_manager import TemplateManager
from src.logic.appium_logic.process import AppiumProcess


class ActivationManager:
    def __init__(self, token):
        self.token = token

    def start(self):
        tm = TemplateManager()
        filename_template = tm.create_template(self.token)
        tm.push_to_app(filename_template)

        return AppiumProcess().activate()
