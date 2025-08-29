#!/bin/bash

curl -k -i \
  --proxy socks5://localhost \
  -H "X-Sphere-Proxy-Spec-Country-Code: de" \
  https://infosfera.ru/.well-known/connection
