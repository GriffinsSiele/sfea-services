#!/bin/bash

PIDNUMBER=$(cat /var/www/html/daemon/PID9.txt);
if [ $PIDNUMBER -gt 0 ]; then
      if kill -s 0 $PIDNUMBER; then
            CHECK=$(cat /var/www/html/daemon/CHECK9.txt);
            CYCLE=$(cat /var/www/html/daemon/CYCLE9.txt);
            if [ $CHECK -ne $CYCLE ]; then
                    echo $CYCLE > /var/www/html/daemon/CHECK9.txt
            else
                    kill $PIDNUMBER;
	            echo '0' > /var/www/html/daemon/CYCLE9.txt
	            echo '0' > /var/www/html/daemon/CHECK9.txt
	            echo $$ > /var/www/html/daemon/PID9.txt
	            while :
	            do
	                  /var/www/html/daemon/daemon9.php
	            done
            fi
      else
            kill $PIDNUMBER
 	    echo '0' > /var/www/html/daemon/CYCLE9.txt
	    echo '0' > /var/www/html/daemon/CHECK9.txt
	    echo $$ > /var/www/html/daemon/PID9.txt
	    while :
	    do
	          /var/www/html/daemon/daemon9.php
	    done
     fi
else
echo $$ > /var/www/html/daemon/PID9.txt
echo '0' > /var/www/html/daemon/CYCLE9.txt
echo '0' > /var/www/html/daemon/CHECK9.txt
while :
do
    /var/www/html/daemon/daemon9.php
done
fi
	
