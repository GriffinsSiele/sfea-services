import logging
import pathlib
from datetime import datetime

from putils_logic.putils import PUtils
from selenium.common import WebDriverException
from undetected_chromedriver import Chrome

from src.browser.selenium_utils import webdriver_exception_handler
from src.config.settings import DEFAULT_FOLDER
from src.logic.utils import random_string

_current_path = pathlib.Path(__file__).parent.absolute()
_base_dir = PUtils.bp(_current_path, "..", "..")
_default_path = PUtils.bp(_base_dir, DEFAULT_FOLDER)


class UtilitiesMixin:
    @staticmethod
    def _check_and_create_path_to_save(
        filename: str, path_to_save: str | None = None
    ) -> str:
        """Строит путь к файлу относительное DEFAULT_FOLDER (определяется
        в настройках проекта) на основе переданных параметров.

        :param filename: название файла
        :param path_to_save: директория для хранения файла
        :return: полный абсолютный путь к файлу
        """
        path_to_save = (
            PUtils.bp(_default_path, path_to_save) if path_to_save else _default_path
        )

        if not PUtils.is_dir_exists(path_to_save):
            PUtils.mkdir(path_to_save)

        return PUtils.bp(path_to_save, filename)


class SaveAsPngMixin(UtilitiesMixin):
    driver: Chrome
    default_screenshot_filename = "screenshot.png"

    def save_page_as_png(
        self, filename: str | None = None, path_to_save: str | None = None
    ) -> str:
        """
        Сохраняет текущую страницу как изображение в формате png.

        :param filename: название файла (не обязательный параметр,
        если не указан - будет присвоено значение по умолчанию "screenshot.png")
        :param path_to_save: путь для сохранения файла (не обязательный параметр).
        :return абсолютный путь к файлу или пустую строку, если не удалось сохранить файл.
        """
        if not filename:
            filename = self.default_screenshot_filename
        path = self._check_and_create_path_to_save(
            filename=filename, path_to_save=path_to_save
        )
        try:
            self.driver.save_screenshot(path)
        except WebDriverException:
            logging.warning(f"Cannot take {path} screenshot")
            return ""
        return path


class SaveAsHtmlMixin(UtilitiesMixin):
    driver: Chrome
    default_html_filename = "page.html"

    def save_page_as_html(
        self, filename: str | None = None, path_to_save: str | None = None
    ) -> str:
        """
        Сохраняет текущую страницу в формате html.

        :param filename: название файла (не обязательный параметр,
        если не указан - будет присвоено значение по умолчанию "page.html")
        :param path_to_save: путь для сохранения файла (не обязательный параметр).
        :return абсолютный путь к файлу.
        """
        if not filename:
            filename = self.default_html_filename
        html_path = self._check_and_create_path_to_save(
            filename=filename, path_to_save=path_to_save
        )
        html = self.driver.page_source
        with open(html_path, "w") as file:
            file.write(html)
        return html_path


class SaveScreenMixin(SaveAsPngMixin, SaveAsHtmlMixin):
    @webdriver_exception_handler
    def save_state(self, suffix=None) -> str:
        """Сохраняет текущий экран в форматах png и html."""
        if not suffix:
            suffix = random_string(length=8)
        current_data = datetime.now().strftime("%Y_%m_%d_%H_%M_%S")
        path_to_save = f"unknown_screen_{suffix}_{current_data}"
        self.save_page_as_png(path_to_save=path_to_save)
        self.save_page_as_html(path_to_save=path_to_save)
        return path_to_save
