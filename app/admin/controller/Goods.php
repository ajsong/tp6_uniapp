<?php
declare (strict_types = 1);

namespace app\admin\controller;


use app\model\Brand;
use app\model\City;
use app\model\District;
use app\model\GoodsCategory;
use app\model\GoodsGradePrice;
use app\model\GoodsPic;
use app\model\GoodsSpec;
use app\model\GoodsSpecCategory;
use app\model\GoodsSpecLinkage;
use app\model\MemberFavorite;
use app\model\MemberGrade;
use app\model\Province;
use app\model\ShippingFee;
use app\model\ShippingFeeArea;
use app\model\Shop;

//@goods
class Goods extends Core
{
    public function index() {
	    $id = $this->request->get('id/d', 0);
	    $keyword = $this->request->get('keyword');
	    $start_price = $this->request->get('start_price');
	    $end_price = $this->request->get('end_price');
	    $category_id = $this->request->get('category_id/d', 0);
	    $shop_id = $this->request->get('shop_id/d', 0);
	    $brand_id = $this->request->get('brand_id/d', 0);
	    $status = $this->request->get('status');
	    $ext_property = $this->request->get('ext_property');
	    $where = [];
	    $whereRaw = '';
	    if ($id > 0) $where[] = ['g.id', '=', $id];
	    if ($keyword) $where[] = ['g.name|g.description|g.content', 'like', "%$keyword%"];
	    if ($start_price) $where[] = ['g.price', '>=', $start_price];
	    if ($end_price) $where[] = ['g.price', '<=', $end_price];
	    if ($shop_id > 0) $where[] = ['g.shop_id', '=', $shop_id];
	    if ($category_id > 0) {
		    $categories = GoodsCategory::get_category_children_tree($category_id);
		    $where[] = ['g.category_id', 'in', $categories];
	    }
	    if ($brand_id > 0) $where[] = ['g.brand_id', '=', $brand_id];
	    if (!is_null($status)) $where[] = ['g.status', '=', $status];
	    if ($ext_property) {
		    if (!is_array($ext_property)) $ext_property = explode(',', $ext_property);
		    foreach ($ext_property as $e) {
			    $whereRaw .= "CONCAT(',',g.ext_property,',') LIKE '%,$e,%' OR ";
		    }
		    $whereRaw = trim(trim($whereRaw), ' OR');
	    }
	    $list = \app\model\Goods::alias('g')
		    ->leftJoin('goods_category c', 'g.category_id=c.id')
		    ->leftJoin('brand b', 'g.brand_id=b.id')
		    ->leftJoin('shop s', 'g.shop_id=s.id')
		    ->where($where)->order('g.id', 'DESC')->field("g.*, c.name as category_name, s.name as shop_name, '' as url");
		if (strlen($whereRaw)) $list = $list->whereRaw($whereRaw);
		$list = $list->paginate($this->paginateArr())->each(function($item) {
			$item->pic = add_domain($item->pic);
			$item->url = urlencode(https().$this->request->server('HTTP_HOST')."/index/goods/detail?id={$item->id}&qrcode=1");
		});
	
	    $shops = Shop::order('id')->field('id, name')->select();
	    //品牌
	    $brands = Brand::whereStatus(1)->order(['sort', 'id'])->field('id, name')->select();
	    //分类
	    $categories = GoodsCategory::get_categories();
	    $categories = GoodsCategory::set_categories_option($categories, $category_id);
	    return $this->render([
			'list' => $list,
			'shops' => $shops,
			'brands' => $brands,
			'categories' => $categories,
	    ]);
    }
	
	//@设置商品扩展
	public function ext_property() {
		$id = $this->request->post('id/d', 0);
		$checked = $this->request->post('checked/d', 0);
		$ext_property = $this->request->post('ext_property/d');
		if ($id <= 0) error('数据错误');
		$ext = \app\model\Goods::whereId($id)->value('ext_property');
		if (strpos(",$ext,", ",$ext_property,") === false) {
			if ($checked) {
				\app\model\Goods::whereId($id)->update(['ext_property'=>$ext ? "$ext,$ext_property" : $ext_property]);
			}
		} else {
			if (!$checked) {
				$ext = preg_replace("/,?$ext_property/", '', $ext);
				\app\model\Goods::whereId($id)->update(['ext_property'=>$ext]);
			}
		}
		return success();
	}
	
