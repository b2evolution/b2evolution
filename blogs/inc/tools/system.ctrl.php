<?php
/**
 * This file implements the UI controller for System configuration and analysis.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs( 'tools/model/_system.funcs.php' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'tools', 'system' );


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Tools'), '?ctrl=crontab' );
$AdminUI->breadcrumbpath_add( T_('About this system'), '?ctrl=system' );


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


echo '<h2>'.T_('About this system').'</h2>';


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
		//$msg = '<p>Updates from b2evolution.net are disabled!</p>';
		//$msg .= '<p>You will <b>NOT</b> be alerted if you are running an insecure configuration.</p>';
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
list( $mediadir_status, $mediadir_msg ) = system_check_dir('media');
$mediadir_long = '';
if( $mediadir_status == 'error' )
{
	$mediadir_long = '<p>'.T_('You will not be able to upload files/images and b2evolution will not be able to generate thumbnails.')."</p>\n"
	.'<p>'.T_('Your host requires that you set special file permissions on your media directory.').get_manual_link('media_file_permission_errors')."</p>\n";
}
init_system_check( T_( 'Media directory' ), $mediadir_msg.' - '.$media_path );
disp_system_check( $mediadir_status, $mediadir_long );


// Cache folder writable?
list( $cachedir_status, $cachedir_msg ) = system_check_dir('cache');
$cachedir_long = '';
if( $cachedir_status == 'error' )
{
	$cachedir_long = '<p>'.T_('You will not be able to use page cache.')."</p>\n"
	.'<p>'.T_('Your host requires that you set special file permissions on your cache directory.').get_manual_link('cache_file_permission_errors')."</p>\n";
}
init_system_check( T_( 'Cache directory' ), $cachedir_msg.' - '.$cache_path );
disp_system_check( $cachedir_status, $cachedir_long );


// /install/ folder deleted?
list( $installdir_status, $installdir_msg ) = system_check_install_removed();
init_system_check( T_( 'Install folder' ), $installdir_msg );
if( $installdir_status == 'error' )
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
$block_item_Widget->title = T_('MySQL');
$block_item_Widget->disp_template_replaced( 'block_start' );

// Version:
$mysql_version = $DB->get_version();
init_system_check( T_( 'MySQL version' ), $DB->version_long );
if( version_compare( $mysql_version, '4.0' ) < 0 )
{
	disp_system_check( 'error', T_('This version is too old. The minimum recommended MySQL version is 4.1.') );
}
elseif( version_compare( $mysql_version, '4.1' ) < 0 )
{
	disp_system_check( 'warning', T_('This version is not guaranteed to work. The minimum recommended MySQL version is 4.1.') );
}
else
{
	disp_system_check( 'ok' );
}

// UTF8 support?
$ok = system_check_db_utf8();
init_system_check( T_( 'MySQL UTF-8 support' ), $ok ?  T_('Yes') : T_('No') );
if( ! $ok )
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
$block_item_Widget->title = T_('PHP');
$block_item_Widget->disp_template_replaced( 'block_start' );


// User ID:
list( $uid, $uname, $running_as ) = system_check_process_user();
init_system_check( T_( 'PHP running as USER:' ), $running_as );
disp_system_check( 'note' );


// Group ID:
list( $gid, $gname, $running_as ) = system_check_process_group();
init_system_check( T_( 'PHP running as GROUP:' ), $running_as );
disp_system_check( 'note' );


// PHP version
$phpinfo_url = '?ctrl=tools&amp;action=view_phpinfo&amp;'.url_crumb('tools');
$phpinfo_link = action_icon( T_('View PHP info'), 'info', $phpinfo_url, '', 5, '', array( 'target'=>'_blank', 'onclick'=>'return pop_up_window( \''.$phpinfo_url.'\', \'phpinfo\', 650 )' ) );
init_system_check( T_( 'PHP version' ), PHP_VERSION.' '.$phpinfo_link );

if( version_compare( PHP_VERSION, '4.1', '<' ) )
{
	disp_system_check( 'error', T_('This version is too old. b2evolution will not run correctly. You must ask your host to upgrade PHP before you can run b2evolution.') );
}
elseif( version_compare( PHP_VERSION, '4.3', '<' ) )
{
	disp_system_check( 'warning', T_('This version is old. b2evolution may run but some features may fail. You should ask your host to upgrade PHP before running b2evolution.') );
}
else
{
	disp_system_check( 'ok' );
}

// register_globals?
init_system_check( 'PHP register_globals', ini_get('register_globals') ?  T_('On') : T_('Off') );
if( ini_get('register_globals' ) )
{
	disp_system_check( 'warning', $facilitate_exploits.' '.sprintf( $change_ini, 'register_globals = Off' )  );
}
else
{
	disp_system_check( 'ok' );
}


// allow_url_include? (since 5.2, supercedes allow_url_fopen for require()/include()
if( version_compare(PHP_VERSION, '5.2', '>=') )
{
	init_system_check( 'PHP allow_url_include', ini_get('allow_url_include') ?  T_('On') : T_('Off') );
	if( ini_get('allow_url_include' ) )
	{
		disp_system_check( 'warning', $facilitate_exploits.' '.sprintf( $change_ini, 'allow_url_include = Off' )  );
	}
	else
	{
		disp_system_check( 'ok' );
	}
}


/*
 * allow_url_fopen
 * Note: this allows including of remote files (PHP 4 only) as well as opening remote files with fopen() (all versions of PHP)
 * Both have potential for exploits. (The first is easier to exploit than the second).
 * dh> Should we check for curl etc then also and warn the user until there's no method for us anymore to open remote files?
 * fp> Yes
 */
