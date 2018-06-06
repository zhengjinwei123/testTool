#!/bin/bash
ps -ef|grep consumer|grep -v grep|awk  '{print "kill -9 " $2}' |sh
sleep 1s
ps -aux | grep consumer