	//@商品添加
	public function add() {
		return $this->edit();
	}
	//@商品修改
	public function edit() {
		$id = $this->request->get('id/d', 0);
		$category_id = 0;
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$type = $this->request->post('type/d', 0);
			$name = $this->request->post('name');
			$price = $this->request->post('price/f', 0);
			$promote_price = $this->request->post('promote_price/f', 0);
			$promote_begin_time = $this->request->post('promote_begin_time');
			$promote_end_time = $this->request->post('promote_end_time');
			$market_price = $this->request->post('market_price/f', 0);
			$cost_price = $this->request->post('cost_price/f', 0);
			$description = $this->request->post('description');
			$keywords = $this->request->post('keywords');
			$content = $this->request->post('content');
			$category_id = $this->request->post('category_id/d', 0);
			$shop_id = $this->request->post('shop_id/d', 0);
			$brand_id = $this->request->post('brand_id/d', 0);
			$stocks = $this->request->post('stocks/d', 0);
			$stock_alert_number = $this->request->post('stock_alert_number/d', 0);
			$ext_property = $this->request->post('ext_property');
			$free_shipping = $this->request->post('free_shipping/f', 0);
			$shipping_fee = $this->request->post('shipping_fee/f', 0);
			$shipping_fee_id = $this->request->post('shipping_fee_id/d', 0);
			$weight = $this->request->post('weight/f', 0);
			$free_shipping_count = $this->request->post('free_shipping_count/d', 0);
			$integral = $this->request->post('integral/d', 0);
			$give_integral = $this->request->post('give_integral/d', 0);
			$sort = $this->request->post('sort/d', 0);
			$status = $this->request->post('status/d', 0);
			$release_time = $this->request->post('release_time');
			$seller_note = $this->request->post('note');
			$in_shop = $this->request->post('in_shop/d', 0);
			$sale_method = $this->request->post('sale_method/d', 0);
			
			$pics = $this->request->post('pics');
			$videos = $this->request->post('videos');
			$memos = $this->request->post('memos');
			
			$grade_id = $this->request->post('grade_id');
			$grade_price = $this->request->post('grade_price');
			
			$params_name = $this->request->post('params_name');
			$params_value = $this->request->post('params_value');
			
			$commission_type = $this->request->post('commission_type/d', 0);
			$commissions = $this->request->post('commissions');
			
			$spec_id = $this->request->post('spec_id');
			$spec_subid = $this->request->post('spec_subid');
			$spec_tree = $this->request->post('spec_tree');
			$spec_price = $this->request->post('spec_price');
			$spec_stock = $this->request->post('spec_stock');
			$spec_promote = $this->request->post('spec_promote');
			$spec_groupbuy = $this->request->post('spec_groupbuy');
			$spec_purchase = $this->request->post('spec_purchase');
			$spec_chop = $this->request->post('spec_chop');
			$spec_pic = $this->request->post('spec_pic');
			
			$pic = upload_file('goods', 'pic');
			$ad_pic = upload_file('goods', 'ad_pic');
			if ($promote_begin_time) $promote_begin_time = strtotime($promote_begin_time);
			if ($promote_end_time) $promote_end_time = strtotime($promote_end_time);
			if ($release_time) $release_time = strtotime($release_time);
			if ($free_shipping==1) {
				$shipping_fee = 0;
				$shipping_fee_id = 0;
			} else {
				if ($shipping_fee_id>0) $shipping_fee = 0;
				else $shipping_fee_id = 0;
			}
			if ($stock_alert_number<0) $stock_alert_number = 0;
			if (is_array($commissions)) $commissions = implode(',', $commissions);
			if (is_array($ext_property)) $ext_property = implode(',', $ext_property);
			if ($type>0) {
				$free_shipping = 1;
				$shipping_fee = 0;
				$shipping_fee_id = 0;
			}
			if ($free_shipping_count<0) $free_shipping_count = 0;
			
			$params = array();
			if (is_array($params_name)) {
				foreach ($params_name as $k=>$p) {
					if (!strlen($p) || !strlen($params_value[$k])) continue;
					$params[] = $p.'`'.$params_value[$k];
				}
			}
			$params = implode('^', $params);
			
			if ($status != -1) $release_time = null;
			
			$data = compact('type', 'name', 'description', 'keywords', 'content', 'price', 'market_price', 'cost_price', 'promote_price', 'promote_begin_time',
				'promote_end_time', 'pic', 'ad_pic', 'integral', 'give_integral', 'category_id', 'shop_id', 'brand_id', 'stocks', 'stock_alert_number',
				'ext_property', 'shipping_fee', 'shipping_fee_id', 'free_shipping', 'weight', 'free_shipping_count', 'commission_type', 'commissions', 'params', 'sort',
				'status', 'release_time', 'seller_note', 'in_shop', 'sale_method');
			if ($id>0) {
				$data['edit_time'] = time();
				\app\model\Goods::update($data, ['id'=>$id]);
			} else {
				$data['add_time'] = time();
				$row = \app\model\Goods::create($data);
				$id = $row->id;
			}
			//等级会员价
			GoodsGradePrice::where('goods_id', $id)->delete();
			if (is_array($grade_id) && is_array($grade_price)) {
				$did = $dprice = [];
				foreach ($grade_id as $k=>$g) {
					if (!strlen($g) || in_array($g, $did) || !isset($grade_price[$k]) || floatval($grade_price[$k])<=0) continue;
					$price = floatval($grade_price[$k]);
					$did[] = $g;
					$dprice[] = $price;
				}
				GoodsGradePrice::createAll(['goods_id'=>$id, 'grade_id'=>$did, 'price'=>$dprice], 'grade_id');
			}
			//商品规格
			GoodsSpecLinkage::where('goods_id', $id)->delete();
			GoodsSpec::where('goods_id', $id)->delete();
			if (is_array($spec_id) && is_array($spec_subid) && is_array($spec_tree)) {
				//保存规格分类联动
				$did = $dparent = [];
				foreach ($spec_id as $g) {
					if (!strlen($g)) continue;
					$did[] = $g;
					$dparent[] = 0;
				}
				$insertData = [];
				foreach ($spec_subid as $g) {
					if (!strlen($g)) continue;
					$did[] = $g;
					$parent_id = intval(GoodsSpecCategory::whereId($g)->value('parent_id'));
					$dparent[] = $parent_id;
					$insertData[] = ['goods_id'=>$id, 'spec_id'=>$did, 'parent_id'=>$dparent];
				}
				$linkage = new GoodsSpecLinkage();
				$linkage->saveAll($insertData);
				//保存规格价格
				$dtree = $dprice = $dstock = $dpromote = $dgroupbuy = $dpurchase = $dchop = $dpic = array();
				foreach ($spec_tree as $k=>$g) {
					if (!strlen($g) || !isset($spec_price[$k]) || floatval($spec_price[$k])<=0 || !isset($spec_stock[$k]) || intval($spec_stock[$k])<=0) continue;
					$dtree[] = $g;
					$dprice[] = floatval($spec_price[$k]) ?: 0;
					$dstock[] = intval($spec_stock[$k]) ?: 1;
					$dpromote[] = (isset($spec_promote[$k]) && floatval($spec_promote[$k])) ? (max(floatval($spec_promote[$k]), 0)) : 0;
					$dgroupbuy[] = (isset($spec_groupbuy[$k]) && floatval($spec_groupbuy[$k])) ? (max(floatval($spec_groupbuy[$k]), 0)) : 0;
					$dpurchase[] = (isset($spec_purchase[$k]) && floatval($spec_purchase[$k])) ? (max(floatval($spec_purchase[$k]), 0)) : 0;
					$dchop[] = (isset($spec_chop[$k]) && floatval($spec_chop[$k])) ? (max(floatval($spec_chop[$k]), 0)) : 0;
					$dpic[] = (isset($spec_pic[$k]) && trim($spec_pic[$k])) ? trim($spec_pic[$k]) : '';
				}
				GoodsSpec::createAll(['goods_id'=>$id, 'spec'=>$dtree, 'price'=>$dprice, 'promote_price'=>$dpromote, 'groupbuy_price'=>$dgroupbuy,
					'purchase_price'=>$dpurchase, 'chop_price'=>$dchop, 'stocks'=>$dstock, 'pic'=>$dpic], 'spec');
			}
			//图片表
			GoodsPic::where('goods_id', $id)->delete();
			if (is_array($pics)) {
				$dpic = $dvideo = $dmemo = array();
				foreach ($pics as $k=>$g) {
					if (!strlen($g)) continue;
					$dpic[] = $g;
					$dvideo[] = (isset($videos[$k]) && trim($videos[$k])) ? trim($videos[$k]) : '';
					$dmemo[] = (isset($memos[$k]) && trim($memos[$k])) ? trim($memos[$k]) : '';
				}
				GoodsPic::createAll(['goods_id'=>$id, 'pic'=>$dpic, 'video'=>$dvideo, 'memo'=>$dmemo, 'add_time'=>time()], 'pic');
			}
			return success("tourl:goods/edit?id=$id", '提交成功');
		} else if ($id > 0) {
			//商品
			$row = \app\model\Goods::whereId($id)->find();
			if ($row->commissions) $row->commissions = explode(',', $row->commissions);
			$category_id = $row->category_id;
			//参数
			if ($row->params) {
				$arr = array();
				$ps = explode('^', $row->params);
				foreach ($ps as $p) {
					$s = explode('`', $p);
					$arr[] = array('name'=>$s[0], 'value'=>$s[1]);
				}
				$row->params = $arr;
			}
			//等级会员价
			$gradeprices = GoodsGradePrice::where('goods_id', $id)->select();
			//商品图
			$pics = GoodsPic::where('goods_id', $id)->select()->toArray();
			$pics = add_domain_deep($pics, ['pic']);
			//规格
			$spec = GoodsSpecLinkage::alias('gsl')
				->leftJoin('goods_spec_category gsc', 'gsl.spec_id=gsc.id')
				->where(['gsl.goods_id'=>$id, 'gsl.parent_id'=>0])->field('gsl.*, gsc.name')->select()->each(function($item) use ($id) {
					$item->sub = GoodsSpecLinkage::alias('gsl')
						->leftJoin('goods_spec_category gsc', 'gsl.spec_id=gsc.id')
						->where(['gsl.goods_id'=>$id, 'gsl.parent_id'=>$item->spec_id])->field('gsl.*, gsc.name')->select();
				});
			$specData = [];
			GoodsSpec::where('goods_id', $id)->select()->each(function($item) use (&$specData) {
				$specData['spec_price_'.str_replace(',', '_', $item->spec)] = $item->price ?: '';
				$specData['spec_promote_'.str_replace(',', '_', $item->spec)] = $item->promote_price ?: '';
				$specData['spec_groupbuy_'.str_replace(',', '_', $item->spec)] = $item->groupbuy_price ?: '';
				$specData['spec_purchase_'.str_replace(',', '_', $item->spec)] = $item->purchase_price ?: '';
				$specData['spec_chop_'.str_replace(',', '_', $item->spec)] = $item->chop_price ?: '';
				$specData['spec_stock_'.str_replace(',', '_', $item->spec)] = $item->stocks ?: '';
				$specData['spec_pic_'.str_replace(',', '_', $item->spec)] = $item->pic ?: '';
			});
		} else {
			$row = t('goods');
			$gradeprices = [];
			$pics = [];
			$spec = [];
			$specData = [];
		}
		//店铺
		$shops = Shop::where('status', 1)->order(['sort', 'id'])->field('id, name')->select();
		//品牌
		$brands = Brand::where('status', 1)->order(['sort', 'id'])->field('id, name')->select();
		//等级
		$grades = MemberGrade::where('status', 1)->order(['sort', 'id'])->field('id, name')->select();
		//分类
		$categories = GoodsCategory::get_categories();
		$categories = GoodsCategory::set_categories_option($categories, $category_id);
		//运费模板
		$shipping = ShippingFee::order('id')->select();
		return $this->render([
			'row' => $row,
			'pics' => $pics,
			'spec' => $spec,
			'specData' => json_encode($specData),
			'shops' => $shops,
			'brands' => $brands,
			'grades' => $grades,
			'categories' => $categories,
			'gradeprices' => $gradeprices,
			'shipping' => $shipping,
		], 'edit');
	}
	
	//@商品删除
	public function delete() {
		$id = $this->request->get('id/d', 0);
		\app\model\Goods::destroy(['id'=>$id]);
		GoodsSpec::where('goods_id', $id)->delete();
		MemberFavorite::where(['item_id'=>$id, 'type'=>1])->delete(); //删除收藏
		//删除该商品被添加的购物车
		//SQL::share('cart')->delete("goods_id='{$id}'");
		return success('tourl:goods/index', '操作成功');
	}
	
	//@获取规格分类
	public function get_spec_category() {
		$parent_id = $this->request->get('parent_id/d', 0);
		$list = GoodsSpecCategory::where('parent_id', $parent_id)->order('id')->select();
		return success($list);
	}
	
	//@设置规格分类
	public function set_spec_category() {
		$parent_id = $this->request->post('parent_id/d', 0);
		$name = $this->request->post('name');
		if (!$name) error('缺少规格名称');
		$id = GoodsSpecCategory::where(['parent_id'=>$parent_id, 'name'=>$name])->value('id');
		if (!$id) {
			$id = GoodsSpecCategory::insertGetId(['parent_id'=>$parent_id, 'name'=>$name]);
		}
		return success($id);
	}
	
	//@获取等级会员价
	public function get_grade_price() {
		$list = MemberGrade::where('status', 1)->order(['sort', 'id'])->select();
		return success($list);
	}
	
	//@获取运费
	public function get_shipping() {
		$list = ShippingFee::order('id')->select();
		return success($list);
	}
	
	//运费模板
	public function shipping() {
		$id = $this->request->get('id/d', 0);
		$keyword = $this->request->get('keyword');
		$where = [];
		if ($id > 0) $where[] = ['id', '=', $id];
		if ($keyword) $where[] = ['name', 'like', "%$keyword%"];
		$list = ShippingFee::where($where)->order('id')->paginate($this->paginateArr());
		return $this->render([
			'list' => $list
		]);
	}
	
	//@修改运费模板
	public function shipping_edit() {
		$id = $this->request->get('id/d', 0);
		if (IS_POST) {
			$id = $this->request->post('id/d', 0);
			$name = $this->request->post('name');
			$type = $this->request->post('type/d', 0);
			$default_first = $this->request->post('default_first/d', 0);
			$default_first_price = $this->request->post('default_first_price/f', 0);
			$default_second = $this->request->post('default_second/d', 0);
			$default_second_price = $this->request->post('default_second_price/f', 0);
			$districts = $this->request->post('districts');
			$first = $this->request->post('first');
			$first_price = $this->request->post('first_price');
			$second = $this->request->post('second');
			$second_price = $this->request->post('second_price');
			$data = compact('name', 'type');
			$data['first'] = $default_first;
			$data['first_price'] = $default_first_price;
			$data['second'] = $default_second;
			$data['second_price'] = $default_second_price;
			if ($id > 0) {
				ShippingFee::update($data, ['id'=>$id]);
			} else {
				$data['add_time'] = time();
				$row = ShippingFee::create($data);
				$id = $row->id;
			}
			ShippingFeeArea::where('shipping_fee_id', $id)->delete();
			if (is_array($districts)) {
				$data = compact('districts', 'first', 'first_price', 'second', 'second_price');
				$data['shipping_fee_id'] = $id;
				ShippingFeeArea::createAll($data, 'districts');
			}
			return success('tourl:goods/shipping', '提交成功');
		} else if ($id > 0) {
			$row = ShippingFee::whereId($id)->find();
		} else {
			$row = t('shipping_fee');
		}
		$area = ShippingFeeArea::where('shipping_fee_id', $row['id'])->select();
		$province = Province::order('province_id')->field("province_id as id, name, 0 as subcount")->select()->each(function($p) {
			$count = 0;
			$city = City::where('parent_id', $p->id)->order('city_id')->field("city_id as id, name, 0 as subcount")->select();
			if (count($city)) {
				foreach ($city as $c) {
					$district = District::where('parent_id', $c->id)->order('district_id')->field("district_id as id, name")->select();
					if (!count($district)) $district = [];
					$c->sub = $district;
					$c->subcount = count($district);
					$count += $c->subcount + 1;
				}
			} else {
				$city = [];
			}
			$p->sub = $city;
			$p->subcount = $count;
		});
		return $this->render([
			'row' => $row,
			'area' => $area,
			'province' => $province,
		]);
	}
	
	//@复制运费模板
	public function shipping_copy() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('缺少数据');
		$row = ShippingFee::whereId($id)->find();
		if (!$row) error('数据错误');
		$newId = ShippingFee::insertGetId(['name'=>$row->name, 'type'=>$row->type, 'add_time'=>time()]);
		$data = [];
		ShippingFeeArea::where('shipping_fee_id', $id)->select()->each(function($item) use (&$data, $newId) {
			$data[] = ['shipping_fee_id'=>$newId, 'districts'=>$item->districts, 'first'=>$item->first, 'first_price'=>$item->first_price,
				'second'=>$item->second, 'second_price'=>$item->second_price];
		});
		ShippingFeeArea::insertAll($data);
		return success('tourl:goods/shipping', '复制成功');
	}
	
	//@删除运费模板
	public function shipping_delete() {
		$id = $this->request->get('id/d', 0);
		if ($id <= 0) error('缺少数据');
		ShippingFee::destroy(['id'=>$id]);
		ShippingFeeArea::where('shipping_fee_id', $id)->delete();
		return success('tourl:goods/shipping', '操作成功');
	}
}
