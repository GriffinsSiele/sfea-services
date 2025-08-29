#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PID5.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECK5.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLE5.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECK5.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLE5.txt
	            echo '0' > /var/www/html/daemon/CHECK5.txt
	            echo $$ > /var/www/html/daemon/PID5.txt
	            while :
	            do
	                  /var/www/html/daemon/daemon5.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLE5.txt
	    echo '0' > /var/www/html/daemon/CHECK5.txt
	    echo $$ > /var/www/html/daemon/PID5.txt
	    while :
	    do
	          /var/www/html/daemon/daemon5.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PID5.txt
echo '0' > /var/www/html/daemon/CYCLE5.txt
echo '0' > /var/www/html/daemon/CHECK5.txt
while :
do
    /var/www/html/daemon/daemon5.php
done
fi
	
