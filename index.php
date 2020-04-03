<?php
include_once('config/symbini.php');
//include_once('content/lang/index.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Home</title>
	<?php
	$activateJQuery = false;
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<link href="css/quicksearch.css" type="text/css" rel="Stylesheet" />
	<link href="js/jquery-ui-1.12.1/jquery-ui.min.css" type="text/css" rel="Stylesheet" />
	<script src="js/jquery-3.2.1.min.js" type="text/javascript"></script>
	<script src="js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>
	<script src="js/symb/api.taxonomy.taxasuggest.js" type="text/javascript"></script>
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/includes/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>Welcome to the Consortium of California Herbaria Portal (CCH2)</h1>
		<div style="float:right;margin-left:15px">
			<!--
			<div>
				<?php
				//---------------------------SLIDESHOW SETTINGS---------------------------------------
				//If more than one slideshow will be active, assign unique numerical ids for each slideshow.
				//If only one slideshow will be active, leave set to 1.
				$ssId = 1;
				//Enter number of images to be included in slideshow (minimum 5, maximum 10)
				$numSlides = 10;
				//Enter width of slideshow window (in pixels, minimum 275, maximum 800)
				$width = 350;
				//Enter amount of days between image refreshes of images
				$dayInterval = 7;
				//Enter amount of time (in milliseconds) between rotation of images
				$interval = 7000;
				//Enter checklist id, if you wish for images to be pulled from a checklist,
				//leave as 0 if you do not wish for images to come from a checklist
				//if you would like to use more than one checklist, separate their ids with a comma ex. "1,2,3,4"
				//$clid = '1279';
				$clid = '2';
				//Enter field, specimen, or both to specify whether to use only field or specimen images, or both
				$imageType = 'field';
				//Enter number of days of most recent images that should be included
				$numDays = 30;

				//---------------------------DO NOT CHANGE BELOW HERE-----------------------------
				ini_set('max_execution_time', 120);
				include_once($SERVER_ROOT.'/classes/PluginsManager.php');
				$pluginManager = new PluginsManager();
				echo $pluginManager->createSlideShow($ssId,$numSlides,$width,$numDays,$imageType,$clid,$dayInterval,$interval);
				?>
			//---------------------------END SLIDESHOW SETTINGS---------------------------------------
		</div>
			-->
		</div>
		<div style="padding: 0px 10px;font-size:120%">
			<div style="float:right"><img src="images/UC1278733_small.jpg" style="width:300px;margin:0px 15px" /></div>
			<p>
				<b>CCH2</b> serves data from specimens housed in CCH member herbaria. The data included in this database represents all
				specimen records from partner institutions.  The data served through this portal are currently growing due to the work of the
				<b>California Phenology Thematic Collections Network (CAP-TCN)</b>. This collaboration of 22 California universities, research stations,
				natural history collections, and botanical gardens aims to capture images, label data, and phenological (i.e., flowering time)
				data from nearly 1 million herbarium specimens by 2022. Data contained in the CCH2 portal will continue to grow even after
				this time through the activities of the CCH member institutions.
			</p>

			<p>For more information about the California Phenology TCN, visit the project website:</p>
				<div style="margin-left:15px;"><p><a href="https://www.capturingcaliforniasflowers.org" target="_blank">https://www.capturingcaliforniasflowers.org</a></p></div>

			<p>	For more information about the California Consortium of Herbaria (CCH) see:</p>
			<div style="margin-left:15px"><p><a href="http://ucjeps.berkeley.edu/consortium/about.html" target="_blank">http://ucjeps.berkeley.edu/consortium/about.html</a></p></div>

			<p>
				The California Phenology TCN is made possible by the National Science Foundation Award
				<a href="https://www.nsf.gov/awardsearch/showAward?AWD_ID=1802312&HistoricalAwards=false" target="_blank">1802312</a>.
				Any opinions, findings, and conclusions or recommendations expressed in this material are
				those of the author(s) and do not necessarily reflect the views of the National Science Foundation.
			</p>
			<p>
				Special thanks to the National Park Service who provided funds for the initial setup of the CCH2 website and database (November 2016).<br />
			</p>
			<p>
				Note also these other portals that will better serve the data needs of more-specialized users:
			</p>

				<div style="margin:10px 15px">
					California vascular plants - CCH1:
					For California vascular plants linked to the statewide flora project
					(the <a href="http://ucjeps.berkeley.edu/eflora/" target="_blank">Jepson eFlora: http://ucjeps.berkeley.edu/eflora/</a>),
					please see the original the <a href="http://ucjeps.berkeley.edu/consortium/" target="_blank">CCH1 portal (active since 2003)</a>.
				</div>
				<div style="margin:10px 15px">
				Pteridophytes: For world-wide ferns, lycophytes, and their extinct, free-sporing relatives, see the <a href="http://www.pteridoportal.org/portal/" target="_blank">Pteridophyte Collections Consortium (PCC)</a>. The CCH2 taxonomic thesaurus has been augmented based on the Checklist of Ferns and Lycophytes of the World, generously provided by Michael Hassler (who also supplied these data for the PCC Thesaurus).
				</div>
				<div style="margin:10px 15px">
					Macroalgae:
					For algae specimens, see the <a href="http://macroalgae.org" target="_blank">Macroalgal Herbarium Consortium Portal</a>.
				</div>
				<div style="margin:10px 15px">
					Brytophytes:
					For bryophyte specimens, see the <a href="http://bryophyteportal.org" blank="_blank">Consortium of North American Bryophyte Herbaria (CNABH)</a>.
				</div>
				<div style="margin:10px 15px">
					Lichens:
					For lichen specimens, see the <a href="http://lichenportal.org" target="_blank">Consortium of North American Lichen Herbaria (CNALH)</a>.
				</div>
				<div style="margin:10px 15px">
					Fungi:
					For fungi, see the <a href="http://mycoportal.org" target="_blank">Mycology Collections data Portal (MyCoPortal)</a>.
				</div>
		</div>

	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
