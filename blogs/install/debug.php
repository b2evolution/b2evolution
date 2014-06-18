<?php
/**
 * This is the debug tool
 *
 * IF YOU ARE READING THIS IN YOUR WEB BROWSER, IT MEANS THAT PHP IS NOT PROPERLY INSTALLED
 * ON YOUR WEB SERVER. IF YOU DON'T KNOW WHAT THIS MEANS, CONTACT YOUR SERVER ADMINISTRATOR
 * OR YOUR HOSTING COMPANY.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package install
 */

/**
 * include config and default functions:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

// Make the includes believe they are being called in the right place...
define( 'EVO_MAIN_INIT', true );

if( ! $config_is_done )
{ // Base config is not done yet, try to guess some values needed for correct display:
	$rsc_url = '../rsc/';
}

require_once $inc_path.'_core/_class'.floor(PHP_VERSION).'.funcs.php';
require_once $inc_path.'_core/_misc.funcs.php';

/**
 * Load locale related functions
 */
require_once $inc_path.'locales/_locale.funcs.php';

load_class( '_core/model/_log.class.php', 'Log');
$Debuglog = new Log();
load_class( '_core/model/_messages.class.php', 'Messages');
$Messages = new Messages();

/**
 * Load modules.
 *
 * This initializes table name aliases and is required before trying to connect to the DB.
 */
load_class( '_core/model/_module.class.php', 'Module' );
foreach( $modules as $module )
{
	require_once $inc_path.$module.'/_'.$module.'.init.php';
}

load_class( '/_core/model/db/_db.class.php', 'DB' );
load_class( '_core/model/_timer.class.php', 'Timer' );
load_funcs( '_core/_url.funcs.php' );

require_once dirname(__FILE__).'/_functions_install.php';


/**
 * Check password
 */
function debug_check_password()
{
	global $errors, $debug_pwd;

	$password = param( 'password', 'string' );
	if( empty( $password ) )
	{
		$errors[] = T_('Please enter the password');
		return false;
	}
	else if( $password != $debug_pwd )
	{
		$errors[] = T_('Incorrect password');
		return false;
	}

	return true;
}

/**
 * Get debug config content
 *
 * @param boolean TRUE- to close file handle after getting the content
 * @return string Debug config
 */
function debug_get_config( $close_handle = true )
{
	global $errors, $file_overrides_name, $file_section_start, $file_section_end, $file_overrides_handle;

	// Get debug config from file
	$file_overrides_handle = @fopen( $file_overrides_name, 'r+' );
	$file_overrides_content = '';
	if( $file_overrides_handle )
	{
		while( !feof( $file_overrides_handle ) )
		{
			$file_overrides_content .= fgets( $file_overrides_handle, 4096 );
		}
		if( $close_handle )
		{
			fclose( $file_overrides_handle );
		}
	}
	else
	{	// Bad file name or file has not the rights to read/write
		$errors[] = T_('Config file cannot be opened');
		return false;
	}

	return $file_overrides_content;
}


$Timer = new Timer('main');

load_funcs('_core/_param.funcs.php');

// Let the modules load/register what they need:
modules_call_method( 'init' );

/**
 * @var File name
 */
$file_overrides_name = $conf_path.'_overrides_TEST.php';

// The start and the end of the debug config
$file_section_start = '// @@BEGIN debug.php section';
$file_section_end = '// @@END debug.php section';

param( 'action', 'string', '' );

