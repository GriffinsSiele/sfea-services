#!/bin/bash

rm /tmp/cookies.txt 2> /dev/null

curl -k -i \
  -b /tmp/cookies.txt \
  -c /tmp/cookies.txt \
  --proxy socks5://localhost \
  -H "X-Sphere-Proxy-Spec-Country-Code: de" \
  https://infosfera.ru/.well-known/connection

echo ""
echo ""
echo "Cookies:"
cat /tmp/cookies.txt

curl -k -i \
  -b /tmp/cookies.txt \
  -c /tmp/cookies.txt \
  --proxy socks5://localhost \
  -H "X-Sphere-Proxy-Spec-Country-Code: de" \
  https://infosfera.ru/.well-known/connection

rm /tmp/cookies.txt
