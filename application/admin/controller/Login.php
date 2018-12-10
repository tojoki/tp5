<?php
namespace app\admin\controller;
use think\Db;
class Login extends Base{
    public function index(){
    	if($this->request->method()=='POST'){
    		$params=$this->check_require('user,password');
    		$params['password']=md5($params['password']);
    		$adminInfo=Db::name('admin')->where($params)->find();
    		if($adminInfo){
                if($adminInfo['is_del'] || !$adminInfo['state']){
                    err('该账号不存在或已被冻结');
                }
                Db::name('admin')->update(array('last_logintime'=>time(),'last_loginip'=>$this->request->ip(),'id'=>$adminInfo['id']));
    			session('admin',$adminInfo);
    			succ('登录成功');
    		}else{
                err('帐号或密码错误');
    		}
    	}
    	return $this->fetch();
    }

    public function loginout(){
        session('admin',null);
        $this->redirect('index');
    }
}
