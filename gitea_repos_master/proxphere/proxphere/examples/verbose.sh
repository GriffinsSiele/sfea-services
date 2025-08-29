#!/bin/bash

curl -k -i \
  --proxy socks5://localhost \
  -H "X-Sphere-Verbose: true" \
  https://infosfera.ru/.well-known/connection
