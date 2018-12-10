<?php
namespace app\admin\controller;
use think\Db;
class Index extends Base{
    public function index(){
        $referer=$this->request->server('HTTP_REFERER');//查询来源页面
        $from=strtolower(substr(str_replace('.html', '', $referer), -17));
        $delAll=input('delAll');
        //如果参数=1 且有开发者session 且是从开发者页面跳转过来的 证明确实是在开发者模式下点击了一键移除 那么就执行清除开发者模式
        if($delAll && session('developer_session') && $from=='developer/del_all'){
            $this->delAllFiles();
        }
        return $this->fetch();
    }

    public function pwd() {
        $model = Db::name('admin');
        $userInfo = session('admin');
        if($this->request->method()=='POST'){
            $where['id']=input('id');
            $where['password']=md5(input('post.oldpassword'));
            $result = $model -> where($where) -> find();
            if($result){
                $data['user'] = input('post.user');
                $data['username'] = input('post.username');
                $data['password'] = md5(input('post.newpassword'));
                $msg = $model -> where($where) -> update($data);
                if ($msg) {
                    session('admin',null);
                    succ("修改成功,请重新登录");
                } else {
                    err("修改失败");
                }
            }else{
                err("密码不正确");
            }
        }
        $this -> assign('userInfo', $userInfo);
        return $this -> fetch();
    }
    
    //删除开发者模式
    private function delAllFiles(){
        session('developer_session',null);
        Db::name('argument')->update(array('developer_name'=>null,'developer_pwd'=>null,'id'=>1));
        // 隐藏掉treenode中所有developer相关的菜单和功能(最好是删除 只是隐藏的话还需要再刷新一次才能不显示菜单)
        Db::name('treenode')->where(array('g'=>'admin','m'=>'developer'))->update(array('is_hide'=>1));
        //直接删除 慎用
        // Db::name('treenode')->where(array('g'=>'admin','m'=>'developer'))->delete();
        // delFileAndDir('./application/admin/controller/Developer.php');
        // delFileAndDir('./application/admin/view/Developer/');
    }
}
