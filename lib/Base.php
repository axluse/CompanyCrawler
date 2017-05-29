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
	function MoreCrawl($brandCode){
		$URL = "http://xn--vckya7nx51ik9ay55a3l3a.com/companies/" . $brandCode;
		$htmlFile = file_get_contents($URL);
		$htmlObj = phpQuery::newDocument($htmlFile);
		$companyData = array();
		$detailAddress = "";
		$counter = 0;

		// クロール対象項目が17個以上のもののみ更新対象
		if(count($htmlObj['dd.companies_data']) > 16){
			foreach($htmlObj['dd.companies_data'] as $val) {
				// 業種
				if($counter == 0){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// Edinetコード
				} else if($counter == 1){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 法人番号
				} else if($counter == 2){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 本店所在地(詳細)
				} else if($counter == 3){
					$detailAddress = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 決済日
				} else if($counter == 4){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 上場年月日
				} else if($counter == 5){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// コーポレートサイトURL
				} else if($counter == 6){
					//$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 最新開示期間
				} else if($counter == 7){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 会計基準
				} else if($counter == 8){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 会計監査人
				} else if($counter == 9){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 資本金
				} else if($counter == 10){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 連結財務諸表有無
				} else if($counter == 11){
					//$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 特定事業規則(連携)
				} else if($counter == 12){
					//$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 特定事業規則(単体)
				} else if($counter == 13){
					//$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 従業員数
				} else if($counter == 14){
					if(count($htmlObj['dd.companies_data']) < 17){
						$companyData[] = $this->DelAllSpace(pq($val)->text());
					} else {
						$companyData[] = "不明";
						$companyData[] = $this->DelAllSpace(pq($val)->text());
					}
					$counter++;
					// 平均年齢
				} else if($counter == 15){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 平均勤務年数
				} else if($counter == 16){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter++;
					// 年間給与
				} else if($counter == 17){
					$companyData[] = $this->DelAllSpace(pq($val)->text());
					$counter = 0;
				}
			}

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

			$param = array($detailAddress);
			$arrBrandCode = array($brandCode);
			$param = array_merge($param, $companyData);
			$param = array_merge($param, $arrBrandCode);

			$this->Execute($sql, $param);
		}
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
		return str_replace( array( " ", "　", "	", "\n"), "", $string);
	}
}

new Base();

?>