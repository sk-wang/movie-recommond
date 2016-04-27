<?php
namespace Comment\Controller;
use Think\Model;

class CommentLogic extends Model {
    public function Search($title){
    	$comment = new CommentModel();
    	$where['mname'] = array('like','%'.$title.'%');
        return $comment->where($where)->select();
    }
}