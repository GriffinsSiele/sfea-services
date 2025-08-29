# HealthCheck Tester

Сервис осуществляет заданные проверки и возвращает их результат.

Проверки делятся на два типа:
1. Проверка осуществляемая с одной мастер ноды. Подразумевается, что всегда запущена одна реплика. 
2. Проверка осуществляемая со всех нод, на которых запущены реплики тестировщика.

Код и конфигурация проверок располагаются в папке `internal/healthcheck`.

Метрики можно получить в следующих форматах.


## Prometheus

```bash
$ http://healthcheck-master.isphere-services.svc.cluster.local:8000/metrics
```

```bash
# HELP healthcheck_coordinator_through_http_http_request 
# TYPE healthcheck_coordinator_through_http_http_request gauge
healthcheck_coordinator_through_http_http_request{hostname="healthcheck-master-d944b657f-tt9lp",node_name="rnd-worker-03",subject="http://coordinator-master-http-serve.isphere-go-modules.svc.cluster.local"} 4.79469387e+08
# HELP healthcheck_coordinator_through_main_service_http_request 
# TYPE healthcheck_coordinator_through_main_service_http_request gauge
healthcheck_coordinator_through_main_service_http_request{hostname="healthcheck-master-d944b657f-tt9lp",node_name="rnd-worker-03",subject="https://i-sphere.ru/2.00"} 1.685973492e+09
# HELP healthcheck_coordinator_through_rabbitmq_and_keydb_keydb_connection 
# TYPE healthcheck_coordinator_through_rabbitmq_and_keydb_keydb_connection gauge
healthcheck_coordinator_through_rabbitmq_and_keydb_keydb_connection{hostname="healthcheck-master-d944b657f-tt9lp",node_name="rnd-worker-03",subject="keydb.keydb.svc.cluster.local:6379"} 2.03106293e+08
# HELP healthcheck_coordinator_through_rabbitmq_and_keydb_keydb_exists 
# TYPE healthcheck_coordinator_through_rabbitmq_and_keydb_keydb_exists gauge
healthcheck_coordinator_through_rabbitmq_and_keydb_keydb_exists{hostname="healthcheck-master-d944b657f-tt9lp",node_name="rnd-worker-03",subject="keydb.keydb.svc.cluster.local:6379"} 736274
```

Значением метрики является таймаут времени выполнения.

### YAML 

Содержит в себе отладочную информацию

```bash
$ http://healthcheck-master.isphere-services.svc.cluster.local:8000/metrics \
  -H "Accept: application/yaml" 
```

