<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */
require( dirname(__FILE__).'/_header.php');
$title = T_('Options');

param( 'action', 'string' );

switch($action) {

case 'update':
	if ($user_level <= 3) 
	{
		die( T_('You have no right to edit the options for this blog.') );
	}

	param( 'newposts_per_page', 'integer', true );
	param( 'newwhat_to_show', 'string', true );
	param( 'newarchive_mode', 'string', true );
	param( 'newtime_difference', 'integer', true );
	param( 'newautobr', 'integer', true );
	
	$query = "UPDATE $tablesettings SET posts_per_page=$newposts_per_page, what_to_show='$newwhat_to_show', archive_mode='$newarchive_mode', time_difference=$newtime_difference, AutoBR=$newautobr WHERE ID = 1";
	mysql_query($query) or mysql_oops( $query );
	
	header ("Location: b2options.php");

break;

default:
	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');
	if ($user_level <= 3) 
	{
		die( T_('You have no right to edit the options for this blog.') );
	}
	?>
	
		<div class="panelblock">

			<form name="form" action="b2options.php" method="post">
			<input type="hidden" name="action" value="update" />
	
			<table cellpadding="5" cellspacing="0">
			<tr height="40">
				<td width="150" height="40"><?php echo T_('Show') ?>:</td>
				<td width="350"><input type="text" name="newposts_per_page" value="<?php echo $posts_per_page; ?>" size="3">
				<select name="newwhat_to_show">
				<option value="days" <?php
				$i = $what_to_show;
				if ($i == "days")
				echo ' selected="selected"';
				?>><?php echo T_('days') ?></option>
				<option value="posts" <?php
				if ($i == "posts")
				echo ' selected="selected"';
				?>><?php echo T_('posts') ?></option>
				<option value="paged" <?php
				if ($i == "paged")
				echo ' selected="selected"';
				?>><?php echo T_('posts paged') ?></option>
				</select>
				</td>
			</tr>
			<tr height="40">
				<td height="40"><?php echo T_('Archive mode') ?>:</td>
				<td><select name="newarchive_mode">
				<?php $i = $archive_mode; ?>
				<option value="daily"<?php
				if ($i == "daily")
				echo " selected";
				echo ' selected="selected"';
				?>><?php echo T_('daily') ?></option>
				<option value="weekly"<?php
				if ($i == "weekly")
				echo ' selected="selected"';
				?>><?php echo T_('weekly') ?></option>
				<option value="monthly"<?php
				if ($i == "monthly")
				echo ' selected="selected"';
				?>><?php echo T_('monthly') ?></option>
				<option value="postbypost"<?php
				if ($i == "postbypost")
				echo ' selected="selected"';
				?>><?php echo T_('post by post') ?></option>
				</select>
			</tr>
			<tr height="40">
				<td height="40"><?php echo T_('Time difference') ?>:</td>
				<td><input type="text" name="newtime_difference" value="<?php echo $time_difference ?>" size="2">
				<em> <?php echo T_('if you\'re not on the timezone of your server') ?></em>
				</td>
			</tr>
			<tr height="40">
				<td height="40" width="150"><?php echo T_('Auto-BR') ?>:</td>
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
				<em><?php echo T_('converts line-breaks into &lt;br /&gt; tags') ?></em>
				</td>
			</tr>
			<tr height="40">
				<td height="40">&nbsp;</td>
				<td>
				<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search">
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