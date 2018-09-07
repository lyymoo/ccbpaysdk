<?php
/**
 * - 建行龙支付停车费无感预查询网关
 * - 入参如下:
 * merchantId
 *   商户编号
 * posId
 *   柜台号
 * branchId
 *   分行号
 * plateId
 *   车牌号
 * key
 *   md5("$merchantId|$posId|$branchId|$plateId|{混淆字符串}")
 */
header('Content-Type:application/json; charset=utf-8');
ini_set('date.timezone','Asia/Shanghai');
//error_reporting(E_ALL);
//ini_set("display_errors","On");
require_once "api.php";
require_once 'log.php';

//初始化日志
$logHandler= new CLogFileHandler("logs/".date('Y-m-d').'.ccb.log');
$log = Log::Init($logHandler, 15);

Log::DEBUG("ccb insensible prequery:" . json_encode($_GET, JSON_UNESCAPED_UNICODE));
$merchantId = isset($_GET['merchantId']) ? $_GET['merchantId'] : '';
$posId = isset($_GET['posId']) ? $_GET['posId'] : '';
$branchId = isset($_GET['branchId']) ? $_GET['branchId'] : '';
$plateId = isset($_GET['plateId']) ? $_GET['plateId'] : '';
$key = isset($_GET['key']) ? $_GET['key'] : '';

if ($key != md5("$merchantId|$posId|$branchId|$plateId|{混淆字符串}")) {
    echo json_encode(array(
        'code' => -1,
        'msg' => 'KEY校验失败，请检查参数',
    ));
    exit(0);
}

$param = array(
    "MERFLAG" => 1,
    "MERCHANTID" => $merchantId,
    "POSID" => $posId,
    "TERMNO1" => "",
    "TERMNO2" => "",
    "BRANCHID" => $branchId,
    "AUTHNO" => $plateId,
    "TXCODE" => "WGZF01",
);
$result = Api::preQuery($param);
Log::DEBUG("prequery result:" . json_encode($result, JSON_UNESCAPED_UNICODE));
echo json_encode($result);
exit(0);
