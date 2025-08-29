#!/bin/bash

curl -k -i \
  --proxy socks5://localhost \
  -H "X-Sphere-Impersonate-Id: ff91esr" \
  https://infosfera.ru/.well-known/connection
