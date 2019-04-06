<?php
use \Workerman\Worker;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/../../vendor/autoload.php';
require_once('include/f5sscPlan.class.php');
require_once('include/jsFTJSPlan.class.php');
header('Content-type:text/html;charset=UTF-8');

$task = new Worker();
// 开启多少个进程运行定时任务，注意多进程并发问题
$task->count = 1;
$task->onWorkerStart = function($task)
{
    $GLOBALS['client'] = stream_socket_client('tcp://127.0.0.1:8066');
    if(!$GLOBALS['client'])exit("can not connect");
    // 每2.5秒执行一次 支持小数，可以精确到0.001，即精确到毫秒级别
    $time_interval = 60;
    Timer::add($time_interval, function()
    {
        $plan = ftjsPlan::init();
        $plan->getplan();
    });
    Timer::add($time_interval, function()
    {
        $f5plan = f5sscPlan::init();
        $f5plan->getplan();
    });
};

// 运行worker
Worker::runAll();
?>