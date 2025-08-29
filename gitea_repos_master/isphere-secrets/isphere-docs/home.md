---
title: Главная
description: Главная страница
published: true
date: 2024-05-07T07:56:20.696Z
tags: 
editor: markdown
dateCreated: 2023-08-23T10:20:40.930Z
---

# Добро пожаловать

Здесь описана документация команды ООО "ИНФО СФЕРА".

Полезные ссылки:

- Система: https://i-sphere.ru/2.00
- Git проектов: http://gitea-http.gitea.svc.cluster.local
- CI: http://drone.gitea.svc.cluster.local/
- Grafana: http://grafana.prometheus.svc.cluster.local
- GlitchTip (Sentry): http://glitchtip-web.glitchtip.svc.cluster.local
- Redmine: http://redmine.redmine.svc.cluster.local
- WikiJS: http://wikijs.wikijs.svc.cluster.local
- Jaeger: http://jaeger-query.jaeger.svc.cluster.local (172.16.97.10:6831)
- Kafka UI: http://kafka-ui.kafka.svc.cluster.local
- HoppScotch^1^: https://hoppscotch.hoppscotch.svc.cluster.local
- DataLens: http://datalens-web.datalens.svc.cluster.local
- Sonarqube: ???

---
^1^ - необходима установка корневого сертификата в браузер/операционную систему:
<details>
  <summary>ca.pem</summary>
  
  ```
  -----BEGIN CERTIFICATE-----
MIID5zCCAs+gAwIBAgIUMPWw56LNsVjHcByZKkOtTcxg0QYwDQYJKoZIhvcNAQEL
BQAwgYIxCzAJBgNVBAYTAlJVMQ8wDQYDVQQIDAZNb3Njb3cxDzANBgNVBAcMBk1v
c2NvdzEQMA4GA1UECgwHaVNwaGVyZTELMAkGA1UECwwCSVQxEDAOBgNVBAMMB2lT
cGhlcmUxIDAeBgkqhkiG9w0BCQEWEWFkbWluQGktc3BoZXJlLnJ1MB4XDTI0MDUw
NjExMjA0NFoXDTI1MDUwNjExMjA0NFowgYIxCzAJBgNVBAYTAlJVMQ8wDQYDVQQI
DAZNb3Njb3cxDzANBgNVBAcMBk1vc2NvdzEQMA4GA1UECgwHaVNwaGVyZTELMAkG
A1UECwwCSVQxEDAOBgNVBAMMB2lTcGhlcmUxIDAeBgkqhkiG9w0BCQEWEWFkbWlu
QGktc3BoZXJlLnJ1MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA9JIE
UR5QZMAvha7DS7RftOGIu3PNChLK9fiq2Ikqc8o3wQ3AqxRJ5Y0TwyqF7JMp3pz1
9rr4NO+Q9zbEoYBwRmNunQx1/Gbupq5dipzpJtOnF21fs0Vy7/U3ui1Z31Cv67jR
19Z2uJv7JIwV2PIh5g6j7+mJetwVH8g4AHNqfWn/h76u2MmJ5I7vF7tFERRW14sj
F4HVO6Mk74bH1Hr1Pqw0nuxM4tFwW+TGY3HliB332zlH0BD34MwR2/UR2XVvMaDy
QGbbfa1oouwNb6L0pKiryyrx/n6uQynUWpOg1fa2wAmCYKWDuq2TPZoS8YGWtSVU
K3aoTAIK0Kd+j3HIrQIDAQABo1MwUTAdBgNVHQ4EFgQUDDtJDN4E6OuRk8XWEtGA
HTrEQScwHwYDVR0jBBgwFoAUDDtJDN4E6OuRk8XWEtGAHTrEQScwDwYDVR0TAQH/
BAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEAGLIdWQ66IPe+CiSTrFJig5b3oToV
TdkbZHHIsYkMf7Rzfo18ni042fkSg5navyNH+xG7UeTnf5GNaljqMYBSbl47udc3
wTDIS0uuhMVHyAuATkjk8UmN3klKBdIzH4zLGs7y/7ocI9nvqQv47dK5y5ujEbMY
RLqgypa9TLBbCK+1hX8sCOG0vmKh4iJIA5gacfNOnxhXW6Q/Zeero5AiwH/zYFZk
SLOEZtdsFxZjFGj//YifA/BrHvrHeqRlAWOry9eBY/y0EzIpNhGAEYoSWlzpJ/jn
JAHVpSSB+6ILotQy8gODMqYcdmblZgnOY99HzHOXsCfBLR/OT2S+IeBBPw==
-----END CERTIFICATE-----
  ```
</details>