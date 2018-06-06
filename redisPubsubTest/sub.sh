#!/bin/bash
## 聊天日志
php ./sub.php -c ex_zjw_event &

## 好友信息
php ./sub.php -c ex_zjw_friend &

## 玩家等级查询
php ./sub.php -c ex_zjw_level &