init_system_check( 'PHP allow_url_fopen', ini_get('allow_url_fopen') ?  T_('On') : T_('Off') );
if( ini_get('allow_url_fopen' ) )
{
	disp_system_check( 'warning', $facilitate_exploits.' '.sprintf( $change_ini, 'allow_url_fopen = Off' )  );
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
	if( $memory_limit < 8096 )
	{
		disp_system_check( 'error', T_('The memory_limit is very low. Some features of b2evolution will fail to work;') );
	}
	elseif( $memory_limit < 12288 )
	{
		disp_system_check( 'warning', T_('The memory_limit is low. Some features of b2evolution may fail to work;') );
	}
	else
	{
		disp_system_check( 'ok' );
	}
}


// mbstring extension
init_system_check( T_( 'PHP mbstring extension' ), extension_loaded('mbstring') ?  T_('Loaded') : T_('Not loaded') );
if( ! extension_loaded('mbstring' ) )
{
	disp_system_check( 'warning', T_('b2evolution will not be able to convert character sets and special characters/languages may not be displayed correctly. Enable the mbstring extension in your php.ini file or ask your hosting provider about it.') );
}
else
{
	disp_system_check( 'ok' );
}


// XML extension
init_system_check( T_( 'PHP XML extension' ), extension_loaded('xml') ?  T_('Loaded') : T_('Not loaded') );
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
init_system_check( T_( 'PHP IMAP extension' ), $imap_loaded ? T_( 'Loaded' ) : T_( 'Not loaded' ) );
if ( ! $imap_loaded )
{
	disp_system_check( 'warning', T_( 'You will not be able to use the Blog  by email feature of b2evolution. Enable the IMAP extension in your php.ini file or ask your hosting provider about it.' ) );
}
else
{
	disp_system_check( 'ok' );
}

// APC extension
$opcode_cache = get_active_opcode_cache();
init_system_check( T_( 'PHP opcode cache' ), $opcode_cache );
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
	init_system_check( T_( 'GD JPG Support' ), ( ! empty($gd_info['JPG Support']) || ! empty($gd_info['JPEG Support']) ) ? T_('Read/Write') : T_('No') );
	if( empty($gd_info['JPG Support']) && empty($gd_info['JPEG Support']) )
	{
		disp_system_check( 'warning', T_('You will not be able to automatically generate thumbnails for JPG images.') );
	}
	else
	{
		disp_system_check( 'ok' );
	}

	// PNG:
	init_system_check( T_( 'GD PNG Support' ), !empty($gd_info['PNG Support']) ? T_('Read/Write') : T_('No') );
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
	init_system_check( T_( 'GD GIF Support' ), $gif_support );
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
	init_system_check( T_( 'GD FreeType Support' ), !empty($gd_info['FreeType Support']) ?  T_('Yes') : T_('No') );
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



// TODO: dh> memory_limit!
// TODO: dh> output_buffering (recommend off)
// TODO: dh> session.auto_start (recommend off)
// TODO: dh> How to change ini settings in .htaccess (for mod_php), link to manual
// fp> all good ideas :)
// fp> MySQL version
// TODO: dh> link to phpinfo()? It's included in the /install/ folder, but that is supposed to be deleted
// fp> we can just include it a second time as an 'action' here.
// TODO: dh> submit the report into a central database
// fp>yope, with a Globally unique identifier in order to avoid duplicates.

