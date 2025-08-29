"""
Модуль содержит определения для экранов сайта account.samsung.com.
Используются для определения страниц (главная страницы, страница с результатом)
и ключевых элементов на странице (поле ввода, кнопка поиска).
"""

from selenium.webdriver.common.by import By

MAIN_SCREEN_DEFINITION = (By.XPATH, '//*[@id="banner-contents"]/div[2]/button')
MAIN_SCREEN_DEFINITION_BUTTON = (By.XPATH, '//*[@id="banner-contents"]/div[2]/button')

INPUT_FIELD = (By.XPATH, '//*[@id="iptLgnPlnID"]')
SEARCH_BUTTON = (By.XPATH, '//*[@id="signInButton"]')
SEARCH_BUTTON_DISABLED = (By.XPATH, '//*[@id="signInButton" and @disabled]')
MAIN_SCREEN_DEFINITION_AUTH = (By.XPATH, '//*[@id="signInButton"]')
RESULT_SCREEN_DEFINITION = (By.XPATH, '//*[@id="02"]')
