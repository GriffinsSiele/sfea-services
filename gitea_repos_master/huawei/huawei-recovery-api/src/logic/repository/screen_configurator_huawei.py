from selenium.webdriver.common.by import By

from src.interfaces.abstract_screen_repo_conf import AbstractScreenRepositoryConfigurator
from src.logic.repository.screen import Screen
from src.logic.repository.screen_names import ScreenNames
from src.logic.repository.screen_repository import ScreenRepository


class ScreenRepositoryConfigurator(AbstractScreenRepositoryConfigurator):
    """Создает хранилище экранов и настраивает конфигурацию экранов для данного проекта."""

    @staticmethod
    def make() -> ScreenRepository:
        """Возвращает настроенный репозиторий экранов для данного проекта.

        :return: ScreenRepository.
        """
        repository = ScreenRepository()

        repository.add_screen(
            ScreenNames.MAIN,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        "/html/body/div/div/div[3]/div/div/div[2]/div[3]/div[1]/div/div/input",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div/div/div[3]/div/div/div[2]/div[5]/div/div/div",
                    ),
                ],
                input_fields=[
                    (
                        By.XPATH,
                        "/html/body/div/div/div[3]/div/div/div[2]/div[3]/div[1]/div/div/input",
                    )
                ],
                buttons=[
                    # Одна и та же кнопка, эта кнопка доступна для клика
                    (
                        By.XPATH,
                        "/html/body/div/div/div[3]/div/div/div[2]/div[5]/div/div/div",
                    ),
                    # Эта кнопка не доступна для клика
                    # !!! на сайте реализована валидация данных, из-за чего кнопка так же может быть не доступной
                    (
                        By.XPATH,
                        "/html/body/div/div/div[3]/div/div/div[2]/div[5]/div/div/div"
                        '[contains(@class, "hwid-disabled")]',
                    ),
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.MAIN,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        "/html/body/div/div/div/div/div[2]/div/div[2]/div[1]/div[1]/div/input",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div/div/div/div/div[2]/div/div[5]/div/div/div/span[2]",
                    ),
                ],
                input_fields=[
                    (
                        By.XPATH,
                        "/html/body/div/div/div/div/div[2]/div/div[2]/div[1]/div[1]/div/input",
                    )
                ],
                buttons=[
                    # Одна и та же кнопка, эта кнопка доступна для клика
                    (
                        By.XPATH,
                        "/html/body/div/div/div/div/div[2]/div/div[5]/div/div/div/span[2]",
                    ),
                    # Эта кнопка не доступна для клика
                    # !!! на сайте реализована валидация данных, из-за чего кнопка так же может быть не доступной
                    (
                        By.XPATH,
                        '/html/body/div/div/div/div/div[2]/div/div[5]/div/div/div[contains(@class, "hwid-disabled")]',
                    ),
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.CAPTCHA,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        "/html/body/div[2]/div[2]/div/div/div[2]/div/div[1]/div/div[1]/img[1][@src]",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div[2]/div[2]/div/div/div[2]/div/div[1]/div/div[1]/img[2][@src]",
                    ),
                ],
                payloads=[
                    # background image
                    (
                        By.XPATH,
                        "/html/body/div[2]/div[2]/div/div/div[2]/div/div[1]/div/div[1]/img[1]",
                    ),
                    # slider image
                    (
                        By.XPATH,
                        "/html/body/div[2]/div[2]/div/div/div[2]/div/div[1]/div/div[1]/img[2]",
                    ),
                ],
                buttons=[
                    # slider
                    (
                        By.XPATH,
                        "/html/body/div[2]/div[2]/div/div/div[2]/div/div[2]/div[2]",
                    )
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.EXTRA,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        "/html/body/div/div/div[6]/div[1]/div[1]/div/div/span/span"
                        '[contains(text(), "Невозможно сбросить пароль на этом веб-сайте. ")]',
                    ),
                ],
                buttons=[
                    # Ok
                    (By.XPATH, "/html/body/div/div/div[6]/div[1]/div[2]/div/div[2]"),
                    # Отмена
                    (By.XPATH, "/html/body/div/div/div[6]/div[1]/div[2]/div/div[1]/div"),
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.EXTRA,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        "/html/body/div/div[2]/div[1]/div[1]/div/div/span/span"
                        '[contains(text(), "Невозможно сбросить пароль на этом веб-сайте. ")]',
                    ),
                ],
                buttons=[
                    # Ok
                    (By.XPATH, "/html/body/div/div[2]/div[1]/div[2]/div/div[3]/div/div"),
                    # Отмена
                    (By.XPATH, "/html/body/div/div[2]/div[1]/div[2]/div/div[1]/div/div"),
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.RESULT_FOUND,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        "/html/body/div/div/div[3]/div/div/div[2]/div[1]/span/span"
                        '[contains(text(), "Сброс пароля с помощью ")]',
                    ),
                    (
                        By.XPATH,
                        "/html/body/div/div/div[3]/div/div/div[2]/div[2]/div"
                        '[contains(text(), "Если Вы по-прежнему используете ")]',
                    ),
                ],
                payloads=[
                    (
                        By.XPATH,
                        "/html/body/div/div/div[3]/div/div/div[2]/div[2]/div/span",
                    ),
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.RESULT_NOT_FOUND,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        "/html/body/div/div/div[3]/div/div/div[2]/div[3]/div[2]/div/span"
                        '[contains(text(), "Аккаунт недействительный или не поддерживается")]',
                    )
                ]
            ),
        )

        return repository
