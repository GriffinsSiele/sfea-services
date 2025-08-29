# simpler-register

1. `pip3 install -r requirements.txt`
2. В файле `register.py` задать количество учетных записей
3. Запустить генерацию учетных записей: `python3 ./register.py`
4. Данные будут сгенерированы в `./data/data_NOW.yml`
5. Задать .env файл по аналогии с .env.example с указанием данных для подключения к mongodb
6. В файле `migrate.py` указать путь к yml файлу
7. Запустить скрипт добавления учетных записей в mongodb: `python3 ./migrate`