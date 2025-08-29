#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PID4.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECK4.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLE4.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECK4.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLE4.txt
	            echo '0' > /var/www/html/daemon/CHECK4.txt
	            echo $$ > /var/www/html/daemon/PID4.txt
	            while :
	            do
	                  /var/www/html/daemon/daemon4.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLE4.txt
	    echo '0' > /var/www/html/daemon/CHECK4.txt
	    echo $$ > /var/www/html/daemon/PID4.txt
	    while :
	    do
	          /var/www/html/daemon/daemon4.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PID4.txt
echo '0' > /var/www/html/daemon/CYCLE4.txt
echo '0' > /var/www/html/daemon/CHECK4.txt
while :
do
    /var/www/html/daemon/daemon4.php
done
fi
	
