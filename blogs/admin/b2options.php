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
	// Check permission:
	$current_User->check_perm( 'options', 'edit', true );

	param( 'newposts_per_page', 'integer', true );
	param( 'newwhat_to_show', 'string', true );
	param( 'newarchive_mode', 'string', true );
	param( 'newtime_difference', 'integer', true );
	param( 'newautobr', 'integer', true );
	param( 'pref_newusers_grp_ID', 'integer', true );
	
	$query = "UPDATE $tablesettings SET posts_per_page=$newposts_per_page, what_to_show='$newwhat_to_show', archive_mode='$newarchive_mode', time_difference=$newtime_difference, AutoBR=$newautobr, pref_newusers_grp_ID = $pref_newusers_grp_ID";
	mysql_query($query) or mysql_oops( $query );
	
	header ("Location: b2options.php");

break;

default:
	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');

	// Check permission:
	$current_User->check_perm( 'options', 'view', true );
	?>
	
		<div class="panelblock">

			<form class="fform" name="form" action="b2options.php" method="post">
			<input type="hidden" name="action" value="update" />

	<fieldset>
		<legend><?php echo T_('User rights') ?></legend>
	
		<?php form_select( 'pref_newusers_grp_ID', get_settings('pref_newusers_grp_ID'), 'groups_options', T_('New user\'s group'), T_('New users will be created in this group.') );?>

	</fieldset>

	<fieldset>
		<legend><?php echo T_('Regional settings') ?></legend>
	
		<?php form_text( 'newtime_difference', $time_difference, 3, T_('Time difference'), sprintf( T_('If you\'re not on the timezone of your server. Current server time is: %s.'), date_i18n( locale_timefmt(), $servertimenow ) ) );?>

	</fieldset>
	
	<fieldset>
		<legend><?php echo T_('Post options') ?></legend>
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
			</table>
	</fieldset>

	<?php if( $current_User->check_perm( 'options', 'edit' ) ) 
	{ ?>
	<fieldset>
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search">
				<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
			</div>
		</fieldset>
	</fieldset>
	<?php } ?>

		</form>
	</div>

<?php

break;
}

/* </Options> */
require( dirname(__FILE__).'/_footer.php' ); 

?>