$errors = array();
switch( $action )
{
	case 'login':
		// Check password
		if( !debug_check_password() )
		{	// Some errors exist, show the form with password again
			$action = 'password_form';
			break;
		}

		$action = 'config_form';

		$file_overrides_content = debug_get_config();

		// Get only part for editing
		$file_overrides_content = substr( $file_overrides_content, strpos( $file_overrides_content, $file_section_start ) + strlen( $file_section_start ) );
		$file_overrides_content = substr( $file_overrides_content, 0, strpos( $file_overrides_content, $file_section_end ) );
		$file_overrides_content = trim( $file_overrides_content );
		break;

	case 'update_config':
		// Update debug config
		if( !debug_check_password() )
		{	// Some errors exist, show the form with password again
			$action = 'password_form';
			break;
		}

		$action = 'config_form';

		$file_overrides_content = param( 'content', 'raw' );
		$old_content = debug_get_config( false );

		if( count( $errors ) > 0)
		{
			if( $file_overrides_handle )
			{
				fclose( $file_overrides_handle );
			}
			break;
		}

		if( strpos( $old_content, $file_section_start ) !== false && strpos( $old_content, $file_section_end ) !== false )
		{ // File contains correct section to edit
			$old_content_start = substr( $old_content, 0, strpos( $old_content, $file_section_start ) + strlen( $file_section_start ) );
			$old_content_end = substr( $old_content, strpos( $old_content, $file_section_end ) );
		}
		else
		{ // Config editable start or end sections don't exist in the file, we should create them
			$is_php_tag_opened = strpos( $old_content, '<?' ) !== false;
			$is_php_tag_closed = strpos( $old_content, '?>' ) !== false;
			if( $is_php_tag_opened && $is_php_tag_closed )
			{ // File contains the open and close tags
				$old_content_start = substr( $old_content, 0, strpos( $old_content, '?>' ) );
				$old_content_end = substr( $old_content, strpos( $old_content, '?>' ) );
			}
			elseif( !$is_php_tag_opened && !$is_php_tag_closed )
			{ // File doesn't contain the open and close php tags, Add this at the file end
				$old_content_start = $old_content.'<?php'."\n";
				$old_content_end = '?>';
			}
			elseif( !$is_php_tag_closed )
			{ // File doesn't contain only the close php tag, Insert new config text at the end
				$old_content_start = $old_content;
				$old_content_end = '';
			}
			$old_content_start = $old_content_start."\n".$file_section_start;
			$old_content_end = $file_section_end."\n".$old_content_end;
		}

		// Write new content into config
		ftruncate( $file_overrides_handle, 0 );
		fseek( $file_overrides_handle, 0 );
		fwrite( $file_overrides_handle, $old_content_start."\n".$file_overrides_content."\n".$old_content_end );

		fclose( $file_overrides_handle );
		$message = T_('Debug config has been changed');
		break;

	default:
		if( file_exists( $file_overrides_name ) )
		{	// Display a form to log in if file already exists
			$action = 'password_form';
		}
		break;
}


switch( $action )
{
	case 'password_form':
		$title = T_('Check password');
		break;

	case 'config_form':
		$title = T_('Update debug config');
		break;

	default:
		$title = '';
		break;
}

// Add CSS:
require_css( 'basic_styles.css', 'rsc_url' ); // the REAL basic styles
require_css( 'basic.css', 'rsc_url' ); // Basic styles
require_css( 'evo_distrib_2.css', 'rsc_url' );

header('Content-Type: text/html; charset='.$io_charset);
header('Cache-Control: no-cache'); // no request to this page should get cached!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/evo_distrib_2.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title><?php echo T_('b2evo debug tool').( $title ? ': '.$title : '' ) ?></title>
	<!-- InstanceEndEditable -->
	<meta name="viewport" content="width = 750" />
	<meta name="robots" content="noindex, follow" />
	<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
	<!-- InstanceBeginEditable name="head" --><!-- InstanceEndEditable -->
	<!-- InstanceParam name="lang" type="text" value="&lt;?php locale_lang() ?&gt;" -->
</head>

<body>
	<!-- InstanceBeginEditable name="BodyHead" --><!-- InstanceEndEditable -->

	<div class="wrapper1">
	<div class="wrapper2">
		<span class="version_top"><!-- InstanceBeginEditable name="Version" --><?php echo T_('Debug tool') ?><!-- InstanceEndEditable --></span>

		<a href="http://b2evolution.net/" target="_blank"><img src="../rsc/img/distrib/b2evolution-logo.gif" alt="b2evolution" width="237" height="92" /></a>

		<div class="menu_top"><!-- InstanceBeginEditable name="MenuTop" -->
			<span class="floatright"><?php echo T_('After install') ?>: <a href="../index.php"><?php echo T_('Blogs') ?></a> &middot;
			<a href="../<?php echo $dispatcher ?>"><?php echo T_('Admin') ?></a>
			</span>
		<?php echo T_('Current installation') ?>:
		<a href="index.php?locale=<?php echo $default_locale ?>"><?php echo T_('Install menu') ?></a> &middot;
		<a href="phpinfo.php"><?php echo T_('PHP info') ?></a>
		<!-- InstanceEndEditable --></div>

		<!-- InstanceBeginEditable name="Main" -->
<?php

if( count( $errors ) > 0 )
{	// Display errors
	echo '<div class="error">';
	echo '<ul class="error"><li>'.implode( '</li><li>', $errors ).'</li></ul>';
	echo '</div>';
}

