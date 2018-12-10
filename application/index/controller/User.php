<?php
namespace app\index\controller;
use think\Db;

class User extends Base{
	//通过wx.login中的code换取用户openid
	public function codeToOpenid(){
		$params=$this->check_require('js_code');
        $params['appid'] = config('site.app_id_mini');
        $params['secret'] = config('site.secret_mini');
        $params['grant_type'] = 'authorization_code';

        $url="https://api.weixin.qq.com/sns/jscode2session";
        $token = http($url, $params);
        $token = json_decode($token, true);

        if(is_array($token)){
            if($token['errcode']){
                $this->error_back($token['errmsg']);
            } else {
                $this->response($token);
            }
        } else {
        	$this->error_back('获取微信openid失败');
        }
	}

	// 如果获取不到unionid 就使用解密encryptedData的方式获取
	public function decryptedData(){
		$params=$this->check_require('session_key,encryptedData,iv');
		$pc = new \DecryptedData\WXBizDataCrypt(config('site.app_id_mini'), $params['session_key']);
		$errCode = $pc->decryptData($params['encryptedData'], $params['iv'], $data );
		if ($errCode == 0) {
		    $this->response(json_decode($data,true));
		} else {
		    $this->response($errCode);
		}
	}

	//注册用户(含unionid)
	public function register_user_with_unionid(){
		$params=$this->check_require('unionid,openid_mp,nickname');
		$params['nickname']=trim(filterEmoji($params['nickname']))?trim(filterEmoji($params['nickname'])):'用户'.time();
		$params['headimg']=input('headimg')?input('headimg'):'https://'.$_SERVER['HTTP_HOST'].'/public/uploads/default.jpg';
		$data['openid_mp']=$params['openid_mp'];//无论是更新还是新增 都要有这个字段
		$data['last_login_time']=time();
		$model=Db::name('user');
		//查询用户是否存在 如果存在 就更新openid 如果不存在 就生成一条带有openid和unionid的用户
		$check_user=$model->where(array('unionid'=>$params['unionid']))->find();
		if($check_user){
			$model->where(array('userid'=>$check_user['userid']))->update($data);
			$userid=$check_user['userid'];
		}else{
			$data['nickname']=$params['nickname'];//更新时不修改昵称
			$data['sex']=input('gender');
			$data['headimg']=$params['headimg'];
			$data['unionid']=$params['unionid'];//用户在该微信开放平台的唯一标识
			$data['register_time']=time();			
			$userid=$model->insertGetId($data);
		}
		$userInfo=$model->where(array('userid'=>$userid))->find();
		$this->response($userInfo);
	}

	//注册用户(不含unionid)
	public function register_user_without_unionid(){
		$params=$this->check_require('openid,nickname');
		$params['nickname']=trim(filterEmoji($params['nickname']))?trim(filterEmoji($params['nickname'])):'用户'.time();
		$params['headimg']=input('headimg')?input('headimg'):'https://'.$_SERVER['HTTP_HOST'].'/Public/Admin/img/default.jpg';
		$data['last_login_time']=time();
		//查询用户是否存在 如果存在 就更新头像 如果不存在 就生成一条
		$model=Db::name('user');
		$check_user=$model->where(array('wxid'=>$params['openid']))->find();
		if($check_user){
			$model->where(array('userid'=>$check_user['userid']))->update($data);
			$userid=$check_user['userid'];
		}else{
			$data['nickname']=$params['nickname'];//更新时不修改昵称
			$data['sex']=input('gender');
			$data['headimg']=$params['headimg'];
			$data['wxid']=$params['openid'];
			$data['register_time']=time();
			$userid=$model->insertGetId($data);
		}
		$userInfo=$model->where(array('userid'=>$userid))->find();
		$this->response($userInfo);
	}
}