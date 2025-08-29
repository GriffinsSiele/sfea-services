# Proxy Service

Сервис упрощает работу с множеством прокси сервисов.

При использовании **необходимо отключать** проверку подлинности TLS сертификатов.

Базовое использование, в этом случае выбор прокси не определен и осуществляется в произвольном порядке.
```bash
curl -k https://2ip.ru \
  --proxy socks5://proxy20-master.isphere-go-modules.svc.cluster.local
```

### Выбор региона
Чтобы выбрать прокси из нужного региона, необходимо установить заголовок `X-Sphere-Country`:
```bash 
curl -k https://2ip.ru \
  -H "X-Sphere-Country: ru" \
  --proxy socks5://proxy20-master.isphere-go-modules.svc.cluster.local  
```

### Выбор категории прокси
Также можно указать категорию прокси. Для этого нужно установить заголовок `X-Sphere-ProxyGroup`:
```bash 
curl -k https://2ip.ru \
  -H "X-Sphere-ProxyGroup: 5" \
  --proxy socks5://proxy20-master.isphere-go-modules.svc.cluster.local
``` 

### Выбор определенного прокси
Если нужно указать явно идентификатор прокси, то это осуществляется используя заголовок `X-Sphere-Proxy-Id`:
```bash 
curl -k https://2ip.ru \
  -H "X-Sphere-Proxy-Id: 10" \
  --proxy socks5://proxy20-master.isphere-go-modules.svc.cluster.local
```

Возможна комбинация заголовков, например выбрать определенный регион из категории прокси:
```bash 
curl -k https://2ip.ru \
  -H "X-Sphere-Country: de" \
  -H "X-Sphere-ProxyGroup: 3" \
  --proxy socks5://proxy20-master.isphere-go-modules.svc.cluster.local
```

В ответе помимо стандартного набора заголовков присутствует информация от прокси сервиса. 
Особый интерес представляют следующие заголовки:

```bash 
HTTP/1.1 200 OK
Vary: X-Sphere-Country
X-Sphere-Proxy-Id: 1008
X-Sphere-Svc: hx="https";
X-Request-Id: 6bbfb01b-ab48-46d4-8926-53d12589727c
```

В заголовок `Vary` добавляются, в том числе заголовки группы `X-Sphere-*` которые участвовали в выборе конкретного прокси.
В заголовке `X-Sphere-Proxy-Id` содержится информация о том, какой конкретно прокси был использован в успешном запросе.
