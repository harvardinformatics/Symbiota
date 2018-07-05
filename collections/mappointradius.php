<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$latCenter = 0;
$lngCenter = 0;
if(isset($MAPPING_BOUNDARIES) && $MAPPING_BOUNDARIES){
	$boundaryArr = explode(";",$MAPPING_BOUNDARIES);
	$latCenter = ($boundaryArr[0]>$boundaryArr[2]?((($boundaryArr[0]-$boundaryArr[2])/2)+$boundaryArr[2]):((($boundaryArr[2]-$boundaryArr[0])/2)+$boundaryArr[0]));
	$lngCenter = ($boundaryArr[1]>$boundaryArr[3]?((($boundaryArr[1]-$boundaryArr[3])/2)+$boundaryArr[3]):((($boundaryArr[3]-$boundaryArr[1])/2)+$boundaryArr[1]));
}
else{
	$latCenter = 42.877742;
	$lngCenter = -97.380979;
}
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE.' - '.$LANG['MPR_TITLE']; ?></title>
	</head>
	<body style="background-color:#ffffff;">
	<script src="//maps.googleapis.com/maps/api/js?<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'key='.$GOOGLE_MAP_KEY:''); ?>"></script>
	<script type="text/javascript">
		var map;
		var marker;
      	
        function initialize(){

			var dmOptions = {
				zoom: 5,
				center: new google.maps.LatLng(<?php echo $latCenter.','.$lngCenter; ?>),
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scaleControl: true
			};

	    	map = new google.maps.Map(document.getElementById("map"), dmOptions);

			google.maps.event.addListener(map, 'click', function(event) {
				if(marker) marker.setMap(null);
				marker = new google.maps.Marker({
					position: event.latLng,
					map: map
				});
				document.getElementById("latbox").value = event.latLng.lat().toFixed(5);
				document.getElementById("lonbox").value = event.latLng.lng().toFixed(5);
			});
        }

        function updateParentForm() {
            opener.document.getElementById("pointlat").value = Math.abs(parseFloat(document.getElementById("latbox").value));
            opener.document.getElementById("pointlong").value = Math.abs(parseFloat(document.getElementById("lonbox").value));
            if(opener.document.getElementById("radius").value == ""){
            	opener.document.getElementById("radius").value = 30;
            } 
            if(document.getElementById("latbox").value < 0) opener.document.getElementById('pointlat_NS').value = 'S';
            else opener.document.getElementById('pointlat_NS').value = 'N';
            if(document.getElementById("lonbox").value < 0) opener.document.getElementById('pointlong_EW').value = 'W';
            else opener.document.getElementById('pointlong_EW').value = 'E';
            self.close();
            return false;
        }

		google.maps.event.addDomListener(window, 'load', initialize);

	</script>
    <div><?php echo $LANG['MPR_INSTRUCTIONS']?></div>
    <div id='map' style='width: 100%; height: 520px'></div>
	<form id="mapForm" onsubmit="return updateParentForm();">
		<div>
			<?php echo $LANG['MPR_LAT']?>: <input type="text" id="latbox" size="13" name="lat" value="" />&nbsp;&nbsp;&nbsp; 
			<?php echo $LANG['MPR_LNG']?>: <input type="text" id="lonbox" size="13" name="lon" value="" /> &nbsp;&nbsp;&nbsp;
			<input type="submit" name="addcoords" value="<?php echo $LANG['SUBMIT']; ?>" />	
		</div>
	</form>
  </body>
</html>
