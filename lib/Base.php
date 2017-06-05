<?php

/**
 * BASE
 */
require_once ('config.php');
require_once ('adodb/adodb.inc.php');
require_once ('phpQuery-onefile.php');

/* DEBUG */
require_once ('ChromePhp.php');

Class Base {

	protected $db;
	protected $p = 0;

	function __construct(){
		session_start();
		// ADODBを使用
		$this->db = &NewADOConnection(DSN);
		$this->db->SetFetchMode(ADODB_FETCH_ASSOC);
		$this->db->Execute('SET NAMES utf8');
		$this->db->debug = false;
	}

	/**
	 * ○クロールpart1
	 */
	public function Crawl($URL){
		$htmlFile = file_get_contents($URL);
		$htmlObj = phpQuery::newDocument($htmlFile);
		$counter = 0;
		$companyData = array();

		foreach($htmlObj['td.hpb-cnt-cell3-x td'] as $val) {
			// 区画名以外は会社情報
			if(pq($val)->attr('colspan') != 6){

				// 企業名
				if($counter == 0){
					$domUrl = pq($val)->find("a")->attr("href");

					$counter++;

					$company = array();
					$company[] = $this->DelAllSpace(pq($val)->text());
					$company[] = $this->DelAllSpace($domUrl);

				// 株価URL
				} else if($counter == 1){
					$domUrl = pq($val)->find("a")->attr("href");

					$counter++;

					$company[] = $this->DelAllSpace($domUrl);

				// 銘柄コード
				} else if($counter == 2){

					$counter++;

					$company[] = $this->DelAllSpace(pq($val)->text());

				// 取引所識別コード
				} else if($counter == 3){

					$counter++;

					$company[] = $this->DelAllSpace(pq($val)->text());

				// 所在地
				} else if($counter == 4){
					$domUrl = pq($val)->find("a")->attr("href");

					$counter++;

					$company[] = $this->DelAllSpace(pq($val)->text());
					$company[] = $this->DelAllSpace($domUrl);

				// 優待URL
				} else if($counter == 5){
					$domUrl = pq($val)->find("a")->attr("href");

					$counter = 0;

					$company[] = $this->DelAllSpace($domUrl);
					$companyData[] = $company;
				}
			}
		}
		ob_flush();
		flush();

		return $companyData;
	}

	/**
	 * クロールPart2
	 */
	function MoreCrawl($brandCode, $getParamMode = false){
		$URL = "http://xn--vckya7nx51ik9ay55a3l3a.com/companies/" . $brandCode;
		$htmlFile = file_get_contents($URL);
		$htmlObj = phpQuery::newDocument($htmlFile);
		$detailAddress = "";

		$keys = array();
		$vals = array();
		$data = array();

		foreach($htmlObj['dt.companies_title'] as $key) {
			$keys[] = $key;
		}

		foreach($htmlObj['dd.companies_data'] as $val) {
			$vals[] = $val;
		}

		for($i = 0; $i < count($keys); $i++) {
			$data[] = array($this->DelAllSpace(pq($keys[$i])->text()) , $this->DelAllSpace(pq($vals[$i])->text()));
		}

		$insertFlg = false;
		$count = 0;
		foreach ($data as $val){

			if($val[0] == "業種"){
				$param = array();
				$param[] = $val[1];
			}

			if($val[0] == "Edinetコード"){
				$param[] = $val[1];
			}

			if($val[0] == "法人番号"){
				$param[] = $val[1];
			}

			if($val[0] == "本店所在地"){
				$detailAddress = $val[1];
			}

			if($val[0] == "決算日"){
				$param[] = $val[1];
			}

			if($val[0] == "上場年月日"){
				$param[] = $val[1];
			}

			if($val[0] == "最新開示期間"){
				$param[] = $val[1];
			}

			if($val[0] == "採用している会計基準"){
				$param[] = $val[1];
			}

			if($val[0] == "会計監査人"){
				$param[] = $val[1];
			}

			if($val[0] == "資本金"){
				$param[] = $val[1];
			}

			if($val[0] == "従業員数"){
				$param[] = $val[1];
			}

			if($val[0] == "平均年齢"){
				$param[] = $val[1];
			}

			if($val[0] == "平均勤続年数"){
				$param[] = $val[1];
			}

			if($val[0] == "年間給与"){
				$param[] = $val[1];
				$param = array_merge(array($detailAddress) , $param);
				$param = array_merge($param, array($brandCode));
			}
		}

		if($getParamMode){
			return $param;
		} else {
			$sql = "UPDATE tbl_company_mst
			SET address = ?,
				industry = ?,
				edinet_code = ?,
				corporate_num = ?,
				settlement_date = ?,
				listing_date = ?,
				disclosure_period = ?,
				account_standard = ?,
				account_auditor = ?,
				capital = ?,
				employees = ?,
				average_age = ?,
				average_work = ?,
				annual_income = ?
			WHERE brand_code = ?";

			$this->Execute($sql, $param);
		}

		flush();
		ob_flush();
	}

	/**
	 * ●新規上場企業サーチ
	 */
	function getNewPublicCompany(){
		$URL = "http://www.jpx.co.jp/listing/stocks/new/index.html";
		$htmlFile = file_get_contents($URL);
		$htmlObj = phpQuery::newDocument($htmlFile);


		$counter = 1;
		foreach ($htmlObj['tbody tr td'] as $val){
			// 一時データ格納用の配列を初期化
			if($counter == 1){
				$arrData = array();

			// 企業名 / 銘柄コード取得 (スペース/改行文字等削除)
			} else if($counter == 2 || $counter == 3){
				$arrData[] = ($this->DelAllSpace(pq($val)->text()));

			// 対象銘柄コードが存在しているか確認
			} else if($counter == 14){
				// DBに未登録の会社
				if(!$this->searchBCtoCN($arrData[1])){

					$targetBrandCode = (strval(intval($arrData[1])));

					$param = $this->MoreCrawl($targetBrandCode, true);

					$arrData[0] = str_replace(array("代表者インタビュー", "＊", "*"), "", $arrData[0]);
					$arrData[1] = intval($arrData[1]);

					$sql  = // 新規追加用
					"INSERT INTO tbl_company_mst (
						company_name,
						brand_code,
						exchange_code,
						address,
						industry,
						edinet_code,
						corporate_num,
						settlement_date,
						listing_date,
						disclosure_period,
						account_standard,
						account_auditor,
						capital,
						employees,
						average_age,
						average_work,
						annual_income,
						ins_date,
						upd_date
						) VALUES (
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							?,
							NOW(),
							NOW()
						)";

					$param = (array_merge($arrData, $param));
					array_pop($param);

					$this->Execute($sql, $param);
				}

			// 取引所取得 (スペース/改行文字等削除)
			} else if($counter == 9){
				$data = strval($this->DelAllSpace(pq($val)->text()));
				if($data == "第一部"){
					$data = "東１";
				} else if($data == "第二部"){
					$data = "東２";
				} else if($data == "JQスタンダード"){
					$data = "ＪＱ";
				} else if($data == "マザーズ"){
					$data = "マザ";
				}

				$arrData[] = $data;
			}
			$counter++;
			if($counter == 15){
				$counter = 1;
			}
		}
	}

	/**
	 * ●上場企業名 - ブランドコードサーチ
	 */
	function searchBCtoCN($brandCode){
		$sql = "SELECT cm.company_name FROM tbl_company_mst cm WHERE cm.brand_code = ?";
		$companyName = $this->GetOne($sql, array($brandCode));
		return $companyName;
	}

	/**
	 * 保存
	 */
	function Save($companyAllData){

		$findSQL = "SELECT cm.company_id FROM tbl_company_mst cm WHERE cm.brand_code = ?";

		$insSQL  = // 新規追加用
			"INSERT INTO tbl_company_mst (
				company_name,
				brand_code,
				exchange_code,
				address,
				address_url,
				stock_price_url,
				incentive_url,
				company_site_url,
				ins_date,
				upd_date
				) VALUES (
					?,
					?,
					?,
					?,
					?,
					?,
					?,
					?,
					NOW(),
					NOW()
				)";

		$updSQL = // 更新用
			"UPDATE tbl_company_mst
			 SET  company_name = ?,
				  brand_code = ?,
				  exchange_code = ?,
				  address = ?,
				  address_url = ?,
				  stock_price_url = ?,
				  incentive_url = ?,
				  company_site_url = ?,
				  upd_date = NOW()
			WHERE company_id = ?";

		foreach ($companyAllData as $companyData){
			$company_id = $this->GetOne($findSQL, array(intval($companyData[3])));

			// 更新
			if(isset($company_id)){
				$param = array(
						$companyData[0],
						$companyData[3],
						$companyData[4],
						$companyData[5],
						$companyData[6],
						$companyData[2],
						$companyData[7],
						$companyData[1],
						$company_id
				);

				$this->Execute($updSQL, $param);

			// 新規
			} else {
				$param = array(
						$companyData[0],
						$companyData[3],
						$companyData[4],
						$companyData[5],
						$companyData[6],
						$companyData[2],
						$companyData[7],
						$companyData[1]
				);

				$this->Execute($insSQL, $param);
			}

		}
	}

	/**
	 * SQL
	 */
	function GetOne($sql, $param){
		return $this->db->GetOne($sql, $param);
	}

	function GetRow($sql, $param){
		return $this->db->GetRow($sql, $param);
	}

	function GetAll($sql, $param){
		return $this->db->GetAll($sql, $param);
	}

	function Execute($sql, $param){
		return $this->db->Execute($sql, $param);
	}

	function ErrorMsg(){
		return $this->db->ErrorMsg();
	}

	/**
	 * 空白削除
	 */
	function DelAllSpace($string){
		return str_replace( array( "\xc2\xa0", " ", "　", "	", "\n", "\r"), "", $string);
	}

}

new Base();

?>