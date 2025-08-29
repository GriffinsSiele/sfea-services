# src.interfaces package

## Submodules

## src.interfaces.abstract_proxy module

### *class* src.interfaces.abstract_proxy.AbstractProxy

Базовые классы: `ABC`

Интерфейс для сервиса прокси

#### *abstract async* get_proxy()

* **Тип результата:**
  `dict` | `None`

## src.interfaces.abstract_result_parser module

### *class* src.interfaces.abstract_result_parser.AbstractResultParser

Базовые классы: `ABC`

Интерфейс для парсера ответа сайта

#### *abstract* parse(response)

Обрабатывает ответ сайта, возвращает словарь с информацией по использованной сессии.

* **Параметры:**
  **response** (`Response`) – Ответ сайта в формате requests.Response.
* **Тип результата:**
  `dict`
* **Результат:**
  Словарь {«status»: «blocked»} или {«status»: «success»},
  в случае ошибок возбуждает исключение или SourceError.

## src.interfaces.abstract_samsung_source module

Модуль содержит интерфейс для получения данных с сайта Samsung.

### *class* src.interfaces.abstract_samsung_source.AbstractSamsungSource(\*args, \*\*kwargs)

Базовые классы: `ABC`

Интерфейс для получения данных с сайта Samsung

#### *abstract* request(\*args, \*\*kwargs)

* **Тип результата:**
  `Response`

## src.interfaces.abstract_seleniumwire_proxy module

### *class* src.interfaces.abstract_seleniumwire_proxy.AbstractSeleniumWireProxy

Базовые классы: `ABC`

Интерфейс адаптера сервиса прокси, для использования в библиотеке seleniumwire

#### *abstract async* get_proxy()

* **Тип результата:**
  `dict` | `None`

## src.interfaces.session_maker module

### *class* src.interfaces.session_maker.AbstractSessionMaker

Базовые классы: `ABC`

Интерфейс для класса, который генерирует сессии

#### *abstract async* prepare()

Выполняет подготовку браузера для получения сессии.

* **Тип результата:**
  [`AbstractSessionMaker`](#src.interfaces.session_maker.AbstractSessionMaker)
* **Результат:**
  SessionMaker

#### *abstract async* make(search_data)

Генерирует сессию

* **Параметры:**
  **search_data** (`str` | `dict`) – Данные для поиска (заведомо не существующий аккаунт)
* **Тип результата:**
  `dict`
* **Результат:**
  Сессия

## Module contents

### *class* src.interfaces.AbstractProxy

Базовые классы: `ABC`

Интерфейс для сервиса прокси

#### *abstract async* get_proxy()

* **Тип результата:**
  `dict` | `None`

### *class* src.interfaces.AbstractSeleniumWireProxy

Базовые классы: `ABC`

Интерфейс адаптера сервиса прокси, для использования в библиотеке seleniumwire

#### *abstract async* get_proxy()

* **Тип результата:**
  `dict` | `None`

### *class* src.interfaces.AbstractSamsungSource(\*args, \*\*kwargs)

Базовые классы: `ABC`

Интерфейс для получения данных с сайта Samsung

#### *abstract* request(\*args, \*\*kwargs)

* **Тип результата:**
  `Response`
