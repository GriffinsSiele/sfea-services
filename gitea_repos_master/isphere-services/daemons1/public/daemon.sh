#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PID.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECK.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLE.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECK.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLE.txt
	            echo '0' > /var/www/html/daemon/CHECK.txt
	            echo $$ > /var/www/html/daemon/PID.txt
	            while :
	            do
	                  /var/www/html/daemon/daemon.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLE.txt
	    echo '0' > /var/www/html/daemon/CHECK.txt
	    echo $$ > /var/www/html/daemon/PID.txt
	    while :
	    do
	          /var/www/html/daemon/daemon.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PID.txt
echo '0' > /var/www/html/daemon/CYCLE.txt
echo '0' > /var/www/html/daemon/CHECK.txt
while :
do
    /var/www/html/daemon/daemon.php
done
fi
	
