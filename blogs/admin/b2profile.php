<?php 
require_once (dirname(__FILE__).'/_header.php'); // this will actually load blog params for req blog

param( 'action', 'string', '' );
param( 'redirect', 'string', '' );
param( 'profile', 'string', '' );
param( 'user', 'integer', '' );

switch($action) 
{
	
	case "viewprofile":
	
		$profiledata=get_userdata($user);
		if( $_COOKIE[$cookie_user] == $profiledata["user_login"])
			header("Location: b2profile.php");
	
		$title = T_('Profile');
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
	
	
	
	default:
		/*
		 * Display urrent user's profile:
		 */	

		$title = T_('My Profile');
		require(dirname(__FILE__).'/_menutop.php');
		require(dirname(__FILE__).'/_menutop_end.php');

		?>
		
<div class="bPosts">
	<div class="bPost">
		<h2><?php echo T_('Edit your profile') ?></h2>
		<?php
			require(dirname(__FILE__).'/'.$pathadmin_out.'/_profile.php');
		?>
	</div>
</div>

<!-- ================================== START OF SIDEBAR ================================== -->

<div class="bSideBar">
	<div class="bSideItem">
	<?php
	if( $user_level > 0 ) 
	{ // If user is active:
		?>	
		<h2><?php echo T_('Bookmarklet') ?></h2>
		
		<?php
		if($is_NS4 || $is_gecko) 
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q=document.selection?document.selection.createRange().text:document.getSelection();void(window.open('<?php echo $pathserver ?>/b2bookmarklet.php?text='+escape(Q)+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
		}
		elseif ($is_winIE)
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;void(btw=window.open('<?php echo $pathserver ?>/b2bookmarklet.php?text='+escape(Q)+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));btw.focus();"><?php echo T_('b2evo bookmarklet') ?></a>
			</p>
			<?php
		}
		elseif($is_opera)
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:void(window.open('<?php echo $pathserver ?>/b2bookmarklet.php?popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
		}
		elseif($is_macIE)
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0);void(btw=window.open('<?php echo $pathserver ?>/b2bookmarklet.php?text='+escape(document.getSelection())+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));btw.focus();"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
		}


		// Sidebar:
		if ($is_gecko)
		{
			?>
			<script language="JavaScript">
				function addsidebar()
				{
					if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function"))
						window.sidebar.addPanel("<?php echo T_('Post to b2evolution') ?>","<?php echo $pathserver ?>/b2sidebar.php","");
					else
						alert("<?php echo T_('No Sidebar found! You must use Mozilla 0.9.4 or later!') ?>");
				}
			</script>
			<br />
			<h2><?php echo T_('SideBar') ?></h2>
			<p><?php printf( T_('Add the <a %s>b2evo sidebar</a> !'), 'href="#" onClick="addsidebar()"' ); ?></p>
			<?php
		}
		elseif($is_winIE || $is_macIE)
		{
			?>
			<br />
			<h2><?php echo T_('SideBar') ?></h2>
			<p><?php echo T_('Add this link to your favorites:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;void(_search=open('<?php echo $pathserver ?>/b2sidebar.php?popuptitle='+escape(document.title)+'&amp;popupurl='+escape(location.href)+'&amp;text='+escape(Q),'_search'))"><?php echo T_('b2evo sidebar') ?></a></p>
			<?php 
		}
		?>
		</div>
	<?php
	} // /user is active
?>
</div>
<div style="clear:both;"></div>
<?php
	break; // case default

} // switch($action)

require( dirname(__FILE__).'/_footer.php' ); 

?>
