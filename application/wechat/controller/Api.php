<?php
namespace app\wechat\controller;
use think\Controller;
use think\Db;
use Think\Log;
header("Content-type: text/html; charset=utf-8");  
class Api extends Controller{
    /**
     * 微信消息接口入口
     * 所有发送到微信的消息都会推送到该操作
     * 所以，微信公众平台后台填写的api地址则为该操作的访问地址
     */
    public function index(){
        

    ob_clean();
    // echo $_GET['echostr'];exit;
    // Log::write(var_export($_GET,true),'WARN');
        //调试
        try{

            $config = Db::name('wxconfig')->find(1);
            $appid = $config['appid']; //AppID(应用ID)
            $token = $config['token']; //微信后台填写的TOKEN
            // echo $token;
            // $data = file_get_contents("php://input");
            // Log::write(var_export($data,true),'WARN');
            //$crypt = 'q6FPCUoCQWaOiR3UUe5RfQu8A7hlJcMW4BnNyH9z2il'; //消息加密KEY（EncodingAESKey）
            
            /* 加载微信SDK */
            $wechat = new \Wechat\Wechat($token, $appid);
            
            /* 获取请求信息 */
            $data = $wechat->request();

            if($data && is_array($data)){
                /**
                 * 你可以在这里分析数据，决定要返回给用户什么样的信息
                 * 接受到的信息类型有10种，分别使用下面10个常量标识
                 * Wechat::MSG_TYPE_TEXT       //文本消息
                 * Wechat::MSG_TYPE_IMAGE      //图片消息
                 * Wechat::MSG_TYPE_VOICE      //音频消息
                 * Wechat::MSG_TYPE_VIDEO      //视频消息
                 * Wechat::MSG_TYPE_SHORTVIDEO //视频消息
                 * Wechat::MSG_TYPE_MUSIC      //音乐消息
                 * Wechat::MSG_TYPE_NEWS       //图文消息（推送过来的应该不存在这种类型，但是可以给用户回复该类型消息）
                 * Wechat::MSG_TYPE_LOCATION   //位置消息
                 * Wechat::MSG_TYPE_LINK       //连接消息
                 * Wechat::MSG_TYPE_EVENT      //事件消息
                 *
                 * 事件消息又分为下面五种
                 * Wechat::MSG_EVENT_SUBSCRIBE    //订阅
                 * Wechat::MSG_EVENT_UNSUBSCRIBE  //取消订阅
                 * Wechat::MSG_EVENT_SCAN         //二维码扫描
                 * Wechat::MSG_EVENT_LOCATION     //报告位置
                 * Wechat::MSG_EVENT_CLICK        //菜单点击
                 */

                //记录微信推送过来的数据
                file_put_contents(ROOT_PATH.'public/uploads/wechat/data.json', json_encode($data));

                /* 响应当前请求(自动回复) */
                //$wechat->response($content, $type);

                /**
                 * 响应当前请求还有以下方法可以使用
                 * 具体参数格式说明请参考文档
                 * 
                 * $wechat->replyText($text); //回复文本消息
                 * $wechat->replyImage($media_id); //回复图片消息
                 * $wechat->replyVoice($media_id); //回复音频消息
                 * $wechat->replyVideo($media_id, $title, $discription); //回复视频消息
                 * $wechat->replyMusic($title, $discription, $musicurl, $hqmusicurl, $thumb_media_id); //回复音乐消息
                 * $wechat->replyNews($news, $news1, $news2, $news3); //回复多条图文消息
                 * $wechat->replyNewsOnce($title, $discription, $url, $picurl); //回复单条图文消息
                 * 
                 */
                
                //执行处理
                $this->process($wechat, $data);
            }
        } catch(\Exception $e){
            file_put_contents(ROOT_PATH.'public/uploads/wechat/error.json', json_encode($e->getMessage()));
        }
        
    }

