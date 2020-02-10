<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class OccurrenceSesar extends Manager {

	private $collid;
	private $collArr = array();
	private $igsnDom;
	private $sesarUser;
	private $sesarPwd;
	private $namespace;
	private $generationMethod = 'sesar';
	private $igsnSeed = false;
	private $registrationMethod;
	private $dynPropArr = false;
	private $fieldMap = array();
	private $devMode = false;

	public function __construct($type = 'write'){
		parent::__construct(null, $type);
		$this->fieldMap['basisOfRecord']['sesar'] = 'collection_method_descr';
		$this->fieldMap['catalogNumber']['sesar'] = 'name';
		$this->fieldMap['catalogNumber']['sql'] = 'CONCAT_WS(" ",IFNULL(o.catalogNumber, o.otherCatalogNumbers),"[",o.occid,"]") AS catalogNumber';
		$this->fieldMap['sciname']['sesar'] = 'field_name';
		$this->fieldMap['sciname']['sql'] = 'CONCAT_WS(" ",o.sciname, o.scientificNameAuthorship) AS sciname';
		$this->fieldMap['recordedBy']['sesar'] = 'collector';
		$this->fieldMap['eventDate']['sesar'] = 'collection_start_date';
		$this->fieldMap['verbatimAttributes']['sesar'] = 'description';
		$this->fieldMap['country']['sesar'] = 'country';
		$this->fieldMap['stateProvince']['sesar'] = 'province';
		$this->fieldMap['county']['sesar'] = 'county';
		$this->fieldMap['decimalLatitude']['sesar'] = 'latitude';
		$this->fieldMap['decimalLatitude']['sql'] = 'ROUND(o.decimalLatitude,6) AS decimalLatitude';
		$this->fieldMap['decimalLongitude']['sesar'] = 'longitude';
		$this->fieldMap['decimalLongitude']['sql'] = 'ROUND(o.decimalLongitude,6) AS decimalLongitude';
		$this->fieldMap['minimumElevationInMeters']['sesar'] = 'elevation';
		//$this->fieldMap['parentOccurrenceID']['sesar'] = 'parent_igsn';
		//$this->fieldMap['parentOccurrenceID']['sql'] = ' AS parentOccurrenceID';
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Profile management functions
	public function getSesarProfile(){
		$profileArr = array();
		$this->setDynamicPropertiesArr();
		if(isset($this->dynPropArr['sesar'])){
			$profileArr = $this->dynPropArr['sesar'];
			$this->namespace = $profileArr['namespace'];
			$this->generationMethod = $profileArr['generationMethod'];
		}
		return $profileArr;
	}

	private function setDynamicPropertiesArr(){
		if($this->dynPropArr === false){
			$this->dynPropArr = array();
			$sql = 'SELECT dynamicProperties FROM omcollections WHERE collid = '.$this->collid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				if($r->dynamicProperties) $this->dynPropArr = json_decode($r->dynamicProperties, true);
			}
			$rs->free();
		}
	}

	public function saveProfile(){
		$this->setDynamicPropertiesArr();
		if($this->namespace){
			$this->dynPropArr['sesar']['namespace'] = $this->namespace;
			$this->dynPropArr['sesar']['generationMethod'] = $this->generationMethod;
			$sql = 'UPDATE omcollections SET dynamicProperties = "'.$this->cleanInStr(json_encode($this->dynPropArr)).'" WHERE collid = '.$this->collid;
			if($this->conn->query($sql)){
				return true;
			}
			else{
				$this->errorMessage = 'ERROR saving profile';
				$this->logOrEcho($this->errorMessage);
				return false;
			}
		}
		return false;
	}

	public function deleteProfile(){
		$this->setDynamicPropertiesArr();
		unset($this->dynPropArr['sesar']);
		$sql = 'UPDATE omcollections SET dynamicProperties = "'.$this->cleanInStr(json_encode($this->dynPropArr)).'" WHERE collid = '.$this->collid;
		if($this->conn->query($sql)){
			return true;
		}
		else{
			$this->errorMessage = 'ERROR deleting profile';
			$this->logOrEcho($this->errorMessage);
			return false;
		}
	}

	//Processing functions
	public function batchProcessIdentifiers($processingCount){
		$status = true;
		if($this->registrationMethod == 'api') $this->setVerboseMode(3);
		else  $this->setVerboseMode(1);
		$logPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/')."content/logs/igsn/IGSN_".date('Y-m-d').".log";
		$this->setLogFH($logPath);
		$this->logOrEcho('Starting batch IGSN processing ('.date('Y-m-d H:i:s').')');
		$this->logOrEcho('sesarUser: '.$this->sesarUser);
		$this->logOrEcho('namespace: '.$this->namespace);
		$this->logOrEcho('registrationMethod: '.$this->registrationMethod);
		$this->logOrEcho('generationMethod locally: '.$this->generationMethod);
		if(!$this->namespace){
			$this->errorMessage = 'FATAL ERROR batch assigning IDs: namespace not set';
			$this->logOrEcho($this->errorMessage);
			return false;
		}
		$baseTenID = '';
		if($this->generationMethod == 'inhouse'){
			if(!$this->igsnSeed){
				$this->errorMessage = 'FATAL ERROR batch assigning IDs: IGSN seed not set';
				$this->logOrEcho($this->errorMessage);
				return false;
			}
			$baseTenID = base_convert($this->igsnSeed,36,10);
		}
		if($this->registrationMethod == 'api'){
			if(!$this->validateUser()){
				$this->errorMessage = 'SESAR username and password failed to validate';
				$this->logOrEcho($this->errorMessage);
				return false;
			}
		}

		//Batch assign GUIDs
		$this->logOrEcho('Generating IGSN identifiers');
		$increment = 1;
		$sql = 'SELECT o.occid';
		foreach($this->fieldMap as $symbField => $mapArr){
			if(isset($mapArr['sql'])) $sql .= ','.$mapArr['sql'];
			else $sql .= ',o.'.$symbField;
		}
		$sql .= ' '.$this->getSqlBase();
		if($processingCount) $sql .= 'LIMIT '.$processingCount;
		$rs = $this->conn->query($sql);
		if($rs->num_rows) $this->initiateDom();
		while($r = $rs->fetch_assoc()){
			$igsn = '';
			if($this->generationMethod == 'inhouse'){
				$igsn = base_convert($baseTenID,10,36);
				$igsn = str_pad($igsn, (9-strlen($this->namespace)), '0', STR_PAD_LEFT);
				$igsn = strtoupper($igsn);
				//$igsn = $this->namespace.$igsn;
				$baseTenID++;
			}
			//Set Symbiota record values
			$this->fieldMap['occid']['value'] = $r['occid'];
			foreach($this->fieldMap as $symbField => $fieldArr){
				$this->fieldMap[$symbField]['value'] = $r[$symbField];
			}
			$this->cleanFieldValues();

			if(!$this->igsnExists($igsn)) $this->setSampleXmlNode($igsn);
			//$this->logOrEcho('#'.$increment.': IGSN created for <a href="../editor/occurrenceeditor.php?occid='.$this->fieldMap['occid']['value'].'" target="_blank">'.$this->fieldMap['catalogNumber']['value'].'</a>',1);
			$increment++;
		}
		$rs->free();
		$this->logOrEcho('XML document created');

		if($this->igsnDom){
			//Register identifier with SESAR
			if($this->registrationMethod == 'api'){
				$this->registerIdentifiersViaApi();
			}
			elseif($this->registrationMethod == 'csv'){

			}
			elseif($this->registrationMethod == 'xml'){
				header('Content-Description: ');
				header('Content-Type: application/xml');
				header('Content-Disposition: attachment; filename=SESAR_IGSN_registration_'.date('Y-m-d_His').'.xml');
				header('Content-Transfer-Encoding: UTF-8');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				$this->igsnDom->preserveWhiteSpace = false;
				$this->igsnDom->formatOutput = true;
				//echo $this->igsnDom->saveXML();
				$this->igsnDom->save('php://output');
			}
		}
		else{
			$this->errorMessage = 'No records available to process';
			$this->logOrEcho($this->errorMessage);
			$status = false;
		}

		$this->logOrEcho('Finished ('.date('Y-m-d H:i:s').')');
		return $status;
	}

	// SESAR web service calls (http://www.geosamples.org/interop)
	// End point: https://app.geosamples.org/webservices/
	// Test end point: https://sesardev.geosamples.org/webservices/
	public function validateUser(){
		$userCodeArr = array();
		$baseUrl = 'https://app.geosamples.org/webservices/credentials_service_v2.php';
		if(!$this->sesarUser || !$this->sesarPwd){
			$this->errorMessage = 'Fatal Error validating user: SESAR username or password not set';
			return false;
		}
		$requestData = array ('username' => $this->sesarUser, 'password' => $this->sesarPwd);
		$responseXML = $this->getSesarApiData($baseUrl, $requestData);
		if($responseXML){
			$dom = new DOMDocument('1.0','UTF-8');
			if($dom->loadXML($responseXML)){
				$validElemList = $dom->getElementsByTagName('valid');
				if($validElemList[0]->nodeValue == 'yes'){
					$userCodeList = $dom->getElementsByTagName('user_code');
					foreach ($userCodeList as $UserCodeElem) {
						$userCodeArr[] = $UserCodeElem->nodeValue;
					}
				}
				else{
					$errCodeList = $dom->getElementsByTagName('error');
					$this->logOrEcho('Fatal Error validating user: '.$errCodeList[0]->nodeValue);
					$userCodeArr = false;
				}
			}
			else{
				$this->logOrEcho('FATAL ERROR parsing response XML: '.htmlentities($responseXML));
				$userCodeArr = false;
			}
		}
		else{
			$this->logOrEcho($this->errorMessage);
			$userCodeArr = false;
		}
		return $userCodeArr;
	}

	private function registerIdentifiersViaApi(){
		$status = false;
		$this->logOrEcho('Submitting XML to SESAR Systems');
		$baseUrl = 'https://app.geosamples.org/webservices/upload.php';
		if($this->devMode) $baseUrl = 'https://sesardev.geosamples.org/webservices/upload.php';		// TEST URI
		$contentStr = $this->igsnDom->saveXML();
		$requestData = array ('username' => $this->sesarUser, 'password' => $this->sesarPwd, 'content' => $contentStr);
		$responseXML = $this->getSesarApiData($baseUrl, $requestData);
		if($responseXML){
			$this->processRegistrationResponse($responseXML);
			$status = true;
		}
		return $status;
	}

	private function getSesarApiData($url, $requestData = null){
		$responseXML = false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		if($requestData) curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$responseXML = curl_exec($ch);
		if(!$responseXML){
			$this->errorMessage = 'FATAL CURL ERROR registering IGSN: '.curl_error($ch).' (#'.curl_errno($ch).')';
			//$header = curl_getinfo($ch);
		}
		curl_close($ch);
		return $responseXML;
	}

	private function processRegistrationResponse($responseXML){
		$status = true;
		//echo htmlentities($responseXML);
		$this->logOrEcho('Processing response');
		$dom = new DOMDocument('1.0','UTF-8');
		if($dom->loadXML($responseXML)){
			$rootElem = $dom->documentElement;
			$resultNodeList = $rootElem->childNodes;
			$this->logOrEcho('RESULTS:');
			foreach($resultNodeList as $resultNode){
				if(isset($resultNode->nodeName)){
					if($resultNode->nodeName == 'valid'){
						if($resultNode->nodeValue == 'no'){
							$errCodeList = $rootElem->getElementsByTagName('error');
							$this->errorMessage = 'ERROR registering IGSN ('.$resultNode->getAttribute('code').'): '.$errCodeList[0]->nodeValue;
						}
						else{
							$this->errorMessage = 'ERROR registering IGSN: unknown1';
						}
						$this->logOrEcho('FAILED processing: '.$this->errorMessage,1);
						break;
					}
					elseif($resultNode->nodeName == 'sample'){
						$sampleArr = array();
						if($resultNode->hasAttribute('name')) $sampleArr['catnum'] = $resultNode->getAttribute('name');
						$childNodeList = $resultNode->childNodes;
						foreach($childNodeList as $childNode){
							//if($childNode->nodeName == 'valid') $sampleArr['valid'] = $childNode->nodeValue.': '.$childNode->attribute->getNamedItem('code')->nodeValue;
							if($childNode->nodeName == 'valid') $sampleArr['valid'] = $childNode->nodeValue.': '.$childNode->getAttribute('code');
							else $sampleArr[$childNode->nodeName] = $childNode->nodeValue;
						}
						if(isset($sampleArr['valid'])){
							$msgStr = 'valid = '.$sampleArr['valid'];
							if(isset($sampleArr['catnum']) && $sampleArr['catnum']) $msgStr .= '; ID = '.$sampleArr['catnum'];
							if(isset($sampleArr['status']) && $sampleArr['status']) $msgStr .= '; status = '.$sampleArr['status'];
							if(isset($sampleArr['error']) && $sampleArr['error']) $msgStr .= '; error = '.$sampleArr['error'];
							$this->logOrEcho('FAILED: '.$msgStr,1);
						}
						elseif(isset($sampleArr['igsn']) && $sampleArr['igsn']){
							$occid = 0;
							$dbStatus = false;
							if(preg_match('/\[\s*(\d+)\s*\]\s*$/', $sampleArr['name'],$m)){
								$occid = $m[1];
								$dbStatus = $this->updateOccurrenceID($sampleArr['igsn'], $occid);
							}
							else{
								$this->errorMessage = 'WARNING: unable to extract occid to add igsn ('.$sampleArr['name'].')';
								//$this->logOrEcho('WARNING: unable to extract occid to add igsn ('.$sampleArr['name'].')',2);
							}
							$this->logOrEcho('IGSN registered: <a href="../editor/occurrenceeditor.php?occid='.$occid.'" target="_blank">'.$sampleArr['igsn'].'</a>',1);
							if(!$dbStatus) $this->logOrEcho($this->errorMessage,2);
						}
					}
				}
			}
		}
		else{
			$this->logOrEcho('FATAL ERROR parsing response XML: '.htmlentities($responseXML));
			$status = false;
		}
		return $status;
	}

	private function initiateDom(){
		$this->igsnDom = new DOMDocument('1.0','UTF-8');

		//Add root element
		$rootElem = $this->igsnDom->createElement('samples');
		$rootElem->setAttribute('xmlns','http://app.geosamples.org');
		$rootElem->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$rootElem->setAttribute('xsi:schemaLocation','http://app.geosamples.org/4.0/sample.xsd');
		$this->igsnDom->appendChild($rootElem);
	}

	private function setSampleXmlNode($igsn){
		$sampleElem = $this->igsnDom->createElement('sample');

		$ns = $this->namespace;
		if($ns == 'NEON') $ns = 'NEO';
		$this->addSampleElem($this->igsnDom, $sampleElem, 'user_code', $ns);		//Required
		$this->addSampleElem($this->igsnDom, $sampleElem, 'sample_type', 'Individual Sample');		//Required
		$this->addSampleElem($this->igsnDom, $sampleElem, 'material', 'Biology');		//Required
		$igsnElem = $this->igsnDom->createElement('igsn');		//If blank, SESAR will generate new IGSN
		$igsnElem->appendChild($this->igsnDom->createTextNode($igsn));
		$sampleElem->appendChild($igsnElem);


		$classificationElem = $this->igsnDom->createElement('classification');
		$biologyElem = $this->igsnDom->createElement('Biology');
		$biologyElem->appendChild($this->igsnDom->createElement('Macrobiology'));
		$classificationElem->appendChild($biologyElem);
		$sampleElem->appendChild($classificationElem);

		$this->addSampleElem($this->igsnDom, $sampleElem, 'collection_method', 'Manual');
		if(isset($this->fieldMap['eventDate']) && $this->fieldMap['eventDate']['value']) $this->addSampleElem($this->igsnDom, $sampleElem, 'collection_date_precision', 'day');

		foreach($this->fieldMap as $symbArr){
			if(isset($symbArr['sesar'])) $this->addSampleElem($this->igsnDom, $sampleElem, $symbArr['sesar'], $symbArr['value']);
		}

		if(isset($this->fieldMap['minimumElevationInMeters']['value']) && $this->fieldMap['minimumElevationInMeters']['value'] !== '') $this->addSampleElem($this->igsnDom, $sampleElem, 'elevation_unit', 'meters');
		$this->addSampleElem($this->igsnDom, $sampleElem, 'current_archive', $this->collArr['collectionName']);
		$this->addSampleElem($this->igsnDom, $sampleElem, 'current_archive_contact', $this->collArr['contact'].($this->collArr['email']?' ('.$this->collArr['email'].')':''));

		$serverDomain = "http://";
		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $serverDomain = "https://";
		$serverDomain .= $_SERVER["SERVER_NAME"];
		if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80 && $_SERVER["SERVER_PORT"] != 443) $serverDomain .= ':'.$_SERVER["SERVER_PORT"];
		$url = $serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
		$url .= 'collections/individual/index.php?occid='.$this->fieldMap['occid']['value'];
		$externalUrlsElem = $this->igsnDom->createElement('external_urls');
		$externalUrlElem = $this->igsnDom->createElement('external_url');
		$urlElem = $this->igsnDom->createElement('url');
		$urlElem->appendChild($this->igsnDom->createTextNode($url));
		$externalUrlElem->appendChild($urlElem);
		$descriptionElem = $this->igsnDom->createElement('description');
		$descriptionElem->appendChild($this->igsnDom->createTextNode('Source Reference URL'));
		$externalUrlElem->appendChild($descriptionElem);
		$urlTypeElem = $this->igsnDom->createElement('url_type');
		$urlTypeElem->appendChild($this->igsnDom->createTextNode('regular URL'));
		$externalUrlElem->appendChild($urlTypeElem);
		$externalUrlsElem->appendChild($externalUrlElem);
		$sampleElem->appendChild($externalUrlsElem);

		$rootElem = $this->igsnDom->documentElement;
		$rootElem->appendChild($sampleElem);
	}

	private function addSampleElem(&$dom, &$sampleElem, $elemName, $elemValue){
		if($elemValue){
			$newElem = $dom->createElement($elemName);
			$newElem->appendChild($dom->createTextNode($elemValue));
			$sampleElem->appendChild($newElem);
		}
	}

	private function updateOccurrenceID($igsn, $occid){
		$status = true;
		if(strlen($igsn) == 9){
			$sql = 'UPDATE omoccurrences SET occurrenceID = '.($igsn=='NULL'?'NULL':'"'.$igsn.'"').' WHERE occurrenceID IS NULL AND occid = '.$occid;
			if(!$this->conn->query($sql)){
				$this->errorMessage = 'ERROR adding IGSN to occurrence table: '.$this->conn->error;
				//$this->logOrEcho('ERROR adding IGSN to occurrence table: '.$this->conn->error,2);
				$status = false;
			}
		}
		else{
			$this->errorMessage = 'ERROR adding IGSN to occurrence table: IGSN ('.$igsn.') not 9 digits';
			//$this->logOrEcho('ERROR adding IGSN to occurrence table: IGSN ('.$igsn.') not 9 digits',2);
			$status = false;
		}
		return $status;
	}

	private function igsnExists($igsn){
		$status = false;
		if($this->namespace){
			$sql = 'SELECT occurrenceID FROM omoccurrences WHERE (occurrenceid LIKE "'.$this->namespace.'%") AND (occurrenceID = "'.$igsn.'")';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$status = true;
			}
			$rs->free();
		}
		return $status;
	}

	//Record field cleaning functions
	private function cleanFieldValues(){
		if(isset($this->fieldMap['country']['value']) && $this->fieldMap['country']['value']){
			$this->fieldMap['country']['value'] = $this->cleanCountryStr($this->fieldMap['country']['value']);
		}
		if(isset($this->fieldMap['eventDate']['value'])){
			if($this->fieldMap['eventDate']['value']){
				//echo 'date: '.$this->fieldMap['eventDate']['value'].' - ';
				$y = substr($this->fieldMap['eventDate']['value'],0,4);
				if($y < 1900) unset($this->fieldMap['eventDate']);
				if(isset($this->fieldMap['eventDate']['value']) && $this->fieldMap['eventDate']['value']) $this->fieldMap['eventDate']['value'] .= 'T00:00:00';
			}
		}
	}

	private function cleanCountryStr($countryStr){
		if(!$countryStr) return $countryStr;
		$countryStr = $this->mbStrtr($countryStr,'áéÉ','aeE');
		$testStr = strtolower($countryStr);
		$synonymArr = array('united states of america'=>'United States','usa'=>'United States','u.s.a.'=>'united states','us'=>'United States');
		if(array_key_exists($testStr, $synonymArr)) $countryStr = $synonymArr[$testStr];
		$goodCountryArr = array('Afghanistan','Albania','Algeria','American Samoa','Andorra','Angola','Anguilla','Antarctica','Antigua And Barbuda','Argentina','Armenia','Aruba',
			'Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bermuda','Bhutan','Bolivia','Bosnia And Herzegovina',
			'Botswana','Bouvet Island','Brazil','British Indian Ocean Territory','Brunei Darussalam','Bulgaria','Burkina Faso','Burundi','Cambodia','Cameroon','Canada','Cape Verde',
			'Cayman Islands','Central African Republic','Chad','Chile','China','Christmas Island','Cocos (keeling) Islands','Colombia','Comoros','Congo',
			'Congo, The Democratic Republic Of The','Cook Islands','Costa Rica',"Cote D'ivoire",'Croatia','Cuba','Cyprus','Czech Republic','Denmark','Djibouti','Dominica',
			'Dominican Republic','East Timor','Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Ethiopia','Falkland Islands (malvinas)','Faroe Islands',
			'Fiji','Finland','France','French Guiana','French Polynesia','French Southern Territories','Gabon','Gambia','Georgia','Germany','Ghana','Gibraltar','Greece','Greenland',
			'Grenada','Guadeloupe','Guam','Guatemala','Guinea','Guinea-bissau','Guyana','Haiti','Heard Island And Mcdonald Islands','Holy See (vatican City State)','Honduras',
			'Hong Kong','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kiribati','South Korea',
			'North Korea','Kosovo','Kuwait','Kyrgyzstan',"Lao People's Democratic Republic",'Latvia','Lebanon','Lesotho','Liberia','Libyan Arab Jamahiriya','Liechtenstein',
			'Lithuania','Luxembourg','Macau','Macedonia','Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Martinique','Mauritania','Mauritius',
			'Mayotte','Mexico','Micronesia, Federated States Of','Moldova, Republic Of','Monaco','Mongolia','Montserrat','Montenegro','Morocco','Mozambique',
			'Myanmar (Burma)','Namibia','Nauru','Nepal','Netherlands','Netherlands Antilles','New Caledonia','New Zealand','Nicaragua','Niger','Nigeria','Niue','Norfolk Island',
			'Northern Mariana Islands','Norway','Oman','Pakistan','Palau','Palestinian Territory, Occupied','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Pitcairn',
			'Poland','Portugal','Puerto Rico','Qatar','Reunion','Romania','Russia','Rwanda','Saint Helena','St. Kitts And Nevis','Saint Lucia','Saint Pierre And Miquelon',
			'Saint Vincent And The Grenadines','Samoa','San Marino','Sao Tome And Principe','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia',
			'Slovenia','Solomon Islands','Somalia','South Africa','South Georgia And The South Sandwich Islands','Spain','Sri Lanka','Sudan','Suriname','Svalbard And Jan Mayen',
			'Swaziland','Sweden','Switzerland','Syria','Taiwan, Republic Of China','Tajikistan','Tanzania','Thailand','Togo','Tokelau','Tonga','Trinidad And Tobago',
			'Tunisia','Turkey','Turkmenistan','Turks And Caicos Islands','Tuvalu','Uganda','Ukraine','United Arab Emirates','United Kingdom','United States',
			'United States Minor Outlying Islands','Uruguay','Uzbekistan','Vanuatu','Venezuela','Viet Nam','British Virgin Islands','U.S. Virgin Islands','Wallis And Futuna',
			'Western Sahara','Yemen','Zambia','Zimbabwe','Not Applicable');
		if(!in_array($countryStr, $goodCountryArr)){
			if(preg_grep( '/'.$countryStr.'/i' , $goodCountryArr )){
				//Name in approved list, but case is wrong, thus fix
				foreach($goodCountryArr as $countryName){
					if(strtolower($countryName) == strtolower($countryStr)) $countryStr = $countryName;
				}
			}
			else{
				$countryStr = '';
			}
		}
		return $countryStr;
	}

	function mbStrtr($str, $from, $to = null) {
		if(function_exists('mb_strtr')) {
			return mb_strtr($str, $from, $to);
		}
		else{
			if(is_array($from)) {
				$from = array_map('utf8_decode', $from);
				$from = array_map('utf8_decode', array_flip ($from));
				return utf8_encode (strtr (utf8_decode ($str), array_flip ($from)));
			}
			return utf8_encode (strtr (utf8_decode ($str), utf8_decode($from), utf8_decode ($to)));
		}
	}

	//GUID verification functions
	public function verifySesarGuids(){
		//Clear IGSN verification table
		$this->conn->query('DELETE FROM igsnverification');
		$this->conn->query('OPTIMIZE TABLE igsnverification');

		$this->logOrEcho('Loading records into verification table...',1);
		$sesarResultArr = array('totalCnt'=>0);
		$this->batchVerifySesar($sesarResultArr);

		if($sesarResultArr['totalCnt']){
			$this->logOrEcho('Calculating stats...',1);
			$sql = 'UPDATE igsnverification i INNER JOIN omoccurrences o ON i.igsn = o.occurrenceid SET i.occid = o.occid WHERE i.occid IS NULL';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR updaing IGSN field: '.$this->conn->error,2);
			}
			//Grab collection details
			$collArr = array();
			$sql = 'SELECT o.collid, COUNT(o.occid) as cnt FROM omoccurrences o INNER JOIN igsnverification i ON o.occid = i.occid GROUP BY o.collid ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$collArr[$r->collid]['cnt'] = $r->cnt;
			}
			$rs->free();
			$sql = 'SELECT collid, CONCAT_WS(":",institutionCode,collectionCode) as code, collectionname FROM omcollections WHERE collid IN('.implode(',',array_keys($collArr)).')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$collArr[$r->collid]['name'] = $r->collectionname.' ('.$r->code.')';
			}
			$rs->free();
			$sesarResultArr['collid'] = $collArr;

			//Add missing IGSNs
			$sql = 'SELECT igsn FROM igsnverification WHERE occid IS NULL';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$sesarResultArr['missing'][$r->igsn] = array();
			}
			$rs->free();
		}
		if(isset($sesarResultArr['missing'])) $this->setMissingSesarMeta($sesarResultArr);
		return $sesarResultArr;
	}

	private function batchVerifySesar(&$sesarResultArr, $pageNumber = 1){
		$batchLimit = 1000;
		$cnt = 0;
		if(isset($sesarResultArr['checkedCnt'])) $cnt = $sesarResultArr['checkedCnt'];
		$ns = substr($this->namespace,0,3);
		$url = 'https://app.geosamples.org/samples/user_code/'.$ns.'?limit='.$batchLimit.'&page_no='.$pageNumber;
		if($this->devMode) $url = 'https://sesardev.geosamples.org/samples/user_code/'.$ns.'?limit='.$batchLimit.'&page_no='.$pageNumber;
		$responseArr = $this->getSesarApiGetData($url);
		if($responseArr['retCode'] == 200){
			if($retJson = $responseArr['retJson']){
				$jsonObj = json_decode($retJson);
				if(!$sesarResultArr['totalCnt']) $sesarResultArr['totalCnt'] = $jsonObj->total_counts;
				$sqlBase = 'INSERT INTO igsnverification(igsn) VALUE';
				$sqlFrag = '';
				foreach($jsonObj->igsn_list as $igsn){
					//Load records into IGSN Verification table
					$sqlFrag .= '("'.$igsn.'"),';
					$cnt++;
					if($cnt%1000==0){
						if($this->conn->query($sqlBase.trim($sqlFrag,', '))){
							$this->logOrEcho($cnt.' records loaded',2);
							$sqlFrag = '';
						}
						else{
							$this->logOrEcho('ERROR loading IGSNs: '.$this->conn->error,2);
							return false;
						}
					}
				}
				if($sqlFrag){
					if($this->conn->query($sqlBase.trim($sqlFrag,', '))){
						$this->logOrEcho($cnt.' records loaded',2);
					}
				}
			}
		}
		else{
			$this->logOrEcho('ERROR obtaining IGSNs (code: '.$responseArr['retCode'].')',1);
		}
		$sesarResultArr['checkedCnt'] = $cnt;
		if($sesarResultArr['totalCnt'] > ($batchLimit*$pageNumber)){
			$pageNumber++;
			$this->batchVerifySesar($sesarResultArr,$pageNumber);
		}
	}

	private function setMissingSesarMeta(&$sesarResultArr){
		//Grab SESAR meta for unmatched IGSNs
		$this->logOrEcho(count($sesarResultArr['missing']).' records unlink IGSNs found. Getting metadata from SESAR Systems...',1);
		$url = 'https://app.geosamples.org/webservices/display.php?igsn=';
		if($this->devMode) $url = 'https://sesardev.geosamples.org/webservices/display.php?igsn=';
		$cnt = 0;
		foreach(array_keys($sesarResultArr['missing']) as $lostIGSN){
			$resArr = $this->getSesarApiGetData($url.$lostIGSN);
			if($resArr['retCode'] == 200){
				$igsnObj = json_decode($resArr['retJson']);
				if(preg_match('/^(.+)\s*\[\s*(\d+)\s*\]$/', $igsnObj->sample->name,$m)){
					$catNum = $m[1];
					$occid = $m[2];
					$sesarResultArr['missing'][$lostIGSN] = array('catNum'=>$catNum,'occid'=>$occid);
				}
			}
			$cnt++;
			if($cnt%10==0) $this->logOrEcho($cnt.' records processed',2);
		}
		$this->logOrEcho('Complete!',2);
	}

	public function verifyLocalGuids(){
		$retArr = array();
		$sql = 'SELECT o.occid, o.occurrenceid FROM omoccurrences o LEFT JOIN igsnverification i ON o.occid = i.occid '.
			'WHERE o.occurrenceID LIKE "'.$this->namespace.'%" AND o.collid = '.$this->collid.' AND i.occid IS NULL';
		$rs = $this->conn->query($sql);
		$retArr['cnt'] = $rs->num_rows;
		while($r = $rs->fetch_object()){
			$retArr['missing'][$r->occid] = $r->occurrenceid;
		}
		$rs->free();
		return $retArr;
	}

	public function syncIGSN($occid,$catalogNumber,$igsn){
		$ok = true;
		$retArr = array('status'=>0);
		if(is_numeric($occid) && preg_match('/^[A-Z0-9]+$/', $igsn)){
			$sql = 'SELECT catalogNumber, occurrenceID FROM omoccurrences WHERE occid = '.$occid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				if($r->occurrenceID){
					$retArr['errCode'] = 1;
					$retArr['guid'] = $r->occurrenceID;
					$ok = false;
				}
				elseif($r->catalogNumber != $catalogNumber){
					$retArr['errCode'] = 2;
					$retArr['catNum'] = $r->catalogNumber;
					$ok = false;
				}
			}
			else{
				$retArr['errCode'] = 3;
				$ok = false;
			}
			$rs->free();

			if($ok){
				$sqlUpdate = 'UPDATE omoccurrences SET occurrenceid = "'.$this->cleanInStr($igsn).'" WHERE occid = '.$occid;
				if($this->conn->query($sqlUpdate)){
					if($this->conn->affected_rows) $retArr['status'] = 1;
				}
			}
		}
		return $retArr;
	}

	private function getSesarApiGetData($url){
		$retArr = array();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array ( 'Accept: application/json' ));
		$retArr['retJson'] = curl_exec($ch);
		$retArr['retCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($retArr['retCode'] != 200){
			$this->errorMessage = 'FATAL CURL ERROR registering IGSN: '.curl_error($ch).' (#'.curl_errno($ch).')';
			//$header = curl_getinfo($ch);
		}
		curl_close($ch);
		return $retArr;
	}

	//Misc data return functions
	public function getGuidCount($collid = null){
		$cnt = 0;
		if($this->namespace){
			$sql = 'SELECT COUNT(*) AS cnt FROM omoccurrences WHERE (occurrenceid LIKE "'.$this->namespace.'%") ';
			if($collid) $sql .= 'AND (collid = '.$this->collid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getMissingGuidCount(){
		$cnt = 0;
		$sql = 'SELECT COUNT(o.occid) AS cnt '.$this->getSqlBase();
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$cnt = $r->cnt;
		}
		$rs->free();
		return $cnt;
	}

	private function getSqlBase(){
		$sqlBase = 'FROM omoccurrences o WHERE (o.occurrenceid IS NULL) ';
		if($this->namespace && $this->namespace == 'NEON'){
			$rs = $this->conn->query('SELECT 1 FROM NeonSample LIMIT 1');
			if($rs->num_rows) $sqlBase = 'FROM omoccurrences o INNER JOIN NeonSample s ON o.occid = s.occid WHERE (o.occurrenceid IS NULL) AND (s.errorMessage IS NULL) ';
			$rs->free();
		}
		if($this->collid) $sqlBase .= 'AND (o.collid = '.$this->collid.') ';
		return $sqlBase;
	}

	//Setters and getters
	public function setCollid($id){
		if($id && is_numeric($id)){
			$this->collid = $id;
		}
	}

	public function setCollArr(){
		if($this->collid){
			$sql = 'SELECT institutionCode, collectionCode, collectionName, contact, email FROM omcollections WHERE collid = '.$this->collid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$this->collArr['collectionName'] = $r->collectionName.' ('.$r->institutionCode.($r->collectionCode?$r->collectionCode:'').')';
				$this->collArr['contact'] = $r->contact;
				$this->collArr['email'] = $r->email;
			}
			$rs->free();
		}
	}

	public function getCollectionName(){
		if($this->collArr) return $this->collArr['collectionName'];
	}

	public function setSesarUser($user){
		$this->sesarUser = $user;
	}

	public function setSesarPwd($pwd){
		$this->sesarPwd = $pwd;
	}

	public function setNamespace($ns){
		if(preg_match('/^[A-Z]+$/', $ns)){
			if($ns == 'NEO') $ns .= 'N';
			$this->namespace = $ns;
		}
	}

	public function generateIgsnSeed(){
		$igsnSeed = '';
		$this->getSesarProfile();
		//Get maximum identifier
		if($this->collid && $this->namespace){
			$seed = 0;
			$sql = 'SELECT MAX(occurrenceID) as maxid FROM omoccurrences WHERE occurrenceID LIKE "'.$this->namespace.'%" AND length(occurrenceID) = 9';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$seed = $r->maxid;
			}
			$rs->free();
			//Increase max alphanumeric ID by 1
			if($seed){
				$seedBaseTen = base_convert($seed,36,10);
				$igsn = base_convert($seedBaseTen+1,10,36);
				$igsn = str_pad($igsn, (9-strlen($this->namespace)), '0', STR_PAD_LEFT);
				$igsnSeed = strtoupper($igsn);
			}
			else{
				$igsnSeed = $this->namespace.str_pad('1', (9-strlen($this->namespace)), '0', STR_PAD_LEFT);
			}
		}
		return $igsnSeed;
	}

	public function setIgsnSeed($seed){
		if($seed && preg_match('/^[A-Z0-9]+$/', $seed)){
			if($this->igsnExists($seed)) $this->warningArr[] = 'ERROR: Seed ('.$seed.') already exists or is out of sequence ';
			else $this->igsnSeed = $seed;
		}
	}

	public function getIgsnSeed(){
		return $this->igsnSeed;
	}

	public function setRegistrationMethod($method){
		$this->registrationMethod = $method;
	}

	public function setGenerationMethod($method){
		if($method == 'inhouse') $this->generationMethod = $method;
	}
}
?>