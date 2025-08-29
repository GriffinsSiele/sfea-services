# src.logger package

## Submodules

## src.logger.context_logger module

### *class* src.logger.context_logger.ContextLogger

Базовые классы: `object`

Логер с контекстом

#### *static* get_logger(name)

Возвращает логер по переданному имени.

* **Параметры:**
  **name** (`str`) – Имя логера, который требуется вернуть.
* **Тип результата:**
  [`LoggingSearchKeyAdapter`](#src.logger.logger_adapter.LoggingSearchKeyAdapter)
* **Результат:**
  Логер.

#### *static* get_root_logger()

Возвращает корневой логер (с именем root)

* **Тип результата:**
  [`LoggingSearchKeyAdapter`](#src.logger.logger_adapter.LoggingSearchKeyAdapter)

## src.logger.logger_adapter module

### *class* src.logger.logger_adapter.LoggingSearchKeyAdapter(logger, wrapper='||')

Базовые классы: `LoggerAdapter`

Адаптер, позволяет задать контекст логера - текст, который будет отображаться
в начале каждого сообщения логера. Такой подход применяется в многопоточных
приложениях, с целью определить к какому запросу относится сообщение лога.

#### process(msg, kwargs)

Обрабатывает сообщение логера, добавляет контекст в сообщение, если он задан.

* **Параметры:**
  * **msg** – Сообщение логера.
  * **kwargs** – Аргументы сообщения.
* **Результат:**
  Обработанное сообщение, аргументы сообщения.

#### *static* get_context_message()

Возвращает контекст из текущего запроса.

* **Тип результата:**
  `str`
* **Результат:**
  Контекст.

## Module contents
