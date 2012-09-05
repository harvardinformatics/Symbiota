<?php 
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');

$uploadManager = new ImageUploadManager();

$collId = (array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:"");
$action = (array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"");

$isEditable = false;
if($isAdmin){
 	$isEditable = true;
}

if($isEditable){
	if($action == "Add Image"){
		
	}
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title>Observation Image Batch Loader</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css"/>
	<meta http-equiv="Content-Type" content="text/html; charset="<?php echo $charset;?>>
	<script type="text/javascript">
	
	function toggle(target){
		var obj = document.getElementById(target);
		if(obj){
			if(obj.style.display=="none"){
				obj.style.display="block";
			}
			else {
				obj.style.display="none";
			}
		}
	}

	function validateQueryForm(f){
		if(f.qloaddate.value == "" && f.qobserver.value == "" && f.qfamily.value == "" && f.qsciname.value == ""){
			alert("Please enter a search term in at least one of the fields below ");
			return false;
		}
		return true;
	}

	function validateImageSubmitForm(f){
		if(f.imgfile.value == ""){
			alert("You must first select an image to upload");
			return false;
		}
		return true;
	}
	
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_admin_observationuploaderMenu)?$collections_admin_observationuploaderMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_admin_observationuploaderCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_admin_observationuploaderCrumbs;
		echo " <b>Observation Image Loader</b>"; 
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h2>Observation Image Loader</h2>
	    <?php
		if($isEditable){
			?>
			<div style="margin:15px;">
				Use the form in the box below to define a subset of occurrence records 
				to which you want link images. Use the default box to define default values for the image loading form.
			</div>   
			<form id="imgloader" name="imgloader" action="imagebatchloader.php" method="get" onsubmit="return validateQueryForm(this)">
				<fieldset style="width:650px;padding:20px;">
					<legend><b>Query Criteria</b></legend>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Collection/Observation:</div> 
						<div style="float:left;">
							<select name="collid">
								<option value=''>Select Collection/Observation Project</option>
								<option value=''>---------------------------------------------------------</option>
								<?php echo $uploadManager->echoOccurrenceHoldings(); ?>
							</select>
						</div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Upload Date:</div> 
						<div style="float:left;"><input type="text" name="qloaddate" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Observer:</div> 
						<div style="float:left;"><input type="text" name="qobserver" style="width:250px;" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Family:</div> 
						<div style="float:left;"><input type="text" name="qfamily" style="width:250px;" /></div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:130px;">Scientific Name:</div> 
						<div style="float:left;"><input type="text" name="qsciname" style="width:250px;" /></div>
					</div>
					<div style="clear:both;margin:20px;">
						<input type="submit" name="action" value="Query Records" />
					</div>
					<div style="clear:both;margin-top:20px;">
						<fieldset style="margin:20px;width:550px;padding:15px;background-color:#FFF380;">
							<legend><b>Default Upload Parameters</b></legend>
							<div style='clear:both;'>
								<div style="float:left;width:120px;">Image Type:</div>
								<select name='dimagetype'>
									<option value='observation'>
										Observation Image
									</option>
									<option value='specimen'>
										Specimen Image
									</option>
									<option value='field'>
										Field Image
									</option>
								</select>
							</div>
							<div style="clear:both;">
								<div style="float:left;width:120px;">Photographer:</div>
								<select name="dphotographeruid">
									<option value="">Select a photographer</option>
									<option value="">--------------------------------</option>
									<?php $uploadManager->echoPhotographerSelect(); ?>
								</select>
							</div>
							<div style="clear:both;">
								<div style="float:left;width:120px;">Manager:</div>
								<div style="float:left;"><input name="downer" style="width:400px;" /></div>
							</div>
							<div style="clear:both;">
								<div style="float:left;width:120px;">Copyright URL:</div>
								<div style="float:left;"><input name="dcopyright" style="width:400px;" /></div>
							</div>
						</fieldset>
					</div>
				</fieldset>
			</form>
			<hr />
			<?php
			if($action == "Query Records" && $collId){
				$queryArr = Array("collid"=>$collId);
				$queryArr["loaddate"] = $_REQUEST["qloaddate"];
				$queryArr["observer"] = $_REQUEST["qobserver"];
				$queryArr["family"] = $_REQUEST["qfamily"];
				$queryArr["sciname"] = $_REQUEST["qsciname"];
				$recArr = $uploadManager->getOccurrenceRecords($queryArr);
				if($recArr){
					foreach($recArr as $occId => $v){
						?>
						<div>
							<form action="<?php echo $clientRoot;?>/collections/editor/occurrenceeditor.php" method="post" enctype='multipart/form-data' target="_blank" onsubmit="return validateImageSubmitForm(this)">
								<fieldset>
									<legend><b><?php echo $v["catalognumber"]; ?></b></legend>
									<div style="margin:3px;">
										<a href="<?php echo $clientRoot."/collections/individual/index.php?occid=".$occId;?>" target="_blank">
											<?php echo $occId;?>
										</a>
										<?php 
										echo $v["recordedby"];
										if($v["recordnumber"]) echo " [".$v["recordnumber"]."] ";
										echo ", ".$v["eventdate"]."; ".$v["sciname"];
										if($v["family"]) {
											echo " [".$v["family"]."]";
										}	
										echo "; ".$v["locality"];
										?>
									</div>
									<div style="padding:15px;">
										<?php 
										if(array_key_exists("images",$v)){
											$imgArr = $v["images"];
											foreach($imgArr as $imgId => $iArr){
												$tnUrl = (array_key_exists("tnurl",$iArr)?$iArr["tnurl"]:"");
												$url = (array_key_exists("url",$iArr)?$iArr["url"]:"");
												if(!$tnUrl) $tnUrl = $url;
												if(array_key_exists("imageDomain",$GLOBALS)){
													if(substr($url,0,1)=="/") $url = $GLOBALS["imageDomain"].$url;
													if(substr($tnUrl,0,1)=="/") $url = $GLOBALS["imageDomain"].$tnUrl;
												}
												?>
												<div style="margin:3px;float:left;">
													<a href="<?php echo $url;?>">
														<img src="<?php echo $tnUrl;?>" style="width:150px;" />
													</a>
												</div>
												<?php
											} 
										} 
										?>
									</div>
									<div style="clear:both;">
										<table>
											<tr>
												<td>
													<b>File:</b>
												</td>
												<td>
													<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
													<input name="imgfile" type="file" size="60" />
													<div style="margin-left:10px;">
														<input type="checkbox" name="createlargeimg" value="1" /> 
														Create a large version of image, when applicable<br/>
														* Upload image size can not be greater than 1MB
													</div>
												</td>
											</tr>
											<tr>
												<td>
													<b>Caption:</b>
												</td>
												<td>
													<input name="caption" value=""  style="width:300px;" />
												</td>
											</tr>
											<tr>
												<td>
													<b>Photographer:</b>
												</td>
												<td>
													<select name="photographeruid">
														<option value="">Select a photographer</option>
														<option value="">--------------------------------</option>
														<?php $uploadManager->echoPhotographerSelect($_REQUEST["dphotographeruid"]); ?>
													</select>
												</td>
											</tr>
											<tr>
												<td>
													<b>Photographer override:</b> 
												</td>
												<td>
													<input name='photographer' type='text' style="width:300px;" maxlength='100' />
													* Warning: value will override above selection
												</td>
											</tr>
											<tr>
												<td>
													<b>Image Type:</b> 
												</td>
												<td>
													<select name='imagetype'>
														<option value='observation'>
															Observation Image
														</option>
														<option value='specimen' <?php echo ($_REQUEST["dimagetype"]=="specimen"?"SELECTED":"");?>>
															Specimen Image
														</option>
														<option value='field' <?php echo ($_REQUEST["dimagetype"]=="field"?"SELECTED":"");?>>
															Field Image
														</option>
													</select>
												</td>
											</tr>
											<tr>
												<td>
													<b>Manager:</b>
												</td>
												<td>
													<input name="institutioncode" value="<?php echo $_REQUEST["downer"]; ?>"  style="width:300px;"/>
												</td>
											</tr>
											<tr>
												<td>
													<b>Copyright URL:</b>
												</td>
												<td>
													<input name="copyright" value="<?php echo $_REQUEST["dcopyright"]; ?>" style="width:300px;" />
												</td>
											</tr>
											<tr>
												<td>
													<b>Source Webpage:</b>
												</td>
												<td>
													<input name="sourceurl" type="text" size="40" value="" />
												</td>
											</tr>
											<tr>
												<td>
													<b>Notes:</b> 
												</td>
												<td>
													<input name="notes" type="text" size="40" value="" />
												</td>
											</tr>
											<tr>
												<td colspan="2" align="right">
													<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
													<input type="hidden" name="tid" value="<?php echo $v["tid"]; ?>" />
													<input type="hidden" name="submitaction" value="Submit New Image" />
													<input type="submit" name="action" value="Upload Image" />
												</td>
											</tr>
										</table>
									</div>
								</fieldset>
							</form>
						</div>
						<?php 
					}
				}
			}
		}
		else{
			echo "<div>You must be logged in and authorized to view this page. Please login.</div>";
		}
		?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
	
