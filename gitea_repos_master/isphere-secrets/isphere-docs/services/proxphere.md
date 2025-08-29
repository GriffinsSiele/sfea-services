---
title: ProxPhere
description: Сервис прокси
published: true
date: 2024-03-15T07:22:31.907Z
tags: proxphere
editor: markdown
dateCreated: 2024-02-21T09:01:32.071Z
---

# ProxPhere

Сервис упрощает работу с множеством прокси сервисов.

## Описание параметров запроса

### Простое использование

```bash
curl -k -i \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection 
```

В этом случае внешние прокси не используются и весь трафик идет через сервер, на котором расположен сервер прокси.

Также для совместимости с произвольными системами конфигурации тождественен следующему:

```bash
curl -k -i \
  -H "X-Sphere-Proxy-Spec-Disable: true" \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection
```

### Выбор специфического региона

```bash
curl -k -i \
  -H "X-Sphere-Proxy-Spec-Country-Code: de" \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection
```

### Выбор группы прокси

```bash
curl -k -i \
  -H "X-Sphere-Proxy-Spec-Group-Id: 5" \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection
```

### Выбор определенного прокси

```bash
curl -k -i \
  -H "X-Sphere-Proxy-Spec-Id: 1638" \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection
```

### Установка времени жизни попытки соединения

```bash
curl -k -i \
  -H "X-Sphere-Proxy-Spec-Ttl: 30s" \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection
```

Обратите внимание, что формат заголовка требует явного указания единиц измерения.

| Значение      | Описание           |
|---------------|--------------------|
| `10s`         | 10 секунд          |
| `1m2s`        | 1 минута 2 секунды |
| `1µs` (`1us`) | 1 микросекунда     |
| `1ms`         | 1 миллисекунда     |

### Подмена отпечатка TLS соединения (JA3)

```bash
curl -k -i \
  -H "X-Sphere-JA3: 771,4865-4866-4867-49196-49195-52393-49200-49199-52392-49162-49161-49172-49171-157-156-53-47-49160-49170-10,0-5-10-11-13-16-18-23-27-43-45-51-65281,29-23-24-25,0" \
  -H "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3.1 Safari/605.1.15" \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection
```

Обратите внимание, что одного JA3 в большинстве случаев недостаточно и помимо подписи соединения необходимо маскировать соединение определенными заголовками и поведением.

### Стратегия выбора прокси из множества

В случае, когда заголовки выше приводят к появлению более чем одного прокси, можно выбрать стратегию выбора прокси из полученного множества.

Стратегия указывается в заголовке `X-Sphere-Proxy-Spec-Strategy`.

```bash
curl -k -i \
  -H "X-Sphere-Proxy-Spec-Country-Code: de" \
  -H "X-Sphere-Proxy-Spec-Strategy: random(2)" \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection
```

Стратегии указываются в порядке использования используя символ-разделитель `|` и могут иметь как обязательные, так и необязательные аргументы в круглых скобках.

Необязательные аргументы можно опускать. Например, `fast(2)` вернет два наиболее быстро откликнувшихся прокси, а аргумент `fast` отсортирует весь перечень прокси по времени отклика.

Стратегия может выглядеть следующим образом:

```bash 
X-Sphere-Proxy-Spec-Strategy: random(10)|fast(3)|top
```

Эта запись определяет следующее поведение:

- из всего перечня прокси в произвольном порядке выбирается 10 записей
- из которых берутся три наиболее быстро откликнувшихся прокси
- из которых берётся первая

Описание стратегий:

| Значение   | Аргументы          | Описание                                                                                                | Комментарий                                                          |
|------------|--------------------|---------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------|
| `top`      | `[(N=1)]`          | Выбрать первые значения                                                                                 |                                                                      |
| `last`     | `[(N=1)]`          | Выбрать последние значения                                                                              |                                                                      |
| `random`   | `[(N=1)]`          | Выбрать произвольные значения                                                                           |                                                                      |
| `fast`     | `[(N=1)]`          | Выбрать наиболее быстро откликнувшиеся прокси                                                           | Если прокси откликается дольше чем 200ms, то он исключается          |
| `pass`     | `[(N=1,[Ttl=1h])]` | Предпочитать прокси, через которые ранее были осуществлены успешные соединения к запрашиваемому ресурсу | Прокси с успешными соединениями перемещаются вверх списка приоритета |
| `not_fail` | `[(N=1,[Ttl=1h])]` | Исключить прокси, в которых были ошибки за период                                                       |                                                                      |

Значение по умолчанию, если явно не определено иное - `random`.

**Внимание!** Если после фильтрации стратегиями остаётся более чем один вариант прокси, все они запускаются последовательно в рамках указанного Ttl вплоть до поступления успешного ответа от целевого сервера.

