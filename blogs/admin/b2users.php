<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */
require_once (dirname(__FILE__).'/_header.php');
$title = T_('User management');

param( 'action', 'string' );

switch ($action) 
{
case 'view':
	/* 
	 * View user:
	 */
	param( 'user', 'integer', true );
	$edited_User = new User( get_userdata($user) );

	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');
	?>
	<div class="panelblock">
	<h2><?php echo T_('Profile for:'), ' ', $edited_User->disp('nickname') ?></h2>

<form class="fform" method="post" action="b2users.php">
	<input type="hidden" name="action" value="userupdate" />
	<input type="hidden" name="edited_user_ID" value="<?php $edited_User->disp('ID','formvalue') ?>" />
	<table>
	<tr>
	<td>
		<table cellpadding="5" cellspacing="0">
		<tr>
		<td align="right"><strong><?php echo T_('Login:') ?></strong></td>
		<td><?php $edited_User->disp('login'); ?></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('First name') ?>:</strong></td>
		<td><?php $edited_User->disp('firstname') ?></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('Last name') ?>:</strong></td>
		<td><?php $edited_User->disp('lastname') ?></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('Nickname') ?>:</strong></td>
		<td><?php $edited_User->disp('nickname') ?></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('Email') ?>:</strong></td>
		<td><?php echo make_clickable($edited_User->disp('email')) ?></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('URL') ?>:</strong></td>
		<td><?php $edited_User->disp('url') ?></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('ICQ') ?>:</strong></td>
		<td><?php if ($edited_User->disp('icq') > 0) { echo make_clickable("icq:".$edited_User->disp('icq')); } ?></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('AIM') ?>:</strong></td>
		<td><?php echo make_clickable("aim:".$edited_User->disp('aim')) ?></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('MSN IM') ?>:</strong></td>
		<td><?php $edited_User->disp('msn') ?></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('YahooIM') ?>:</strong></td>
		<td><?php $edited_User->disp('yim') ?></td>
		</tr>
		</table>

	</td>
	<td valign="top">

		<p><strong><?php echo T_('ID') ?>:</strong> <?php $edited_User->disp('ID')  ?></p>

		<p><strong><?php echo T_('Posts') ?>:</strong>	<?php $posts=get_usernumposts($user); ?></p>

		<p><strong><?php echo T_('Identity') ?>:</strong>	<?php $edited_User->disp('preferedname'); ?></p>

		<p><strong><?php echo T_('Created on:') ?></strong>	<?php $edited_User->disp('datecreated'); ?></p>

		<p><strong><?php echo T_('From IP:') ?></strong>	<?php $edited_User->disp('ip'); ?></p>

		<p><strong><?php echo T_('From Domain:') ?></strong>	<?php $edited_User->disp('domain'); ?></p>

		<p><strong><?php echo T_('With Browser:') ?></strong>	<?php $edited_User->disp('browser'); ?></p>

	</td>
	</table>

	<fieldset>
		<legend><?php echo T_('User rights') ?></legend>
		<p><strong><?php echo T_('Level') ?>:</strong> <?php $edited_User->disp('level') ?></p>

		<?php form_select( 'edited_user_grp_ID', $edited_User->Group->get('ID'), 'groups_options', T_('User group') );?>

	</fieldset>

	<fieldset>
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search">
				<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
			</div>
		</fieldset>
	</fieldset>

	<div class="clear"></div>
</form>
	</div>
	<?php
	
	break;
	
case 'userupdate':
	/* 
	 * Update user:
	 */
	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');

	param( 'edited_user_ID', 'integer' );
	$edited_User = new User( get_userdata( $edited_user_ID ) );

	param( 'edited_user_grp_ID', 'integer', true );
	$edited_user_Group = Group_get_by_ID( $edited_user_grp_ID );
	$edited_User->setGroup( $edited_user_Group );
	// echo 'new group = ';
	// $edited_User->Group->disp('name');
	
	// Peform update to the DB:
	$edited_User->dbupdate();
	
	header("Location: b2users.php");
	exit();

	
case "promote":
	param( 'prom', 'string' );
	param( 'id', 'integer' );
	
	if (empty($prom))
	{
		header("Location: b2users.php");
	}

	$user_data=get_userdata($id);
	$usertopromote_level = get_user_info( 'level', $user_data );
	
	if ($user_level <= $usertopromote_level) 
	{
		die(T_('Can\'t change the level of an user whose level is higher than yours.'));
	}

	if ($prom == "up") 
	{
		$sql="UPDATE $tableusers SET user_level=user_level+1 WHERE ID = $id";
	}
	elseif ($prom == "down") 
	{
		$sql="UPDATE $tableusers SET user_level=user_level-1 WHERE ID = $id";
	}
	$result=mysql_query($sql) or die("Couldn't change $id's level.");

	header("Location: b2users.php");
	exit();

case "delete":
	param( 'id', 'integer' );
	if (!$id) {
		header("Location: b2users.php");
	}

	$user_data=get_userdata($id);
	$usertodelete_level=$user_data[13];

	if ($user_level <= $usertodelete_level)
	die(T_('Can\'t delete an user whose level is higher than yours.'));

	$sql="DELETE FROM $tableusers WHERE ID = $id";
	$result=mysql_query($sql) or die(sprintf( T_('Couldn\'t delete user #%d.'), $id ));

	// TODO: MORE DB STUFF:
	$sql="DELETE FROM $tableposts WHERE post_author = $id";
	$result=mysql_query($sql) or die( sprintf( T_('Couldn\'t delete user #%d\'s posts.'), $id ) );

	header("Location: b2users.php");
	exit();
	break;


default:
	require( dirname(__FILE__).'/_menutop.php');
	require( dirname(__FILE__).'/_menutop_end.php');
}

// Display user list:
require dirname(__FILE__).'/_user_list.php';	
if ($user_level >= 3) 
{ ?>
	<div class="panelblock">
		<?php	
		echo '<p>[<a href="', $htsrv_url, '/register.php?redirect_to=', $admin_url, '/b2users.php">', T_('Register a new user...'), '</a>]</p>'; ?>

		<p><?php echo T_('To delete an user, bring his/her level to zero, then click on the red cross.') ?><br />
		<strong><?php echo T_('Warning') ?>:</strong> <?php echo T_('deleting an user also deletes all posts made by this user.') ?></p>
	</div>
<?php
}
require( dirname(__FILE__).'/_footer.php' ); 
?>