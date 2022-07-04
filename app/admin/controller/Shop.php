<?php
declare (strict_types = 1);

namespace app\admin\controller;


use app\model\MemberFavorite;

//@shop
class Shop extends Core
{
    public function index()
    {
	    $id = $this->request->get('id/d', 0);
	    $keyword = $this->request->get('keyword');
	    $where = [];
	    if ($id > 0) $where[] = ['s.id', '=', $id];
	    if ($keyword) $where[] = ['s.name|s.mobile|m.name', 'like', "%$keyword%"];
	    $list = \app\model\Shop::alias('s')->leftJoin('member m', 's.member_id=m.id')->where($where)
		    ->order('s.id', 'DESC')->field('s.*, m.name as member_name')->paginate($this->paginateArr());
	    return $this->render([
			'list' => $list
	    ]);
    }
	
	//@店铺添加
	public function add() {
		return $this->edit();
	}
	//@店铺修改
	public function edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$name = $this->request->post('name');
			$member_id = $this->request->post('member_id/d', 0);
			$mobile = $this->request->post('mobile');
			$tel = $this->request->post('tel');
			$shop_type = $this->request->post('shop_type/d', 0);
			$status = $this->request->post('status/d', 0);
			$province = $this->request->post('province/d', 440000);
			$city = $this->request->post('city/d', 440100);
			$district = $this->request->post('district/d', 0);
			$address = $this->request->post('address');
			$description = $this->request->post('description');
			$longitude = $this->request->post('longitude');
			$latitude = $this->request->post('latitude');
			$sort = $this->request->post('sort', 999);
			$wifi = $this->request->post('wifi/d', 0);
			$pickup = $this->request->post('pickup/d', 0);
			$app_discount = $this->request->post('app_discount/f', 0);
			$business_time = $this->request->post('business_time');
			$poster_pic = upload_file('shop', 'poster_pic');
			$data = compact('name', 'member_id', 'mobile', 'shop_type', 'status', 'province', 'city', 'district', 'description', 'address', 'tel',
				'longitude', 'latitude', 'sort', 'wifi', 'pickup', 'poster_pic', 'app_discount', 'business_time');
			if ($id > 0) {
				\app\model\Shop::update($data, ['id'=>$id]);
			} else {
				$data['add_time'] = time();
				$row = \app\model\Shop::create($data);
				$id = $row->id;
			}
			//清除该店铺的所属会员
			\app\model\Member::where('shop_id', $id)->update(['shop_id'=>0]);
			if ($member_id > 0) {
				\app\model\Member::whereId($member_id)->update(['shop_id'=>$id]);
			}
			return success('tourl:shop/index', '提交成功');
		} else if ($id > 0) {
			$row = \app\model\Shop::whereId($id)->find();
		} else {
			$row = t('shop');
		}
		$member = \app\model\Member::where('status', 1)->order('id', 'DESC')->field('id, name, nick_name, real_name')->select()->each(function($item) {
			if ($item->nick_name) $item->name = $item->nick_name;
			else if ($item->real_name) $item->name = $item->real_name;
		});
		return $this->render([
			'row' => $row,
			'member' => $member,
		], 'edit');
	}
	
	//@店铺删除
	public function delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('数据错误');
		\app\model\Shop::destroy(['id'=>$id]);
		MemberFavorite::where(['item_id'=>$id, 'type'=>2])->delete(); //删除收藏
		return success('tourl:shop/index', '操作成功');
	}
}
