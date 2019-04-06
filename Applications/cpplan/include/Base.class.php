<?php

abstract class Base
{
    private $name;
    private $beishu;
    private $currentPlan;
    private $currentQS;
    private $restartTime;
    private $qiuWeiShu;
    public $code;
    public $planmsg;

    public function __construct($name, $beishu, $restartTime, $qiuWeiShu, $code)
    {
        $this->name = $name;
        $this->beishu = $beishu;
        $this->restartTime = $restartTime;
        $this->qiuWeiShu = $qiuWeiShu;
        $this->code = $code;
    }

    abstract function getplan();

    public function getQSKey()
    {
        return ['preDrawIssue', 'preDrawCode'];
    }

    public function plan($data)
    {
        if (!is_array($data)) {
            saveTotxt('必须传入数组', $this->code);
            return false;
        }
        $current = $this->getQSKey();
        $preDrawIssue = $current[0];
        $preDrawCode = $current[1];
        $newdata = reset($data);

        $lastPlan = self::stringToJson();
        $this->currentQS = $lastPlan['lastQS'];
        $this->currentPlan = $lastPlan['plan'];

        if ($this->currentQS == $newdata->$preDrawIssue) {
//             $this->setInterval($this->restartTime);
            saveTotxt('step:3,旧数据', $this->code);
            return false;
        }
        saveTotxt('step:3,拿到最新数据', $this->code);
        // 统计出1-10位的 大小单双次数
        $DXDS = self::createObj($this->qiuWeiShu); // 大小单双  统计

        $this->currentQS = $newdata->$preDrawIssue;
        $currentRES = $newdata->$preDrawCode;
        $data = array_slice($data, 0, 20);
        $data = array_reverse($data);

        //暂时通过几位数判断  3位是快三类型、 以后添加一个大类判断
        if ($this->qiuWeiShu == 3) {
            // 快三类型  和值的 大小单双
            foreach ($data as $value) {
                $everyQ = stringToArray($value->$preDrawCode);
                $everyQSum = array_sum($everyQ);
                $DXDS[0] = $this->setDXDSNum($everyQSum, $DXDS[0]);
            }
        } else {
            //时时彩 pk10 每一位的大小单双
            foreach ($data as $item) {
                $everyQ = stringToArray($item->$preDrawCode);
                foreach ($everyQ as $key => $value) {
                    $DXDS[$key] = $this->setDXDSNum($value, $DXDS[$key]);
                }
            }
        }

        // 查出最大的次数
        $MaxNum = self::getMaxcs($DXDS);

        // 是否为长龙

        $PK10_plan = self::newPlan($this->currentPlan, $MaxNum);
        self::saveToJson(['lastQS' => $this->currentQS, 'plan' => $PK10_plan]);
        // 发送计划
        // 北京赛车726316期开奖结果：07,10,01,06,03,08,09,05,02,04，☆☆☆☆☆726317期计划：第十名 大 1倍
        $planmsg = $this->planmsg = $this->name .
            $this->currentQS . '期开奖结果：' . $currentRES . '，☆☆☆☆☆' . self::getNextQS($this->currentQS) . '期计划：' . $PK10_plan['name'] . $PK10_plan['maxKey'] . pow(
                $this->beishu,
                $PK10_plan['beishu']
            ) . '倍';
        echo $planmsg;
        self::sendToChatRoom();
        saveTotxt('step:5,分析出计划,并且发送成功', $this->code);
        self::saveToTxt();
        return $planmsg;

    }

    private function saveToJson($obj)
    {
        // 把PHP数组转成JSON字符串
        $json_string = json_encode($obj);
        // 写入文件
        file_put_contents(dirname(dirname(__FILE__)) . '/planTemp/' . $this->code . '.json', $json_string);
    }

