<?php
require_once('Base.class.php');
require_once('function.php');

class  f5sscPlan extends Base
{
    private $url;

    function __construct($name, $restartTime, $url, $code)
    {
        $beishu = 3;
        $qiuWeiShu = 5;
        $this->url = $url;
        parent::__construct($name, $beishu, $restartTime, $qiuWeiShu, $code);
    }

    public static function init()
    {
        $name = '腾讯五分彩';
        $restartTime = 10;
        $code = 'f5ssc';
        $url = 'http://gf5.809996.com:93/Result/GetLotteryResultList?gameID=156&pageSize=20&pageIndex=1&_=1554465527652';
        return new f5sscPlan($name, $restartTime, $url, $code);
    }

    public function getQSKey()
    {
        return ['period', 'result'];
    }


    public function setDXDSNum($opennum, $obj)
    {
        if ($opennum >= 5) { // 大于5 是 大
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

    public function getplan()
    {
        try {
            $rs = doCurlPostRequest($this->url);
            saveTotxt($rs . 'step:1，请求', $this->code);
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
            $data = $rs->list;
            $planmsg = self::plan($data);
            return $planmsg;
        } catch (Exception $e) {
            saveTotxt($e, $this->code);
            return false;
        }
    }
}

?>