if( !empty( $message ) )
{	// Display errors
	echo '<div class="success">';
	echo '<ul class="success"><li>'.$message.'</li></ul>';
	echo '</div>';
}

block_open();
echo '<h1>'.T_('Debug tool').'</h1>';
echo '<p>'.sprintf( T_('This tool allows you to configure some debug variables in the file %s'), $file_overrides_name ).'</p>';

switch( $action )
{
	case 'password_form':
		/*
		 * -----------------------------------------------------------------------------------
		 * Form to log in
		 * -----------------------------------------------------------------------------------
		 */
		?>
		<form class="fform" name="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<input type="hidden" name="action" value="login" />

			<fieldset>
				<legend><?php echo T_('Log in to edit the config of debug') ?></legend>
				<?php
					form_text( 'password', '', 16, T_('Password'), T_('Debug password'), 120, '', 'password' );
				?>
					<div class="input">
						<input type="submit" name="submit" value="<?php echo T_('Log in') ?>" class="search" />
					</div>
			</fieldset>

		</form>
		<?php
		break;

	case 'config_form':
		/*
		 * -----------------------------------------------------------------------------------
		 * Form to change the debug config
		 * -----------------------------------------------------------------------------------
		 */
		?>
		<form class="fform" name="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<input type="hidden" name="action" value="update_config" />
			<input type="hidden" name="password" value="<?php echo $password; ?>" />

			<fieldset>
				<legend><?php echo T_('Debug config') ?></legend>
				<?php

					form_textarea( 'content', $file_overrides_content, 20, T_('Config'), array(
						'cols' => 50,
						'note' => '<br />
A few possible settings:<br /><br />
$minimum_comment_interval = 1;<br />
$debug = 1;<br />
$debug_jslog = 1;<br />
$allow_po_extraction = 1;<br />
$test_install_all_features = true;<br />
$db_config[\'debug_dump_rows\'] = 20;<br />
$db_config[\'debug_explain_joins\'] = false;<br />
$display_errors_on_production = false;'
						) );
				?>
					<div class="input">
						<input type="submit" name="submit" value="<?php echo T_('Save Changes!') ?>" class="search" />
					</div>
			</fieldset>

		</form>
		<?php
		break;

	default:
		/*
		 * -----------------------------------------------------------------------------------
		 * Default config
		 * -----------------------------------------------------------------------------------
		 */

		echo T_( 'To enable this tool, create a file called /conf/_overrides_TEST.php with the following contents:' );
		echo '<p>&lt;?php<br />
$debug_pwd = \'set a password here\';<br />
// @@BEGIN debug.php section<br />
// @@END debug.php section<br />
?&gt;</p>';

		break;
}

block_close();
?>

<!-- InstanceEndEditable -->
	</div>

	<div class="body_fade_out">

	<div class="menu_bottom"><!-- InstanceBeginEditable name="MenuBottom" -->
			<?php echo T_('Online resources') ?>: <a href="http://b2evolution.net/" target="_blank"><?php echo T_('Official website') ?></a> &bull; <a href="http://b2evolution.net/about/recommended-hosting-lamp-best-choices.php" target="_blank"><?php echo T_('Find a host') ?></a> &bull; <a href="<?php echo get_manual_url( NULL ); ?>" target="_blank"><?php echo T_('Manual') ?></a> &bull; <a href="http://forums.b2evolution.net/" target="_blank"><?php echo T_('Forums') ?></a>
		<!-- InstanceEndEditable --></div>

	<div class="copyright"><!-- InstanceBeginEditable name="CopyrightTail" -->Copyright &copy; 2003-2014 by Fran&ccedil;ois Planque &amp; others &middot; <a href="http://b2evolution.net/about/license.html" target="_blank">GNU GPL license</a> &middot; <a href="http://b2evolution.net/contact/" target="_blank">Contact</a>
		<!-- InstanceEndEditable --></div>

	</div>
	</div>

	<!-- InstanceBeginEditable name="BodyFoot" -->
	<?php
		// We need to manually call debug_info since there is no shutdown function registered during the install process.
		// debug_info( true ); // force output of debug info

		// The following comments get checked in the automatic install script of demo.b2evolution.net:
?>
<!-- b2evo-install-action:<?php echo $action ?> -->
<!-- b2evo-install-end -->
	<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>