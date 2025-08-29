---
title: Чек-лист Python разработчика
description: 
published: true
date: 2024-05-15T09:27:51.288Z
tags: 
editor: markdown
dateCreated: 2024-02-20T12:05:05.567Z
---

# Чек-лист Python разработчика

- [ ] Выдан доступ к Gitea
- [ ] Выдан доступ к Grafana
- [ ] Выдан доступ к Glitchtip
- [ ] Выдан доступ к Grafana
- [ ] Установлен Python 3.11
- [ ] Установлен pipenv==2022.1.8
- [ ] Установлен Docker, git
- [ ] Установлен глобально black, mypy, isort
- [ ] Заданы переменные окружения ENV:
  - export `GIT_TOKEN = токен из gitea`
  - export `GIT_USER = логин gitea`
  - export `GIT_HTTP_DOMAIN = gitea-http.gitea.svc.cluster.local`
  - export `GIT_HTTP_URL_WITH_CREDENTIALS = http://${GIT_USER}:${GIT_TOKEN}@gitea-http.gitea.svc.cluster.local` - подставить свои значения

  
 
