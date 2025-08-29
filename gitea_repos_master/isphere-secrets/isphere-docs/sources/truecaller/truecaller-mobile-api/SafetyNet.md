---
title: Аттестация SafetyNet
description: 
published: true
date: 2023-03-13T17:07:52.528Z
tags: 
editor: markdown
dateCreated: 2023-03-13T17:07:52.528Z
---

## Обоснования невозможности автоматической регистрации через API

Документация от 02/2022

Основной сценарий регистрации в TrueCaller описан на странице API.

Кроме этих запросов происходит регистрация мобильного устройства в FireBase. 

## 1. Получение токена
```
POST /v1/projects/truecallerapis/installations HTTP/1.1
Content-Type: application/json
Accept: application/json
Content-Encoding: gzip
Cache-Control: no-cache
X-Android-Package: com.truecaller
x-firebase-client: fire-analytics-ktx/19.0.1 fire-perf/20.0.2 android-target-sdk/30 fire-core-ktx/20.0.0 fire-analytics/19.0.1 kotlin/1.5.10 device-model/vbox86p device-name/vbox86p android-min-sdk/ fire-fcm-ktx/22.0.0 fire-core/20.0.0 fire-cfg-ktx/21.0.1 fire-abt/21.0.0 fire-android/23 android-installer/com.android.vending fire-installations/17.0.0 fire-fcm/22.0.0 fire-rc/21.0.1 device-brand/Android android-platform/ fire-cls/18.2.1 fire-perf-ktx/20.0.2
x-firebase-client-log-type: 3
X-Android-Cert: 0AC1169AE6CEAD75264C725FEBD8E8D941F25E31
x-goog-api-key: AIzaSyB-U1NIB36__pxbjuXECXX28S8PK6l672g
User-Agent: Dalvik/2.1.0 (Linux; U; Android 6.0; Google Build/MRA58K)
Host: firebaseinstallations.googleapis.com
Connection: close
Accept-Encoding: gzip, deflate
Content-Length: 133

�������«VJËLQ²RJõ
(4Iö­
Ô-Õñ
)tt3KTÒQJ,(ð)0´2226·°00²06²JÌK)ÊÏL±J101H³H²4OK36µHµ�i(-ÉK-*ÎÌÏjsó/3
§d#D­Íõôjqt���
```

- X-Android-Cert - сертификат Android приложения
- x-goog-api-key - рандом ??? - *токен firebase*

```
{"fid":"eMPq4cMzQ-u-_TMTqGQF6a","appId":"1:22378802832:android:d040f8b97ff358e8","authVersion":"FIS_v2","sdkVersion":"a:17.0.0"}
```
- fid - рандомная строка
- appId, authVersion - константы

Ответ:
```
HTTP/1.1 200 OK
Content-Type: application/json; charset=UTF-8
Vary: Origin
Vary: X-Origin
Vary: Referer
Date: Mon, 24 Jan 2022 17:31:20 GMT
Server: ESF
Cache-Control: private
X-XSS-Protection: 0
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Alt-Svc: h3=":443"; ma=2592000,h3-29=":443"; ma=2592000,h3-Q050=":443"; ma=2592000,h3-Q046=":443"; ma=2592000,h3-Q043=":443"; ma=2592000,quic=":443"; ma=2592000; v="46,43"
Connection: close
Content-Length: 569

{
  "name": "projects/22378802832/installations/eMPq4cMzQ-u-_TMTqGQF6a",
  "fid": "eMPq4cMzQ-u-_TMTqGQF6a",
  "refreshToken": "2_bTC6lMiBtlV2Ka7uaOImRLs4lYYjNYBWyADk9nOKDHxVYbIqNA-MRpDfuch7lzYK",
  "authToken": {
    "token": "eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9.eyJhcHBJZCI6IjE6MjIzNzg4MDI4MzI6YW5kcm9pZDpkMDQwZjhiOTdmZjM1OGU4IiwiZXhwIjoxNjQzNjUwMjgwLCJmaWQiOiJlTVBxNGNNelEtdS1fVE1UcUdRRjZhIiwicHJvamVjdE51bWJlciI6MjIzNzg4MDI4MzJ9.AB2LPV8wRAIgH6fWxwWAVeZ2MmlGX29bMORoIy9Rol-kgZohgis9h6ACIBmUO8AGcqJ3EINUfDVoqbQPS_rzYdQz9LDxqAM7DHMa",
    "expiresIn": "604800s"
  }
}

```
- authToken.token - подписанный firebase token