</body>
</html>
<?php 
class ImageUploadManager{

	private $conn;
	
	function __construct() {
		$this->setConnection();
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
		
	private function setConnection() {
 		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	public function getOccurrenceRecords($queryArr){
		$returnArr = Array();
		$sql = "SELECT o.occid, o.tidinterpreted, o.recordedby, o.recordnumber, o.eventdate, ".
			"o.family, o.sciname, o.locality, o.datelastmodified ".
			"FROM omoccurrences o ".
			"WHERE o.collid = ".$queryArr["collid"]." AND o.tidinterpreted IS NOT NULL ";
		if($queryArr["loaddate"]) $sql .= "AND o.datelastmodified = '".$queryArr["loaddate"]."' "; 
		if($queryArr["observer"]) $sql .= "AND o.recordedby LIKE '%".$queryArr["observer"]."%' "; 
		if($queryArr["family"]) $sql .= "AND o.family = '".$queryArr["family"]."' "; 
		if($queryArr["sciname"]) $sql .= "AND o.sciname LIKE '".$queryArr["sciname"]."%' ";
		//echo "SQL: ".$sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$occId = $row->occid;
			$returnArr[$occId]["tid"] = $row->tidinterpreted;
			$returnArr[$occId]["recordedby"] = $row->recordedby;
			$returnArr[$occId]["recordnumber"] = $row->recordnumber;
			$returnArr[$occId]["eventdate"] = $row->eventdate;
			$returnArr[$occId]["family"] = $row->family;
			$returnArr[$occId]["sciname"] = $row->sciname;
			$returnArr[$occId]["locality"] = $row->locality;
			$returnArr[$occId]["datelastmodified"] = $row->datelastmodified;
		}
		$result->close();
		//Grab images
		if($returnArr){
			$sql = "SELECT i.imgid, i.occid, i.url, i.thumbnailurl ".
				"FROM images i ".
				"WHERE i.occid IN (".implode(",",array_keys($returnArr)).")";
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$occId = $row->occid;
				if($row->url) $returnArr[$occId]["images"][$row->imgid]["url"] = $row->url;
				if($row->thumbnailurl) $returnArr[$occId]["images"][$row->imgid]["tnurl"] = $row->thumbnailurl;
			}
			$rs->close();
		}
		return $returnArr;	
	}

	public function echoOccurrenceHoldings(){
		$sql = "SELECT c.collid, c.collectionname FROM omcollections c ".
			"WHERE colltype = 'observations' ORDER BY c.collectionname";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->collid."' ".(strpos($row->collectionname,"Madrean")!==false?"SELECTED":"").">".$row->collectionname."</option>";
		}
		$rs->close();
	}

	public function echoPhotographerSelect($defaultUid = 0){
 		$sql = "SELECT u.uid, CONCAT_WS(' ',u.lastname,u.firstname) AS fullname ".
			"FROM users u ORDER BY u.lastname, u.firstname ";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			echo "<option value='".$row->uid."'".($defaultUid==$row->uid?" SELECTED":"").">".$row->fullname."</option>";
		}
		$result->close();
 	}
}

?>