Например, если нам нужно осуществить **три** попытки осуществить запрос к какому-либо ресурсу через произвольные прокси, то стратегия может выглядеть следующим образом:

```bash 
X-Sphere-Proxy-Spec-Strategy: random(3)
```

### Ограничение времени выполнения попытки обращения для множественных стратегий

```bash
curl -k -i \
  -H "X-Sphere-Proxy-Spec-Strategy: random(10)" \
  -H "X-Sphere-Proxy-Spec-Strategy-Ttl: 5s" \
  -H "X-Sphere-Proxy-Spec-Ttl: 15s" \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection
```

Эта конфигурация говорит о том, что на каждую попытку дается время выполнения не более пяти секунд, после чего она признаётся неуспешной.

> Особое внимание стоит уделять комбинацией заголовка `X-Sphere-Proxy-Spec-Strategy-Ttl` с заголовком `X-Sphere-Proxy-Spec-Ttl`, который ограничивает общее время выполнения.
{.is-info}

> Если истечёт время указанное в заголовке `X-Sphere-Proxy-Spec-Ttl`, то процесс будет прерван вне зависимости от того, какое количество прокси было выбрано для выполнения запроса.
{.is-warning}

### Комбинированные заголовки

Сервис поддерживает комбинации заголовков в любых комбинациях, например:

```bash
curl -k -i \
  -H "X-Sphere-Impersonate-Id: chrome100" \
  -H "X-Sphere-Proxy-Spec-Country-Code: ru" \
  -H "X-Sphere-Proxy-Spec-Group-Id: 5" \
  -H "X-Sphere-Proxy-Spec-Ttl: 30s" \
  -H "X-Sphere-Proxy-Spec-Strategy: not_fail(3, 1h)" \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection
```

В данном случае осуществляется запрос с подменой JA3 по группе прокси `5` в российском регионе с тремя попытками через прокси, в которых не было ошибок за последний час.

### Отладочная информация о запросе 

```bash
curl -k -i \
  -H "X-Sphere-Verbose: true" \
  --proxy socks5://proxphere-master-socks5.proxphere.svc.cluster.local \
  https://infosfera.ru/.well-known/connection
```

Пример ответа:

