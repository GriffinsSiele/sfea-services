# Список запросов

## Поддерживаемые запросы для сущности __proxy__:
### POST /proxy - создание прокси
- Запрос:
    - URL запроса: ___\<protocol\>://\<hostname:port\>/proxy___
    - Тело запроса:
        ```
        {
            "host": "hostname",
            "port": 8100,
            "login": "login_string",
            "password": "secret_password",
            "protocol": "https",
            "country": "ru",
            "provider": "provider_name",
            "tags": [
                "TagName1", "TagName2"
            ]
        }
        ```
- Ответ:
    - Статусы ответа:
        - 201 - прокси успешно создан
        - 400 - невалидные данные
    - Тело ответа для статуса 201:
        ```
        {
            "id": 1,
            "host": "hostname",
            "port": 8100,
            "login": "login_string",
            "password": "secret_password",
            "protocol": "https",
            "country": "ru",
            "provider": "provider_name",
            "active": true,
            "created": "2023-01-01T15:00:00Z",
            "deleted": null,
            "url": "https://login_string:secret_password@hostname:8100",
            "tags": [
                "TagName1", "TagName2"
            ]
        }
        ```
    - Тело ответа для статуса 400:
        ```
        // Несколько примеров ответа
        // Пример 1: поле port было передано в виде строки или пропущено
        {"error": "port field must be a positive number"}
        // Пример 2: длина поля country больше 2 символов
        {"error": "country field must be no more than 2 characters"}
        // Пример 3: длина поля country больше 2 символов
        {"error": "country field must be no more than 2 characters"}
        // Пример 4: переданный тег не существует в БД
        {"error": "tag \"TagName3\" does not exist"}
        ```

