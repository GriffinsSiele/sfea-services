---
title: Список реализованных источников
description: 
published: true
date: 2024-09-17T12:10:01.656Z
tags: 
editor: markdown
dateCreated: 2024-09-17T12:10:01.656Z
---

# Список реализованных источников

На данной странице описаны источники, которые были одобрены и реализованы. Чаще всего сюда будут переносится источники со страницы утвержденных

## Xiaomi [completed]

- Название: Xiaomi
- Статус: Одобрен
- Ссылка: [https://account.xiaomi.com/fe/service/forgetPassword?_locale=ru_RU
](https://account.xiaomi.com/fe/service/forgetPassword?_locale=ru_RU)
- Дата исследования: 01.06.2023
- Тип поиска: login
- Входные данные: номер телефона, почта
- Выходные поля: наличие учетки, id, часть почты
- Ограничения: не исследованы

## Apple Recovery [completed]

- Название: Apple
- Статус: Одобрен
- Ссылка: [https://iforgot.apple.com/password/verify/appleid](https://iforgot.apple.com/password/verify/appleid)
- Дата исследования: 01.06.2023
- Тип поиска: recover
- Входные данные: почта
- Выходные поля: наличие учетки, часть телефона, заблокированность
- Ограничения: капча цифро-буквенная

Примеры:
- daniil.sviridov8897@icloud.com - `* (***) ***-**-88	`
- rusikali-ogl@mail.ru -`Только пароль`
- www.ildar_08@mail.r- заблокирован


## Samsung [completed]

- Название: Samsung
- Статус: Одобрен
- Ссылка: [https://account.samsung.com/membership/auth/sign-in](https://account.samsung.com/membership/auth/sign-in)
- Дата исследования: 01.06.2023
- Тип поиска: login
- Входные данные: почта
- Выходные поля: наличие учетки
- Ограничения: капча цифро-буквенная


## Samsung Recovery [completed]

- Название: Samsung
- Статус: Одобрен
- Ссылка: [https://account.samsung.com/accounts/v1/MBR/findIdWithUserInfo](https://account.samsung.com/accounts/v1/MBR/findIdWithUserInfo)
- Дата исследования: 01.06.2023
- Тип поиска: recover
- Входные данные: имя + фамилия + ДР
- Выходные поля: наличие учетки, часть почты
- Ограничения: не исследовано

Примеры:
- Денисов Денис 16.07.1981

## Samsung Recover 2 [completed]

- Название: Samsung
- Статус: Одобрен
- Ссылка: [https://account.samsung.com/accounts/v1/MBR/findId](https://account.samsung.com/accounts/v1/MBR/findId)
- Дата исследования: 01.06.2023
- Тип поиска: recover
- Входные данные: имя + фамилия + ДР + телефон (почта)
- Выходные поля: наличие учетки, сверка на совпадение
- Ограничения: не исследовано


## Huawei [completed]

- Название: Huawei
- Статус: Одобрен
- Ссылка: [https://id5.cloud.huawei.com/AMW/portal/resetPwd/forgetbyid.html#/forgetPwd/forgetbyid](
https://id5.cloud.huawei.com/AMW/portal/resetPwd/forgetbyid.html#/forgetPwd/forgetbyid)
- Дата исследования: 01.06.2023
- Тип поиска: login
- Входные данные: телефон или почта
- Выходные поля: наличие учетки
- Ограничения: не исследовано

Примеры:
- kovinevmv@gmail.com - существует


## Eyecon [completed]

- Название: Eyecon
- Статус: Одобрен
- Ссылка: [https://4pda.to/forum/index.php?showtopic=841069](https://4pda.to/forum/index.php?showtopic=841069)
- Дата исследования: 01.01.2023
- Тип поиска: import, search
- Входные данные: номер телефона
- Выходные поля: имя
- Ограничения: требуется учетка в eyecon, приложение заблокировано в РФ, требуется чистое устройство


## Красное и Белое [completed]

- Название: Красное и Белое
- Статус: Одобрен
- Ссылка: [https://krasnoeibeloe.ru/personal/](https://krasnoeibeloe.ru/personal/)
- Дата исследования: 01.01.2023
- Тип поиска: login
- Входные данные: номер телефона
- Выходные поля: наличие учетки
- Ограничения: в настольной версии google captcha, необходимо изучить мобильную

Сценарий:
- Перейти на форму авторизации
- Ввести входной телефон и любой пароль
- Если пользователь есть - "Неверный пароль", иначе - "Пользователь с данным номером телефона не зарегистрирован."

 
![kb_1.png](/sources/researches/resources/kb_1.png)



## Фотострана [completed]

- Название: Фотострана
- Статус: Одобрен
- Ссылка: [https://fotostrana.ru/](https://fotostrana.ru/)
- Дата исследования: 01.01.2023
- Тип поиска: login, link
- Входные данные: телефон или почта
- Выходные поля: ссылка на профиль, фото, возраст, имя
- Ограничения: числовая капча

Сценарий:
- Перейти на форму авторизации
- Ввести почту или телефон, рандомный пароль.
- Если пользователь есть, то в ответном json запроса будет user_id с ошибкой неверный пароль, иначе пользователь не найден
- Переходим по ссылке https://fotostrana.ru/user/%user_id%/ и получаем данные с профиля

Пример пользователя:
- +79039687447

Пример капчи:
![fotostrana_1.png](/sources/researches/resources/fotostrana_1.png)

## Литрес [completed]

- Название: Литрес
- Статус: Одобрен
- Ссылка: [https://www.litres.ru/](https://www.litres.ru/)
- Дата исследования: 01.01.2023
- Тип поиска: login
- Входные данные: номер телефона или почта
- Выходные поля: наличие учетки
- Ограничения: не выявлено

Сценарий:
- Перейти на сайт
- Нажать кнопку "Войти" -> "Электронная почта" или "Номер телефона" -> Ввести данные
- Смотри изображения ниже

Примеры:
- Смотри изображения ниже
 
![litres_1.png](/sources/researches/resources/litres_1.png)

![litres_2.png](/sources/researches/resources/litres_2.png)

## РосНефть [completed]

- Название: РосНефть
- Статус: Одобрен
- Ссылка: [https://4pda.to/forum/index.php?showtopic=691716](https://4pda.to/forum/index.php?showtopic=691716)
- Дата исследования: 01.10.2023
- Тип поиска: search
- Входные данные: номер телефона
- Выходные поля: наличие учетки
- Ограничения: для просмотра аватара нужна учетка

Сценарий:
- Указать в query phone - телефон человека

Примеры:
```bash
curl -X POST -H 'Accept: */*' -H 'Accept-Encoding: gzip, deflate' -H 'Connection: keep-alive' -H 'Content-Length: 17' -H 'content-type: application/x-www-form-urlencoded' -H 'user-agent: okhttp/5.0.0-alpha.9' -H 'x-build-number: 679' -H 'x-device-id: SAMPLE' -H 'x-device-type: android' -H 'x-region-code: 22' -d phone=79208533711 https://rn-brand.sitesoft.ru/api/v19/recover/check
```


## Duolingo [completed]

- Название: Duolingo
- Статус: Одобрен
- Ссылка: [https://www.duolingo.com/2017-06-30/users?email=kovinevmv@gmail.com](https://www.duolingo.com/2017-06-30/users?email=kovinevmv@gmail.com)
- Дата исследования: 01.01.2023
- Тип поиска: link, import
- Входные данные: номер телефона или почта
- Выходные поля: имя, аватар, страна, родной язык, изучаемые языки
- Ограничения: для просмотра аватара нужна учетка

Сценарий:
- Указать в query email - почту человека

Примеры:
- kovinevmv@gmail.com, ravil_tiger@mail.ru - есть в системе


Сценарий:
- Рассмотреть импорт по контактам в мобильном приложении. 


## Почта РФ [completed]

- Название: Почта РФ
- Статус: Одобрен
- Ссылка: [https://play.google.com/store/apps/details?id=com.octopod.russianpost.client.android&hl=ru&gl=US](hhttps://play.google.com/store/apps/details?id=com.octopod.russianpost.client.android&hl=ru&gl=US)
- Дата исследования: 21.04.2023
- Тип поиска: search
- Входные данные: номер телефона
- Выходные поля: имя, отчество, первая буква фамилии, город, индекс
- Ограничения: для просмотра фио нужна учетка

Сценарий:
- в мобильном приложении авторизоваться
- создать отправление, указать адреса
- выбрать телефон из контактов

Примеры:
- +79117731525 - есть

Примечание:
- Аналогичное API есть на сайте, но с другими URL 

<img src="/sources/researches/resources/pochta_1.jpg" width="400">
<img src="/sources/researches/resources/pochta_2.jpg" width="400">


