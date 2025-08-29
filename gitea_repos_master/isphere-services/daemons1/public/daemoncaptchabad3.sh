#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PIDcaptchabad3.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECKcaptchabad3.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLEcaptchabad3.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECKcaptchabad3.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLEcaptchabad3.txt
	            echo '0' > /var/www/html/daemon/CHECKcaptchabad3.txt
	            echo $$ > /var/www/html/daemon/PIDcaptchabad3.txt
	            while :
	            do
	                  /var/www/html/daemon/daemoncaptchabad3.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLEcaptchabad3.txt
	    echo '0' > /var/www/html/daemon/CHECKcaptchabad3.txt
	    echo $$ > /var/www/html/daemon/PIDcaptchabad3.txt
	    while :
	    do
	          /var/www/html/daemon/daemoncaptchabad3.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PIDcaptchabad3.txt
echo '0' > /var/www/html/daemon/CYCLEcaptchabad3.txt
echo '0' > /var/www/html/daemon/CHECKcaptchabad3.txt
while :
do
    /var/www/html/daemon/daemoncaptchabad3.php
done
fi
	
