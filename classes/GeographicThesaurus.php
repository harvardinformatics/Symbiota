<?php
include_once ($SERVER_ROOT . '/classes/Manager.php');

class GeographicThesaurus extends Manager
{

	function __construct($type = 'write'){
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getGeograpicList($conditionTerm = null){
		$retArr = array();
		$sql = 'SELECT t.geoThesID, t.geoTerm, t.abbreviation, t.iso2, t.iso3, t.numCode, t.category, t.termStatus, a.geoterm as acceptedTerm
			FROM geographicthesaurus t LEFT JOIN geographicthesaurus a ON t.acceptedID = a.geoThesID ';
		if($conditionTerm){
			if(is_numeric($conditionTerm)) $sql .= 'WHERE (t.parentID = '.$conditionTerm.') ';
			else $sql .= 'WHERE (t.category = "'.$this->cleanInStr($conditionTerm).'") ';
		}
		else $sql .= 'WHERE (t.category = "country") ';
		$sql .= 'ORDER BY t.geoTerm';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->geoThesID]['geoTerm'] = $r->geoTerm;
			$retArr[$r->geoThesID]['abbreviation'] = $r->abbreviation;
			$retArr[$r->geoThesID]['iso2'] = $r->iso2;
			$retArr[$r->geoThesID]['iso3'] = $r->iso3;
			$retArr[$r->geoThesID]['numCode'] = $r->numCode;
			$retArr[$r->geoThesID]['category'] = $r->category;
			$retArr[$r->geoThesID]['termStatus'] = $r->termStatus;
			$retArr[$r->geoThesID]['acceptedTerm'] = $r->acceptedTerm;
		}
		$rs->free();

		if($retArr){
			$childCntArr = $this->setChildCnt(implode(',',array_keys($retArr)));
			foreach($childCntArr as $id => $cnt){
				$retArr[$id]['childCnt'] = $cnt;
			}
		}
		return $retArr;
	}

	public function getGeograpicUnit($geoThesID){
		$retArr = array();
		if(is_numeric($geoThesID)){
			$sql = 'SELECT t.geoThesID, t.geoTerm, t.abbreviation, t.iso2, t.iso3, t.numCode, t.category, t.parentID, t.termStatus, a.geoterm as acceptedTerm
				FROM geographicthesaurus t LEFT JOIN geographicthesaurus a ON t.acceptedID = a.geoThesID
				WHERE t.geoThesID = '.$geoThesID;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['geoThesID'] = $r->geoThesID;
				$retArr['geoTerm'] = $r->geoTerm;
				$retArr['abbreviation'] = $r->abbreviation;
				$retArr['iso2'] = $r->iso2;
				$retArr['iso3'] = $r->iso3;
				$retArr['numCode'] = $r->numCode;
				$retArr['category'] = $r->category;
				$retArr['parentID'] = $r->parentID;
				$retArr['termStatus'] = $r->termStatus;
				$retArr['acceptedTerm'] = $r->acceptedTerm;
			}
			$rs->free();
			if($retArr){
				$childArr = $this->setChildCnt($retArr['geoThesID']);
				if($childArr) $retArr['childCnt'] = current($childArr);
			}
		}
		return $retArr;
	}

	public function updateGeoUnit($postArr){
		//Deal with edits

	}

	private function setChildCnt($geoIdStr){
		$retArr = array();
		$sql = 'SELECT parentID, count(*) as cnt FROM geographicthesaurus WHERE parentID IN('.$geoIdStr.') GROUP BY parentID ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->parentID] = $r->cnt;
		}
		$rs->free();
		return $retArr;
	}

	public function getCoordStatistics(){
		$retArr = array();
		$totalCnt = 0;
		$sql = 'SELECT COUNT(*) AS cnt FROM omoccurrences WHERE (collid IN(' . $this->collStr . '))';
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$totalCnt = $r->cnt;
		}
		$rs->free();

		// Full count
		$sql2 = 'SELECT COUNT(occid) AS cnt FROM omoccurrences WHERE (collid IN(' . $this->collStr . ')) AND (decimalLatitude IS NULL) AND (georeferenceVerificationStatus IS NULL) ';
		if ($rs2 = $this->conn->query($sql2)) {
			if ($r2 = $rs2->fetch_object()) {
				$retArr['total'] = $r2->cnt;
				$retArr['percent'] = round($r2->cnt * 100 / $totalCnt, 1);
			}
			$rs2->free();
		}

		return $retArr;
	}

	public function transferDataFromLkupTables(){
		/*
		INSERT INTO geographicthesaurus(geoterm,iso2,iso3,numcode,category,geoLevel,termstatus)
		SELECT countryName, iso, iso3, numcode, "Country", 1 as geoLevel, 1 as termStatus
		FROM lkupcountry
		WHERE iso IS NOT NULL;

		SELECT * FROM geographicthesaurus ORDER BY geoLevel, geoTerm LIMIT 100000;

		INSERT INTO geographicthesaurus(geoterm,abbreviation,parentID,category,geoLevel,termStatus)
		SELECT DISTINCT s.stateName, s.abbrev, t.geoThesID, 'State', 2 as geoLevel, 1 as termStatus
		FROM lkupcountry c INNER JOIN lkupstateprovince s ON c.countryid = s.countryid
		INNER JOIN geographicthesaurus t ON c.iso = t.iso2
        WHERE t.category = "country" AND t.termstatus = 1
		LIMIT 1000000;

		INSERT INTO geographicthesaurus(geoterm,parentID,category,geoLevel,termStatus)
		SELECT DISTINCT c.countyName, t.geoThesID, 'County', 3 as geoLevel, 1 as termStatus
		FROM lkupstateprovince s INNER JOIN lkupcounty c ON s.stateid = c.stateid
		INNER JOIN geographicthesaurus t ON s.stateName = t.geoterm
		WHERE t.category = "State" AND t.termstatus = 1 AND (c.countyName NOT LIKE "% County" AND c.countyName NOT LIKE "% Parish");
		 */
	}

	// Setters and getters



}
?>