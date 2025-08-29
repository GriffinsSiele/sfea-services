#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PID6.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECK6.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLE6.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECK6.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLE6.txt
	            echo '0' > /var/www/html/daemon/CHECK6.txt
	            echo $$ > /var/www/html/daemon/PID6.txt
	            while :
	            do
	                  /var/www/html/daemon/daemon6.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLE6.txt
	    echo '0' > /var/www/html/daemon/CHECK6.txt
	    echo $$ > /var/www/html/daemon/PID6.txt
	    while :
	    do
	          /var/www/html/daemon/daemon6.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PID6.txt
echo '0' > /var/www/html/daemon/CYCLE6.txt
echo '0' > /var/www/html/daemon/CHECK6.txt
while :
do
    /var/www/html/daemon/daemon6.php
done
fi
	
