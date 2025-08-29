#!/bin/bash

curl -k -i \
  --proxy socks5://localhost \
  https://infosfera.ru/.well-known/connection
