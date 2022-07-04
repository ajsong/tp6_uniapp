<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class GoodsCategory extends Core
{
	//获取分类
	public static function get_categories($parent_id = 0) {
		$list = self::where(['status'=>1, 'parent_id'=>$parent_id])->order(['sort', 'id'])->field('*, null as categories')->select()->toArray();
		foreach ($list as $k => $g) {
			$list[$k]['categories'] = self::get_categories($g['id']);
		}
		return $list;
	}
	
	//生成分类的option, separated,parents_and_me不用设置,函数递归用, attributes键值:key自定义属性名称,value在categories里的字段名
	public static function set_categories_option($categories, $selected_id=0, $attributes=array(), $separated='', $parents_and_me='') {
		if (!is_array($categories)) return '';
		$html = '';
		foreach ($categories as $k => $g) {
			$html .= '<option value="'.$g['id'].'" tree="'.$parents_and_me.$g['id'].'"';
			if ($g['id'] == $selected_id) $html .= ' selected';
			foreach ($attributes as $name=>$value) {
				$html .= " {$name}=\"".preg_replace_callback('/\((\w+)\)/', function($matches) use ($g) {
						return str_replace('"', '\"', $g[$matches[1]] ?? '');
					}, $value)."\"";
			}
			$html .= '>'.$separated.($k==count($categories)-1?'└':'├').$g['name'].'</option>';
			if (is_array($g['categories']) && count($g['categories'])) {
				$html .= self::set_categories_option($g['categories'], $selected_id, $attributes, '　'.$separated, $parents_and_me.$g['id'].',');
			}
		}
		return $html;
	}
	
	//获取分类与所有上级的id
	public static function get_category_parents_tree($category_id) {
		$ids = $category_id;
		$row = self::where(['status'=>1, 'id'=>$category_id])->field('parent_id')->find();
		if ($row && $row->parent_id>0) $ids = self::get_category_parents_tree($row->parent_id) . ',' . $ids;
		return $ids;
	}
	
	//获取分类与所有下级的id
	public static function get_category_children_tree($category_id) {
		$ids = $category_id;
		$rs = self::where(['status'=>1, 'parent_id'=>$category_id])->field('id')->select();
		if ($rs) {
			foreach ($rs as $g) {
				$ids .= ',' . $g->id;
				if (self::where(['status'=>1, 'parent_id'=>$g->id])->count()) $ids .= ',' . self::get_category_children_tree($g->id);
			}
		}
		$ids = trim($ids, ',');
		return $ids;
	}
}
