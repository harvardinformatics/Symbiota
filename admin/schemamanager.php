<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SchemaManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$host = MySQLiConnectionFactory::$SERVERS[0]['host'];
if(isset($_POST['host'])) $host = filter_var($_POST['host']);
$database = MySQLiConnectionFactory::$SERVERS[0]['database'];
if(isset($_POST['database'])) $database = filter_var($_POST['database'], FILTER_SANITIZE_STRING);
$port = MySQLiConnectionFactory::$SERVERS[0]['port'];
if(isset($_POST['port'])) $port = filter_var($_POST['port'], FILTER_SANITIZE_NUMBER_INT);
$username = isset($_POST['username']) ? filter_var($_POST['username'], FILTER_SANITIZE_STRING) : '';
$schemaCode = isset($_POST['schemaCode']) ? filter_var($_POST['schemaCode'], FILTER_SANITIZE_STRING) : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

$schemaManager = new SchemaManager();
$verHistory = $schemaManager->getVersionHistory();
$curentVersion = $schemaManager->getCurrentVersion();
?>
<html>
	<head>
		<title>Database Schema Manager</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<style type="text/css">
			label{ font-weight:bold; }
			fieldset legend{ font-weight:bold; }
			.info-div{ margin:10px 5px; }
			.form-section{ margin: 5px 10px; }
			button{ margin: 15px; }
		</style>
	</head>
	<body>
		<?php
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div id="innertext">
			<h1>Database Schema Manager</h1>
			<div style="margin:15px;">
				<label>Current version: </label>
				<?php echo $curentVersion?$curentVersion:'not installed'; ?>
			</div>
			<?php
			if($verHistory){
				?>
				<div style="margin:15px">
					<table class="styledtable" style="width:300px;">
						<tr><th>Version</th><th>Date Applied</th></tr>
						<?php
						foreach($verHistory as $ver => $date){
							echo '<tr><td>'.$ver.'</td><td>'.$date.'</td></tr>';
						}
						?>
					</table>
				</div>
				<?php
			}
			if($IS_ADMIN){
				if($action == 'installSchema'){
					$schemaManager->setTargetSchema($schemaCode);
					$schemaManager->installPatch($host, $username, $database, $port);
				}
			}
			?>
			<fieldset>
				<legend>Database Schema Assistant</legend>
				<div class="info-div">Enter database criteria that will be used to apply database schema patch.
				The database user must have full DDL pivileges (e.g. create/alter tables, routines, indexes, etc.)
				We recommend creating a backup of the database before applying any database patches.</div>
				<form name="databaseMaintenanceForm" action="schemamanager.php" method="post">
					<div class="form-section">
						<label>Host:</label>
						<input name="database" type="text" value="<?php echo $host; ?>" required>
					</div>
					<div class="form-section">
						<label>Username:</label>
						<input name="username" type="text" value="<?php echo $username; ?>" required autocomplete="off">
					</div>
					<div class="form-section">
						<label>Password: </label>
						<input name="password" type="password" value="" required autocomplete="off">
					</div>
					<div class="form-section">
						<label>Database name:</label>
						<input name="database" type="text" value="<?php echo $database; ?>" required>
					</div>
					<div class="form-section">
						<label>Port:</label>
						<input name="port" type="text" value="<?php echo $port; ?>" required>
					</div>
					<div class="form-section">
						<label>Schema: </label>
						<select name="schemaCode">
							<option value="1.0" <?php echo !$curentVersion || $curentVersion < 1 ? 'selected' : ''; ?>>Base Schema 1.0</option>
							<option value="1.1"<?php echo $curentVersion == 1.0 ? 'selected' : ''; ?>>Schema Patch 1.1</option>
							<option value="1.2"<?php echo $curentVersion == 1.1 ? 'selected' : ''; ?>>Schema Patch 1.2</option>
							<option value="2.0"<?php echo $curentVersion == 1.2 ? 'selected' : ''; ?>>Schema Patch 2.0</option>
						</select>
					</div>
					<div class="form-section">
						<button name="action" type="submit" value="installSchema">Install</button>
					</div>
				</form>
			</fieldset>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
