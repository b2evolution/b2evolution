<?php
/**
 * This file implements the UI controller for System configuration and analysis.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author blueyed
 *
 * @version $Id: system.ctrl.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( 'tools/model/_system.funcs.php' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'options', 'system' );


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Status'), '?ctrl=system' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

function init_system_check( $name, $value )
{
	global $syscheck_name, $syscheck_value;
	$syscheck_name = $name;
	$syscheck_value = $value;
}

function disp_system_check( $condition, $message = '' )
{
	global $syscheck_name, $syscheck_value;
	echo '<div class="system_check">';
	echo '<div class="system_check_name">';
	echo $syscheck_name;
	echo '</div>';
	echo '<div class="system_check_value_'.$condition.'">';
	echo $syscheck_value;
	echo '&nbsp;</div>';
	if( !empty( $message ) )
	{
		echo '<div class="system_check_message_'.$condition.'">';
		echo $message;
		echo '</div>';
	}
	echo '</div>';
}

$facilitate_exploits = '<p>'.T_('When enabled, this feature is known to facilitate hacking exploits in any PHP application.')."</p>\n<p>"
	.T_('b2evolution includes additional measures in order not to be affected by this.
	However, for maximum security, we still recommend disabling this PHP feature.')."</p>\n";
$change_ini = '<p>'.T_('If possible, change this setting to <code>%s</code> in your php.ini or ask your hosting provider about it.').'</p>';


echo '<h2>'.T_('System status').'</h2>';

// Get system stats to display:
$system_stats = get_system_stats();

// Note: hopefully, the update swill have been downloaded in the shutdown function of a previous page (including the login screen)
// However if we have outdated info, we will load updates here.
load_funcs( 'dashboard/model/_dashboard.funcs.php' );
// Let's clear any remaining messages that should already have been displayed before...
$Messages->clear();

if( b2evonet_get_updates( true ) !== NULL )
{	// Updates are allowed, display them:

	// Display info & error messages
	echo $Messages->display( NULL, NULL, false, 'action_messages' );

	/**
	 * @var AbstractSettings
	 */
	global $global_Cache;
	$version_status_msg = $global_Cache->get( 'version_status_msg' );
	if( !empty($version_status_msg) )
	{	// We have managed to get updates (right now or in the past):
		$msg = '<p>'.$version_status_msg.'</p>';
		$extra_msg = $global_Cache->get( 'extra_msg' );
		if( !empty($extra_msg) )
		{
			$msg .= '<p>'.$extra_msg.'</p>';
		}
	}

}
else
{
	$msg = '';
}


$block_item_Widget = new Widget( 'block_item' );


/**
 * b2evolution
 */
$block_item_Widget->title = 'b2evolution';
$block_item_Widget->disp_template_replaced( 'block_start' );

// Version:
$app_timestamp = mysql2timestamp( $app_date );
init_system_check( T_( 'b2evolution version' ), sprintf( /* TRANS: First %s: App version, second %s: release date */ T_( '%s released on %s' ), $app_version, date_i18n( locale_datefmt(), $app_timestamp ) ) );
if( ! empty($msg) )
{
	switch( $global_Cache->get( 'version_status_color' ) )
	{
		case 'green':
			disp_system_check( 'ok', $msg );
			break;

		case 'yellow':
			disp_system_check( 'warning', $msg );
			break;

		default:
			disp_system_check( 'error', $msg );
	}
}
else
{
	$msg = '<p>Updates from b2evolution.net are disabled!</p>
			<p>You will <b>NOT</b> be alerted if you are running an insecure configuration.</p>';

	$app_age = ($localtimenow - $app_timestamp) / 3600 / 24 / 30;	// approx age in months
	if( $app_age > 12 )
	{
		$msg .= '<p>'.sprintf( T_('Furthermore, this version is old. You should check for newer releases on %s.'),
			'<a href="http://b2evolution.net/downloads/">b2evolution.net</a>' ).'</p>';
	}
	elseif( $app_age > 6 )
	{
		$msg .= '<p>'.sprintf( T_('Furthermore, this version is aging. You may want to check for newer releases on %s..'),
			'<a href="http://b2evolution.net/downloads/">b2evolution.net</a>' ).'</p>';
	}

	disp_system_check( 'error', $msg );
}

