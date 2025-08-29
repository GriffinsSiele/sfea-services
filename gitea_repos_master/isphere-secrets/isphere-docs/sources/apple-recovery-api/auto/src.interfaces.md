# src.interfaces package

## Submodules

## src.interfaces.abstract_base64_converter module

Модуль содержит интерфейс для работы с конвертером изображений.

### *class* src.interfaces.abstract_base64_converter.AbstractBase64Converter

Базовые классы: `ABC`

Интерфейс для работы с конвертером изображений base64 в bytes.

#### *abstract static* covert_to_bytes(base64_string)

Конвертирует изображение base64 в bytes

* **Параметры:**
  **base64_string** (`str`) – Изображение в формате base64.
* **Response:**
  Изображение в формате bytes.
* **Тип результата:**
  `bytes`

## src.interfaces.abstract_captcha_service module

Модуль содержит интерфейс для работы с сервисом решения капчи.

### *class* src.interfaces.abstract_captcha_service.AbstractCaptchaService

Базовые классы: `ABC`

Интерфейс для работы с сервисом решения капчи.

#### *abstract async* post_captcha(image, timeout=0)

Отправляет изображение для решения капчи.

* **Параметры:**
  * **image** (`bytes`) – Изображение с капчей.
  * **timeout** (`int`) – Максимальное время в течении которого требуется вернуть решение капчи.
* **Тип результата:**
  `dict` | `None`
* **Результат:**
  Словарь с результатами решения.

#### *abstract async* result_report(task_id, correct)

Отправляет отчет о решении капчи.

* **Параметры:**
  * **task_id** (`str`) – ID задачи по решению капчи.
  * **correct** (`bool`) – Результат решения.
* **Тип результата:**
  `bool`
* **Результат:**
  Результат отправки решения.

## src.interfaces.abstract_page_parser module

Модуль содержит интерфейс для работы с парсером ответов от сайта.

### *class* src.interfaces.abstract_page_parser.AbstractPageParser

Базовые классы: `ABC`

Интерфейс для работы с парсером ответов от сайта.

#### *abstract* parse(response)

Запускает парсинг ответа.

* **Параметры:**
  **response** (`Response`) – Экземпляр класса requests.Response.
* **Response:**
  Словарь с результатами парсинга.
* **Тип результата:**
  `dict`

## src.interfaces.abstract_proxy module

Модуль содержит интерфейс для работы с сервисом прокси.

### *class* src.interfaces.abstract_proxy.AbstractProxy

Базовые классы: `ABC`

Интерфейс для работы с сервисом прокси.
Используемый сервис прокси должен возвращать словарь с ключом „http“ или „https“,
как показано в примере ниже.

### Example:

`get_proxy() -> '{'http': 'http://...', 'https': 'http://...', ...}'`

#### *abstract async* get_proxy()

Возвращает прокси.

* **Тип результата:**
  `dict` | `None`
* **Результат:**
  Словарь с ключами „http“ или „https“.

## Module contents

### *class* src.interfaces.AbstractBase64Converter

Базовые классы: `ABC`

Интерфейс для работы с конвертером изображений base64 в bytes.

#### *abstract static* covert_to_bytes(base64_string)

Конвертирует изображение base64 в bytes

* **Параметры:**
  **base64_string** (`str`) – Изображение в формате base64.
* **Response:**
  Изображение в формате bytes.
* **Тип результата:**
  `bytes`

### *class* src.interfaces.AbstractCaptchaService

Базовые классы: `ABC`

Интерфейс для работы с сервисом решения капчи.

#### *abstract async* post_captcha(image, timeout=0)

Отправляет изображение для решения капчи.

* **Параметры:**
  * **image** (`bytes`) – Изображение с капчей.
  * **timeout** (`int`) – Максимальное время в течении которого требуется вернуть решение капчи.
* **Тип результата:**
  `dict` | `None`
* **Результат:**
  Словарь с результатами решения.

#### *abstract async* result_report(task_id, correct)

Отправляет отчет о решении капчи.

* **Параметры:**
  * **task_id** (`str`) – ID задачи по решению капчи.
  * **correct** (`bool`) – Результат решения.
* **Тип результата:**
  `bool`
* **Результат:**
  Результат отправки решения.

### *class* src.interfaces.AbstractPageParser

Базовые классы: `ABC`

Интерфейс для работы с парсером ответов от сайта.

#### *abstract* parse(response)

Запускает парсинг ответа.

* **Параметры:**
  **response** (`Response`) – Экземпляр класса requests.Response.
* **Response:**
  Словарь с результатами парсинга.
* **Тип результата:**
  `dict`

### *class* src.interfaces.AbstractProxy

Базовые классы: `ABC`

Интерфейс для работы с сервисом прокси.
Используемый сервис прокси должен возвращать словарь с ключом „http“ или „https“,
как показано в примере ниже.

### Example:

`get_proxy() -> '{'http': 'http://...', 'https': 'http://...', ...}'`

#### *abstract async* get_proxy()

Возвращает прокси.

* **Тип результата:**
  `dict` | `None`
* **Результат:**
  Словарь с ключами „http“ или „https“.
