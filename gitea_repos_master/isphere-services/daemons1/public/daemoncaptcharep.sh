#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PIDcaptcharep.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECKcaptcharep.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLEcaptcharep.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECKcaptcharep.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLEcaptcharep.txt
	            echo '0' > /var/www/html/daemon/CHECKcaptcharep.txt
	            echo $$ > /var/www/html/daemon/PIDcaptcharep.txt
	            while :
	            do
	                  /var/www/html/daemon/daemoncaptcharep.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLEcaptcharep.txt
	    echo '0' > /var/www/html/daemon/CHECKcaptcharep.txt
	    echo $$ > /var/www/html/daemon/PIDcaptcharep.txt
	    while :
	    do
	          /var/www/html/daemon/daemoncaptcharep.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PIDcaptcharep.txt
echo '0' > /var/www/html/daemon/CYCLEcaptcharep.txt
echo '0' > /var/www/html/daemon/CHECKcaptcharep.txt
while :
do
    /var/www/html/daemon/daemoncaptcharep.php
done
fi
	
