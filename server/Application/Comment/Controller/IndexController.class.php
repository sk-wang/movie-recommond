<?php
namespace Comment\Controller;
use Think\Controller;

class IndexController extends Controller {
    public function Search(){
    	$comment = new CommentLogic();
    	$title = I('title');
        $this->ajaxReturn($comment->Search($title));
    }
}