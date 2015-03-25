<?php
/**
 * This is the debug tool
 *
 * IF YOU ARE READING THIS IN YOUR WEB BROWSER, IT MEANS THAT PHP IS NOT PROPERLY INSTALLED
 * ON YOUR WEB SERVER. IF YOU DON'T KNOW WHAT THIS MEANS, CONTACT YOUR SERVER ADMINISTRATOR
 * OR YOUR HOSTING COMPANY.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package install
 */

/**
 * include config and default functions:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

// Make the includes believe they are being called in the right place...
define( 'EVO_MAIN_INIT', true );

if( ! defined( 'EVO_MAIN_INIT' ) ) die( 'Please, do not access this page directly.' );

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

// Form params
$booststrap_install_form_params = array(
		'formstart'      => '',
		'formend'        => '',
		'fieldstart'     => '<div class="form-group" $ID$>'."\n",
		'fieldend'       => "</div>\n\n",
		'labelclass'     => 'control-label col-sm-4',
		'labelstart'     => '',
		'labelend'       => "\n",
		'labelempty'     => '<label class="control-label col-sm-4"></label>',
		'inputstart'     => '<div class="col-sm-8">',
		'inputend'       => "</div>\n",
		'buttonsstart'   => '<div class="form-group"><div class="control-buttons col-sm-offset-4 col-sm-8">',
		'buttonsend'     => "</div></div>\n\n",
		'note_format'    => ' <span class="help-inline text-muted small">%s</span>',
	);

header('Content-Type: text/html; charset='.$evo_charset);
header('Cache-Control: no-cache'); // no request to this page should get cached!
?>
<!DOCTYPE  html>
<html lang="en">
	<head>
		<base href="<?php echo get_script_baseurl(); ?>">
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, follow" />
		<title><?php echo format_to_output( T_('b2evo installer').( $title ? ': '.$title : '' ), 'htmlhead' ); ?></title>
		<script type="text/javascript" src="../rsc/js/jquery.min.js"></script>
		<!-- Bootstrap -->
		<script type="text/javascript" src="../rsc/js/bootstrap/bootstrap.min.js"></script>
		<link href="../rsc/css/bootstrap/bootstrap.min.css" rel="stylesheet">
		<link href="../rsc/build/b2evo_helper_screens.css" rel="stylesheet">
	</head>
	<body>
		<div class="container">
			<div class="header">
				<nav>
					<ul class="nav nav-pills pull-right">
						<li role="presentation"><a href="../readme.html"><?php echo T_('Read me'); ?></a></li>
						<li role="presentation" class="active"><a href="index.php"><?php echo T_('Installer'); ?></a></li>
						<li role="presentation"><a href="../index.php"><?php echo T_('Your site'); ?></a></li>
					</ul>
				</nav>
				<h3 class="text-muted"><a href="http://b2evolution.net/"><img src="../rsc/img/b2evolution8.png" alt="b2evolution CCMS"></a></h3>
			</div>

		<!-- InstanceBeginEditable name="Main" -->
<?php

if( count( $errors ) )
{ // Display errors
	display_install_messages( $errors );
}

if( ! empty( $message ) )
{ // Display a message
	display_install_messages( $message, 'success' );
}

echo '<h1>'.T_('Debug tool').'</h1>';
display_install_messages( sprintf( T_('This tool allows you to configure some debug variables in the file %s'), $file_overrides_name ), 'info' );

switch( $action )
{
	case 'password_form':
		/*
		 * -----------------------------------------------------------------------------------
		 * Form to log in
		 * -----------------------------------------------------------------------------------
		 */
		block_open( T_('Log in to edit the config of debug') );
		$Form = new Form( $_SERVER['PHP_SELF'] );

		$Form->switch_template_parts( $booststrap_install_form_params );

		$Form->begin_form( 'form-horizontal' );

		$Form->hidden( 'action', 'login' );

		$Form->text( 'password', '', 16, T_('Password'), T_('Debug password'), 120, '', 'password' );

		$Form->end_form( array( array( 'name' => 'submit', 'value' => T_('Log in'), 'class' => 'btn-primary btn-lg' ) ) );
		block_close();
		break;

	case 'config_form':
		/*
		 * -----------------------------------------------------------------------------------
		 * Form to change the debug config
		 * -----------------------------------------------------------------------------------
		 */
		
		block_open( T_('Debug config') );
		$Form = new Form( $_SERVER['PHP_SELF'] );

		$Form->switch_template_parts( $booststrap_install_form_params );

		$Form->begin_form( 'form-horizontal' );

		$Form->hidden( 'action', 'update_config' );
		$Form->hidden( 'password', $password );

		$Form->textarea_input( 'content', $file_overrides_content, 20, T_('Config'), array(
						'cols' => 50,
						'note' => 'A few possible settings:<br /><br />
$minimum_comment_interval = 1;<br />
$debug = 1;<br />
$debug_jslog = 1;<br />
$allow_po_extraction = 1;<br />
$test_install_all_features = true;<br />
$db_config[\'debug_dump_rows\'] = 20;<br />
$db_config[\'debug_explain_joins\'] = false;<br />
$display_errors_on_production = false;'
						) );

		$Form->end_form( array( array( 'name' => 'submit', 'value' => T_('Save Changes!'), 'class' => 'btn-primary btn-lg' ) ) );
		block_close();
		break;

	default:
		/*
		 * -----------------------------------------------------------------------------------
		 * Default config
		 * -----------------------------------------------------------------------------------
		 */

		echo T_( 'To enable this tool, create a file called /conf/_overrides_TEST.php with the following contents:' );
		echo '<pre>&lt;?php
$debug_pwd = \'set a password here\';
// @@BEGIN debug.php section
// @@END debug.php section
?&gt;</pre>';

		break;
}
?>


<!-- InstanceEndEditable -->

			<footer class="footer">
				<p class="pull-right"><a href="https://github.com/b2evolution/b2evolution" class="text-nowrap"><?php echo T_('Project page on GitHub'); ?></a></p>
				<p><a href="http://b2evolution.net/" class="text-nowrap">b2evolution.net</a>
				&bull; <a href="http://b2evolution.net/man/" class="text-nowrap"><?php echo T_('Online manual'); ?></a>
				&bull; <a href="http://forums.b2evolution.net" class="text-nowrap"><?php echo T_('Get help from the community!'); ?></a>
				</p>
			</footer>

		</div><!-- /container -->

	<?php
		// We need to manually call debug_info since there is no shutdown function registered during the install process.
		// debug_info( true ); // force output of debug info

		// The following comments get checked in the automatic install script of demo.b2evolution.net:
?>
<!-- b2evo-install-action:<?php echo $action ?> -->
<!-- b2evo-install-end -->
	</body>
</html>