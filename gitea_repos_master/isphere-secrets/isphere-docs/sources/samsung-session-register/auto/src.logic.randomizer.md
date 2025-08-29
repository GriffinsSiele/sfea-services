# src.logic.randomizer package

## Submodules

## src.logic.randomizer.randomizer module

### src.logic.randomizer.randomizer.random_string(length)

Возвращает рандомнцю строку длинной length

* **Параметры:**
  **length** (`int`) – Длинна строки
* **Тип результата:**
  `str`
* **Результат:**
  Рандомная строка

### src.logic.randomizer.randomizer.random_fake_email(\*args)

Возвращает не существующую почту, которая содержит ошибки в написании
и с большой вероятностью никому не принадлежит

* **Тип результата:**
  `str`
* **Результат:**
  Не существующая почта

### src.logic.randomizer.randomizer.get_random_string_month(\*args)

Возвращает случайный месяц в формате строки: «Январь» - «Декабрь»

return: Месяц в формате строки

* **Тип результата:**
  `str`

### src.logic.randomizer.randomizer.get_random_int_month(\*args)

Возвращает случайный месяц в формате числа: 1 - 12

return: Месяц в формате числа

* **Тип результата:**
  `int`

### src.logic.randomizer.randomizer.random_birthdate(month_strategy=<function get_random_string_month>)

Возвращает случайную дату рождения в диапазоне 1970-2004 гг.

* **Тип результата:**
  `dict`
* **Результат:**
  Дата рождения в формате {«day»: 1-28, «month»: Январь-Декабрь, «year»: 1970-2004}.

### src.logic.randomizer.randomizer.random_fake_person(\*args)

Возвращает не существующего человека для получения сессии.

* **Тип результата:**
  `dict`
* **Результат:**
  Словарь {«first_name»: <случайная строка длинной 10 символов>,
  «last_name»: <случайная строка длинной 12 символов>, «birthdate»: <дата рождения>}

### src.logic.randomizer.randomizer.random_fake_person_to_prolong(\*args)

Возвращает не существующего человека для пролонгации сессии person.

* **Тип результата:**
  `dict`
* **Результат:**
  Словарь {«first_name»: <случайная строка длинной 10 символов>,
  «last_name»: <случайная строка длинной 12 символов>, «birthdate»: <дата рождения>}

### src.logic.randomizer.randomizer.random_fake_person_with_mail(\*args)

Возвращает не существующего человека получения сессии в обработчике name.

* **Тип результата:**
  `dict`
* **Результат:**
  Словарь {«account_login»: <не существующая почта>, «first_name»: <случайная строка длинной 10 символов>,
  «last_name»: <случайная строка длинной 12 символов>, «birthdate»: <дата рождения>}

### src.logic.randomizer.randomizer.random_fake_person_to_prolong_with_email(\*args)

Возвращает не существующего человека для пролонгации сессии name.

* **Тип результата:**
  `dict`
* **Результат:**
  Словарь {«account_login»: <не существующая почта>, «first_name»: <случайная строка длинной 10 символов>,
  «last_name»: <случайная строка длинной 12 символов>, «birthdate»: <дата рождения>}

## Module contents

### src.logic.randomizer.random_string(length)

Возвращает рандомнцю строку длинной length

* **Параметры:**
  **length** (`int`) – Длинна строки
* **Тип результата:**
  `str`
* **Результат:**
  Рандомная строка

### src.logic.randomizer.random_fake_email(\*args)

Возвращает не существующую почту, которая содержит ошибки в написании
и с большой вероятностью никому не принадлежит

* **Тип результата:**
  `str`
* **Результат:**
  Не существующая почта

### src.logic.randomizer.random_fake_person(\*args)

Возвращает не существующего человека для получения сессии.

* **Тип результата:**
  `dict`
* **Результат:**
  Словарь {«first_name»: <случайная строка длинной 10 символов>,
  «last_name»: <случайная строка длинной 12 символов>, «birthdate»: <дата рождения>}

### src.logic.randomizer.random_birthdate(month_strategy=<function get_random_string_month>)

Возвращает случайную дату рождения в диапазоне 1970-2004 гг.

* **Тип результата:**
  `dict`
* **Результат:**
  Дата рождения в формате {«day»: 1-28, «month»: Январь-Декабрь, «year»: 1970-2004}.

### src.logic.randomizer.random_fake_person_to_prolong(\*args)

Возвращает не существующего человека для пролонгации сессии person.

* **Тип результата:**
  `dict`
* **Результат:**
  Словарь {«first_name»: <случайная строка длинной 10 символов>,
  «last_name»: <случайная строка длинной 12 символов>, «birthdate»: <дата рождения>}

### src.logic.randomizer.random_fake_person_to_prolong_with_email(\*args)

Возвращает не существующего человека для пролонгации сессии name.

* **Тип результата:**
  `dict`
* **Результат:**
  Словарь {«account_login»: <не существующая почта>, «first_name»: <случайная строка длинной 10 символов>,
  «last_name»: <случайная строка длинной 12 символов>, «birthdate»: <дата рождения>}
