<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TPDescEditorManager.php');
header('Content-Type: text/html; charset='.$CHARSET);

$tid = array_key_exists('tid',$_REQUEST)?$_REQUEST['tid']:0;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$descEditor = new TPDescEditorManager();
if($tid) $descEditor->setTid($tid);

$isEditor = false;
if($IS_ADMIN || array_key_exists('TaxonProfile',$USER_RIGHTS)) $isEditor = true;

if($isEditor){
	$descList = $descEditor->getDescriptions(true)
	?>
	<script type="text/javascript">
		tinymce.init({
			selector: "textarea",
			width: "100%",
			height: 300,
			menubar: false,
			plugins: "link,charmap,code,paste",
			toolbar : "bold italic underline cut copy paste outdent indent undo redo subscript superscript removeformat link charmap code",
			default_link_target: "_blank",
			paste_as_text: true
		});
	</script>
	<div style="float:right;" onclick="toggle('adddescrblock');" title="Add a New Description">
		<img style='border:0px;width:15px;' src='../../images/add.png'/>
	</div>
	<div id='adddescrblock' style='display:<?php echo ($descList?'none':''); ?>;'>
		<form name='adddescrblockform' action="tpeditor.php" method="post">
			<fieldset style='width:90%;margin:10px;padding:10px;'>
    			<legend><b>New Description Block</b></legend>
				<div style=''>
					Language: <input id="language" name="language" type="text" value="<?php echo $DEFAULT_LANG; ?>" />
				</div>
				<div style=''>
					Caption: <input id='caption' name='caption' style='width:300px;' type='text' />
				</div>
				<div style=''>
					Source: <input id='source' name='source' style='width:450px;' type='text' />
				</div>
				<div style=''>
					Source Url: <input id='sourceurl' name='sourceurl' style='width:450px;' type='text' />
				</div>
				<div style=''>
					Notes: <input id='notes' name='notes' style='width:450px;' type='text' />
				</div>
				<div style="float:right;">
					<input name='action' style='margin-top:5px;' type='submit' value='Add Description Block' />
					<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
					<input type="hidden" name="tabindex" value="4" />
				</div>
				<div style=''>
					Sort Order: <input name='displaylevel' style='width:40px;' type='text' />
				</div>
			</fieldset>
		</form>
	</div>
	<?php
	if($descList){
		foreach($descList as $tdbid => $dArr){
    		?>
    		<fieldset style='width:90%;margin:10px 5px 5px 5px;padding:10px;'>
				<legend><b><?php echo ($dArr["caption"]?$dArr["caption"]:"Description ".$dArr["displaylevel"]); ?></b></legend>
				<div style="float:right;" onclick="toggle('dblock-<?php echo $tdbid;?>');" title="Edit Description Block">
					<img style='border:0px;width:12px;' src='../../images/edit.png'/>
				</div>
				<?php
				if($descEditor->getTid() != $dArr['tid']){
					?>
					<div style="margin:4px 0px;">
						<b>Linked to synonym:</b> <?php echo $dArr['sciname']; ?>
						(<a href="tpeditor.php?action=remap&tdbid=<?php echo $tdbid.'&tid='.$descEditor->getTid(); ?>">relink to accepted taxon</a>)
					</div>
					<?php
				}
				?>
				<div><b>Caption:</b> <?php echo $dArr["caption"]; ?></div>
				<div><b>Source:</b> <?php echo $dArr["source"]; ?></div>
				<div><b>Source URL:</b> <a href='<?php echo $dArr["sourceurl"]; ?>'><?php echo $dArr["sourceurl"]; ?></a></div>
				<div><b>Notes:</b> <?php echo $dArr["notes"]; ?></div>
				<div id="dblock-<?php echo $tdbid;?>" style="display:none;margin-top:10px;">
					<fieldset style="padding:10px;">
						<legend><b>Description Block Edits</b></legend>
						<form id='updatedescrblock' name='updatedescrblock' action="tpeditor.php" method="post">
							<div>
								Language:
								<input name='language' type='text' value='<?php echo $dArr['language']; ?>' />
							</div>
							<div>
								Caption:
								<input id='caption' name='caption' style='width:450px;' type='text' value='<?php echo $dArr["caption"];?>' />
							</div>
							<div>
								Source:
								<input id='source' name='source' style='width:450px;' type='text' value='<?php echo $dArr["source"];?>' />
							</div>
							<div>
								Source URL:
								<input id='sourceurl' name='sourceurl' style='width:500px;' type='text' value='<?php echo $dArr["sourceurl"];?>' />
							</div>
							<div>
								Notes:
								<input name='notes' style='width:450px;' type='text' value='<?php echo $dArr["notes"];?>' />
							</div>
							<div>
								Display Level:
								<input name='displaylevel' style='width:40px;' type='text' value='<?php echo $dArr['displaylevel'];?>' />
							</div>
							<div style="margin:10px;">
								<input name="tdbid" type="hidden" value="<?php echo $tdbid;?>" />
								<input name="tid" type="hidden" value="<?php echo $descEditor->getTid();?>" />
								<input name="tabindex" type="hidden" value="4" />
								<button name="action" type="submit" value="saveDescriptionBlock">Save Edits</button>
							</div>
						</form>
						<hr/>
						<div style='margin:10px;border:2px solid red;padding:2px;'>
							<form name='delstmt' action='tpeditor.php' method='post' onsubmit="return window.confirm('Are you sure you want to delete this Description?');">
								<input type='hidden' name='tdbid' value='<?php echo $tdbid;?>' />
								<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
								<input type="hidden" name="tabindex" value="4" />
								<input type='hidden' name='action' value='Delete Description Block'>
								<input name='submitaction' value='Delete Description Block' style='margin:10px 0px 0px 20px;height:12px;' type='image' src='../../images/del.png'/>
								Delete Description Block (Including all statements below)
							</form>
						</div>
					</fieldset>
				</div>
    			<div style="margin-top:10px;">
					<fieldset style="padding:10px;">
						<legend><b>Statements</b></legend>
						<div onclick="toggle('addstmt-<?php echo $tdbid;?>');" style="float:right;" title="Add a New Statement">
							<img style='border:0px;width:15px;' src='../../images/add.png'/>
						</div>
						<div id='addstmt-<?php echo $tdbid;?>' style='display:<?php echo (isset($dArr["stmts"])?'none':'block'); ?>'>
							<form name='adddescrstmtform' action="tpeditor.php" method="post">
								<fieldset style='margin:5px 0px 0px 15px;'>
					    			<legend><b>New Description Statement</b></legend>
									<div style='margin:3px;'>
										Heading: <input name='heading' style='margin-top:5px;' type='text' />&nbsp;&nbsp;&nbsp;&nbsp;
										<input name='displayheader' type='checkbox' value='1' CHECKED /> Display Heading
									</div>
									<div style='margin:3px;'>
										<textarea name='statement'></textarea>
									</div>
									<div style='margin:3px;'>
										Sort Sequence:
										<input name='sortsequence' style='margin-top:5px;width:40px;' type='text' value='' />
									</div>
									<div style="margin:10px;">
										<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
										<input type='hidden' name='tdbid' value='<?php echo $tdbid;?>' />
										<input type="hidden" name="tabindex" value="4" />
										<input name='action' type='submit' value='Add Statement' />
									</div>
								</fieldset>
							</form>
						</div>
						<?php
						if(array_key_exists("stmts",$dArr)){
							$sArr = $dArr["stmts"];
							foreach($sArr as $tdsid => $stmtArr){
								?>
								<div style="margin-top:3px;clear:both;">
									<span onclick="toggle('edstmt-<?php echo $tdsid;?>');" title="Edit Statement"><img style='border:0px;width:12px;' src='../../images/edit.png'/></span>
									<?php
									echo ($stmtArr["heading"]?'<b>'.$stmtArr["heading"].'</b>:':'');
									echo $stmtArr["statement"];
									?>
								</div>
								<div class="edstmt-<?php echo $tdsid;?>" style="clear:both;display:none;">
									<div style='margin:5px 0px 5px 20px;border:2px solid cyan;padding:5px;'>
										<form id='updatedescr' name='updatedescr' action="tpeditor.php" method="post">
											<div>
												<b>Heading:</b> <input name='heading' style='margin:3px;' type='text' value='<?php echo $stmtArr["heading"];?>' />
												<input name='displayheader' type='checkbox' value='1' <?php echo ($stmtArr["displayheader"]?"CHECKED":"");?> /> Display Header
											</div>
											<div>
												<textarea name='statement'  style="width:99%;height:200px;margin:3px;"><?php echo $stmtArr["statement"];?></textarea>
											</div>
											<div>
												<b>Sort Sequence:</b>
												<input name='sortsequence' style='width:40px;' type='text' value='<?php echo $stmtArr["sortsequence"];?>' />
											</div>
											<div style="margin:10px;">
												<input name="tdsid" type="hidden" value="<?php echo $tdsid;?>" />
												<input name="tid" type="hidden" value="<?php echo $descEditor->getTid();?>" />
												<input name="tabindex" type="hidden" value="4" />
												<button name="action" type="submit" value="saveStatementEdit">Save Edits</button>
											</div>
										</form>
									</div>
									<div style='margin:5px 0px 5px 20px;border:2px solid red;padding:2px;'>
										<form name='delstmt' action='tpeditor.php' method='post' onsubmit="return window.confirm('Are you sure you want to delete this Description?');">
											<input type='hidden' name='tdsid' value='<?php echo $tdsid;?>' />
											<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
											<input type="hidden" name="tabindex" value="4" />
											<input type='hidden' name='action' value='Delete Statement'>
											<input name='submitaction' value='Delete Statement' style='margin:10px 0px 0px 20px;height:12px;' type='image' src='../../images/del.png'/>
											Delete Statement
										</form>
									</div>
								</div>
								<?php
							}
						}
						?>
					</fieldset>
				</div>
			</fieldset>
			<?php
		}
	}
	else{
		echo '<h2>No descriptions available.</h2>';
	}
}
?>