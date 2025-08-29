#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PID8.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECK8.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLE8.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECK8.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLE8.txt
	            echo '0' > /var/www/html/daemon/CHECK8.txt
	            echo $$ > /var/www/html/daemon/PID8.txt
	            while :
	            do
	                  /var/www/html/daemon/daemon8.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLE8.txt
	    echo '0' > /var/www/html/daemon/CHECK8.txt
	    echo $$ > /var/www/html/daemon/PID8.txt
	    while :
	    do
	          /var/www/html/daemon/daemon8.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PID8.txt
echo '0' > /var/www/html/daemon/CYCLE8.txt
echo '0' > /var/www/html/daemon/CHECK8.txt
while :
do
    /var/www/html/daemon/daemon8.php
done
fi
	
