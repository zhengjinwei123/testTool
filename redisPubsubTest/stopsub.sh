#!/bin/bash
ps -ef|grep sub|grep -v grep|awk  '{print "kill -9 " $2}' |sh
sleep 1s
ps -aux | grep sub