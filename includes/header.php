<script>
        //if(top.frames.length!=0) top.location=self.document.location;
</script>
<table id="maintable" cellspacing="0">
        <tr id="trheader">
                <td id="header" colspan="3">
                        <div style="clear:both; width:100%; height:170px; border-bottom:1px solid #000000;">
                                <div style="float:left">
                                        <h1>Bringing Asia to Digital Life</h1>
                                        <h2>Mobilizing underrepresented Asian herbarium collections in the US to propel biodiversity discovery</h2>
                                </div>
                        </div>
                        <div id="top_navbar">
                                <div id="right_navbarlinks">
                                        <?php
                                        if($USER_DISPLAY_NAME){
                                                ?>
                                                <span style="">
                                                        Welcome <?php echo $USER_DISPLAY_NAME; ?>!
                                                </span>
                                                <span style="margin-left:5px;">
                                                        <a href="<?php echo $CLIENT_ROOT; ?>/profile/viewprofile.php">My Profile</a>
                                                </span>
                                                <span style="margin-left:5px;">
                                                        <a href="<?php echo $CLIENT_ROOT; ?>/profile/index.php?submit=logout">Logout</a>
                                                </span>
                                                <?php
                                        }
                                        else{
                                                ?>
                                                <span style="">
                                                        <a href="<?php echo $CLIENT_ROOT.'/profile/index.php?refurl='.$_SERVER['SCRIPT_NAME'].'?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES); ?>">
                                                                Log In
                                                        </a>
                                                </span>
                                                <span style="margin-left:5px;">
                                                        <a href="<?php echo $CLIENT_ROOT; ?>/profile/newprofile.php">
                                                                New Account
                                                        </a>
                                                </span>
                                                <?php
                                        }
                                        ?>
                                        <span style="margin-left:5px;margin-right:5px;">
                                                <a href='<?php echo $CLIENT_ROOT; ?>/sitemap.php'>Sitemap</a>
                                        </span>

                                </div>
                                <ul id="hor_dropdown">
                                        <li>
                                                <a href="<?php echo $CLIENT_ROOT; ?>/index.php" >Home</a>
                                        </li>
                                        <li>
                                                <a href="#" >Search</a>
                                                <ul>
                                                        <li>
                                                                <a href="<?php echo $CLIENT_ROOT; ?>/collections/index.php" >Search Collections</a>
                                                        </li>
                                                        <li>
                                                                <a href="<?php echo $CLIENT_ROOT; ?>/collections/map/index.php" target="_blank">Map Search</a>
                                                        </li>
                                                </ul>
                                        </li>
                                        <li>
                                                <a href="#" >Images</a>
                                                <ul>
                                                        <li>
                                                                <a href="<?php echo $CLIENT_ROOT; ?>/imagelib/index.php" >Image Browser</a>
                                                        </li>
                                                        <li>
                                                                <a href="<?php echo $CLIENT_ROOT; ?>/imagelib/search.php" >Search Images</a>
                                                        </li>
                                                </ul>
                                        </li>
                                        <li>
                                                <a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php" >Inventories</a>
                                                <ul>
                                                        <li>
                                                                <a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php?pid=1" >Project 1</a>
                                                        </li>
                                                        <li>
                                                                <a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php?pid=2" >Project 2</a>
                                                        </li>
                                                        <li>
                                                                <a href="<?php echo $CLIENT_ROOT; ?>/projects/index.php?pid=3" >Project 3</a>
                                                        </li>
                                                </ul>
                                        </li>
                                        <li>
                                                <a href="#" >Interactive Tools</a>
                                                <ul>
                                                        <li>
                                                                <a href="<?php echo $CLIENT_ROOT; ?>/checklists/dynamicmap.php?interface=checklist" >Dynamic Checklist</a>
                                                        </li>
                                                        <li>
                                                                <a href="<?php echo $CLIENT_ROOT; ?>/checklists/dynamicmap.php?interface=key" >Dynamic Key</a>
                                                        </li>
                                                </ul>
                                        </li>
                                </ul>
                        </div>
                </td>
        </tr>
        <tr>
                <td id='middlecenter'  colspan="3">
