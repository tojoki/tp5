<?php
namespace app\admin\controller;
use think\Db;
class Wechat extends Base {
    //配置参数
    public function set(){
        if($this->request->method()=='POST'){
            $data = $this->check_require('name,url,appid,appsecret,token,encodingaeskey');
            $data['etime']=time();
            $data['id']=1;
            $set=Db::name('wxconfig')->update($data);
            if($set){
                succ('操作成功');
            }else{
                err('操作失败');
            }
        }
        $data = Db::name('wxconfig')->find(1);
        $this->assign('data', $data);
        return $this->fetch();
    }

    //自定义菜单
    public function menu(){
        if($this->request->method()=='POST'){
            $menu = array_filter(input("wechatMenu/a"));
            $newmenu = array();
            foreach($menu as $key => $v){
                $arr = array();
                if($v['name'] == "") continue;
                $arr['name'] = $v['name'];
                if($v['sub_button']){
                    foreach($v['sub_button'] as $sub_k => $sub_btn){
                        if($sub_btn['name'] == "") continue;
                        $arr_sub = array();
                        $arr_sub["name"] = $sub_btn['name'];
                        $arr_sub["type"] = $sub_btn['type'];
                        if($sub_btn['type'] == 'view'){
                            $arr_sub["url"] = $sub_btn['value'];
                        }else{
                            $arr_sub["key"] = $sub_btn['value'];
                        }
                        $arr['sub_button'][] = $arr_sub;
                    }

                }else{
                    $arr['type'] = $v['type'];
                    if($v['type'] == 'view'){
                        $arr['url'] = $v['value'];
                    }else{
                        $arr['key'] = $v['value'];
                    }
                }
                array_push($newmenu , $arr);
            }
            if(!count($newmenu)) err('菜单不能为空');
            if(update_config($newmenu,'extra/menu.php',true)){
                succ('保存成功');
            }else{
                err('保存失败，文件存在');
            }
            
        }
        $data = config('menu');
        $this->assign('wechatMenu',$data);
        return $this->fetch();
    }

    //更新微信自定义菜单
    public function updateMenu(){
        $newmenu = config('menu');        
        $newmenu || exit;
        $wechatAuth = getWechatAuth();
        $result = $wechatAuth->menuCreate($newmenu);
        if($result['errcode'] == "0"){
            succ("更新成功");
        }else{
            err("更新失败:ErrCode".$result['errcode']);
        }
    }