### GET /proxy - получение списка прокси
- Запрос:
    - Поддерживаемые QUERY параметры:
        - filter - параметры фильтрации, по умолчанию фильтрует active=true,
          deleted is null.
            - Данный параметр представляет собой json object, который в качестве
              ключа должен содержать имя поля, в качестве значения - значение для
              фильтрации. Все параметры по умолчанию будут объеденены через __AND__.
            - filter={"protocol": "https", "country": "ru"}. Для вышеприведенного
              примера будут найдены proxy с protocol=https и country=ru.
            - В качестве ключа можно указать $AND или $OR, которые должны содержать
              вложеннйы объект: filter={"$OR": {"protocol": "http", "country": "ru"}}.
            - filter={"$OR": {"$AND": {"protocol": "https","port": 8080},"$AND": {
              "country": "ru","provider": "name"}} - будут найдены proxy с
              protocol=https и port=8080 или proxy с country=ru и provider=name
            - Для связи ManyToMany с сущностью __тег__ в качестве ключа нужно указать
              "tags", в качестве значения - вложенный объект, имеющий структуру родителя.
            - filter={"tags": {"name": "tag1", "name": "tag2"}} - будут найдены proxy,
              у которых есть оба тега tag1 и tag2.
            - filter={"tags": {"$OR": {"name": "tag1", "name": "tag2"}}} - будут найдены
              proxy, у которых есть тег либо tag1, либо tag2, либо оба tag1 и tag2.
        - sort - параметры сортировки:
            - Принимает список вида ["id", "-country", "+port"]. Знак "-" перед именем
              поля означает сортировку по убыванию, знак "+" и отсутствие знака перед
              именем поля означает сортировку по возрастанию.
            - sort=["id", "-country", "-port", "+deleted"] - для приведенного примера,
              поля country, port будут отсортированы с ключевым словом DESC,
              поля id и deleted - с ключевым словом ASC
        - worker - наименование обработчика, по умолчанию - default
        - limit - количество возвращаемых записей, по умолчанию - 1
        - offset - количество записей, которые будут пропущены, по умолчанию - 0
    - URL запроса: \<protocol\>://\<hostname:port\>/proxy?limit=1&offset=0&
        worker=default&sort=-id,port&filter={"$OR": {"country": "us", "country": "ru"},
        "tags": {"name": ["tag1", "tag2"]}}.
        Для описанного запроса будут найдены proxy с country либо us, либо ru и
        тегами tag1 и tag2.
- Ответ:
    - Статусы ответа:
        - 200 - запрос обработан без ошибок
    - Тело ответа:
        ```
        {
            "total": 10,
            "items": [
                {
                    "id": 3,
                    "host": "hostname",
                    "port": 8103,
                    "login": "login_string",
                    "password": "secret_password",
                    "protocol": "https",
                    "country": "ru",
                    "provider": "provider_name_3",
                    "active": true,
                    "created": "2023-01-01T15:00:00Z",
                    "deleted": null,
                    "url": "https://login_string:secret_password@hostname:8103",
                    "tags": [
                        "TagName1", "TagName2"
                    ]
                },
                {
                    "id": 4,
                    "host": "hostname",
                    "port": 8104,
                    "login": "login_string",
                    "password": "secret_password",
                    "protocol": "https",
                    "country": "ru",
                    "provider": "provider_name_4",
                    "active": true,
                    "created": "2023-01-01T15:00:00Z",
                    "deleted": null,
                    "url": "https://login_string:secret_password@hostname:8104",
                    "tags": [
                        "TagName1", "TagName2"
                    ]
                }
            ]
        }
        ```

### GET /proxy/{id} - получение прокси по id
- Запрос:
    - URL запроса: ___\<protocol\>://\<hostname:port\>/proxy/1___
- Ответ:
    - Статусы ответа:
        - 200 - запрос обработан без ошибок
        - 404 - proxy не найден
    - Тело ответа для статуса 200:
        ```
        {
            "id": 1,
            "host": "hostname",
            "port": 8100,
            "login": "login_string",
            "password": "secret_password",
            "protocol": "https",
            "country": "ru",
            "provider": "provider_name",
            "active": true,
            "created": "2023-01-01T15:00:00Z",
            "deleted": null,
            "url": "https://login_string:secret_password@hostname:8100",
            "tags": [
                "TagName1", "TagName2"
            ]
        }
        ```

### PATCH /proxy/{id} - обновление прокси
- Запрос:
    - URL запроса: ___\<protocol\>://\<hostname:port\>/proxy/1___
    - Тело запроса:
        ```
        // Пример 1: все редактируемые поля
        {
            "host": "newhostname",
            "port": 8100,
            "login": "login_string",
            "password": "secret_password",
            "protocol": "https",
            "country": "ru",
            "provider": "provider_name",
            "tags": [
                "TagName1", "TagName3"
            ]
        }
        // Пример 2: часть полей
        {
            "protocol": "https",
            "country": "ru",
            "provider": "provider_name",
            "tags": [
                "TagName1"
            ]
        }
        ```
- Ответ:
    - Статусы ответов:
        - 200 - proxy отредактирован успешно
        - 400 - невалидные данные
        - 404 - proxy не найден
    - Тело ответа для статуса 200
        ```
        {
            "id": 1,
            "host": "newhostname",
            "port": 8100,
            "login": "login_string",
            "password": "secret_password",
            "protocol": "https",
            "country": "ru",
            "provider": "provider_name",
            "active": true,
            "created": "2023-01-01T15:00:00Z",
            "deleted": null,
            "url": "https://login_string:secret_password@hostname:8100",
            "tags": [
                "TagName1", "TagName3"
            ]
        }
        ```
    - Тело ответа для статуса 400:
        ```
        // Несколько примеров ответа
        // Пример 1: поле port было передано в виде строки или пропущено
        {"error": "port field must be a positive number"}
        // Пример 2: длина поля country больше 2 символов
        {"error": "country field must be no more than 2 characters"}
        // Пример 3: длина поля country больше 2 символов
        {"error": "country field must be no more than 2 characters"}
        // Пример 4: переданный тег не существует в БД
        {"error": "tag \"TagName3\" does not exist"}
        ```

### DELETE /proxy/{id} - удаление прокси (обновление поля deleted)
- Запрос:
    - URL запроса: ___\<protocol\>://\<hostname:port\>/proxy/1___
- Ответ:
    - Статусы ответа:
        - 204 - proxy успешно удален (обновлено поле deleted)
        - 404 - proxy не найден

### POST /proxy/{id}/report - отчет обработчика об успешном обращении к proxy.
Значение __count_success__ в таблице proxy_usage увеличится на 1, __last_success__
обновится текущей датой
- QUERY параметры:
    - worker - наименование обработчика, для которого будет обновлена статистика,
      обязательный параметр
- Запрос:
    - URL запроса: ___\<protocol\>://\<hostname:port\>/proxy/1/report?worker=worker1___
- Ответ:
    - Статусы ответа:
        - 200 - статистика успешно обновлена
        - 404 - не найден proxy, указанный в URL строке
<hr/><br/>


## Поддерживаемые запросы для сущности __worker__:
### POST /worker/block-proxy - блокировка прокси для обработчика
- Запрос:
    - URL запроса: ___\<protocol\>://\<hostname:port\>/worker/block-proxy___
    - Тело запроса:
        ```
        {
            "worker": "worker1",
            "block_proxy_ids": [1, 2],
            "unblock_proxy_ids": [3, 4]
        }
        ```
- Ответ:
    - Статусы ответа:
        - 200 - proxy успешно заблокированы/разблокированы
        - 404 - worker или proxy с указанным id не найден
    - Тело ответа для статуса 404:
        ```
        // Пример 1: worker не найден
        {"error": "worker with id \"1\" does not exist"}
        // Пример 2: proxy не найден
        {"error": "proxy with id \"1\" does not exist"}
        ```
<hr/><br/>


## Поддерживаемые запросы для сущности __tag__:
### POST /tag - создание тега
- Запрос:
    - URL запроса: ___\<protocol\>://\<hostname:port\>/tag___
    - Тело запроса:
        ```
        {"name": "TagName1"}
        ```
- Ответ:
    - Статусы ответов:
        - 201 - тег создан успешно
        - 400 - невалидные данные
    - Тело ответа для статуса 201:
        ```
        {"id": 1, "name": "TagNumber1"}
        ```
    - Тело ответа для статуса 400 (данные не были переданны в теле запроса):
        ```
        {"error": "name field is required"}
        ```

### GET /tag - получение списка тегов
- Запрос:
    - URL запроса: ___\<protocol\>://\<hostname:port\>/tag___
- Ответ:
    - Статусы ответов:
        - 200 - запрос обработан без ошибок
    - Тело ответа для статуса 200
        ```
        [
            {
                "id": 1,
                "name": "TagNumber1"
            },
            {
                "id": 2,
                "name": "TagNumber2"
            }
        ]
        ```

### DELETE /tag/{id} - удаление тега
- Запрос:
    - URL запроса: ___\<protocol\>://\<hostname:port\>/tag/1___
- Ответ:
    - Статусы ответов:
        - 204 - тег успешно удален
        - 404 - тег с id {id} не найден

