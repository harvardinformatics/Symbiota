<!DOCTYPE html>

<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrencetabledisplay.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);

$collId = array_key_exists('collid',$_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : false;
$recLimit = array_key_exists('reclimit', $_REQUEST) ? filter_var($_REQUEST['reclimit'], FILTER_SANITIZE_NUMBER_INT) : 1000;
$occIndex = array_key_exists('occindex', $_REQUEST) ? filter_var($_REQUEST['occindex'], FILTER_SANITIZE_NUMBER_INT) : 0;
$crowdSourceMode = array_key_exists('csmode', $_REQUEST) ? filter_var($_REQUEST['csmode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$dynamicTable = array_key_exists('dynamictable', $_REQUEST) ? filter_var($_REQUEST['dynamictable'], FILTER_SANITIZE_NUMBER_INT) : 0;
$action = array_key_exists('submitaction', $_REQUEST) ? $_REQUEST['submitaction'] : '';

$occManager = new OccurrenceEditorManager();

if($crowdSourceMode) $occManager->setCrowdSourceMode(1);

$isEditor = 0;		//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences
$displayQuery = 0;
$isGenObs = 0;
$collMap = array();
$recArr = array();
$headerMapBase = array('institutioncode'=>'Institution Code (override)','collectioncode'=>'Collection Code (override)',
	'ownerinstitutioncode'=>'Owner Code (override)','catalognumber' => 'Catalog Number',
	'othercatalognumbers' => 'Other Catalog #','family' => 'Family','identificationqualifier' => 'ID Qualifier',
	'sciname' => 'Scientific Name','scientificnameauthorship'=>'Author','recordedby' => 'Collector','recordnumber' => 'Number',
	'associatedcollectors' => 'Associated Collectors','eventdate' => 'Event Date','verbatimeventdate' => 'Verbatim Date',
	'identificationremarks' => 'Identification Remarks','taxonremarks' => 'Taxon Remarks','identifiedby' => 'Identified By',
	'dateidentified' => 'Date Identified', 'identificationreferences' => 'Identification References',
	'country' => 'Country','stateprovince' => 'State/Province','county' => 'County','municipality' => 'Municipality',
	'locality' => 'Locality','decimallatitude' => 'Latitude', 'decimallongitude' => 'Longitude',
	'coordinateuncertaintyinmeters' => 'Uncertainty In Meters', 'verbatimcoordinates' => 'Verbatim Coordinates','geodeticdatum' => 'Datum',
	'georeferencedby' => 'Georeferenced By','georeferenceprotocol' => 'Georeference Protocol','georeferencesources' => 'Georeference Sources',
	'georeferenceverificationstatus' => 'Georef Verification Status','georeferenceremarks' => 'Georef Remarks',
	'minimumelevationinmeters' => 'Elev. Min. (m)','maximumelevationinmeters' => 'Elev. Max. (m)','verbatimelevation' => 'Verbatim Elev.',
	'minimumdepthinmeters' => 'Depth. Min. (m)','maximumdepthinmeters' => 'Depth. Max. (m)','verbatimdepth' => 'Verbatim Depth',
	'habitat' => 'Habitat','substrate' => 'Substrate','occurrenceremarks' => 'Notes (Occurrence Remarks)','associatedtaxa' => 'Associated Taxa',
	'verbatimattributes' => 'Description','lifestage' => 'Life Stage', 'sex' => 'Sex', 'individualcount' => 'Individual Count',
	'samplingprotocol' => 'Sampling Protocol', 'preparations' => 'Preparations', 'reproductivecondition' => 'Reproductive Condition',
	'typestatus' => 'Type Status','cultivationstatus' => 'Cultivation Status','establishmentmeans' => 'Establishment Means','datageneralizations' => 'Data Generalizations',
	'disposition' => 'Disposition','duplicatequantity' => 'Duplicate Qty','datelastmodified' => 'Date Last Modified', 'labelproject' => 'Project',
	'processingstatus' => 'Processing Status','recordenteredby' => 'Entered By','dbpk' => 'dbpk','basisofrecord' => 'Basis Of Record',
	'language' => 'Language');
$headMap = array();

$qryCnt = 0;
$statusStr = '';

if($SYMB_UID){
	$occManager->setCollId($collId);
	$collMap = $occManager->getCollMap();
	if($IS_ADMIN || ($collId && array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin']))){
		$isEditor = 1;
	}

	if($collMap && $collMap['colltype']=='General Observations') $isGenObs = 1;
	if(!$isEditor){
		if($isGenObs){
			if($collId && array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])){
				//Approved General Observation editors can add records
				$isEditor = 2;
			}
			elseif($action){
				//Lets assume that Edits where submitted and they remain on same specimen, user is still approved
				 $isEditor = 2;
			}
			elseif($occManager->getObserverUid() == $SYMB_UID){
				//User can only edit their own records
				$isEditor = 2;
			}
		}
		elseif($collId && array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollEditor"])){
			$isEditor = 2;
		}
	}

	if(array_key_exists('bufieldname',$_POST)){
		$occManager->setQueryVariables();
		$statusStr = $occManager->batchUpdateField($_POST['bufieldname'],$_POST['buoldvalue'],$_POST['bunewvalue'],$_POST['bumatch']);
	}

	if($occIndex !== false){
		//Query Form has been activated
		$occManager->setQueryVariables();
		$qryCnt = $occManager->getQueryRecordCount(1);
	}
	elseif(isset($_SESSION['editorquery'])){
		//Make sure query is null
		unset($_SESSION['editorquery']);
	}
	if(!is_numeric($occIndex)) $occIndex = 0;
	$recStart = floor($occIndex/$recLimit)*$recLimit;
	$recArr = $occManager->getOccurMap($recStart, $recLimit);
	$navStr = '<div class="navpath" style="float:right;">';


	if($recStart >= $recLimit){
		$navStr .= '<a href="#" onclick="return submitQueryForm(0);" title="'.(isset($LANG['FIRST'])?$LANG['FIRST']:'First').' '.$recLimit.' '.(isset($LANG['RECORDS'])?$LANG['RECORDS']:'records').'">|&lt;</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		$navStr .= '<a href="#" onclick="return submitQueryForm('.($recStart-$recLimit).');" title="'.(isset($LANG['PREVIOUS'])?$LANG['PREVIOUS']:'Previous').' '.$recLimit.' '.(isset($LANG['RECORDS'])?$LANG['RECORDS']:'records').'">&lt;&lt;</a>';
	}
	$navStr .= ' | ';
	$navStr .= ($recStart+1).'-'.($qryCnt<$recLimit+$recStart?$qryCnt:$recLimit+$recStart).' '.(isset($LANG['OF'])?$LANG['OF']:'of').' '.$qryCnt.' '.(isset($LANG['RECORDS'])?$LANG['RECORDS']:'records');
	$navStr .= ' | ';
	if($qryCnt > ($recLimit+$recStart)){
		$navStr .= '<a href="#" onclick="return submitQueryForm('.($recStart+$recLimit).');" title="'.(isset($LANG['NEXT'])?$LANG['NEXT']:'Next').' '.$recLimit.' '.(isset($LANG['RECORDS'])?$LANG['RECORDS']:'records').'">&gt;&gt;</a>&nbsp;&nbsp;&nbsp;&nbsp;';

		$navStr .= '<a href="#" onclick="return submitQueryForm('.(floor($qryCnt/$recLimit) * $recLimit).');" title="'.(isset($LANG['LAST'])?$LANG['LAST']:'Last').' '.$recLimit.' '.(isset($LANG['RECORDS'])?$LANG['RECORDS']:'records').'">&gt;|</a>';
	}
	$navStr .= '</div>';
}
else{
	header('Location: ../../profile/index.php?refurl=../collections/editor/occurrencetabledisplay.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));
}
?>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.(isset($LANG['TABLE_VIEW'])?$LANG['TABLE_VIEW']:'Occurrence Table View'); ?></title>
	<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, HTML_SPECIAL_CHARS_FLAGS); ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<link href="<?php echo htmlspecialchars($CLIENT_ROOT, HTML_SPECIAL_CHARS_FLAGS); ?>/js/datatables/datatables.min.css" type="text/css" rel="stylesheet">
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script src="../../js/datatables/datatables.min.js?ver=1" type="text/javascript"></script>
	<script type="text/javascript">
		$(document).ready(
			function () {
				$('#dynamictable').DataTable( {
					paging: false,
					searching: false,
					fixedColumns: {
						left: 1
					}
				} );
			}
		);
	</script>
	<script src="../../js/symb/collections.editor.table.js?ver=2" type="text/javascript" ></script>
	<script src="../../js/symb/collections.editor.query.js?ver=6" type="text/javascript" ></script>
	<style>
		#titleDiv { font-weight: bold; font-size: 1.5rem; width:790px; margin-bottom: 5px; }
		table.styledtable td { white-space: nowrap; }
		fieldset{ padding:15px }
		fieldset > legend{ font-weight:bold }
		.fieldGroupDiv{ clear:both; margin-bottom:2px; overflow: auto}
		.fieldDiv{ float:left; margin-right: 20px}
		#innertext{ background-color: white; margin: 0px 10px; }
		.editimg{ width: 15px; }
	</style>
</head>
<body style="margin-left: 0px; margin-right: 0px;background-color:white;">
	<a class="skip-link" href="#skip-search"><?php echo $LANG['SKIP_SEARCH'] ?></a>
	<div id="innertext">
		<?php
		if(($isEditor || $crowdSourceMode)){
			?>
			<div id="titleDiv">
				<div style="float:right;">
					<a href="#" title="<?php echo htmlspecialchars($LANG['SEARCH_FILTER'], HTML_SPECIAL_CHARS_FLAGS); ?>" aria-label="<?php echo htmlspecialchars($LANG['SEARCH_FILTER'], HTML_SPECIAL_CHARS_FLAGS); ?>" onclick="toggleQueryForm();"><img src="../../images/find.png" style="width:16px;" alt="<?php echo htmlspecialchars($LANG['IMG_SEARCH'], HTML_SPECIAL_CHARS_FLAGS); ?>" /></a>
					<?php
					if($isEditor == 1 || $isGenObs){
						?>
						<a href="#" title="<?php echo htmlspecialchars($LANG['BATCH_TOOL'], HTML_SPECIAL_CHARS_FLAGS); ?>" aria-label="<?php echo htmlspecialchars($LANG['BATCH_TOOL'], HTML_SPECIAL_CHARS_FLAGS); ?>" onclick="toggleBatchUpdate();return false;">
							<img class="editimg" src="../../images/editplus.png" alt="<?php echo htmlspecialchars($LANG['IMG_EDIT'], HTML_SPECIAL_CHARS_FLAGS); ?>" />
						</a>
						<?php
					}
					?>
				</div>
				<?php
				if($collMap) echo $collMap['collectionname'].' ('.$collMap['institutioncode'].($collMap['collectioncode']?':'.$collMap['collectioncode']:'').')';
				?>
			</div>
			<?php
			if(!$recArr) $displayQuery = 1;
			include 'includes/queryform.php';
			//Setup header map
			if($recArr){
				$headerArr = array();
				foreach($recArr as $id => $occArr){
					foreach($occArr as $k => $v){
						if(!is_array($v)){
							if($v && trim($v) && !array_key_exists($k,$headerArr)){
								$headerArr[$k] = $k;
							}
						}
					}
				}
				for($x=1; $x<9; $x++){
					if(isset($customArr[$x]['field'])){
						$customField = $customArr[$x]['field'];
						if($customField && !array_key_exists(strtolower($customField), $headerArr)){
							$headerArr[strtolower($customField)] = $customField;
						}
					}
				}
				$headerMap = array_intersect_key($headerMapBase, $headerArr);
			}
			if($isEditor == 1 || $isGenObs){
				$buFieldName = (array_key_exists('bufieldname',$_REQUEST)?$_REQUEST['bufieldname']:'');
				?>
				<div id="batchupdatediv" style="width:600px;clear:both;display:<?php echo ($buFieldName?'block':'none'); ?>;">
					<form name="batchupdateform" action="occurrencetabledisplay.php" method="post" onsubmit="return false;">
						<fieldset>
							<legend><b><?php echo (isset($LANG['BATCH_UPDATE'])?$LANG['BATCH_UPDATE']:'Batch Update'); ?></b></legend>
							<div style="float:left;">
								<div style="margin:2px;">
									<?php echo (isset($LANG['FIELD_NAME'])?$LANG['FIELD_NAME']:'Field name'); ?>:
									<select name="bufieldname" id="bufieldname" onchange="detectBatchUpdateField();">
										<option value=""><?php echo (isset($LANG['SELECT_FIELD'])?$LANG['SELECT_FIELD']:'Select Field Name'); ?></option>
										<option value="">----------------------</option>
										<?php
										asort($headerMapBase);
										foreach($headerMapBase as $k => $v){
											//Scientific name fields are excluded because batch updates will not update tidinterpreted index and authors
											//Scientific name updates should happen within
											if($k != 'scientificnameauthorship' && $k != 'sciname'){
												echo '<option value="'.$k.'" '.($buFieldName==$k?'SELECTED':'').'>'.$v.'</option>';
											}
										}
										?>
									</select>
								</div>
								<div style="margin:2px;">
									<?php echo (isset($LANG['CURRENT_VALUE'])?$LANG['CURRENT_VALUE']:'Current Value'); ?>:
									<input name="buoldvalue" type="text" value="<?php echo (array_key_exists('buoldvalue',$_REQUEST)?$_REQUEST['buoldvalue']:''); ?>" />
								</div>
								<div style="margin:2px;">
									<?php echo (isset($LANG['NEW_VALUE'])?$LANG['NEW_VALUE']:'New Value'); ?>:
									<span id="bunewvaluediv">
										<?php
										if($buFieldName=='processingstatus'){
											?>
											<select name="bunewvalue">
												<option value="unprocessed" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='unprocessed'?'SELECTED':''); ?>><?php echo (isset($LANG['UNPROCESSED'])?$LANG['UNPROCESSED']:'Unprocessed'); ?></option>
												<option value="unprocessed/nlp" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='unprocessed/nlp'?'SELECTED':''); ?>><?php echo (isset($LANG['UNPROCESSED_NLP'])?$LANG['UNPROCESSED_NLP']:'Unprocessed/NLP'); ?></option>
												<option value="stage 1" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='stage 1'?'SELECTED':''); ?>><?php echo (isset($LANG['STAGE_1'])?$LANG['STAGE_1']:'Stage 1'); ?></option>
												<option value="stage 2" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='stage 2'?'SELECTED':''); ?>><?php echo (isset($LANG['STAGE_2'])?$LANG['STAGE_2']:'Stage 2'); ?></option>
												<option value="stage 3" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='stage 3'?'SELECTED':''); ?>><?php echo (isset($LANG['STAGE_3'])?$LANG['STAGE_3']:'Stage 3'); ?></option>
												<option value="pending review-nfn" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='pending review-nfn'?'SELECTED':''); ?>><?php echo (isset($LANG['PENDING_NFN'])?$LANG['PENDING_NFN']:'Pending Review-NfN'); ?></option>
												<option value="pending review" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='pending review'?'SELECTED':''); ?>><?php echo (isset($LANG['PENDING_REVIEW'])?$LANG['PENDING_REVIEW']:'Pending Review'); ?></option>
												<option value="expert required" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='expert required'?'SELECTED':''); ?>><?php echo (isset($LANG['EXPERT_REQUIRED'])?$LANG['EXPERT_REQUIRED']:'Expert Required'); ?></option>
												<option value="reviewed" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='reviewed'?'SELECTED':''); ?>><?php echo (isset($LANG['REVIEWED'])?$LANG['REVIEWED']:'Reviewed'); ?></option>
												<option value="closed" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='closed'?'SELECTED':''); ?>><?php echo (isset($LANG['CLOSED'])?$LANG['CLOSED']:'Closed'); ?></option>
												<option value="" <?php echo (array_key_exists('bunewvalue',$_REQUEST)&&$_REQUEST['bunewvalue']=='no set status'?'SELECTED':''); ?>><?php echo (isset($LANG['NO_STATUS'])?$LANG['NO_STATUS']:'No Set Status'); ?></option>
											</select>
											<?php
										}
										else{
											?>
											<input name="bunewvalue" type="text" value="<?php echo (array_key_exists('bunewvalue',$_POST)?$_POST['bunewvalue']:''); ?>" />
											<?php
										}
										?>
									</span>
								</div>
							</div>
							<div style="float:left;margin-left:30px;">
								<div style="margin:2px;">
									<input name="bumatch" type="radio" value="0" checked />
									<?php echo (isset($LANG['MATCH_WHOLE'])?$LANG['MATCH_WHOLE']:'Match Whole Field'); ?><br/>
									<input name="bumatch" type="radio" value="1" />
									<?php echo (isset($LANG['MATCH_PART'])?$LANG['MATCH_PART']:'Match Any Part of Field'); ?>
								</div>
								<div style="margin:2px;">
									<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
									<input name="occid" type="hidden" value="0" />
									<input name="occindex" type="hidden" value="0" />
									<button name="submitaction" type="submit" value="Batch Update Field" onclick="submitBatchUpdate(this.form); return false;"><?php echo (isset($LANG['BATCH_UP_FIELD'])?$LANG['BATCH_UP_FIELD']:'Batch Update Field'); ?></button>
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			?>
			<div style="width:850px;clear:both;">
				<div class='navpath' style="float:left">
					<a href="../../index.php"><?php echo htmlspecialchars((isset($LANG['HOME'])?$LANG['HOME']:'Home'), HTML_SPECIAL_CHARS_FLAGS); ?></a> &gt;&gt;
					<?php
					if($crowdSourceMode){
						?>
						<a href="../specprocessor/crowdsource/index.php"><?php echo htmlspecialchars((isset($LANG['CENTRAL_CROWD'])?$LANG['CENTRAL_CROWD']:'Crowd Source Central'), HTML_SPECIAL_CHARS_FLAGS); ?></a> &gt;&gt;
						<?php
					}
					else{
						if(!$isGenObs || $IS_ADMIN){
							?>
							<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collId, HTML_SPECIAL_CHARS_FLAGS); ?>&emode=1"><?php echo htmlspecialchars((isset($LANG['COL_MANAGEMENT'])?$LANG['COL_MANAGEMENT']:'Collection Management'), HTML_SPECIAL_CHARS_FLAGS); ?></a> &gt;&gt;
							<?php
						}
						if($isGenObs){
							?>
							<a href="../../profile/viewprofile.php?tabindex=1"><?php echo htmlspecialchars((isset($LANG['PERS_MANAGEMENT'])?$LANG['PERS_MANAGEMENT']:'Personal Management'), HTML_SPECIAL_CHARS_FLAGS); ?></a> &gt;&gt;
							<?php
						}
					}
					?>
					<b><?php echo (isset($LANG['TABLE_VIEW'])?$LANG['TABLE_VIEW']:'Occurrence Table View'); ?></b>
				</div>
				<?php
				echo $navStr; ?>
			</div>
			<?php
			if($recArr){
				?>
				<div style="clear: both; padding-top:10px" id="skip-search">
					<?php
					$tableId = 'defaulttable';
					$tableClass = 'styledtable';
					if($dynamicTable){
						$tableId = 'dynamictable';
						$tableClass = 'stripe hover order-column compact nowrap cell-border';
					}
					?>
					<table id="<?php echo $tableId; ?>" class="<?php echo $tableClass; ?> accessible-font" title="<?php echo htmlspecialchars((isset($LANG['TABLE_VIEW']) ? $LANG['TABLE_VIEW'] : 'Occurrence Table View'), HTML_SPECIAL_CHARS_FLAGS); ?>" aria-describedby="table-desc">
						<thead>
							<tr>
								<th><?php echo (isset($LANG['SYMB_ID'])?$LANG['SYMB_ID']:'Symbiota ID'); ?></th>
								<?php
								foreach($headerMap as $k => $v){
									echo '<th>'.$v.'</th>';
								}
								?>
							</tr>
						</thead>
						<tbody>
							<?php
							$recCnt = 0;
							foreach($recArr as $id => $occArr){
								if($occArr['sciname']){
									$occArr['sciname'] = '<i>'.$occArr['sciname'].'</i> ';
								}
								echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
								echo '<td>';
								$url = 'occurrenceeditor.php?csmode='.$crowdSourceMode.'&occindex='.($recCnt+$recStart).'&occid='.$id.'&collid='.$collId;
								echo '<a href="' . htmlspecialchars($url, HTML_SPECIAL_CHARS_FLAGS) . '" title="' . htmlspecialchars((isset($LANG['SAME_WINDOW'])?$LANG['SAME_WINDOW']:'open in same window'), HTML_SPECIAL_CHARS_FLAGS) . '" aria-label="' .  htmlspecialchars($id, HTML_SPECIAL_CHARS_FLAGS) . '">' . htmlspecialchars($id, HTML_SPECIAL_CHARS_FLAGS) . '</a> ';
								echo '<a href="' . htmlspecialchars($url, HTML_SPECIAL_CHARS_FLAGS) . '" target="_blank" title="' . htmlspecialchars((isset($LANG['NEW_WINDOW'])?$LANG['NEW_WINDOW']:'open in new window'), HTML_SPECIAL_CHARS_FLAGS) . '" aria-label="' . htmlspecialchars((isset($LANG['NEW_WINDOW'])?$LANG['NEW_WINDOW']:'open in new window'), HTML_SPECIAL_CHARS_FLAGS) . '">';
								echo '<img src="../../images/newwin.png" style="width:10px;" alt="' . htmlspecialchars($LANG['IMG_LINK'], HTML_SPECIAL_CHARS_FLAGS) . '" />';
								echo '</a>';
								echo '</td>'."\n";
								foreach($headerMap as $k => $v){
									$displayStr = $occArr[$k];
									if($displayStr){
										if(strlen($displayStr) > 60){
											$displayStr = substr($displayStr,0,60).'...';
										}
									}
									else $displayStr = '&nbsp;';
									echo '<td>'.$displayStr.'</td>'."\n";
								}
								echo "</tr>\n";
								$recCnt++;
							}
							?>
						</tbody>
					</table>
					<p id="table-desc">
							<?php echo htmlspecialchars((isset($LANG['TABLE_VIEW_DESC']) ? $LANG['TABLE_VIEW_DESC'] : 'Table displays occurrence information with columns showing Symbiota ID, Family, Event Date, Author, Location, and other details'), HTML_SPECIAL_CHARS_FLAGS); ?>
					</p>
				</div>
				<div style="width:790px;">
					<?php echo $navStr; ?>
				</div>
				*<?php echo (isset($LANG['CLICK_ID'])?$LANG['CLICK_ID']:'Click on the Symbiota identifier in the first column to open the editor.'); ?>
				<?php
			}
			else{
				?>
				<div style="clear:both;padding:20px;font-weight:bold;font-size:120%;">
					<?php echo (isset($LANG['NONE_FOUND'])?$LANG['NONE_FOUND']:'No records found matching the query'); ?>
				</div>
				<?php
			}
		}
		else{
			if(!$isEditor){
				echo '<h2>'.(isset($LANG['NOT_AUTH'])?$LANG['NOT_AUTH']:'You are not authorized to access this page').'</h2>';
			}
		}
		?>
	</div>
</body>
</html>
