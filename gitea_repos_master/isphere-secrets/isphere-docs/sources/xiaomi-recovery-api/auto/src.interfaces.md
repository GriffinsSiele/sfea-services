# src.interfaces package

## Submodules

## src.interfaces.abstract_browser module

## src.interfaces.abstract_captcha_service module

### *class* src.interfaces.abstract_captcha_service.AbstractCaptchaService

Базовые классы: `ABC`

#### *abstract async* post_captcha(image, timeout=0)

* **Тип результата:**
  `dict` | `None`

#### *abstract async* result_report(task_id, correct)

* **Тип результата:**
  `bool`

## src.interfaces.abstract_extension module

### *class* src.interfaces.abstract_extension.AbstractProxyExtension

Базовые классы: `ABC`

#### *abstract* prepare(host, port, user, password)

* **Тип результата:**
  `None`

#### *abstract property* directory *: str*

## src.interfaces.abstract_xiaomi_browser module

## src.interfaces.utils module

### *class* src.interfaces.utils.SingletonABCMeta(name, bases, namespace, /, \*\*kwargs)

Базовые классы: `ABCMeta`

## Module contents
