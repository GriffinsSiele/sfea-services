# src.utils package

## Submodules

## src.utils.utils module

### src.utils.utils.now()

Возвращает текущую дату и время в формате unix epoch.

* **Тип результата:**
  `int`
* **Результат:**
  Unix epoch время и дата.

### src.utils.utils.informer(step_number, step_message)

Декоратор, выводит логи до начала работы функции и после ее завершения.

* **Параметры:**
  * **step_number** (`int`) – Шаг в формате int
  * **step_message** (`str`) – Сообщение на шаге.
* **Тип результата:**
  `Callable`
* **Результат:**
  Декорированная функция.

### src.utils.utils.strip_str(input_str)

Удаляет из строки символы »
«, « «.

* **Тип результата:**
  `str`

## Module contents

### src.utils.now()

Возвращает текущую дату и время в формате unix epoch.

* **Тип результата:**
  `int`
* **Результат:**
  Unix epoch время и дата.

### src.utils.informer(step_number, step_message)

Декоратор, выводит логи до начала работы функции и после ее завершения.

* **Параметры:**
  * **step_number** (`int`) – Шаг в формате int
  * **step_message** (`str`) – Сообщение на шаге.
* **Тип результата:**
  `Callable`
* **Результат:**
  Декорированная функция.
