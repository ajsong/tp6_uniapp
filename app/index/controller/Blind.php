<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\model\BlindOrder;

class Blind extends Core
{
    public function index() {
	    $page = $this->request->get('page/d', 0);
	    $pagesize = $this->request->get('pagesize/d', 8);
	    $status = $this->request->get('status/d', 1);
		if ($status < 1) $status = 1;
	    $list = BlindOrder::where([
			'member_id' => $this->memberId,
			'status' => $status,
	    ])->order('id', 'desc')->field('id, no, day, percent, pic, price, total, add_time, 0 as now, 0 as end_time')->page($page, $pagesize)->select()->each(function($item) {
	        $item->now = time();
			$item->end_time = strtotime('+'.$item->day.' day', $item->add_time);
		});
	    $list = add_domain_deep($list, 'pic');
	    return success($list);
    }
}
