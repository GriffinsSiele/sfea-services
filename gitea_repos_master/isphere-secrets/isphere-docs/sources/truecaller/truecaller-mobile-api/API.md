---
title: Описание API
description: 
published: true
date: 2023-03-13T15:58:32.162Z
tags: 
editor: markdown
dateCreated: 2023-03-13T14:23:14.523Z
---

# Описание API

- Протокол: HTTP/1.1
- Классическое REST API с использованием json

## Общие механизмы защиты от ботов
- Наличие авторизации: да
- Наличие fingerprint браузера: нет
- Proxy: нет
- TLS: нет
- JA3 Proxy: нет

## Поиск по номеру телефона (Search тип)

Данный запрос позволяет по входному номеру телефона определитель профиль человека.

Request:
```json
{
   "method":"GET",
   "url":"https://search5-noneu.truecaller.com/v2/search",
   "json":"None",
   "headers":{
      "Content-Type":"application/json; charset=UTF-8",
      "Host":"search5-noneu.truecaller.com",
      "Connection":"close",
      "Accept-Encoding":"gzip, deflate",
      "clientSecret":"lvc22mp3l1sfv6ujg83rd17btt",
      "User-Agent":"Truecaller/12.8.6 (Android;6.0)",
      "Authorization":"Bearer a1i0f--X12k6pFv-_cn5dGkveD98hz0CTYxZXHZiav6DhTCOIKfYbrt826DGlSBY",
      "accept-encoding":"gzip, deflate",
      "connection":"keep-alive"
   },
   "proxy":"None",
   "params":{
      "q":"77786401814",
      "countryCode":"RU",
      "encoding":"json",
      "type":"4",
      "locAddr":"",
      "placement":"SEARCHRESULTS,HISTORY,DETAILS"
   },
   "cookies":"None",
   "allow_redirects":true,
   "ssl":false,
   "timeout":3
}
```

Описание параметров:
- `clientSecret` - константа, захардкожена в исходном коде приложения
- `Bearer a1i0f--X12k6pFv-_cn5dGkveD98hz0CTYxZXHZiav6DhTCOIKfYbrt826DGlSBY` - токен пользователя (сессия)
- `placement` - activity приложения, где происходит поиск

