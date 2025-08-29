"""
Модуль содержит определения для экранов сайта account.samsung.com.
Используются для определения страниц (главная страницы, страница с результатом)
и ключевых элементов на странице (поле ввода, кнопка поиска).
"""

from selenium.webdriver.common.by import By

CONTINUE_BUTTON_NAME = (By.XPATH, "/html/body/div[1]/main/div/div[2]/div[1]/button")
CONTINUE_BUTTON_DISABLED_NAME = (
    By.XPATH,
    '/html/body/div[1]/main/div/div[2]/div[1]/button[@disabled="disabled"]',
)

SEARCH_BUTTON_NAME = (By.XPATH, "/html/body/div[1]/main/div/div[2]/div[3]/button")
SEARCH_BUTTON_DISABLED_NAME = (
    By.XPATH,
    '/html/body/div[1]/main/div/div[2]/div[3]/button[@disabled="disabled"]',
)

MAIN_SCREEN_DEFINITION_NAME = (By.XPATH, '//*[@id="recoveryId"]')
RESULT_SCREEN_DEFINITION_NAME = (
    By.XPATH,
    '//*[@id="tryAgainButton"][contains(text(), "Повторить попытку")]',
)

MONTH_FIELD_DEFINITION = (By.XPATH, '//*[@id="month"]/option[13]')