## 2. Настройка (1, 2)

Запрос 1
```
POST /v1/projects/22378802832/namespaces/fireperf:fetch HTTP/1.1
X-Goog-Api-Key: AIzaSyB-U1NIB36__pxbjuXECXX28S8PK6l672g
X-Android-Package: com.truecaller
X-Android-Cert: 0AC1169AE6CEAD75264C725FEBD8E8D941F25E31
X-Google-GFE-Can-Retry: yes
X-Goog-Firebase-Installations-Auth: eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9.eyJhcHBJZCI6IjE6MjIzNzg4MDI4MzI6YW5kcm9pZDpkMDQwZjhiOTdmZjM1OGU4IiwiZXhwIjoxNjQzNjUwMjgwLCJmaWQiOiJlTVBxNGNNelEtdS1fVE1UcUdRRjZhIiwicHJvamVjdE51bWJlciI6MjIzNzg4MDI4MzJ9.AB2LPV8wRAIgH6fWxwWAVeZ2MmlGX29bMORoIy9Rol-kgZohgis9h6ACIBmUO8AGcqJ3EINUfDVoqbQPS_rzYdQz9LDxqAM7DHMa
Content-Type: application/json
Accept: application/json
Content-Length: 642
User-Agent: Dalvik/2.1.0 (Linux; U; Android 6.0; Google Build/MRA58K)
Host: firebaseremoteconfig.googleapis.com
Connection: close
Accept-Encoding: gzip, deflate

{"platformVersion":"23","appInstanceId":"eMPq4cMzQ-u-_TMTqGQF6a","packageName":"com.truecaller","appVersion":"12.8.6","countryCode":"US","sdkVersion":"21.0.1","appBuild":"1208006","analyticsUserProperties":{},"languageCode":"en-US","appId":"1:22378802832:android:d040f8b97ff358e8","appInstanceIdToken":"eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9.eyJhcHBJZCI6IjE6MjIzNzg4MDI4MzI6YW5kcm9pZDpkMDQwZjhiOTdmZjM1OGU4IiwiZXhwIjoxNjQzNjUwMjgwLCJmaWQiOiJlTVBxNGNNelEtdS1fVE1UcUdRRjZhIiwicHJvamVjdE51bWJlciI6MjIzNzg4MDI4MzJ9.AB2LPV8wRAIgH6fWxwWAVeZ2MmlGX29bMORoIy9Rol-kgZohgis9h6ACIBmUO8AGcqJ3EINUfDVoqbQPS_rzYdQz9LDxqAM7DHMa","timeZone":"America\/New_York"}
```

- X-Goog-Api-Key - firebase токен
- X-Android-Cert - см. предыдущий запрос
- X-Goog-Firebase-Installations-Auth, appInstanceIdToken - подписанный firebase токен
- appInstanceId - fid из предыдущего запроса
- appId - см. предыдущий запрос

Запрос 2
```
Аналогичный, только URL: POST /v1/projects/22378802832/namespaces/firebase:fetch HTTP/1.1
```


## Аттестация (Шаг 1)

Запрос
```
GET /v1/attestation/android/getNonce?encoding=json HTTP/1.1
Authorization: Bearer a1i02--WtIzmmF-k3EyspitBFipoEpt-SIfCg4bhpyiZb8zB05knC0IDS6CimQsL
Host: device-safety-noneu.truecaller.com
Connection: close
Accept-Encoding: gzip, deflate
User-Agent: Truecaller/12.8.6 (Android;6.0)
```

