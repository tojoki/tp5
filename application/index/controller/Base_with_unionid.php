<?php 
namespace app\index\controller;
use think\Controller;
use think\Db;
header("Content-type:text/html;charset=utf-8");
Class Base extends Controller {
	// 初始化
	protected function _initialize(){
        // 如果是index控制器 说明是公众号 如果是其他控制器 说明是小程序或app或h5
        if(strtolower($this->request->controller())=='index'){
            $this->wxLogin();
            $user = $this->get_fresh_user();
        }
    }

    // 微信授权登录
    protected function wxLogin(){
        // $user = Db::name('user')->find(3);
        // session('USER_PROFILES',$user);
        if(config('site.siteurl')){
            $siteurl=config('site.siteurl');
        }else{
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $siteurl = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        if(!$siteurl) exit('请先设置域名');

        if(session('USER_PROFILES')) return false;
        $code = input("get.code");
        $wechatAuth = getWechatAuth();
        if($code){
            try{
                $token = $wechatAuth -> getAccessToken("code",$code);
            }catch(\Exception $e){
                $redirect = $siteurl.'/'.$this->get_info_self(1);
                header("location:$redirect");
                exit;
            }

            // 因为有unionid的概念 所以先获取用户信息 注意这里不能判断是否有unionid 因为在没进入过小程序且在没授权的情况下第一次进入公众号是肯定没有的
            // 所以和$user一起判断 如果$user为空/公众号openid为空/unionid为空 都表示未授权 此时提示授权
            $wx_profiles = $wechatAuth -> getUserInfo($token['openid']);
            // dump($wx_profiles);die;

            $userModel = Db::name('user');
            $user = $userModel->field('nickname,userid')->where(array('unionid'=>$wx_profiles['unionid']))->find();

            if((empty($user) || $user['openid_oa'] == NULL || !$wx_profiles['unionid'] || !$user['unionid']) && input('scope')  == ''){
                $redirect = $siteurl.'/'.$this->get_info_self(1).'?scope=snsapi_userinfo';
                $RequestCodeURL = $wechatAuth -> getRequestCodeURL($redirect);
                header("location:$RequestCodeURL");
                exit;
            }
            // dump($_SERVER);
            // dump($user);
            $new_nickname=filterEmoji($wx_profiles['nickname'])?filterEmoji($wx_profiles['nickname']):'用户'.time();
            $data = array(
                'nickname'=>$new_nickname,
                'headimg'=>$wx_profiles['headimgurl'],
                'last_login_time'=>time(),
                'openid_oa'=>$token['openid'],
                'sex'=>$wx_profiles['sex']
            );
            if(!$user){ //未注册 额外需要添加注册时间和unionid
                $data['register_time']=time();
                $data['unionid']=$wx_profiles['unionid'];
                $userModel->insert($data);
            }elseif($user['openid_oa'] == NULL){ //资料不全(主要是针对先进入小程序后进入公众号 导致数据库中有unionid和小程序openid但是没有公众号openid的情况)
                $userModel->where(array('unionid'=>$wx_profiles['unionid']))->update($data);
            }else{//数据齐全 直接更新登录时间即可
                $userModel -> where(array('unionid'=>$wx_profiles['unionid'])) -> update(array('last_login_time'=>time()));
            }

            $userdata = $userModel->where(array('unionid'=>$wx_profiles['unionid']))->find();                

            session("USER_PROFILES",$userdata);

        }else{
            $redirect = $siteurl.$this->get_info_self(2);
            // if(!input('scope')){
            //     $RequestCodeURL = $wechatAuth -> getRequestCodeURL($redirect, null, 'snsapi_base');
            // }else{
            //     $RequestCodeURL = $wechatAuth -> getRequestCodeURL($redirect);
            // }
            // Log::write($RequestCodeURL,'RequestCodeURL');
            $RequestCodeURL = $wechatAuth -> getRequestCodeURL($redirect, null, 'snsapi_base');
            header("location:$RequestCodeURL");
            exit;
        }
    }

    // 获取用户信息
    protected function get_fresh_user(){
        $user =  session('USER_PROFILES');
        return Db::name('user')->find($user['userid']);
    }

    // 检查变量
    protected function check_require($keys){
        $keyarr = explode(",",$keys);
        $data = array();
       	foreach($keyarr as $key){
            $value = input($key);
            if($value==''){
                if(strtolower($this->request->controller())=='index'){
                    $this->error('缺少必填项:'.$key);
                }else{
                    $this->error_back('缺少必填项:'.$key);
                }
            }
            $data[$key] = $value;
        }
        return $data;
    }

    // 获取wxjsapi参数
    protected function setjsApi(){
        $signPackage = getSignPackage();
        $this->assign('signPackage',$signPackage);
    }

    // 获取tp3中的__INFO__(type=1)和__SELF__(type=2)
    private function get_info_self($type){
    	if($type==1){
    		if(empty($_SERVER['PATH_INFO'])) {
	            $result='';
	        }else{
	            $result=trim($_SERVER['PATH_INFO'],'/');
	        }
    	}else if($type==2){
    		$result=strip_tags($_SERVER['REQUEST_URI']);
    	}else{
    		$result='';
    	}
    	return $result;
    }

    // 小程序的base部分

    protected function response($data=NULL,$msg='success',$status=0){
        exit(json_encode(array('status'=>$status,'time'=>time(),'msg'=>$msg,'data'=>$data)));
    }
    
    protected function succ_back($msg=''){
        $this->response(NULL,$msg);
    }

    protected function error_back($msg='',$status=1){
        $this->response(NULL, $msg, $status);
    }
    
    protected function check_user(){
        $data = $this->check_require('openid_mp');
        $user = Db::name('user')->alias('u')
             ->where($data)->field('u.*')->find();
        if(empty($user)){
            $this->error_back('登录出错');
        }
        return $user;
    }
    // 小程序的base部分

    // 生成用户逻辑
    // 进入公众号->判断数据库中是否有此unionid的用户->如果没有->生成一条新用户 保存unionid和openid_oa(1)
    //                                              ->如果有->无论openid_oa字段是否有值 都修改用户并追加上openid_oa(2)
    // 进入小程序->判断数据库中是否有此unionid的用户->如果没有->生成一条新用户 保存unionid和openid_mp(3)
    //                                              ->如果有->无论openid_mp字段是否有值 都修改用户并追加上openid_mp(4)
    // 如果先进入公众号再进入小程序 那么执行顺序就是(1)->(4)
    // 如果先进入小程序再进入公众号 那么执行顺序就是(3)->(2)
}