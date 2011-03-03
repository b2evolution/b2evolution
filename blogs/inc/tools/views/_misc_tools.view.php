<?php
/**
 * This file display the additional tools
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Plugins;

$block_item_Widget = new Widget( 'block_item' );


// Event AdminToolPayload for each Plugin:
$tool_plugins = $Plugins->get_list_by_event( 'AdminToolPayload' );
foreach( $tool_plugins as $loop_Plugin )
{
	$block_item_Widget->title = format_to_output($loop_Plugin->name);
	$block_item_Widget->disp_template_replaced( 'block_start' );
	$Plugins->call_method_if_active( $loop_Plugin->ID, 'AdminToolPayload', $params = array() );
	$block_item_Widget->disp_template_raw( 'block_end' );
}


// TODO: dh> this should really be a separate permission.. ("tools", "exec") or similar!
if( $current_User->check_perm('options', 'edit') )
{ // default admin actions:
	global $Settings;

	$block_item_Widget->title = T_('Cache management');
	// dh> TODO: add link to delete all caches at once?
	$block_item_Widget->disp_template_replaced( 'block_start' );
	echo '<ul>';
	echo '<li><a href="'.regenerate_url('action', 'action=del_itemprecache&amp;'.url_crumb('tools')).'">'.T_('Clear pre-renderered item cache (DB)').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=del_pagecache&amp;'.url_crumb('tools')).'">'.T_('Clear full page cache (/cache directory)').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=del_filecache&amp;'.url_crumb('tools')).'">'.T_('Clear thumbnail caches (?evocache directories)').'</a></li>';
	echo '</ul>';
	$block_item_Widget->disp_template_raw( 'block_end' );

	$block_item_Widget->title = T_('Database management');
	$block_item_Widget->disp_template_replaced( 'block_start' );
	echo '<ul>';
	echo '<li><a href="'.regenerate_url('action', 'action=optimize_tables&amp;'.url_crumb('tools')).'">'.T_('Optimize database tables (MyISAM tables used for sessions & logs)').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=del_obsolete_tags&amp;'.url_crumb('tools')).'">'.T_('Remove obsolete (unused) tag entries').'</a></li>';
	// echo '<li><a href="'.regenerate_url('action', 'action=backup_db').'">'.T_('Backup database').'</a></li>';
	echo '</ul>';
	$block_item_Widget->disp_template_raw( 'block_end' );
	
	$block_item_Widget->title = T_('Database Maintenance Tools');
	$block_item_Widget->disp_template_replaced( 'block_start' );
	echo '<ul>';
	echo '<li><a href="'.regenerate_url('action', 'action=find_broken_posts&amp;'.url_crumb('tools')).'">'.T_('Find all broken posts that have no matching category').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=find_broken_slugs&amp;'.url_crumb('tools')).'">'.T_('Find all broken slugs that have no matching target post').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=delete_orphan_comment_uploads&amp;'.url_crumb('tools')).'">'.T_('Find and delete orphan comment uploads').'</a></li>';
	echo '</ul>';
	$block_item_Widget->disp_template_raw( 'block_end' );

	$block_item_Widget->title = T_('Testing Tools');
	$block_item_Widget->disp_template_replaced( 'block_start' );
	echo '<ul>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_comments&amp;'.url_crumb('tools')).'">'.T_('Create sample comments for testing moderation').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=show_create_posts&amp;'.url_crumb('tools')).'">'.T_('Create sample posts for testing').'</a></li>';
	echo '</ul>';
	$block_item_Widget->disp_template_raw( 'block_end' );

	$block_item_Widget->title = T_('Recreate item slugs');
	$block_item_Widget->disp_template_replaced( 'block_start' );
	echo '&raquo; <a href="'.regenerate_url('action', 'action=recreate_itemslugs&amp;'.url_crumb('tools')).'">'.T_('Recreate all item slugs (change title-[0-9] canonical slugs to a slug generated from current title). Old slugs will still work, but redirect to the new one.').'</a>';
	$block_item_Widget->disp_template_raw( 'block_end' );
}


// fp> TODO: pluginize MT! :P
$block_item_Widget->title = T_('Movable Type Import');
$block_item_Widget->disp_template_replaced( 'block_start' );
?>
	<ol>
		<li><?php echo T_('Use MT\'s export functionnality to create a .TXT file containing your posts;') ?></li>
		<li><?php printf( T_('Follow the instructions in <a %s>Daniel\'s Movable Type Importer</a>.'), ' href="?ctrl=mtimport"' ) ?></li>
	</ol>
<?php
$block_item_Widget->disp_template_raw( 'block_end' );


$block_item_Widget->title = T_('WordPress Import');
$block_item_Widget->disp_template_replaced( 'block_start' );
printf( '<p>'.T_('You can import contents from your WordPress 2.3 database into your b2evolution database by using <a %s>Hari\'s WordPress Importer</a>.').'</p>', ' href="?ctrl=wpimport"' );
$block_item_Widget->disp_template_raw( 'block_end' );

/*
 * $Log$
 * Revision 1.5  2011/03/03 12:50:57  efy-asimo
 * tool to find and delete orphan comment attachment files
 *
 * Revision 1.4  2011/02/21 15:27:02  efy-asimo
 * Change tool text
 *
 * Revision 1.3  2010/12/06 14:27:57  efy-asimo
 * Generate sample posts tool
 *
 * Revision 1.2  2010/11/12 15:13:31  efy-asimo
 * MFB:
 * Tool 1: "Find all broken posts that have no matching category"
 * Tool 2: "Find all broken slugs that have no matching target post"
 * Tool 3: "Create sample comments for testing moderation"
 *
 */
?>