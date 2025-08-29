#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PID2.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECK2.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLE2.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECK2.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLE2.txt
	            echo '0' > /var/www/html/daemon/CHECK2.txt
	            echo $$ > /var/www/html/daemon/PID2.txt
	            while :
	            do
	                  /var/www/html/daemon/daemon2.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLE2.txt
	    echo '0' > /var/www/html/daemon/CHECK2.txt
	    echo $$ > /var/www/html/daemon/PID2.txt
	    while :
	    do
	          /var/www/html/daemon/daemon2.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PID2.txt
echo '0' > /var/www/html/daemon/CYCLE2.txt
echo '0' > /var/www/html/daemon/CHECK2.txt
while :
do
    /var/www/html/daemon/daemon2.php
done
fi
	
