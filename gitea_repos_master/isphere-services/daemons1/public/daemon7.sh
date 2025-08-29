#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PID7.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECK7.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLE7.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECK7.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLE7.txt
	            echo '0' > /var/www/html/daemon/CHECK7.txt
	            echo $$ > /var/www/html/daemon/PID7.txt
	            while :
	            do
	                  /var/www/html/daemon/daemon7.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLE7.txt
	    echo '0' > /var/www/html/daemon/CHECK7.txt
	    echo $$ > /var/www/html/daemon/PID7.txt
	    while :
	    do
	          /var/www/html/daemon/daemon7.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PID7.txt
echo '0' > /var/www/html/daemon/CYCLE7.txt
echo '0' > /var/www/html/daemon/CHECK7.txt
while :
do
    /var/www/html/daemon/daemon7.php
done
fi
	