// Media folder writable?
list( $mediadir_status, $mediadir_msg ) = system_get_result( $system_stats['mediadir_status'] );
$mediadir_long = '';
if( $mediadir_status == 'error' )
{
	$mediadir_long = '<p>'.T_('You will not be able to upload files/images and b2evolution will not be able to generate thumbnails.')."</p>\n"
	.'<p>'.T_('Your host requires that you set special file permissions on your media directory.').get_manual_link('media_file_permission_errors')."</p>\n";
}
init_system_check( T_( 'Media directory' ), $mediadir_msg.' - '.$media_path );
disp_system_check( $mediadir_status, $mediadir_long );


// /install/ folder deleted?
init_system_check( T_( 'Install folder' ), $system_stats['install_removed'] ?  T_('Deleted') : T_('Not deleted').' - '.$basepath.$install_subdir );
if( ! $system_stats['install_removed'] )
{
	disp_system_check( 'warning', T_('For maximum security, it is recommended that you delete your /blogs/install/ folder once you are done with install or upgrade.') );

	// Database reset allowed?
	init_system_check( T_( 'Database reset' ), $allow_evodb_reset ?  T_('Allowed!') : T_('Forbidden') );
	if( $allow_evodb_reset )
	{
  	disp_system_check( 'error', '<p>'.T_('Currently, anyone who accesses your install folder could entirely reset your b2evolution database.')."</p>\n"
  	 .'<p>'.T_('ALL YOUR DATA WOULD BE LOST!')."</p>\n"
  	 .'<p>'.T_('As soon as possible, change the setting <code>$allow_evodb_reset = 0;</code> in your /conf/_basic.config.php.').'</p>' );
	}
	else
	{
		disp_system_check( 'ok' );
	}
}
else
{
	disp_system_check( 'ok' );
}

init_system_check( 'Internal b2evo charset' , $system_stats['evo_charset'] );
disp_system_check( 'note' );

init_system_check( 'Blog count' , $system_stats['evo_blog_count'] );
disp_system_check( 'note' );

$block_item_Widget->disp_template_raw( 'block_end' );


/*
 * Caching
 */
$block_item_Widget->title = T_( 'Caching' );
$block_item_Widget->disp_template_replaced( 'block_start' );

// Cache folder writable?
list( $cachedir_status, $cachedir_msg ) = system_get_result( $system_stats['cachedir_status'] );
$cachedir_long = '';
if( $cachedir_status == 'error' )
{
	$cachedir_long = '<p>'.T_('You will not be able to use page cache.')."</p>\n"
	.'<p>'.T_('Your host requires that you set special file permissions on your cache directory.').get_manual_link('cache_file_permission_errors')."</p>\n";
}
init_system_check( T_( 'Cache directory' ), $cachedir_msg.' - '.$cache_path );
disp_system_check( $cachedir_status, $cachedir_long );

// cache folder size
init_system_check( 'Cache folder size', bytesreadable($system_stats['cachedir_size']) );
disp_system_check( 'note' );

if( $cachedir_status != 'error' )
{ // 'cache/ directory exists and, it is writable

	// General cache is enabled
	init_system_check( T_( 'General caching' ), $system_stats['general_pagecache_enabled'] ? 'Enabled' : 'Disabled' );
	disp_system_check( 'note' );

	// how many blogs have enabled caches
	$error_messages = system_check_caches( false );
	$enabled_message = $system_stats['blog_pagecaches_enabled'].' enabled /'.$system_stats['evo_blog_count'].' blogs';
	init_system_check( T_( 'Blog\'s cache setting' ), $enabled_message );
	disp_system_check( 'note' );
	if( count( $error_messages ) > 0 )
	{ // show errors
		init_system_check( T_( 'Blog\'s cache errors' ), implode( '<br />', $error_messages ) );
		disp_system_check( 'error' );
	}
}

$block_item_Widget->disp_template_raw( 'block_end' );


/*
 * Time
 */
$block_item_Widget->title = T_('Time');
$block_item_Widget->disp_template_replaced( 'block_start' );

init_system_check( T_( 'Server time' ), date_i18n( locale_datetimefmt( ' - ' ), $servertimenow ) );
disp_system_check( 'note' );

init_system_check( T_( 'GMT / UTC time' ), gmdate( locale_datetimefmt( ' - ' ), $servertimenow ) );
disp_system_check( 'note' );

init_system_check( T_( 'b2evolution time' ), date_i18n( locale_datetimefmt( ' - ' ), $localtimenow ) );
disp_system_check( 'note' );

$block_item_Widget->disp_template_raw( 'block_end' );


/*
 * MySQL Version
 */
$block_item_Widget->title = 'MySQL';
$block_item_Widget->disp_template_replaced( 'block_start' );

