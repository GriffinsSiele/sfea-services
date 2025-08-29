#!/bin/sh
TINC_DIR=/etc/tinc/i-sphere/
cd $TINC_DIR
keys_src=`ls $TINC_DIR/hosts`
git submodule update --init --remote
keys_dst=`ls $TINC_DIR/hosts`
if [ "$keys_src" != "$keys_dst" ]
then 
	python3 $TINC_DIR/cron/rewrite_config.py
	systemctl restart tinc@i-sphere
fi
