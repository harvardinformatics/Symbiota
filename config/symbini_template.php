<?php
$DEFAULT_LANG = 'en';			//Default language
$DEFAULT_PROJ_ID = 1;
$DEFAULTCATID = 0;
$DEFAULT_TITLE = '';
$EXTENDED_LANG = 'en';		//Add all languages you want to support separated by commas (e.g. en,es); currently supported languages: en,es
$TID_FOCUS = '';
$ADMIN_EMAIL = '';
$CHARSET = '';					//ISO-8859-1 or UTF-8
$PORTAL_GUID = '';				//Typically a UUID
$SECURITY_KEY = '';				//Typically a UUID used to verify access to certain web service

$CLIENT_ROOT = '';				//URL path to project root folder (relative path w/o domain, e.g. '/seinet')
$SERVER_ROOT = '';				//Full path to Symbiota project root folder
$TEMP_DIR_ROOT = $SERVER_ROOT.'/temp';				//Must be writable by Apache; will use system default if not specified
$LOG_PATH = $SERVER_ROOT.'/content/logs';					//Must be writable by Apache; will use <SYMBIOTA_ROOT>/temp/logs if not specified

//the root for the image directory
$IMAGE_DOMAIN = '';				//Domain path to images, if different from portal
$IMAGE_ROOT_URL = '';				//URL path to images
$IMAGE_ROOT_PATH = '';			//Writable path to images, especially needed for downloading images

//Pixel width of web images
$IMG_WEB_WIDTH = 1400;
$IMG_TN_WIDTH = 200;
$IMG_LG_WIDTH = 3200;
$IMG_FILE_SIZE_LIMIT = 300000;		//Files above this size limit and still within pixel width limits will still be resaved w/ some compression
$IPLANT_IMAGE_IMPORT_PATH = '';		//Path used to map/import images uploaded to the iPlant image server (e.g. /home/shared/project-name/--INSTITUTION_CODE--/, the --INSTITUTION_CODE-- text will be replaced with collection's institution code)

//$USE_IMAGE_MAGICK = 0;		//1 = ImageMagick resize images, given that it's installed (faster, less memory intensive)
$TESSERACT_PATH = ''; 			//Needed for OCR function in the occurrence editor page
$NLP_LBCC_ACTIVATED = 0;
$NLP_SALIX_ACTIVATED = 0;

//Module activations
$OCCURRENCE_MOD_IS_ACTIVE = 1;
$FLORA_MOD_IS_ACTIVE = 1;
$KEY_MOD_IS_ACTIVE = 1;

//Configurations for publishing to GBIF
$GBIF_USERNAME = '';                //GBIF username which portal will use to publish
$GBIF_PASSWORD = '';                //GBIF password which portal will use to publish
$GBIF_ORG_KEY = '';                 //GBIF organization key for organization which is hosting this portal

//Misc variables
$DEFAULT_TAXON_SEARCH = 2;			//Default taxonomic search type: 1 = Any Name, 2 = Scientific Name, 3 = Family, 4 = Taxonomic Group, 5 = Common Name

$GOOGLE_MAP_KEY = '';				//Needed for Google Map; get from Google
$MAPBOX_API_KEY = '';
$MAP_THUMBNAILS = false;				//Display Static Map thumbnails within taxon profile, checklist, etc

