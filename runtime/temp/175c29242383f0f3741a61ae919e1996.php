<?php if (!defined('THINK_PATH')) exit(); /*a:6:{s:91:"D:\heyi\USBWebserver\root\tp5_templet\public/../application/admin\view\developer\index.html";i:1536139839;s:79:"D:\heyi\USBWebserver\root\tp5_templet\application\admin\view\Public\header.html";i:1536218201;s:76:"D:\heyi\USBWebserver\root\tp5_templet\application\admin\view\Public\nav.html";i:1536133787;s:76:"D:\heyi\USBWebserver\root\tp5_templet\application\admin\view\Public\top.html";i:1536137648;s:79:"D:\heyi\USBWebserver\root\tp5_templet\application\admin\view\Public\footer.html";i:1536219313;s:77:"D:\heyi\USBWebserver\root\tp5_templet\application\admin\view\Public\form.html";i:1536127424;}*/ ?>
<!DOCTYPE html>
<html lang="en">
    <head>
<title><?php echo config('site.sitename'); ?></title>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="/static/admin/css/bootstrap.min.css?v=3.3.5" rel="stylesheet">
    <!-- <link href="/static/admin/css/font-awesome.min.css?v=4.4.0" rel="stylesheet"> -->
    <link href="/static/admin/css/plugins/iCheck/custom.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/chosen/chosen.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/cropper/cropper.min.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/switchery/switchery.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/jasny/jasny-bootstrap.min.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/nouslider/jquery.nouislider.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/datepicker/foundation-datepicker.css" rel="stylesheet">
    <link href="/static/admin/css/plugins/clockpicker/clockpicker.css" rel="stylesheet">
    <link href="/static/admin/css/animate.min.css" rel="stylesheet">  
    <link href="/static/admin/css/style.min.css?v=4.0.0" rel="stylesheet">	
    <link href="/static/admin/css/uploadfile.css" rel="stylesheet">
   	<script src="/static/admin/js/jquery.min.js"></script>
    <script src="/static/admin/layer/layer.js"></script>
    <link href="/static/admin/font-awesome-4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script src="/static/admin/js/plugins/chosen/chosen.jquery.js"></script>
   
