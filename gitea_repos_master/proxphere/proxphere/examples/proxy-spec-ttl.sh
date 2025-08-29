#!/bin/bash

curl -k -i \
  --proxy socks5://localhost \
   -H "X-Sphere-Proxy-Spec-Ttl: 30s" \
  https://infosfera.ru/.well-known/connection
