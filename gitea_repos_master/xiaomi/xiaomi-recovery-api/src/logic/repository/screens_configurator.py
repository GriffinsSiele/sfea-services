from selenium.webdriver.common.by import By

from src.interfaces.abstract_browser import AbstractBrowser
from src.logic.repository.screens_constructor import ScreenConstructor
from src.logic.repository.screens_repository import ScreensRepository


class ScreensRepositoryConfigurator:
    @staticmethod
    def make(browser: AbstractBrowser) -> ScreensRepository:
        repository = ScreensRepository()
        repository.add_page(
            "main_page",
            ScreenConstructor.main_screen(
                browser=browser,
                definitions=[
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/div/div/div[2]/form/span/input',
                    ),
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/div[1]/div/form/div/div[2]/div[2]/div/div/div/div/input',
                    ),
                    (
                        By.XPATH,
                        '//*[@id="account"]',
                    ),
                ],
                input_fields=[
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/div/div/div[2]/form/span/input',
                    ),
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/div[1]/div/form/div/div[2]/div[2]/div/div/div/div/input',
                    ),
                    (
                        By.XPATH,
                        '//*[@id="account"]',
                    ),
                ],
            ),
        )
        repository.add_page(
            "email_page",
            ScreenConstructor.main_screen(
                browser=browser,
                definitions=[
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/form/div[2]/div/div/div/div/div/div[1]/span[2][contains(text(), "E-mail")]',
                    )
                ],
                input_fields=[
                    (
                        By.XPATH,
                        '//*[@id="type"]',
                    ),  # поле с выбором поиска по телефону или E-mail
                    (
                        By.XPATH,
                        '//*[@id="type" and @aria-expanded="true"]',
                    ),  # появилось всплывающее окно
                    (
                        By.XPATH,
                        '//*[@id="type" and @aria-activedescendant="type_list_1"]',
                    ),  # выбрано E-mail
                    (
                        By.XPATH,
                        '//*[@id="type" and @aria-expanded="false"]',
                    ),  # закрылось всплывающее окно
                ],
            ),
        )
        repository.add_page(
            "captcha_page",
            ScreenConstructor.captcha_screen(
                browser=browser,
                definitions=[
                    (
                        By.XPATH,
                        "/html/body/div[3]/div[2]/div[3]/div/div/div/div[1]/div/img[@src]",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div[3]/div/div[2]/div/div[2]/div[2]/form/span/span/img[@src]",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div[3]/div/div[2]/div/div[2]/div[2]/form/div[1]/div/div[2]/img[@src]",
                    ),
                ],
                input_fields=[
                    (
                        By.XPATH,
                        "/html/body/div[3]/div/div[2]/div/div[2]/div[2]/form/span/input",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div[3]/div[2]/div[3]/div/div/div/div[1]/input",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div[3]/div/div[2]/div/div[2]/div[2]/form/div[1]/div/div[1]/div/input",
                    ),
                ],
                payloads=[
                    (
                        By.XPATH,
                        "/html/body/div[3]/div[2]/div[3]/div/div/div/div[1]/div/img",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div[3]/div/div[2]/div/div[2]/div[2]/form/span/span/img",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div[3]/div/div[2]/div/div[2]/div[2]/form/div[1]/div/div[2]/img",
                    ),
                ],
                buttons=[
                    (
                        By.XPATH,
                        "/html/body/div[3]/div[2]/div[3]/div/div/div/div[2]/button",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div[3]/div/div[2]/div/div[2]/div[2]/form/div[2]/button",
                    ),
                ],
            ),
        )

        repository.add_page(
            "captcha_not_solved_page",
            ScreenConstructor.result_screen(
                browser=browser,
                definitions=[
                    (
                        By.XPATH,
                        '/html/body/div[3]/div/div[2]/div/div[2]/div[2]/form/div[1][contains(text(), "Некорректный проверочный код")]',
                    ),
                    (
                        By.XPATH,
                        '/html/body/div[3]/div[2]/div[3]/div/div/div/div[1]/p[2][contains(text(), "Неверный или устаревший код")]',
                    ),
                    (
                        By.XPATH,
                        '/html/body/div[3]/div/div[2]/div/div[2]/div[2]/form/div[1]/div[2][contains(text(), "Некорректный проверочный код")]',
                    ),
                ],
            ),
        )

        repository.add_page(
            "found_page",
            ScreenConstructor.result_screen_found(
                browser=browser,
                definitions=[
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/div/div/div/div[2]/form/ul/li[1][contains(text(), "Чтобы подтвердить личность, вам необходимо отправить сообщение со своего номера телефона ")]',
                    ),
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/div/div/div/div[2]/form/ul/li[2][contains(text(), "После отправки сообщения нажмите ")]',
                    ),
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/div/div/div/div[2]/div[contains(text(), "Нам нужно проверить действительность вашего адреса эл. почты для проверки вашей личности.")]',
                    ),
                ],
                extra_info=[
                    (
                        "emails",
                        (
                            By.XPATH,
                            '//*[@id="root"]/div/div/div/div/div/div/div[2]/form/div[1]/span[2]/span',
                        ),
                    ),
                ],
            ),
        )

        repository.add_page(
            "not_found_page",
            ScreenConstructor.result_screen(
                browser=browser,
                definitions=[
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/div[1]/div/form/div/div[2]/div[2]/div/div[2]/span[contains(text(), "Аккаунт Xiaomi, соответствующий предоставленной вами информации, не обнаружен.")]',
                    ),
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/div/div/div[2]/form/div[2]/span[contains(text(), "Аккаунт Xiaomi, соответствующий предоставленной вами информации, не обнаружен.")]',
                    ),
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/form/div[3]/div/div[2]/div[contains(text(), "Этот аккаунт не существует")]',
                    ),
                ],
            ),
        )

        repository.add_page(
            "current_country_selector",
            ScreenConstructor.result_screen(
                browser=browser,
                definitions=[
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/form/div[3]/div/div/div/span/span/span/span/span',
                    )
                ],
            ),
        )

        repository.add_page(
            "countries_switcher",
            ScreenConstructor.result_screen(
                browser=browser,
                definitions=[
                    (
                        By.XPATH,
                        '//*[@id="root"]/div/div/div/form/div[3]/div/div/div/span/span/span',
                    ),
                ],
            ),
        )

        repository.add_page(
            "countries_page",
            ScreenConstructor.result_screen(
                browser=browser,
                definitions=[
                    (
                        By.XPATH,
                        "/html/body/div[4]/div/div[3]/div/div/div/div/div[2]/div/div[2]",
                    ),
                    (
                        By.XPATH,
                        "/html/body/div[3]/div/div[3]/div/div/div/div/div[2]/div/div[2]",
                    ),
                ],
            ),
        )

        return repository
