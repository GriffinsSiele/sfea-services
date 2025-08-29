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
                    (By.XPATH, '//*[@id="formBean_username"]'),
                    (By.XPATH, '//*[@id="btnSubmit"]'),
                ],
                input_fields=[
                    (
                        By.XPATH,
                        '//*[@id="formBean_username"]',
                    )
                ],
                buttons=[
                    # Одна и та же кнопка, эта кнопка доступна для клика
                    (By.XPATH, '//*[@id="btnSubmit"]'),
                    # Эта кнопка не доступна для клика
                    # !!! на сайте реализована валидация данных, из-за чего кнопка так же может быть не доступной
                    (By.XPATH, '//*[@id="btnSubmit"][contains(@class, "disabled")]'),
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.CAPTCHA,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        "/html/body/div[8]/div[2]/div/div/div[2]/div/div[1]/div/div[1]/img[1][@src]",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div[8]/div[2]/div/div/div[2]/div/div[1]/div/div[1]/img[2][@src]",
                    ),
                ],
                payloads=[
                    # background image
                    (
                        By.XPATH,
                        "/html/body/div[8]/div[2]/div/div/div[2]/div/div[1]/div/div[1]/img[1]",
                    ),
                    # slider image
                    (
                        By.XPATH,
                        "/html/body/div[8]/div[2]/div/div/div[2]/div/div[1]/div/div[1]/img[2]",
                    ),
                    # iframe (капча находится в iframe)
                    (By.XPATH, "/html/body/div[8]/iframe"),
                    # форма капчи
                    (By.XPATH, '/html/body/div[8][contains(@style, "block")]'),
                ],
                buttons=[
                    # slider
                    (
                        By.XPATH,
                        "/html/body/div[8]/div[2]/div/div/div[2]/div/div[2]/div[2]",
                    )
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.RESULT_FOUND,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        '//*[@id="checkAuthCodeFrom"]/div/div[1][contains(text(), "Сброс пароля")]',
                    ),
                    (
                        By.XPATH,
                        '//*[@id="checkAuthCodeFrom"]/div/div[2]',
                    ),
                ],
                payloads=[
                    (By.XPATH, '//*[@id="checkAuthCodeFrom"]/div/div[2]/span'),
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.RESULT_FOUND,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        '//*[@id="checkAuthCodeFrom"]/div/div[1][contains(text(), "Reset password with")]',
                    ),
                    (
                        By.XPATH,
                        '//*[@id="checkAuthCodeFrom"]/div/div[2][contains(text(), "If your")]',
                    ),
                ],
                payloads=[
                    (By.XPATH, '//*[@id="checkAuthCodeFrom"]/div/div[2]/span'),
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.BLOCKED,
            # Аккаунт временно заблокирован, получаем сообщение:
            # "Выполнение этого действия сейчас может быть небезопасным. Повторите попытку через 24 часа."
            # В английской версии:
            # "It may not be safe to do this right now. Please try again in 24 hours."
            Screen(
                definitions=[
                    # сообщение
                    (By.XPATH, '//*[@id="authTest_DialogSimple_text"]'),
                    # кнопка подтверждения
                    (By.XPATH, '//*[@id="rightBtn"]'),
                ],
            ),
        )

        repository.add_screen(
            ScreenNames.RESULT_NOT_FOUND,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        '//*[@id="error-tips"][contains(text(), "Информация аккаунта не существует")]',
                    )
                ]
            ),
        )

        repository.add_screen(
            ScreenNames.RESULT_NOT_FOUND,
            Screen(
                definitions=[
                    (
                        By.XPATH,
                        '//*[@id="error-tips"][contains(text(), "Account Information Does Not Exist")]',
                    )
                ]
            ),
        )

        return repository