- Authorization - truecaller токен

Ответ
```
HTTP/1.1 200 OK
vary: Accept-Encoding
date: Mon, 24 Jan 2022 17:32:38 GMT
content-type: application/json
x-envoy-upstream-service-time: 4
server: istio-envoy
Via: 1.1 google
Alt-Svc: h3=":443"; ma=2592000,h3-29=":443"; ma=2592000
Connection: close
Content-Length: 72

{"nonce":"VlRBUE5VQVRRS1M1MVpic2xTS2xid0FoNVRfeXlRQXdBQUFCZm8wbG1pMD0="}
```
nonce - сохраняем

## Аттестация (Шаг 2)

Потерян, нужно искать, аналог - https://github.com/shchuchynshchyna/antiabuse-request/blob/master/src/main/java/droidguard/antiabuse/request/AntiabuseRequest.java

## Аттестация (Шаг 3)
Запрос
```
POST /androidcheck/v1/attestations/attest?alt=PROTO&key=AIzaSyB-U1NIB36__pxbjuXECXX28S8PK6l672g HTTP/1.1
Host: www.googleapis.com
Connection: close
Content-Length: 11665
X-Android-Package: com.truecaller
X-Android-Cert: 0AC1169AE6CEAD75264C725FEBD8E8D941F25E31
Content-Type: application/x-protobuf
User-Agent: SafetyNet/214815022 (vbox86p MRA58K); gzip
Accept-Encoding: gzip, deflate


Û
,VTAPNUATQKS51ZbslSKlbwAh5T_yyQAwAAABfo0lmi0=com.truecaller wµòUòÈ[EjÄ8¹£X-ÍÒ·:ÀnçFI®" ²5v(´n®¶Ô<¦Ü÷?+ï³¥tÈ]1q(®¢·f22
/system/bin/su Ãû½¢méË#vÆ£Àfë0Q¿å°Á¡¤ÐX^J23
/system/xbin/su Ãû½¢méË#vÆ£Àfë0Q¿å°Á¡¤ÐX^J2
/system/xbin/librank2
/system/xbin/procmem2
/system/xbin/procrank2
/system/xbin/su:��@¶éè/H�°XCgZSYXpep78S_kEKBkt7nPspbtIQQAAAuBKbht3U2vcAHD_2-x7pPXkAfoAvihatfYEILpRP-0t-KzgAeJSjngetw9wBIOWlHGDTc3QA0VaLOHFa7qzaB-kFAOuhCBJJWuD5-14iqlslI0HDP6sd3PFMREu5uFqPSqAG-tnuzH5ApFwUDpnH5HcvI3DqxmB-tylqX5sHdUOq4H89Ls2hczfVy0OjZku9tsnRRuYBdILKYDMZ-4nDcVqNauD7LqTBhbhT8lvo1ZKZdrpxifT5TtNlPfR3ZTcQyaTLngtsFEctRimdV8SkNuolI4tLg-............HKm_04h4Nk7DLUVXUBAsGhcKAggBCgUIDBCzAgoECA0QAAoECA4QACIA
```
Protobuf decode
```
1 {
  1: "VTAPNUATQKS51ZbslSKlbwAh5T_yyQAwAAABfo0lmi0="
  2: "com.truecaller"
  3: "w\022\265\362U\362\310[\213\026E\031\021j\3048\033\271\243X-\315\322\267:\300n\347FI\023\256"
  4: "\2625v(\264n\001\256\266\006\177\002\324<\024\246\334\367\223?+\357\263\245t\310\007]\2001\211q"
  5: 214815022
  6 {
    1: "/system/bin/su"
    2: "\303\373\223\232\275\242m\351\313#v\306\243\300f\200\3530Q\277\345\260\004\301\226\241\244\320X^J\013"
  }
  6 {
    1: "/system/xbin/su"
    2: "\303\373\223\232\275\242m\351\313#v\306\243\300f\200\3530Q\277\345\260\004\301\226\241\244\320X^J\013"
  }
  6 {
    1: "/system/xbin/librank"
  }
  6 {
    1: "/system/xbin/procmem"
  }
  6 {
    1: "/system/xbin/procrank"
  }
  6 {
    1: "/system/xbin/su"
  }
  7 {
    1: 0
    2: 0
  }
  8: 1643045559045
  9: 0
}
2: "CgZSYXpep78S_kEKBkt7nPspbtIQQAAAuBKbht3U2vcAHD_2-x7pPXkAfoAvihatfYEILpRP-0t-KzgAeJSjngetw9wBIOWlHGDTc3QA0VaLOHFa7qzaB-kFAOuhCBJJWuD5-14iqlslI0HDP6sd3PFMREu5uFqPSqAG-tnuzH5ApFwUDpnH5HcvI3DqxmB-tylqX5sHdUOq4H89Ls2hczfVy0OjZku9tsnRRuYBdILKYDMZ-4nDcVqNauD7LqTBhbhT8lvo1ZKZdrpxifT5TtNlPfR3ZTcQyaTLngtsFEctRimdV8SkNuolI4tLg-pzwu8bHZMhJDycM5c9TdVo_m6QYgpyOEhJmoXAo5v3v42cCGOwgymTiM-Dwf0kcQP20PliNE_dSXnI8SMdkFTw8rp-........HKm_04h4Nk7DLUVXUBAsGhcKAggBCgUIDBCzAgoECA0QAAoECA4QACIA"
```

