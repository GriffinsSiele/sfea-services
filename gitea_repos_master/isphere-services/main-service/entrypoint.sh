#!/bin/bash

php-fpm -R &

nginx -g "daemon off;" &

wait -n
exit $?
