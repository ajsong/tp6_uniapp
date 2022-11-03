<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\model\ArticleCategory;
use app\model\ManageLog;

//@article
class Article extends Core
{
	public function index() {
		$category_id = $this->request->get('category_id/d', 0);
		$keyword = $this->request->get('keyword');
		$where = [];
		if ($category_id > 0) $where[] = ['category_id', '=', $category_id];
		if ($keyword) $where[] = ['title', 'like', "%$keyword%"];
		$list = \app\model\Article::where($where)
			->alias('a')
			->leftJoin('article_category ac', 'category_id=ac.id')
			->field('a.*, ac.name as category_name')->order('a.id', 'desc')
			->paginate($this->paginateArr());
		$category = ArticleCategory::where('status', 1)->order(['sort', 'id'])->select();
		return $this->render([
			'list' => $list,
			'category' => $category
		]);
	}
	
	//@文章添加
	public function add() {
		return $this->edit();
	}
	//@文章修改
	public function edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$category_id = $this->request->post('category_id/d', 0);
			$title = $this->request->post('title');
			$content = $this->request->post('content');
			$sort = $this->request->post('sort/d', 0);
			$status = $this->request->post('status/d', 0);
			if (!$title) error('请输入标题');
			if (!$content) error('请输入内容');
			$data = compact('title', 'content', 'category_id', 'sort', 'status');
			if ($id > 0) {
				\app\model\Article::update($data, ['id'=>$id]);
			} else {
				$data['add_time'] = time();
				\app\model\Article::create($data);
			}
			return success('tourl:article/index', '提交成功');
		} else if ($id > 0) {
			$row = \app\model\Article::where('id', $id)->find();
		} else {
			$row = t('article');
		}
		$category = ArticleCategory::where('status', 1)->order(['sort', 'id'])->select();
		return $this->render([
			'row' => $row,
			'category' => $category
		], 'edit');
	}
	
	//@文章删除
	public function delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Article::destroy(['id'=>$id]);
		return success(null, '操作成功');
	}
	
	//分类
	public function category() {
		$list = ArticleCategory::where('status', 1)->order(['sort', 'id'])->select();
		return $this->render([
			'list' => $list
		]);
	}
	
	//文章分类修改
	public function category_edit() {
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$name = $this->request->post('name');
			$status = $this->request->post('status/d', 0);
			$sort = $this->request->post('sort/d', 0);
			if (!$name) error('缺少数据');
			$data = compact('name', 'status', 'sort');
			if ($id > 0) {
				ArticleCategory::update($data, ['id'=>$id]);
			} else {
				ArticleCategory::create($data);
			}
			return success(null, '提交成功');
		}
		return null;
	}
	
	//文章分类全部修改
	public function category_edit_all() {
		$param = $this->request->post('param/a');
		if (!is_array($param)) error('数据错误');
		foreach ($param as $g) {
			$data = ['name'=>$g['name'], 'status'=>intval($g['status']), 'sort'=>intval($g['sort'])];
			if (isset($g['id']) && $g['id'] > 0) {
				ArticleCategory::update($data, ['id'=>$g['id']]);
			} else {
				ArticleCategory::create($data);
			}
		}
		return success(null, '提交成功');
	}
	
	//文章分类删除
	public function category_delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		ArticleCategory::destroy(['id'=>$id]);
		return success('stay:', '操作成功');
	}
}