// Version:
init_system_check( T_( 'MySQL version' ), $DB->version_long );
if( version_compare( $system_stats['db_version'], $required_mysql_version['application'] ) < 0 )
{
	disp_system_check( 'error', sprintf( T_('This version is too old. The minimum recommended MySQL version is %s.'), $required_mysql_version['application'] ) );
}
else
{
	disp_system_check( 'ok' );
}

// UTF8 support?
init_system_check( 'MySQL UTF-8 support', $system_stats['db_utf8'] ?  T_('Yes') : T_('No') );
if( ! $system_stats['db_utf8'] )
{
	disp_system_check( 'warning', T_('UTF-8 is not supported by your MySQL server.') ); // fp> TODO: explain why this is bad. Better yet: try to detect if we really need it, base don other conf variables.
}
else
{
	disp_system_check( 'ok' );
}

$block_item_Widget->disp_template_raw( 'block_end' );


/**
 * PHP
 */
$block_item_Widget->title = 'PHP';
$block_item_Widget->disp_template_replaced( 'block_start' );


// User ID:
list( $uid, $uname, $running_as ) = system_check_process_user();
init_system_check( 'PHP running as USER:', $running_as );
disp_system_check( 'note' );


// Group ID:
list( $gid, $gname, $running_as ) = system_check_process_group();
init_system_check( 'PHP running as GROUP:', $running_as );
disp_system_check( 'note' );


// PHP version
$phpinfo_url = '?ctrl=tools&amp;action=view_phpinfo&amp;'.url_crumb('tools');
$phpinfo_link = action_icon( T_('View PHP info'), 'info', $phpinfo_url, '', 5, '', array( 'target'=>'_blank', 'onclick'=>'return pop_up_window( \''.$phpinfo_url.'\', \'phpinfo\', 650 )' ) );
init_system_check( 'PHP version', $system_stats['php_version'].' '.$phpinfo_link );

if( version_compare( $system_stats['php_version'], $required_php_version['application'], '<' ) )
{
	disp_system_check( 'error', T_('This version is too old. b2evolution will not run correctly. You must ask your host to upgrade PHP before you can run b2evolution.') );
}
elseif( version_compare( $system_stats['php_version'], '5.2', '<' ) )
{
	disp_system_check( 'warning', T_('This version is old. b2evolution may run but some features may fail. You should ask your host to upgrade PHP before running b2evolution.')
		// Display a message about httpOnly for PHP < 5.2
		.'<br />'.T_( 'PHP 5.2 or greater is recommended for maximum security, especially for "httpOnly" cookies support.' ) );
}
else
{
	disp_system_check( 'ok' );
}

// register_globals?
init_system_check( 'PHP register_globals', $system_stats['php_reg_globals'] ?  T_('On') : T_('Off') );
if( $system_stats['php_reg_globals'] )
{
	disp_system_check( 'warning', $facilitate_exploits.' '.sprintf( $change_ini, 'register_globals = Off' )  );
}
else
{
	disp_system_check( 'ok' );
}


// allow_url_include? (since 5.2, supercedes allow_url_fopen for require()/include())
if( version_compare(PHP_VERSION, '5.2', '>=') )
{
	init_system_check( 'PHP allow_url_include', $system_stats['php_allow_url_include'] ?  T_('On') : T_('Off') );
	if( $system_stats['php_allow_url_include'] )
	{
		disp_system_check( 'warning', $facilitate_exploits.' '.sprintf( $change_ini, 'allow_url_include = Off' )  );
	}
	else
	{
		disp_system_check( 'ok' );
	}
}
else
{
	/*
	 * allow_url_fopen
	 * Note: this allows including of remote files (PHP 4 only) as well as opening remote files with fopen() (all versions of PHP)
	 * Both have potential for exploits. (The first is easier to exploit than the second).
	 * dh> Should we check for curl etc then also and warn the user until there's no method for us anymore to open remote files?
	 * fp> Yes
	 */
	init_system_check( 'PHP allow_url_fopen', $system_stats['php_allow_url_fopen'] ?  T_('On') : T_('Off') );
	if( $system_stats['php_allow_url_fopen'] )
	{
		disp_system_check( 'warning', $facilitate_exploits.' '.sprintf( $change_ini, 'allow_url_fopen = Off' )  );
	}
	else
	{
		disp_system_check( 'ok' );
	}
}

