<?php
/**
 * This file implements the body top template
 *
 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package admin-skin
 * @subpackage evo
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( empty($mode) )
{ // We're not running in an special mode (bookmarklet, sidebar...)
	?>
	<div id="header">
		<?php echo $app_admin_logo; ?>
	
		<div id="headinfo">
	
		<span id="headfunctions">
			<a href="<?php echo $htsrv_url ?>login.php?action=logout"><?php echo T_('Logout') ?></a>
			&bull;
			<a href="<?php echo $baseurl ?>"><?php echo T_('Exit to blogs') ?> <img src="img/close.gif" width="14" height="14" class="top" alt="" title="<?php echo T_('Exit to blogs') ?>" /></a><br />
		</span>
	
		<?php
		if( !$obhandler_debug )
		{ // don't display changing time when we want to test obhandler
		?>
			<?php echo T_('Blog time:') ?> <strong><?php echo date_i18n( locale_timefmt(), $localtimenow ) ?></strong>
			&middot; <?php echo T_('GMT:') ?> <strong><?php echo gmdate( locale_timefmt(), $servertimenow); ?></strong>
			&middot; <?php echo T_('Logged in as:'), ' <strong>', $user_login; ?></strong>
		<?php } ?>
		</div>
	
		<h1>
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
		</h1>
	</div>
	
	<div id="mainmenu">
	<ul>
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
	
				echo "</a></li>\n";
			}
		}
	?>
	</ul>
	<p class="center"><?php echo $app_shortname; ?> v <strong><?php echo $app_version ?></strong></p>
	</div>

	<div class="panelbody">
	<?php
	}
?>

<div id="payload">

<?php // OPTIONAL BLOG SELECTION...
	if( isset($blogListButtons) )
	{	// We have blog selection buttons to display:
		echo '<div id="TitleArea">';
		echo $blogListButtons;
		echo '</div>';
	}
?>