</head>

    <link rel="stylesheet" href="/static/admin/css/layui_admin.css" media="all">
    <body class="fixed-sidebar full-height-layout gray-bg childrenBody" style="overflow:hidden">
            <div id="wrapper">
        <!--左侧导航开始-->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="nav-close"><i class="fa fa-times-circle"></i>
            </div>
            <div class="sidebar-collapse">
                <ul class="nav" id="side-menu">
                    <li class="nav-header">
                        <div class="dropdown profile-element">
                            <span data-toggle="dropdown" class="dropdown-toggle"><img alt="image" class="img-circle m-t-xs" src="<?php echo (isset($admin['userimg']) && ($admin['userimg'] !== '')?$admin['userimg']:'/uploads/default.jpg'); ?>" width="70px" height="70px" />&nbsp;&nbsp;
                            <strong style="color:white;">用户:<?php echo $admin['username']; ?></strong>
                            </span>
                            <!-- <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                                <span class="clear">
                               <span class="block m-t-xs"><strong><?php echo $admin['username']; ?></strong></span>
                                <span class="text-muted text-xs block">
                                	<if condition="$admin.role eq 0 ">
                                		超级管理员
                                		<else/>
	                                	<volist name="list2" id="vo2">
											<if condition="$admin.role eq $vo2.id ">
												<?php echo $vo2['name']; ?>
											</if>
											<if condition="$admin.role eq 0 ">
												超级管理员
											</if>
										</volist>
									</if>
                                	<b class="caret"></b></span>
                                </span>
                            </a> -->
                            <ul class="dropdown-menu animated fadeInRight m-t-xs">
                                <li><a href="<?php echo url('Index/pwd'); ?>">修改密码</a></li>
                                <li class="divider"></li>
                                <li><a href="#"><?php echo $admin['role_name']; ?></a></li>
                            </ul>
                        </div>
                        <div class="logo-element">H+</div>
                    </li>
                               
													
					  <li> 
					    <?php if(is_array($menu) || $menu instanceof \think\Collection || $menu instanceof \think\Paginator): $i = 0; $__LIST__ = $menu;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;if($vo['pid'] == 1): if($vo['single'] == 1): ?>
					              <li            
					                <?php if((strtolower($nownav['m']) == strtolower($vo['m']))): ?>
					                 class="active"
					                 <?php endif; ?>
					                ><a href="<?php echo url($vo['m'].'/'.$vo['a']); ?>"><i class="icon <?php echo $vo['ico']; ?>"></i> <span><?php echo $vo['title']; ?></span>
                                    <?php if(($vo['m'] == 'index') and ($vo['a'] == 'index')): ?>
                                        <span class="label label-danger pull-right" id='unread_order_num'><?php echo \think\Session::get('unread_order_num')?\think\Session::get('unread_order_num'):''; ?></span>    
                                    <?php endif; ?>
					                </a>
                                  </li>
					        <?php else: ?>
                                
					            <li class='submenu
					                <?php if((strtolower($nownav['m']) == strtolower($vo['m']))): ?>
					                  active 
					                 <?php endif; ?>
					                '
					             > <a href="#"><i class="icon <?php echo $vo['ico']; ?>"></i> <span><?php echo $vo['title']; ?></span><span class="fa arrow"></a>
					              <ul class="nav nav-second-level">
					                  <?php if(is_array($menu) || $menu instanceof \think\Collection || $menu instanceof \think\Paginator): $i = 0; $__LIST__ = $menu;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$subnode): $mod = ($i % 2 );++$i;if($subnode['pid'] == $vo['id']): ?>
			                                 <li  
			                                   <?php if((strtolower($nownav['m']) == strtolower($subnode['m']) ) && (strtolower($nownav['a']) == strtolower($subnode['a']) )): ?>
			                                   class="active"
			                                   <?php endif; ?>
			                                ><a href="<?php echo url($subnode['m'].'/'.$subnode['a']); ?>"><?php echo $subnode['title']; ?></a></li>
					                      <?php endif; endforeach; endif; else: echo "" ;endif; ?>
					              </ul>            
					        <?php endif; endif; endforeach; endif; else: echo "" ;endif; ?>
					  </li>									
                </ul>
            </div>
        </nav>
    <div id="page-wrapper" class="gray-bg dashbard-1">
    <div class="row border-bottom">
	<nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
		<div class="navbar-header"><a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
			<!-- <form role="search" class="navbar-form-custom" method="post" action="search_results.html">
				<div class="form-group">
					<input type="text" placeholder="请输入您需要查找的内容 …" class="form-control" name="top-search" id="top-search">
				</div>
			</form> -->
		</div>
		<ul class="nav navbar-top-links navbar-right">
			<!-- <li>
				<i class="fa fa-shopping-cart">
					<span id='unread_order_num' style="color:red;">
						<?php echo !empty($_SESSION['unread_order_num'])?$_SESSION['unread_order_num']:0; ?>
					</span>
				</i>
			</li> -->
			<li>
				<span class="m-r-sm text-muted welcome-message">
					<a href="<?php echo url('Index/index'); ?>" title="返回首页"><i class="fa fa-home"></i></a>欢迎使用<?php echo config('site.sitename'); ?>
					<span class="label label-danger pull-right"><?php echo $Os; ?></span>
				</span>
			</li>
			
			<li>
				<a href="javascript:;"  id="loginout">
					<i class="fa fa-sign-out"></i> 退出
				</a>
			</li>
		</ul>
	</nav>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		$("#loginout").click(function(){
			layer.confirm('你确定要退出吗？', {icon: 3}, function(index){
			    layer.close(index);
			    window.location.href="<?php echo url('Login/loginout'); ?>";
			});
		});
	});
