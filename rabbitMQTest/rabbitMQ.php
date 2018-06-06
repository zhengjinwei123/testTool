<?php

/**
 * Created by PhpStorm.
 * User: zhengjw
 * Date: 2018/3/2
 * Time: 14:45
 */
class RabbitMqPublisher
{
    private $_config = [];
    private $_connection = null;
    private $_channel = null;
    private $_exchange = null;
    private $_queue = null;
    private $name = "";


    public function __construct($config = array(),$name)
    {
        $defaultCfg = array(
            "host" => "127.0.0.1",
            "port" => 5672,
            "login" => "",
            "password" => "",
            "vhost" => "/",
            "exchange_name" => "e_name",
            "route_name" => "r_name"
        );

        $this->name = $name;

        $this->_config = array_merge($defaultCfg, $config);

        $this->init();
    }

    private function init()
    {
        $this->_connection = new AMQPConnection();

        $this->_connection->setHost($this->_config["host"]);
        $this->_connection->setPort($this->_config["port"]);
        $connected = $this->_connection->connect();
        if (!$connected) {
            die("cannot connect to the broken!\r\n");
        }

        // 创建通道，一个connection 里可以有多个channel
        $this->_channel = new AMQPChannel($this->_connection);

        // 创建交换机，消息载体，定义消息路由规则
        $this->_exchange = new AMQPExchange($this->_channel);
        $this->_exchange->setName($this->_config["exchange_name"]);
        $this->_exchange->setType(AMQP_EX_TYPE_DIRECT);
//        $this->_exchange->setFlags(AMQP_DURABLE);

        $this->_exchange->declareExchange();
    }

    public function publish($msg)
    {
        $this->_exchange->publish($msg, $this->_config["route_name"]);
    }

    public function log($msg){
        echo "$msg";
        file_put_contents("/data/rabbitMQ_publish.txt",$msg,FILE_APPEND);
    }
}


class RabbitMQConsumer
{
    private $_config = [];
    private $_connection = null;
    private $_channel = null;
    private $_exchange = null;
    private $_queue = null;
    private $name = "";


    public function __construct($config = array(),$name)
    {
        $defaultCfg = array(
            "host" => "127.0.0.1",
            "port" => 5672,
            "login" => "",
            "password" => "",
            "vhost" => "/",
            "exchange_name" => "e_name",
            "queue_name" => "q_name",
            "route_name" => "r_name"
        );

        $this->name = $name;

        $this->_config = array_merge($defaultCfg, $config);
        $this->init();
    }

    private function init()
    {
        $this->_connection = new AMQPConnection();

        $this->_connection->setHost($this->_config["host"]);
        $this->_connection->setPort($this->_config["port"]);
        $connected = $this->_connection->connect();
        if (!$connected) {
            die("cannot connect to the broken!\r\n");
        }

        // 创建通道，一个connection 里可以有多个channel
        $this->_channel = new AMQPChannel($this->_connection);

        // 创建交换机，消息载体，定义消息路由规则
        $this->_exchange = new AMQPExchange($this->_channel);
        $this->_exchange->setName($this->_config["exchange_name"]);
        $this->_exchange->setType(AMQP_EX_TYPE_DIRECT);
//        $this->_exchange->setFlags(AMQP_DURABLE);

        $status = $this->_exchange->declareExchange();
        echo $this->name . " exchange status:" . $status . "\r\n";

        // 创建队列
        $this->_queue = new AMQPQueue($this->_channel);
        $this->_queue->setName($this->_config["queue_name"]);
        $this->_queue->setArgument('x-queue-mode', 'lazy');
//        $this->_queue->setFlags(AMQP_DURABLE);
        $status = $this->_queue->declareQueue();
        echo $this->name . " queue status:$status \r\n";

        // 绑定交换机与队列，并指定路由键
        $this->_queue->bind($this->_config["exchange_name"], $this->_config["route_name"]);
    }

    public function run()
    {
        $startTm = time();
        $lastTm = time();
        $msgCount = 0;

        $flag = 0;
        $autoAck = true;
        if ($autoAck) {
            $flag |= AMQP_AUTOACK;
        }

        while (true) {
            $envelope = $this->_queue->get($flag);
            if(empty($envelope)){
                continue;
            }
            $body = $envelope->getBody();

            $now = time();
            if($now - $lastTm >= 5){
                $cost = $now - $startTm;
                $lastTm = $now;
                $this->logger("[". $this->name . "] 消费 $msgCount 条,耗时:[$cost] 秒 \r\n");
            }

//        $queue->ack($envelope->getDeliveryTag()); //手动发送ACK应答
            $msgCount++;

//            $this->_queue->consume($callbackFunc);
        }
    }

    function logger($msg){
        echo "$msg";
        file_put_contents("/data/rabbitMQ_consume.txt",$msg,FILE_APPEND);
        exec("top -p 4949 -b -n 1 | grep rabbit >> /data/rabbitMQ_cpumem.txt");
    }
}