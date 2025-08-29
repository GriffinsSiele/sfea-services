# example for parallel consuming 

```shell
export RABBITMQ_ADDR=172.16.99.1:5672

consumer="bin/console messenger:consume --use-internal-server=false --scope getcontact_phone"

$consumer > /tmp/log.1 2> /tmp/log.1.err &
consumer1_pid=$!

$consumer > /tmp/log.2 2> /tmp/log.2.err &
consumer2_pid=$!

$consumer > /tmp/log.3 2> /tmp/log.3.err &
consumer3_pid=$!

$consumer > /tmp/log.4 2> /tmp/log.4.err &
consumer4_pid=$!

$consumer > /tmp/log.5 2> /tmp/log.5.err &
consumer5_pid=$!

wait $consumer1_pid $consumer2_pid $consumer3_pid $consumer4_pid $consumer5_pid
```