Response:
```json
{
   "data":[
      {
         "id":"rm198GwMX85S0n36LZl65g==",
         "name":"John Maximov",
         "birthday":"2002-01-22",
         "imId":"1iq4lr1582qrk",
         "gender":"MALE",
         "about":"54646",
         "image":"https://storage.googleapis.com/tc-images-noneu/myview/1/73ba398ff734e84c1c95588100d00eb5/1",
         "jobTitle":"2",
         "score":0.9,
         "access":"PUBLIC",
         "enhanced":true,
         "companyName":"1",
         "phones":[
            {
               "e164Format":"+77786401814",
               "numberType":"MOBILE",
               "nationalFormat":"8 (778) 640 1814",
               "dialingCode":7,
               "countryCode":"KZ",
               "carrier":"Kcell",
               "type":"openPhone"
            }
         ],
         "addresses":[
            {
               "address":"Jump, 112313, KZ",
               "street":"Jump",
               "zipCode":"112313",
               "countryCode":"KZ",
               "type":"address"
            }
         ],
         "internetAddresses":[
            {
               "id":"fsdgdsfgdfg@gsdf.cob",
               "service":"email",
               "caption":"John Maximov",
               "type":"internetAddress"
            },
            {
               "id":"https://vk.com",
               "service":"link",
               "type":"internetAddress"
            }
         ],
         "badges":[
            "user"
         ],
         "tags":[
            
         ],
         "cacheTtl":1296000000,
         "sources":[
            
         ],
         "searchWarnings":[
            
         ],
         "surveys":[
            {
               "id":"0905a705-3c8c-48ab-a4a6-001ce0b20566",
               "frequency":86400,
               "passthroughData":"eyAiNCI6ICJwZiIsICIyIjogIkpvaG4gTWF4aW1vdiIsICIzIjogIjc3Nzg2NDAxODE0IiB9",
               "perNumberCooldown":31536000
            },
            {
               "id":"4eb9afcf-f36e-430d-86f9-4f72ca091f91",
               "frequency":86400,
               "passthroughData":"eyAiNCI6ICJwZiIsICIyIjogIkpvaG4gTWF4aW1vdiIsICIzIjogIjc3Nzg2NDAxODE0IiB9",
               "perNumberCooldown":31536000
            }
         ],
         "commentsStats":{
            "showComments":false
         },
         "ns":0
      }
   ],
   "provider":"ss-nu",
   "stats":{
      "sourceStats":[
         
      ]
   }
}
```
| Название поля              	| Описание                                                                                                                                                                                                                                                                                                                                                                               	| Использование                                   	|
|----------------------------	|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------	|-------------------------------------------------	|
|  id                        	| id номера в системе Truecaller, назначается всем номерам, в виде хеша                                                                                                                                                                                                                                                                                                                  	| Игнорируется                                    	|
| name                       	| Имя пользователя, чаще всего ФИО или иные варианты                                                                                                                                                                                                                                                                                                                                     	| Имя                                             	|
| birthday                   	| Дата рождения пользователя                                                                                                                                                                                                                                                                                                                                                             	| Дата рождения                                   	|
| imId                       	| id пользователя в системе. Если не пусто, значит пользователь зарегистрирован в системе.                                                                                                                                                                                                                                                                                               	| Пользователь зарегистрирован в Truecaller       	|
| gender                     	| Пол пользователя: MALE \| FEMALE                                                                                                                                                                                                                                                                                                                                                       	| Пол                                             	|
| about                      	| Информация о себе                                                                                                                                                                                                                                                                                                                                                                      	| О себе                                          	|
| image                      	| Аватар пользователя. Ссылка на storage.googleapis.com                                                                                                                                                                                                                                                                                                                                  	| Аватар                                          	|
| jobTitle                   	| Должность пользователя на работе                                                                                                                                                                                                                                                                                                                                                       	| Должность                                       	|
| score                      	| Рейтинг доверия, число диапазон 0 - 1                                                                                                                                                                                                                                                                                                                                                  	| Спам-рейтинг                                    	|
| access                     	| Видимость профиля                                                                                                                                                                                                                                                                                                                                                                      	| Игнорируется                                    	|
| enhanced                   	| ???                                                                                                                                                                                                                                                                                                                                                                                    	| Игнорируется                                    	|
| companyName                	| Место работы                                                                                                                                                                                                                                                                                                                                                                           	| Компания                                        	|
| phones.numberType          	| "FIXED_LINE": "Фиксированная линия"<br>"MOBILE": "Мобильный"<br>"FIXED_LINE_OR_MOBILE": "Фиксированная линия или мобильный"<br>"TOLL_FREE": "Бесплатная (горячая) линия"<br>"PREMIUM_RATE": "Аудиотекс"<br>"SHARED_COST": "Телефон с раздельной платой"<br>"VOIP": "VOIP"<br>"PERSONAL_NUMBER": "Личный номер"<br>"PAGER": "Пейджер"<br>"UAN": "UAN"<br>"VOICEMAIL": "Голосовая почта" 	| Тип телефона                                    	|
| phones.carrier             	| Оператор связи                                                                                                                                                                                                                                                                                                                                                                         	| Оператор                                        	|
| phones.countryCode         	| Код страны оператора                                                                                                                                                                                                                                                                                                                                                                   	| Код страны                                      	|
| addresses                  	| Адрес указанный в профиле                                                                                                                                                                                                                                                                                                                                                              	| Адрес                                           	|
| internetAddresses          	| Тип указан в service: email \| link \| facebook \| twitter. Значение в поле id.                                                                                                                                                                                                                                                                                                        	| Почта \| Ссылка \| Facebook \| Twitter          	|
| badges                     	| Тип записи                                                                                                                                                                                                                                                                                                                                                                             	| Тип записи                                      	|
| tags                       	| Массив индексов из локальных БД тегов (1200+). Полный список представлен в исходниках                                                                                                                                                                                                                                                                                                  	| Теги                                            	|
| cacheTtl                   	| Актуальность данных                                                                                                                                                                                                                                                                                                                                                                    	| Игнорируется                                    	|
| sources                    	| ???                                                                                                                                                                                                                                                                                                                                                                                    	| Игнорируется                                    	|
| searchWarnings             	| Автоматическая рекомендация категории звонка (возможно полезная организация, банк или тп.)                                                                                                                                                                                                                                                                                             	| Возможная категория                             	|
| surveys                    	| ???                                                                                                                                                                                                                                                                                                                                                                                    	| Игнорируется                                    	|
| commentsStats.showComments 	| Наличие комментариев                                                                                                                                                                                                                                                                                                                                                                   	| Возможность написания комментариев под профилем 	|
| commentsStats.count        	| Количество комментариев                                                                                                                                                                                                                                                                                                                                                                	| Количество комментариев                         	|


## Поиск по номеру телефона (Bulk тип)

