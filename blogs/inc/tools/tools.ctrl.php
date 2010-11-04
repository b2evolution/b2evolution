<?php
/**
 * This file implements the UI controller for additional tools.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_funcs('plugins/_plugin.funcs.php');


param( 'tab', 'string', '', true );

$tab_Plugin = NULL;
$tab_plugin_ID = false;

if( ! empty($tab) )
{	// We have requested a tab which is handled by a plugin:
	if( preg_match( '~^plug_ID_(\d+)$~', $tab, $match ) )
	{ // Instanciate the invoked plugin:
		$tab_plugin_ID = $match[1];
		$tab_Plugin = & $Plugins->get_by_ID( $match[1] );
		if( ! $tab_Plugin )
		{ // Plugin does not exist
			$Messages->add( sprintf( T_( 'The plugin with ID %d could not get instantiated.' ), $tab_plugin_ID ), 'error' );
			$tab_plugin_ID = false;
			$tab_Plugin = false;
			$tab = '';
		}
		else
		{
			$Plugins->call_method_if_active( $tab_plugin_ID, 'AdminTabAction', $params = array() );
		}
	}
	else
	{
		$tab = '';
		$Messages->add( 'Invalid sub-menu!' ); // Should need no translation, prevented by GUI
	}
}

// Highlight the requested tab (if valid):
$AdminUI->set_path( 'tools', $tab );


if( empty($tab) )
{	// "Main tab" actions:
	if( param( 'action', 'string', '' ) )
	{
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tools' );
	
		// fp> TODO: have an option to only PRUNE files older than for example 30 days
		$current_User->check_perm('options', 'edit', true);
	}

	switch( $action )
	{
		case 'del_itemprecache':
			$DB->query('DELETE FROM T_items__prerendering WHERE 1=1');

			$Messages->add( sprintf( T_('Removed %d cached entries.'), $DB->rows_affected ), 'success' );
			break;

		case 'del_pagecache':
			// Delete the page cache /blogs/cache
			global $cache_path;

			// Clear general cache directory
			if( cleardir_r( $cache_path.'general' ) )
			{
				$Messages->add( sprintf( T_('General cache deleted: %s'), $cache_path.'general' ), 'note' );
			}
			else
			{
				$Messages->add( sprintf( T_('Could not delete general cache: %s'), $cache_path.'general' ), 'error' );
			}

			$SQL = 'SELECT blog_ID FROM T_blogs
					INNER JOIN T_coll_settings ON ( blog_ID = cset_coll_ID
								AND cset_name = "cache_enabled"
								AND cset_value = "1" )
					WHERE 1=1';

			if( $blog_array = $DB->get_col( $SQL ) )
			{
				foreach( $blog_array as $l_blog )
				{	// Clear blog cache
					if( cleardir_r( $cache_path.'c'.$l_blog ) )
					{
						$Messages->add( sprintf( T_('Blog %d cache deleted: %s'), $l_blog, $cache_path.'c'.$l_blog ), 'note' );
					}
					else
					{
						$Messages->add( sprintf( T_('Could not delete blog %d cache: %s'), $l_blog, $cache_path.'c'.$l_blog ), 'error' );
					}
				}
			}

			$Messages->add( T_('Page cache deleted.'), 'success' );
			break;

		case 'del_filecache':
			// delete the thumbnail cahces .evocache
			// TODO> handle custom media directories dh> ??
			// Delete any ?evocache folders:
			$deleted_dirs = delete_cachefolders($Messages);
			$Messages->add( sprintf( T_('Deleted %d directories.'), $deleted_dirs ), 'success' );
			break;

		case 'optimize_tables':
			// Optimize MyISAM tables
			global $tableprefix;

			$db_optimized = false;
			$tables = $DB->get_results( 'SHOW TABLE STATUS FROM `'.$DB->dbname.'` LIKE \''.$tableprefix.'%\'');

			foreach( $tables as $table )
			{
				// Before MySQL 4.1.2, the "Engine" field was labeled as "Type".
				if( ( ( isset( $table->Engine ) && $table->Engine == 'MyISAM' )
					  || ( isset( $table->Type ) && $table->Type == 'MyISAM' ) )
					&& $table->Data_free )
				{	// Optimization needed
					if( !$DB->query( 'OPTIMIZE TABLE '.$table->Name ) )
					{
						$Messages->add( sprintf( T_('Database table %s could not be optimized.'), '<b>'.$table->Name.'</b>' ), 'note' );
					}
					else
					{
						$db_optimized = true;
						$Messages->add( sprintf( T_('Database table %s optimized.'), '<b>'.$table->Name.'</b>' ), 'success' );
					}
				}
			}

			if( !$db_optimized )
			{
				$Messages->add( T_('Database tables are already optimized.'), 'success' );
			}
			break;

		case 'recreate_itemslugs':
			$ItemCache = get_ItemCache();
			$ItemCache->load_where( '( post_title != "" ) AND ( post_urltitle = "title" OR post_urltitle LIKE "title-%" )');
			$items = $ItemCache->get_ID_array();
			$count_slugs = 0;
			@set_time_limit(0);
			foreach( $items as $item_ID )
			{
				$Item = $ItemCache->get_by_ID($item_ID);

				$prev_urltitle = $Item->get( 'urltitle' );
				$item_title = $Item->get( 'title' );

				// check if post title is not empty and urltitle was auto generated ( equals title or title-[0-9]+ )
				// Note: urltitle will be auto generated on this form (title-[0-9]+), if post title wass empty and, urltitle was not set
				// Note: Even if a post title was set to 'title' on purpose it's possible, that this tool will change the post urltitle
				if( ( ! empty( $item_title ) ) && ( ( $prev_urltitle == 'title' ) || ( preg_match( '#^title-[0-9]+$#', $prev_urltitle ) ) ) )
				{
					// set urltitle empty, so the item update function will regenerate the item slug
					$Item->set( 'urltitle', '' );
					$result = $Item->dbupdate(/* do not autotrack modification */ false, /* update slug */ true, /* do not update excerpt */ false); 
					if( ( $result ) && ( $prev_urltitle != $Item->get( 'urltitle' ) ) )
					{ // update was successful, and item urltitle was changed
						$count_slugs++;
					}
				}
			}
			$Messages->add( sprintf( 'Created %d new URL slugs.', $count_slugs ), 'success' );
			break;

		case 'del_obsolete_tags':
			$DB->query('
				DELETE T_items__tag FROM T_items__tag
				  LEFT JOIN T_items__itemtag ON tag_ID = itag_tag_ID
				 WHERE itag_itm_ID IS NULL');
			$Messages->add( sprintf(T_('Removed %d obsolete tag entries.'), $DB->rows_affected), 'success' );
			break;

		case 'view_phpinfo':
			// Display PHP info and exit
			headers_content_mightcache('text/html');
			phpinfo();
			exit();
			break;
	}
}

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Tools'), '?ctrl=crontab' );
$AdminUI->breadcrumbpath_add( T_('Miscellaneous'), '?ctrl=tools' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();


if( empty($tab) )
{

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

}
elseif( $tab_Plugin )
{ // Plugin tab

	// Icons:
	?>

	<div class="right_icons">

	<?php
	echo $tab_Plugin->get_edit_settings_link()
		.' '.$tab_Plugin->get_help_link('$help_url')
		.' '.$tab_Plugin->get_help_link('$readme');
	?>

	</div>

	<?php
	$Plugins->call_method_if_active( $tab_plugin_ID, 'AdminTabPayload', $params = array() );
}


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.34  2010/11/04 03:16:10  sam2kb
 * Display PHP info in a pop-up window
 *
 * Revision 1.33  2010/07/28 07:58:53  efy-asimo
 * Add where condition to recreate slugs tool query
 *
 * Revision 1.32  2010/07/26 07:24:27  efy-asimo
 * Tools recreate item slugs (change description + fix notice)
 *
 * Revision 1.31  2010/07/26 06:52:27  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.30  2010/06/15 21:33:24  blueyed
 * Fix patch failure.
 *
 * Revision 1.29  2010/06/15 21:20:37  blueyed
 * Add tools action to remove obsolete/unused tags.
 *
 * Revision 1.28  2010/05/24 21:27:58  sam2kb
 * Fixed some translated strings
 *
 * Revision 1.27  2010/05/02 00:15:07  blueyed
 * cleanup
 *
 * Revision 1.26  2010/05/02 00:14:07  blueyed
 * Add recreate_itemslugs tool to re-generate slugs for all items.
 *
 * Revision 1.25  2010/03/27 19:57:30  blueyed
 * Add delete_cachefolders function and use it in the Tools Misc actions and with the watermark plugin. The latter will also remove caches when it gets enabled or disabled.
 *
 * Revision 1.24  2010/03/12 10:52:56  efy-asimo
 * Set EvoCache  folder names - task
 *
 * Revision 1.23  2010/02/08 17:54:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.22  2010/01/14 21:30:31  blueyed
 * Make deleting .evocache folders far less verbose.
 *
 * Revision 1.21  2010/01/03 18:07:37  fplanque
 * crumbs
 *
 * Revision 1.20  2009/12/06 22:55:20  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.19  2009/11/25 19:53:58  blueyed
 * Fix 'Optimize database tables' SQL: quote DB name.
 *
 * Revision 1.18  2009/11/25 00:54:26  blueyed
 * todo
 *
 * Revision 1.17  2009/11/12 03:54:17  fplanque
 * wording/doc/cleanup
 *
 * Revision 1.16  2009/10/16 18:41:47  tblue246
 * Cleanup/doc
 *
 * Revision 1.15  2009/10/02 14:17:34  tblue246
 * minor/doc
 *
 * Revision 1.13  2009/10/02 13:28:03  sam2kb
 * Backup b2evo database from Tools > Misc
 *
 * Revision 1.12  2009/10/01 16:19:14  sam2kb
 * minor
 *
 * Revision 1.11  2009/10/01 14:58:44  sam2kb
 * Delete page and thumbnails cache
 *
 * Revision 1.10  2009/10/01 13:06:03  tblue246
 * Fix for backward compatibility with MySQL versions lower than 4.1.2.
 *
 * Revision 1.9  2009/10/01 12:57:18  tblue246
 * Tools -> Optimize DB: Drop substr() check for table prefix and modify the SQL query to only return appropriate tables instead.
 *
 * Revision 1.8  2009/09/30 19:48:38  tblue246
 * Tools -> Optimize tables: Do not use preg_match() to check table prefix but a simple substr().
 *
 * Revision 1.7  2009/09/30 18:00:19  sam2kb
 * Optimize b2evo tables from Tools > Misc
 *
 * Revision 1.6  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.5  2008/07/11 23:10:01  blueyed
 * s/insctructions/instructions/g
 *
 * Revision 1.4  2008/01/21 09:35:35  fplanque
 * (c) 2008
 *
 * Revision 1.3  2007/10/09 01:18:12  fplanque
 * Hari's WordPress importer
 *
 * Revision 1.2  2007/09/04 14:57:07  fplanque
 * interface cleanup
 *
 * Revision 1.1  2007/06/25 11:01:42  fplanque
 * MODULES (refactored MVC)
 *
 */
?>
