<?php
/**
 * Created by PhpStorm.
 * User: zhengjw
 * Date: 2018/3/2
 * Time: 16:01
 */
date_default_timezone_set("Asia/Shanghai");

$param_arr = getopt('e:r:c:k:n:');
if(!isset($param_arr["e"]) ||
   !isset($param_arr["r"]) ||
   !isset($param_arr["c"]) ||
   !isset($param_arr["k"]) ||
   !isset($param_arr["n"])){
    echo "参数错误 [php publisher.php -e exchangeName -r routeName -c 1000 -k 100 -n event]\r\n";
    exit(0);
}

$exchange = $param_arr["e"];
$route = $param_arr["r"];
$count = intval($param_arr["c"]);// 每秒发送消息条数
$kbSize = intval($param_arr["k"]); // 包体大小
$name = $param_arr["n"]; // 名字


require_once dirname(__FILE__)."/rabbitMQ.php";

$b = new RabbitMqPublisher([
    "host" => "127.0.0.1",
    "port" => 5672,
    "login" => "",
    "password" => "",
    "vhost" => "/",
    "exchange_name" => $exchange,
    "route_name" => $route
],$name);

$startTm = time();
$lastTm = time();
$msgCount = 0;
$msg = str_repeat("z",$kbSize);
while(true){
    usleep(1000000);// 延迟1秒 1秒 = 1000000微妙

    $now = time();
    if($now - $lastTm >= 5){
        $cost = $now - $startTm;
        $b->log("[". $name . "] 已发布消息: $msgCount 条,耗时:[$cost] 秒 \r\n");
        $lastTm = $now;
    }

    for($i = 0 ; $i < $count;++$i){
        $b->publish($msg);
        $msgCount ++;
    }
}
