<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
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
	 * TODO: use escaped user functions
	 */
	param( 'user', 'integer' );
	$profiledata=get_userdata($user);

	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');
	?>
	<div class="panelblock">
	<h2><?php echo T_('Profile for:'), ' ', $profiledata["user_nickname"] ?></h2>

	<table width="100%">
	<tr><td width="250">

	<table cellpadding="5" cellspacing="0">
	<tr>
	<td align="right"><strong><?php echo T_('Login:') ?></strong></td>
	<td><?php echo $profiledata["user_login"] ?></td>
	</tr>
	<tr>
	<td align="right"><strong><?php echo T_('First name') ?></strong></td>
	<td><?php echo $profiledata["user_firstname"] ?></td>
	</tr>
	<tr>
	<td align="right"><strong><?php echo T_('Last name') ?></strong></td>
	<td><?php echo $profiledata["user_lastname"] ?></td>
	</tr>
	<tr>
	<td align="right"><strong><?php echo T_('Nickname') ?></strong></td>
	<td><?php echo $profiledata["user_nickname"] ?></td>
	</tr>
	<tr>
	<td align="right"><strong><?php echo T_('Email') ?></strong></td>
	<td><?php echo make_clickable($profiledata["user_email"]) ?></td>
	</tr>
	<tr>
	<td align="right"><strong><?php echo T_('URL') ?></strong></td>
	<td><?php echo $profiledata["user_url"] ?></td>
	</tr>
	<tr>
	<td align="right"><strong><?php echo T_('ICQ') ?></strong></td>
	<td><?php if ($profiledata["user_icq"] > 0) { echo make_clickable("icq:".$profiledata["user_icq"]); } ?></td>
	</tr>
	<tr>
	<td align="right"><strong><?php echo T_('AIM') ?></strong></td>
	<td><?php echo make_clickable("aim:".$profiledata["user_aim"]) ?></td>
	</tr>
	<tr>
	<td align="right"><strong><?php echo T_('MSN IM') ?></strong></td>
	<td><?php echo $profiledata["user_msn"] ?></td>
	</tr>
	<tr>
	<td align="right"><strong><?php echo T_('YahooIM') ?></strong></td>
	<td><?php echo $profiledata["user_yim"] ?></td>
	</tr>
	</table>

	</td>
	<td valign="top">

	<table cellpadding="5" cellspacing="0">
	<tr>
	<td>
	<strong><?php echo T_('ID') ?>:</strong> <?php echo $profiledata["ID"] ?></td>
	</tr>
	<tr>
	<td>
	<strong><?php echo T_('Level') ?>:</strong> <?php echo $profiledata["user_level"] ?>
	</td>
	</tr>
	<tr>
	<td>
	<strong><?php echo T_('Posts') ?>:</strong>
	<?php
	$posts=get_usernumposts($user);
	echo $posts;
	?>
	</td>
	</tr>
	<tr>
	<td>
	<strong><?php echo T_('Identity') ?>:</strong><br />
	<?php
	switch($profiledata["user_idmode"]) {
		case "nickname":
			$r=$profiledata["user_nickname"];
			break;
		case "login":
			$r=$profiledata["user_login"];
			break;
		case "firstname":
			$r=$profiledata["user_firstname"];
			break;
		case "lastname":
			$r=$profiledata["user_lastname"];
			break;
		case "namefl":
			$r=$profiledata["user_firstname"]." ".$profiledata["user_lastname"];
			break;
		case "namelf":
			$r=$profiledata["user_lastname"]." ".$profiledata["user_firstname"];
			break;
	}
	echo $r;
	?>
	</td>
	</tr>
	</table>

	</td>
	</table>
	</div>
	<?php
	
	break;
	
	
case "promote":
	param( 'prom', 'string' );
	param( 'id', 'integer' );
	
	if (empty($prom))
	{
		header("Location: b2team.php");
	}

	$user_data=get_userdata($id);
	$usertopromote_level=$user_data[13];

	if ($user_level <= $usertopromote_level) {
		die(T_('Can\'t change the level of an user whose level is higher than yours.'));
	}

	if ($prom == "up") {
		$sql="UPDATE $tableusers SET user_level=user_level+1 WHERE ID = $id";
	} elseif ($prom == "down") {
		$sql="UPDATE $tableusers SET user_level=user_level-1 WHERE ID = $id";
	}
	$result=mysql_query($sql) or die("Couldn't change $id's level.");

	header("Location: b2team.php");
	exit();
	break;

case "delete":
	param( 'id', 'integer' );
	if (!$id) {
		header("Location: b2team.php");
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

	header("Location: b2team.php");
	exit();
	break;


default:
	require( dirname(__FILE__).'/_menutop.php');
	require( dirname(__FILE__).'/_menutop_end.php');
	?>
	<div class="panelblock">
		<?php echo T_('Click on an user\'s login name to see his/her complete Profile.') ?><br />
		<?php echo T_('To edit your Profile, click on your login name.') ?>
	</div>
	<?php
}
?>

<div class="panelblock">
	<h2><?php echo T_('Active users') ?></h2>
	<?php
	$request = " SELECT * FROM $tableusers WHERE user_level>0 ORDER BY ID";
	$querycount++; 
	$result = mysql_query($request);
	require dirname(__FILE__).'/_user_list.php';	
	?>
</div>

<div class="panelblock">
	<h2><?php echo T_('Inactive users (level 0)') ?></h2>
	<?php
	$request = " SELECT * FROM $tableusers WHERE user_level=0 ORDER BY ID";
	$querycount++; 
	$result = mysql_query($request);
	require dirname(__FILE__).'/_user_list.php';	

	if (mysql_num_rows($result)) {
	?>
</div>

<?php 
}
if ($user_level >= 3) 
{ ?>
	<div class="panelblock">
		<?php echo T_('To delete an user, bring his/her level to zero, then click on the red cross.') ?><br />
		<strong><?php echo T_('Warning') ?>:</strong> <?php echo T_('deleting an user also deletes all posts made by this user.') ?>
	</div>
<?php
}
require( dirname(__FILE__).'/_footer.php' ); 
?>