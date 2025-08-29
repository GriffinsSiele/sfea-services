#!/bin/bash

curl -k -i \
  --proxy socks5://localhost \
  -H "X-Sphere-Proxy-Spec-Id: 1638" \
  https://infosfera.ru/.well-known/connection