// Magic quotes:
if( !strcasecmp( ini_get('magic_quotes_sybase'), 'on' ) )
{
	$magic_quotes = T_('On').' (magic_quotes_sybase)';
	$message = 'magic_quotes_sybase = Off';
}
elseif( get_magic_quotes_gpc() )
{
	$magic_quotes = T_('On').' (magic_quotes_gpc)';
	$message = 'magic_quotes_gpc = Off';
}
else
{
	$magic_quotes = T_('Off');
	$message = '';
}
init_system_check( 'PHP Magic Quotes', $magic_quotes );
if( !empty( $message ) )
{
	disp_system_check( 'warning', T_('PHP is adding extra quotes to all inputs. This leads to unnecessary extra processing.')
		.' '.sprintf( $change_ini, $message ) );
}
else
{
	disp_system_check( 'ok' );
}


// Max upload size:
$upload_max_filesize = system_check_upload_max_filesize();
init_system_check( 'PHP upload_max_filesize', ini_get('upload_max_filesize') );
disp_system_check( 'ok' );

// Max post size:
$post_max_size = system_check_post_max_size();
init_system_check( 'PHP post_max_size', ini_get('post_max_size') );
if( $post_max_size > $upload_max_filesize )
{
	disp_system_check( 'ok' );
}
else
{
	disp_system_check( 'error', T_('post_max_size should be larger than upload_max_filesize') );
}

// Memory limit:
$memory_limit = system_check_memory_limit();
if( empty($memory_limit) )
{
	init_system_check( 'PHP memory_limit', T_('n.a.') );
	disp_system_check( 'note' );
}
else
{
	init_system_check( 'PHP memory_limit', ini_get('memory_limit') );
	if( $memory_limit < get_php_bytes_size( '8M' ) )
	{
		disp_system_check( 'error', T_('The memory_limit is very low. Some features of b2evolution will fail to work;') );
	}
	elseif( $memory_limit < get_php_bytes_size( '12M' ) )
	{
		disp_system_check( 'warning', T_('The memory_limit is low. Some features of b2evolution may fail to work;') );
	}
	else
	{
		disp_system_check( 'ok' );
	}
}

// Maximum execution time of each script
$max_execution_time = system_check_max_execution_time();
if( empty( $max_execution_time ) )
{
	init_system_check( 'PHP max_execution_time', T_('Unlimited') );
	disp_system_check( 'ok' );
}
else
{
	init_system_check( 'PHP max_execution_time', sprintf( T_('%s seconds'), $max_execution_time ) );
	if( $max_execution_time <= 5 * 60 )
	{
		disp_system_check( 'error' );
	}
	elseif( $max_execution_time > 5 * 60 )
	{
		disp_system_check( 'warning' );
	}
}
if( $max_execution_time < 600 )
{ // Force max_execution_time to 10 minutes
	$result = ini_set( 'max_execution_time', 600 );
	if( $result !== false )
	{
		$max_execution_time = system_check_max_execution_time();
		init_system_check( 'PHP forced max_execution_time', sprintf( T_('%s seconds'), $max_execution_time ) );
		disp_system_check( 'warning' );
	}
}

// mbstring extension
init_system_check( 'PHP mbstring extension', extension_loaded('mbstring') ?  T_('Loaded') : T_('Not loaded') );
if( ! extension_loaded('mbstring' ) )
{
	disp_system_check( 'warning', T_('b2evolution will not be able to convert character sets and special characters/languages may not be displayed correctly. Enable the mbstring extension in your php.ini file or ask your hosting provider about it.') );
}
else
{
	disp_system_check( 'ok' );
}


// XML extension
init_system_check( 'PHP XML extension', extension_loaded('xml') ?  T_('Loaded') : T_('Not loaded') );
if( ! extension_loaded('xml' ) )
{
	disp_system_check( 'warning', T_('The XML extension is not loaded.') ); // fp> This message only repeats the exact same info that is already displayed. Not helpful.
	// fp>TODO: explain what we need it for. Is it a problem or not.
	// furthermore I think xmlrpc does dynamic loading (or has it been removed?), in which case this should be tested too.
	// dh> You mean the deprecated dl() loading? (fp>yes) We might just try this then here also before any warning.
}
else
{
	disp_system_check( 'ok' );
}

// IMAP extension
$imap_loaded = extension_loaded( 'imap' );
init_system_check( 'PHP IMAP extension', $imap_loaded ? T_( 'Loaded' ) : T_( 'Not loaded' ) );
if ( ! $imap_loaded )
{
	disp_system_check( 'warning', T_( 'You will not be able to use the Post by Email feature and the Return Path email processing of b2evolution. Enable the IMAP extension in your php.ini file or ask your hosting provider about it.' ) );
}
else
{
	disp_system_check( 'ok' );
}