Данный запрос позволяет по входному номеру телефона определитель профиль человека. Отличие данного запроса от search, что он поддерживает несколько параметров q в query params запроса, тем самым позволяет пробивать несколько номеров за 1 запрос.

Request:
```json
{
   "method":"GET",
   "url":"https://search5-noneu.truecaller.com/v2/bulk",
   "json":"None",
   "headers":{
      "Content-Type":"application/json; charset=UTF-8",
      "Host":"search5-noneu.truecaller.com",
      "Connection":"close",
      "Accept-Encoding":"gzip, deflate",
      "clientSecret":"lvc22mp3l1sfv6ujg83rd17btt",
      "User-Agent":"Truecaller/12.8.6 (Android;6.0)",
      "Authorization":"Bearer a1i0Z--XS9FMBkyV4icOewYywSrfB5cIVXu_y7UlEWBOoATRgf6iNm8Qt6EyAT9-",
      "accept-encoding":"gzip, deflate",
      "connection":"keep-alive"
   },
   "proxy":"None",
   "params":{
      "q":"79208313100",
      "countryCode":"RU",
      "type":"14"
   },
   "cookies":"None",
   "allow_redirects":true,
   "ssl":false,
   "timeout":3
}
```
Response
```
{
   "data":[
      {
         "key":"79208313100",
         "value":{
            "id":"0TAOWUP3Zzfxqj/sEnMBQA==",
            "access":"PUBLIC",
            "enhanced":true,
            ...
         }
      }
   ],
   "provider":"ss-uni-leg-bulk",
   "stats":{
      "sourceStats":[]
   }
}
```

В отличие от search запроса в data хранится список словарей вида {key: PHONE, value: RESPONSE}. 
Набор полей RESPONSE аналогичный ответу выше. 

## Начало регистрации

Рекомендуется использовать прокси для данного запроса. 

Request:
```json
{
   "method":"POST",
   "url":"https://account-asia-south1.truecaller.com/v2/sendOnboardingOtp",
   "json":{
      "countryCode":"ru",
      "dialingCode":7,
      "installationDetails":{
         "app":{
            "buildVersion":6,
            "majorVersion":12,
            "minorVersion":8,
            "store":"GOOGLE_PLAY"
         },
         "device":{
            "deviceId":"0d974336bd5329e9",
            "language":"en",
            "manufacturer":"Google",
            "mobileServices":[
               "GMS"
            ],
            "model":"Pixel 4XL",
            "osName":"Android",
            "osVersion":"6.0"
         },
         "language":"en",
         "storeVersion":{
            "buildVersion":6,
            "majorVersion":12,
            "minorVersion":8
         }
      },
      "sims":[
         
      ],
      "phoneNumber":"9060362378",
      "region":"region-2",
      "sequenceNo":1
   },
   "headers":{
      "Content-Type":"application/json; charset=UTF-8",
      "Host":"account-asia-south1.truecaller.com",
      "Connection":"close",
      "Accept-Encoding":"gzip, deflate",
      "clientSecret":"lvc22mp3l1sfv6ujg83rd17btt",
      "User-Agent":"Truecaller/12.8.6 (Android;6.0)",
      "accept-encoding":"gzip, deflate",
      "connection":"keep-alive"
   },
   "proxy":"http://3NcKoL:CvxsVs@194.124.50.3:8000",
   "params":"None",
   "cookies":"None",
   "allow_redirects":true,
   "ssl":false,
   "timeout":3
}
```

| Название поле                       	| Описание                                   	|
|-------------------------------------	|--------------------------------------------	|
| installationDetails.app             	| Версия API приложения                      	|
| installationDetails.device.deviceId 	| Произвольные 16 hex-смиволов               	|
| installationDetails.storeVersion    	| Аналогично installationDetails.app         	|
| sequenceNo                          	| Номер в порядке регистрации, должна инкрементироваться 	|
| headers.User-Agent                  	| Версия должна совпадать с указанной выше   	|

Response:
```json
{
   "status":1,
   "message":"Sent",
   "domain":"noneu",
   "parsedPhoneNumber":79060362378,
   "parsedCountryCode":"RU",
   "requestId":"7cf30ae5-da9d-4d96-a024-5f181d15b0a5",
   "method":"call",
   "tokenTtl":25,
   "pattern":"1,1,7,2,3,6"
}
```

Если в ответе `"method": "call"`, значит будет произведена попытка дозвона до указанного номера телефона в течении `tokenTtl` секунду. Рекомендуется послать запрос снова, с `sequenceNo + 1` через `tokenTtl + 10` секунд с момента прыдущего запроса для переключения режима подтверждения на SMS. 

