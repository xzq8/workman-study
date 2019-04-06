<?php
require_once('Base.class.php');
require_once('function.php');

class  ftjsPlan extends Base
{
    private $url;

    function __construct($name, $restartTime, $url, $code)
    {
        $beishu = 3;
        $qiuWeiShu = 10;
        $this->url = $url;
        parent::__construct($name, $beishu, $restartTime, $qiuWeiShu, $code);
    }

    public static function init()
    {
        $name = '幸运飞艇';
        $code = 'FTJS';
        $restartTime = 10;
        $url = 'https://api.api68.com/pks/getPksHistoryList.do?lotCode=10057&rows=20';
        return new ftjsPlan($name, $restartTime, $url, $code);
    }
//        public function getQSKey(){
//            return ['num','number'];
//        }

    public function getplan()
    {
        try {
            $rs = doCurlPostRequest($this->url);
            saveTotxt('step:1，发送请求', $this->code);
            if (!$rs) {
                sleep(5);
                echo "请求数据失败...\n";
                self::getplan();
                return false;
            }
            $rs = json_decode($rs);
            if (!$rs) {
                sleep(5);
                echo "请求数据失败...\n";
                saveTotxt($rs . 'step:2,获取数据失败2', $this->code);
                self::getplan();
                return false;
            }
//            $data = object_to_array($rs);
            $data = $rs->result->data;
            $planmsg = self::plan($data);
            return $planmsg;
        } catch (Exception $e) {
            saveTotxt($e, $this->code);
            return false;
        }

    }

}

?>