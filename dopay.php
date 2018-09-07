<?php
/**
 * - 建行龙支付停车费无感支付网关
 * - 入参如下:
 * merchantId
 *   商户编号
 * posId
 *   柜台号
 * branchId
 *   分行号
 * plateId
 *   车牌号
 * payNo
 *   支付单号
 * amount
 *   支付金额
 * key
 *   md5("$merchantId|$posId|$payNo|$plateId|{混淆字符串}")
 */
header('Content-Type:application/json; charset=utf-8');
ini_set('date.timezone','Asia/Shanghai');
require_once "api.php";
require_once 'log.php';

//初始化日志
$logHandler= new CLogFileHandler("logs/".date('Y-m-d').'.ccb.log');
$log = Log::Init($logHandler, 15);

//接收参数支付参数
Log::DEBUG("ccb insensible dopay:" . json_encode($_GET, JSON_UNESCAPED_UNICODE));
$merchantId = isset($_GET['merchantId']) ? $_GET['merchantId'] : '';
$posId = isset($_GET['posId']) ? $_GET['posId'] : '';
$branchId = isset($_GET['branchId']) ? $_GET['branchId'] : '';
$plateId = isset($_GET['plateId']) ? $_GET['plateId'] : '';
$payNo = isset($_GET['payNo']) ? $_GET['payNo'] : '';
$amount = isset($_GET['amount']) ? $_GET['amount'] : '';
$key = isset($_GET['key']) ? $_GET['key'] : '';

if ($key != md5("$merchantId|$posId|$payNo|$plateId|{混淆字符串}")) {
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
    "ORDERID" => $payNo,
    "AUTHNO" => $plateId,
    "AMOUNT" => $amount,
    "TXCODE" => "WGZF00",
    "PROINFO" => "停车费".$amount."元",
);
$result = Api::doPay($param);
Log::DEBUG("dopay result:" . json_encode($result, JSON_UNESCAPED_UNICODE));
echo json_encode($result);
exit(0);
