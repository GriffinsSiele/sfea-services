---
title: Правила создания репозитория в Gitea
description: Правила создания репозитория в Gitea
published: true
date: 2024-02-26T14:09:11.576Z
tags: 
editor: markdown
dateCreated: 2024-02-26T12:42:58.395Z
---

# Правила создания репозитория в Gitea


## Создание репозитория

На странице организации по кнопке "New Repository" можно создать репозиторий.

- Owner: организация, которой будет принадлежать репо
- Repository Name:  указать название репозитория. Поддерживаемые символы: `[a-z-_]`, snake-case. Рекомендуется указывать короткое осмысленное название. Чаще всего имя источника или сервиса.
- **Visibility: private**
- Description: заполняем описание, для чего нужен данный репозиторий. Например, что это источник и как обрабатывает данные или что-то иное указывающее обоснование существования данного репо.
- Template: шаблонный репо, опционально. Для python существуют примеры
- **Default Branch: master**

![org-create-7.png](/rules/develpoment/org-create-7.png)

## Редактирование репозитория

После создания репозитория необходимо донастроить информацию о нем. Переходим на страницу `$GIT_DOMAIN/$ORG_NAME/$REPO_NAME/settings`, где `$ORG_NAME` - название организации, указанной в начале как Owner, `$REPO_NAME` - название репо.



- Visibility: проверяем, что private
- Website: ссылка (если есть) имеющая связь с репо.
> Avatar: обязательно указать картинку. Для быстрого ориентирования среди всех репо. Форматы: png, jpg поддерживаются gitea, желательно material design). Если нет идей, то искать в гугле по запросу `$ORG_NAME type:png`
{.is-info}

![org-create-8.png](/rules/develpoment/org-create-8.png)




- Ниже нужно прожать на чекбокс и выбрать: `Delete pull request branch after merge by default` для автоудаления ненужных веток. 

> Не забываем после редактирования каждого раздела нажать на `Update settings`!
{.is-warning}


## Защита веток

Для запрета пуша в мастер и связанных вещей переходим на страницу `$GIT_DOMAIN/$ORG_NAME/$REPO_NAME/settings/branches`, где `$ORG_NAME` - название организации, указанной в начале как Owner, `$REPO_NAME` - название репо.

В пункте `Default Branch` проверяем, что стоит `master`. 

Создаем локально или через веб-интерфейс ветку `dev` и пушим ее. 

Далее создаем 2 одинаковых правила для веток `dev`, `master` по очередно:

- **Protected Branch Name Pattern: `master` (или `dev`)**
- **Push: Whitelist Restricted Push**
   - **Whitelisted users for pushing: drone-bot** - разрешаем только боту ci/cd пушить
- Required approvals: 1
- Block merge on rejected reviews: чекбокс да
- Block merge if pull request is outdated: чекбокс да

![org-create-9.png](/rules/develpoment/org-create-9.png)

![org-create-10.png](/rules/develpoment/org-create-10.png)

## Включение drone

Переходим на страницу Drone для включения CI/CD проекта. 

Нажимаем в верхней правой части кнопку "Sync" и дожидаемся появления новых репо. Переходим на страницу репозитория и нажимаем "Activate Resitory".

![org-create-11.png](/rules/develpoment/org-create-11.png)

После этого проверяем, что стоит Project Visibility: Private. 

![org-create-12.png](/rules/develpoment/org-create-12.png)

## Sentry (опционально)

Создать Sentry для проекта. Название проекта в Sentry - существенная часть репо или полное имя репо. Например: `ok-mobile-api` -> `ok-mobile` в разделе `workers` или `captcha-image-server` -> `captcha-image-server` в разделе `services`. 

- `workers` - обработчики данных, выполняют поиск по телефону, почте и прочем
- `services` - вспомогательные или внутренние сервисы
- `infrastructure` - инфраструктурые вещи: k8s, БД и прочее. 

## Redmine (опционально) 

Для источников на странице `Administration -> Custom Fields -> Источники` добавить новое поле источника. 
