<?php 
namespace app\admin\controller;
use think\Db;
Class Developer extends Base {
	//登录开发者模式
	public function login(){
		if($this->request->method()=='POST'){
			$data=$this->check_require('developer_name,developer_pwd');
			$data['developer_pwd']=md5($data['developer_pwd']);
			$info=Db::name('argument')->where($data)->field('developer_name,developer_pwd')->find();
			if($info){
				session('developer_session',$info);
				succ('登录成功');
			}else{
				err('登录失败');
			}
		}
		return $this->fetch();
	}

	//首页
	public function index(){
		$this->check_developer();
		return $this->fetch();
	}

	//设置网站
	public function site_index(){
        if($this->request->method()=='POST'){
        	$data=$this->request->post();
        	if(!$data['use_cover']){
        		$data['use_cover']=0;
        	}
	        if(update_config($data,'extra/site.php')){
	            succ('操作成功');
	        }else{
	            err('操作失败');
	        }    
        }
        return $this->fetch();
    }

    //设置菜单
    public function menu_lists(){
		//先查出除了首页以外的一级分类
		$level1=Db::name('treenode')
					->where(array('level'=>1,'id'=>array('not in',array(1,2))))
					->field('id,title,m,a,level,is_hide,ico,single,sort,pid')->order('sort desc')->select();
		$lists=array();
		foreach($level1 as $k=>$v){
			$lists[]=$v;
			$level2=Db::name('treenode')
						->where(array('level'=>2,'pid'=>$v['id']))
						->field('id,title,m,a,level,is_hide,sort,pid')->order('sort desc')->select();
			foreach($level2 as $v2){
				$lists[]=$v2;
			}
		}

		$this->assign('lists',$lists);
		return $this->fetch();
	}

	//菜单隐藏/展示
	public function menu_lock(){
		$model=Db::name('treenode');
		$data=$this->check_require('id,level,is_hide');
		$id_arr=array($data['id']);//不管点击的是一级还是二级 先放进数组(如果是一级的话 需要查询其直属的三级分类)
		if($data['level']==1){
			//如果是一级 那么找出二级分类 三级分类 全部放入新数组
			$level2_id=$model->where(array('pid'=>$data['id'],'level'=>2))->column('id');
			$id_arr=array_merge($id_arr,$level2_id);//把二级分类合并进数组
			$level3_id=$model->where(array('pid'=>array('in',$id_arr),'level'=>3))->column('id');
			$id_arr=array_merge($id_arr,$level3_id);//把三级分类合并进数组
		}else if($data['level']==2){
			//如果是二级 那么找出三级分类 放入新数组
			$level3_id=$model->where(array('pid'=>$data['id'],'level'=>3))->column('id');
			$id_arr=array_merge($id_arr,$level3_id);//把三级分类合并进数组
			//查询自己的同级并且同状态(is_hide)的还有几个 如果只有这一个二级菜单 那就把父级也放到数组里一起改掉
			$pid=$model->where(array('id'=>$data['id']))->value('pid');
			$brother_num=$model->where(array('pid'=>$pid,'level'=>2,
										  'id'=>array('neq',$data['id']),
										  'is_hide'=>array('neq',$data['is_hide'])))->count();
			if(!$brother_num){
				$id_arr[]=$pid;
			}
		}else{
			err('非法操作');
		}

		$set=$model->where(array('id'=>array('in',$id_arr)))->update(array('is_hide'=>$data['is_hide']));
		if($set){
			succ('操作成功');
		}else{
			err('操作失败');
		}
		//目前的功能 点击一级分类 二级分类联动 三级分类联动 
		//点击二级分类 三级分类联动 如果该二级分类该分类中是最后一个要隐藏/展示的分类 那么对应的一级分类也联动
		//如果该二级分类是第一个要隐藏/展示的分类 且该二级分类:1.有兄弟分类 对应的一级分类不联动 2.无兄弟分类 对应的一级分类也联动
		//现在的逻辑是二级分类和三级分类都直属于一级 不同的是二级分类是菜单 三级分类是功能点
	}

	//设置菜单
	public function set_menu(){
		$model=Db::name('treenode');
		$id=input('id');

		if($this->request->method()=='POST'){
			$data=array(
				'title'=>input('title'),
				'single'=>input('single')?0:1,//0表示多节点 1表示单节点
				'g'=>'admin',
				'm'=>strtolower(input('mm')),
				'a'=>input('single')?null:strtolower(input('aa')),//如果是多节点 那这里没有值
				'ico'=>input('ico'),
				'pid'=>1,'level'=>1,'menuflag'=>1,
				'sort'=>input('sort'),
				'status'=>1,'is_hide'=>input('is_hide')?0:1
			);
			if($id){
				$check_menu=$model->where(array('m'=>$data['m'],'a'=>$data['a'],'id'=>array('neq',$id)))->count();
				if($check_menu){
					err('菜单重复');
				}
				//查询重复排序号
				$check_sort=$model->where(array('pid'=>1,'sort'=>$data['sort'],'id'=>array('neq',$id)))->count();
				if($check_sort){
					err('排序号重复');
				}
				//如果修改后是单节点 不管之前是单节点还是多节点 都要查询一下数据库中此菜单是否有子类 如果有 不让改
				if($data['single']){
					$check_son=$model->where(array('pid'=>$id,'level'=>2))->count();
					if($check_son){
						err('该菜单有子类 暂不可执行此操作');
					}	
				}
				$data['id']=$id;
				$set=$model->update($data);
			}else{
				$check_menu=$model->where(array('m'=>$data['m'],'a'=>$data['a']))->count();
				if($check_menu){
					err('菜单重复');
				}
				//查询重复排序号
				$check_sort=$model->where(array('pid'=>1,'sort'=>$data['sort']))->count();
				if($check_sort){
					err('排序号重复');
				}
				$set=$model->insert($data);
			}
			if($set){
				succ('操作成功');
			}else{
				err('操作失败');
			}
		}
		if($id){
			$info=$model->find($id);
			$this->assign('data',$info);
		}
		//查询所有icon
		$icon_list=Db::name('fontawesome')->select();
		$this->assign('icon_list',$icon_list);
		return $this->fetch();
	}

	//设置子菜单
	public function set_menu_son(){
		$model=Db::name('treenode');
		$pid=input('pid');//有pid 说明是一级分类点击增加二级分类/二级分类点击修改二级分类
		$id=input('id');//有id 说明是二级分类点击修改二级分类

		if($this->request->method()=='POST'){
			$data=array(
				'title'=>input('title'),
				'single'=>1,//二级分类默认都是单节点
				'g'=>'admin',
				'm'=>strtolower(input('mm')),
				'a'=>strtolower(input('aa')),
				'ico'=>input('ico'),
				'pid'=>$pid,'level'=>2,'menuflag'=>1,
				'sort'=>input('sort'),
				'status'=>1,'is_hide'=>input('is_hide')?0:1
			);
			if($id){
				$check_menu=$model->where(array('m'=>$data['m'],'a'=>$data['a'],'id'=>array('neq',$id)))->count();
				if($check_menu){
					err('菜单重复');
				}
				//查询重复排序号
				$check_sort=$model->where(array('pid'=>$pid,'sort'=>$data['sort'],'id'=>array('neq',$id)))->count();
				if($check_sort){
					err('排序号重复');
				}
				$data['id']=$id;
				$set=$model->update($data);
			}else{
				$check_menu=$model->where(array('m'=>$data['m'],'a'=>$data['a']))->count();
				if($check_menu){
					err('菜单重复');
				}
				//查询重复排序号
				$check_sort=$model->where(array('pid'=>$pid,'sort'=>$data['sort']))->count();
				if($check_sort){
					err('排序号重复');
				}
				$set=$model->insert($data);
			}
			if($set){
				succ('操作成功');
			}else{
				err('操作失败');
			}
		}
		if($id){
			$info=$model->find($id);
			$this->assign('data',$info);
		}
		return $this->fetch();
	}

	//删除菜单/功能点
	public function menu_del(){
		$model=Db::name('treenode');
		$id=input('id');
		$info=$model->find($id);
		// 一级二级分类可能会涉及到子菜单/子功能点的删除 三级分类就是子功能点本身 
		if($info['level']==1){
			$del=$model->where(array('id|pid'=>$id))->delete();
		}else if($info['level']==2){
			$del=$model->where(array('id|pid'=>$id))->delete();
		}else if($info['level']==3){
			$del=$model->delete($id);
		}else{
			err('非法操作');
		}
		if($del){
			succ('删除成功');
		}else{
			err('删除失败');
		}
	}

	//查看权限功能点
	public function set_auth(){
		$model=Db::name('treenode');
		$pid=input('pid');
		if($this->request->method()=='POST'){
			$params=$this->check_require('title,mm,aa');
			$data=array('title'=>$params['title'],'g'=>'admin','m'=>$params['mm'],
						'a'=>$params['aa'],'pid'=>$pid,'level'=>3,'menuflag'=>0,'single'=>1);
			$add=$model->insert($data);
			if($add){
				succ('操作成功');
			}else{
				err('操作失败');
			}
		}
		$lists=$model->where(array('pid'=>$pid,'level'=>3))->select();
		$this->assign('lists',$lists);
		return $this->fetch();
	}

	//排序
	public function sort_up_down(){
		$model=Db::name('treenode');
		$id=input('id');
		$type=input('type');//接收排序规则
		switch ($type) {
			// 排序号的规则是数字大的在前 所以上调时倒序查询 下调时正序查询 
			// 逻辑就是当前数据和查询出来的前一个数据互换排序号
			//上调
			case 'up':
				$order_type='desc';//排序倒序
				$err_msg='已经是第一位了';
			break;
			//下调
			case 'down':
				$order_type='asc';//排序正序
				$err_msg='已经是最后一位了';
			break;
			default:
				err('非法操作');
			break;
		}
		$info=$model->find($id);

		$where['level']=$info['level'];
		$where['is_hide']=0;
		if($where['level']==1){
			$where['id']=array('not in',array(1,2));//首页和全选不考虑
		}else if($where['level']==2){
			$where['menuflag']=1;//如果是二级分类 那必须是侧边栏已有的
			$where['pid']=$info['pid'];//只查询自己内部的
		}else{
			err('非法操作');
		}
		//按顺序查询当前层级的所有可视菜单 一级分类查询所有 二级分类只查自己父类的
		$lists=$model->where($where)->order('sort '.$order_type)->column('id');
		if(count($lists)==1){
			err('无需排序');//因为只有一条数据
		}
		//查询自己的位置
		$position=array_search($id,$lists);
		if($position==0){
			err($err_msg);//为0表示自身处于最大或最小的排序号上 给出提示即可
		}else{
			Db::startTrans();
			//不为0表示可排序 那么查询出前一位的数据
			$pre_id=$lists[($position-1)];
			$pre_sort=$model->where(array('id'=>$pre_id))->value('sort');//前一位的sort
			if($pre_sort==$info['sort']){
				//排序上调时 如果两条数据的排序号相等 只需要把自身排序号+1即可 这样自己的排序号就大了
				//排序下调时 如果两条数据的排序号相等 只需要把对方排序号+1即可 这样自己的排序号就小了
				$this_id_arr=array('up'=>$id,'down'=>$pid);//上调时使用$id 下调时使用$pid
				$set1=$model->where(array('id'=>$this_id_arr[$type]))->setInc('sort');
				$set2=true;
			}else{
				//如果两条数据的排序号不相等 互相交换即可
				$set1=$model->update(array('sort'=>$pre_sort,'id'=>$id));
				$set2=$model->update(array('sort'=>$info['sort'],'id'=>$pre_id));
			}
			if($set1 && $set2){
				Db::commit();
				succ('操作成功');
			}else{
				Db::rollback();
				err('操作失败');
			}
		}
	}

	//数据库建表
	public function add_table(){
		if($this->request->method()=='POST'){
			$data=$this->check_require('table_name,table_desc');
			$data['field_name']=input('field_name/a');
			$data['field_type']=input('field_type/a');
			$data['field_length']=input('field_length/a');
			$data['field_defaut']=input('field_defaut/a');
			$data['is_key']=input('is_key/a');
			$data['field_comment']=input('field_comment/a');
			//先查询表
			$all_tables=Db::getTables();
			if(in_array($data['table_name'], $all_tables)){
				err('表名重复');
			}
			$length=count($data['field_name']);
			$sql="CREATE TABLE `".$data['table_name']."` (";
			$primary_key='';
			for($i=0;$i<$length;$i++){
				//如果是float或longtext 就不插入长度了
				if($data['field_type'][$i]=='float' || $data['field_type'][$i]=='longtext'){
					$field_length=" ";
				}else{
					$field_length=" (".$data['field_length'][$i].") ";
				}
				//判断是否为主键
				if($data['is_key'][$i]==1){
					$primary_key=",PRIMARY KEY (`".$data['field_name'][$i]."`)";//主键存一下
					$default=" NOT NULL AUTO_INCREMENT ";//主键这里默认非空且自增
				}else{
					if($data['field_defaut'][$i]){
						$default=" DEFAULT '".$data['field_defaut'][$i]."' ";
					}else{
						$default=" DEFAULT NULL ";
					}
				}
				if($data['field_comment'][$i]){
					$comment=" COMMENT '".$data['field_comment'][$i]."',";
				}else{
					$comment=",";
				}

				$sql.="`".$data['field_name'][$i]."` ".$data['field_type'][$i].$field_length.$default.$comment;
			}
			$sql=trim($sql,',').$primary_key." ) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='".$data['table_desc']."'";
			try{
				$test=Db::execute($sql);
				if($test===false){
					err('操作失败');//执行成功返回为0 所以必须全等于才可以
				}else{
					succ('操作成功');
				}
			}catch(\Exception $e){
				err($e->getMessage());//失败直接报错
			}
		}
		return $this->fetch();
	}

	//批量新增
	public function insert_values(){
		if($this->request->method()=='POST'){
			$data=$this->check_require('table_name,num');
			//查询该表所有信息
			$table_detail=Db::query('desc '.$data['table_name']);
			// dump($table_detail);die;
			$insert_data=array();
			foreach($table_detail as $v){
				//判断类型是否在允许的范围内
				//如果是主键或者自增的字段 略过
				if($v['Key']=='PRI' || $v['Extra']=='auto_increment'){
					continue;
				}
				//查询是否有长度(判断有没有括号即可)
				$if_bracket=strpos($v['Type'], '(');
				if($if_bracket===false){
					//如果没括号 type的值直接就是类型
					$type=$v['Type'];
				}else{
					//如果有括号 截取类型
					$type=substr($v['Type'], 0,$if_bracket);
				}
				//根据不同类型给出不同的测试数据
				switch ($type) {
					case 'decimal':
						$value='0.';	
					break;
					case 'float':
						$value='0.';	
					break;
					case 'int':
						$value='';	
					break;
					case 'longtext':
						$value='测试文本';	
					break;
					case 'tinyint':
						$value='';	
					break;
					case 'varchar':
						$value='测试数据';	
					break;
					
					default:
						continue;
					break;
				}
				for($i=0;$i<$data['num'];$i++){
					$insert_data[$v['Field']][]=$value.$i;
				}
			}
			$sql=insert_table($data['table_name'],$insert_data);//获取批量插入的sql
			try{
				$add=Db::execute($sql);
				if($add){
					succ('操作成功');
				}else{
					err('操作失败');
				}
			}catch(\Exception $e){
				err($e->getMessage());
			}
		}
		$all_tables=Db::getTables();//所有表名
		$system_tables=Db::name('system_table')->column('table_name');
		if(!$system_tables){
			$system_tables=array();
		}
		$rest_tables=array_diff($all_tables, $system_tables);
		$this->assign('lists',$rest_tables);
		return $this->fetch();
	}

	//新增模块
	public function add_new_module(){
		if($this->request->method()=='POST'){
			$model=Db::name('argument');
			$data=$this->check_require('module_name');
			$module_name=strtolower(trim($data['module_name']));//模块名称小写
			$all_modules=$model->value('all_modules');
			$all_modules=explode(',', $all_modules);
			if(in_array($module_name, $all_modules)){
				err('模块名称重复');
			}else{
				//复制public中的模板文件夹 并命名为提交过来的模块名称
				copydir(ROOT_PATH.'public/static/admin/new_module',ROOT_PATH.'application/'.$module_name);
				//修改数据库 追加相应的模块字段
				array_unique(array_push($all_modules, $module_name));
				$update=$model->update(array('all_modules'=>implode(',', $all_modules),'id'=>1));
				if($update){
					succ('操作成功');
				}else{
					err('操作失败');
				}
			}
		}
		return $this->fetch();
	}

	//新建控制器
	public function add_controller(){
		if($this->request->method()=='POST'){
			$data=$this->check_require('module_name,controller_name');
			$module_name=strtolower(trim($data['module_name']));//模块名称
			$controller_name=ucfirst(trim($data['controller_name']));//控制器名称
	        $all_controller = $this->getController($module_name);
	        if(in_array($controller_name, $all_controller)){
	        	err('控制器名称重复');
	        }else{
	        	$extends_name=input('extends_name')?ucfirst(trim(input('extends_name'))):'';
	        	$add=add_controller_file($module_name,$controller_name,$extends_name);
	        	//根据新建的模块新建文件夹(防止linux找不到视图文件 这里目录一律小写)
	        	mkdir(ROOT_PATH.'application/'.$module_name.'/view/'.strtolower($controller_name).'/',0777,true);
	        	if($add){
	        		succ('操作成功');
	        	}else{
	        		err('操作失败');
	        	}
	        }
		}
		//查询所有可用模块 并排除掉系统禁止访问的模块
		$all_modules=Db::name('argument')->value('all_modules');
		$module_list=explode(',', $all_modules);
		$deny_module=config('deny_module_list');
		$module_list=array_diff($module_list, $deny_module);

		$this->assign('module_list',$module_list);
		return $this->fetch();
	}

	//删除缓存
	public function del() { 
		delFileByDir(RUNTIME_PATH);
		succ('操作成功');
	}

	//开发者登出
	public function logout(){
		session('developer_session',null);
		succ('操作成功');
	}

	//一键移除(慎用)
	public function del_all(){
		if($this->request->method()=='POST'){
			$data=$this->check_require('developer_name,developer_pwd');
			$data['developer_pwd']=md5($data['developer_pwd']);
			$info=Db::name('argument')->where($data)->field('developer_name,developer_pwd')->find();
			if($info){
				succ('操作成功');
			}else{
				err('操作失败');
			}
		}
		return $this->fetch();
	}

	//先查询是否有开发者session 如果没有提示登录
	protected function check_developer(){
		$developer_session=session('developer_session');
		if(!$developer_session){
			$this->redirect('login');
		}
	}

	//前台查询目标模块下的所有controller
	public function getAllControllers(){
		$data=$this->check_require('module_name');
		$module_name=strtolower(trim($data['module_name']));//模块名称
	    $all_controller = $this->getController($module_name);
	    if(!$all_controller){
	    	$all_controller=array();
	    }
	    return json(array('list'=>$all_controller));
	}

	//获取目标模块下的所有控制器名称
	protected function getController($module){
        if(empty($module)) return null;
        $module_path = APP_PATH . '/' . $module . '/controller/';  //控制器路径
        if(!is_dir($module_path)) return null;
        $module_path .= '/*.php';
        $ary_files = glob($module_path);
        foreach ($ary_files as $file) {
            if (is_dir($file)) {
                continue;
            }else {
                $files[] = basename($file, '.php');
            }
        }
        return $files;
    }
}