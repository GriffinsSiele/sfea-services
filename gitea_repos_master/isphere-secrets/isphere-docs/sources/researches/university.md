---
title: Список ЛК вузов как источники данных
description: 
published: true
date: 2023-03-18T14:39:08.387Z
tags: 
editor: markdown
dateCreated: 2023-03-18T14:39:08.387Z
---

# Список ЛК вузов как источники данных

На данной странице описаны источники, которые были одобрены и были(будут) исследованы на предмет подключения к основному сервису поиска информации по телефону, почте или иным полям. Источники - ЛК вузов


## ВШЭ

- Название: ВШЭ
- Статус: Будущие исследования
- Ссылка: [https://point.hse.ru/password_recovery](https://point.hse.ru/password_recovery)
- Дата исследования: 01.01.2023
- Тип поиска: login, search
- Входные данные: почта
- Выходные поля: наличие учетки или ФИО, дата рождения и тп.
- Ограничения: капча Google в случае login, учетка ВШЭ (легко регается) для search

Сценарий:
-  выводит часть телефона по почте. Требуется капча.  

Пример: 
- kovinevmv@gmail.com - есть

![hse_1.png](/sources/researches/resources/hse_1.png)

![hse_2.png](/sources/researches/resources/hse_2.png)

Сценарий:
- На страницах https://bpm.hse.ru/Runtime/Runtime/Form/DR__f__Favorites/, https://bpm.hse.ru/Runtime/Runtime/Form/SA__MyInfo нажать кнопку добавить, есть поиск по телефону, почте. 


## МГУ

- Название: МГУ
- Статус: Будущие исследования
- Ссылка: [https://lk.msu.ru/site/login](https://lk.msu.ru/site/login)
- Дата исследования: 01.01.2023
- Тип поиска: login
- Входные данные: почта
- Выходные поля: наличие учетки
- Ограничения: неизвестно

Сценарий:
-  выводит часть телефона по почте. Требуется капча.  

Пример: 
- kovinevmv@gmail.com - нет
- нужны примеры где есть

![msu_1.png](/sources/researches/resources/msu_1.png)

## ИТМО
ИТМО: https://id.itmo.ru/contacts# - при смене телефона предлагает позвонить на номер или выводит что телефон занят


## ЛЭТИ

ЛЭТИ: Регистрируемся через https://priem.etu.ru/#/, попадаем в систему https://digital.etu.ru/science/authors, ищем по почте или сгружаем весь список для поиска по телефону. Дополнительный поиск: https://lk.etu.ru/api/contact-list/contact-info/318068

## СПбГУ

СПбГУ: http://cabinet.spbu.ru/Account/Login - вход по почте (Неверный пароль или Неудачная попытка входа)