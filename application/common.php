<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Db;
// 应用公共文件

// 关闭部分错误提示(变量未定义)
error_reporting(E_ERROR | E_PARSE );

/**
 * JSON 返回成功数据
 * @param  string $param1    提示信息
 * @param  string $param2    返回数据
 */
function succ($param1 = "", $param2 = ""){
    if(is_array($param1)){
        return_json("", "SUCCESS", $param1);
    }else{
        return_json($param1, "SUCCESS", $param2);
    }
}

/**
 * JSON 返回失败数据
 */
function err($msg = ""){
    return_json($msg, "FAIL");
}

/**
 * JSON 返回数据
 * @param  string $msg       提示信息
 * @param  string $code      状态码
 * @param  array $result     返回数据
 */
function return_json($msg = '', $code = "SUCCESS", $result = ""){
    if(empty($msg)) $msg = $code == "SUCCESS" ? "返回成功" : "返回失败";
    $response = array(
        "returnMsg"   => $msg,
        "returnCode"  => $code, 
    );
    $result && $response["result"] = $result;
    echo json_encode($response);
    exit();
}

// 导出excel
function export($width,$topTitle,$bottomTitle,$header,$param,$without_num='',$extra_height=''){
     //引入PHPExcel库文件
    Vendor('PHPExcel.PHPExcel');
    // $param = session('param');
    $res1 = count($param);
    //创建新的PHPExcel对象
    $objPHPExcel = new \PHPExcel();
    //Excel表格式
    $objPHPExcel->getProperties()->setCreator("ctos")
                ->setLastModifiedBy("ctos")
                ->setTitle("sinohst Office 2007 XLSX Document")
                ->setSubject("sinohst Office 2007 XLSX Document")
                ->setDescription("sinohst document for Office 2007 XLSX")
                ->setKeywords("sinohst")
                ->setCategory("Test result file");
    //设置表格宽度
        //如果列比较少 那就直接用26个字母即可 如果超过了26列 那就用复杂的
        if(count($width)>26){
            $letter1 = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
            $letter2 = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
            array_unshift($letter2, '');
            $letter=array();
            $len1=count($letter1);
            $len2=count($letter2);
            for($i=0;$i<$len2;$i++){
                for($ii=0;$ii<$len1;$ii++){
                    $letter[]=$letter2[$i].$letter1[$ii];
                }
            }
        }else{
            $letter = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        }

        foreach ($letter as $key => $value) {
            $col_width[$key]['letter'] = $value;
        }
        $end = implode(array_slice($letter,count($width)-1,1));
        foreach ($width as $key => $value) {
            $col_width[$key]['number'] = $value;
        }
        foreach ($col_width as $key => $value) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($value['letter'])->setWidth($value['number']);
        }

    //设置行高度  
        $this_height=20;
        if($extra_height){
            $this_height+=$extra_height;
        }
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(($this_height+2));
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight($this_height);
        for ($i=3; $i<count($res1)+3; $i++) {
            $objPHPExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(18);
        }
        // $rr = "A".$i.":".$end.$i;
        // dump($rr);die;
     //设置字体样式  
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(20);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$end.'1')->getFont()->setBold(true);
        for ($i=2; $i<count($res1)+2; $i++) {
            $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);
            $objPHPExcel->getActiveSheet()->getStyle("A".$i.":".$end.$i)->getFont()->setBold(true);
        }
        for ($i=2; $i<count($res1)+3; $i++) {
            $objPHPExcel->getActiveSheet()->getStyle("A".$i.":".$end.$i)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        }
        $objPHPExcel->getActiveSheet()->getStyle('A2:'.$end.'2')->getAlignment()->setWrapText(true);//表示可换行

    //设置水平居中
    for ($i=1; $i<count($res1)+3; $i++) { 
        $objPHPExcel->getActiveSheet()->getStyle("A".$i.":".$end.$i)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    }
    $objPHPExcel->getActiveSheet()->mergeCells('A1:'.$end.'1');
    //设置表头名称
    foreach ($letter as $key => $value) {
        $col_header[$key]['letter'] = $value.'2';
    }
    foreach ($header as $key => $value) {
        $col_header[$key]['header'] = $value;
    }
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('A1', $topTitle);
        foreach ($col_header as $key => $value) {
            if(count($value) == 2){
                $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue($value['letter'], $value['header']);
            }        
        }
        $arr = array();
        foreach ($param[0] as $key => $v) {
            array_push($arr, $key);
        }
        foreach ($letter as $key => $value) {
            $rr[$key]['a'] = $value;
            $rr[$key]['b'] = $arr[$key];
        }
        $rownum=1;
        for ($i = 0; $i < count($param); $i++) {
            foreach ($rr as $key => $value) {
                if($without_num){
                    //不需要自增编号
                    $objPHPExcel->getActiveSheet(0)->setCellValue($value['a'] . ($i + 3), $param[$i][$value['b']]);
                }else{
                    //需要自增编号
                    if($key == 0){
                        $objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 3), $rownum++);
                    }else{
                        $objPHPExcel->getActiveSheet(0)->setCellValue($value['a'] . ($i + 3), $param[$i][$value['b']]);
                    }    
                }
            }
            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':'.$end . ($i + 3))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':'.$end . ($i + 3))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);            
            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':'.$end . ($i + 3))->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getRowDimension($i + 3)->setRowHeight(16);
        }

        // Rename sheet  
        $objPHPExcel->getActiveSheet()->setTitle($bottomTitle);
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet    
        $objPHPExcel->setActiveSheetIndex(0); 
         // 输出  
       ob_end_clean();
       header('Content-Type: application/vnd.ms-excel');  
       header('Content-Disposition: attachment;filename="' . date('Ymd-His') . '.xls"');  
       header('Cache-Control: max-age=0');  
       $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
       ob_end_clean();
       $info = $objWriter->save('php://output');
       return $info;
}

