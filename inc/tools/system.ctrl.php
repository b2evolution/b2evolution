<?php
/**
 * This file implements the UI controller for System configuration and analysis.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( 'tools/model/_system.funcs.php' );

// Check minimum permission:
$current_User->check_perm( 'admin', 'normal', true );
$current_User->check_perm( 'options', 'view', true );

if( $current_User->check_perm( 'options', 'edit' ) && system_check_charset_update() )
{ // DB charset is required to update
	$Messages->add( sprintf( T_('WARNING: Some of your tables have different charsets/collations than the expected. It is strongly recommended to upgrade your database charset by running the tool <a %s>Check/Convert/Normalize the charsets/collations used by the DB (UTF-8 / ASCII)</a>.'), 'href="'.$admin_url.'?ctrl=tools&amp;action=utf8check&amp;'.url_crumb( 'tools' ).'"' ) );
}

$AdminUI->set_path( 'options', 'system' );


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Status'), $admin_url.'?ctrl=system' );

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'system-status-tab' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

function init_system_check( $name, $value, $info = '' )
{
	global $syscheck_name, $syscheck_value, $syscheck_info;
	$syscheck_name = $name;
	$syscheck_value = $value;
	$syscheck_info = $info;
	evo_flush();
}

function disp_system_check( $condition, $message = '' )
{
	global $syscheck_name, $syscheck_value, $syscheck_info;
	echo '<div class="system_check">';
	echo '<div class="system_check_name">';
	echo $syscheck_name;
	echo '</div>';
	echo '<div class="system_check_value_'.$condition.'">';
	echo $syscheck_value;
	echo '&nbsp;</div>';
	echo $syscheck_info;
	if( !empty( $message ) )
	{
		echo '<div class="system_check_message_'.$condition.'">';
		echo $message;
		echo '</div>';
	}
	echo '</div>';
}


/**
 * Check API url
 *
 * @param string Title
 * @param string Url
 */
function system_check_api_url( $title, $url )
{
	$ajax_response = fetch_remote_page( $url, $info );
	if( $ajax_response !== false )
	{	// Response is correct data:
		init_system_check( $title, 'OK', $url );
		disp_system_check( 'ok' );
	}
	else
	{	// Response is not correct data:
		init_system_check( $title, T_('Failed'), $url );
		disp_system_check( 'warning', T_('This API doesn\'t work properly on this server.' )
			.' <b>'.sprintf( T_('Error: %s'), $info['error'] ).'; '.sprintf( T_('Status code: %s'), $info['status'] ).'</b>' );
	}
}

$facilitate_exploits = '<p>'.T_('When enabled, this feature is known to facilitate hacking exploits in any PHP application.')."</p>\n<p>"
	.T_('b2evolution includes additional measures in order not to be affected by this. However, for maximum security, we still recommend disabling this PHP feature.')."</p>\n";
$change_ini = '<p>'.T_('If possible, change this setting to <code>%s</code> in your php.ini or ask your hosting provider about it.').'</p>';


