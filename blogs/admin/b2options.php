<?php
$title = _('Options');
/* <Options> */

function add_magic_quotes($array) {
	foreach ($array as $k => $v) {
		if (is_array($v)) {
			$array[$k] = add_magic_quotes($v);
		} else {
			$array[$k] = addslashes($v);
		}
	}
	return $array;
} 

if (!get_magic_quotes_gpc()) {
	$HTTP_GET_VARS    = add_magic_quotes($HTTP_GET_VARS);
	$HTTP_POST_VARS   = add_magic_quotes($HTTP_POST_VARS);
	$HTTP_COOKIE_VARS = add_magic_quotes($HTTP_COOKIE_VARS);
}

$b2varstoreset = array('action','standalone');
for ($i=0; $i<count($b2varstoreset); $i += 1) {
	$b2var = $b2varstoreset[$i];
	if (!isset($$b2var)) {
		if (empty($HTTP_POST_VARS["$b2var"])) {
			if (empty($HTTP_GET_VARS["$b2var"])) {
				$$b2var = '';
			} else {
				$$b2var = $HTTP_GET_VARS["$b2var"];
			}
		} else {
			$$b2var = $HTTP_POST_VARS["$b2var"];
		}
	}
}

switch($action) {

case "update":

	$standalone = 1;
	require( dirname(__FILE__).'/b2header.php');
	if ($user_level <= 3) 
	{
		die( _('You have no right to edit the options for this blog.') );
	}

	$newposts_per_page=addslashes($HTTP_POST_VARS["newposts_per_page"]);
	$newwhat_to_show=addslashes($HTTP_POST_VARS["newwhat_to_show"]);
	$newarchive_mode=addslashes($HTTP_POST_VARS["newarchive_mode"]);
	$newtime_difference=addslashes($HTTP_POST_VARS["newtime_difference"]);
	$newautobr=addslashes($HTTP_POST_VARS["newautobr"]);
	
	$query = "UPDATE $tablesettings SET posts_per_page=$newposts_per_page, what_to_show='$newwhat_to_show', archive_mode='$newarchive_mode', time_difference=$newtime_difference, AutoBR=$newautobr WHERE ID = 1";
	mysql_query($query) or mysql_oops( $query );
	
	header ("Location: b2options.php");

break;

default:

	$standalone=0;
	require( dirname(__FILE__).'/b2header.php');
	if ($user_level <= 3) 
	{
		die( _('You have no right to edit the options for this blog.') );
	}
	?>
	
		<div class="panelblock">

			<form name="form" action="b2options.php" method="post">
			<input type="hidden" name="action" value="update" />
	
			<table width="550" cellpadding="5" cellspacing="0">
			<tr height="40">
				<td width="150" height="40"><?php echo _('Show') ?>:</td>
				<td width="350"><input type="text" name="newposts_per_page" value="<?php echo get_settings("posts_per_page") ?>" size="3">
				<select name="newwhat_to_show">
				<option value="days" <?php
				$i = $what_to_show;
				if ($i == "days")
				echo ' selected="selected"';
				?>><?php echo _('days') ?></option>
				<option value="posts" <?php
				if ($i == "posts")
				echo ' selected="selected"';
				?>><?php echo _('posts') ?></option>
				<option value="paged" <?php
				if ($i == "paged")
				echo ' selected="selected"';
				?>><?php echo _('posts paged') ?></option>
				</select>
				</td>
			</tr>
			<tr height="40">
				<td height="40"><?php echo _('Archive mode') ?>:</td>
				<td><select name="newarchive_mode">
				<?php $i = $archive_mode; ?>
				<option value="daily"<?php
				if ($i == "daily")
				echo " selected";
				echo ' selected="selected"';
				?>><?php echo _('daily') ?></option>
				<option value="weekly"<?php
				if ($i == "weekly")
				echo ' selected="selected"';
				?>><?php echo _('weekly') ?></option>
				<option value="monthly"<?php
				if ($i == "monthly")
				echo ' selected="selected"';
				?>><?php echo _('monthly') ?></option>
				<option value="postbypost"<?php
				if ($i == "postbypost")
				echo ' selected="selected"';
				?>><?php echo _('post by post') ?></option>
				</select>
			</tr>
			<tr height="40">
				<td height="40"><?php echo _('Time difference') ?>:</td>
				<td><input type="text" name="newtime_difference" value="<?php echo $time_difference ?>" size="2">
				<em> <?php echo _('if you\'re not on the timezone of your server') ?></em>
				</td>
			</tr>
			<tr height="40">
				<td height="40" width="150"><?php echo _('Auto-BR') ?>:</td>
				<td><select name="newautobr">
				<option value="1" <?php
				if ($autobr)
				echo ' selected="selected"';
				?>>on</option>
				<option value="0" <?php
				if (!$autobr)
				echo ' selected="selected"';
				?>>off</option>
				</select>
				<em><?php echo _('converts line-breaks into &lt;br /&gt; tags') ?></em>
				</td>
			</tr>
			<tr height="40">
				<td height="40">&nbsp;</td>
				<td>
				<input type="submit" name="submit" value="<?php echo _('Update') ?>" class="search">
				</td>
			</tr>
		</table>

		</form>
	</div>

<?php

break;
}

/* </Options> */
require( dirname(__FILE__).'/_footer.php' ); 

?>