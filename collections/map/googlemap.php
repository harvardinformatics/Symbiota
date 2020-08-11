<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMapManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:0;
$gridSize = array_key_exists('gridSizeSetting',$_REQUEST)?$_REQUEST['gridSizeSetting']:10;
$minClusterSize = array_key_exists('minClusterSetting',$_REQUEST)?$_REQUEST['minClusterSetting']:50;

$occurManager = new OccurrenceMapManager();
$coordArr = $occurManager->getMappingData(0);

//Build taxa mapping key
$colorKey = Array();
$taxaKey = Array();
$taxaArr = $occurManager->getTaxaArr();
if(array_key_exists('taxa', $taxaArr)){
	foreach($taxaArr['taxa'] as $scinameStr => $snArr){
		if(isset($snArr['tid'])){
			$snTid = key($snArr['tid']);
			$taxaKey[$snTid]['str'] = $scinameStr;
			$taxaKey[$snTid]['target'] = $snTid;
			if(array_key_exists('synonyms', $snArr)){
				foreach($snArr['synonyms'] as $synTid => $synStr){
					$taxaKey[$synTid]['str'] = $synStr;
					$taxaKey[$synTid]['target'] = $snTid;
				}
			}
		}
		else{
			$taxaKey['orphan'][$scinameStr] = '';
		}
	}
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> - Google Map</title>
  <?php
    $activateJQuery = false;
    if(file_exists($SERVER_ROOT.'/includes/head.php')){
      include_once($SERVER_ROOT.'/includes/head.php');
    }
    else{
      echo '<link href="'.$CLIENT_ROOT.'/css/jquery-ui.css" type="text/css" rel="stylesheet" />';
      echo '<link href="'.$CLIENT_ROOT.'/css/base.css?ver=1" type="text/css" rel="stylesheet" />';
      echo '<link href="'.$CLIENT_ROOT.'/css/main.css?ver=1" type="text/css" rel="stylesheet" />';
    }
  ?>
	<script src="//www.google.com/jsapi"></script>
	<script src="//maps.googleapis.com/maps/api/js?<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'key='.$GOOGLE_MAP_KEY:''); ?>"></script>
	<script type="text/javascript" src="../../js/symb/markerclusterer.js?ver=260913"></script>
	<script type="text/javascript" src="../../js/symb/oms.min.js"></script>
	<script type="text/javascript" src="../../js/symb/keydragzoom.js"></script>
	<script type="text/javascript">
		var map = null;
		var markerClusterer = null;
		var useLLDecimal = true;
		var infoWins = new Array();
		var puWin;
		var markers = [];

		function initialize(){
			<?php
			$boundLatMin = -90;
			$boundLatMax = 90;
			$boundLngMin = -180;
			$boundLngMax = 180;
			$latCen = 41.0;
			$longCen = -95.0;
			if(isset($MAPPING_BOUNDARIES)){
				$coorArr = explode(";",$MAPPING_BOUNDARIES);
				if($coorArr && count($coorArr) == 4){
					$boundLatMin = $coorArr[2];
					$boundLatMax = $coorArr[0];
					$boundLngMin = $coorArr[3];
					$boundLngMax = $coorArr[1];
					$latCen = ($boundLatMax + $boundLatMin)/2;
					$longCen = ($boundLngMax + $boundLngMin)/2;
				}
			}
			?>
			var dmOptions = {
				zoom: 3,
				center: new google.maps.LatLng(<?php echo $latCen.','.$longCen; ?>),
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scaleControl: true
			};

			map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);

			oms = new OverlappingMarkerSpiderfier(map);

			map.enableKeyDragZoom({
				visualEnabled: true,
				visualPosition: google.maps.ControlPosition.LEFT,
				visualPositionOffset: new google.maps.Size(35, 0),
				visualPositionIndex: null,
				visualSprite: "../../images/dragzoom_btn.png",
				visualSize: new google.maps.Size(20, 20),
				visualTips: {
					off: "Turn on",
					on: "Turn off"
				}
			});

			oms.addListener('click', function(marker, event) {
				closeAllInfoWins();
				occid = marker.occid;
				clid = marker.clid;
				openIndPU(occid,clid);
			});

			oms.addListener('spiderfy', function(markers) {
				closeAllInfoWins();
			});

		   <?php
			$markerCnt = 0;
			$spCnt = 0;
			$minLng = 180; $minLat = 90; $maxLng = -180; $maxLat = -90;
			$iconColors = array('fc6355','5781fc','fcf357','00e13c','e14f9e','55d7d7','ff9900','7e55fc');
			$iconColorKey = 0;
			foreach($coordArr as $sciName => $valueArr){
				?>
				markers = [];
				<?php
				$iconColor = '';
				$tid = 0;
				if(array_key_exists('tid', $valueArr)){
					$tid = $valueArr['tid'];
					unset($valueArr['tid']);
					if(isset($taxaKey[$tid]['target'])){
						$tid = $taxaKey[$tid]['target'];
					}
					elseif($sciName != 'undefined' && isset($taxaKey['orphan'])){
						foreach($taxaKey['orphan'] as $nameKey => $nameRaw){
							if(stripos($sciName,$nameKey) !== false) $tid = $nameKey;
						}
					}
					else{
						$tid = 0;
					}
				}
				elseif($sciName != 'undefined' && isset($taxaKey['orphan'])){
					foreach($taxaKey['orphan'] as $nameKey => $nameRaw){
						if(stripos($sciName,$nameKey) !== false) $tid = $nameKey;
					}
				}
				if(!array_key_exists($tid, $colorKey)){
					$colorKey[$tid] = $iconColors[$iconColorKey%8];
					$iconColorKey++;
				}
				$iconColor = $colorKey[$tid];
				foreach($valueArr as $occid => $spArr){
					//Find max/min point values
					if($spArr['lat'] < $minLat) $minLat = $spArr['lat'];
					if($spArr['lat'] > $maxLat) $maxLat = $spArr['lat'];
					if($spArr['lng'] < $minLng) $minLng = $spArr['lng'];
					if($spArr['lng'] > $maxLng) $maxLng = $spArr['lng'];
					//Create marker
					$displayStr = '';
					$collCode = '';
					if(isset($spArr['collcode']) && $spArr['collcode']) $collCode = '-'.$spArr['collcode'];
					if(isset($spArr['catnum']) && $spArr['catnum']){
						if(is_numeric($spArr['catnum'])){
							$displayStr = $spArr['instcode'].$collCode.'-'.$spArr['catnum'];
						}
						else{
							$displayStr = $spArr['catnum'];
						}
					}
					elseif(isset($spArr['collector']) && $spArr['collector']){
						$displayStr = $spArr['instcode'].$collCode.'-'.$spArr['collector'];
					}
					elseif(isset($spArr['ocatnum']) && $spArr['ocatnum']){
						$displayStr = $spArr['instcode'].$collCode.'-'.$spArr['ocatnum'];
					}
					if($spArr['colltype'] == 'obs'){
						?>
						var markerIcon = {path:"m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z",fillColor:"#<?php echo $iconColor; ?>",fillOpacity:1,scale:1,strokeColor:"#000000",strokeWeight:1};
						<?php
					}
					else{
						?>
						var markerIcon = {path:google.maps.SymbolPath.CIRCLE,fillColor:"#<?php echo $iconColor; ?>",fillOpacity:1,scale:7,strokeColor:"#000000",strokeWeight:1};
						<?php
					}
					echo 'var m'.$markerCnt.' = getMarker('.$spArr['lat'].','.$spArr['lng'].',"'.addslashes($displayStr).'",markerIcon,"'.$spArr['colltype'].'","'.(isset($spArr['tid'])?$spArr['tid']:0).'",'.$occid.','.($clid?$clid:'0').');',"\n";
					echo 'oms.addMarker(m'.$markerCnt.');',"\n";
					$markerCnt++;
				}
				?>
				//set style options for marker clusters (these are the default styles)
				mcOptions<?php echo $spCnt; ?> = {
					styles: [{
						color: "<?php echo $iconColor; ?>"
					}],
					maxZoom: 13,
					gridSize: <?php echo $gridSize; ?>,
					minimumClusterSize: <?php echo $minClusterSize; ?>
				}
				//Initialize clusterer with options
				var markerCluster<?php echo $spCnt; ?> = new MarkerClusterer(map, markers, mcOptions<?php echo $spCnt; ?>);
				<?php
				$spCnt++;
			}
			//Add some padding
			$padding = 0.5;
			if($minLat > -80) $minLat -= $padding;
			if($maxLat < 80) $maxLat += $padding;
			if($minLng > -170) $minLng -= $padding;
			if($maxLng < 170) $maxLng += $padding;
			?>
			var bounds = new google.maps.LatLngBounds(new google.maps.LatLng(<?php echo $minLat.','.$minLng; ?>),new google.maps.LatLng(<?php echo $maxLat.','.$maxLng; ?>));
			map.fitBounds(bounds);
			map.panToBounds(bounds);
		}

		function openIndPU(occId,clid){
			newWindow = window.open('../individual/index.php?occid='+occId+'&clid='+clid,'indspec' + occId,'scrollbars=1,toolbar=0,resizable=1,width=1100,height=800,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
			setTimeout(function () { newWindow.focus(); }, 0.5);
		}

		function closeAllInfoWins(){
			for( var w = 0; w < infoWins.length; w++ ) {
				var win = infoWins[w];
				win.close();
			}
		}

		function getMarker(newLat, newLng, newTitle, newIcon, type, tid, occid, clid){
			var m = new google.maps.Marker({
				position: new google.maps.LatLng(newLat, newLng),
				title: newTitle,
				icon: newIcon,
				customInfo: type,
				taxatid: tid,
				occid: occid,
				clid: clid
			});
			markers.push(m);
			return m;
		}

		function addRefPoint(){
			var lat = document.getElementById("lat").value;
			var lng = document.getElementById("lng").value;
			var title = document.getElementById("title").value;
			if(!useLLDecimal){
				var latdeg = document.getElementById("latdeg").value;
				var latmin = document.getElementById("latmin").value;
				var latsec = document.getElementById("latsec").value;
				var latns = document.getElementById("latns").value;
				var longdeg = document.getElementById("longdeg").value;
				var longmin = document.getElementById("longmin").value;
				var longsec = document.getElementById("longsec").value;
				var longew = document.getElementById("longew").value;
				if(latdeg != null && longdeg != null){
					if(latmin == null) latmin = 0;
					if(latsec == null) latsec = 0;
					if(longmin == null) longmin = 0;
					if(longsec == null) longsec = 0;
					lat = latdeg*1 + latmin/60 + latsec/3600;
					lng = longdeg*1 + longmin/60 + longsec/3600;
					if(latns == "S") lat = lat * -1;
					if(longew == "W") lng = lng * -1;
				}
			}
			if(lat != null && lng != null){
				if(lat < -180 || lat > 180 || lng < -180 || lng > 180){
					window.alert("Latitude and Longitude must be of values between -180 and 180 (" + lat + ";" + lng + ")");
				}
				else{
					var addPoint = true;
					if(lng > 0) addPoint = window.confirm("Longitude is positive, which will put the marker in the eastern hemisphere (e.g. Asia).\nIs this what you want?");
					if(!addPoint) lng = -1*lng;

					var iconImg = new google.maps.MarkerImage( '../../images/google/arrow.png' );

					var m = new google.maps.Marker({
						position: new google.maps.LatLng(lat,lng),
						map: map,
						title: title,
						icon: iconImg,
						zIndex: google.maps.Marker.MAX_ZINDEX
					});
				}
			}
			else{
				window.alert("Enter values in the latitude and longitude fields");
			}
		}

		function toggleLatLongDivs(){
			var divs = document.getElementsByTagName("div");
			for (i = 0; i < divs.length; i++) {
				var obj = divs[i];
				if(obj.getAttribute("class") == "latlongdiv" || obj.getAttribute("className") == "latlongdiv"){
					if(obj.style.display=="none"){
						obj.style.display="block";
					}
					else{
						obj.style.display="none";
					}
				}
			}
			if(useLLDecimal){
				useLLDecimal = false;
			}
			else{
				useLLDecimal = true;
			}
		}

	</script>
</head>
<body style="background-color:#ffffff;width:100%" onload="initialize();">
	<?php
	if(!$coordArr){
		?>
			<div style="font-size:120%;font-weight:bold;">
				Your query apparently does not contain any records with coordinates that can be mapped.
			</div>
			<div style="margin-left:20px;">
				Either the records in the query are not georeferenced (no lat/long)<br/>
			</div>
			<div style="margin-left:100px;">
				-or-
			</div>
			<div style="margin-left:20px;">
				Rare/threatened status requires the locality coordinates be hidden.
			</div>
		<?php
	}
	?>
	<div id="map_canvas" style="width:100%;height:700px"></div>
	<table title='Add Point of Reference' style="width:100%;" >
		<tr>
			<td style="width:330px" valign='top'>
				<fieldset>
					<legend>Legend</legend>
					<div style="float:left;">
						<?php
						foreach($colorKey as $iconKey => $colorCode){
							echo '<div>';
							echo '<svg xmlns="http://www.w3.org/2000/svg" style="height:12px;width:12px;margin-bottom:-2px;"><g><rect x="1" y="1" width="11" height="10" fill="#'.$colorCode.'" stroke="#000000" stroke-width="1px" /></g></svg> ';
							if(!$iconKey) echo '= various taxa';
							elseif(is_numeric($iconKey)) echo '= <i>'.$taxaKey[$iconKey]['str'].'</i>';
							elseif(isset($taxaKey['orphan'][$iconKey])) echo '= <i>'.$iconKey.'</i>';
							else echo '= various taxa';
							echo '</div>';
						}
						?>
					</div>
					<div style="float:right;">
						<div>
							<svg xmlns="http://www.w3.org/2000/svg" style="height:15px;width:15px;margin-bottom:-2px;">">
								<g>
									<circle cx="7.5" cy="7.5" r="7" fill="white" stroke="#000000" stroke-width="1px" ></circle>
								</g>
							</svg> = Collection
						</div>
						<div>
							<svg style="height:14px;width:14px;margin-bottom:-2px;">" xmlns="http://www.w3.org/2000/svg">
								<g>
									<path stroke="#000000" d="m6.70496,0.23296l-6.70496,13.48356l13.88754,0.12255l-7.18258,-13.60611z" stroke-width="1px" fill="white"/>
								</g>
							</svg> = Observation
						</div>
					</div>
				</fieldset>
			</td>
			<td style="width:375px;" valign='top'>
				<div>
					<fieldset>
						<legend>Add Point of Reference</legend>
						<div style='float:left;width:350px;'>
							<div class="latlongdiv">
								<div>
									Latitude decimal: <input name='lat' id='lat' size='10' type='text' /> eg: 34.57
								</div>
								<div style="margin-top:5px;">
									Longitude decimal: <input name='lng' id='lng' size='10' type='text' /> eg: -112.38
								</div>
								<div style='font-size:80%;margin-top:5px;'>
									<a href='#' onclick='toggleLatLongDivs();'>Enter in D:M:S format</a>
								</div>
							</div>
							<div class='latlongdiv' style='display:none;'>
								<div>
									Latitude:
									<input name='latdeg' id='latdeg' size='2' type='text' />&deg;
									<input name='latmin' id='latmin' size='5' type='text' />&prime;
									<input name='latsec' id='latsec' size='5' type='text' />&Prime;
									<select name='latns' id='latns'>
										<option value='N'>N</option>
										<option value='S'>S</option>
									</select>
								</div>
								<div style="margin-top:5px;">
									Longitude:
									<input name='longdeg' id='longdeg' size='2' type='text' />&deg;
									<input name='longmin' id='longmin' size='5' type='text' />&prime;
									<input name='longsec' id='longsec' size='5' type='text' />&Prime;
									<select name='longew' id='longew'>
										<option value='E'>E</option>
										<option value='W' selected>W</option>
									</select>
								</div>
								<div style='font-size:80%;margin-top:5px;'>
									<a href='#' onclick='toggleLatLongDivs();'>Enter in Decimal format</a>
								</div>
							</div>
						</div>
						<div style="float:right;width:100px;">
							<div style="float:right;">
								Marker Name: <input name='title' id='title' size='20' type='text' />
							</div><br />
							<div style="float:right;margin-top:10px;">
								<input type='submit' value='Add Marker' onclick='addRefPoint();' />
							</div>
						</div>
					</fieldset>
				</div>
			</td>
		</tr>
	</table>
</body>
</html>