```bash
HTTP/2.0 200 OK
Connection: close
Content-Type: application/yaml
Date: Wed, 06 Mar 2024 07:10:17 GMT
Server: SpheriX/1.23.4
Set-Cookie: X-Sphere-Proxy-Spec-Id=1638; Path=/; Max-Age=604800; HttpOnly
X-Request-Id: 0cda7005-42da-41a4-8677-6642a0c1c122
X-Sphere-Proxy-Spec-Country-Code: ru
X-Sphere-Proxy-Spec-Id: 1638
X-Sphere-Verbose: *   Trying 193.56.190.46:8000...
X-Sphere-Verbose: * Connected to 193.56.190.46 (193.56.190.46) port 8000
X-Sphere-Verbose: * CONNECT tunnel: HTTP/1.1 negotiated
X-Sphere-Verbose: * allocate connect buffer
X-Sphere-Verbose: * Proxy auth using Basic with user 'VbWeek'
X-Sphere-Verbose: * Establish HTTP proxy tunnel to infosfera.ru:443
X-Sphere-Verbose: > CONNECT infosfera.ru:443 HTTP/1.1
X-Sphere-Verbose: > Host: infosfera.ru:443
X-Sphere-Verbose: > Proxy-Authorization: Basic VmJXZWVrOkJCbjhGbQ==
X-Sphere-Verbose: > User-Agent: curl/8.5.0
X-Sphere-Verbose: > Proxy-Connection: Keep-Alive
X-Sphere-Verbose: >
X-Sphere-Verbose: < HTTP/1.0 200 Connection established
X-Sphere-Verbose: <
X-Sphere-Verbose: * CONNECT phase completed
X-Sphere-Verbose: * CONNECT tunnel established, response 200
X-Sphere-Verbose: * ALPN: curl offers h2,http/1.1
X-Sphere-Verbose: } [5 bytes data]
X-Sphere-Verbose: * TLSv1.3 (OUT), TLS handshake, Client hello (1):
X-Sphere-Verbose: } [512 bytes data]
X-Sphere-Verbose: * TLSv1.3 (IN), TLS handshake, Server hello (2):
X-Sphere-Verbose: { [122 bytes data]
X-Sphere-Verbose: * TLSv1.3 (IN), TLS handshake, Encrypted Extensions (8):
X-Sphere-Verbose: { [15 bytes data]
X-Sphere-Verbose: * TLSv1.3 (IN), TLS handshake, Certificate (11):
X-Sphere-Verbose: { [3762 bytes data]
X-Sphere-Verbose: * TLSv1.3 (IN), TLS handshake, CERT verify (15):
X-Sphere-Verbose: { [78 bytes data]
X-Sphere-Verbose: * TLSv1.3 (IN), TLS handshake, Finished (20):
X-Sphere-Verbose: { [36 bytes data]
X-Sphere-Verbose: * TLSv1.3 (OUT), TLS change cipher, Change cipher spec (1):
X-Sphere-Verbose: } [1 bytes data]
X-Sphere-Verbose: * TLSv1.3 (OUT), TLS handshake, Finished (20):
X-Sphere-Verbose: } [36 bytes data]
X-Sphere-Verbose: * SSL connection using TLSv1.3 / TLS_AES_128_GCM_SHA256 / X25519 / id-ecPublicKey
X-Sphere-Verbose: * ALPN: server accepted h2
X-Sphere-Verbose: * Server certificate:
X-Sphere-Verbose: *  subject: CN=infosfera.ru
X-Sphere-Verbose: *  start date: Feb  6 09:58:44 2024 GMT
X-Sphere-Verbose: *  expire date: May  6 09:58:43 2024 GMT
X-Sphere-Verbose: *  issuer: C=US; O=Let's Encrypt; CN=R3
X-Sphere-Verbose: *  SSL certificate verify result: unable to get local issuer certificate (20), continuing anyway.
X-Sphere-Verbose: *   Certificate level 0: Public key type EC/prime256v1 (256/128 Bits/secBits), signed using sha256WithRSAEncryption
X-Sphere-Verbose: *   Certificate level 1: Public key type RSA (2048/112 Bits/secBits), signed using sha256WithRSAEncryption
X-Sphere-Verbose: *   Certificate level 2: Public key type RSA (4096/152 Bits/secBits), signed using sha256WithRSAEncryption
X-Sphere-Verbose: { [5 bytes data]
X-Sphere-Verbose: * TLSv1.3 (IN), TLS handshake, Newsession Ticket (4):
X-Sphere-Verbose: { [122 bytes data]
X-Sphere-Verbose: * using HTTP/2
X-Sphere-Verbose: * [HTTP/2] [1] OPENED stream for https://infosfera.ru/.well-known/connection
X-Sphere-Verbose: * [HTTP/2] [1] [:method: GET]
X-Sphere-Verbose: * [HTTP/2] [1] [:scheme: https]
X-Sphere-Verbose: * [HTTP/2] [1] [:authority: infosfera.ru]
X-Sphere-Verbose: * [HTTP/2] [1] [:path: /.well-known/connection]
X-Sphere-Verbose: * [HTTP/2] [1] [user-agent: curl/8.5.0]
X-Sphere-Verbose: * [HTTP/2] [1] [accept: */*]
X-Sphere-Verbose: * [HTTP/2] [1] ['user-agent: curl/8.4.0']
X-Sphere-Verbose: * [HTTP/2] [1] ['accept: */*']
X-Sphere-Verbose: } [5 bytes data]
X-Sphere-Verbose: > GET /.well-known/connection HTTP/2
X-Sphere-Verbose: > Host: infosfera.ru
X-Sphere-Verbose: > User-Agent: curl/8.5.0
X-Sphere-Verbose: > Accept: */*
X-Sphere-Verbose: > 'User-Agent: curl/8.4.0'
X-Sphere-Verbose: > 'Accept: */*'
X-Sphere-Verbose: >
X-Sphere-Verbose: { [5 bytes data]
X-Sphere-Verbose: < HTTP/2 200
X-Sphere-Verbose: < content-type: application/yaml
X-Sphere-Verbose: < server: SpheriX/1.23.4
X-Sphere-Verbose: < x-request-id: 0cda7005-42da-41a4-8677-6642a0c1c122
X-Sphere-Verbose: < content-length: 802
X-Sphere-Verbose: < date: Wed, 06 Mar 2024 07:10:17 GMT
X-Sphere-Verbose: <
X-Sphere-Verbose: { [802 bytes data]
X-Sphere-Verbose: * Connection #0 to host 193.56.190.46 left intact
```

## Описание специфических заголовков ответа

В каждом ответе присутствует перечень заголовков, которые могут помочь выстраивать цепочку запросов в том числе в автоматическом режиме.

| Заголовок                          | Описание                           |
|------------------------------------|------------------------------------|
| `X-Sphere-Proxy-Spec-Id`           | Идентификатор используемого прокси |
| `X-Sphere-Proxy-Spec-Country-Code` | Регион используемого прокси        |

Также описанные заголовки, которые совпадают с управляющими заголовками описанными в предыдущем разделе заголовков запроса, проставляются в `Set-Cookie`, например:

```bash
Set-Cookie: X-Sphere-Proxy-Spec-Id=29; Path=/; Max-Age=604800; HttpOnly
```

Если использовать Cookie Jar, то все последующие запросы будут использовать ранее использованный прокси.
