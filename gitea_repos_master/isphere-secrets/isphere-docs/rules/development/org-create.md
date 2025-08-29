---
title: Правила создания организации в Gitea
description: Правила создания организации в Gitea
published: true
date: 2024-02-26T13:44:24.800Z
tags: 
editor: markdown
dateCreated: 2024-02-26T12:43:24.523Z
---

# Правила создания организации в Gitea

Для создания организации в gitea необходимо обладать правами на создание репозитория в настройках профиля. Данный чекбокс настраивает администратор в момент регистрации пользователя.


## Создание организации

Создание организации происходит по клику на "+" в верхней правой части экрана. 

Далее необходимо:
- Organization name: указать название организации. Поддерживаемые символы: `[a-z-_]`, snake-case. Рекомендуется указывать короткое осмысленное название. Чаще всего имя источника или группы сервисов.
- Visibility: **Видимость - обязательно private**
- Permissins: Последняя галочка - да

Пример:
![org-create-1.png](/rules/develpoment/org-create-1.png)

## Редактирование организации

После создания организации необходимо донастроить информацию о ней. Переходим на страницу `$GIT_DOMAIN/org/$ORG_NAME/settings`, где `$ORG_NAME` - название организации, указанной в начале.

**Данный раздел рекомендуется максимально заполнить для понимания других коллег назначения организации**. 

- Organization name: остается как в начале
- Organization full name: полное название организации на русском языке для удобного поиска в дальнейшем. 
- Contact Email: пропуск
- Description: заполняем описание, для чего нужна данная организация. Например, для группы источников описываем, что это источник и как обрабатывает данные или что-то иное указывающее обоснование существования данной организации.
- Website: ссылка (если есть) имеющая связь с репо. 
- Location: опционально
- Visibility: проверяем, что private
- Permissions: проверяем, что галочка есть
- Maximum Number of Repositories: -1
> Avatar: обязательно указать картинку. Для быстрого ориентирования среди всех репо. Форматы: png, jpg поддерживаются gitea, желательно material design). Если нет идей, то искать в гугле по запросу `$ORG_NAME type:png`
{.is-info}

![org-create-2.png](/rules/develpoment/org-create-2.png)


## Очистка пакетов

В вкладке Packages настроить автоматическую очистку пакетов/образов. 

Рекомендуемые настройки: 
- Docker (Container) - 5 последних
- Pypi (Python пакеты) - 25 последних. 

Ниже примеры:
![org-create-4.png](/rules/develpoment/org-create-4.png)

![org-create-3.png](/rules/develpoment/org-create-3.png)

## Настройка команд (пользователей)


На странице `$GIT_DOMAIN/org/$ORG_NAME/teams` задается доступ к организации сотрудников. 
Необходимо создать все команды по списку ниже с определенным пулом прав. 


### Owners

Description: `Владельцы проекты`. 
Members: `rudakov_an`, `автор организации`


### Bots

Team Name: `Bots`
Description: `Боты для взаимодействия с CI/CD, Packages`
Repository access: `All repositories`
Permission: `General Access`
Allow Access to Repository Sections:

| Unit          | No Access | Read | Write |
|---------------|-----------|------|-------|
| Code          |           |      |   x   |
| Issues        |     x     |      |       |
| Pull Requests |           |      |   x   |
| Releases      |           |      |   x   |
| Wiki          |     x     |      |       |
| Projects      |     x     |      |       |
| Packages      |           |      |   x   |
| Actions       |     x     |      |       |

Members: `drone-bot`, `sonarqube-bot` - основные боты ci/cd



### Leads

Team Name: `Leads`
Description: `Руководство`
Repository access: `All repositories`
Permission: `General Access`
Allow Access to Repository Sections:

| Unit          | No Access | Read | Write |
|---------------|-----------|------|-------|
| Code          |           |   x  |       |
| Issues        |           |      |   x   |
| Pull Requests |           |   x  |       |
| Releases      |           |   x  |       |
| Wiki          |           |      |   x   |
| Projects      |           |   x  |       |
| Packages      |           |   x  |       |
| Actions       |           |   x  |       |

Members: `vinogradov_ay` - руководство для чтения и заведения issue


### MainDevelopers

Team Name: `MainDevelopers`
Description: `Основная команда разработки данного проекта`
Repository access: `All repositories`
Permission: `General Access`
Allow Access to Repository Sections:

| Unit          | No Access | Read | Write |
|---------------|-----------|------|-------|
| Code          |           |      |   x   |
| Issues        |           |      |   x   |
| Pull Requests |           |      |   x   |
| Releases      |           |      |   x   |
| Wiki          |           |      |   x   |
| Projects      |           |      |   x   |
| Packages      |           |      |   x   |
| Actions       |           |      |   x   |

Members: разработчики, которые будут делать коммиты


### ReviewDevelopers

Team Name: `ReviewDevelopers`
Description: `Разработчики, наблюдающие за проектом`
Repository access: `All repositories`
Permission: `General Access`
Allow Access to Repository Sections:

| Unit          | No Access | Read | Write |
|---------------|-----------|------|-------|
| Code          |           |   x  |       |
| Issues        |           |   x  |       |
| Pull Requests |           |   x  |       |
| Releases      |           |   x  |       |
| Wiki          |           |   x  |       |
| Projects      |           |   x  |       |
| Packages      |           |   x  |       |
| Actions       |           |   x  |       |

Members: разработчики, которые чисто посмотреть на код


### Testers

Team Name: `Testers`
Description: `Тестирование/исследование проектов`
Repository access: `All repositories`
Permission: `General Access`
Allow Access to Repository Sections:

| Unit          | No Access | Read | Write |
|---------------|-----------|------|-------|
| Code          |           |   x  |       |
| Issues        |           |      |   x   |
| Pull Requests |           |   x  |       |
| Releases      |           |   x  |       |
| Wiki          |           |   x  |       |
| Projects      |           |   x  |       |
| Packages      |           |   x  |       |
| Actions       |     x     |      |       |

Members: `akhtyamova_eg` - для тестировщиков и прочих людей заводящих issue

Список команд: 
![org-create-6.png](/rules/develpoment/org-create-6.png)

Настройки для команды `Bots`:
![org-create-5.png](/rules/develpoment/org-create-5.png)


