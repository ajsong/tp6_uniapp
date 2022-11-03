<?php
namespace EthTool;

class Callback {
	function __invoke($error, $result = '') {
		$this->error = $error;
		$this->result = $result;
	}
}
