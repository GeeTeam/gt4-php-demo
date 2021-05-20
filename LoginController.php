<?php
error_reporting(0);
// 1.初始化极验参数信息
$captcha_id = "647f5ed2ed8acb4be36784e01556bb71";
$captcha_key = "b09a7aafbfd83f73b35a9b530d0337bf";
$api_server = "http://gcaptcha4.geetest.com";

// 2.获取用户验证后前端传过来的验证流水号参数
$lot_number = $_GET['lot_number'];
$captcha_output = $_GET['captcha_output'];
$pass_token = $_GET['pass_token'];
$gen_time = $_GET['gen_time'];

// 3.生成签名
// 生成签名使用标准的hmac算法，使用用户当前完成验证的流水号lot_number作为原始消息message，使用客户验证私钥作为key
// 采用sha256散列算法将message和key进行单向散列生成最终的签名
$sign_token = hash_hmac('sha256', $lot_number, $captcha_key);

// 4.上传校验参数到极验二次验证接口, 校验用户验证状态
// captcha_id 参数建议放在 url 后面, 方便请求异常时可以在日志中根据id快速定位到异常请求
$query = array(
    "lot_number" => $lot_number,
    "captcha_output" => $captcha_output,
    "pass_token" => $pass_token,
    "gen_time" => $gen_time,
    "sign_token" => $sign_token
);
$url = sprintf($api_server . "/validate" . "?captcha_id=%s", $captcha_id);
$res = post_request($url,$query);
$obj = json_decode($res,true);

// 5.根据极验返回的用户验证状态, 网站主进行自己的业务逻辑
echo sprintf('{"login":"%s","reason":"%s"}', $obj['result'], $obj['reason']);

// 注意处理接口异常情况，当请求极验二次验证接口异常时做出相应异常处理
// 保证不会因为接口请求超时或服务未响应而阻碍业务流程
function post_request($url, $postdata) {
    $data = http_build_query($postdata);

    $options    = array(
        'http' => array(
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'content' => $data,
            'timeout' => 5
        )
    );
    $context = stream_context_create($options);
    $result    = file_get_contents($url, false, $context);
    if($http_response_header[0] != 'HTTP/1.1 200 OK'){
        $result = array(
            "result" => "success",
            "reason" => "request geetest api fail"
        );
        return json_encode($result);
    }else{
        return $result;
    }
}

?>
