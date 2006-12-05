<?php
/**
 * This file implements the UI controller for System configuration and analysis.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'tools', 'system' );

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
	echo '</div>';
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


echo '<h2>'.T_('System checks').'</h2>';

/*
 * PHP version
 */
init_system_check( 'PHP version', PHP_VERSION );
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


/*
 * register_globals
 */
init_system_check( 'PHP register_globals', ini_get('register_globals') ?  T_('On') : T_('Off') );
if( ini_get('register_globals' ) )
{
	disp_system_check( 'warning', $facilitate_exploits.' '.sprintf( $change_ini, 'register_globals = Off' )  );
}
else
{
	disp_system_check( 'ok' );
}


if( version_compare(PHP_VERSION, '5.2', '>=') )
{
	/*
	 * allow_url_include (since 5.2, supercedes allow_url_fopen for require()/include()
	 */
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
 * dh> TODO: this is "irrelevant" for PHP 5.2.. it refers to fopen() only, not include()/require()
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


/*
 * Magic quotes:
 */
if( ini_get('magic_quotes_sybase') )
{
	$magic_quotes = T_('On').' (magic_quotes_sybase)';
	$message = 'magic_quotes_sybase = Off';
}
elseif( ini_get('magic_quotes_gpc') )
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


/*
 * XML extension
 */
init_system_check( 'PHP XML extension', extension_loaded('xml') ?  T_('Loaded') : T_('Not loaded') );
if( ! extension_loaded('xml' ) )
{
	disp_system_check( 'warning', T_('The XML extension is not loaded.') );
}
else
{
	disp_system_check( 'ok' );
}


/*
 * /install/ folder
 */
$ok = ! is_dir( $basepath.$install_subdir );
init_system_check( 'Install folder', $ok ?  T_('Deleted') : T_('Not deleted') );
if( ! $ok )
{
	disp_system_check( 'warning', T_('The /install directory has not been removed.') );
}
else
{
	disp_system_check( 'ok' );
}


// TODO: dh> memory_limit!
// TODO: dh> output_buffering (recommend off)
// TODO: dh> session.auto_start (recommend off)
// TODO: dh> How to change ini settings in .htaccess (for mod_php), link to manual
// TODO: dh> link to phpinfo()? It's included in the /install/ folder, but that is supposed to be deleted

// pre_dump( ini_get_all() );


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
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
 *
 */
?>