<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorServices.php');
header("Content-Type: application/json; charset=".$CHARSET);

$term = $_REQUEST['term'];
$target = isset($_REQUEST['target'])?$_REQUEST['target']:'';
$parentTerm = isset($_REQUEST['parentTerm'])?$_REQUEST['parentTerm']:'';

$searchManager = new OccurrenceEditorServices();
$retArr = $searchManager->getGeography($term, $target, $parentTerm);

echo json_encode($retArr);
?>