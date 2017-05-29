<?php

require_once (dirname(__FILE__) . '/lib/config.php');
require_once (dirname(__FILE__) . '/lib/Base.php');

/**
 * 高速詳細クロールマネージャー
 */

class d_crawl extends Base {

	function __construct(){
		Base::__construct();
		$this->start();
	}

	function start(){
		// データ取得
		$sql = "SELECT cm.brand_code FROM tbl_company_mst cm WHERE cm.company_id > 730";
		//$sql = "SELECT cm.brand_code FROM tbl_company_mst cm";
		$data = $this->GetAll($sql);

		$counter = 0;
		foreach ($data as $d){
			$counter++;
			echo $counter ."/". count($data);
			$this->MoreCrawl($d['brand_code']);
		}
	}

}

new d_crawl();

?>