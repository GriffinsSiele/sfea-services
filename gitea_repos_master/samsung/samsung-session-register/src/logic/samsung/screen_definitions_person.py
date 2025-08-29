"""
Модуль содержит определения для экранов сайта account.samsung.com.
Используются для определения страниц (главная страницы, страница с результатом)
и ключевых элементов на странице (поле ввода, кнопка поиска).
"""

from selenium.webdriver.common.by import By

SEARCH_BUTTON_PERSON = (By.XPATH, "/html/body/div[1]/main/div[1]/div[2]/div/button")
SEARCH_BUTTON_DISABLED_PERSON = (
    By.XPATH,
    '/html/body/div[1]/main/div[1]/div[2]/div/button[@disabled="disabled"]',
)
MAIN_SCREEN_DEFINITION_PERSON = (By.XPATH, '//*[@id="givenName1"]')
RESULT_SCREEN_DEFINITION_PERSON = (
    By.XPATH,
    '/html/body/div[1]/main/div[3]/div[2]/div[1]/button[contains(text(), "Повторить попытку")]',
)
