#!/bin/bash
# 每秒发布消息数100=1000人
CT=1000
## 聊天日志 180字节 1000人每秒200个信息
count=$[CT*2]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 180 -n chatLogging &

## 聊天状态 50字节 1000人每秒200个信息
count=$[CT*2]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 50 -n chatStat &

## 断开连接 60字节 1000人每秒5个信息
count=$[CT/20]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 60 -n disconnect &

## 关闭  60字节  1000人每秒5个信息
count=$[CT/20]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 60  -n close &

## 地图事件  30字节 1000人每秒50个信息
count=$[CT/2]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 30 -n mapEvent &

## 登录  30字节 1000人每秒 20 个信息
count=$[CT/5]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 30 -n login &

## 离开 30字节 1000人每秒5个信息
count=$[CT/20]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 30 -n leave &

## 公会 60字节 1000人每秒20个信息
count=$[CT/5]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 60 -n loungeInfo &

## 注册 60 字节 1000人每秒10个信息
count=$[CT/10]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 60 -n register &

## 战斗奖励 30字节 1000人每秒100个信息
count=$[CT*1]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 30 -n boost &

## 玩家信息 30字节 1000人每秒50个信息
count=$[CT/2]
php ./publisher.php -e ex_zjw_event -r r_zjw_event -c $count -k 30 -n userInfo &

## 好友信息 20字节 1000人每秒 5个信息
count=$[CT/20]
php ./publisher.php -e ex_zjw_friend -r r_zjw_friend -c $count -k 20 -n friendInfo &

## 玩家等级查询 20字节 1000人每秒50 个信息查询
count=$[CT/2]
php ./publisher.php -e ex_zjw_level -r r_zjw_level -c $count -k 20 -n levelSearching &
