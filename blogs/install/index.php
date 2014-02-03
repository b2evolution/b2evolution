<?php
/**
 * This is the main install menu
 *
 * IF YOU ARE READING THIS IN YOUR WEB BROWSER, IT MEANS THAT PHP IS NOT PROPERLY INSTALLED
 * ON YOUR WEB SERVER. IF YOU DON'T KNOW WHAT THIS MEANS, CONTACT YOUR SERVER ADMINISTRATOR
 * OR YOUR HOSTING COMPANY.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package install
 */

// Turn off the output buffering to do the correct work of the function flush()
@ini_set( 'output_buffering', 'off' );

/**
 * include config and default functions:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

// Make the includes believe they are being called in the right place...
define( 'EVO_MAIN_INIT', true );

/**
 * Define that we're in the install process.
 */
define( 'EVO_IS_INSTALLING', true );

// Force to display errors during install/upgrade, even when not in debug mode
$display_errors_on_production = true;

$script_start_time = time();
$localtimenow = $script_start_time; // used e.g. for post_datemodified (sample posts)

if( ! $config_is_done )
{	// Base config is not done yet, try to guess some values needed for correct display:
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

// fp> TODO: we may want to try to get the base init into here somehow
// $require_base_config = false;

require_once $conf_path.'_upgrade.php';
// no longer exists: require_once $inc_path.'_vars.inc.php';
load_class( '/_core/model/db/_db.class.php', 'DB' );
//load_funcs('collections/model/_blog.funcs.php');
//load_funcs('collections/model/_category.funcs.php');
//load_class( 'items/model/_item.class.php', 'Item' );
//load_funcs('items/model/_item.funcs.php');
//load_funcs('users/model/_user.funcs.php');
//load_funcs( '_core/ui/forms/_form.funcs.php' );
load_class( '_core/model/_timer.class.php', 'Timer' );
//load_class( 'plugins/model/_plugins.class.php', 'Plugins' );
load_funcs( '_core/_url.funcs.php' );


require_once dirname(__FILE__).'/_functions_install.php';

$Timer = new Timer('main');

load_funcs('_core/_param.funcs.php');

// Let the modules load/register what they need:
modules_call_method( 'init' );


param( 'action', 'string', 'default' );
// check if we should try to connect to db if config is not done
switch( $action )
{
	case 'evoupgrade':
	case 'auto_upgrade':
	case 'svn_upgrade':
	case 'newdb':
	case 'cafelogupgrade':
	case 'deletedb':
	case 'menu':
	case 'localeinfo':
		$try_db_connect = true;
		break;
	case 'start':
	case 'conf':
	case 'default':
		$try_db_connect = false;
		break;
	default:
		// set a valid action
		$action = 'default';
		$try_db_connect = false;
		break;
}

$timestamp = time() - 120; // We start dates 2 minutes ago because their dates increase 1 second at a time and we want everything to be visible when the user watches the blogs right after install :P

// Load all available locale defintions:
locales_load_available_defs();
param( 'locale', 'string' );
$use_locale_from_request = false;
if( preg_match( '/[a-z]{2}-[A-Z]{2}(-.{1,14})?/', $locale ) )
{
	$default_locale = $locale;
	$use_locale_from_request = true;
}
if( ! empty( $default_locale ) && ! empty( $locales ) && isset( $locales[ $default_locale ] ) )
{ // Set correct charset, The main using is for DB connection
	$evo_charset = $locales[ $default_locale ]['charset'];
}

if( $config_is_done || $try_db_connect )
{ // Connect to DB:

	$tmp_evoconf_db = $db_config;

	// We want a friendly message if we can't connect:
	$tmp_evoconf_db['halt_on_error'] = false;
	$tmp_evoconf_db['show_errors'] = false;

	// Make sure we use the proper charset:
	$tmp_evoconf_db['connection_charset'] = $evo_charset;

	// CONNECT TO DB:
	$DB = new DB( $tmp_evoconf_db );
	unset($tmp_evoconf_db);

	if( !$DB->error )
	{ // restart conf
		$DB->halt_on_error = true;  // From now on, halt on errors.
		$DB->show_errors = true;    // From now on, show errors (they're helpful in case of errors!).

		// Check MySQL version
		$mysql_version = $DB->get_version();
		foreach( $required_mysql_version as $key => $value )
		{ // check required MySQL version for the whole application and for each module
			if( version_compare( $mysql_version, $value, '<' ) )
			{
				if( $key == 'application' )
				{
					$error_message = sprintf( T_('The minimum requirement for this version of b2evolution is %s version %s but you are trying to use version %s!'), 'MySQL', $value, $mysql_version );
				}
				else
				{
					$error_message = sprintf( T_('The minimum requirement for %s module is %s version %s but you are trying to use version %s!'), $key, 'MySQL', $value, $mysql_version );
				}
				die( '<h1>Insufficient Requirements</h1><div class="error"><p class="error"><strong>'.$error_message.'</strong></p></div>');
			}
		}
	}
}

if( ! $use_locale_from_request )
{ // detect language
	// try to check if db already exists and default locale is set on it
	$default_locale = get_default_locale_from_db();
	if( empty( $default_locale ) )
	{ // db doesn't exists yet
		$default_locale = locale_from_httpaccept();
	}
	// echo 'detected locale: ' . $default_locale. '<br />';
}
// Activate default locale:
if( ! locale_activate( $default_locale ) )
{	// Could not activate locale (non-existent?), fallback to en-US:
	$default_locale = 'en-US';
	locale_activate( 'en-US' );
}

init_charsets( $current_charset );

switch( $action )
{
	case 'evoupgrade':
	case 'auto_upgrade':
	case 'svn_upgrade':
		$title = T_('Upgrade from a previous version');
		break;

	case 'newdb':
		$title = T_('New Install');
		break;

	case 'cafelogupgrade':
		$title = T_('Upgrade from Cafelog/b2');
		break;

	case 'deletedb':
		$title = T_('Delete b2evolution tables');
		break;

	case 'start':
		$title = T_('Base configuration');
		break;

	case 'conf':
	case 'menu':
	case 'localeinfo':
	case 'default':
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
	<title><?php echo T_('b2evo installer').( $title ? ': '.$title : '' ) ?></title>
	<!-- InstanceEndEditable -->
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $io_charset; ?>" />
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
		<span class="version_top"><!-- InstanceBeginEditable name="Version" --><?php echo T_('Installer for version ').' '. $app_version ?><!-- InstanceEndEditable --></span>

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
block_open();

// echo $action;
$date_timezone = ini_get( "date.timezone" );
if( empty( $date_timezone ) && empty( $date_default_timezone ) )
{ // The default timezone is not set, display a warning
	echo '<div class="error"><p class="error">'.sprintf( T_("No default time zone is set. Please open PHP.ini and set the value of 'date.timezone' (Example: date.timezone = Europe/Paris) or open /conf/_advanced.php and set the value of %s (Example: %s)"), '$date_default_timezone', '$date_default_timezone = \'Europe/Paris\';' ).'</p></div>';
}

if( ( $config_is_done || $try_db_connect ) && ( $DB->error ) )
{ // DB connect was unsuccessful, restart conf
	echo '<div class="error"><p class="error">'.T_('Check your database config settings below and update them if necessary...').'</p></div>';
	display_base_config_recap();
	$action = 'start';
}

// Check other dependencies:
// TODO: Non-install/upgrade-actions should be allowed (e.g. "deletedb")
if( $req_errors = install_validate_requirements() )
{
	echo '<div class="error">';
	echo '<p class="error"><strong>'.T_('b2evolution cannot be installed, because of the following errors:').'</strong></p>';
	echo '<ul class="error"><li>'.implode( '</li><li>', $req_errors ).'</li></ul>';
	echo '</div>';
	die;
}

switch( $action )
{
	case 'conf':
		/*
		 * -----------------------------------------------------------------------------------
		 * Write conf file:
		 * -----------------------------------------------------------------------------------
		 */
		display_locale_selector();

		block_open();

		param( 'conf_db_user', 'string', true );
		param( 'conf_db_password', 'raw', true );
		param( 'conf_db_name', 'string', true );
		param( 'conf_db_host', 'string', true );
		param( 'conf_db_tableprefix', 'string', $tableprefix );
		param( 'conf_baseurl', 'string', true );
		$conf_baseurl = preg_replace( '#(/)?$#', '', $conf_baseurl ).'/'; // force trailing slash
		param( 'conf_admin_email', 'string', true );

		// Connect to DB:
		$DB = new DB( array(
			'user' => $conf_db_user,
			'password' => $conf_db_password,
			'name' => $conf_db_name,
			'host' => $conf_db_host,
			'aliases' => $db_config['aliases'],
			'use_transactions' => $db_config['use_transactions'],
			'table_options' => $db_config['table_options'],
			'connection_charset' => $db_config['connection_charset'],
			'halt_on_error' => false ) );
		if( $DB->error )
		{ // restart conf
			echo '<p class="error">'.T_('It seems that the database config settings you entered don\'t work. Please check them carefully and try again...').'</p>';
			$action = 'start';
		}
		else
		{
			$conf_template_filepath = $conf_path.'_basic_config.template.php';
			$conf_filepath = $conf_path.'_basic_config.php';

			// Read original:
			$file_loaded = @file( $conf_template_filepath );

			if( empty( $file_loaded ) )
			{ // This should actually never happen, just in case...
				echo '<div class="error"><p class="error">'.sprintf( T_('Could not load original conf file [%s]. Is it missing?'), $conf_filepath ).'</p></div>';
				break;
			}

			// File loaded...
			$conf = implode( '', $file_loaded );
			// Update conf:
			$conf = preg_replace(
				array(
					'#\$db_config\s*=\s*array\(
						\s*[\'"]user[\'"]\s*=>\s*[\'"].*?[\'"],     ([^\n\r]*\r?\n)
						\s*[\'"]password[\'"]\s*=>\s*[\'"].*?[\'"], ([^\n\r]*\r?\n)
						\s*[\'"]name[\'"]\s*=>\s*[\'"].*?[\'"],     ([^\n\r]*\r?\n)
						\s*[\'"]host[\'"]\s*=>\s*[\'"].*?[\'"],     ([^\n\r]*\r?\n)
						#ixs',
					"#tableprefix\s*=\s*'.*?';#",
					"#baseurl\s*=\s*'.*?';#",
					"#admin_email\s*=\s*'.*?';#",
					"#config_is_done\s*=.*?;#",
				),
				array(
					"\$db_config = array(\n"
						."\t'user'     => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $conf_db_user )."',\$1"
						."\t'password' => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $conf_db_password )."',\$2"
						."\t'name'     => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $conf_db_name )."',\$3"
						."\t'host'     => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $conf_db_host )."',\$4",
					"tableprefix = '".str_replace( "'", "\'", $conf_db_tableprefix )."';",
					"baseurl = '".str_replace( "'", "\'", $conf_baseurl )."';",
					"admin_email = '".str_replace( "'", "\'", $conf_admin_email )."';",
					'config_is_done = 1;',
				), $conf );

			// Write new contents:
			if( save_to_file( $conf, $conf_filepath, 'w' ) )
			{
				printf( '<p>'.T_('Your configuration file [%s] has been successfully created.').'</p>', $conf_filepath );

				$tableprefix = $conf_db_tableprefix;
				$baseurl = $conf_baseurl;
				$admin_email = $conf_admin_email;
				$config_is_done = 1;
				$action = 'menu';
			}
			else
			{
				?>
				<h1><?php echo T_('Config file update') ?></h1>
				<p><strong><?php printf( T_('We cannot automatically create or update your config file [%s]!'), $conf_filepath ); ?></strong></p>
				<p><?php echo T_('There are two ways to deal with this:') ?></p>
				<ul>
					<li><strong><?php echo T_('You can allow the installer to create the config file by changing permissions for the /conf directory:') ?></strong>
						<ol>
							<li><?php printf( T_('Make sure there is no existing and potentially locked configuration file named <code>%s</code>. If so, please delete it.'), $conf_filepath ); ?></li>
							<li><?php printf( T_('<code>chmod 777 %s</code>. If needed, see the <a %s>online manual about permissions</a>.'), $conf_path, 'href="http://manual.b2evolution.net/Directory_and_file_permissions" target="_blank"' ); ?></li>
							<li><?php echo T_('Come back to this page and refresh/reload.') ?></li>
						</ol>
						<br />
					</li>
					<li><strong><?php echo T_('Alternatively, you can update the config file manually:') ?></strong>
						<ol>
							<li><?php echo T_('Create a new text file with a text editor.') ?></li>
							<li><?php echo T_('Copy the contents from the box below.') ?></li>
							<li><?php echo T_('Paste them into your local text editor. <strong>ATTENTION: make sure there is ABSOLUTELY NO WHITESPACE after the final <code>?&gt;</code> in the file.</strong> Any space, tab, newline or blank line at the end of the conf file may prevent cookies from being set when you try to log in later.') ?></li>
							<li><?php echo T_('Save the file locally under the name <code>_basic_config.php</code>') ?></li>
							<li><?php echo T_('Upload the file to your server, into the <code>/_conf</code> folder.') ?></li>
							<li><?php printf( T_('<a %s>Call the installer from scratch</a>.'), 'href="index.php?locale='.$default_locale.'"') ?></li>
						</ol>
					</li>
				</ul>
				<p><?php echo T_('This is how your _basic_config.php should look like:') ?></p>
				<blockquote>
				<pre><?php
					echo htmlspecialchars( $conf );
				?></pre>
				</blockquote>
				<?php
				break;
			}
		}
		// ATTENTION: we continue here...

	case 'start':
	case 'default':
		/*
		 * -----------------------------------------------------------------------------------
		 * Start of install procedure:
		 * -----------------------------------------------------------------------------------
		 */
		if( $action == 'start' || !$config_is_done )
		{
			display_locale_selector();

			block_open();

			echo '<h1>'.T_('Base configuration').'</h1>';

			if( $config_is_done && $allow_evodb_reset != 1 )
			{
				echo '<p><strong>'.T_('Resetting the base configuration is currently disabled for security reasons.').'</strong></p>';
				echo '<p>'.sprintf( T_('To enable it, please go to the %s file and change: %s to %s'), '/conf/_basic_config.php', '<pre>$allow_evodb_reset = 0;</pre>', '<pre>$allow_evodb_reset = 1;</pre>' ).'</p>';
				echo '<p>'.T_('Then reload this page and a reset option will appear.').'</p>';
				block_close();
				break;
			}
			else
			{

			// Set default params if not provided otherwise:
			param( 'conf_db_user', 'string', $db_config['user'] );
			param( 'conf_db_password', 'raw', $db_config['password'] );
			param( 'conf_db_name', 'string', $db_config['name'] );
			param( 'conf_db_host', 'string', $db_config['host'] );
			param( 'conf_db_tableprefix', 'string', $tableprefix );
			// Guess baseurl:
			// TODO: dh> IMHO HTTP_HOST would be a better default, because it's what the user accesses for install.
			//       fp, please change it, if it's ok. SERVER_NAME might get used if HTTP_HOST is not given, but that shouldn't be the case normally.
			// fp> ok for change and test after first 3.x-stable release
			$baseurl = 'http://'.( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'yourserver.com' );
			if( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) )
				$baseurl .= ':'.$_SERVER['SERVER_PORT'];

			// ############ Get ReqPath & ReqURI ##############
			list($ReqPath,$ReqURI) = get_ReqURI();

			$baseurl .= preg_replace( '#/install(/(index.php)?)?$#', '', $ReqPath ).'/';

			param( 'conf_baseurl', 'string', $baseurl );
			param( 'conf_admin_email', 'string', $admin_email );

			?>

			<p><?php echo T_('The basic configuration file (<code>/conf/_basic_config.php</code>) has not been created yet. You can do automatically generate it by filling out the form below.') ?></p>

			<p><?php echo T_('This is the minimum info we need to set up b2evolution on this server:') ?></p>

			<form class="fform" name="form" action="index.php" method="post">
				<input type="hidden" name="action" value="conf" />
				<input type="hidden" name="locale" value="<?php echo $default_locale; ?>" />

				<fieldset>
					<legend><?php echo T_('Database you want to install into') ?></legend>
					<p class="note"><?php echo T_('b2evolution stores blog posts, comments, user permissions, etc. in a MySQL database. You must create this database prior to installing b2evolution and provide the access parameters to this database below. If you are not familiar with this, you can ask your hosting provider to create the database for you.') ?></p>
					<?php
						form_text( 'conf_db_host', $conf_db_host, 16, T_('MySQL Host/Server'), sprintf( T_('Typically looks like "localhost" or "sql-6" or "sql-8.yourhost.net"...' ) ), 120 );
						form_text( 'conf_db_name', $conf_db_name, 16, T_('MySQL Database'), sprintf( T_('Name of the MySQL database you have created on the server' ) ), 100);
						form_text( 'conf_db_user', $conf_db_user, 16, T_('MySQL Username'), sprintf( T_('Used by b2evolution to access the MySQL database' ) ), 100 );
						form_text( 'conf_db_password', $conf_db_password, 16, T_('MySQL Password'), sprintf( T_('Used by b2evolution to access the MySQL database' ) ), 100 ); // no need to hyde this. nobody installs b2evolution from a public place
						// Too confusing for (most) newbies.	form_text( 'conf_db_tableprefix', $conf_db_tableprefix, 16, T_('MySQL tables prefix'), sprintf( T_('All DB tables will be prefixed with this. You need to change this only if you want to have multiple b2evo installations in the same DB.' ) ), 30 );
					?>
				</fieldset>

				<fieldset>
					<legend><?php echo T_('Additional settings') ?></legend>
					<?php
						form_text( 'conf_baseurl', $conf_baseurl, 50, T_('Base URL'), sprintf( T_('This is where b2evo and your blogs reside by default. CHECK THIS CAREFULLY or not much will work. If you want to test b2evolution on your local machine, in order for login cookies to work, you MUST use http://<strong>localhost</strong>/path... Do NOT use your machine\'s name!' ) ), 120 );

						form_text( 'conf_admin_email', $conf_admin_email, 50, T_('Your email'), sprintf( T_('This is used to create your admin account. You will receive notifications for comments on your blog, etc.' ) ), 80 );
					?>
				</fieldset>

				<fieldset>
					<fieldset>
						<div class="input">
							<input type="submit" name="submit" value="<?php echo T_('Update config file') ?>" class="search" />
							<input type="reset" value="<?php echo T_('Reset') ?>" class="search" />
						</div>
					</fieldset>
				</fieldset>

			</form>

			<?php
			break;
			}
		}
		// if config was already done, move on to main menu:

	case 'menu':
		/*
		 * -----------------------------------------------------------------------------------
		 * Menu
		 * -----------------------------------------------------------------------------------
		 */

		display_locale_selector();

		block_open();
		?>
		<h1><?php echo T_('How would you like your b2evolution installed?') ?></h1>

		<?php
			$old_db_version = get_db_version();
		?>

		<form action="index.php" method="get">
			<input type="hidden" name="locale" value="<?php echo $default_locale ?>" />
			<input type="hidden" name="confirmed" value="0" />
			<input type="hidden" name="installer_version" value="10" />

			<p><?php echo T_('The installation can be done in different ways. Choose one:')?></p>

			<p><input type="radio" name="action" id="newdb" value="newdb"
				<?php
					// fp> change the above to 'newdbsettings' for an additional settings screen.
					if( is_null($old_db_version) )
					{
						echo 'checked="checked"';
					}
				?>
				/>
				<label for="newdb"><?php echo T_('<strong>New Install</strong>: Install b2evolution database tables.')?></label></p>
			<p style="margin-left: 2em;">
				<input type="checkbox" name="create_sample_contents" id="create_sample_contents" value="1" checked="checked" />
				<label for="create_sample_contents"><?php echo T_('Also install sample blogs &amp; sample contents. The sample posts explain several features of b2evolution. This is highly recommended for new users.')?></label>
				<br />
				<?php
					// Pre-check if current installation is local
					$is_local = php_sapi_name() != 'cli' && // NOT php CLI mode
						( $_SERVER['SERVER_ADDR'] == '127.0.0.1' ||
							$_SERVER['SERVER_ADDR'] == '::1' || // IPv6 address of 127.0.0.1
							$basehost == 'localhost' ||
							$_SERVER['REMOTE_ADDR'] == '127.0.0.1' ||
							$_SERVER['REMOTE_ADDR'] == '::1' );
				?>
				<input type="checkbox" name="local_installation" id="local_installation" value="1"<?php echo $is_local ? ' checked="checked"' : ''; ?> />
				<label for="local_installation"><?php echo T_('This is a local / test / intranet installation.')?></label>
				<?php
					if( $test_install_all_features )
					{	// Checkbox to install all features
				?>
				<br />
				<input type="checkbox" name="install_all_features" id="install_all_features" value="1" />
				<label for="install_all_features"><?php echo T_('Also install all test features.')?></label>
				<?php } ?>
			</p>

			<p><input type="radio" name="action" id="evoupgrade" value="evoupgrade"
				<?php if( !is_null($old_db_version) && $old_db_version < $new_db_version )
					{
						echo 'checked="checked"';
					}
				?>
				/>
				<label for="evoupgrade"><?php echo T_('<strong>Upgrade from a previous version of b2evolution</strong>: Upgrade your b2evolution database tables in order to make them compatible with the current version. <strong>WARNING:</strong> If you have modified your database, this operation may fail. Make sure you have a backup.') ?></label></p>

			<?php
				if( $allow_evodb_reset == 1 )
				{
					?>
					<p><input type="radio" name="action" id="deletedb" value="deletedb" />
					<label for="deletedb"><strong><?php echo T_('Delete b2evolution tables &amp; cache files')?></strong>:
					<?php echo T_('If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. <strong>WARNING: All your b2evolution tables and data will be lost!!!</strong> Any non-b2evolution tables will remain untouched though.')?></label></p>

					<p><input type="radio" name="action" id="start" value="start" />
					<label for="start"><?php echo T_('<strong>Change your base configuration</strong> (see recap below): You only want to do this in rare occasions where you may have moved your b2evolution files or database to a different location...')?></label></p>
					<?php
				}


			if( $allow_evodb_reset != 1 )
			{
				echo '<div class="floatright"><a href="index.php?action=deletedb&amp;locale='.$default_locale.'">'.T_('Need to start anew?').' &raquo;</a></div>';
			}
			?>

			<p>
			<input type="submit" value="&nbsp; <?php echo T_('GO!')?> &nbsp;"
				onclick="var dc = document.getElementById( 'deletedb' ); if( dc && dc.checked ) { if ( confirm( '<?php
					printf( /* TRANS: %s gets replaced by app name, usually "b2evolution" */ TS_( 'Are you sure you want to delete your existing %s tables?\nDo you have a backup?' ), $app_name );
					?>' ) ) { this.form.confirmed.value = 1; return true; } else return false; }" />
			</p>
			</form>
		<?php

		block_close();

		display_base_config_recap();
		break;

	case 'localeinfo':
		// Info about getting additional locales.
		display_locale_selector();

		block_open();

		// Note: Do NOT make these strings translatable. We are not in the desired language anyways!
		?>
		<h2>What if your language is not in the list above?</h2>
		<ol>
			<li>Go to the <a href="http://b2evolution.net/downloads/language-packs.html" target="_blank">language packs section on b2evolution.net</a>.</li>
			<li>Select the version of b2evolution you're trying to install. If it's not available select the closest match (in most cases this should work).</li>
			<li>Find your language and click the "Download" link.</li>
			<li>Unzip the contents of the downloaded ZIP file.</li>
			<li>Upload the new folder (for example es_ES) into the /locales folder on your server. (The /locales folder already contains a few locales such as de_DE, ru_RU, etc.)</li>
			<li>Reload this page. The new locale should now appear in the list at the top of this screen. If it doesn't, it means the language pack you installed is not compatible with this version of b2evolution.</li>
		</ol>

		<h3>What if there is no language pack to download?</h3>
		<p>Nobody has contributed a language pack in your language yet. You could help by providing a translation for your language.</p>
		<p>For now, you will have to install b2evolution with a supported language.</p>
		<p>Once you get familiar with b2evolution you will be able to <a href="http://b2evolution.net/man/localization" target="_blank">create your own language pack</a> fairly easily.</p>
		<p><a href="index.php?locale=<?php echo $default_locale ?>">&laquo; <?php echo T_('Back to install menu') ?></a></p>
		<?php
		break;

	case 'newdbsettings':
		/*
		 * fp> TODO: Add a screen for additionnal settings:
		 * - create_sample_contents : to be moved away from main screen
		 * - admin_email: to be moved out of conf file
		 * - storage_charset: offer option to FORCE storing data in UTF-8 even if current locale doesn't require it (must be supported by MySQL) -- recommended for multilingual blogs
		 * - evo_charset: offer option to FORCE handling data internally in UTF-8 even if current locale doesn't require it (requires mbstring) -- not recommended in most situations
		 */


	case 'newdb':
		/*
		 * -----------------------------------------------------------------------------------
		 * NEW DB: install a new b2evolution database.
		 * -----------------------------------------------------------------------------------
		 * Note: auto installers should kick in directly at this step and provide all required params.
		 */

		$test_install_all_features = param( 'install_all_features', 'boolean', false );

		// fp> TODO: this test should probably be made more generic and applied to upgrade too.
		$expected_connection_charset = $DB->php_to_mysql_charmap($evo_charset);
		if( $DB->connection_charset != $expected_connection_charset )
		{
			echo '<div class="error"><p class="error">'.sprintf( T_('In order to install b2evolution with the %s locale, your MySQL needs to support the %s connection charset.').' (SET NAMES %s)',
				$current_locale, $evo_charset, $expected_connection_charset ).'</p></div>';
			// sam2kb> TODO: If something is not supported we can display a message saying "do this and that, enable extension X etc. etc... or switch to a better hosting".
			break;
		}

		if( $old_db_version = get_db_version() )
		{
			echo '<p><strong>'.T_('OOPS! It seems b2evolution is already installed!').'</strong></p>';

			if( $old_db_version < $new_db_version )
			{
				echo '<p>'.sprintf( T_('Would you like to <a %s>upgrade your existing installation now</a>?'), 'href="?action=evoupgrade"' ).'</p>';
			}

			break;
		}

		echo '<h2>'.T_('Installing b2evolution...').'</h2>';

		echo '<h2>'.T_('Checking files...').'</h2>';
		evo_flush();
		// Check for .htaccess:
		if( !install_htaccess( false ) )
		{	// Exit installation here because the .htaccess file has the some errors
			break;
		}

		// Here's the meat!
		install_newdb();
		break;


	case 'evoupgrade':
	case 'auto_upgrade':
	case 'svn_upgrade':
		/*
		 * -----------------------------------------------------------------------------------
		 * EVO UPGRADE: Upgrade data from existing b2evolution database
		 * -----------------------------------------------------------------------------------
		 */
		require_once( dirname(__FILE__). '/_functions_evoupgrade.php' );


		echo '<h2>'.T_('Upgrading b2evolution...').'</h2>';

		echo '<h2>'.T_('Checking files...').'</h2>';
		evo_flush();
		// Check for .htaccess:
		if( !install_htaccess( true ) )
		{	// Exit installation here because the .htaccess file has the some errors
			break;
		}

		// Try to obtain some serious time to do some serious processing (5 minutes)
		// NOte: this must NOT be in upgrade_b2evo_tables(), otherwise it will mess with the longer setting used by the auto upgrade feature.
		if( set_max_execution_time(300) === false )
		{ // max_execution_time ini setting could not be changed for this script, display a warning
			$manual_url = '"http://b2evolution.net/man/blank-or-partial-page" target = "_blank"';
			echo '<div class="orange">'.sprintf( T_('WARNING: the max_execution_time is set to %s seconds in php.ini and cannot be increased automatically. This may lead to a PHP <a href=%s>timeout causing the upgrade to fail</a>. If so please post a screenshot to the <a href=%s>forums</a>.'), ini_get( 'max_execution_time' ), $manual_url, '"http://forums.b2evolution.net/"' ).'</div>';
		}

		echo '<h2>'.T_('Upgrading data in existing b2evolution database...').'</h2>';
		evo_flush();

		$not_evoupgrade = ( $action !== 'evoupgrade' );
		if( upgrade_b2evo_tables( $action ) )
		{
			if( $not_evoupgrade )
			{ // After successful auto_upgrade or svn_upgrade we must remove files/folder based on the upgrade_policy.conf
				remove_after_upgrade();
				// disable maintenance mode at the end of the upgrade script
				switch_maintenance_mode( false, 'upgrade' );
			}
			?>
			<p><?php echo T_('Upgrade completed successfully!')?></p>
			<p><?php printf( T_('Now you can <a %s>log in</a> with your usual %s username and password.'), 'href="'.$admin_url.'"', 'b2evolution')?></p>
			<?php
		}
		else
		{
			if( $not_evoupgrade )
			{ // disable maintenance mode at the end of the upgrade script
				switch_maintenance_mode( false, 'upgrade' );
			}
			?>
			<p class="red"><?php echo T_('Upgrade failed!')?></p>
			<?php
		}

		break;


	case 'deletedb':
		/*
		 * -----------------------------------------------------------------------------------
		 * DELETE DB: Delete the db structure!!! (Everything will be lost)
		 * -----------------------------------------------------------------------------------
		 */
		require_once( dirname(__FILE__). '/_functions_delete.php' );

		echo '<h2>'.T_('Deleting b2evolution tables from the datatase...').'</h2>';
		evo_flush();

		if( $allow_evodb_reset != 1 )
		{
			echo T_('If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. b2evolution can delete its own tables for you, but for obvious security reasons, this feature is disabled by default.');
			echo '<p>'.sprintf( T_('To enable it, please go to the %s file and change: %s to %s'), '/conf/_basic_config.php', '<pre>$allow_evodb_reset = 0;</pre>', '<pre>$allow_evodb_reset = 1;</pre>' ).'</p>';
			echo '<p>'.T_('Then reload this page and a reset option will appear.').'</p>';
			echo '<p><a href="index.php?locale='.$default_locale.'">&laquo; '.T_('Back to install menu').'</a></p>';

			break;
		}

		if( ! param('confirmed', 'integer', 1) )
		{
			?>
			<p>
			<?php
			echo nl2br( htmlspecialchars( sprintf( /* TRANS: %s gets replaced by app name, usually "b2evolution" */ T_( "Are you sure you want to delete your existing %s tables?\nDo you have a backup?" ), $app_name ) ) );
			?>
			</p>
			<p>
			<form class="inline" name="form" action="index.php" method="post">
				<input type="hidden" name="action" value="deletedb" />
				<input type="hidden" name="confirmed" value="1" />
				<input type="hidden" name="locale" value="<?php echo $default_locale; ?>" />
				<input type="submit" value="&nbsp; <?php echo T_('I am sure!')?> &nbsp;" />
			</form>

			<form class="inline" name="form" action="index.php" method="get">
				<input type="hidden" name="locale" value="<?php echo $default_locale; ?>" />
				<input type="submit" value="&nbsp; <?php echo T_('CANCEL')?> &nbsp;" />
			</form>
			</p>
			<?php
			break;
		}

		// Uninstall Plugins
		// TODO: fp>> I don't trust the plugins to uninstall themselves correctly. There will be tons of lousy poorly written plugins. All I trust them to do is to crash the uninstall procedure. We want a hardcore brute force uninsall! and most users "may NOT want" to even think about "ma-nu-al-ly" removing something from their DB.
		/*
				$DB->show_errors = $DB->halt_on_error = false;
				$Plugins = new Plugins();
				$DB->show_errors = $DB->halt_on_error = true;
				$at_least_one_failed = false;
				foreach( $Plugins->get_list_by_event( 'Uninstall' ) as $l_Plugin )
				{
					$success = $Plugins->call_method( $l_Plugin->ID, 'Uninstall', $params = array( 'unattended' => true ) );
					if( $success === false )
					{
						echo "Failed un-installing plugin $l_Plugin->classname (ID $l_Plugin->ID)...<br />\n";
						$at_least_one_failed = false;
					}
					else
					{
						echo "Uninstalled plugin $l_Plugin->classname (ID $l_Plugin->ID)...<br />\n";
					}
				}
				if( $at_least_one_failed )
				{
					echo "You may want to manually remove left files or DB tables from the failed plugin(s).<br />\n";
				}
				$DB->show_errors = $DB->halt_on_error = true;
		*/

		/* REMOVE PAGE CACHE */
		load_class( '_core/model/_pagecache.class.php', 'PageCache' );

		// Remove general page cache
		$PageCache = new PageCache( NULL );
		$PageCache->cache_delete();

		// Skip if T_blogs table is already deleted. Note that db_delete() will not throw any errors on missing tables.
		if( $DB->query('SHOW TABLES LIKE "T_blogs"') )
		{	// Get all blogs
			$blogs_SQL = new SQL();
			$blogs_SQL->SELECT( 'blog_ID' );
			$blogs_SQL->FROM( 'T_blogs' );
			$blogs = $DB->get_col( $blogs_SQL->get() );

			foreach( $blogs as $blog_ID )
			{
				$BlogCache = & get_BlogCache();
				$Blog = $BlogCache->get_by_ID( $blog_ID );

				// Remove page cache of current blog
				$PageCache = new PageCache( $Blog );
				$PageCache->cache_delete();
			}
		}

		/* REMOVE DATABASE */
		db_delete();
		?>
		<p><?php echo T_('Reset done!')?></p>
		<p><a href="index.php?locale=<?php echo $default_locale ?>">&laquo; <?php echo T_('Back to install menu') ?></a></p>
		<?php
		break;
}

block_close();
?>

<!-- InstanceEndEditable -->
	</div>

	<div class="body_fade_out">

	<div class="menu_bottom"><!-- InstanceBeginEditable name="MenuBottom" -->
			<?php echo T_('Online resources') ?>: <a href="http://b2evolution.net/" target="_blank"><?php echo T_('Official website') ?></a> &bull; <a href="http://b2evolution.net/about/recommended-hosting-lamp-best-choices.php" target="_blank"><?php echo T_('Find a host') ?></a> &bull; <a href="http://b2evolution.net/man/" target="_blank"><?php echo T_('Manual') ?></a> &bull; <a href="http://forums.b2evolution.net/" target="_blank"><?php echo T_('Forums') ?></a>
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