echo '<h2 class="page-title">'.T_('System status').'</h2>';

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
	$Messages->display();

	/**
	 * @var AbstractSettings
	 */
	global $global_Cache;
	$version_status_msg = $global_Cache->getx( 'version_status_msg' );
	if( !empty($version_status_msg) )
	{	// We have managed to get updates (right now or in the past):
		$msg = '<p>'.$version_status_msg.'</p>';
		$extra_msg = $global_Cache->getx( 'extra_msg' );
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
$block_item_Widget->title = 'b2evolution'.get_manual_link( 'system-status-tab' );
$block_item_Widget->disp_template_replaced( 'block_start' );

// Instance name:
init_system_check( T_('Instance name'), $instance_name );
disp_system_check( 'note' );

// Version:
$app_timestamp = mysql2timestamp( $app_date );
init_system_check( T_( 'b2evolution version' ), sprintf( /* TRANS: First %s: App version, second %s: release date */ T_( '%s released on %s' ), $app_version, date_i18n( locale_datefmt(), $app_timestamp ) ) );
if( ! empty($msg) )
{
	switch( $global_Cache->getx( 'version_status_color' ) )
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
		$msg .= '<p>'.sprintf( T_('Furthermore, this version is aging. You may want to check for newer releases on %s.'),
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

// .htaccess
$htaccess_path = $basepath.'.htaccess';
$sample_htaccess_path = $basepath.'sample.htaccess';
init_system_check( '.htaccess', $htaccess_path );
if( ! file_exists( $htaccess_path ) )
{	// No .htaccess
	disp_system_check( 'error', T_('Error').': '.sprintf( T_('%s has not been found.'), '<code>.htaccess</code>' ) );
}
elseif( ! file_exists( $sample_htaccess_path ) )
{	// No sample.htaccess
	disp_system_check( 'warning', T_('Warning').': '.sprintf( T_('%s has not been found.'), '<code>sample.htaccess</code>' ) );
}
elseif( trim( file_get_contents( $htaccess_path ) ) != trim( file_get_contents( $sample_htaccess_path ) ) )
{	// Different .htaccess
	disp_system_check( 'warning', T_('Warning').': '.sprintf( T_('%s differs from %s'), '<code>.htaccess</code>', '<code>sample.htaccess</code>' ) );
}
else
{	// All OK
	disp_system_check( 'ok', sprintf( T_('%s is identical to %s'), '<code>.htaccess</code>', '<code>sample.htaccess</code>' ) );
}

// /install/ folder deleted?
init_system_check( T_( 'Install folder' ), $system_stats['install_removed'] ?  T_('Deleted') : T_('Not deleted').' - '.$basepath.$install_subdir );
if( ! $system_stats['install_removed'] )
{
	disp_system_check( 'warning', T_('For maximum security, it is recommended that you delete your /blogs/install/ folder once you are done with install or upgrade.') );

	// Database reset allowed?
	init_system_check( T_( 'Database reset' ), $allow_evodb_reset ?  T_('Allowed').'!' : T_('Forbidden') );
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
elseif( version_compare( $system_stats['php_version'], '5.6', '<' ) )
{
	disp_system_check( 'warning', T_('This version is old. b2evolution may run but some features may fail. You should ask your host to upgrade PHP before running b2evolution.')
		.'<br />'.T_( 'PHP 5.6 or greater is recommended for maximum security.' ) );
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


// allow_url_include? (since PHP 5.2, supercedes allow_url_fopen for require()/include())
init_system_check( 'PHP allow_url_include', $system_stats['php_allow_url_include'] ?  T_('On') : T_('Off') );
if( $system_stats['php_allow_url_include'] )
{
	disp_system_check( 'warning', $facilitate_exploits.' '.sprintf( $change_ini, 'allow_url_include = Off' )  );
}
else
{
	disp_system_check( 'ok' );
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
elseif( $post_max_size == $upload_max_filesize )
{
	disp_system_check( 'warning', T_('post_max_size should be larger than upload_max_filesize') );
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
	if( $memory_limit < get_php_bytes_size( '256M' ) )
	{
		disp_system_check( 'error', T_('The memory_limit is too low. Some features like image manipulation will fail to work.') );
	}
	elseif( $memory_limit < get_php_bytes_size( '384M' ) )
	{
		disp_system_check( 'warning', T_('The memory_limit is low. Some features like image manipulation of large files may fail to work.') );
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
{	// Time is limited, can we request more?:
	$can_force_time = @ini_set( 'max_execution_time', 600 ); // Try to force max_execution_time to 10 minutes

	if( $can_force_time !== false )
	{
		$forced_max_execution_time = system_check_max_execution_time();
		init_system_check( 'PHP forced max_execution_time', sprintf( T_('%s seconds'), $forced_max_execution_time ) );
		disp_system_check( 'ok', sprintf( T_('b2evolution was able to request more time (than the default %s seconds) to execute complex tasks.'), $max_execution_time ) );
	}
	elseif( $max_execution_time <= 5 * 60 )
	{
		init_system_check( 'PHP max_execution_time', sprintf( T_('%s seconds'), $max_execution_time ) );
		disp_system_check( 'error', T_('b2evolution may frequently run out of time to execute properly.') );
	}
	elseif( $max_execution_time > 5 * 60 )
	{
		init_system_check( 'PHP max_execution_time', sprintf( T_('%s seconds'), $max_execution_time ) );
		disp_system_check( 'warning', T_('b2evolution may sometimes run out of time to execute properly.' ) );
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
	disp_system_check( 'warning', T_( 'Using an opcode cache allows all your PHP scripts to run faster by caching a "compiled" (opcode) version of the scripts instead of recompiling everything at every page load. Several opcode caches are available. We recommend APC (which is included with PHP starting from PHP 7).' ) );
}
else
{
	disp_system_check( 'ok' );
}

// User cache
$user_cache = get_active_user_cache();
init_system_check( 'PHP user cache', $user_cache );
if( $user_cache == 'none' )
{
	disp_system_check( 'warning', T_( 'Using an user cache allows b2evolution to store some cached data (Block Cache) in memory instead of regenerated the blocks at each page load. Several user caches are available. We recommend APCu (which needs to be enabled separately from APC, starting from PHP 7).' ) );
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
 * API
 */
$block_item_Widget->title = T_('APIs');
$block_item_Widget->disp_template_replaced( 'block_start' );

// REST API:
$api_title = 'REST API';
$api_url = $baseurl.'api/v6/collections';
$json_response = fetch_remote_page( $api_url, $api_info );
$api_result = false;
if( $json_response !== false )
{	// Try to decode REST API json data:
	$decoded_response = @json_decode( $json_response );
	$api_result = ! empty( $decoded_response );
}
if( $api_result )
{	// Response is correct json data:
	init_system_check( $api_title, 'OK', $api_url );
	disp_system_check( 'ok' );
}
else
{	// Response is not json data:
	if( ! empty( $api_info['error'] ) )
	{	// Display error message from function fetch_remote_page():
		$api_error = ' <b>'.sprintf( T_('Error: %s'), $api_info['error'] ).'; '.sprintf( T_('Status code: %s'), $api_info['status'] ).'</b>';
	}
	else
	{	// Display error message from other places:
		$api_error = error_get_last();
		$api_error = ( isset( $api_error['message'] ) ? ' <b>'.sprintf( T_( 'Error: %s' ), $api_error['message'] ).'</b>' : '' );
	}
	init_system_check( $api_title, T_('Failed'), $api_url );
	disp_system_check( 'warning', T_('This API doesn\'t work properly on this server.' )
		.' '.T_('You should probably update your <code>.htaccess</code> file to the latest version and check the file permissions of this file.')
		.$api_error );
}

// XML-RPC:
$api_title = 'XML-RPC';
$api_file = 'xmlrpc.php';
$api_url = $baseurl.$api_file;
load_funcs( 'xmlrpc/model/_xmlrpc.funcs.php' );
if( defined( 'CANUSEXMLRPC' ) && CANUSEXMLRPC === true )
{	// Try XML-RPC API only if current server can use it:
	$client = new xmlrpc_client( $api_url );
	$message = new xmlrpcmsg( 'system.listMethods' );
	$result = $client->send( $message );
	$api_error_type = T_('This API doesn\'t work properly on this server.');
}
else
{	// Get an error message if current server cannot use XML-RPC client:
	$result = false;
	$xmlrpc_error_message = CANUSEXMLRPC;
	$api_error_type = T_('This server cannot use XML-RPC client.');
}
if( $result && ! $result->faultCode() )
{	// XML-RPC request is successful:
	init_system_check( $api_title, 'OK', $api_url );
	disp_system_check( 'ok' );
}
else
{	// Some error on XML-RPC request:
	if( $result )
	{	// Get an error message of XML-RPC request:
		$xmlrpc_error_message = $result->faultString();
	}
	if( $xmlrpc_error_message == 'XML-RPC services are disabled on this system.' )
	{	// Exception for this error:
		$api_status_title = T_('Disabled');
		$api_status_type = 'ok';
	}
	else
	{	// Other errors:
		$api_status_title = T_('Failed');
		$api_status_type = 'warning';
	}
	init_system_check( $api_title, $api_status_title, $api_url );
	disp_system_check( $api_status_type, $api_error_type.' <b>'.sprintf( T_( 'Error: %s' ), $xmlrpc_error_message ).'</b>' );
}

// AJAX anon_async.php:
system_check_api_url( 'AJAX anon_async.php', $htsrv_url.'anon_async.php?action=test_api' );

// AJAX async.php:
system_check_api_url( 'AJAX async.php', $htsrv_url.'async.php?action=test_api' );

// AJAX action.php:
system_check_api_url( 'AJAX action.php', $htsrv_url.'action.php?mname=test_api' );

// AJAX call_plugin.php:
system_check_api_url( 'AJAX call_plugin.php', $htsrv_url.'call_plugin.php?plugin_ID=-1&method=test_api' );

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