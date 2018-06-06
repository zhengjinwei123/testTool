<?php
/**
 * Created by PhpStorm.
 * User: zhengjw
 * Date: 2018/3/2
 * Time: 11:29
 */
date_default_timezone_set("Asia/Shanghai");

$param_arr = getopt('e:q:r:n:');

if (!isset($param_arr["e"]) ||
    !isset($param_arr["q"]) ||
    !isset($param_arr["r"]) ||
    !isset($param_arr["n"])) {
    echo "参数缺失 [php consumer.php -e exchangeName -q queueName -r routeName -n event]\r\n";
    exit(0);
}

$exchange = $param_arr['e'];
$queue = $param_arr["q"];
$route = $param_arr["r"];
$name = $param_arr["n"];

require_once dirname(__FILE__) . "/rabbitMQ.php";

$a = new RabbitMqConsumer([
    "host" => "127.0.0.1",
    "port" => 5672,
    "login" => "",
    "password" => "",
    "vhost" => "/",
    "exchange_name" => $exchange,
    "queue_name" => $queue,
    "route_name" => $route
],$name);
$a->run();





