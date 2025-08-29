# ok-register

Скрипт автоматической активации учетных записей Одноклассников, купленных с hstock.org с использованием Selenium и RuCaptcha

## Процесс

1. Купить учетные записи: https://hstock.org/product/akkaunty-odnoklassniki-sposob-registratsii-avtoreg-sostoyanie-aktiv-zapolnennost-profilya-pustoy-cf8c10eb
2. Скачать в формате магазина
3. Поместить файл в директорию data
4. В файле register.py указать переменную name равной названию файла до точки (`abc.txt -> abc`)
5. Установить зависимости: `pip3 install -r requirements.txt`
6. Создать файл `.env` по аналогии с `.env.example`, указать переменные
7. Запустить скрипт `python3 ./register.py`
8. В директории data появиться `.yml` файл