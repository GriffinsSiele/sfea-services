---
title: Как блокировать пользователя при увольнении
description: 
published: true
date: 2024-10-12T11:19:53.915Z
tags: 
editor: markdown
dateCreated: 2024-10-12T11:18:39.535Z
---

# Как блокировать пользователя при увольнении

Основные шаги при увольнении: Gitea, т.к. он является SSO для остальных источников.

Дополнительно рекомендуется заблокировать VPN сотрудника. 

## Gitea

- Перейти на страницу редактирования профиля через Панель администратора. Примерная ссылка: http://git.i-sphere.local/admin/users/136/edit
- Проставить флажки: Disable Sign-In (Вкл), User Account Is Activated (Выкл), Is Restricted (Вкл)
------------
- Перейти на страницу пользователя в Gitea. Примерная ссылка: http://git.i-sphere.local/voronckin_ds
- Перейти в каждую организацию, в которой он состоит и во владке Team убрать пользователя. 
------------
- Подключиться к ПРОД БД gitea
- Перейти в таблицу users, проставить в поле информации о себе (должность): `[Fired] ...`

## Grafana

- Перейти на страницу General -> Organizations -> ISphere. (http://grafana.i-sphere.local/admin/orgs/edit/1)
- Нажать на красный крестик напротив никнейма

## Redmine

- Перейти на страницу пользователей Administation -> Users. (http://redmine.i-sphere.local/users)
- Проставить lock напротив никнейма

## Wikijs

- Перейти на страницу пользователей Администрирование -> Пользователи. (http://wiki.i-sphere.local/a/users)
- Найти человека по никнейму и перейти на его страницу
- Нажать на Actions -> Deactivate
- Убрать все "Группы пользователя"
- Нажать "Обновить пользователя"

## Glitchtip

- По каждому проект перейти в Settings -> Teams. Примерная ссылка: http://glitchtip.i-sphere.local/infrastructure/settings/teams
- Найти пользователя по почте и нажать на Remove
