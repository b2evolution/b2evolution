<?php 
require_once (dirname(__FILE__).'/_header.php'); // this will actually load blog params for req blog
$title = T_('Profile');

function add_magic_quotes($array)
{
	foreach ($array as $k => $v)
	{
		if (is_array($v))
		{
			$array[$k] = add_magic_quotes($v);
		}
		else
		{
			$array[$k] = addslashes($v);
		}
	}
	return $array;
}

if (!get_magic_quotes_gpc())
{
	$_GET    = add_magic_quotes($_GET);
	$_POST   = add_magic_quotes($_POST);
	$HTTP_COOKIE_VARS = add_magic_quotes($HTTP_COOKIE_VARS);
}

$b2varstoreset = array('action','standalone','redirect','profile','user');

for ($i=0; $i<count($b2varstoreset); $i++)
{
	$b2var = $b2varstoreset[$i];
	if (!isset($$b2var))
	{
		if (empty($_POST['$b2var']))
		{
			if (empty($_GET['$b2var']))
			{
				$$b2var = '';
			}
			else
			{
				$$b2var = $_GET['$b2var'];
			}
		} else {
			$$b2var = $_POST['$b2var'];
		}
	}
}

switch($action) 
{
	
	case 'update':
	
		get_currentuserinfo();
		
		if( !isset($demo_mode) )
		{
			$demo_mode = 0;
		}
		if( $demo_mode && ($user_login == 'demouser'))
		{
			die( 'Demo mode: you can\'t edit the demouser profile!'.'<br />[<a href="javascript:history.go(-1)">'. T_('Back to profile'). '</a>]' );
		}

		/* checking the nickname has been typed */
		if (empty($_POST['newuser_nickname']))
		{
			die ('<strong>'.T_('ERROR').'</strong>: '.T_('please enter your nickname (can be the same as your login)').'<br />[<a href="javascript:history.go(-1)">'. T_('Back to profile'). '</a>]' );
			return false;
		}
	
		/* if the ICQ UIN has been entered, check to see if it has only numbers */
		if (!empty($_POST['newuser_icq']))
		{
			if (!ereg("^[0-9]+$", $_POST["newuser_icq"])) {
				die ('<strong>'. T_('ERROR'). '</strong>: '. T_('your ICQ UIN can only be a number, no letters allowed').'<br />[<a href="javascript:history.go(-1)">'. T_('Back to profile'). '</a>]' );
				return false;
			}
		}
	
		/* checking e-mail address */
		if (empty($_POST["newuser_email"]))
		{
			die ('<strong>'. T_('ERROR'). '</strong>: '. T_('please type your e-mail address').'<br />[<a href="javascript:history.go(-1)">'. T_('Back to profile'). '</a>]' );
			return false;
		}
		elseif (!is_email($_POST['newuser_email']))
		{
			die ('<strong>'. T_('ERROR'). '</strong>: '. T_('the email address isn\'t correct').'<br />[<a href="javascript:history.go(-1)">'. T_('Back to profile'). '</a>]' );
			return false;
		}
	
		if ($_POST['pass1'] == '')
		{
			if ($_VARS['pass2'] != '')
			{
				die ('<strong>'. T_('ERROR'). '</strong>: '. T_('you typed your new password only once. Go back to type it twice.'));
			}
			$updatepassword = '';
		}
		else
		{
			if ($_POST['pass2'] == '')
			{
				die ('<strong>'. T_('ERROR'). '</strong>: '. T_('you typed your new password only once. Go back to type it twice.') );
			}
			if ($_POST['pass1'] != $_POST['pass2'])
			{
				die ('<strong>'. T_('ERROR'). '</strong>: '. T_('you typed two different passwords. Go back to correct that.') );
			}
			$newuser_pass = md5($_POST['pass1']);
			$updatepassword = "user_pass = '$newuser_pass', ";
			if( !setcookie( $cookie_pass, $newuser_pass, $cookie_expires, $cookie_path, $cookie_domain) )
			{
				printf( T_('setcookie %s failed!'), $cookie_pass );
			}

			echo '<br />';
		}

      	$newuser_firstname = addslashes($_POST['newuser_firstname']);
		$newuser_lastname  = addslashes($_POST['newuser_lastname']);
		$newuser_nickname  = addslashes($_POST['newuser_nickname']);
		$newuser_icq       = addslashes($_POST['newuser_icq']);
		$newuser_aim       = addslashes($_POST['newuser_aim']);
		$newuser_msn       = addslashes($_POST['newuser_msn']);
		$newuser_yim       = addslashes($_POST['newuser_yim']);
		$newuser_email     = addslashes($_POST['newuser_email']);
		$newuser_url       = addslashes($_POST['newuser_url']);
		$newuser_idmode    = addslashes($_POST['newuser_idmode']);

		$query = "UPDATE $tableusers SET user_firstname = '$newuser_firstname', ".$updatepassword."user_lastname='$newuser_lastname', user_nickname='$newuser_nickname', user_icq='$newuser_icq', user_email='$newuser_email', user_url='$newuser_url', user_aim='$newuser_aim', user_msn='$newuser_msn', user_yim='$newuser_yim', user_idmode='$newuser_idmode' WHERE ID = $user_ID";
		$result = mysql_query($query) or mysql_oops( $query );
	
		?>
		<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
		<body onload="window.close();">
			<?php echo T_('Profile updated!') ?><br />
			<?php echo T_('If that window doesn\'t close itself, close it yourself :p') ?>
		</body>
		</html>
		<?php
	
	break; // case 'update'
	
	
	case "viewprofile":
	
		$profiledata=get_userdata($user);
		if( $_COOKIE[$cookie_user] == $profiledata["user_login"])
			header("Location: b2profile.php");
	
		$profile=1;
		require(dirname(__FILE__).'/_menutop.php');
		require(dirname(__FILE__).'/_menutop_end.php');
		?>
	
		<div class="menutop" align="center">
		<?php echo $profiledata["user_login"] ?>
		</div>
	
		<form name="form" action="b2profile.php" method="post">
		<input type="hidden" name="action" value="update" />
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
	
		</form>
		<?php
	
	break; // case 'viewprofile'
	
	
	case 'IErightclick':
	
		$profile = 1;
		require(dirname(__FILE__).'/_menutop.php');
		require(dirname(__FILE__).'/_menutop_end.php');
	
		$bookmarklet_tbpb  = ($use_trackback) ? '&trackback=1' : '';
		$bookmarklet_tbpb .= ($use_pingback)  ? '&pingback=1'  : '';
		$bookmarklet_height= ($use_trackback) ? 340 : 300;
	
		?>
	
		<div class="menutop">&nbsp;<?php echo T_('IE one-click bookmarklet') ?></div>
	
		<table width="100%" cellpadding="20">
		<tr><td>
	
		<p><?php echo T_('To have a one-click bookmarklet, just copy and paste this into a new text file:') ?></p>
		<?php
		$regedit = "REGEDIT4\r\n[HKEY_CURRENT_USER\Software\Microsoft\Internet Explorer\MenuExt\Post To &b2 : ".$blogname."]\r\n@=\"javascript:doc=external.menuArguments.document;Q=doc.selection.createRange().text;void(btw=window.open('".$pathserver."/b2bookmarklet.php?text='+escape(Q)+'".$bookmarklet_tbpb."&popupurl='+escape(doc.location.href)+'&popuptitle='+escape(doc.title),'b2bookmarklet','scrollbars=yes,resizable=yes,width=600,height=".$bookmarklet_height.",left=100,top=150,status=yes'));btw.focus();\"\r\n\"contexts\"=hex:31\"";
		?>
		<pre style="margin: 20px; background-color: #cccccc; border: 1px dashed #333333; padding: 5px; font-size: 12px;"><?php echo $regedit; ?></pre>
		<p><?php echo T_('Save it as b2.reg, and double-click on this file in an Explorer window. Answer Yes to the question, and restart Internet Explorer.') ?></p>
		<p><?php echo T_('That\'s it, you can now right-click in an IE window and select \'Post to b2\' to make the bookmarklet appear :)') ?></p>
	
		<p align="center">
			<form>
			<input class="search" type="button" value="1" name="<?php echo T_('Close this window') ?>" />
			</form>
		</p>
		</td></tr>
		</table>
		<?php
	
	break; // case 'IErightclick'
	
	
	default:
	
		$profile=1;
		require(dirname(__FILE__).'/_menutop.php');
		require(dirname(__FILE__).'/_menutop_end.php');
		$profiledata=get_userdata($user_ID);
	
		$bookmarklet_tbpb  = ($use_trackback) ? '&trackback=1' : '';
		$bookmarklet_tbpb .= ($use_pingback)  ? '&pingback=1'  : '';
		$bookmarklet_height= 450;
	
		?>
	
		<form name="form" action="b2profile.php" method="post">
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="checkuser_id" value="<?php echo $user_ID ?>" />
		<table width="100%">
		<td width="200" valign="top">
	
		<table cellpadding="5" cellspacing="0">
		<tr>
		<td align="right"><strong><?php echo T_('Login:') ?></strong></td>
		<td><?php echo $profiledata["user_login"] ?></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('First name') ?>:</strong></td>
		<td><input type="text" name="newuser_firstname" value="<?php echo $profiledata["user_firstname"] ?>" class="postform" /></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('Last name') ?>:</strong></td>
		<td><input type="text" name="newuser_lastname" value="<?php echo $profiledata["user_lastname"] ?>" class="postform" /></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('Nickname') ?>:</strong></td>
		<td><input type="text" name="newuser_nickname" value="<?php echo $profiledata["user_nickname"] ?>" class="postform" /></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('Email') ?>:</strong></td>
		<td><input type="text" name="newuser_email" value="<?php echo $profiledata["user_email"] ?>" class="postform" /></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('URL') ?>:</strong></td>
		<td><input type="text" name="newuser_url" value="<?php echo $profiledata["user_url"] ?>" class="postform" /></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('ICQ') ?>:</strong></td>
		<td><input type="text" name="newuser_icq" value="<?php if ($profiledata["user_icq"] > 0) { echo $profiledata["user_icq"]; } ?>" class="postform" /></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('AIM') ?>:</strong></td>
		<td><input type="text" name="newuser_aim" value="<?php echo $profiledata["user_aim"] ?>" class="postform" /></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('MSN IM') ?>:</strong></td>
		<td><input type="text" name="newuser_msn" value="<?php echo $profiledata["user_msn"] ?>" class="postform" /></td>
		</tr>
		<tr>
		<td align="right"><strong><?php echo T_('YahooIM') ?>:</strong></td>
		<td><input type="text" name="newuser_yim" value="<?php echo $profiledata["user_yim"] ?>" class="postform" /></td>
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
		$posts=get_usernumposts($user_ID);
		echo $posts;
		?>
		</td>
		</tr>
		<tr>
		<td>
		<?php echo T_('<strong>Identity</strong> on the blog') ?>:<br />
		<select name="newuser_idmode" class="postform">
		<option value="nickname"<?php
		if ($profiledata["user_idmode"]=="nickname")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_nickname"] ?></option>
		<option value="login"<?php
		if ($profiledata["user_idmode"]=="login")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_login"] ?></option>
		<option value="firstname"<?php
		if ($profiledata["user_idmode"]=="firstname")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_firstname"] ?></option>
		<option value="lastname"<?php
		if ($profiledata["user_idmode"]=="lastname")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_lastname"] ?></option>
		<option value="namefl"<?php
		if ($profiledata["user_idmode"]=="namefl")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_firstname"]." ".$profiledata["user_lastname"] ?></option>
		<option value="namelf"<?php
		if ($profiledata["user_idmode"]=="namelf")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_lastname"]." ".$profiledata["user_firstname"] ?></option>
		</select>
		</td>
		</tr>
		<tr>
		<td>
		<br />
		<?php echo T_('New <strong>password</strong> (twice):') ?><br />
		<input type="password" name="pass1" size="16" value="" class="postform" /><br />
		<input type="password" name="pass2" size="16" value="" class="postform" />
		</td>
		</tr>
	<?php
	if($user_level > 0) {
	?>	<tr>
	<td><br /><strong><?php echo T_('Bookmarklet') ?></strong><br />
	<?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
	<?php
	if($is_NS4 || $is_gecko) {
	?>
	<a href="javascript:Q=document.selection?document.selection.createRange().text:document.getSelection();void(window.open('<?php echo $pathserver ?>/b2bookmarklet.php?text='+escape(Q)+'<?php echo $bookmarklet_tbpb ?>&popupurl='+escape(location.href)+'&popuptitle='+escape(document.title),'b2 bookmarklet','scrollbars=yes,resizable=yes,width=600,height=<?php echo $bookmarklet_height ?>,left=100,top=150,status=yes'));"><?php echo T_('b2 - bookmarklet') ?></a>
	<?php
	}
	elseif ($is_winIE)
	{
	?>
	<a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;void(btw=window.open('<?php echo $pathserver ?>/b2bookmarklet.php?text='+escape(Q)+'<?php echo $bookmarklet_tbpb ?>&popupurl='+escape(location.href)+'&popuptitle='+escape(document.title),'b2bookmarklet','scrollbars=yes,resizable=yes,width=600,height=<?php echo $bookmarklet_height ?>,left=100,top=150,status=yes'));btw.focus();"><?php echo T_('b2 - bookmarklet') ?></a>
	
	<script type="text/javascript" language="javascript">
	<!--
	function oneclickbookmarklet(blah) {
		window.open ("b2profile.php?action=IErightclick", "oneclickbookmarklet", "width=600, height=450, location=0, menubar=0, resizable=1, scrollbars=1, status=1, titlebar=0, toolbar=0, screenX=120, left=120, screenY=120, top=120");
	}
	// -->
	</script>
	
	<br /><br />
	<?php echo T_('One-click bookmarklet:') ?><br />
	<a href="javascript:oneclickbookmarklet(0);"><?php echo T_('Click here') ?></a>
	
	<?php
	}
	elseif($is_opera)
	{
	?>
	<a href="javascript:void(window.open('<?php echo $pathserver ?>/b2bookmarklet.php?popupurl='+escape(location.href)+'&popuptitle='+escape(document.title)+'<?php echo $bookmarklet_tbpb ?>','b2bookmarklet','scrollbars=yes,resizable=yes,width=600,height=<?php echo $bookmarklet_height ?>,left=100,top=150,status=yes'));"><?php echo T_('b2 - bookmarklet') ?></a>
	<?php
		}
		elseif($is_macIE)
		{
	?>
	<a href="javascript:Q='';if(top.frames.length==0);void(btw=window.open('<?php echo $pathserver ?>/b2bookmarklet.php?text='+escape(document.getSelection())+'&popupurl='+escape(location.href)+'&popuptitle='+escape(document.title)+'<?php echo $bookmarklet_tbpb ?>','b2bookmarklet','scrollbars=yes,resizable=yes,width=600,height=<?php echo $bookmarklet_height ?>,left=100,top=150,status=yes'));btw.focus();"><?php echo T_('b2 - bookmarklet') ?></a> <?php
		}
	?>
<?php
		if ($is_gecko)
		{
?>
	<br /><br />
	<script language="JavaScript">
		function addPanel()
		{
			if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function"))
				window.sidebar.addPanel("b2 post","<?php echo $pathserver ?>/b2sidebar.php","");
			else
				alert("<?php echo T_('No Sidebar found!  You must use Mozilla 0.9.4 or later!') ?>");
		}
	</script>
	<strong><?php echo T_('SideBar') ?></strong><br />
	<?php printf( T_('Add the <a %s>b2 Sidebar</a> !'), 'href="#" onClick="addPanel()"' ); ?>
<?php
			}
			elseif($is_winIE || $is_macIE)
			{
?>
	<br /><br />
	<strong><?php echo T_('SideBar') ?></strong><br />
	<?php echo T_('Add this link to your favorites:') ?><br /><a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;void(_search=open('<?php echo $pathserver ?>/b2sidebar.php?text='+escape(Q)+'&popupurl='+escape(location.href)+'&popuptitle='+escape(document.title),'_search'))"><?php echo T_('b2 Sidebar') ?></a>.
<?php
		}
?>
		</td>
		</tr>
<?php
		}
?>	</table>
		</td></tr>
	<tr>
		<td colspan="2" align="center"><br /><input class="search" type="submit" value="Update" name="submit"><br /><?php echo T_('Note: closes the popup window.') ?></td>
		</tr>
		</table>
	
		</form>
		<?php
	
	break; // case default
} // switch($action)

/* </Profile | My Profile> */
require( dirname(__FILE__).'/_footer.php' ); 

?>
