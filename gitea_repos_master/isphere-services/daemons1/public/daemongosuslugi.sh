#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PIDgosuslugi.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECKgosuslugi.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLEgosuslugi.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECKgosuslugi.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLEgosuslugi.txt
	            echo '0' > /var/www/html/daemon/CHECKgosuslugi.txt
	            echo $$ > /var/www/html/daemon/PIDgosuslugi.txt
	            while :
	            do
	                  php -f /var/www/html/daemon/daemongosuslugi.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLEgosuslugi.txt
	    echo '0' > /var/www/html/daemon/CHECKgosuslugi.txt
	    echo $$ > /var/www/html/daemon/PIDgosuslugi.txt
	    while :
	    do
	          php -f /var/www/html/daemon/daemongosuslugi.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PIDgosuslugi.txt
echo '0' > /var/www/html/daemon/CYCLEgosuslugi.txt
echo '0' > /var/www/html/daemon/CHECKgosuslugi.txt
while :
do
    php -f /var/www/html/daemon/daemongosuslugi.php
done
fi
	
