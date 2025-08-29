#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PIDcaptchabad.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECKcaptchabad.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLEcaptchabad.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECKcaptchabad.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLEcaptchabad.txt
	            echo '0' > /var/www/html/daemon/CHECKcaptchabad.txt
	            echo $$ > /var/www/html/daemon/PIDcaptchabad.txt
	            while :
	            do
	                  /var/www/html/daemon/daemoncaptchabad.php 
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLEcaptchabad.txt
	    echo '0' > /var/www/html/daemon/CHECKcaptchabad.txt
	    echo $$ > /var/www/html/daemon/PIDcaptchabad.txt
	    while :
	    do
	          /var/www/html/daemon/daemoncaptchabad.php 
	    done
     fi
else
echo $$ > /var/www/html/daemon/PIDcaptchabad.txt
echo '0' > /var/www/html/daemon/CYCLEcaptchabad.txt
echo '0' > /var/www/html/daemon/CHECKcaptchabad.txt
while :
do
    /var/www/html/daemon/daemoncaptchabad.php
done
fi
	
