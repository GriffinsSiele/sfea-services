---
title: Регистрация сессий
description: 
published: true
date: 2024-04-08T16:09:00.591Z
tags: 
editor: markdown
dateCreated: 2024-04-08T16:08:24.414Z
---

# Регистрация сессий


Сессий для источника 2gis-web-api используются из БД MongoDB. 

Структура сессии:
```json
        {
            "cookies": {
                "_2gis_webapi_session": "07a095b0-b751-4e51-ab01-ea59323f2cad",
                "_2gis_webapi_user": "cf7b3dd4-af78-4c84-9416-00a6bded0a5e",
                "spid": "1666605447343_73aaf82b7e247cd1116c53ec5a2a305c_09ulh9vfvdqbd2n5",
            },
            "auth_query": {
                "search_user_hash": "7293074260173678128",
                "stat[sid]": "a07c09e9-5da5-429a-b8d8-81c051c7fdcc",
                "stat[user]": "0f26f46e-3aeb-48d0-b46d-0e050d3e5dda",
            },
            "ja3": "771,4865-4866-4867-49195-49199-49196-49200-52393-52392-49171-49172-156-157-47-53,0-23-65281-10-11-35-16-5-13-18-51-45-43-27-21,29-23-24,0",
            "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36",
            "proxy_id": "13",
            "captcha_block": false,
        }
```

Обработчик автоматически обновляет поля cookies, auth_query при достижении предельного срока жизни. 

- cookies - автоматически создается, указать `{}`
- auth_query - автоматически создается, указать `{}`
- ja3 - использовать из requests-logic примеры. Можно здесь: https://tls.peet.ws/api/all
- user_agent - можно свой из браузера
- proxy_id - взять из get_proxies.php?proxygroup=5
- captcha_block - false, временная блокировка из-за не прохождения внутренней капчи 2gis