<?php
include_once($SERVER_ROOT.'/classes/Manager.php');
class MediaResolutionTools extends Manager {

	private $reportFH;

	//Archiver variables
	private $imgidArr;
	private $archiveImages = false;
	private $archiveDir;
	private $deleteThumbnail = false;
	private $deleteWeb = false;
	private $deleteOriginal = false;

	//Image migration variables
	private $collMetaArr;
	private $transferThumbnail = false;
	private $transferWeb = false;
	private $transferLarge = false;
	private $matchTermThumbnail;
	private $matchTermWeb;
	private $matchTermLarge;
	private $imgRootUrl;
	private $imgRootPath;
	private $imgSubPath;

	function __construct() {
		parent::__construct('write');
		set_time_limit(600);
		$this->verboseMode = 3;
		$this->setLogFH('../../../temp/logs/imgMigration_error_'.date('Ym').'.log');
		$this->reportFH = fopen('../../../temp/logs/imgMigration_'.date('Ym').'.log', 'a');
	}

	function __destruct(){
		parent::__destruct();
		fclose($this->reportFH);
	}

	//Archiver functions
	public function archiveImageFiles($imgidStart, $limit){
		//Set stage
		if(!$imgidStart) $imgidStart = 0;
		if(!$this->imgidArr){
			echo '<li>ABORTED: Image ids (imgid) not supplied</li>';
			return false;
		}
		$this->archiveDir = $GLOBALS['IMAGE_ROOT_PATH'].'/archive_'.date('Y-m-d');
		if(!file_exists($this->archiveDir)){
			if(!mkdir($this->archiveDir)) {
				echo '<li>ABORTED: unalbe to create archive directory ('.$this->archiveDir.')</li>';
				return false;
			}
		}
		$createHeader = true;
		if(file_exists($this->archiveDir.'/mediaArchiveReport.csv')) $createHeader = false;
		$this->reportFH = fopen($this->archiveDir.'/mediaArchiveReport.csv', 'a');
		if(!$this->reportFH){
			echo '<li>ABORTED: unalbe to create archive file ('.$this->archiveDir.')</li>';
			return false;
		}
		if($createHeader) fputcsv($this->reportFH, array('imgid','status','path','url'));
		//Remove images
		$imgidFinal = $imgidStart;
		$cnt = 0;
		$sql = 'SELECT i.* FROM images i ';
		if($this->collid) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE (i.imgid IN('.trim(implode(',',$this->imgidArr),', ').')) AND (i.imgid > '.$imgidStart.') ';
		if($this->collid) $sql .= 'AND (o.collid = '.$this->collid.') ';
		$sql .= 'ORDER BY i.imgid LIMIT '.$limit;
		//echo $sql;
		$rs = $this->conn->query($sql);
		echo '<ul>';
		while($r = $rs->fetch_assoc()){
			$imgId = $r['imgid'];
			$derivArr = array('tn'=>1,'web'=>1,'lg'=>1);
			$delArr = array();
			if(!$r['thumbnailurl']) unset($derivArr['tn']);
			if(!$r['url']) unset($derivArr['web']);
			if(!$r['originalurl']) unset($derivArr['lg']);
			//Transfer images to archive folder
			if($this->deleteThumbnail && isset($derivArr['tn'])){
				if($this->archiveImage($r['thumbnailurl'], $imgId)){
					$delArr['tn'] = 1;
					unset($derivArr['tn']);
				}
			}
			if($this->deleteWeb && isset($derivArr['web'])){
				if($this->archiveImage($r['url'], $imgId)){
					$delArr['web'] = 1;
					unset($derivArr['web']);
				}
			}
			if($this->deleteOriginal && isset($derivArr['lg'])){
				if($this->archiveImage($r['originalurl'], $imgId)){
					$delArr['lg'] = 1;
					unset($derivArr['lg']);
				}
			}
			//Place INSERT sql into file in case record needs to be reintalled
			$insertArr = $r;
			unset($insertArr['imgid']);
			unset($insertArr['initialtimestamp']);
			$insertStr = '';
			foreach($insertArr as $v){
				if($v){
					$insertStr .= ',"'.$v.'"';
				}
				else{
					$insertStr .= ',NULL';
				}
			}
			$insSql = 'INSERT INTO images('.implode(',', array_keys($insertArr)).') VALUES('.substr($insertStr,1).');';
			fputcsv($this->reportFH,array($imgId,'record deleted',$insSql));
			//Adjust database record
			$sqlImg = '';
			if($derivArr){
				if(isset($delArr['tn'])) $sqlImg .= ', thumbnailurl = NULL';
				if(isset($delArr['web'])) $sqlImg .= ', url = "empty"';
				if(isset($delArr['lg'])) $sqlImg .= ', originalurl = NULL';
				if($sqlImg) $sqlImg = 'UPDATE images SET '.substr($sqlImg,1).' WHERE imgid = '.$imgId;
			}
			else{
				$sqlImg = 'DELETE FROM images WHERE imgid = '.$imgId;
			}
			if($sqlImg){
				if(!$this->conn->query($sqlImg)){
					echo '<li>ERROR: '.$this->conn->error.'</li>';
					echo '<li style="margin-left:15px;">sqlImg: '.$sqlImg.'</li>';
				}
			}
			if($cnt && $cnt%100 == 0){
				echo '<li>'.$cnt.' images checked</li>';
				ob_flush();
				flush();
			}
			$cnt++;
			$imgidFinal = $imgId;
		}
		echo '</ul>';
		$rs->free();
		fclose($this->reportFH);
		echo '<div>Done! '.$cnt.' images handled</div>';
		return $imgidFinal;
	}

