---
title: Активация сессий
description: 
published: true
date: 2023-03-13T17:14:23.111Z
tags: 
editor: markdown
dateCreated: 2023-03-13T17:04:36.739Z
---

# Активация сессий

Если слать много запросов с одной учетки или зарегистрировать новую сессию в автоматическом режиме, то на следующий день в 03:00 AM аккаунт будет временно заморожен. Требуется решение Google Recaptcha. К сожалению, используются каптчи от [SafetyNet](https://developer.android.com/training/safetynet/recaptcha), решение которых нельзя автоматизировать при помощи сервисов. 

![truecaller_1.jpg](/sources/truecaller/truecaller-mobile-api/resources/truecaller_1.jpg)

### Вариант полуавтоматической активации

Требования:
- Android устройство с приложением Truecaller (тестировалось на версии 12.8.6)
- Выполненная авторизация в приложении Truecaller (или заблокированная сессий). Не чистая установка!
- Root, adb

1. Создаем файл на ПК:
```xml
<?xml version='1.0' encoding='utf-8' standalone='yes' ?>
<map>
    <boolean name="featureRegion1" value="false" />
    <long name="key_region_1_timestamp" value="1676816355865" />
    <string name="networkDomain">noneu</string>
    <string name="profileNumber"></string>
    <long name="installationIdFetchTime" value="1676816350964" />
    <string name="auth_token_cross_domain"></string>
    <string name="profileCountryIso">RU</string>
    <long name="xd_t_f_t" value="1677001676057" />
    <string name="installationId">a1i0_--XVlLsJVDkyl6m_WlNL6yiK9l6GHaAnseH54fMI5TIyTMJLFmZ46FBbsDU</string>
    <long name="installationIdTtl" value="259200000" />
    <long name="xd_t_e_t" value="1677044576057" />
    <int name="VERSION_account" value="7" />
</map>
```

2. Меняем installationId на токен сессии, которой нужно активировать.
3. Заливаем его в песочницу приложения, останавливаем и запускаем
```shell
./adb push /home/alien/.config/JetBrains/PyCharm2022.2/scratches/1.xml /data/data/com.truecaller/shared_prefs/account.xml; ./adb shell am force-stop com.truecaller; ./adb shell monkey -p com.truecaller 1
```
Вместо `/home/alien/.config/JetBrains/PyCharm2022.2/scratches/1.xml ` путь до xml выше.  

4. Должно появиться окно с предложением решить капчу
5. Проходим капчу
6. Рекомендую использовать аудио капчу, она решается быстрее. 
6. Токен становится рабочим. 