</script>
    <script type="text/javascript">
        $(".del").click(function(){
            var thi= $(this);            
            var href = thi.data("href");
            $.getJSON(href,function(data){
                layer.msg(data.returnMsg,{skin:"layui-layer-hui",time:1000,end:function(){
                    if(data.returnCode == 'SUCCESS'){
                        window.location.reload();
                    }
                }});
            });
        });
    </script>

        <div class="row">
            <div class="col-sm-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-content no-padding">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <p class="text-success">
                                    <a href="<?php echo url('Index/index'); ?>" title="返回首页" class="tip-bottom"><i class="fa fa-home"></i> 首页</a> /
                                    <a href="#" class="tip-bottom">开发管理</a> /
                                    <a href="#" class="current"> 功能列表</a>
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
            <div class="panel_box row">
                <div class="panel col">
                    <a href="<?php echo url('site_index'); ?>">
                        <div class="panel_icon">
                            <i class="fa fa-internet-explorer"></i>
                        </div>
                        <div class="panel_word newMessage">
                            <span></span>
                            <cite>网站管理</cite>
                        </div>
                    </a>
                </div>
                <div class="panel col">
                    <a href="<?php echo url('menu_lists'); ?>">
                        <div class="panel_icon" style="background-color:#FF5722;">
                            <i class="fa fa-th-list"></i>
                        </div>
                        <div class="panel_word userAll">
                            <span></span>
                            <cite>菜单管理</cite>
                        </div>
                    </a>
                </div>
                <div class="panel col">
                    <a href="<?php echo url('add_table'); ?>">
                        <div class="panel_icon" style="background-color:#009688;">
                            <i class="fa fa-cube"></i>
                        </div>
                        <div class="panel_word userAll">
                            <span></span>
                            <cite>快速建表</cite>
                        </div>
                    </a>
                </div>
                <div class="panel col">
                    <a href="javascript:;" class='add0'>
                        <div class="panel_icon" style="background-color:#5FB878;">
                            <i class="fa fa-cubes"></i>
                        </div>
                        <div class="panel_word imgAll">
                            <span></span>
                            <cite>批量新增</cite>
                        </div>
                    </a>
                </div>
                <div class="panel col">
                    <a href="javascript:;" class='add1'>
                        <div class="panel_icon" style="background-color:#F7B824;">
                            <i class="fa fa-plus-circle"></i>
                        </div>
                        <div class="panel_word waitNews">
                            <span></span>
                            <cite>新建模块</cite>
                        </div>
                    </a>
                </div>
                <div class="panel col max_panel">
                    <a href="javascript:;" class='add2'>
                        <div class="panel_icon" style="background-color:#2F4056;">
                            <i class="fa fa-plus-square"></i>
                        </div>
                        <div class="panel_word waitNews">
                            <span></span>
                            <cite>新建控制器</cite>
                        </div>
                    </a>
                </div>
                <div class="panel col max_panel">
                    <a href="javascript:;" class='del' data-href="<?php echo url('del'); ?>">
                        <div class="panel_icon" style="background-color:#ED5565;">
                            <i class="fa fa-trash"></i>
                        </div>
                        <div class="panel_word waitNews">
                            <span></span>
                            <cite>清除缓存</cite>
                        </div>
                    </a>
                </div>
                <div class="panel col max_panel">
                    <a href="javascript:;" class='logout' data-href="<?php echo url('logout'); ?>">
                        <div class="panel_icon" style="background-color:#CCCCCC;">
                            <i class="fa fa-sign-out"></i>
                        </div>
                        <div class="panel_word waitNews">
                            <span></span>
                            <cite>开发者登出</cite>
                        </div>
                    </a>
                </div>
                <div class="panel col max_panel">
                    <a href="javascript:;" class='del_all'>
                        <div class="panel_icon" style="background-color:#000000;">
                            <i class="fa fa-eraser"></i>
                        </div>
                        <div class="panel_word waitNews">
                            <span></span>
                            <cite>一键移除</cite>
                        </div>
                    </a>
                </div>
            </div>
        <div class="footer" style="position: fixed;z-index: 999;bottom: 0;width: 89%;">
	<div class="pull-right"><?php echo config('site.address'); ?></div>
