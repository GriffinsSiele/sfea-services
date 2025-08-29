---
title: Список отклоненных источников
description: 
published: true
date: 2024-09-17T12:12:30.278Z
tags: 
editor: markdown
dateCreated: 2024-02-20T12:06:07.303Z
---

# Список отклоненных источников


## Samsung Health

- Название: Samsung Health
- Статус: отклонен
- Ссылка: [https://play.google.com/store/apps/details?id=com.sec.android.app.shealth&hl=en_US&pli=1](https://play.google.com/store/apps/details?id=com.sec.android.app.shealth&hl=en_US&pli=1)
- Дата исследования: 01.01.2022
- Тип поиска: import
- Входные данные: номер телефона
- Выходные поля: никнейм, аватар
- Ограничения: учетка Samsung

Сценарий:
- Добавить телефон в записную книжку контактов
- Перейти на вкладку "Контакты" в приложении

Причина отклонения:
- Низкий хитрейт: 5/150 контактов находит. 


## Aliexpress

- Название: Aliexpress
- Статус: отклонен
- Ссылка: [https://login.aliexpress.ru/](https://login.aliexpress.ru/1)
- Дата исследования: 01.01.2023
- Тип поиска: login
- Входные данные: почта
- Выходные поля: забанена ли учетка
- Ограничения: капча Aliexpress?

Сценарий:
- Перейти на страницу, заполнить форму почтой
- Перейти на следующую форму, ввести произвольный пароль
- Либо "Некорректные учетные данные", либо "Аккаунт заблокирован ..."

Причина отклонения:
- Низкий хитрейт по почтек

![aliexpress_1.png](/sources/researches/resources/aliexpress_1.png)

## Sololearn

- Название: Sololearn
- Статус: отклонен
- Ссылка: [https://play.google.com/store/apps/details?id=com.sololearn](https://play.google.com/store/apps/details?id=com.sololearn)
- Дата исследования: 01.01.2023
- Тип поиска: import
- Входные данные: номер телефона
- Выходные поля: имя, аватар
- Ограничения: учетка Sololearn

Сценарий:
- Добавить телефон в записную книжку контактов
- Перейти на вкладку "Контакты" в приложении

Причина отклонения:
- Низкий хитрейт: 2/150 контактов находит. 

## Google Meet

- Название: Google Meet
- Статус: отклонен
- Ссылка: [https://meet.google.com/?pli=1](https://meet.google.com/?pli=1)
- Дата исследования: 01.01.2023
- Тип поиска: import
- Входные данные: номер телефона
- Выходные поля: наличие учетки google и установленного приложения meet
- Ограничения: ???

Сценарий:
- Добавить телефон в записную книжку контактов
- Перейти на вкладку "Звонки" в приложении

Причина отклонения:
- Дублирует поиск по Google, причем урезанно
- Средний хитрейт: 19/150 контактов находит. 

## Drom.ru

- Название: Drom.ru
- Статус: отклонен
- Ссылка: [https://www.drom.ru/](https://www.drom.ru/)
- Дата исследования: 01.01.2023
- Тип поиска: login
- Входные данные: номер телефона
- Выходные поля: наличие учетки
- Ограничения: ???

Сценарий:
- При авторизации происходит валидация телефона

Причина отклонения:
- Есть аналогичный поиск boards
- Низкий хитрейт

## Мегафон Подбор тарифа

- Название: Мегафон
- Статус: отклонен
- Ссылка: [https://spb.shop.megafon.ru/sim](https://spb.shop.megafon.ru/sim)
- Дата исследования: 01.01.2023
- Тип поиска: search
- Входные данные: номер телефона
- Выходные поля: рекомендуемый тариф
- Ограничения: ???

Сценарий:
- По произвольному номеру происходит подбор тарифа. Видимо у них какая-то статистика есть. как минимум по звонкам абонентов мегафона на этот номер и наоборот

Причина отклонения:
- что с этими данными делать?

## Pinterest

- Название: Pinterest
- Статус: отклонен
- Ссылка: [https://ru.pinterest.com/](https://ru.pinterest.com/)
- Дата исследования: 01.01.2023
- Тип поиска: import
- Входные данные: номер телефона
- Выходные поля: ссылка на учетку pinterest
- Ограничения: ???

Сценарий:
- Добавить телефон в записную книжку контактов
- Перейти на вкладку "Импорт" в приложении

Причина отклонения:
- Низкий хитрейт: 1/150 контактов находит. 


## ICQ

- Название: ICQ
- Статус: отклонен
- Ссылка: [https://icq.im/](https://icq.im/)
- Дата исследования: 01.01.2023
- Тип поиска: link
- Входные данные: почта
- Выходные поля: имя, аватар
- Ограничения: ???

Сценарий:
- https://icq.im/kovinevmv@gmail.com

Причина отклонения:
- Низкий хитрейт

## Signal Messenger

- Название: Signal Messenger
- Статус: отклонен
- Ссылка: [https://play.google.com/store/apps/details?id=org.thoughtcrime.securesms](https://play.google.com/store/apps/details?id=org.thoughtcrime.securesms)
- Дата исследования: 01.01.2023
- Тип поиска: import
- Входные данные: номер телефона
- Выходные поля: имя
- Ограничения: ???

Сценарий:
- Добавить телефон в записную книжку контактов
- Перейти на вкладку "Импорт" в приложении

Причина отклонения:
- Низкий хитрейт: 7/150 контактов находит. 


## Проверка авто по базе ГИБДД

- Название: Проверка авто по базе ГИБДД
- Статус: отклонен
- Ссылка: [https://play.google.com/store/apps/details?id=ru.bloodsoft.gibddchecker](https://play.google.com/store/apps/details?id=ru.bloodsoft.gibddchecker)
- Дата исследования: 01.01.2023
- Тип поиска: search
- Входные данные: VIN, гос. номер
- Выходные поля: Идёт как агрегатор данных из разных источников, ingos.ru, osago.finuslugi.ru, autoins и многое другое, Проверка на каршеринг, судебные приставы, дтп, фото машины, отзывные компании, Фотографии
- Ограничения: неизвестно

Сценарий:
- Поиск в приложении

Причина отклонения:
- ???

## 2ГИС Друзья

- Название: 2ГИС
- Статус: отклонен
- Ссылка: [https://2gis.ru/](https://2gis.ru/)
- Дата исследования: 01.01.2023
- Тип поиска: import
- Входные данные: номер телефона
- Выходные поля: имя
- Ограничения: 1 запрос поиска - 1 учетка 2gis

Сценарий:
- Добавить телефон в записную книжку контактов
- Перейти на вкладку "Импорт" в приложении

Причина отклонения:
- Низкий хитрейт: 2/150 контактов находит. 
- Жесткие лимиты поиска

![2gis_2.jpg](/sources/researches/resources/2gis_2.jpg)


## Discord

- Название: Discord
- Статус: отклонен
- Ссылка: [https://discord.com/](https://discord.com/)
- Дата исследования: 01.01.2023
- Тип поиска: import
- Входные данные: номер телефона
- Выходные поля: профиль discord
- Ограничения: ???

Сценарий:
- Добавить телефон в записную книжку контактов
- Перейти на вкладку "Импорт" в приложении

Причина отклонения:
- Низкий хитрейт: 2/150 контактов находит. 


## Ozon Tiktok

- Название: Ozon
- Статус: отклонен
- Ссылка: [https://www.ozon.ru/](https://www.ozon.ru/)
- Дата исследования: 01.01.2023
- Тип поиска: import
- Входные данные: номер телефона
- Выходные поля: имя и инициал
- Ограничения: ???

Сценарий:
- Добавить телефон в записную книжку контактов
- Перейти на вкладку "Tiktok" в приложении, выполнить поиск. 
- Перейти на страницу добавления друзей, будут в рекомендациях выводиться новые.

Причина отклонения:
- Низкий хитрейт: 5/150 контактов находит. 



## ДомКлик [deprecated]

- Название: ДомКлик
- Статус: Одобрен
- Ссылка: [https://domclick.ru/](https://domclick.ru/)
- Дата исследования: 20.12.2023
- Тип поиска: search
- Входные данные: номер телефона
- Выходные поля: имя, отчество, первая буква фамилии, наличие учетки в домклике от сбера
- Ограничения: не выявлено

Сценарий:
```python
headers = {
    'Accept': 'application/json',
    'Accept-Language': 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
    'Connection': 'keep-alive',
    'DNT': '1',
    'Origin': 'https://ipoteka.domclick.ru',
    'Referer': 'https://ipoteka.domclick.ru/',
    'Sec-Fetch-Dest': 'empty',
    'Sec-Fetch-Mode': 'cors',
    'Sec-Fetch-Site': 'same-site',
    'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'sec-ch-ua': '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
    'sec-ch-ua-mobile': '?0',
    'sec-ch-ua-platform': '"Linux"',
    'x-User-Context': 'CUSTOMER',
    'x-User-Role': 'CUSTOMER',
}

params = {
    'phone': '9778317025',
    'personTypeId': '21020',
}

response = requests.get('https://api.domclick.ru/portal/api/v1/user_info', params=params, cookies=cookies, headers=headers)
print(response.text)
```

Примечания:
- Одноразовые учетки по телефону

Причина отклонения:
- Данное API закрыто со стороны Сбера


## Буквоед [deprecated]

- Название: Буквоед
- Статус: Одобрен
- Ссылка: [https://www.bookvoed.ru/#register_tab](https://www.bookvoed.ru/#register_tab)
- Дата исследования: 01.01.2023
- Тип поиска: login
- Входные данные: номер телефона или почта
- Выходные поля: наличие учетки, часть телефона
- Ограничения: не выявлено

Сценарий:
- Перейти на сайт
- Ввести номер телефона, остальные поля оставить пустые, "Получить СМС"
- Если пользователь есть, то сообщение "Вы уже зарегистрированы.", иначе ошибки в других полях

Сценарий:
- Перейти на сайт
- Ввести почту, остальные поля оставить пустые, "Получить СМС"
- Если пользователь есть, то сообщение "Этот адрес уже привязан к кабинету с номером +7 920 ***-**-38.", иначе ошибки в других полях

Примечания:
- Возможна группировка с другими магазинами книг

Причина отклонения:
- Буквоед, Читай/Город объединились в одну систему, авторизация по номеру телефона и сразу СМС

Примеры:
- Смотри изображения ниже
![bookvoed_1.png](/sources/researches/resources/bookvoed_1.png)

![bookvoed_2.png](/sources/researches/resources/bookvoed_2.png)

## ЧитайГород [deprecated]

- Название: ЧитайГород
- Статус: Одобрен
- Ссылка: [https://old.chitai-gorod.ru/](https://old.chitai-gorod.ru/)
- Дата исследования: 01.01.2023
- Тип поиска: login
- Входные данные: телефон или почта 
- Выходные поля: наличие учетки, часть телефона
- Ограничения: не выявлено

Сценарий:
- Перейти на сайт
- Ввести номер телефона, остальные поля оставить пустые, "Получить СМС"
- Если пользователь есть, то сообщение "Вы уже зарегистрированы.", иначе ошибки в других полях

Сценарий:
- Перейти на сайт
- Ввести почту, остальные поля оставить пустые, "Получить СМС"
- Если пользователь есть, то сообщение "Этот адрес уже привязан к кабинету с номером +7 920 ***-**-38.", иначе ошибки в других полях

Причина отклонения:
- Буквоед, Читай/Город объединились в одну систему, авторизация по номеру телефона и сразу СМС

Примечания:
- Возможна группировка с другими магазинами книг

Запросы:
```
curl 'https://webapi.chitai-gorod.ru/web/users/checkemail?token=123&action=read&data%5Bemail%5D=kovinevmv%40gmail.com' \
  -H 'authority: webapi.chitai-gorod.ru' \
  -H 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7' \
  -H 'accept-language: ru-RU,ru;q=0.9' \
  -H 'cache-control: no-cache' \
  -H 'cookie: __ddg1_=0SHgxnE6Y1ZAzrX4QWmB; PHPSESSID=2t4b2djro3urkpi3d69sk15552b8jg1q; cguuid=1678696487_2t4b2djro3urkpi3d69sk15552b8jg1q; chg_ref=https%3A%2F%2Fold.chitai-gorod.ru%2F; chg_req=https%3A%2F%2Fold.chitai-gorod.ru%2F; cityId=213; cityName=%C3%EE%F0%EE%E4%20%CC%EE%F1%EA%E2%E0; countryId=643; countryName=%D0%EE%F1%F1%E8%FF; tmr_lvid=f314468112e8e02b30f9bd009367ddc3; tmr_lvidTS=1678696488605; _ym_uid=1678696489966370154; _ym_d=1678696489; _ym_isad=1; _ga=GA1.2.1220715219.1678696489; _gid=GA1.2.1633735446.1678696489; gdeslon.ru.__arc_domain=gdeslon.ru; gdeslon.ru.user_id=613298aa-50cd-4a97-ba23-fe7c9bb5b4c4; VisitorId=5db0cf6c-4670-46d9-aabf-8bf58254a1c0; mindboxDeviceUUID=4775e8ee-a479-4fa4-abc8-b1abe389b26e; directCrm-session=%7B%22deviceGuid%22%3A%224775e8ee-a479-4fa4-abc8-b1abe389b26e%22%7D' \
  -H 'dnt: 1' \
  -H 'pragma: no-cache' \
  -H 'sec-ch-ua: "Chromium";v="110", "Not A(Brand";v="24", "Google Chrome";v="110"' \
  -H 'sec-ch-ua-mobile: ?0' \
  -H 'sec-ch-ua-platform: "Linux"' \
  -H 'sec-fetch-dest: document' \
  -H 'sec-fetch-mode: navigate' \
  -H 'sec-fetch-site: none' \
  -H 'sec-fetch-user: ?1' \
  -H 'upgrade-insecure-requests: 1' \
  -H 'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36' \
  --compressed
```
```
{"result":{"exists":true,"phone":"+7(920)***-**-38"},"error":false}
```

```
curl 'https://webapi.chitai-gorod.ru/web/users/checkphone?token=123&action=read&data%5Bphone%5D=%2B79208533738' \
  -H 'authority: webapi.chitai-gorod.ru' \
  -H 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7' \
  -H 'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7' \
  -H 'cache-control: no-cache' \
  -H 'cookie: __ddg1_=G1KusR0TYrOweo5Ea4j5; PHPSESSID=13okhfjc22gskmuhj7uie9juvuqjcuk3; cguuid=1678696333_13okhfjc22gskmuhj7uie9juvuqjcuk3; chg_ref=https%3A%2F%2Fwww.chitai-gorod.ru%2F; chg_req=https%3A%2F%2Fold.chitai-gorod.ru%2F; cityId=213; cityName=%C3%EE%F0%EE%E4%20%CC%EE%F1%EA%E2%E0; countryId=643; countryName=%D0%EE%F1%F1%E8%FF; _ym_uid=16786961331714481; _ym_d=1678696365; gdeslon.ru.__arc_domain=gdeslon.ru; gdeslon.ru.user_id=d1eaf0e6-39ed-43bc-bbde-dcba8bca052a; tmr_lvid=64ae9bc62470da03ad1a34af44bb6d4d; tmr_lvidTS=1678696259959; VisitorId=e1755e1f-be6e-477b-a571-c34a60fca657; _ym_isad=1; _ga=GA1.2.1805139759.1678696260; _gid=GA1.2.1644885748.1678696260; mindboxDeviceUUID=979a8cc9-63de-4412-ad9c-acbce0ba3839; directCrm-session=%7B%22deviceGuid%22%3A%22979a8cc9-63de-4412-ad9c-acbce0ba3839%22%7D; _gali=registration-form' \
  -H 'dnt: 1' \
  -H 'pragma: no-cache' \
  -H 'sec-ch-ua: "Chromium";v="110", "Not A(Brand";v="24", "Google Chrome";v="110"' \
  -H 'sec-ch-ua-mobile: ?0' \
  -H 'sec-ch-ua-platform: "Linux"' \
  -H 'sec-fetch-dest: document' \
  -H 'sec-fetch-mode: navigate' \
  -H 'sec-fetch-site: none' \
  -H 'sec-fetch-user: ?1' \
  -H 'upgrade-insecure-requests: 1' \
  -H 'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36' \
  --compressed
 ```
 
```
{"result":false,"error":false }
```


## HH.ru [deprecated]

- Название: HH.ru
- Статус: Одобрен
- Ссылка: [https://spb.hh.ru/auth/employer?backurl=%2Femployer%2Fvacancy%2Fcreate&from=employer_index_content&hhtmFromLabel=employer_index_content&hhtmFrom=employer_main](https://spb.hh.ru/auth/employer?backurl=%2Femployer%2Fvacancy%2Fcreate&from=employer_index_content&hhtmFromLabel=employer_index_content&hhtmFrom=employer_main)
- Дата исследования: 01.01.2023
- Тип поиска: login
- Входные данные: почта
- Выходные поля: наличие учетки (работодатель или работник)
- Ограничения: около 3-5 запросов в час, иначе капча. Временный бан по IP

Сценарий:
- Перейти на форму и ввести почту

Примеры:
- ir@heaad.ru - зарегистрированный пользователь как работодатель
- kovinevmv@gmail.com - зарегистрированный как работник

Причина отклонения:
- Сменилось API со стороны HH


![hh_1.png](/sources/researches/resources/hh_1.png)

![hh_2.png](/sources/researches/resources/hh_2.png)