# src.logger package

## Submodules

## src.logger.context_logger module

## src.logger.logger_adapter module

### *class* src.logger.logger_adapter.LoggingSearchKeyAdapter(logger, wrapper='||')

Базовые классы: `LoggerAdapter`

#### process(msg, kwargs)

Process the logging message and keyword arguments passed in to
a logging call to insert contextual information. You can either
manipulate the message itself, the keyword args or both. Return
the message and kwargs modified (or not) to suit your needs.

Normally, you’ll only need to override this one method in a
LoggerAdapter subclass for your specific needs.

#### *static* get_context_message()

Возвращает контекст из текущего запроса

* **Тип результата:**
  `str`

## Module contents
