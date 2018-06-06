#!/bin/bash
# 每秒发布消息数100=1000人
CT=500
## 聊天日志 180字节 1000人每秒200个信息
count=$[CT*2]
php ./pub.php -c ex_zjw_event -n $count -k 180 &

## 聊天状态 50字节 1000人每秒200个信息
count=$[CT*2]
php ./pub.php -c ex_zjw_event  -n $count -k 50 &

## 断开连接 60字节 1000人每秒5个信息
count=$[CT/20]
php ./pub.php -c ex_zjw_event -n $count -k 60 &

## 关闭  60字节  1000人每秒5个信息
count=$[CT/20]
php ./pub.php -c ex_zjw_event -n $count -k 60 &

## 地图事件  30字节 1000人每秒50个信息
count=$[CT/2]
php ./pub.php -c ex_zjw_event -n $count -k 30 &

## 登录  30字节 1000人每秒 20 个信息
count=$[CT/5]
php ./pub.php -c ex_zjw_event -n $count -k 30 &

## 离开 30字节 1000人每秒5个信息
count=$[CT/20]
php ./pub.php -c ex_zjw_event -n $count -k 30 &

## 公会 60字节 1000人每秒20个信息
count=$[CT/5]
php ./pub.php -c ex_zjw_event -n $count -k 60 &

## 注册 60 字节 1000人每秒10个信息
count=$[CT/10]
php ./pub.php -c ex_zjw_event -n $count -k 60 &

## 战斗奖励 30字节 1000人每秒100个信息
count=$[CT*1]
php ./pub.php -c ex_zjw_event -n $count -k 30 &

## 玩家信息 30字节 1000人每秒50个信息
count=$[CT/2]
php ./pub.php -c ex_zjw_event -n $count -k 30 &

## 好友信息 20字节 1000人每秒 5个信息
count=$[CT/20]
php ./pub.php -c ex_zjw_friend -n $count -k 20 &

## 玩家等级查询 20字节 1000人每秒50 个信息查询
count=$[CT/2]
php ./pub.php -c ex_zjw_level -n $count -k 20 &
