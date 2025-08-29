#!/bin/bash

curl -k -i \
  --proxy socks5://localhost \
  -H "X-Sphere-Proxy-Spec-Group-Id: 5" \
  https://infosfera.ru/.well-known/connection
