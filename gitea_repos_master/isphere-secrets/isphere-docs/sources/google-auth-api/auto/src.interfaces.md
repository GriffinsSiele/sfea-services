# src.interfaces package

## Submodules

## src.interfaces.abstract_browser module

### *class* src.interfaces.abstract_browser.AbstractBrowser(\*args, \*\*kwargs)

Базовые классы: `object`

#### proxy_extension *: [`AbstractProxyExtension`](#src.interfaces.abstract_extension.AbstractProxyExtension)*

#### is_started *: `bool`*

#### browser_prepared *: `bool`*

#### options *: `ChromeOptions`*

#### headless *: `bool`*

#### window_size *: `list`[`int`]*

#### implicitly_wait_delay *: `float`*

#### explicit_wait_delay *: `float`*

#### *abstract* start_browser()

* **Тип результата:**
  `None`

#### *abstract* close_browser()

Завершает работу web драйвера.

* **Тип результата:**
  `None`

#### *abstract* get(url)

Переходит по переданному url
и ждет загрузки страницы.

* **Параметры:**
  **url** (`str`) – url-адрес страницы.
* **Тип результата:**
  `None`

#### *abstract property* page_source *: str*

Возвращает текущую html-страницу.

* **Результат:**
  html код страницы в формате str.

#### *abstract* get_element(by, selector)

* **Тип результата:**
  `WebElement`

#### *abstract* get_element_and_clear(by, selector)

* **Тип результата:**
  `WebElement`

#### *abstract* get_loaded_element(by, selector)

* **Тип результата:**
  `WebElement`

#### *abstract* get_element_and_click(by, selector)

Находит элемент на странице и переходит по нему (click).
Имеет задержку для ожидания обновления страницы.

* **Параметры:**
  * **by** (`By`) – локатор, определяет стратегию поиска.
  * **selector** (`str`) – ключ поиска.
* **Тип результата:**
  `None`

#### get_element_and_set_data(by, selector, data)

Находит элемент на странице и вводит в него данные.

* **Параметры:**
  * **by** (`By`) – локатор, определяет стратегию поиска.
  * **selector** (`str`) – ключ поиска.
  * **data** (`str`) – данные для ввода.
* **Тип результата:**
  `None`

#### *abstract* get_element_set_data_and_enter(by, selector, data)

Находит элемент на странице, вводит данные и отправляет сигнал нажатия Enter.
Имеет задержку для ожидания обновления страницы.

* **Параметры:**
  * **by** (`By`) – локатор, определяет стратегию поиска.
  * **selector** (`str`) – ключ поиска.
  * **data** (`str`) – данные для ввода.
* **Тип результата:**
  `None`

#### *abstract* get_current_url()

Возвращает текущий URL

* **Тип результата:**
  `str`

## src.interfaces.abstract_captcha_service module

### *class* src.interfaces.abstract_captcha_service.AbstractCaptchaService

Базовые классы: `ABC`

#### *abstract* solve_captcha(image, timeout)

* **Тип результата:**
  `dict` | `None`

#### *abstract* solve_report(task_id, correct)

* **Тип результата:**
  `dict` | `str` | `None`

## src.interfaces.abstract_extension module

### *class* src.interfaces.abstract_extension.AbstractProxyExtension

Базовые классы: `ABC`

#### *abstract* prepare(host, port, user, password)

* **Тип результата:**
  `None`

#### *abstract property* directory *: str*

## src.interfaces.abstract_google_captcha_service module

### *class* src.interfaces.abstract_google_captcha_service.AbstractGoogleCaptchaService

Базовые классы: [`AbstractCaptchaService`](#src.interfaces.abstract_captcha_service.AbstractCaptchaService)

#### image_tag *: `tuple`[`By`, `str`]*

#### input_tag *: `tuple`[`By`, `str`]*

## src.interfaces.abstract_response_adapter module

### *class* src.interfaces.abstract_response_adapter.ResponseAdapter

Базовые классы: `ABC`

#### *abstract static* cast(response)

* **Тип результата:**
  `list`[`dict`[`str`, `str` | `list`]]

## src.interfaces.abstract_screen_dispatcher module

### *class* src.interfaces.abstract_screen_dispatcher.AbstractScreenDispatcher

Базовые классы: `ABC`

Собирает и возвращает данные со страницы.

#### *abstract* get_data(web_browser)

* **Тип результата:**
  `dict`[`str`, `list` | `bool`] | `None`

## src.interfaces.abstract_screens_repository module

## src.interfaces.abstract_telegram_api module

### *class* src.interfaces.abstract_telegram_api.AbstractTelegramAPI

Базовые классы: `ABC`

#### *abstract* send_file(file, send_as=None)

Отправляет файл в телеграм.

* **Параметры:**
  * **file** (`str`) – Путь к файлу.
  * **send_as** (`str` | `None`) – Изменить имя отправляемого файла.
* **Тип результата:**
  `str`
* **Результат:**
  Ответ клиента.

#### *abstract* send_message(message)

Отправляет сообщение в телеграм.

* **Параметры:**
  **message** (`str`) – Сообщение.
* **Тип результата:**
  `str`
* **Результат:**
  Ответ клиента.

## src.interfaces.abstract_telegram_bot module

### *class* src.interfaces.abstract_telegram_bot.AbstractTelegramBot

Базовые классы: `ABC`

#### message_prefix *= ''*

#### *abstract* send_files_from_path(path, message)

* **Тип результата:**
  `None`

## src.interfaces.event module

### *class* src.interfaces.event.Event(iterable=(), /)

Базовые классы: `list`

## src.interfaces.utils module

### *class* src.interfaces.utils.SingletonABCMeta(name, bases, namespace, /, \*\*kwargs)

Базовые классы: `ABCMeta`

## Module contents
