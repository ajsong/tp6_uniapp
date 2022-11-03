<?php
declare (strict_types = 1);

namespace app\index\controller;

class Article extends Core
{
	/**
	 * showdoc
	 * @catalog 文章
	 * @title 文章列表
	 * @method get
	 * @url #
	 * @param page 可选 int 页数，0开始
	 * @param pagesize 可选 int 每页记录数
	 * @return {}
	 */
    public function index() {
	    $page = $this->request->get('page/d', 0);
	    $pagesize = $this->request->get('pagesize/d', 15);
		$where = [];
	    $list = \app\model\Article::alias('a')
		    ->where($where)->field('id, title, add_time')->order(['sort', 'id'=>'desc'])->page($page, $pagesize)->select()->each(function($item) {
		        $item->add_time = date('Y年m月d日 H:i:s', $item->add_time);
			});
	    return success($list);
    }
	
	/**
	 * showdoc
	 * @catalog 文章
	 * @title 文章详情
	 * @method get
	 * @url #
	 * @param id 必选 int 文章id
	 * @return {}
	 */
	public function detail() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('缺少id');
		$item = \app\model\Article::whereId($id)->find();
		$item->add_time = date('Y年m月d日 H:i:s', $item->add_time);
		return success($item);
	}
}