    private function stringToJson()
    {
        // 从文件中读取数据到PHP变量
        try {
            $path = dirname(dirname(__FILE__)) . '/planTemp/' . $this->code . '.json';
            if (!file_exists(dirname(dirname(__FILE__)) . '/planTemp')) {//检查文件夹是否存在
                mkdir(dirname(dirname(__FILE__)) . "/planTemp");    //没有就创建一个新文件夹
            }
            if (!file_exists($path)) {
                $str = '[]';    //join函数返回一个被指定分隔符分割后字符串
                file_put_contents($path, $str);
            }
            $json_string = file_get_contents($path);
            // 把JSON字符串转成PHP数组
            $data = json_decode($json_string, true);
        } catch (Exception $e) {
            $data = [];
        }
        return $data;
    }

    public function getNextQS($string)
    {
        if (strpos($string, '-') > -1) {
            $arrt = explode('-', $string);
            $arrt[1] = (float)$arrt[1] + 1;
            return implode("-", $arrt);
        } else {
            return (float)$string + 1;
        }
    }

    /*
     * 根据key 返回相反的中文
     */
    private static function getkeyCH($key)
    {
        if ($key == 'da') {
            return "小";
        }
        if ($key == "xiao") {
            return "大";
        }
        if ($key == "dan") {
            return '双';
        }
        if ($key == "shuang") {
            return "单";
        }
    }

    /*
     * 参数 开奖的数字 ， 私有对象
     * 通过开奖数字判断对象大小单双的连续次数
     */
    public function setDXDSNum($opennum, $obj)
    {
        if ($opennum > 5) { // 大于5 是 大
            $obj["da"]++;
            $obj['xiao'] = 0;
        } else {
            $obj["xiao"]++;
            $obj['da'] = 0;
        }
        if ($opennum % 2) { // 单双
            $obj['shuang'] = 0;
            $obj["dan"]++;
        } else {
            $obj['dan'] = 0;
            $obj["shuang"]++;
        }
        return $obj;
    }

    /*
     * 创建基础对象 收集开奖大小单双次数
     * 参数 对象上有几位、 10对应10个球
     */
    private static function createObj($num)
    {
        $arr = [];
        // 時時彩
        if ($num == 5) {
            for ($i = 0; $i < $num; $i++) {
                if ($i == 0) {
                    $name = "万位";
                }
                if ($i == 1) {
                    $name = "千位";
                }
                if ($i == 2) {
                    $name = "百位";
                }
                if ($i == 3) {
                    $name = "十位";
                }
                if ($i == 4) {
                    $name = "个位";
                }
                $tempObj['name'] = $name;
                $tempObj['da'] = 0;
                $tempObj['xiao'] = 0;
                $tempObj['dan'] = 0;
                $tempObj['shuang'] = 0;
                array_push($arr, $tempObj);
            }

        } else if ($num == 10) {
            // pk10
            for ($i = 0; $i < $num; $i++) {
                $name = '第' . (int)($i + 1) . '名';
                if ($i == 0) {
                    $name = "冠军";
                }
                if ($i == 1) {
                    $name = "亚军";
                }
                if ($i == 2) {
                    $name = "季军";
                }
                $tempObj['name'] = $name;
                $tempObj['da'] = 0;
                $tempObj['xiao'] = 0;
                $tempObj['dan'] = 0;
                $tempObj['shuang'] = 0;
                array_push($arr, $tempObj);
            }
        } else if ($num == 3) {
            $tempObj['name'] = '和值';
            $tempObj['da'] = 0;
            $tempObj['xiao'] = 0;
            $tempObj['dan'] = 0;
            $tempObj['shuang'] = 0;
            array_push($arr, $tempObj);
        }

        return $arr;
    }

    /*
     * 最大的次数
     */
    private static function getMaxcs($arr)
    {
        $max = 0;
        $maxobj = [];
        foreach ($arr as $item) {
            foreach ($item as $key => $value) {
                if ($key == "name") {
                    continue;
                }
                if ($value > $max) {
                    $max = $value;
                    $maxobj = $item;
                    $maxobj['maxKey'] = self::getkeyCH($key);
                }
            }
        }
        return $maxobj;
    }

