<?php

require_once (dirname(__FILE__) . '/lib/config.php');
require_once (dirname(__FILE__) . '/lib/Base.php');

/**
 * 新規上場企業クロールマネージャー
 */

class newCompanyCrawl extends Base {

	function __construct(){
		Base::__construct();
		$this->start();
	}

	function start(){
		$this->getNewPublicCompany();
	}

}

new newCompanyCrawl();

?>