    /**
     * 处理微信消息
     * @param  Object $wechat Wechat对象
     * @param  array  $data   接受到微信推送的消息
     */
    private function process($wechat, $data){
        // Log::write(var_export($data,true),'data');
        $replyModel = Db::name('wxreply');
        $replyData  = array();
        $replyField = 'replytype,replycontent,service,mid';
        $replyDefualt  = $replyModel->field($replyField)->where(array('is_default'=>1))->find();
        $userModel=$userModel;
        switch ($data['MsgType']) {
            case Wechat::MSG_TYPE_EVENT:
                switch ($data['Event']) {
                    case Wechat::MSG_EVENT_SUBSCRIBE:

                        Log::write(var_export($data,true),'WARN');
                        $replyData = $replyModel->field($replyField)->where(array('is_subscribe'=>1))->find();
                        $userid = $userModel->where(array('wxid'=>$data['FromUserName']))->value('userid');
                        //注册
                        if(!$userid){
                            $insrtData = array(
                                'register_time'=>NOW_TIME,
                                'is_follow'=>1,
                                'wxid'=>$data['FromUserName'],
                                );
                            $userid = $userModel->insertGetId($insrtData);
                        }else{
                            $userModel->where(array('userid'=>$userid))->update(array('is_follow'=>1));
                        }
                        //处理上级关系
                        if($data['EventKey'] ){
                            $leader_id = $userModel->where(array('userid'=>$userid))->value('leader_id');
                            $vip = $userModel->where(array('wxid'=>$data['FromUserName']))->value('vip');
                            $newleadid = substr($data['EventKey'],strpos($data['EventKey'],'_')+1);
                            if($newleadid && empty($leader_id) && $vip == 0 && $newleadid != $userid){ //更新上级关系
                                $userModel->where(array('userid'=>$userid))->update(array('leader_id'=>$newleadid));
                            }
                        }

                        break;

                    case Wechat::MSG_EVENT_UNSUBSCRIBE:
                    //更新关注状态
                        $userModel->where(array('wxid'=>$data['FromUserName']))->update(array('is_follow'=>0));
                        break;
                    case Wechat::MSG_EVENT_SCAN:
                        //处理上级关系
                        Log::write(var_export($data,true),'WARN');
                        
                        if($data['EventKey']){
                            $user =  $userModel->where(array('wxid'=>$data['FromUserName']))->find();
                            $newleadid = intval($data['EventKey']);
                            if($newleadid && empty($user['leader_id'])  && $user['vip'] == 0 && $user['userid'] != $newleadid){ //更新上级关系
                                $userModel->where(array('wxid'=>$data['FromUserName']))->update(array('leader_id'=>$newleadid));
                            }
                        }
                        break;
                    default:
                        // $replyData = $replyDefualt;
                        break;
                }
                break;

            case Wechat::MSG_TYPE_TEXT:
                $replyData = $replyModel->field($replyField)->where(array('keyword'=>array('like','%|'.$data['Content'].'|%')))->find();
                break;
            default:
                $replyData = $replyDefualt;
                break;
        }

        // $replyData = empty($replyData) ? $replyDefualt : $replyData;
        if($replyData){
            //更新使用次数
            $replyModel->where(array('mid'=>$replyData['mid']))->setInc('visit');
            $this->_reply($wechat, $replyData);
        }else{
            exit('success');
        }
    }
    /**
     * 处理回复消息
     * @date   2016-06-03
     * @param  Object $wechat Wechat对象
     * @param  Array  $reply  回复消息
     */
    private function _reply($wechat, $reply){
        // Log::write(var_export($reply,true),'replyData');
        if($reply['service'] == 1){ //客服接管
// $xml = <<<DAT
// <xml>
// <ToUserName><![CDATA[oted9wpvb_90EyInxGIRuIbubep8]]></ToUserName>
// <FromUserName><![CDATA[wx3c88b63d8bb5c552]]></FromUserName>
// <CreateTime>1465181368</CreateTime>
// <MsgType><![CDATA[transfer_customer_service]]></MsgType>
// </xml>
// DAT;
// echo $xml;
        }else{ //回复
            switch ($reply['replytype']) {
                case 'text':
                    $wechat->replyText(htmlspecialchars_decode($reply['replycontent']));
                    break;
                case 'news':
                    $news = json_decode(htmlspecialchars_decode($reply['replycontent']),true);
                    // if(0 == count($news)) break;
                    $articles = $this->_transNews($news);
                    // log::write(var_export($articles,true),'lxy');
                    call_user_func_array(array($wechat,'replyNews'),$articles);
                    break;
                // default:
                //     $wechat->replyText("欢迎访问公众平台！您输入的内容是：{$data['Content']}");
                //     break;
                // case 'image':
                //     //$media_id = $this->upload('image');
                //     $media_id = '1J03FqvqN_jWX6xe8F-VJr7QHVTQsJBS6x4uwKuzyLE';
                //     $wechat->replyImage($media_id);
                //     break;

                // case 'voice':
                //     //$media_id = $this->upload('voice');
                //     $media_id = '1J03FqvqN_jWX6xe8F-VJgisW3vE28MpNljNnUeD3Pc';
                //     $wechat->replyVoice($media_id);
                //     break;

                // case 'video':
                //     //$media_id = $this->upload('video');
                //     $media_id = '1J03FqvqN_jWX6xe8F-VJn9Qv0O96rcQgITYPxEIXiQ';
                //     $wechat->replyVideo($media_id, '视频标题', '视频描述信息。。。');
                //     break;

                // case 'music':
                //     //$thumb_media_id = $this->upload('thumb');
                //     $thumb_media_id = '1J03FqvqN_jWX6xe8F-VJrjYzcBAhhglm48EhwNoBLA';
                //     $wechat->replyMusic(
                //         'Wakawaka!', 
                //         'Shakira - Waka Waka, MaxRNB - Your first R/Hiphop source', 
                //         'http://wechat.zjzit.cn/Public/music.mp3', 
                //         'http://wechat.zjzit.cn/Public/music.mp3', 
                //         $thumb_media_id
                //     ); //回复音乐消息
                //     break;
            }
        }
        
    }


