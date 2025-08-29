# src.telegram package

## Submodules

## src.telegram.bot module

## src.telegram.telegram_api module

Модуль для работы с телеграм ботом.

URL адрес для работы с телеграм ботом:
[https://api.telegram.org](https://api.telegram.org)/bot<token>/METHOD_NAME

Отправить сообщение:
[https://api.telegram.org](https://api.telegram.org)/bot<token>/sendMessage
Отправить файл:
[https://api.telegram.org](https://api.telegram.org)/bot<token>/sendDocument

В теле сообщения обязательно передаем «chat_id»: self.chat_id

Пример отпавки файла из терминала:
curl -v -F «chat_id=569502265» -F [document=@/Users/users/Desktop/file.txt](mailto:document=@/Users/users/Desktop/file.txt) [https://api.telegram.org](https://api.telegram.org)/bot<TOKEN>/sendDocument

### *class* src.telegram.telegram_api.TelegramAPI(token, chat_id)

Базовые классы: [`AbstractTelegramAPI`](src.interfaces.md#src.interfaces.abstract_telegram_api.AbstractTelegramAPI)

#### send_file(file, send_as=None)

Отправляет файл в телеграм.

* **Параметры:**
  * **file** (`str`) – Путь к файлу.
  * **send_as** (`str` | `None`) – Изменить имя отправляемого файла.
* **Тип результата:**
  `str`
* **Результат:**
  Ответ клиента.

#### send_message(message)

Отправляет сообщение в телеграм.

* **Параметры:**
  **message** (`str`) – Сообщение.
* **Тип результата:**
  `str`
* **Результат:**
  Ответ клиента.

## Module contents