	private function archiveImage($imgFilePath, $imgid){
		$status = false;
		if($imgFilePath){
			if(substr($imgFilePath,0,4) == 'http') {
				$imgFilePath = substr($imgFilePath,strpos($imgFilePath,"/",9));
			}
			$path = str_replace($GLOBALS['IMAGE_ROOT_URL'], $GLOBALS['IMAGE_ROOT_PATH'], $imgFilePath);
			if(is_writable($path)){
				if($this->archiveImages){
					$fileName = substr($path, strrpos($path, '/'));
					if(rename($path,$this->archiveDir.'/'.$fileName)) $status = true;
				}
				else{
					if(unlink($path)) $status = true;
				}
			}
			else{
				fputcsv($this->reportFH,array($imgid,'unwritable',$imgFilePath,$path));
				echo '<li>ERROR: image unwritable (imgid: <a href="'.$GLOBALS['CLIENT_ROOT'].'/imagelib/imgdetails.php?imgid='.$imgid.'" target="_blank">'.$imgid.'</a>, path: '.$imgFilePath.')</li>';
			}
		}
		return $status;
	}

	//Image migration functions
	public function migrateDerivatives($limit){
		if(is_numeric($limit) && is_numeric($this->collid) && $this->imgRootUrl && $this->imgRootPath){
			if($this->transferThumbnail && $this->transferWeb && $this->transferLarge){
				if($this->matchTermTn || $this->matchTermWeb || $this->matchTermLarge){
					$this->setTargetPaths();
					$dirCnt = 0;
					do{
						$imgArr = array();
						$pathFrag = date('Ym');
						if(!file_exists($this->imgRootPath.$pathFrag)) mkdir($this->imgRootPath.$pathFrag);
						$subDir = str_pad($dirCnt,4,'0',STR_PAD_LEFT);
						while(file_exists($this->imgRootPath.$pathFrag.'/'.$subDir)){
							$dirCnt ++;
							$subDir = str_pad($dirCnt,4,'0',STR_PAD_LEFT);
						}
						$pathFrag .= '/'.$subDir;
						$dirCnt ++;
						$sql = 'SELECT imgid, thumbnailurl, url, originalurl FROM images WHERE occid IS NULL ';
						if($this->collid) $sql = 'SELECT i.thumbnailurl, i.url, i.originalurl FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid WHERE o.collid = '.$this->collid;
						if($this->matchTermThumbnail) $sql .= ' AND thumbnailurl LIKE "'.$this->matchTermThumbnail.'%" ';
						if($this->matchTermWeb) $sql .= ' AND url LIKE "'.$this->matchTermWeb.'%" ';
						if($this->matchTermLarge) $sql .= ' AND originalurl LIKE "'.$this->matchTermLarge.'%" ';
						$sql .= 'LIMIT 1000';
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							if($this->transferThumbnail){
								$filePath = $pathFrag.strrpos($r->thumbnailurl+1, '/');
								if(copy($r->thumbnailurl,$this->imgRootPath.$filePath)){
									$imgArr[$r->imgid]['tn'] = $filePath;
									fwrite($this->reportFH,$r->thumbnailurl."\n");
								}
							}
							if($this->transferWeb){
								$filePath = $pathFrag.strrpos($r->url+1, '/');
								if(copy($r->url,$this->imgRootPath.$filePath)){
									$imgArr[$r->imgid]['web'] = $filePath;
									fwrite($this->reportFH,$r->url."\n");
								}
							}
							if($this->transferLarge){
								$filePath = $pathFrag.strrpos($r->originalurl+1, '/');
								if(copy($r->originalurl,$this->imgRootPath.$filePath)){
									$imgArr[$r->imgid]['lg'] = $filePath;
									fwrite($this->reportFH,$r->originalurl."\n");
								}
							}
							$limit--;
							if($limit < 1) break;
						}
						$rs->free();
						$this->processImageArr($imgArr);
						$cnt = count($imgArr);
						$this->logOrEcho($cnt.' image records remapped');
						unset($imgArr);
					}while($cnt && $limit);
				}
			}
		}
	}

	private function processImageArr($imgArr){
		foreach($imgArr as $imgID => $iArr){
			$sqlFrag = '';
			if(isset($iArr['tn'])) $sqlFrag .= 'thumbnailurl = "'.$this->imgRootUrl.$iArr['tn'].'"';
			if(isset($iArr['web'])) $sqlFrag .= ',url = "'.$this->imgRootUrl.$iArr['web'].'"';
			if(isset($iArr['lg'])) $sqlFrag .= ',originalurl = "'.$this->imgRootUrl.$iArr['lg'].'"';
			if($sqlFrag){
				$sql = 'UPDATE images '.trim($sqlFrag,' ,').' WHERE imgid = '.$imgID;
				if(!$this->conn->query($sql)) $this->logOrEcho('ERROR saving new paths: '.$this->conn->error,1);

			}
		}
	}

	private function setTargetPaths(){
		if($this->imgRootPath && $this->imgRootUrl){
			if($this->collid){
				$this->imgRootPath .= $this->collMetaArr['code'].'/';
			}
			elseif($this->collid === 0){
				$this->imgRootPath .= 'fieldimg/';
			}
			if(!file_exists($this->imgRootPath)) mkdir($this->imgRootPath);
		}
	}

	//Navigates through iDigBio media links and fixes bad full derivative links that were the result of a disk crash
	public function checkImageLinks($imgidStart, $limit, $collid){
		$imgidFinal = $imgidStart;
		$cnt = 1;
		$sql = 'SELECT i.imgid, i.originalurl FROM images i ';
		if($collid) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE (i.originalurl LIKE "https://api.idigbio.org/v2/media/%size=fullsize") AND (i.imgid > '.$imgidStart.') ';
		if($collid) $sql .= 'AND (o.collid = '.$collid.') ';
		$sql .= 'ORDER BY i.imgid LIMIT '.$limit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$url = $r->originalurl;
			if($this->isBrokenUrl($url)){
				if($newUrl = substr($url,0,-14)){
					if(!$this->isBrokenUrl($newUrl)){
						$sql2 = 'UPDATE images SET originalurl = "'.$newUrl.'" WHERE imgid = '.$r->imgid;
						$this->conn->query($sql2);
						echo '<li>'.$cnt.': Remapping image #'.$r->imgid.' to: '.$newUrl.'</li>';
						ob_flush();
						flush();
					}
				}
			}
			//echo '<li>Image is good (imgid: '.$r->imgid.'): '.$url.'</li>';
			if($cnt%500 == 0){
				echo '<li>'.$cnt.' image checked (imgid: '.$r->imgid.')</li>';
				ob_flush();
				flush();
			}
			$cnt++;
			$imgidFinal = $r->imgid;
		}
		$rs->free();
		return $imgidFinal;
	}

	private function isBrokenUrl($url){
		$status = false;
		$handle = curl_init($url);
		if(false === $handle){
			$status = true;
		}
		curl_setopt($handle, CURLOPT_HEADER, true);
		curl_setopt($handle, CURLOPT_NOBODY, true);
		curl_setopt($handle, CURLOPT_FAILONERROR, true);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true );
		//curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36');
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_exec($handle);
		$retCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		//print_r(curl_getinfo($handle));
		if($retCode == 403) $status = true;
		curl_close($handle);
		return $status;
	}

	//Misc data return functions
	public function getCollectionMeta(){
		$retArr = array();
		$sql = 'SELECT collid, collectionname, CONCAT_WS(":",institutioncode,collectioncode) as instcode FROM omcollections ORDER BY collectionname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]= $r->collectionname.' ('.$r->instcode.')';
		}
		$rs->free();
		return $retArr;
	}

	//Setters and getters
	public function setCollid($id){
		if(is_numeric($id)){
			$this->collid = $id;
			$sql = 'SELECT collectionname, CONCAT_WS("_",institutioncode,collectioncode) as instcode FROM omcollections WHERE collid = '.$id;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collMetaArr['name']= $r->collectionname;
				$this->collMetaArr['code']= $r->instcode;
			}
			$rs->free();
		}
	}

	//Archiver setters and getters
	public function setImgidArr($imgidStr){
		$imgidStr = str_replace(';', ' ', $imgidStr);
		$imgidStr = str_replace(',', ' ', $imgidStr);
		$imgidStr = trim(preg_replace('/\s\s+/',' ',$imgidStr),',');
		if($imgidStr){
			if(preg_match('/^[\d\s]+$/',$imgidStr)){
				$this->imgidArr = explode(' ',$imgidStr);
			}
		}
	}

	public function setArchiveImages($b){
		if($b) $this->archiveImages = true;
	}

	public function setDeleteThumbnail($delTn){
		if($delTn) $this->deleteThumbnail = true;
		else $this->deleteThumbnail = false;
	}

	public function setDeleteWebImage($delWeb){
		if($delWeb) $this->deleteWeb = true;
		else $this->deleteWeb = false;
	}

	public function setDeleteOriginal($delOrig){
		if($delOrig) $this->deleteOriginal = true;
		else $this->deleteOriginal = false;
	}

	//Image migration setters and getter
	public function setTransferThumbnail($bool){
		if($bool) $this->transferThumbnail = true;
		else $this->transferThumbnail = false;
	}

	public function setTransferWeb($bool){
		if($bool) $this->transferWeb = true;
		else $this->transferWeb = false;
	}

	public function setTransferLarge($bool){
		if($bool) $this->transferLarge = true;
		else $this->transferLarge = false;
	}

	public function setMatchTermThumbnail($str){
		$this->matchTermThumbnail = $str;
	}

	public function setMatchTermWeb($str){
		$this->matchTermWeb = $str;
	}

	public function setMatchTermLarge($str){
		$this->matchTermLarge = $str;
	}

	public function setImgRootUrl($url){
		if(substr($url, -1) != '/') $url .= '/';
		$this->imgRootUrl = $url;
	}

	public function setImgRootPath($url){
		if(substr($url, -1) != '/') $url .= '/';
		$this->imgRootPath = $url;
	}

	public function setImgSubPath($path){
		$this->imgSubPath = $path;
	}
}
?>