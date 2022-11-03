<?php
declare (strict_types = 1);

namespace app\admin\controller;


//@system
class Feedback extends Core
{
    public function index() {
		$keyword = $this->request->get('keyword');
		$where = [];
		if ($keyword) $where[] = ['content|reply', 'like', "%$keyword%"];
	    $list = \app\model\Feedback::where($where)->order('id', 'desc')->paginate($this->paginateArr());
		return $this->render([
			'list' => $list,
		]);
    }
	
	public function add() {
		return $this->edit();
	}
	//@反馈处理
	public function edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$content = $this->request->post('content');
			$account = $this->request->post('account');
			$reply = $this->request->post('reply');
			if (!$content) error('内容不能为空');
			$data = compact('content', 'account', 'reply');
			if ($id > 0) {
				\app\model\Feedback::update($data, ['id'=>$id]);
			} else {
				$data['add_time'] = time();
				\app\model\Feedback::create($data);
			}
			return success('tourl:feedback/index', '提交成功');
		} else if ($id > 0) {
			$row = \app\model\Feedback::alias('fb')->leftJoin('member m', 'fb.member_id=m.id')->where('fb.id', $id)->field('fb.*, m.email')->find();
		} else {
			$row = t('feedback');
		}
		return $this->render([
			'row' => $row,
		], 'edit');
	}
	
	//@反馈删除
	public function delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Feedback::destroy(['id'=>$id]);
		return success(null, '操作成功');
	}
}
