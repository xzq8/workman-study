<?php
/**
 * Created by PhpStorm.
 * User: lenovo12
 * Date: 2019/1/18
 * Time: 21:42
 */

/*
 * 判断是不是字符串 包含,
 */
function isString($string)
{
    if (is_string($string) && (strpos($string, ',') > -1)) {
        return true;
    } else {
        return false;
    }
}

/*
 *  字符串通過，分隔成数组
 */
function stringToArray($string)
{
    if (!isString($string)) {
        return exit('不合法的字符串');
    }
    return explode(',', $string);
}

/*
 * crul请求
 */
function doCurlPostRequest($url, $data = [], $header = [], $timeout = 3)
{
    if ($url == '' || $timeout <= 0) {
        return false;
    }
    $curl = curl_init((string)$url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, (int)$timeout);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header); //添加自定义的http header
    $abcd = curl_exec($curl);
    if (curl_errno($curl)) {
        $ddsd = curl_getinfo($curl);
        file_put_contents("d:/phpStudy/WWW/log/error" . date('Y-m-d') . ".txt", date('H:i:s') . curl_errno($curl) . "    \t\r\n", FILE_APPEND);
        file_put_contents("d:/phpStudy/WWW/log/error" . date('Y-m-d') . ".txt", date('H:i:s') . print_r($ddsd, true) . "    \t\r\n", FILE_APPEND);
    }
    curl_close($curl);
    return $abcd;
}

function doCurlGetRequest($url)
{
    if ($url == '') {
        return false;
    }
    $curl_handle = curl_init((string)$url);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    $query = curl_exec($curl_handle);
    if (curl_errno($curl_handle)) {
        $ddsd = curl_getinfo($curl_handle);
        file_put_contents("d:/phpStudy/WWW/log/error" . date('Y-m-d') . ".txt", date('H:i:s') . curl_errno($curl_handle) . "    \t\r\n", FILE_APPEND);
        file_put_contents("d:/phpStudy/WWW/log/error" . date('Y-m-d') . ".txt", date('H:i:s') . print_r($ddsd, true) . "    \t\r\n", FILE_APPEND);
    }
    curl_close($curl_handle);
    return $query;
}

function saveTotxt($string,$name){
    if(!is_string($string)){
        $string = json_encode($string);
    }
   file_put_contents("d:/phpStudy/WWW/log/" .$name.'plan'. date('Y-m-d') . ".txt",
    date('H:i:s') .$string." \t\r\n",FILE_APPEND);
}


/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj)
{
    $obj = (array)$obj;
//    foreach ($obj as $k=> $v) {
//        if (gettype($v) == 'resource') {
//            return;
//        }
//        if (gettype($v) == 'object' || gettype($v) == 'array') {
//            $obj[$k] = (array)object_to_array($v);
//        }
//    }
    return $obj;
}
