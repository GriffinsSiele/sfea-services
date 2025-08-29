#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PIDcaptchabad4.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECKcaptchabad4.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLEcaptchabad4.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECKcaptchabad4.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLEcaptchabad4.txt
	            echo '0' > /var/www/html/daemon/CHECKcaptchabad4.txt
	            echo $$ > /var/www/html/daemon/PIDcaptchabad4.txt
	            while :
	            do
	                  /var/www/html/daemon/daemoncaptchabad4.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLEcaptchabad4.txt
	    echo '0' > /var/www/html/daemon/CHECKcaptchabad4.txt
	    echo $$ > /var/www/html/daemon/PIDcaptchabad4.txt
	    while :
	    do
	          /var/www/html/daemon/daemoncaptchabad4.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PIDcaptchabad4.txt
echo '0' > /var/www/html/daemon/CYCLEcaptchabad4.txt
echo '0' > /var/www/html/daemon/CHECKcaptchabad4.txt
while :
do
    /var/www/html/daemon/daemoncaptchabad4.php
done
fi
	
