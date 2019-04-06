<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \Workerman\Connection\AsyncTcpConnection;

require_once __DIR__ . '/../../vendor/autoload.php';

// 心跳间隔55秒
define('HEARTBEAT_TIME', 55);

$worker = new Worker("http://0.0.0.0:8066");

$worker->count = 1;
$worker->name = 'chatClient';

function createWS($roomid){
    $con = new AsyncTcpConnection('ws://127.0.0.1:7006');
    $con->onConnect = function($con)use($roomid) {
        $loginjson = '{"age" : "0","cam" : "1","chatid" : "3526","color" : "2","ip" : "127.0.0.1","mood" : "计划员999","nick" : "计划员999","qx" : "1","roomid" : "' . $roomid . '","sex" : "2","state" : "0","token" : "f866a3daaf526c63676bd04cf48b211b","touxiang" : "/face/p1/1.png?number=0.0223251221928229","type" : "Login","vip" : ""}';
        $con->send($loginjson);
    };

    $con->onMessage = function($con, $data) {
      echo $data .'\t\n';
    };

    $con->onClose = function($con) {
        // 如果连接断开，则在1秒后重连
        $con->reConnect(2);
    };

    $con->connect();

    return $con;
}

function wsArr(){
    $arr = [6001,7001,8001];
    $conArr = [];
    foreach($arr as $item){
        $conArr[$item]= createWS($item);
    }
    return $conArr;
}


$worker->onWorkerStart = function($worker){

    $conArr = wsArr();

    $worker->onMessage = function($connection, $data)use($conArr)
    {
//        var_dump($data);
//		$realIP = $_SERVER['REMOTE_ADDR'];
//		echo $realIP;
        $get = $data['get'];
        if(!$get){
            return ;
        }
        $msg=$get['msg'];
        $roomid = $get['roomid'];
        if($msg && $roomid){
            $json = '{"ChatId" : "1","IsPersonal": "false","Style": "","ToChatId": "ALL","Txt": "b2d5cc35_+_' . $msg . '","tanmu": "false","type": "SendMsg"}';
//		var_dump($conArr);
            $conArr[$roomid]->send($json);
            //  // 向浏览器发送hello world
            $connection->send('SUCCESS');
        }

    };
};
Worker::runAll();

