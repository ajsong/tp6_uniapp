<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\Base;

class Error extends Base
{
	public function __call($method, $args)
	{
		error('THIS PAGE MAY BE ON MARS.');
	}
}
