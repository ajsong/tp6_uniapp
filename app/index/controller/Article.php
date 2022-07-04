<?php
declare (strict_types = 1);

namespace app\index\controller;

class Article extends Core
{
    public function index() {
	    $page = $this->request->get('page/d', 0);
	    $pagesize = $this->request->get('pagesize/d', 15);
		$where = [];
	    $list = \app\model\Article::alias('a')
		    ->where($where)->order(['sort', 'id'=>'desc'])->page($page, $pagesize)->select();
	    return success($list);
    }
}