// pre_dump( ini_get_all() );


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.33  2011/01/03 03:02:54  sam2kb
 * Check if /cache directory is writable. Check if /install directory and index.php are readable.
 *
 * Revision 1.32  2010/11/25 15:16:35  efy-asimo
 * refactor $Messages
 *
 * Revision 1.31  2010/11/04 03:16:10  sam2kb
 * Display PHP info in a pop-up window
 *
 * Revision 1.30  2010/04/22 20:45:09  blueyed
 * Fix typo with "PHP opcode cache" message
 *
 * Revision 1.29  2010/03/23 03:16:40  sam2kb
 * Added info about GD FreeType Support
 *
 * Revision 1.28  2010/02/08 17:54:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.27  2010/02/04 19:37:13  blueyed
 * trans cleanup
 *
 * Revision 1.26  2010/01/30 18:55:35  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.25  2010/01/03 12:39:08  fplanque
 * no message
 *
 * Revision 1.24  2009/12/24 12:32:07  waltercruz
 * Minor
 *
 * Revision 1.23  2009/12/06 22:55:20  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.22  2009/11/30 01:08:27  fplanque
 * extended system optimization checks
 *
 * Revision 1.21  2009/09/26 18:58:18  tblue246
 * GD info fix for PHP 5.3
 *
 * Revision 1.20  2009/04/13 14:50:22  tblue246
 * Typo, bugfix
 *
 * Revision 1.19  2009/04/12 20:15:38  tblue246
 * Make more strings available for translation
 *
 * Revision 1.18  2009/04/11 15:19:28  tblue246
 * typo
 *
 * Revision 1.17  2009/03/24 20:05:59  waltercruz
 * mispelled word
 *
 * Revision 1.16  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.15  2009/02/23 14:07:23  afwas
 * Minor
 *
 * Revision 1.14  2009/02/21 23:10:43  fplanque
 * Minor
 *
 * Revision 1.13  2009/01/24 21:49:45  tblue246
 * Add a check for the PHP IMAP extension which is needed by the Blog by email feature.
 *
 * Revision 1.12  2008/09/27 00:05:54  fplanque
 * minor/version bump
 *
 * Revision 1.11  2008/04/24 22:06:00  fplanque
 * factorized system checks
 *
 * Revision 1.10  2008/03/24 03:12:36  blueyed
 * Add TODO
 *
 * Revision 1.9  2008/02/07 00:36:28  fplanque
 * added mbstrings check
 *
 * Revision 1.8  2008/01/21 09:35:35  fplanque
 * (c) 2008
 *
 * Revision 1.7  2008/01/06 18:47:08  fplanque
 * enhanced system checks
 *
 * Revision 1.6  2008/01/06 17:52:50  fplanque
 * minor/doc
 *
 * Revision 1.5  2008/01/05 22:02:55  blueyed
 * Add info about process user and her group
 *
 * Revision 1.4  2007/10/06 21:31:51  fplanque
 * minor
 *
 * Revision 1.3  2007/10/01 19:02:23  fplanque
 * MySQL version check
 *
 * Revision 1.2  2007/09/04 15:29:16  fplanque
 * interface cleanup
 *
 * Revision 1.1  2007/06/25 11:01:42  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.17  2007/05/20 01:02:32  fplanque
 * magic quotes fix
 *
 * Revision 1.16  2007/04/26 00:11:15  fplanque
 * (c) 2007
 *
 * Revision 1.15  2007/03/04 20:14:16  fplanque
 * GMT date now in system checks
 *
 * Revision 1.14  2007/02/22 19:08:31  fplanque
 * file/memory size checks (not fully tested)
 *
 * Revision 1.13  2006/12/21 21:50:32  fplanque
 * removed rant
 *
 * Revision 1.11  2006/12/13 03:08:28  fplanque
 * thumbnail implementation design demo
 *
 * Revision 1.10  2006/12/13 00:57:18  fplanque
 * GD... just for fun ;)
 *
 * Revision 1.9  2006/12/07 23:21:00  fplanque
 * dashboard blog switching
 *
 * Revision 1.8  2006/12/07 23:16:08  blueyed
 * doc: we want no remote file opening anymore?!
 *
 * Revision 1.7  2006/12/06 23:38:45  fplanque
 * doc
 *
 * Revision 1.6  2006/12/06 22:51:41  blueyed
 * doc
 *
 * Revision 1.5  2006/12/05 15:15:56  fplanque
 * more tests
 *
 * Revision 1.4  2006/12/05 12:26:39  blueyed
 * Test for "SET NAMES utf8"
 *
 * Revision 1.3  2006/12/05 12:11:14  blueyed
 * Some more checks and todos
 *
 * Revision 1.2  2006/12/05 11:30:26  fplanque
 * presentation
 *
 * Revision 1.1  2006/12/05 10:20:18  fplanque
 * A few basic systems checks
 *
 * Revision 1.15  2006/12/05 04:27:49  fplanque
 * moved scheduler to Tools (temporary until UI redesign)
 *
 * Revision 1.14  2006/11/26 01:42:08  fplanque
 * doc
 */
?>
