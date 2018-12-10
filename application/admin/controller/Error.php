<?php
namespace app\admin\controller;
class Error extends Base{
	public function _initialize(){
		header( "HTTP/1.0  404  Not Found" );
        return $this->fetch('Public:404');
	}

    public function _empty(){
        header( "HTTP/1.0  404  Not Found" );
        return $this->fetch('Public:404');
    }
   
}