<?php
ini_set('date.timezone','Asia/Shanghai');

class Api
{
    //建设银行龙支付加密验签服务
    protected static $ccb_socket_host = "127.0.0.1";
    protected static $ccb_socket_port = 55533;
    //建设银行龙支付停车费无感支付扣款网关-正式
    protected static $ccb_entry = "https://ibsbjstar.ccb.com.cn/CCBIS/B2CMainPlat_00_ENPAY";

    public function __construct() {
    }

    /**
     * 无感支付-支付网关
     * $param = array(
     *      "MERFLAG" => 1,
     *      "MERCHANTID" => "000000000000000",
     *      "POSID" => "000000000",
     *      "TERMNO1" => "",
     *      "TERMNO2" => "",
     *      "BRANCHID" => "000000000",
     *      "ORDERID" => "00000800000",
     *      "AUTHNO" => "京A88888",
     *      "AMOUNT" => "0.01",
     *      "TXCODE" => "WGZF00",
     *      "PROINFO" => "停车费0.01元",
     *  );
     */
    public static function doPay($param)
    {
        $resArr = self::doReq($param);
        $signCheck = self::toUrlParams(
            array(
                "RESULT" => $resArr["RESULT"],
                "ORDERID" => $resArr["ORDERID"],
                "AMOUNT" => $resArr["AMOUNT"],
                "TRACEID" => $resArr["TRACEID"],
                "SIGN" => $resArr["SIGN"],
            )
        );
        $result = self::getDataFromServer(self::$ccb_socket_host, self::$ccb_socket_port, "SIGN|$signCheck\n");
        if ($result == "Y") {
            return $resArr;
        } else {
            return array("msg" => "验签失败！");
        }
    }

    /**
     * 无感支付-授权预查询网关
     * $param = array(
     *      "MERFLAG" => 1,
     *      "MERCHANTID" => "000000000000000",
     *      "POSID" => "000000000",
     *      "TERMNO1" => "",
     *      "TERMNO2" => "",
     *      "BRANCHID" => "000000000",
     *      "AUTHNO" => "京A88888",
     *      "TXCODE" => "WGZF01",
     *  );
     */
    public static function preQuery($param)
    {
        $resArr = self::doReq($param);
        $signCheck = self::toUrlParams(
            array(
                "AUTHNO" => $resArr["AUTHNO"],
                "AUTHSTATUS" => $resArr["AUTHSTATUS"],
                "TRACEID" => $resArr["TRACEID"],
                "SIGN" => $resArr["SIGN"],
            )
        );
        $result = self::getDataFromServer(self::$ccb_socket_host, self::$ccb_socket_port, "SIGN|$signCheck\n");
        if ($result == "Y") {
            return $resArr;
        } else {
            return array("msg" => "验签失败！");
        }
    }

    /**
     * 执行无感支付相关网关
     */
    private static function doReq($param)
    {
        $urlParam = self::toUrlParams($param);
        $ccbParam = self::getDataFromServer(self::$ccb_socket_host, self::$ccb_socket_port, "ENCRYPTOR|$urlParam\n");
        $requestParam = array(
            "BRANCHID" => $param["BRANCHID"],
            "MERCHANTID" => $param["MERCHANTID"],
            "POSID" => $param["POSID"],
            "ccbParam" => $ccbParam,
        );
        $response = self::postJsonCurl(self::toUrlParams($requestParam), self::$ccb_entry);
        return json_decode($response, true);
    }

    /**
     * 格式化参数格式化成url参数
     */
    private static function toUrlParams($signArr)
    {
        $buff = "";
        //ksort($signArr);
        foreach ($signArr as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 以post方式提交json到对应的接口url
     * 
     * @param string $address  地址
     * @param string $service_port  端口
     * @param bool $send_data 发送的数据
     * @return 成功时返回
     */
    private static function getDataFromServer($address, $service_port, $send_data)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket < 0) {
            return "socket fail msg: " . socket_strerror($socket);
        }
        $result = socket_connect($socket, $address, $service_port);
        if ($result < 0) {
            return "SOCKET fail msg: ($result) " . socket_strerror($result);
        }
        socket_write($socket, $send_data, strlen($send_data));
        $out = socket_read($socket, 2048);
        socket_close($socket);
        return str_replace("\n", '', $out);
    }

    /**
     * 以post方式提交json到对应的接口url
     * 
     * @param string $json  需要post的json数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws CmbException
     */
    private static function postJsonCurl($json, $url, $useCert = false, $second = 30)
    {        
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        
        //如果有配置代理这里就设置代理
        if(false && CmbConfig::CURL_PROXY_HOST != "0.0.0.0" 
            && CmbConfig::CURL_PROXY_PORT != 0){
            curl_setopt($ch,CURLOPT_PROXY, CmbConfig::CURL_PROXY_HOST);
            curl_setopt($ch,CURLOPT_PROXYPORT, CmbConfig::CURL_PROXY_PORT);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    
        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, '');
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, '');
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else { 
            $error = curl_errno($ch);
            curl_close($ch);
            return "curl error:$error";
        }
    }
}