</div>
</div>
</div>
<script type="text/javascript" src="/static/admin/js/bootstrap.min.js?v=3.3.5"></script>
<script type="text/javascript" src="/static/admin/js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script type="text/javascript" src="/static/admin/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script type="text/javascript" src="/static/admin/js/plugins/layer/layer.min.js"></script>
<script type="text/javascript" src="/static/admin/js/hplus.min.js?v=4.0.0"></script>
<script type="text/javascript" src="/static/admin/js/contabs.min.js"></script>
<script type="text/javascript" src="/static/admin/js/plugins/pace/pace.min.js"></script>    
<script type="text/javascript" src="/static/admin/js/plugins/chosen/chosen.jquery.js"></script>
<script type="text/javascript" src="/static/admin/js/plugins/iCheck/icheck.min.js"></script>
<script type="text/javascript" src="/static/admin/js/plugins/sweetalert/sweetalert.min.js"></script>
<script type="text/javascript" src="/static/admin/js/jquery.form.js"></script>
<script type="text/javascript" src='/static/admin/js/plugins/laydate/laydate.js' /></script>
<script type="text/javascript" src="/static/admin/js/plugins/switchery/switchery.js"></script>
<script>
    $(document).ready(function(){$(".i-checks").iCheck({checkboxClass:"icheckbox_square-green",radioClass:"iradio_square-green",})});
</script>
        <script type="text/javascript" src="/static/admin/js/plugins/Validform/Validform_v5.3.1_min.js"></script>
<link rel="stylesheet" type="text/css" href="/static/admin/css/plugins/Validform/validform.css" />

    </body>        
    <script type="text/javascript">
        //批量新增
        $('.add0').on('click',function(){
            var index = layer.open({
                type: 2,
                title:"批量新增",
                content: '<?php echo url("insert_values"); ?>',
                area: ['800px', '400px'],
                skin: 'layui-layer-rim',
                maxmin: true,
                scrollbar: true,
            });
        });

        //新建模块
        $('.add1').on('click',function(){
            var index = layer.open({
                type: 2,
                title:"新建模块",
                content: '<?php echo url("add_new_module"); ?>',
                area: ['800px', '240px'],
                skin: 'layui-layer-rim',
                maxmin: true,
                scrollbar: true,
            });
        });

        //新建控制器
        $('.add2').on('click',function(){
            var index = layer.open({
                type: 2,
                title:"新建控制器",
                content: '<?php echo url("add_controller"); ?>',
                area: ['800px', '360px'],
                skin: 'layui-layer-rim',
                maxmin: true,
                scrollbar: true,
            });
        });

        //清除缓存
        $(".del").click(function(){
            var thi= $(this);            
            var href = thi.data("href");
            $.getJSON(href,function(data){
                layer.msg(data.returnMsg,{skin:"layui-layer-hui",time:1000,end:function(){
                    if(data.returnCode == 'SUCCESS'){
                        window.location.reload();
                    }
                }});
            });
        });

        //登出开发者
        $(".logout").click(function(){
            var thi= $(this);            
            var href = thi.data('href');
            layer.confirm('确定此操作？', {icon: 3}, function(index){
                $.getJSON(href,function(data){
                    layer.msg(data.returnMsg,{skin:"layui-layer-hui",time:1000,end:function(){
                        if(data.returnCode == 'SUCCESS')window.location.reload(); //重载页面
                    }});
                });
            });
        });

        //一键移除
        $(".del_all").click(function(){
            layer.confirm('此操作将完全移除开发者模式 确定？', {icon: 3}, function(index){
                var index = layer.open({
                    type: 2,
                    title:"一键移除",
                    content: '<?php echo url("del_all"); ?>',
                    area: ['800px', '300px'],
                    skin: 'layui-layer-rim',
                    maxmin: true,
                    scrollbar: true,
                });    
            });
        });
    </script>
</html>