Response:
```json
{
   "status":1,
   "message":"Sent",
   "domain":"noneu",
   "parsedPhoneNumber":79060362378,
   "parsedCountryCode":"RU",
   "requestId":"7cf30ae5-da9d-4d96-a024-5f181d15b0a5",
   "method":"sms",
   "tokenTtl":300
}
```

Если в ответе `"method": "sms"`, значит будет произведена попытка отправки SMS на указанный номера телефона в течении `tokenTtl` секунду. Для подтверждения кода используйте запрос ниже, указав `requestId` как один из параметров запроса.

## Проверка кода

Рекомендуется использовать прокси для данного запроса. 


Request:
```json
{
   "method":"POST",
   "url":"https://account-asia-south1.truecaller.com/v1/verifyOnboardingOtp",
   "json":{
      "countryCode":"ru",
      "dialingCode":7,
      "phoneNumber":"9060362378",
      "requestId":"7cf30ae5-da9d-4d96-a024-5f181d15b0a5",
      "token":"171187"
   },
   "headers":{
      "Content-Type":"application/json; charset=UTF-8",
      "Host":"account-asia-south1.truecaller.com",
      "Connection":"close",
      "Accept-Encoding":"gzip, deflate",
      "clientSecret":"lvc22mp3l1sfv6ujg83rd17btt",
      "User-Agent":"Truecaller/12.8.6 (Android;6.0)",
      "accept-encoding":"gzip, deflate",
      "connection":"keep-alive"
   },
   "proxy":"http://3NcKoL:CvxsVs@194.124.50.3:8000",
   "params":"None",
   "cookies":"None",
   "allow_redirects":true,
   "ssl":false,
   "timeout":3
}
```


| Название поле                    	| Описание                                    	|
|----------------------------------	|---------------------------------------------	|
| requestId                        	| Идентификатор запроса из /sendOnboardingOtp 	|
| token                            	| Код из SMS                                  	|

Response:
```json
{
   "status":2,
   "message":"Verified",
   "installationId":"a1i0v--eBvfyGkxkW4A1ZaT6ecYp3n25UBd_fiOjfwPb8bUx-gbiECF756ixGBaJ",
   "ttl":259200,
   "userId":11878827244851056,
   "suspended":false,
   "phones":[
      {
         "phoneNumber":79060362378,
         "countryCode":"RU",
         "priority":1
      }
   ]
}
```


| Название поле                    	| Описание                                   	|
|----------------------------------	|--------------------------------------------	|
| installationId                   	| Основной токен сессии                      	|


## Наполнение профиля

Рекомендуется использовать прокси для данного запроса. 


```json
{
   "method":"POST",
   "url":"https://profile4-noneu.truecaller.com/v4/profile?encoding=json",
   "json":{
      "firstName":"Mayworm",
      "lastName":"Morales",
      "personalData":{
         "about":"",
         "address":{
            "city":"",
            "country":"RU",
            "street":"",
            "zipCode":""
         },
         "avatarUrl":"",
         "companyName":"",
         "gender":"N",
         "isCredUser":false,
         "jobTitle":"",
         "onlineIds":{
            "email":"ZDsgMHucWpOtpWdUnKlQPYmR@yahoo.com",
            "facebookId":"",
            "googleIdToken":"",
            "twitterId":"",
            "url":""
         },
         "phoneNumbers":[
            79060362378
         ],
         "privacy":"Private",
         "tags":[
            
         ]
      }
   },
   "headers":{
      "Content-Type":"application/json; charset=UTF-8",
      "Host":"profile4-noneu.truecaller.com",
      "Connection":"close",
      "Accept-Encoding":"gzip, deflate",
      "clientSecret":"lvc22mp3l1sfv6ujg83rd17btt",
      "User-Agent":"Truecaller/12.8.6 (Android;6.0)",
      "Authorization":"Bearer a1i0v--eBvfyGkxkW4A1ZaT6ecYp3n25UBd_fiOjfwPb8bUx-gbiECF756ixGBaJ",
      "accept-encoding":"gzip, deflate",
      "connection":"keep-alive"
   },
   "proxy":"http://3NcKoL:CvxsVs@194.124.50.3:8000",
   "params":{
      "encoding":"json"
   },
   "cookies":"None",
   "allow_redirects":true,
   "ssl":false,
   "timeout":3
}
```

Данный запрос опционален после момента регистрации, рекомендуется частично наполнить профиль полями. Обязательно используйте рандомное поле firstName, lastName, onlineIds.email. Подтверждения на email не высылается. 

Response
```json
```