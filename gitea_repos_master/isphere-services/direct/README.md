Сервис решает проблемы сетевого доступа к подам k8s.

## Примеры использования

### Получить список доменов пода

```bash
$ dig @direct-master.isphere-services.svc.cluster.local keydb.keydb.svc.cluster.local PTR

;; ANSWER SECTION:
keydb.keydb.svc.cluster.local.   10 IN PTR  keydb.keydb.pod.gaydary-worker-01.local.
keydb.keydb.svc.cluster.local.   10 IN PTR  keydb.keydb.pod.msk-worker-02.local.
keydb.keydb.svc.cluster.local.   10 IN PTR  keydb.keydb.pod.rnd-worker-01.local.
```

В ответе содержится информация о доменных именах с прямым доступом к подам минуя сервисы.


### Ресолвинг домена

```bash 
$ dig @direct-master.isphere-services.svc.cluster.local keydb.keydb.pod.msk-worker-02.local A

;; ANSWER SECTION:
keydb.keydb.pod.msk-worker-02.local.   10 IN A  10.42.203.190
```

В этом ответе содержится IP адрес пода для прямого доступа.


### Список всех доменов для работы

```bash 
$ curl http://direct-master.isphere-services.svc.cluster.local/.well-known/names
  
...
keydb.keydb.pod.gaydary-worker-01.local.   10 IN A    10.42.149.10
keydb.keydb.pod.msk-worker-02.local.       10 IN A    10.42.203.190
keydb.keydb.pod.rnd-worker-01.local.       10 IN A    10.42.127.189
keydb.keydb.svc.cluster.local.             10 IN A    10.43.53.114
keydb.keydb.svc.cluster.local.             10 IN PTR  keydb.keydb.pod.gaydary-worker-01.local.
keydb.keydb.svc.cluster.local.             10 IN PTR  keydb.keydb.pod.msk-worker-02.local.
keydb.keydb.svc.cluster.local.             10 IN PTR  keydb.keydb.pod.rnd-worker-01.local.
...
```

Тут можно узнать обо всём ресолвинге сервисов и подов в принципе. 