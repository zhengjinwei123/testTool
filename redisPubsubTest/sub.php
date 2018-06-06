<?php
/**
 * Created by PhpStorm.
 * User: zhengjw
 * Date: 2018/3/8
 * Time: 11:52
 */
date_default_timezone_set("Asia/Shanghai");
include "./RedisDb.php";


$param = getopt("c:");

if(!isset($param["c"])){
    echo "param lost \r\n";
    exit();
}

$channel = $param["c"];

$config = array('host' => '127.0.0.1', 'port' => 6379, 'db' => 6, 'auth' => "123456", 'pconnect' => false);

$redis = new RedisDb($config);


$cnt = 0;
$startTm = $lastTm = time();
while(true){
    usleep(1000);
    $value = $redis->lpop($channel);
    if(!$value){
        continue;
    }
    $cnt ++;
    $now = time();
    if($now - $lastTm >= 5){
        $cost = $now - $startTm;
        $lastTm = $now;
        echo "订阅消息 频道:$channel [$cnt] 条. 耗时:$cost 秒 \r\n";
    }


}

//class CallFunc{
//    private $startTm;
//    private $sCount;
//
//    public function __construct(){
//        $this->startTm = time();
//        $this->sCount = 0;
//    }
//
//    public function logger($str)
//    {
//        echo "$str \r\n";
//    }
//
//    public function callback($redis, $chan, $msg){
//        $now = time();
//        $this->sCount ++;
//
//        $cost = $now - $this->startTm;
//        $cnt = $this->sCount;
//        $this->logger("订阅消息 频道:$chan [$cnt] 条. 耗时:$cost 秒");
//    }
//}
//
//$inst = new CallFunc();
//$redis->subscribe(array($channel), array($inst,"callback"));

