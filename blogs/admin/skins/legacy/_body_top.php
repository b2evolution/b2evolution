<?php
/**
 * This is the admin page body top...
 */
if( empty($mode) )
{ // We're not running in an special mode (bookmarklet, sidebar...)

	// GLOBAL HEADER - APP TITLE, LOGOUT, ETC. :
	?>
	<div id="header">
		<a href="http://b2evolution.net/" title="<?php echo T_("visit b2evolution's website") ?>"><img id="evologo" src="../img/b2evolution_minilogo2.png" alt="b2evolution"  title="<?php echo T_("visit b2evolution's website") ?>" width="185" height="40" /></a>
	
		<div id="headfunctions">
			<?php echo T_('Style:') ?>
			<a href="#" onclick="setActiveStyleSheet('Variation'); return false;" title="Variation (Default)">V</a>&middot;<a href="#" onclick="setActiveStyleSheet('Desert'); return false;" title="Desert">D</a>&middot;<a href="#" onclick="setActiveStyleSheet('Legacy'); return false;" title="Legacy">L</a><?php if( is_file( dirname(__FILE__).'/custom.css' ) ) { ?>&middot;<a href="#" onclick="setActiveStyleSheet('Custom'); return false;" title="Custom">C</a><?php } ?>
			&bull;
			<a href="<?php echo $htsrv_url ?>login.php?action=logout"><?php echo T_('Logout') ?></a>
			&bull;
			<a href="<?php echo $baseurl ?>"><?php echo T_('Exit to blogs') ?> <img src="img/close.gif" width="14" height="14" class="top" alt="" title="<?php echo T_('Exit to blogs') ?>" /></a><br />
		</div>
	
		<?php
		if( !$obhandler_debug )
		{ // don't display changing time when we want to test obhandler
		?>
		<div id="headinfo">
			b2evo v <strong><?php echo $app_version ?></strong>
			&middot; <?php echo T_('Blog time:') ?> <strong><?php echo date_i18n( locale_timefmt(), $localtimenow ) ?></strong>
			&middot; <?php echo T_('GMT:') ?> <strong><?php echo gmdate( locale_timefmt(), $servertimenow); ?></strong>
			&middot; <?php echo T_('Logged in as:'), ' <strong>', $user_login; ?></strong>
		</div>
		<?php } ?>
	
		<ul class="tabs">
		<?php 	// GLOBAL MENU :
			foreach( $menu as $loop_tab => $loop_details )
			{
				$perm = true; // By default
				if( (!isset($loop_details['perm_name']))
					|| ($perm = $current_User->check_perm( $loop_details['perm_name'], $loop_details['perm_level'] ) )
					|| isset($loop_details['text_noperm']) )
				{ // If no permission requested or if perm granted or if we have an alt text, display tab:
	
					echo (($loop_tab == $admin_tab) ? '<li class="current">' : '<li>');
	
					echo '<a href="'.$loop_details['href'].'"';
	
					if( isset($loop_details['style']) ) echo ' style="'.$loop_details['style'].'"';
	
					echo '>';
	
					echo ($perm ? $loop_details['text'] : $loop_details['text_noperm'] );
	
					echo '</a></li>';
				}
			}
		?>
		</ul>
	</div>
	<?php
} // not in special mode
?>

<div id="TitleArea">
	<h1>
	<strong>
		<?php // CURRENT PAGE TITLE / PATH:
			echo $admin_path_seprator;
			if( isset( $admin_pagetitle_titlearea ) )
			{
				echo $admin_pagetitle_titlearea;
			}
			else
			{
				echo $admin_pagetitle;
			}
		?>
	</strong>

	<?php // OPTIONAL BLOG SELECTION...
		if( isset($blogListButtons) )
		{	// We have blog selection buttons to display:
			echo $blogListButtons;
		}
	?>
	</h1>
</div>

<div class="panelbody">