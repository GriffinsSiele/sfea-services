#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PIDgetcontact.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECKgetcontact.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLEgetcontact.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECKgetcontact.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLEgetcontact.txt
	            echo '0' > /var/www/html/daemon/CHECKgetcontact.txt
	            echo $$ > /var/www/html/daemon/PIDgetcontact.txt
	            while :
	            do
	                  php -f /var/www/html/daemon/daemongetcontact.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLEgetcontact.txt
	    echo '0' > /var/www/html/daemon/CHECKgetcontact.txt
	    echo $$ > /var/www/html/daemon/PIDgetcontact.txt
	    while :
	    do
	          php -f /var/www/html/daemon/daemongetcontact.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PIDgetcontact.txt
echo '0' > /var/www/html/daemon/CYCLEgetcontact.txt
echo '0' > /var/www/html/daemon/CHECKgetcontact.txt
while :
do
    php -f /var/www/html/daemon/daemongetcontact.php
done
fi
	
