<?php
namespace app\admin\controller;
use think\Controller;
class Upload extends Controller {
    private $type_arr=array('images','video','app','files');
    private $maxsize_arr=array('images'=>'3145728','video'=>'100000000','app'=>'100000000','files'=>'3145728');
    private $exts_arr=array('images'=>array('jpg', 'gif', 'png', 'jpeg','bmp'),
                            'video'=>array('mp4','avi','rmvb','flv','wmv','mkv','mov'),
                            'app'=>array('apk'),'files'=>array('xls','xlsx'));

    //前后台的上传文件整合
    public function new_upload(){
        $type=input('type')?input('type'):0;//没传type 默认是图片类型
        $subtype=input('subtype')?input('subtype'):0;//0后台上传 1前台上传 没传subtype 默认是后台上传
        $is_resize=input('is_resize')?input('is_resize'):0;//0不缩略 1缩略
        $type_str=$this->type_arr[$type];//确定上传类型

        $key = array_shift(array_keys($_FILES));
        $file=$this->request->file($key);
        if($file){
            $info=$file->validate(array('size'=>$this->maxsize_arr[$type_str],'ext'=>$this->exts_arr[$type_str]))//验证大小/后缀
                    ->move(ROOT_PATH.'public/uploads/'.$type_str);//上传路径 这里ROOT_PATH为绝对路径(windows下带盘符的那种)
            if($info){
                //默认获取的是类似于20180828\123456.jpg这种文件名 所以要先把\转为/ 再拼接上uploads/images的路径
                $pic_url='/uploads/'.$type_str.'/'.str_replace('\\', '/', $info->getSaveName());
                // 将$pic_url保存在数据库即可 注意这里的路径没有拼接public 因为在视图页面可以直接使用__ROOT__{$pic_url}展示图片
                // 在tp5中__ROOT__就是从public文件夹下开始的 因为那里是入口文件的位置 所以不用带这个public
                // 但是注意在控制器中不能直接使用__ROOT__ 解析不了 
                // 亲测图片输出的页面不使用__ROOT__也可以输出图片 因为图片的/uploads这个路径的起始位置就是相对于根目录/public下的
                if($subtype){
                    $this->success($pic_url);
                }else{
                    echo $pic_url;
                }
            }else{
                if($subtype){
                    $this->error($file->getError());//前台上传返回报错信息
                }else{
                    die($file->getError()); //后台上传返回报错信息
                }
            }
        }else{
            if($subtype){
                $this->error('请上传图片');//$file为空表示没上传
            }else{
                die('请上传图片'); //$file为空表示没上传
            }
        }
    }


}
