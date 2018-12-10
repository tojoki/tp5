<?php 
namespace app\index\controller;
use think\Controller;
use think\Db;
Class Base extends Controller {
	// 初始化
	protected function _initialize(){
        $this->wxLogin();
        $user = $this->get_fresh_user();
    }

    // 微信授权登录
    protected function wxLogin(){
        $user = Db::name('user')->find(628);
        session('USER_PROFILES',$user);

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

            $userModel = Db::name('user');
            $user = $userModel->field('nickname,userid')->where(array('wxid'=>$token['openid']))->find();

            if((empty($user) || $user['nickname'] == NULL) && input('scope')  == ''){
                $redirect = $siteurl.'/'.$this->get_info_self(1).'?scope=snsapi_userinfo';
                $RequestCodeURL = $wechatAuth -> getRequestCodeURL($redirect);
                header("location:$RequestCodeURL");
                exit;
            }
            // dump($_SERVER);
            // dump($user);
            if(!$user){ //未注册
                $wx_profiles = $wechatAuth -> getUserInfo($token['openid']);
                $new_nickname=filterEmoji($wx_profiles['nickname'])?filterEmoji($wx_profiles['nickname']):'用户'.time();
                $data = array(
                    'nickname'=>$new_nickname,
                    'sex'=>$wx_profiles['sex'],
                    'headimg'=>$wx_profiles['headimgurl'],
                    'register_time'=>time(),
                    'last_login_time'=>time(),
                    'defaultcity'=>$wx_profiles['country'].$wx_profiles['province'].$wx_profiles['city'],
                    'wxid'=>$token['openid'],
                    );
                $userModel->insert($data);
            }elseif($user['nickname'] == NULL){ //资料不全
                $wx_profiles = $wechatAuth -> getUserInfo($token['openid']);
                $new_nickname=filterEmoji($wx_profiles['nickname'])?filterEmoji($wx_profiles['nickname']):'用户'.time();
                $data = array(
                    'nickname'=>$new_nickname,
                    'sex'=>$wx_profiles['sex'],
                    'headimg'=>$wx_profiles['headimgurl'],
                    'last_login_time'=>time(),
                    'defaultcity'=>$wx_profiles['country'].$wx_profiles['province'].$wx_profiles['city'],
                    );
                $userModel->where(array('wxid'=>$token['openid']))->update($data);
            }else{//登录
                $userModel -> where(array('wxid'=>$token['openid'])) -> update(array('last_login_time'=>time()));
            }

            $userdata = $userModel->where(array('wxid'=>$token['openid']))->find();                

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
                $this->error('缺少必填项:'.$key);
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

}