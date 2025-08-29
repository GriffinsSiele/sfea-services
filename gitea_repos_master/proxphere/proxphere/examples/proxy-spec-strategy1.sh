#!/bin/bash

curl -k -i \
  --proxy socks5://localhost \
  -H "X-Sphere-Proxy-Spec-Country-Code: de" \
  -H "X-Sphere-Proxy-Spec-Strategy: random(10)" \
  https://infosfera.ru/.well-known/connection
