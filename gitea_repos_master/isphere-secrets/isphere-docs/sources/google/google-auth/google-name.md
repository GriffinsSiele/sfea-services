---
title: Поиск по имени
description: 
published: true
date: 2024-03-07T08:39:08.580Z
tags: 
editor: markdown
dateCreated: 2024-02-20T12:05:22.207Z
---

# google-name-api
Осуществляет проверку наличие аккаунта по полученным в качестве входных данных email или номера телефона, Фамилии и Имени. 
### Особенности работы сайта google
После загрузки страницы "https://accounts.google.com/signin/v2/usernamerecovery" получаем форму с приглашением ввести почту или номер телефона.
На следующем экране - форма с Именем и Фамилией, имя обязательное поле, фамилия - не обязательное.
На следующем экране получаем предложение отправить код подтверждения на почту (аккаунт найден) или сообщение, что аккаунт не найден.
Также могут появляться формы с ошибками, например Небезопасный браузер.
### Описание приложения
Приложение построено на кодовой базе [google-auth-api](http://wikijs.wikijs.svc.cluster.local/ru/sources/google/google-auth/screens) и в своей работе использует логику его работы. Исключение составляет описание экранов и граф перехода по экранам, которые для каждого приложения свои (`connections_name.py` и `connections_auth.py` соответственно) и некоторые модули с незначительным отличием в коде. Модули python которые относятся к приложению `google-name-api` имеют окончание `_name.py`, к приложению `google-auth-api` - окончание `_auth.py`, остальные модули и пакеты являются общими для указанных приложений.
#### Настройка приложения
Смотри настройку приложения [google-auth-api](http://wikijs.wikijs.svc.cluster.local/ru/sources/google/google-auth/screens)
#### Добавление новых экранов
В случае обнаружения неизвестного экрана, приложение сохранит скриншот и html-код экрана (два файла png и html) в указанную в настройках папку DEFAULT_FOLDER, выведет ошибку в логах и отправит сохраненные файлы в Telegram (если он настроен TG_BOT_TOKEN и TG_CHAT_ID).
Модуль `connections_name` сдержит граф `ScreensRepository` котором описаны все известные экраны. Экраны являются экземплярами класса `Screen`, для которого реализован конструктор:
- `create_screen_with_payload` - создать экран с полезной нагрузкой;
- `create_screen_without_payload` - создать экран без полезной нагрузки;
- `create_error_screen` - создать экран с ошибкой;
- `create_end_screen` - создать конечный экран.
Определяем тип экрана и добавляем по аналогии с существующими, при необходимости добавляем обработчик нового экрана в модуль `screen_dispatchers`.

## Примеры экранов

|MainPage|
|:-:|
|<img src="/sources/google/google-auth/resources/mainpage.png" width="250" height="300" display="inline">|


| NamePage | InsecureBrowser | SourceIncorrectData | NotificationNotSent |
|:-:|:-:|:-:|:-:|
|<img src="/sources/google/google-auth/resources/namepage.png" width="250" height="300">|<img src="/sources/google/google-auth/resources/insecurebrowser.png" width="250" height="300">|<img src="/sources/google/google-auth/resources/sourceincorrectdata_2.png" width="250" height="300">|<img src="/sources/google/google-auth/resources/notificationnotsent.png" width="250" height="300">|


| FoundEmailAlert_1 | FoundPhoneAlert_1 | NotFoundError_1 |
|:-:|:-:|:-:|
|<img src="/sources/google/google-auth/resources/foundemailalert_1.png" width="250" height="300">|<img src="/sources/google/google-auth/resources/foundphonealert_1.png" width="250" height="300">|<img src="/sources/google/google-auth/resources/notfounderror_1.png" width="250" height="300">|