```yaml
- id: f4482a5e-e232-41c8-b6e6-77987acbef38
  name: coordinator-through-http
  node_name: rnd-worker-03
  hostname: healthcheck-master-d944b657f-tt9lp
  events:
  - name: http-request
    subject: http://coordinator-master-http-serve.isphere-go-modules.svc.cluster.local
    opaque:
      request:
        body: '{"id":1,"key":"f4482a5e-e232-41c8-b6e6-77987acbef38","ip":"185.158.155.34","starttime":1707726780}'
        headers: '{"Accept":"application/json","Content-Type":"application/json","X-Request-Id":"f4482a5e-e232-41c8-b6e6-77987acbef38"}'
        method: POST
        url: http://coordinator-master-http-serve.isphere-go-modules.svc.cluster.local/api/v1/check-types/geoip
      response:
        body: '{"status":"ok","code":200,"message":"found","records":[{"ip":"185.158.155.34","country_code":"RU","region":"","city":"","location":{"coords":[55.7386,37.6068],"text":"Россия"}}],"timestamp":1707726781,"events":null}'
        headers: '{"Age":"0","Content-Length":"221","Content-Type":"application/json;
          charset=utf-8","Date":"Mon, 12 Feb 2024 08:33:01 GMT","Etag":"f4482a5e-e232-41c8-b6e6-77987acbef38","Expires":"Tue,
          13 Feb 2024 08:33:01 UTC","Last-Modified":"Mon, 12 Feb 2024 08:33:01 UTC","Vary":"X-Request-Id","X-Request-Id":"f4482a5e-e232-41c8-b6e6-77987acbef38"}'
        status_code: 200
    duration: 479.469387ms
    created_at: 2024-02-12T08:33:00.726127913Z
  duration: 479.691456ms
  created_at: 2024-02-12T08:33:00.726066926Z
- id: 2e26e6c7-4d9f-4171-b288-c359c1dce7b0
  name: coordinator-through-main-service
  node_name: rnd-worker-03
  hostname: healthcheck-master-d944b657f-tt9lp
  events:
  - name: http-request
    subject: https://i-sphere.ru/2.00
    opaque:
      request:
        body: <Request><UserIP>127.0.0.1</UserIP><UserID>sk</UserID><Password>********</Password><requestType>checkip</requestType><requestId>2e26e6c7-4d9f-4171-b288-c359c1dce7b0</requestId><sources>geoip</sources><timeout>30</timeout><recursive>0</recursive><async>0</async><IPReq><ip>185.158.155.34</ip></IPReq></Request>
        headers: '{"Accept":"application/xml","Content-Type":"application/xml","X-Request-Id":"2e26e6c7-4d9f-4171-b288-c359c1dce7b0"}'
        method: POST
        url: https://i-sphere.ru/2.00/index_new.php
      response:
        body: |-
          <?xml version="1.0" encoding="utf-8"?>
          <Response id="263928363" status="1" datetime="2024-02-12T11:33:02" result="https://i-sphere.ru/2.00/showresult.php?id=263928363&amp;mode=xml" view="https://i-sphere.ru/2.00/showresult.php?id=263928363">
          <Request><UserIP>127.0.0.1</UserIP><UserID>sk</UserID><requestDateTime>2024-02-12T11:33:01</requestDateTime><requestType>checkip</requestType><requestId>2e26e6c7-4d9f-4171-b288-c359c1dce7b0</requestId><sources>geoip</sources><timeout>30</timeout><recursive>0</recursive><async>0</async><IPReq><ip>185.158.155.34</ip></IPReq></Request>
                      <Source code="geoip" checktype="geoip" start="ip[0]" param="ip[0]" path="ip[0]" level="0" index="0" request_id="2e26e6c7-4d9f-4171-b288-c359c1dce7b0__ip_0__geoip__0" process_time="1.2">
                          <Name>GeoIP</Name>
                          <Title>Определение города/страны по IP</Title>
                          <CheckTitle>Определение города/страны по IP</CheckTitle>
                          <Request>geoip 185.158.155.34</Request>
                          <ResultsCount>1</ResultsCount>
                          <Record>
                              <Field>
                                  <FieldType>string</FieldType>
                                  <FieldName>ip</FieldName>
                                  <FieldTitle>ip</FieldTitle>
                                  <FieldDescription>ip</FieldDescription>
                                  <FieldValue>185.158.155.34</FieldValue>
                              </Field>
                              <Field>
                                  <FieldType>string</FieldType>
                                  <FieldName>country_code</FieldName>
                                  <FieldTitle>Код страны</FieldTitle>
                                  <FieldDescription>Код страны</FieldDescription>
                                  <FieldValue>RU</FieldValue>
                              </Field>
                              <Field>
                                  <FieldType>string</FieldType>
                                  <FieldName>region</FieldName>
                                  <FieldTitle>region</FieldTitle>
                                  <FieldDescription>region</FieldDescription>
                                  <FieldValue></FieldValue>
                              </Field>
                              <Field>
                                  <FieldType>string</FieldType>
                                  <FieldName>city</FieldName>
                                  <FieldTitle>city</FieldTitle>
                                  <FieldDescription>city</FieldDescription>
                                  <FieldValue></FieldValue>
                              </Field>
                              <Field>
                                  <FieldType>map</FieldType>
                                  <FieldName>location</FieldName>
                                  <FieldTitle>Местоположение</FieldTitle>
                                  <FieldDescription>Местоположение</FieldDescription>
                                  <FieldValue>[{"coords":[55.7386,37.6068],"text":"\u0420\u043e\u0441\u0441\u0438\u044f"}]</FieldValue>
                              </Field>
                           </Record>
                      </Source>
          </Response>
        headers: '{"Connection":"keep-alive","Content-Type":"text/html; charset=UTF-8","Date":"Mon,
          12 Feb 2024 08:33:02 GMT","Server":"nginx","X-Request-Id":"2e26e6c7-4d9f-4171-b288-c359c1dce7b0","X-Trusted-Proxy":"proxy-pass/nginx"}'
        status_code: 200
    duration: 1.685973492s
    created_at: 2024-02-12T08:33:00.72621303Z
  duration: 1.686490703s
  created_at: 2024-02-12T08:33:00.726023278Z
```

### JSON

То же самое, что YAML, но в формате JSON

```bash
$ http://healthcheck-master.isphere-services.svc.cluster.local:8000/metrics \
  -H "Accept: application/json" 
```