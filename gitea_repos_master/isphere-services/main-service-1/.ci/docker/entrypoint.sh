#!/bin/bash
echo "Creating /opt/xml/ folder"
mkdir -p /opt/xml/
chown www-data:www-data -R /opt/xml/

echo "Creating logs/ folder"
mkdir -p logs/
chown www-data:www-data logs/

echo "Creating logs/pdf/ folder"
mkdir -p logs/pdf/
chown www-data:www-data logs/pdf/

echo "Starting php-fpm..."
php-fpm -R &

echo "Starting nginx..."
nginx -g "daemon off;" &

echo "Wait for stop something service..."
wait -n

echo "Stopping services..."
exit $?
