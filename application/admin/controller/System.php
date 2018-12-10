<?php
namespace app\admin\controller;
use think\Db;
class System extends Base {
	// 角色列表
	public function role_list(){
		$list=Db::name('role')->order('id desc')
				->where(array('is_del'=>0))
				->paginate(
					config('paginate.list_rows'),false,
					array('query'=>$this->request->param())
				);

		$this -> assign('list', $list);
		return $this -> fetch();
	}

	// 设置角色
	public function set_role(){
		$model=Db::name('role');
		$id=input('id');
		if($this->request->method()=='POST'){
			$data=$this->check_require('name');
			$data['remark']=input('remark');
			$data['status']=input('status')?input('status'):0;
			if($id){
				// 修改时如果冻结了角色 那么把用户也冻结
				if(!$data['status']){
					Db::name('admin')->where(array('role'=>$id))->update(array('state'=>0));
				}
				$data['id']=$id;
				$set=$model->update($data);
			}else{
				$set=$model->insertGetId($data);
			}
			if($set){
				succ('操作成功');
			}else{
				err('操作失败');
			}
		}

		if($id){
			$info=$model->find($id);
			$this->assign('info',$info);	
		}
		
		return $this->fetch();
	}

	// 删除角色
	public function del_role() {
		$data = $this->check_require('id');
		// 如果要删除的是当前用户的角色 提示不让删
		if($this->_admin['role']==$data['id']){
			err('不可删除自身角色');
		}
		$data['is_del']=1;
		$result =Db::name('role')->update($data);
		if ($result) {
			// 删除掉对应的用户
			Db::name('admin')->where(array('role'=>$data['id']))->update(array('is_del'=>1));
			succ('删除成功');
		} else {
			err('删除失败');
		}
	}

	// 权限设置
	public function setting() {
		$id = input('id');
		$access = Db::name('access');
		$treenode = Db::name('treenode');
		if ($this->request->method()=='POST') {
			$getrule = input('rule/a');//数组必须这么接收
			$access -> where(array('role_id' => $id)) -> delete();
			$rule_num=count($getrule);
			$result=true;//如果全部取消 那就直接为true
			if($rule_num){
				$sql="insert into web_access (role_id,node_id) values ";
				for ($i = 0; $i < $rule_num; $i++) {
					$sql.="('".$id."','".$getrule[$i]."'),";
				}
				$result=Db::execute(trim($sql,','));//批量添加
			}
			if ($result) {
				succ("操作成功");
			} else {
				err("操作失败");
			}
		}
		$nodeids = $access -> where(array('role_id'=>$id)) -> column('node_id');
		$ids='';
		foreach ($nodeids as $val) {
			$ids = '#' . $ids . $val . '#';
		}
		$data = $treenode -> field('id,title,pid,menuflag')->where(array('is_hide'=>0))->order('sort desc')-> select();

		$this -> assign('list', $data);
		$this -> assign('group', $ids);
		return $this -> fetch();
	}

	//用户列表
	public function user_list() {
		$list = Db::name('admin')->alias('a')
					->join('web_role r','r.id=a.role','left')
					->field('a.*,r.name as role_name')
					->order('a.id desc')
					->where(array('a.is_del'=>0))
					->paginate(
						config('paginate.list_rows'),false,
						$this->request->param()
					);

		$this -> assign('list', $list);
		return $this -> fetch();
	}

	// 设置用户
	public function set_user(){
		$model=Db::name('admin');
		$id=input('id');
		if($this->request->method()=='POST'){
			$data=$this->check_require('user,username,userimg,role');
			$data['note']=input('note');
			$data['state']=input('state')?input('state'):0;
			if($id){
				$data['id']=$id;
				$data['update_time']=time();
				$set=$model->update($data);
			}else{
				$data2=$this->check_require('password');
				$data['password']=md5($data2['password']);
				$data['add_time']=time();
				$set=$model->insertGetId($data);
			}
			if($set){
				succ('操作成功');
			}else{
				err('操作失败');
			}
		}

		if($id){
			$info=$model->find($id);
			$this->assign('info',$info);	
		}
		$roleList=Db::name('role')->where(array('status' => 1))->select();
		$this->assign('roleList',$roleList);	
		
		return $this->fetch();
	}

	//删除用户
	public function del_user() {
		$data = $this->check_require('id');
		if($this->_admin['id']==$data['id']){
			err('不可删除自身用户');
		}
		$data['is_del']=1;
		$result = Db::name('admin')->update($data);
		if ($result) {
			succ('删除成功');
		} else {
			err('删除失败');
		}
	}

	function test($day=''){
		if(!intval($day)){
			$day=intval(date('d',time()));
		}
		echo $day+1>16?'下半月':'上半月';
	}

}
