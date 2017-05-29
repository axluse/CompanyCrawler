<?php

require_once (dirname(__FILE__) . '/lib/config.php');
require_once (dirname(__FILE__) . '/lib/Base.php');

/**
 * 高速基本情報クロールマネージャー
 */

class s_crawl extends Base {

	function __construct(){
		Base::__construct();
		$this->start();
	}

	function start(){
		// クロールデータ格納配列
		$allCompanyData = array();

		// 各ページクロール処理
		// 最大ページ数 21ページ(現在は1ページのみにセット。 全クロールなら$this->pの比較数を1->21に変更したらOK)
		for($this->p = 1; $this->p <= 1; $this->p++){
			$allCompanyData = array_merge($allCompanyData, $this->Crawl('http://joujou.skr.jp/page'.str_pad($this->p, 3, 0, STR_PAD_LEFT).'.html'));
		}

		//全クロールデータを保存
		$this->Save($allCompanyData);
	}

}

new s_crawl();

?>