- key - firebase токен
- Последняя часть подписана через nonce. Читать что тут происходит - https://habr.com/ru/post/442872/

Ответ
```
HTTP/1.1 200 OK
Content-Type: application/x-protobuf
Content-Disposition: attachment
Vary: Origin
Vary: X-Origin
Vary: Referer
Date: Mon, 24 Jan 2022 17:32:39 GMT
Server: ESF
Cache-Control: private
X-XSS-Protection: 0
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Alt-Svc: h3=":443"; ma=2592000,h3-29=":443"; ma=2592000,h3-Q050=":443"; ma=2592000,h3-Q046=":443"; ma=2592000,h3-Q043=":443"; ma=2592000,quic=":443"; ma=2592000; v="46,43"
Connection: close
Content-Length: 8153

Ö?eyJhbGciOiJSUzI1NiIsIng1YyI6WyJNSUlGWHpDQ0JFZWdBd0lCQWdJUUJRc1UvdmFzWlpZS0FBQUFBU1NvQlRBTkJna3Foa2lHOXcwQkFRc0ZBREJHTVFzd0NRWURWUVFHRXdKVlV6RWlNQ0FHQTFVRUNoTVpSMjl2WjJ4bElGUnlkWE4wSUZObGNuWnBZMlZ6SUV4TVF6RVRNQkVHQTFVRUF4TUtSMVJUSUVOQklERkVOREFlRncweU1URXhNakl3TnpReU16VmFGdzB5TWpBeU1qQXdOelF5TXpSYU1CMHhHekFaQmdOVkJBTVRFbUYwZEdWemRDNWhibVJ5YjJsa0xtTnZiVENDQVNJd0RRWUpLb1pJaHZjTkFRRUJCUUFEZ2dFUEFEQ0NBUW9DZ2dFQkFLRXc5eVlKanRYRU4rZWFQQnk4alVFVVdRR0Riem1ZYTFtWGFFMEVNVmxKTTc3TnRvdXpMOGZ5RVp5dGNuWm1yM08xZ255THR5UUJvU0FWOE5MaTB4bkVXWHh1SEdrZW42bjlrTGRHMTVQOHZEazB4Tm1jTUtFd0d3TnFZRFRxTFA5QmU5c1dkRDFIWk0wekU0QUV2cmhDSVZGeXRzTzI2Wm0yNjZsZzdi.... 
```
- Ответ base64 decode:
```
{"alg":"RS256","x5c":["MIIFXzCCBEegAwI.....118fessmXn1hIVw41oeQa1v1vg4Fv74zPl6/AhSrw9U5pCZEt4Wi4wStz6dTZ/CLANx8LZh1J7QJVj2fhMtfTJr9w4z30Z209fOU0iOMy+qduBmpvvYuR7hZL6Dupszfnw0Skfths18dG9ZKb59UhvmaSGZRVbNQpsg3BZlvid0lIKO2d1xozclOzgjXPYovJJIultzkMu34qQb9Sz/yilrbCgj8="]}{"nonce":"VlRBUE5VQVRRS1M1MVpic2xTS2xid0FoNVRfeXlRQXdBQUFCZm8wbG1pMD0=","timestampMs":1643045559723,"ctsProfileMatch":false,"apkCertificateDigestSha256":[],"basicIntegrity":false,"advice":"RESTORE_TO_FACTORY_ROM","evaluationType":"BASIC"}�zC5{	kz^C?5R
qЗa$&jREBԋnbLUQ(~I֎GMU]1QbӮTVҺQΨ
ɠ0�OdlCY?Dq,vlh}M_{E3jO\9@ufPb2.֠{gEʹo~-BXyr6Th-RTyvV<p`MO8
h�

```
В x5c - сертификат Google

## Аттестация (Шаг 4)
Запрос
```
POST /v1.1/attestation/android/verify?encoding=json HTTP/1.1
Authorization: Bearer a1i02--WtIzmmF-k3EyspitBFipoEpt-SIfCg4bhpyiZb8zB05knC0IDS6CimQsL
Content-Type: application/json; charset=UTF-8
Content-Length: 8166
Host: device-safety-noneu.truecaller.com
Connection: close
Accept-Encoding: gzip, deflate
User-Agent: Truecaller/12.8.6 (Android;6.0)

{"statement":"eyJhbGciOiJSUzI1NiIsIng1YyI6WyJNSUlGWHpDQ0JFZWdBd0lCQWdJUUJRc1UvdmFzWlpZS0FBQUFBU1NvQlRBTkJna3Foa2lHOXcwQkFRc0ZBREJHTVFzd0NR......UB1AmaDuVCNgGL51PXRMqgu1qB7rs5n5UX1AZvKue4Pb36eLQ6I1_eoQlh5cpmE4jb0VGgtjo6jUuW2VHnJdqbcD-8HAYUVVoHaPLvxcGCUmPZN9ddPOKYKaP4A"}
```
- statement- ответ сервера предыдущий запрос


Ответ:
```
HTTP/1.1 200 OK
vary: Accept-Encoding
date: Mon, 24 Jan 2022 17:32:40 GMT
content-type: application/json
x-envoy-upstream-service-time: 4
server: istio-envoy
Via: 1.1 google
Alt-Svc: h3=":443"; ma=2592000,h3-29=":443"; ma=2592000
Connection: close
Content-Length: 13

{"ttl":86400}
```

Если ttl вернулся, значит прошли аттестацию.

## Привязка firebase токена к truecaller токен
Запрос
```
PUT /v0/subscription?encoding=json HTTP/1.1
Authorization: Bearer a1i02--WtIzmmF-k3EyspitBFipoEpt-SIfCg4bhpyiZb8zB05knC0IDS6CimQsL
Content-Type: application/json; charset=UTF-8
Content-Length: 188
Host: pushid-noneu.truecaller.com
Connection: close
Accept-Encoding: gzip, deflate
User-Agent: Truecaller/12.8.6 (Android;6.0)

{"provider":1,"token":"eMPq4cMzQ-u-_TMTqGQF6a:APA91bHsD-uth6hwPFphfL3vXBtZnbrvUwciA7uFO8IetBp2JMfAmFrtrwFyPlvSpxqAhvKc7G_wKn5w0qeGOcFX0fLM6BA5Icu1wyx6TBHynKZzXfYtk9goJQQjrHfzOMuMnuHUOJ_d"}
```

token - "eMPq4cMzQ-u-_TMTqGQF6a" - fid, android.clients.google.com/c2dm/register3 - токен https://github.com/nborrmann/gcmreverse