$MAPPING_BOUNDARIES = '';			//Project bounding box; default map centering; (e.g. 42.3;-100.5;18.0;-127)
$ACTIVATE_GEOLOCATION = false;		//Activates HTML5 geolocation services in Map Search
$GOOGLE_ANALYTICS_KEY = '';			//Needed for setting up Google Analytics
$GOOGLE_ANALYTICS_TAG_ID = '';		//Needed for setting up Google Analytics 4 Tag ID
$RECAPTCHA_PUBLIC_KEY = '';			//Now called site key
$RECAPTCHA_PRIVATE_KEY = '';		//Now called secret key
$TAXONOMIC_AUTHORITIES = array('COL'=>'','WoRMS'=>'');		//List of taxonomic authority APIs to use in data cleaning and thesaurus building tools, concatenated with commas and order by preference; E.g.: array('COL'=>'','WoRMS'=>'','TROPICOS'=>'','EOL'=>'')
$QUICK_HOST_ENTRY_IS_ACTIVE = 0;   	//Allows quick entry for host taxa in occurrence editor
$GLOSSARY_EXPORT_BANNER = '';		//Banner image for glossary exports. Place in images/layout folder.
$DYN_CHECKLIST_RADIUS = 10;			//Controls size of concentric rings that are sampled when building Dynamic Checklist
$DISPLAY_COMMON_NAMES = 1;			//Display common names in species profile page and checklists displays
$ACTIVATE_EXSICCATI = 0;			//Activates exsiccati fields within data entry pages; adding link to exsiccati search tools to portal menu is recommended
$ACTIVATE_GEOLOCATE_TOOLKIT = 0;	//Activates GeoLocate Toolkit located within the Processing Toolkit menu items
$OCCUR_SECURITY_OPTION = 1;			//Occurrence security options supported: value 1-7; 1 = Locality security, 2 = Taxon security, 4 = Full security, 3 = L & T, 5 = L & F, 6 = T & F, 7 = all
$SEARCH_BY_TRAITS = '0';			//Activates search fields for searching by traits (if trait data have been encoded): 0 = trait search off; any number of non-zeros separated by commas (e.g., '1,6') = trait search on for the traits with these id numbers in table tmtraits.
$PLOT_TRAITS = array(''); /*This controls which traits are presented in plots on the taxon profile pages. Each desired plot should be a separate array element. An array element consists of a string with three required parts and one optional part separated by commas:
  - the internal ID number of the trait (id),
  - the type of plot (type), and
  - the summary method (summary).
  - optionally, a semicolon separated list of the internal IDs of a subset of character states (limitto) can be specified to limit the plot to only the states listed.
Available options for plot type include:
  -'polar'
  -'bar'
Available options for summary method include:
  -'bymonth' counts all occurrences in each month across all years.
	-'byyear' counts all occurrences in each year.
	-'bycountry' counts over the appropriate administrative division
	-'bystate'
	-'bycounty'.
The 'bycountry', 'bystate', and 'bycounty' methods count over the appropriate administrative division where bycountry is a country or equivalent sovereignty as stored in the country field, 'bystate' is the State/Province/Department or equivalent first-level division as stored in the stateProvince field. stateProvince is nested by country when there is more than one country. 'bycounty' is the county/district/canton or equivalent second-level division in the county field. County is nested by the stateProvince when there is more than one, and stateProvince is nested by country when there is more than one.
Ex.
array('id=1, type=polar, summary=bymonth, limitto=2', 'id=3, type=bar, summary=byear');
The above example will produce two plots on the taxon profile pages, the first is for trait with id #1 and will be a monthly polar plot only showing results from the state with id #2 (assuming state 2 is linked to trait 1). The second plot will be a barplot by year summarizing the trait with id #3.
*/

$IGSN_ACTIVATION = 0;

//$SMTP_ARR = array('host'=>'','port'=>587,'username'=>'','password'=>'','timeout'=>60);  //Host is requiered, others are optional and can be removed

$RIGHTS_TERMS = array(
	'CC0 1.0 (Public-domain)' => 'http://creativecommons.org/publicdomain/zero/1.0/',
	'CC BY (Attribution)' => 'http://creativecommons.org/licenses/by/4.0/',
	'CC BY-NC (Attribution-Non-Commercial)' => 'http://creativecommons.org/licenses/by-nc/4.0/'
);
//$CSS_BASE_PATH = '/css/custom';	//To create a custom styling, uncomment, move all css files from /css/symb to new CSS Base Path, and modify as needed
$CSS_VERSION_LOCAL = '20170414';		//Changing this variable will force a refresh of main.css styles within users browser cache for all pages

/*
//Default editor properties; properties defined in collection will override these values
$EDITOR_PROPERTIES = array(
	'modules-panel' => array(
		'paleo' => array('status'=>0,'titleOverride'=>'Paleonotology Terms')
	),
	'features' => array('catalogDupeCheck'=>1,'otherCatNumDupeCheck'=>0,'dupeSearch'=>1),
	'labelOverrides' => array(),
	'cssTerms' => array(
		'#recordNumberDiv'=>array('float'=>'left','margin-right'=>'2px'),
		'#recordNumberDiv input'=>array('width'=>'60px'),
		'#eventDateDiv'=>array('float'=>'left'),
		'#eventDateDiv input'=>array('width'=>'110px')
	),
	'customCSS' => array(),
	'customLookups' => array(
		'processingStatus' => array('Unprocessed','Stage 1','Stage 2','Pending Review','Expert Required','Reviewed','Closed')
	)
);
// json: {"editorProps":{"modules-panel":{"paleo":{"status":1}}}}
*/

$COOKIE_SECURE = false;
if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443){
	header("strict-transport-security: max-age=600");
	$COOKIE_SECURE = true;
}

//Base code shared by all pages; leave as is
include_once("symbbase.php");
/* --DO NOT ADD ANY EXTRA SPACES BELOW THIS LINE-- */?>
