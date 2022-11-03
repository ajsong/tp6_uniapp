<?php
declare (strict_types = 1);

namespace app\index\controller;


class Index extends Core
{
	
	/**
	 * showdoc
	 * @catalog 首页
	 * @title 首页
	 * @method get
	 * @url #
	 * @return {}
	 */
	public function index() {
		if (!IS_AJAX) {
			$code = $this->request->get('code');
			$this->redirect('/h5/#/' . ($code ? '?code='.$code : ''));
		}
		$money = 0;
		$win = 0;
		if ($this->memberId) {
			$money = $this->memberObj->money;
			$win = \app\model\ConTransaction::alias('t')
				->leftJoin(['con_strategy'=>'s'], 't.strategy_id=s.id')
				->where([
					['t.uid', '=', $this->memberId],
					['t.status', '<>', 0],
					//['t.rp', '>', 0],
				])->whereTime('t.timestamp', 'today')->sum('t.rp');
		}
		return success([
			'banner' => \app\model\Banner::get('flash', $this->lang),
			'notic' => \app\model\Article::where('status', 1)->order(['sort', 'id'=>'desc'])->field('id, title')->select(),
			'money' => $money,
			'win' => $win,
		]);
	}
	
	/**
	 * showdoc
	 * @catalog 首页
	 * @title 获取okx的K线数据
	 * @method get
	 * @url #
	 * @param instId 1 string instId
	 * @param bar 0 string bar
	 * @param after 0 string after
	 * @param before 0 string before
	 * @param limit 0 string limit
	 * @return {}
	 */
	public function okx() {
		$instId = $this->request->get('instId', 'BTC-USD-SWAP');
		$bar = $this->request->get('bar');
		$after = $this->request->get('after');
		$before = $this->request->get('before');
		$limit = $this->request->get('limit');
		$res = file_get_contents("https://www.okx.com/api/v5/market/candles?instId=$instId&bar=$bar&after=$after&before=$before&limit=$limit");
		return json(json_decode($res, true));
	}
}
