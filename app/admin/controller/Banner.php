<?php
declare (strict_types = 1);

namespace app\admin\controller;

//@promote
class Banner extends Core {
	
	public function index() {
		$keyword = $this->request->get('keyword');
		$status = $this->request->get('status');
		$type = $this->request->get('type');
		$position = $this->request->get('position');
		$begin_time = $this->request->get('begin_time');
		$end_time = $this->request->get('end_time');
		$where = [];
		if ($keyword) $where[] = ['name', 'like', "%$keyword%"];
		if (is_numeric($status)) $where[] = ['status', '=', $status];
		if ($type) $where[] = ['type', '=', $type];
		if ($position) $where[] = ['position', '=', $position];
		if ($begin_time) $where[] = ['begin_time', '>=', strtotime($begin_time)];
		if ($end_time) $where[] = ['end_time', '<=', strtotime($end_time.' 23:59:59')];
		$model = new \app\model\Banner();
		$list = \app\model\Banner::where($where)->order(['sort', 'id'])
			->paginate($this->paginateArr());
		return $this->render([
			'list' => $list,
			'types' => $model->getType(),
			'positions' => $model->getPosition(),
		]);
	}
	
	//@广告添加
	public function add() {
		return $this->edit();
	}
	//@广告修改
	public function edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$name = $this->request->post('name');
			$pic = c('upload')->image('pic', 'banner');
			$pic3 = c('upload')->image('pic3', 'banner');
			$type = $this->request->post('type');
			$content = $this->request->post('content');
			$position = $this->request->post('position');
			$begin_time = $this->request->get('begin_time');
			$end_time = $this->request->get('end_time');
			$status = $this->request->post('status/d', 0);
			$sort = $this->request->post('sort/d', 0);
			if (!strlen($name)) error('请输入名称');
			$begin_time = $begin_time ? strtotime($begin_time) : 0;
			$end_time = $end_time ? strtotime($end_time) : 0;
			$data = compact('name', 'pic', 'pic3', 'type', 'content', 'position', 'begin_time', 'end_time', 'status', 'sort');
			if ($id > 0) {
				\app\model\Banner::update($data, ['id'=>$id]);
			} else {
				$data['add_time'] = time();
				\app\model\Banner::create($data);
			}
			return success('tourl:banner/index', '提交成功');
		} else if ($id > 0) {
			$row = \app\model\Banner::where('id', $id)->find();
		} else {
			$row = t('banner');
		}
		$model = new \app\model\Banner();
		return $this->render([
			'row' => $row,
			'types' => $model->getType(),
			'positions' => $model->getPosition(),
		], 'edit');
	}
	
	//@广告删除
	public function delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Banner::destroy(['id'=>$id]);
		return success(null, '操作成功');
	}
	
}