// 删除文件夹中的所有文件(不删除文件夹)
function delFileByDir($dir) {
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file != "." && $file != "..") {
            $fullpath = $dir . "/" . $file;
            if (is_dir($fullpath)) {
                delFileByDir($fullpath);
            } else {
                unlink($fullpath);
            }
        }
    }
    closedir($dh);
}

//删除文件/文件夹
function delFileAndDir($path) {
    if(is_dir($path)) {
        $file_list= scandir($path);
        foreach ($file_list as $file){
            if( $file!='.' && $file!='..'){
                delFileAndDir($path.'/'.$file);
            }
        }
        @rmdir($path);  //这种方法不用判断文件夹是否为空,  因为不管开始时文件夹是否为空,到达这里的时候,都是空的     
    } else {
        @unlink($path);    //这两个地方最好还是要用@屏蔽一下warning错误,看着闹心
    }
}

/**
 * 更新缓存文件
 * @param  array  $cfg       配置数据
 * @param  string $filename  配置文件名
 * @param  boolean $is_replace  是否替换
 * @return boolean           操作结果
 */
function update_config($cfg,$filename,$is_replace=false) {
    $file = CONF_PATH . $filename;
    if (is_file($file) && is_writable($file)) {
        if($is_replace){
            $config = $cfg;//完全替换
        }else{
            $config = require $file;
            $config = array_merge($config, $cfg);//不替换 仅合并 
        }
        file_put_contents($file, "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX);        
        @unlink(RUNTIME_FILE);
        return true;
    } else {
        return false;
    }
}

//获取批量添加的sql语句
//table_name 表名 
//data 二维数组格式 key为要添加的字段 value为一维数组 多个值
function insert_table($table_name,$data=array()){
    $field_arr=array_keys($data);//插入的字段的一维数组
    $field=' ('.implode(',',$field_arr).') ';
    $sql="insert into ".$table_name.$field."values ";
    $length=count($data[$field_arr[0]]);//预插入的数据条数
    //循环的次数应该与length的个数相同 要插入几条 就循环几次
    for($index=0;$index<$length;$index++){
        //循环每个字段 如果该字段是第一个字段 那就带上前括号 
        //如果该字段是最后一个字段 那就带上后括号 否则不带括号
        foreach($field_arr as $key=>$value){
            if($key==0){
                if($key==(count($field_arr)-1)){
                    $sql.="('".$data[$value][$index]."'),";//是第一个字段 且是最后一个字段 直接用括号结尾
                }else{
                    $sql.="('".$data[$value][$index];//是第一个字段 但不是最后一个字段 不用括号结尾
                }
            }else if($key==(count($field_arr)-1)){
                $sql.="','".$data[$value][$index]."'),";//不是第一个字段 但是最后一个字段 用括号结尾
            }else{
                $sql.="','".$data[$value][$index];//不是第一个字段 且不是最后一个字段 不结尾
            }
        }    
    }
    return trim($sql,',');
}

//获取批量修改的sql语句
//table_name 表名 
//data 二维数组格式 key为要修改的字段 value为一维数组 多个值
//field where条件的字段名
//field_data 一位数组 field字段对应的多个值
function update_table($table_name,$data=array(),$field,$field_data){
    $sql="update ".$table_name." set ";
    foreach($data as $key=>$value){
        $sql.=$key."= case ".$field;
        $length=count($value);//因为field_data中的数据个数与$value的数据个数肯定是一致的 因此选用一个即可
        for($i=0;$i<$length;$i++){
            $sql.=" when '".$field_data[$i]."' then '".$value[$i]."'";
        }
        $sql.=" end,";
    }
    $sql=trim($sql,',');
    $sql.=" where ".$field." in (".implode(',',$field_data).")";
    return $sql;
}

/**
 * 复制文件夹
 * @param $source
 * @param $dest
 */
function copydir($source, $dest){
    if (!file_exists($dest)) mkdir($dest);
    $handle = opendir($source);
    while (($item = readdir($handle)) !== false) {
        if ($item == '.' || $item == '..') continue;
        $_source = $source . '/' . $item;
        $_dest = $dest . '/' . $item;
        if (is_file($_source)) copy($_source, $_dest);
        if (is_dir($_source)) copydir($_source, $_dest);
    }
    closedir($handle);
}

//生成controller文件 module_name模块名称 controller_name控制器名称 extends_name要继承的控制器名称(非必传)
function add_controller_file($module_name,$controller_name,$extends_name='') {
    $myfile = fopen(ROOT_PATH."application/".$module_name."/controller/".$controller_name.".php", "w");
    if(!$myfile){
        return false;
    }
    if($extends_name){
        $txt2="\nuse think\Db;\nClass ".$controller_name." extends ".$extends_name." {";
    }else{
        $txt2="\nuse think\Controller;\nuse think\Db;\nClass ".$controller_name." extends Controller {";
    }
    $txt = "<?php \nnamespace app\\".$module_name."\controller;".$txt2."\npublic function index(){\n\n}\n}";
    fwrite($myfile, $txt);
    fclose($myfile);
    return true;
}

/**
 * 返回微信高级接口对象
 * @return Object  
 */
function getWechatAuth(){
    $wxcfg = Db::name('wxconfig')->find(1);
    empty($wxcfg) && exit('请在后台设置微信相关参数');
    if((time()-200) > $wxcfg['expires_in'] || empty($wxcfg['access_token'])){
        $wechatAuth = new \Wechat\WechatAuth($wxcfg['appid'], $wxcfg['appsecret']);
        $result = $wechatAuth->getAccessToken();

        if(isset($result['errcode'])) err($result);
        $upd=array('id'=>1);
        $wxcfg['expires_in'] = $upd['expires_in'] = time() + $result['expires_in'];
        $wxcfg['access_token'] = $upd['access_token'] = $result['access_token'];
        if(!Db::name('wxconfig')->update($upd))exit('更新微信配置缓存失败');
        unset($wechatAuth);
    }
    return new \Wechat\WechatAuth($wxcfg['appid'], $wxcfg['appsecret'], $wxcfg['access_token']);
}

// 返回微信jsapi参数
function getJsApiTicket(){
    $ticket = '';
    $wxcfg = Db::name('wxconfig')->find(1);
    empty($wxcfg) && exit('请在后台设置微信相关参数');
    if((time()-200) > $wxcfg['JsApiTicket_expires_in'] || empty($wxcfg['JsApiTicket'])){
        $wechatAuth = getWechatAuth();
        $result = json_decode($wechatAuth->getJsApiTicket(),true);
        if($result['errcode'] !== 0) err($result);
        $upd=array('id'=>1);
        $wxcfg['JsApiTicket_expires_in'] = $upd['JsApiTicket_expires_in']    = time() + $result['expires_in'];
        $wxcfg['JsApiTicket']            = $upd['JsApiTicket']    = $ticket = $result['ticket'];
        if(!Db::name('wxconfig')->update($upd))exit('更新微信配置缓存失败');
        unset($wechatAuth);
    }else{
        $ticket = $wxcfg['JsApiTicket'];
    }
    return $ticket;
}

// 返回微信卡券api参数
function getCardApiTicket(){
    $ticket = '';
    $wxcfg = Db::name('wxconfig')->find(1);
    empty($wxcfg) && exit('请在后台设置微信相关参数');
    if((time()-200) > $wxcfg['CardApiTicket_expires_in'] || empty($wxcfg['CardApiTicket'])){
        $wechatAuth = getWechatAuth();
        $result = json_decode($wechatAuth->getCardApiTicket(),true);
        if($result['errcode'] !== 0) err($result);
        $upd=array('id'=>1);
        $wxcfg['CardApiTicket_expires_in'] = $upd['CardApiTicket_expires_in']    = time() + $result['expires_in'];
        $wxcfg['CardApiTicket']            = $upd['CardApiTicket']    = $ticket = $result['ticket'];
        if(!Db::name('wxconfig')->update($upd))exit('更新微信配置缓存失败');
        unset($wechatAuth);
    }else{
        $ticket = $wxcfg['CardApiTicket'];
    }
    return $ticket;
}

// 生成微信jsapi参数
function getSignPackage() {
    $appid=Db::name('wxconfig')->value('appid');
    $jsapiTicket = getJsApiTicket();
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $timestamp = time();
    $nonceStr  = uniqid();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $appid,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string,
    );
    return $signPackage;
}

