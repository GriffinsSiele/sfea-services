#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PID10.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECK10.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLE10.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECK10.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLE10.txt
	            echo '0' > /var/www/html/daemon/CHECK10.txt
	            echo $$ > /var/www/html/daemon/PID10.txt
	            while :
	            do
	                  /var/www/html/daemon/daemon10.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLE10.txt
	    echo '0' > /var/www/html/daemon/CHECK10.txt
	    echo $$ > /var/www/html/daemon/PID10.txt
	    while :
	    do
	          /var/www/html/daemon/daemon10.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PID10.txt
echo '0' > /var/www/html/daemon/CYCLE10.txt
echo '0' > /var/www/html/daemon/CHECK10.txt
while :
do
    /var/www/html/daemon/daemon10.php
done
fi
	
