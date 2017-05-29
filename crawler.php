<?php

require_once (dirname(__FILE__) . '/lib/config.php');
require_once (dirname(__FILE__) . '/lib/Base.php');

/**
 * クロールマネージャー
 */

class crawl extends Base {

	function __construct(){
		Base::__construct();
		$this->start();
	}

	function start(){
		// クロールデータ格納配列
		$allCompanyData = array();

		// 各ページクロール処理
		// 最大ページ数 21ページ(現在は1ページのみにセット。 全クロールなら$this->pの比較数を1->21に変更したらOK)
		for($this->p =1; $this->p <= 21; $this->p++){
			$allCompanyData = array_merge($allCompanyData, $this->Crawl('http://joujou.skr.jp/page'.str_pad($this->p, 3, 0, STR_PAD_LEFT).'.html'));
		}

		// 全クロールデータを保存
		$this->Save($allCompanyData);

		// 上でクロールした後に詳細情報を取得し更新する
		foreach ($allCompanyData as $c){
			$this->MoreCrawl($c[3]);
		}
	}

}

new crawl();

?>