# src.interfaces package

## Submodules

## src.interfaces.abstract_proxy module

Модуль содержит интерфейс для работы с сервисом прокси.

### *class* src.interfaces.abstract_proxy.AbstractProxy

Базовые классы: `ABC`

Интерфейс для работы с сервисом прокси.

#### *abstract async* get_proxy(proxy_id=None)

Возвращает прокси по переданному ID.

* **Параметры:**
  **proxy_id** (`str` | `None`) – ID прокси.
* **Тип результата:**
  `dict` | `None`
* **Результат:**
  Прокси.

## src.interfaces.abstract_result_parser module

Модуль содержит интерфейс парсера ответа сайта Samsung.

### *class* src.interfaces.abstract_result_parser.AbstractResultParser

Базовые классы: `ABC`

#### *abstract* parse(response)

Обработка ответа сайта Samsung.

* **Параметры:**
  **response** (`Response`) – Ответ сайта в формате requests.Response.
* **Тип результата:**
  `dict`
* **Результат:**
  Словарь {«result»: «Найден», «result_code»: «FOUND»,
  «user_info: [<дополнительная информация о пользователе>]»}
  или исключение NoDataEvent, в случае ошибок возбуждает исключение SessionBlockedInfo или SourceError.

## src.interfaces.abstract_samsung module

Модуль содержит интерфейс поиска аккаунта на сайте Samsung.

### *class* src.interfaces.abstract_samsung.AbstractSamsung(\*args, \*\*kwargs)

Базовые классы: `ABC`

Интерфейс для поиска аккаунта на сайте Samsung.

#### *abstract async* search(data)

Запускает поиск аккаунта на сайте Samsung.

* **Параметры:**
  **data** (`str` | `dict`) – Проверяемый аккаунт.
* **Тип результата:**
  `dict`
* **Результат:**
  Словарь с результатами проверки (найден или нет).

## src.interfaces.abstract_samsung_source module

Модуль содержит интерфейс для получения данных с сайта Samsung.

### *class* src.interfaces.abstract_samsung_source.AbstractSamsungSource(\*args, \*\*kwargs)

Базовые классы: `ABC`

Интерфейс для получения данных с сайта Samsung

#### *abstract* request(\*args, \*\*kwargs)

* **Тип результата:**
  `Response`

## Module contents

### *class* src.interfaces.AbstractProxy

Базовые классы: `ABC`

Интерфейс для работы с сервисом прокси.

#### *abstract async* get_proxy(proxy_id=None)

Возвращает прокси по переданному ID.

* **Параметры:**
  **proxy_id** (`str` | `None`) – ID прокси.
* **Тип результата:**
  `dict` | `None`
* **Результат:**
  Прокси.

### *class* src.interfaces.AbstractSamsungSource(\*args, \*\*kwargs)

Базовые классы: `ABC`

Интерфейс для получения данных с сайта Samsung

#### *abstract* request(\*args, \*\*kwargs)

* **Тип результата:**
  `Response`

### *class* src.interfaces.AbstractResultParser

Базовые классы: `ABC`

#### *abstract* parse(response)

Обработка ответа сайта Samsung.

* **Параметры:**
  **response** (`Response`) – Ответ сайта в формате requests.Response.
* **Тип результата:**
  `dict`
* **Результат:**
  Словарь {«result»: «Найден», «result_code»: «FOUND»,
  «user_info: [<дополнительная информация о пользователе>]»}
  или исключение NoDataEvent, в случае ошибок возбуждает исключение SessionBlockedInfo или SourceError.

### *class* src.interfaces.AbstractSamsung(\*args, \*\*kwargs)

Базовые классы: `ABC`

Интерфейс для поиска аккаунта на сайте Samsung.

#### *abstract async* search(data)

Запускает поиск аккаунта на сайте Samsung.

* **Параметры:**
  **data** (`str` | `dict`) – Проверяемый аккаунт.
* **Тип результата:**
  `dict`
* **Результат:**
  Словарь с результатами проверки (найден или нет).
