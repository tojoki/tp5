<?php
namespace app\index\controller;
use think\Controller;
use think\Db;

class Weibo extends Controller{
	// 获取微博授权 
	// (因为微博不像微信要定时刷新接口的access_token 微博用的都是用户授权后拿到的access_token 所以这里就把配置保存在本地了)
    public function index(){
    	$config=config('weibo')['app1'];
        $weibo=new \Weibo\SaeTOAuthV2($config['WB_AKEY'],$config['WB_SKEY']);
        $code_url = $weibo->getAuthorizeURL( $config['WB_CALLBACK_URL'] );

        $this->assign('code_url',$code_url);
        return $this->fetch();
    }
    // 授权成功后的回调页
    public function callback(){
    	$config=config('weibo')['app1'];
    	$weibo = new \Weibo\SaeTOAuthV2($config['WB_AKEY'],$config['WB_SKEY']);
    	$code=input('code');
		if ($code) {
			$keys = array();
			$keys['code'] = $code;
			$keys['redirect_uri'] = $config['WB_CALLBACK_URL'];
			try {
				$token = $weibo->getAccessToken( 'code', $keys ) ;
			} catch (OAuthException $e) {
			}
		}
		if($token){
			// 保存token 
			session('weibo_token',$token);
			// 先查询用户 如果查不到 或者信息不全 或者access_token已过期 那么再保存
			$model=Db::name('user');
			$weibo_api=getWeiboApi('app1',$token['access_token']);
			$uid=$weibo_api->get_uid()['uid'];
			$userInfo=$weibo_api->show_user_by_id( $uid);
			$data=array('uid'=>$uid,'screen_name'=>$userInfo['screen_name'],'access_token'=>$token['access_token'],'expires_in'=>time()+$token['expires_in']);
			$check_user=$model->find($uid);
			if(!$check_user){
				$set=$model->insert($data);
			}else if(!$check_user['access_token'] || $check_user['expires_in']<time()){
				$set=$model->update($data);
			}
			$this->assign('is_ok',1);
		}else{
			$this->assign('is_ok',0);
		}
		return $this->fetch();
    }

    // 微博列表
    public function weiboList(){
    	// $config=config('weibo')['app1'];
    	// $weibo_api = new \Weibo\SaeTClientV2( $config['WB_AKEY'],$config['WB_SKEY'] , session('weibo_token')['access_token'] );
    	$weibo_api=getWeiboApi('app1',session('weibo_token')['access_token']);
		$ms  = $weibo_api->home_timeline(); // done
		$uid_get = $weibo_api->get_uid();
		$uid = $uid_get['uid'];
		$user_message = $weibo_api->show_user_by_id( $uid);//根据ID获取用户等基本信息
		dump($user_message);//获取用户基本信息
		dump($ms);//获取首页微博
    }

    // 测试评论
    public function weibo_comment(){
    	$weibo_api=getWeiboApi('app1',session('weibo_token')['access_token']);
    	$res=$weibo_api->send_comment('4306958556870667','hahaha');//微博id 评论内容
    	// $res=$weibo_api->get_comments_by_sid('4306979520783411');
    	dump($res);    	
    }

    // 测试评论 查询首页的50条微博 如果发现有目标用户 那么给这条微博发送一个评论
    public function weibo_get_sofa(){
    	$weibo_api=getWeiboApi('app1',session('weibo_token')['access_token']);
		$ms  = $weibo_api->home_timeline(); 
		$list=$ms['statuses'];//微博数组
		foreach($list as $v){
			if($v['user']['screen_name']=='Mo_帅狗子浩劫'){
				$res=$weibo_api->send_comment($v['idstr'],'测试');
				dump($res);
				break;
			}
		}
    }

    // 自动抢评 (需用到crontab)
    // 每隔一段时间调用用户的微博首页 并循环首页微博 
    public function weibo_auto_get_sofa(){
    	$userInfo=Db::name('user')->find('2284062854');
    	if($userInfo['expires_in']>time()){
			$weibo_api=getWeiboApi('app1',$userInfo['access_token']);
			$ms  = $weibo_api->home_timeline();//只循环第一页的50条数据即可
			$list=$ms['statuses'];
			$model=Db::name('comment');
			foreach($list as $v){
				// 如果查询到了目标用户 那么再去表中查询是否已评论过
				if($v['user']['idstr']=='3171301332'){
					$where=array('wb_id'=>$v['idstr'],'wb_uid'=>$v['user']['idstr'],'uid'=>$userInfo['uid']);
					$check_comment=$model->where($where)->find();
					// 查不到就评论一条 然后把这次的评论以及微博id等相关信息保存到数组中 防止下次查询时重复评论
					if(!$check_comment){
						$where['wb_screen_name']=$v['user']['screen_name'];
						$where['screen_name']=$userInfo['screen_name'];
						$where['comment_content']='sofa';
						$where['ctime']=time();
						$res=$weibo_api->send_comment($v['idstr'],'sofa');//发送评论
						if ( isset($res['error_code']) && $res['error_code'] > 0 ) {
							$where['comment_id']=$res['error_code'];
							$where['comment_content']=$res['error'];
							$insert=$model->insert($where);
							// echo "<p>发送失败，错误：{$res['error_code']}:{$res['error']}</p>";
						} else {
							$where['comment_id']=$res['mid'];
							$insert=$model->insert($where);
							// echo $insert?'评论成功':'评论失败';
						}
					}
					break;
				}
			}
    	}
    }

    // 分享第三方链接 目前发文字/图片类的微博都不允许使用接口调用了 只能用这个发链接
    public function update_weibo(){
    	// $text='http://weibo.tojoki.asia/index/weibo/index';
    	$text=input('text');
    	if(!$text){
    		err('请输入内容');
    	}
    	$weibo_api=getWeiboApi('app1',session('weibo_token')['access_token']);
    	$ret = $weibo_api->share( $text );	//发送微博
		if ( isset($ret['error_code']) && $ret['error_code'] > 0 ) {
			echo "<p>发送失败，错误：{$ret['error_code']}:{$ret['error']}</p>";
		} else {
			echo "<p>发送成功</p>";
		}
    }
}