// 获取会员卡签名包
function getCardSignPackage($timestamp,$nonceStr,$card_id,$openid) {
    $apiTicket = getCardApiTicket();
    $code       = '';
    $arr        = array($apiTicket, $code, $timestamp, $nonceStr, $card_id, $openid);
    sort($arr, SORT_STRING);
    $signature  = sha1(implode($arr));
    return $signature;
}

// get远程
function curlGets($url){
    $ch = curl_init();
    $header = "Accept-Charset: utf-8";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $temp = curl_exec($ch);
    return $temp;
}

// post远程
function curlPosts($url,$data=array()){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    // POST数据
    curl_setopt($ch, CURLOPT_POST, 1);
    // 把post的变量加上
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

/**
 * 发送HTTP请求方法，目前只支持CURL发送请求
 * @param  string $url    请求URL
 * @param  array  $param  GET参数数组
 * @param  array  $data   POST的数据，GET请求时该参数无效
 * @param  string $method 请求方法GET/POST
 * @return array          响应数据
 */
function http($url, $param, $data = '', $method = 'GET'){
    $opts = array(
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    );

    /* 根据请求类型设置特定参数 */
    $opts[CURLOPT_URL] = $url . '?' . http_build_query($param);
    if(strtoupper($method) == 'POST'){
        $opts[CURLOPT_POST] = 1;
        $opts[CURLOPT_POSTFIELDS] = $data;
        
        if(is_string($data)){ //发送JSON数据
            $opts[CURLOPT_HTTPHEADER] = array(
                'Content-Type: application/json; charset=utf-8',  
                'Content-Length: ' . strlen($data),
            );
        }
    }

    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    //发生错误
    if($error) return $error;
    return  $data;
}

// 生成订单号
function gen_order_id($prefix=''){
    return $prefix.date("ymdHis",time()).gen_vcode(6);
}

function gen_vcode($size){
    $code = "";
    for($i=0;$i<$size;$i++){
        $code = $code.mt_rand(0, 9);
    }
    return $code;
}

//生成优惠券码
function build_coupon_no($salt = ''){
    return strtoupper(substr(md5($salt.rand()),0,8).substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8));
}

