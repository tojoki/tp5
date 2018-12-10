<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
header("Content-type:text/html;charset=utf-8");
class Base extends Controller {
    protected $_admin = array();
    protected function _initialize(){
        $this->_admin = session('admin');
        $controller_name=$this->request->controller();//控制器名称
        $action_name=$this->request->action();//方法名称
        if (strtolower($controller_name) != 'login' && strtolower($controller_name) != 'public') { 
            if (empty($this->_admin)) {
                header("Location: " . url('Login/index'));
                die;
            }
            $this->_admin['role_name']='超级管理员';
            if($this->_admin['role']){
                $this->_admin['role_name']=Db::name('role')->where(array('id'=>$this->_admin['role']))->value('name');
            }
            $this->assign('admin',$this->_admin);

            $nownav['m']=strtolower($controller_name);
            $nownav['a']=strtolower($action_name);
                
           
            $this->assign('nownav',$nownav);
            
            //权限处理
            if ($this->_admin['role']) {
                
                $this->_admin['menu_list'] = Db::name('Access')->where(array("role_id"=>$this->_admin['role']))->field('node_id')->select();    
                
                $menu_action = strtolower($controller_name . '/' . $action_name);
                $menu = Db::name('treenode')->select();
                $menu_id = 0;
                
                foreach($this->_admin['menu_list'] as $val){
                    foreach ($menu as $k => $v) {
                        if($v['id']==$val['node_id']){
                            if (($v['m'].'/'.$v['a']) == $menu_action) {
                                $menu_id = (int) $k;
                                break;
                            }
                        }
                    }
                }
                
                if (empty($menu_id) || $menu_id==0) {
                    // $errmsg='很抱歉您没有权限操作模块:' . $menu[$menu_id]['title'];
                    $errmsg='很抱歉您没有此权限';
                    if($this->request->isAjax()){
                        err($errmsg);
                    }else{
                        $this->error($errmsg);
                    }
                }
            }
            $this->loadMenu();
        }
    }

    private function loadMenu(){
        $where=array('menuflag'=>1,'is_hide'=>0);
        //非超级管理员只能查看之前被分配的节点
        if($this->_admin['role']){
            foreach ($this->_admin['menu_list'] as  $v) {
                $node_id[]=$v['node_id'];
            }
            $where['id']=array('in',$node_id);
        }
        $menu=Db::name('treenode')->where($where)->order("sort DESC")->select();
        $this->assign('menu',$menu);
    }

    protected function check_require($keys){
        $keyarr = explode(",",$keys);
        $data = array();
          foreach($keyarr as $key){
            $value = input($key);
            if($value==''){
                err('缺少必填项:'.$key);
            }
            $data[$key] = $value;
        }
        return $data;
    }
}