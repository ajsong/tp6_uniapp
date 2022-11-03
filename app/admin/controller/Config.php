<?php
declare (strict_types = 1);

namespace app\admin\controller;

use think\facade\Cache;

//@system
class Config extends Core
{
    public function index() {
		$where = [];
		if ($this->manageSuper != 1) $where[] = ['status', '=', 1];
		$list = \app\model\Config::where($where)->order(['status', 'id'])
			->paginate($this->paginateArr())->each(function($item) {
				$item->placeholder = '';
				$item->attr = '';
				$item->fileType = '';
				$item->memo = str_replace('<font ', '<font style="float:none;" ', $item->memo);
				if (stripos($item->memo, '，')!==false || stripos($item->memo, ',')!==false) {
					$comma = stripos($item->memo, '，')!==false ? '，' : ',';
					$offset = stripos($item->memo, '，')!==false ? 3 : 1;
					$item->placeholder = substr($item->memo, stripos($item->memo, $comma)+$offset);
					$item->memo = substr($item->memo, 0, stripos($item->memo, $comma));
				}
				if (!$item->type) $item->type = 'text';
				if (preg_match('/^file/', $item->type)) {
					//file|可上传后缀(逗号隔开,默认图片)|附加attr
					$con = explode('|', $item->type);
					$item->type = $con[0];
					if (count($con) > 1) $item->fileType = $con[1];
					if (count($con) > 2) $item->attr = $con[2];
					$is_image = is_image($item->value) ? 1 : 0;
					$item->is_image = $is_image;
					$item->content = $is_image ? add_domain($item->value) : $item->value;
				} else if (preg_match('/^checkbox-app/', $item->type)) {
					//checkbox-app|文字|附加attr
					$con = explode('|', $item->type);
					$item->type = $con[0];
					$name = random_str(8);
					$text = count($con) > 1 ? $con[1] : '';
					if (count($con) > 2) $item->attr = $con[2];
					$item->content = '<input type="checkbox" data-type="checkbox-app" id="value_'.$name.'" class="config-value" value="1" '.(intval($item->value)==1?'checked':'').' '.$item->attr.($text?' data-text-class="col-item" data-text-style="padding-top:4px;" data-text="'.$text.'"':'').' />';
				} else if (preg_match('/^(radio|checkbox|select|switch)/', $item->type)) {
					//[radio|checkbox|select|switch]|值1:字1#值2:字2|附加attr
					$con = explode('|', $item->type);
					$item->type = $con[0];
					if (count($con) < 2 || !preg_match('/:/', $con[1])) return $item;
					$cons = explode('#', $con[1]);
					if (count($con) > 2) $item->attr = $con[2];
					$content = '';
					if ($item->type=='radio' || $item->type=='checkbox') {
						$content .= '<span class="config-value">';
					} else if ($item->type=='select') {
						$content .= '<select class="form-control config-value" '.$item->attr.'>';
					} else if ($item->type=='switch') {
						$content .= '<span class="col-switch pull-left config-value">';
					}
					$name = random_str(8);
					foreach ($cons as $h) {
						$g = explode(':', $h);
						if ($item->type=='radio') {
							$content .= '<input type="radio" data-type="radio" data-text="'.$g[1].'" name="value_'.$name.'" value="'.$g[0].'" '.($item->value==$g[0]?'checked':'').' '.$item->attr.' />';
						} else if ($item->type=='checkbox') {
							$content .= '<input type="checkbox" data-type="checkbox" data-text="'.$g[1].'" name="value_'.$name.'" value="'.$g[0].'" '.($item->value==$g[0]?'checked':'').' '.$item->attr.' />';
						} else if ($item->type=='select') {
							$content .= '<option value="'.$g[0].'" '.($item->value==$g[0]?'selected':'').'>'.$g[1].'</option>';
						} else if ($item->type=='switch') {
							$content .= '<label><input type="radio" name="value_'.$name.'" value="'.$g[0].'" '.($item->value==$g[0]?'checked':'').' '.$item->attr.' /><div>'.$g[1].'</div></label>';
						}
					}
					if ($item->type=='radio' || $item->type=='checkbox') {
						$content .= '</span>';
					} else if ($item->type=='select') {
						$content .= '</select>';
					} else if ($item->type=='switch') {
						$content .= '</span>';
					}
					$item->content = $content;
				} else if (stripos($item->type, 'color')!==false) {
					//color|占位符|附加attr
					if (stripos($item->type, '|')!==false) {
						$con = explode('|', $item->type);
						$item->type = $con[0];
						$item->placeholder = $con[1];
						if (count($con) > 2) $item->attr = $con[2];
					}
					$item->content = '<input type="text" class="col-sm-3 config-value" value="'.$item->value.'" placeholder="'.$item->placeholder.'" '.$item->attr.' /><div class="col-color pull-left" color="'.$item->value.'" style="background:'.$item->value.';"></div>';
				} else {
					//[text|password|number|date|textarea]|占位符|附加attr
					if (stripos($item->type, '|')!==false) {
						$con = explode('|', $item->type);
						$item->type = $con[0];
						$item->placeholder = $con[1];
						if (count($con) > 2) $item->attr = $con[2];
					}
					$item->content = $item->value;
				}
				$item->placeholder = str_replace('"', '&#34', $item->placeholder);
				return $item;
			});
		return $this->render([
			'list' => $list
		]);
    }
	
	//@系统参数修改
	public function edit() {
		$memo = $this->request->post('memo');
		$key = $this->request->post('key');
		$value = $this->request->post('value', '');
		$type = $this->request->post('type');
		if (!$memo && !$key) error('数据错误');
		if ($memo) {
			if ($this->manageObj->super != 1) error('数据错误');
			if (\app\model\Config::whereKey($key)->count() > 0) error('参数已存在');
			\app\model\Config::create(compact('memo', 'key', 'value', 'type'));
		} else {
			\app\model\Config::update(compact('value'), ['key'=>$key]);
		}
		Cache::delete('config:data');
		return success(null, '提交成功');
	}
}
