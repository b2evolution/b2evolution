<?php
/**
 * This file implements the UI controller for additional tools.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
{ // "Main tab" actions:
	param( 'action', 'string', '' );

	switch( $action )
	{
		case 'del_itemprecache':
			// TODO: dh> this should really be a separate permission.. ("tools", "exec") or similar!
			$current_User->check_perm('options', 'edit', true);

			$DB->query('DELETE FROM T_items__prerendering WHERE 1=1');

			$Messages->add( sprintf( T_('Removed %d cached entries.'), $DB->rows_affected ), 'success' );
			break;

		case 'del_pagecache':
			$current_User->check_perm('options', 'edit', true);

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
			$current_User->check_perm('options', 'edit', true);

			global $media_path;

			// TODO> handle custom media directories
			$dirs = get_filenames( $media_path, false );
			foreach( $dirs as $dir )
			{
				if( basename($dir) == '.evocache' )
				{	// Delete .evocache directory recursively
					if( rmdir_r( $dir ) )
					{
						$Messages->add( sprintf( T_('Directory deleted: %s'), $dir ), 'note' );
					}
					else
					{
						$Messages->add( sprintf( T_('Could not delete directory: %s'), $dir ), 'error' );
					}
				}
			}

			$Messages->add( T_('Files cache deleted.'), 'success' );
			break;

		case 'optimize_tables':
			$current_User->check_perm('options', 'edit', true);

			global $tableprefix;

			$db_optimized = false;
			// This fails if DB name is numeric!
			$tables = $DB->get_results( 'SHOW TABLE STATUS FROM '.$DB->dbname.' LIKE \''.$tableprefix.'%\'');

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
	}
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();


if( empty($tab) )
{

	$block_item_Widget = & new Widget( 'block_item' );


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
		$block_item_Widget->title = T_('Advanced operations');
		$block_item_Widget->disp_template_replaced( 'block_start' );
		echo '&raquo; <a href="'.regenerate_url('action', 'action=del_itemprecache').'">'.T_('Delete pre-renderered item cache.').'</a>';
		echo '<br /><br />&raquo; <a href="'.regenerate_url('action', 'action=del_pagecache').'">'.T_('Delete rendered pages from cache directory.').'</a>';
		echo '<br /><br />&raquo; <a href="'.regenerate_url('action', 'action=del_filecache').'">'.T_('Delete cached thumbnails (.evocache directories).').'</a>';
		echo '<br /><br />&raquo; <a href="'.regenerate_url('action', 'action=optimize_tables').'">'.T_('Optimize database tables.').'</a>';
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
