#!/bin/bash
## 聊天日志
php ./consumer.php -e ex_zjw_event -q q_zjw_event -r r_zjw_event -n event &

## 好友信息
php ./consumer.php -e ex_zjw_friend -q q_zjw_friend -r r_zjw_friend -n friend &

## 玩家等级查询
php ./consumer.php -e ex_zjw_level -q q_zjw_level -r r_zjw_level -n level &