//emoji表情过滤
function filterEmoji($str){
    $str = preg_replace_callback('/./u',function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
    $str);
    return $str;
}

// 获取access_token 先查询数据库 如果有access_token且还没过期 那么就直接使用这个旧的
// 如果过期了 就重新获取并保存 然后使用这个新获取到的
function getAccessToken(){
    $wxcfg = Db::name('wxconfig')->find(1);
    empty($wxcfg) && exit('请在后台设置微信相关参数');
    if((time()-200) > $wxcfg['expires_in'] || empty($wxcfg['access_token'])){
        $wechatAuth = new \Wechat\WechatAuth($wxcfg['appid'], $wxcfg['appsecret']);
        $result = $wechatAuth->getAccessToken();

        if(isset($result['errcode'])) err($result);
        $upd=array('id'=>1);
        $wxcfg['expires_in'] = $upd['expires_in'] = time() + $result['expires_in'];
        $wxcfg['access_token'] = $upd['access_token'] = $result['access_token'];
        if(!Db::name('wxconfig')->update($upd))exit('更新微信配置缓存失败');
        return $result['access_token'];
    }else{
        return $wxcfg['access_token'];
    }
}

// 获取微博jsapi
function getWeiboApi($index,$access_token){
    $config=config('weibo')[$index];
    return new \Weibo\SaeTClientV2( $config['WB_AKEY'],$config['WB_SKEY'] , $access_token );
}