    /*
     * 如果遇到长龙
     * 倍数翻倍
     */
    private static function newPlan($oldplan, $maxNum)
    {
        $newplan = [];
        if (count($oldplan)) {
            if ($oldplan['name'] == $maxNum['name'] && $oldplan['maxKey'] == $maxNum['maxKey']) {
                $newplan = $maxNum;
                if ($oldplan['beishu'] >= 4) {
                    $newplan['beishu'] = 0;
                } else {
                    $newplan['beishu'] = (int)$oldplan['beishu'] + 1;
                }
            } else {
                $newplan = $maxNum;
                $newplan['beishu'] = 0;
            }
        } else {
            $newplan = $maxNum;
            $newplan['beishu'] = 0;
        }
        return $newplan;
    }

    /*
     * 定時請求
     */
    public function setInterval($time)
    {
        $Time = $time;
        set_time_limit(0);
        sleep($Time * 60);
        $this->getplan();
        die;
    }

    private function saveToTxt()
    {
        saveTotxt($this->planmsg, $this->code);
    }

    /*
     * 6001
        白天 官方+极速赛车
        晚上 系统
        7001
        四个都发
        8001
        白天 发官方
        晚上 发系统彩
     */
    private function jsscIsNight()
    {
        $time = date("H");
        if ($time < 9) { // 凌晨到九點 才發
            return true;
        } else {
            return false;
        }
    }

    private function sscIsNight()
    {
        $time = date("H");
        if ($time >= 2 && $time < 10) { // 凌晨2点到早上十点才發
            return true;
        } else {
            return false;
        }
    }

    private function DoNotSend($roomid)
    {
        //暂时都发
        /*
         * 白天晚上
         */
//        if ($roomid == 6001) {
//            if ($this->code == 'jsssc') {
//                return $this->sscIsNight();
//            }
//        }
//        if ($roomid == 8001) {
//            if ($this->code == 'jsssc') {
//                return $this->sscIsNight();
//            }
//            if ($this->code == 'jsscpk10') {
//                return $this->jsscIsNight();
//            }
//        }
        if ($roomid == 6001 || $roomid == 7001) {
            $cpcode = ['jsssc', 'jsscpk10', 'JSK3', 'FTJS'];
            return in_array($this->code, $cpcode);
        } else if ($roomid == 8001) {
            $cpcode = ['PK10F5', 'f5ssc', 'bjpk10', 'cqssc'];
            return in_array($this->code, $cpcode);
        }
        return false;
    }

    private function sendToWhich($roomid)
    {
//        $issend = self::DoNotSend($roomid);
//        if (!$issend) {
//            return $roomid . '房间此时不发送' . $this->code . '计划';
//        }
        $message_data = '{"ChatId" : "3468", "roomid" : ' . $roomid . ',"IsPersonal": "false","Style": "","ToChatId": "ALL","Txt": "b2d5cc35_+_' . $this->planmsg . '","tanmu": "false","type": "Plan"}';
        // 模拟超级用户，以文本协议发送数据，注意Text文本协议末尾有换行符（发送的数据中最好有能识别超级用户的字段），这样在Event.php中的onMessage方法中便能收到这个数据，然后做相应的处理即可
        try {
            fwrite($GLOBALS['client'], $message_data . "\n");

        } catch (Exception $exception) {
            saveTotxt('step:4,发送计划socket失败' . $exception, $this->code);
            echo "发送数据失败...\n";
        }
        saveTotxt('step:4,发送计划socket成功', $this->code);
    }

    private function sendToChatRoom()
    {
        self::sendToWhich(6001);
        self::saveToSQL(6001);
//        $roomid = [6001, 7001, 8001];
//        foreach ($roomid as $item) {
//        self::sendToWhich($item);
//        }
    }

    private function saveToSQL($roomid)
    {
        $res = doCurlGetRequest('http://chat.old.com/plansaveToSql.php' . '?roomid=' . $roomid . '&msg=' . $this->planmsg);
        if (!$res) {
            saveTotxt('step:4,发送计划socket失败', $this->code);
//            sleep(6);
//            self::sendToWhich($roomid);
            echo "计划保存失败...\n";
        }
    }
}

?>