// Opcode cache
$opcode_cache = get_active_opcode_cache();
init_system_check( 'PHP opcode cache', $opcode_cache );
if( $opcode_cache == 'none' )
{
	disp_system_check( 'warning', T_( 'Using an opcode cache allows all your PHP scripts to run faster by caching a "compiled" (opcode) version of the scripts instead of recompiling everything at every page load. Several opcode caches are available. We recommend APC.' ) );
}
else
{
	disp_system_check( 'ok' );
}

// pre_dump( get_loaded_extensions() );

$block_item_Widget->disp_template_raw( 'block_end' );



/*
 * GD Library
 * windows: extension=php_gd2.dll
 * unix: ?
 * fp> Note: I'm going to use this for thumbnails for now, but I plan to use it for other things like small stats & status graphics.
 */
$block_item_Widget->title = T_('GD Library (image handling)');
$block_item_Widget->disp_template_replaced( 'block_start' );

$gd_version = system_check_gd_version();
init_system_check( T_( 'GD Library version' ), isset($gd_version) ? $gd_version : T_('Not installed') );
if( ! isset($gd_version) )
{
	disp_system_check( 'warning', T_('You will not be able to automatically generate thumbnails for images. Enable the gd2 extension in your php.ini file or ask your hosting provider about it.') );
}
else
{
	disp_system_check( 'ok' );

	$gd_info = gd_info();

	// JPG:
	// Tblue> Note: "JPG Support" was renamed to "JPEG Support" in PHP 5.3.
	init_system_check( 'GD JPG Support', ( ! empty($gd_info['JPG Support']) || ! empty($gd_info['JPEG Support']) ) ? T_('Read/Write') : T_('No') );
	if( empty($gd_info['JPG Support']) && empty($gd_info['JPEG Support']) )
	{
		disp_system_check( 'warning', T_('You will not be able to automatically generate thumbnails for JPG images.') );
	}
	else
	{
		disp_system_check( 'ok' );
	}

	// PNG:
	init_system_check( 'GD PNG Support', !empty($gd_info['PNG Support']) ? T_('Read/Write') : T_('No') );
	if( empty($gd_info['PNG Support']) )
	{
		disp_system_check( 'warning', T_('You will not be able to automatically generate thumbnails for PNG images.') );
	}
	else
	{
		disp_system_check( 'ok' );
	}

	// GIF:
	if( !empty($gd_info['GIF Create Support']) )
	{
		$gif_support = T_('Read/Write');
	}
	elseif( !empty($gd_info['GIF Read Support']) )
	{
		$gif_support = T_('Read');
	}
	else
	{
		$gif_support = T_('No');
	}
	init_system_check( 'GD GIF Support', $gif_support );
	if( $gif_support == T_('No') )
	{
		disp_system_check( 'warning', T_('You will not be able to automatically generate thumbnails for GIF images.') );
	}
	elseif( $gif_support == T_('Read') )
	{
		disp_system_check( 'warning', T_('Thumbnails for GIF images will be generated as PNG or JPG.') );
	}
	else
	{
		disp_system_check( 'ok' );
	}

	// FreeType:
	init_system_check( 'GD FreeType Support', !empty($gd_info['FreeType Support']) ?  T_('Yes') : T_('No') );
	if( empty($gd_info['FreeType Support']) )
	{
		disp_system_check( 'warning', T_('You will not be able to write text to images using TrueType fonts.') );
	}
	else
	{
		disp_system_check( 'ok' );
	}
	// pre_dump( $gd_info );
}
$block_item_Widget->disp_template_raw( 'block_end' );


/*
 * Info pages
 */
$block_item_Widget->title = T_('Info pages');
$block_item_Widget->disp_template_replaced( 'block_start' );

init_system_check( 'Default page:', '<a href="'.$baseurl.'default.php">'.$baseurl.'default.php</a>' );
disp_system_check( 'note' );

$block_item_Widget->disp_template_raw( 'block_end' );

// TODO: dh> output_buffering (recommend off)
// TODO: dh> session.auto_start (recommend off)
// TODO: dh> How to change ini settings in .htaccess (for mod_php), link to manual
// fp> all good ideas :)
// TODO: dh> submit the report into a central database
// fp>yope, with a Globally unique identifier in order to avoid duplicates.

// pre_dump( ini_get_all() );


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>