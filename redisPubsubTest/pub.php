<?php
/**
 * Created by PhpStorm.
 * User: zhengjw
 * Date: 2018/3/8
 * Time: 11:52
 */
date_default_timezone_set("Asia/Shanghai");
include "./RedisDb.php";

$param = getopt("c:n:k:");

if(!isset($param["c"]) ||
   !isset($param["n"]) ||
   !isset($param["k"])){
    echo "param lost \r\n";
    exit();
}

$channel = $param["c"];
$c = intval($param["n"]);
$kb = intval($param["k"]);

if($c <= 0){
    $c = 1;
}

function logger($str)
{
    echo "$str \r\n";
}



$config = array('host' => '127.0.0.1', 'port' => 6379, 'db' => 6, 'auth' => "123456", 'pconnect' => false);

$redis = new RedisDb($config);

$startTm = time();
$lastTm = $startTm;
$msg = str_repeat("z",$kb);

$sCount = 0;
while (true) {
    usleep(1000000);

    for($i = 0 ;$i < $c;++$i){
//        $redis->publish($channel,$msg);
        $redis->lpush($channel,$msg);
        $sCount += 1;
    }

    $now = time();
    if($now - $lastTm >= 5){
        $lastTm = $now;
        $cost = $now - $startTm;
        logger("发布消息发 频道:$channel   kb:$kb  [$sCount] 条. 耗时:$cost 秒.");
    }
}