    /**
     * 处理图文回复数据
     * @date   2016-06-03
     * @param  array     $newsData 后台添加的图文回复数据
     * @return array               重组后的数据
     */
    private function _transNews($newsData){
        $ret = array();
        $i = 0;
        foreach($newsData as &$n){
            $n['PicUrl'] = ROOT_PATH.'public'.$n['PicUrl'];
            $ret[$i][] = $n['Title'];
            $ret[$i][] = $n['Description'];
            $ret[$i][] = $n['Url'];
            $ret[$i][] = $n['PicUrl'];
            $i++;
        }
        return $ret;
    }
    /**
     * 资源文件上传方法
     * @param  string $type 上传的资源类型
     * @return string       媒体资源ID
     */
    private function upload($type){
        $wxInfo=Db::name('wxconfig')->find(1);
        $appid     = $wxInfo['appid'];
        $appsecret     = $wxInfo['appsecret'];

        $token = session("token");

        if($token){
            $auth = new \Wechat\WechatAuth($appid, $appsecret, $token);
        } else {
            $auth  = new \Wechat\WechatAuth($appid, $appsecret);
            $token = $auth->getAccessToken();

            session(array('expire' => $token['expires_in']));
            session("token", $token['access_token']);
        }

        switch ($type) {
            case 'image':
                $filename = ROOT_PATH.'public/uploads/wechat/image.jpg';
                $media    = $auth->materialAddMaterial($filename, $type);
                break;

            case 'voice':
                $filename = ROOT_PATH.'public/uploads/wechat/voice.mp3';
                $media    = $auth->materialAddMaterial($filename, $type);
                break;

            case 'video':
                $filename    = ROOT_PATH.'public/uploads/wechat/video.mp4';
                $discription = array('title' => '视频标题', 'introduction' => '视频描述');
                $media       = $auth->materialAddMaterial($filename, $type, $discription);
                break;

            case 'thumb':
                $filename = ROOT_PATH.'public/uploads/wechat/music.jpg';
                $media    = $auth->materialAddMaterial($filename, $type);
                break;
            
            default:
                return '';
        }

        if($media["errcode"] == 42001){ //access_token expired
            session("token", null);
            $this->upload($type);
        }

        return $media['media_id'];
    }
}