    // 新版自定义菜单(含小程序)
    // 逻辑是先将3个一级菜单固定住 通过选择使用/不使用来决定是否展示此菜单
    // 在设置一级菜单时可以选择是否有子菜单 如果有 那么进入详情页设置的就是二级菜单(此时最多设置5条数据)
    // 如果没有 那么进入详情页设置的就是一级菜单本身(此时只有固定1条数据 且菜单名称不可修改)
    // 进入详情页设置时 有关键字/链接/小程序3种类型 如果设置为小程序 那么appid和路径是必填项
    // 以上所有操作 保存时都是先保存在wxconfig表中的menu_list字段 
    // 在点击"更新微信菜单"时 会将该字段取出并按照设置重新生成menu.php文件 然后调用微信自带的接口更新微信自定义菜单
    public function menu_list(){
        $model=Db::name('wxconfig');
        $list=json_decode($model->value('menu_list'),true);

        if($this->request->method()=='POST'){
            $data=$this->check_require('index,name,is_use,has_son');
            $index=$data['index']-1;
            unset($data['index']);
            // 判断当前提交的子菜单情况和之前是否不同 如果不同 那就重置
            if($list[$index]['has_son']!=$data['has_son']){
                // 如果本次有子菜单 说明是从无->有 
                // 如果本次没子菜单 说明是从有->无 
                if($data['has_son']){
                    $data['son_list']=array(array());//说明至少要有一个子菜单
                }else{
                    $data['son_list']=array();//说明只有一个子菜单
                }
            }
            $list[$index]=array_merge($list[$index],$data);
            $update=$model->update(array('id'=>1,'menu_list'=>json_encode($list)));
            $this->success('操作成功',url('menu_list_son',array('index'=>$index)));
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    // 保存一级菜单
    public function menu_list_son(){
        $index=input('index');
        $model=Db::name('wxconfig');
        $list=json_decode($model->value('menu_list'),true);
        $has_son=$list[$index]['has_son'];

        if($this->request->method()=='POST'){
            $name=input('name/a');
            $type=input('type/a');
            $value=input('value/a');
            $appid=input('appid/a');
            $pagepath=input('pagepath/a');
            $array=array();
            if(!$has_son){
                // 如果没有子菜单 说明只有一个
                $array=array('name'=>$name[0],'type'=>$type[0],'value'=>$value[0],
                            'appid'=>$type[0]=='miniprogram'?$appid[0]:'',
                            'pagepath'=>$type[0]=='miniprogram'?$pagepath[0]:'');
            }else{
                // 如果有子菜单 说明至少有一个
                $son_num=count($name);
                for($i=0;$i<$son_num;$i++){
                    $array[$i]['name']=$name[$i];
                    $array[$i]['type']=$type[$i];
                    $array[$i]['value']=$value[$i];
                    $array[$i]['appid']=$type[$i]=='miniprogram'?$appid[$i]:'';
                    $array[$i]['pagepath']=$type[$i]=='miniprogram'?$pagepath[$i]:'';
                }
            }
            $list[$index]['son_list']=$array;
            $update=$model->update(array('id'=>1,'menu_list'=>json_encode($list)));
            $this->success('操作成功');
        }
        $son_list=$list[$index]['son_list'];
        if(!$has_son){
            $son_list=array($son_list);//如果没有子菜单 说明就只有一个菜单 那么就套一层array 方便视图页统一
        }
        // 把一级菜单的名称也传过去
        $menu_name=$list[$index]['name'];
        $this->assign('menu_name',$menu_name);
        $this->assign('list',$son_list);
        $this->assign('has_son',$has_son);
        return $this->fetch();
    }

    // 将数据库中保存的微信自定义菜单生成文件 并调用微信接口进行更新
    public function update_wxmenu(){
        $list=Db::name('wxconfig')->value('menu_list');
        if(!$list){
            err('请先设置微信自定义菜单');
        }
        $list=json_decode($list,true);
        // dump($list);
        $array=array();
        foreach($list as $k=>$v){
            // 只把使用中的菜单加进来
            if($v['is_use']){
                $arr=array();
                if(!$v['has_son']){
                    // 如果没有子菜单 说明son_list是一维数组
                    if(count($v['son_list'])){
                        $arr=array('name'=>$v['son_list']['name'],'type'=>$v['son_list']['type']);
                        switch ($arr['type']) {
                            case 'click':
                                $arr['key']=$v['son_list']['value'];//如果是关键字 就把值存入key
                            break;
                            case 'view':
                                $arr['url']=$v['son_list']['value'];//如果是链接 就把值存入url
                            break;
                            case 'miniprogram':
                                $arr['url']=$v['son_list']['value'];//如果是小程序 就把值存入url 还要保存appid和pagepath
                                $arr['appid']=$v['son_list']['appid'];
                                $arr['pagepath']=$v['son_list']['pagepath'];
                            break;
                        }    
                    }
                }else{
                    // 如果有子菜单 说明son_list是二维数组
                    if(count($v['son_list'][0])){
                        $arr['name']=$v['name'];
                        foreach($v['son_list'] as $son_value){
                            $son_array=array('name'=>$son_value['name'],'type'=>$son_value['type']);
                            switch ($son_array['type']) {
                                case 'click':
                                    $son_array['key']=$son_value['value'];//如果是关键字 就把值存入key
                                break;
                                case 'view':
                                    $son_array['url']=$son_value['value'];//如果是链接 就把值存入url
                                break;
                                case 'miniprogram':
                                    $son_array['url']=$son_value['value'];//如果是小程序 就把值存入url 还要保存appid和pagepath
                                    $son_array['appid']=$son_value['appid'];
                                    $son_array['pagepath']=$son_value['pagepath'];
                                break;
                            }
                            $arr['sub_button'][]=$son_array;
                        }
                    }
                }
                if(count($arr)){
                    $array[]=$arr;
                }
            }
        }
        if(!count($array)){
            err('菜单不能为空');
        }

        if(update_config($array,'extra/menu.php',true)){
            // 只生成文件 不调用接口
            succ('操作成功');

            // 生成文件并调用接口
            // $newmenu = config('menu');        
            // $newmenu || exit;
            // $wechatAuth = getWechatAuth();
            // $result = $wechatAuth->menuCreate($newmenu);
            // if($result['errcode'] == "0"){
            //     succ("更新成功");
            // }else{
            //     err("更新失败:ErrCode".$result['errcode']);
            // }
        }else{
            err('更新微信自定义菜单文件失败');
        }
    }

    //回复列表
    public function reply(){
        $where=array();
        $keyword = input("keyword");//关键字
        if($keyword){
            $where['mname'] = array("LIKE","%".$keyword."%");
        }
        $list = Db::name('wxreply')
                ->where($where)->order('mid desc')->paginate(
                    config('paginate.list_rows'),false,
                    array('query'=>$this->request->param())
                )->each(function($item,$key){
                    $item['keyword'] = explode(',', $this->_keyword($item['keyword'],false));
                    return $item;
                });

        $this->assign('list',$list);
        return $this->fetch();
    }

    //设置回复
    public function set_reply(){
        $mid=input('mid');
        $model=Db::name('wxreply');
        if($this->request->method()=='POST'){
            // dump($this->request->param());die;
            $data=$this->check_require('mname,keyword,replytype,is_default,is_subscribe,service');
            $data['keyword']=$this->_keyword($data['keyword']);
            if($data['replytype']=='text'){
                $data['replycontent']=input('replyTextContent');
            }else if($data['replytype']=='news'){
                $data['replycontent']=input('replyNewsContent');
            }else{
                err('非法操作');
            }
            //关闭其他的默认回复
            if($data['is_default']){
                $model->where('1=1')->update(array('is_default'=>0));
            }
            //关闭其他的关注回复            
            if($data['is_subscribe']){
                $model->where('1=1')->update(array('is_subscribe'=>0));
            }
            $data['dateline']=time();
            if($mid){
                $data['mid']=$mid;
                $set=$model->update($data);
            }else{
                $set=$model->insert($data);
            }
            if($set){
                succ('操作成功');
            }else{
                err('操作失败');
            }
        }
        if($mid){
            $info=$model->find($mid);
            $info['keyword'] = $this->_keyword($info['keyword'],false);
            $this->assign('data',$info);
        }
        return $this->fetch();
    }

    //删除回复
    public function del_reply(){
        $mid=input('mid');
        $del=Db::name('wxreply')->delete($mid);
        if($del){
            succ('操作成功');
        }else{
            err('操作失败');
        }
    }

    /**
     * 处理关键字
     * @date   2016-06-03
     * @param  [type]     $keyword [description]
     * @param  boolean    $import  [description]
     * @return [type]              [description]
     */
    public function _keyword($keyword, $import = true){
        if(!$keyword) return '';
        if($import){
            return str_replace(',','|',','.trim($keyword,',').',');
       }else{
            return str_replace('|',',',trim($keyword,'|'));
       }
    }




}