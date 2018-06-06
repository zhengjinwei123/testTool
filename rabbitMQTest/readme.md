# 压力测试相关

1. 统计rabbitMQ cpu,mem

top -p 4949 -b -n 10000 | grep rabbit