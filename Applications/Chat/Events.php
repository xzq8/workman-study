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

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

/**
 * 聊天主逻辑
 * 主要是处理 onMessage onClose 
 */
use \GatewayWorker\Lib\Gateway;

class Events
{
   
   /**
    * 有消息时
    * @param int $client_id
    * @param mixed $message
    */
   public static function onMessage($client_id, $message)
   {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";
        
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
        }
        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;

            // 客户端登录 message格式: {type:login, name:xx, roomid:1} ，添加到客户端，广播给所有客户端xx进入聊天室
//request:{"type":"Login","roomid":"6001","chatid":"x08D74A69","nick":"游客08D74A69","sex":"0","touxiang":"/face/p1/null.jpg","age":"1","qx":"0","ip":"180.178.127.82","vip":"","color":"0","cam":"0","state":"0","mood":"","token":"82eb395220226a22721b54fdc1062175"}
            case 'Login':
                // 判断是否有房间号
                if(!isset($message_data['roomid']))
                {
                    echo "非法请求,没有房间号";
                    return;
//                    throw new \Exception("\$message_data['roomid'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                if(md5("8i2w9$^p8iTY1q72".$message_data['ip'])!= $message_data['token']){
                    echo "非法请求,token加密不相等not";
                    return;
//                    throw new \Exception("token加密不相等not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }

                // 把房间号 和用戶id放到session中
                $roomid = $message_data['roomid'];
                $chatid = htmlspecialchars($message_data['chatid']);
                $_SESSION['roomid'] = $roomid;
                $_SESSION['chatid'] = $chatid;
                $_SESSION['userinfo'] = $message_data;

//  {"type":"Ulogin","stat":"OK","roomListUser":null,"UMsg":null,"Ulogin":{"type":"Login","roomid":"8001","chatid":"1","nick":"系统管理员","sex":"2",
//"touxiang":"/face/p1/1.png?number=0.0223251221928229","age":"0","qx":"1","ip":"127.0.0.1","vip":"","color":"2","cam":"1","state":"0","mood":"我是管理员","token":"f866a3daaf526c63676bd04cf48b211b"}}
                // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx} 
                $new_message = array('type'=>'Ulogin','Ulogin'=> $message_data,'roomListUser'=>null, 'stat'=>'OK', 'Ulogout'=>null,'UMsg'=>null);
                Gateway::sendToGroup($roomid, json_encode($new_message));
                Gateway::joinGroup($client_id, $roomid);
//repose: {"type":"UonlineUser","stat":"OK",
//"roomListUser":[{"client_id":"9597f5bc-126c-4923-93b8-312e0adf7b30",
//"client_name":{
//"type":"Login","roomid":"8001","chatid":"3567","nick":"落寞的花生","sex":"2","touxiang":"/face/p1/null.jpg","age":"4","qx":"0","ip":"127.0.0.1","vip":"AA6",
//"color":"1","cam":"0","state":"0","mood":"","token":"f866a3daaf526c63676bd04cf48b211b"}},
//{"client_id":"cd7dbe09-240e-48c8-a1e0-0edfa4e80bd6","client_name":{"age":"0","cam":"1","chatid":"3526","color":"2","ip":"127.0.0.1",
//"mood":"计划员999","nick":"计划员999","qx":"1","roomid":"8001","sex":"2","state":"0","token":"f866a3daaf526c63676bd04cf48b211b",
//"touxiang":"/face/p1/null.jpg","type":"Login","vip":""}}],"Ulogout":null,"UMsg":null}
                // 获取房间内所有用户列表
                $clients_list = Gateway::getClientSessionsByGroup($roomid);
//                echo "用戶列表：";
//                var_dump($clients_list);
                $roomLisetUser = [];
                $onlineUser = []; // 防止用戶重複
                foreach($clients_list as $tmp_client_id=>$item)
                {
                    if(in_array($item['userinfo']['chatid'],$onlineUser)){
                       continue;
                    }else{
                        array_push($onlineUser,$item['userinfo']['chatid']);// 防止用戶重複
                        $user['client_id'] = $tmp_client_id;
                        $user['client_name'] =$item['userinfo'];
                       array_push($roomLisetUser,$user);
                    }
                }
                //当前用户自己
                if(!in_array($chatid,$onlineUser)){
                    array_push($roomLisetUser,array(
                        'client_id'=>$client_id,
                        'client_name'=>$message_data
                    ));
                }

                // 给当前用户发送用户列表
                $curr_message = array('type'=>'UonlineUser', 'roomListUser'=>$roomLisetUser, 'stat'=>'OK', 'Ulogout'=>null,'UMsg'=>null);
                Gateway::sendToCurrentClient(json_encode($curr_message));
                return;


//{"type":"SendMsg","ToChatId":"ALL","IsPersonal":"false","Style":"","tanmu":"false","Txt":"754ae5a0_+_1"}
//返回{"type":"UMsg","stat":"OK","Ulogout":null,
//"UMsg":{"Txt":"754ae5a0_+_1","IsPersonal":"false","Style":"","tanmu":"false","ToChatId":"ALL","device":"","ChatId":"3567"}}
           // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'SendMsg':
                // 非法请求
                if(!isset($_SESSION['roomid']))
                {
                    echo "非法请求";
                    return;
//                    throw new \Exception("\$_SESSION['roomid'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $roomid = $_SESSION['roomid'];
                $chatid = $_SESSION['chatid'];
                
                // 私聊
//                if($message_data['ToChatId'] != 'all')
//                {
//                    $new_message = array(
//                        'type'=>'say',
//                        'from_client_id'=>$client_id,
//                        'from_client_name' =>$client_name,
//                        'to_client_id'=>$message_data['to_client_id'],
//                        'content'=>"<b>对你说: </b>".nl2br(htmlspecialchars($message_data['content'])),
//                        'time'=>date('Y-m-d H:i:s'),
//                    );
//                    Gateway::sendToClient($message_data['to_client_id'], json_encode($new_message));
//                    $new_message['content'] = "<b>你对".htmlspecialchars($message_data['to_client_name'])."说: </b>".nl2br(htmlspecialchars($message_data['content']));
//                    return Gateway::sendToCurrentClient(json_encode($new_message));
//                }
                $message_data['ChatId'] = $chatid;
                $new_message = array(
                    'type'=>'UMsg',
                    'stat'=>'OK',
                    'Ulogout' =>'null',
                    'UMsg'=>$message_data,
                );
                return Gateway::sendToGroup($roomid ,json_encode($new_message));

           case 'Plan':
               $roomid = $message_data['roomid'];
               $new_message = array(
                   'type'=>'UMsg',
                   'stat'=>'OK',
                   'Ulogout' =>'null',
                   'UMsg'=>$message_data,
               );
               echo $roomid;
               Gateway::sendToGroup($roomid ,json_encode($new_message));
               return;
        }
   }
   
   /**
    * 当客户端断开连接时
    * @param integer $client_id 客户端id
    */
   public static function onClose($client_id)
   {
       // debug
       echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";
//{"type":"Ulogout","stat":"OK","roomListUser":null,"UMsg":null,"Ulogout":{"type":"Login","roomid":"8001","chatid":"3567","nick":"落寞的花生","sex":"2","touxiang":"/face/p1/null.jpg","age":"4","qx":"0","ip":"127.0.0.1","vip":"AA6","color":"1","cam":"0","state":"0","mood":"","token":"f866a3daaf526c63676bd04cf48b211b"}}
       // 从房间的客户端列表中删除
       if(isset($_SESSION['roomid']))
       {
           $roomid = $_SESSION['roomid'];
           $new_message = array('type'=>'Ulogout', 'stat'=>'OK','roomListUser'=>null,'UMsg'=>null,'Ulogout'=>$_SESSION['userinfo']);
           Gateway::sendToGroup($roomid, json_encode($new_message));
       }
   }
  
}
