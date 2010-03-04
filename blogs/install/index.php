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
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */

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

$script_start_time = time();
$localtimenow = $script_start_time; // used e.g. for post_datemodified (sample posts)

if( ! $config_is_done )
{	// Base config is not done yet, try to guess some values needed for correct display:
	$rsc_url = '../rsc/';
}

require_once $inc_path.'_core/_class'.floor(PHP_VERSION).'.funcs.php';
require_once $inc_path.'_core/_misc.funcs.php';

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


require_once dirname(__FILE__).'/_functions_install.php';

$Timer = new Timer('main');

load_funcs('_core/_param.funcs.php');

// Let the modules load/register what they need:
modules_call_method( 'init' );


param( 'action', 'string', 'default' );

// Load all available locale defintions:
locales_load_available_defs();
param( 'locale', 'string' );
if( preg_match('/[a-z]{2}-[A-Z]{2}(-.{1,14})?/', $locale) )
{
	$default_locale = $locale;
}
else
{ // detect language
	$default_locale = locale_from_httpaccept();
	// echo 'detected locale: ' . $default_locale. '<br />';
}
// Activate default locale:
if( ! locale_activate( $default_locale ) )
{	// Could not activate locale (non-existent?), fallback to en-US:
	$default_locale = 'en-US';
	locale_activate( 'en-US' );
}

init_charsets( $current_charset );

$timestamp = time() - 120; // We start dates 2 minutes ago because their dates increase 1 second at a time and we want everything to be visible when the user watches the blogs right after install :P


switch( $action )
{
	case 'evoupgrade':
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

	default:
		$action = 'default';
		$title = '';
}

header('Content-Type: text/html; charset='.$io_charset);
header('Cache-Control: no-cache'); // no request to this page should get cached!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/evo_distrib_2.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title><?php echo T_('b2evo installer').( $title ? ': '.$title : '' ) ?></title>
	<!-- InstanceEndEditable -->
	<meta name="viewport" content="width = 750" />
	<meta name="robots" content="noindex, follow" />
	<link href="../rsc/css/evo_distrib_2.css" rel="stylesheet" type="text/css" />
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

if( $config_is_done || (($action != 'start') && ($action != 'default') && ($action != 'conf')) )
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

	if( $DB->error )
	{ // restart conf
		echo '<div class="error"><p class="error">'.T_('Check your database config settings below and update them if necessary...').'</p></div>';
		display_base_config_recap();
		$action = 'start';
	}
	else
	{
		$DB->halt_on_error = true;  // From now on, halt on errors.
		$DB->show_errors = true;    // From now on, show errors (they're helpful in case of errors!).

		// Check MySQL version
		$mysql_version = $DB->get_var( 'SELECT VERSION()' );
		list( $mysl_version_main, $mysl_version_minor ) = explode( '.', $mysql_version );
		if( ($mysl_version_main * 100 + $mysl_version_minor) < 401 )
		{
			die( '<div class="error"><p class="error"><strong>'.sprintf(T_('The minimum requirement for this version of b2evolution is %s version %s but you are trying to use version %s!'), 'MySQL', '4.1', $mysql_version ).'</strong></p></div>');
		}
	}
}

// Check PHP version
list( $version_main, $version_minor ) = explode( '.', phpversion() );
if( ($version_main * 100 + $version_minor) < 401 )
{
	die( '<div class="error"><p class="error"><strong>'.sprintf(T_('The minimum requirement for this version of b2evolution is %s version %s but you are trying to use version %s!'), 'PHP', '4.1.0', phpversion() ).'</strong></p></div>');
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
						."\t'user'     => '".str_replace( "'", "\'", $conf_db_user )."',\$1"
						."\t'password' => '".str_replace( "'", "\'", $conf_db_password )."',\$2"
						."\t'name'     => '".str_replace( "'", "\'", $conf_db_name )."',\$3"
						."\t'host'     => '".str_replace( "'", "\'", $conf_db_host )."',\$4",
					"tableprefix = '".str_replace( "'", "\'", $conf_db_tableprefix )."';",
					"baseurl = '".str_replace( "'", "\'", $conf_baseurl )."';",
					"admin_email = '".str_replace( "'", "\'", $conf_admin_email )."';",
					'config_is_done = 1;',
				), $conf );

			$f = @fopen( $conf_filepath , 'w' );
			if( $f == false )
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
			else
			{ // Write new contents:
				fwrite( $f, $conf );
				fclose($f);

				printf( '<p>'.T_('Your configuration file [%s] has been successfully created.').'</p>', $conf_filepath );

				$tableprefix = $conf_db_tableprefix;
				$baseurl = $conf_baseurl;
				$admin_email = $conf_admin_email;
				$config_is_done = 1;
				$action = 'menu';
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
					<label for="deletedb"><strong><?php echo T_('Delete b2evolution tables')?></strong>:
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
		<p>Once you get familiar with b2evolution you will be able to <a href="http://manual.b2evolution.net/Localization" target="_blank">create your own language pack</a> fairly easily.</p>
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

		echo '<h2>'.T_('Checking files...').'</h2>';
		flush();
		// Check for .htaccess:
		install_htaccess( false );

		// Here's the meat!
		install_newdb();
		break;


	case 'evoupgrade':
		/*
		 * -----------------------------------------------------------------------------------
		 * EVO UPGRADE: Upgrade data from existing b2evolution database
		 * -----------------------------------------------------------------------------------
		 */
		require_once( dirname(__FILE__). '/_functions_evoupgrade.php' );


		echo '<h2>'.T_('Checking files...').'</h2>';
		flush();
		// Check for .htaccess:
		install_htaccess( true );

		echo '<h2>'.T_('Upgrading data in existing b2evolution database...').'</h2>';
		flush();
		if( upgrade_b2evo_tables() )
		{
			?>
			<p><?php echo T_('Upgrade completed successfully!')?></p>
			<p><?php printf( T_('Now you can <a %s>log in</a> with your usual %s username and password.'), 'href="'.$admin_url.'"', 'b2evolution')?></p>
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
		flush();

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
			<?php echo T_('Online resources') ?>: <a href="http://b2evolution.net/" target="_blank"><?php echo T_('Official website') ?></a> &bull; <a href="http://b2evolution.net/about/recommended-hosting-lamp-best-choices.php" target="_blank"><?php echo T_('Find a host') ?></a> &bull; <a href="http://manual.b2evolution.net/" target="_blank"><?php echo T_('Manual') ?></a> &bull; <a href="http://forums.b2evolution.net/" target="_blank"><?php echo T_('Forums') ?></a>
		<!-- InstanceEndEditable --></div>

	<div class="copyright"><!-- InstanceBeginEditable name="CopyrightTail" -->Copyright &copy; 2003-2010 by Fran&ccedil;ois Planque &amp; others &middot; <a href="http://b2evolution.net/about/license.html" target="_blank">GNU GPL license</a> &middot; <a href="http://b2evolution.net/contact/" target="_blank">Contact</a>
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


<?php
/*
 * $Log$
 * Revision 1.196  2010/03/04 18:02:55  fplanque
 * Cleaned up .htaccess install
 *
 * Revision 1.195  2010/02/08 17:55:42  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.194  2010/01/28 03:42:19  fplanque
 * minor
 *
 * Revision 1.193  2010/01/25 18:18:25  efy-asimo
 * .htaccess automatic install
 *
 * Revision 1.192  2010/01/21 22:49:10  blueyed
 * Installer: sanitize $action always. Add marker with the action done into the footer, used by the automatic installer.
 *
 * Revision 1.191  2009/12/22 08:45:44  fplanque
 * fix install
 *
 * Revision 1.190  2009/12/06 05:34:31  fplanque
 * Violent refactoring for _main.inc.php
 * Sorry for potential side effects.
 * This needed to be done badly -- for clarity!
 *
 * Revision 1.189  2009/11/30 00:22:05  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.188  2009/09/29 17:56:19  tblue246
 * minor
 *
 * Revision 1.187  2009/09/29 15:47:59  tblue246
 * Installer: Escape single quotes when writing config file
 *
 * Revision 1.186  2009/09/29 13:29:58  tblue246
 * Proper security fixes
 *
 * Revision 1.185  2009/09/29 03:38:34  fplanque
 * security rollback. NEVER EVER ALLOW UNFILTERED INPUTS. problem here: close password with single quote, then inject PHP, no less!!!
 *
 * Revision 1.184  2009/09/28 20:02:43  tblue246
 * param()/$type parameter: Deprecate "" value in favor of (newly added) "raw".
 *
 * Revision 1.183  2009/09/28 17:48:09  tblue246
 * Bugfix: Allow <> chars in DB password
 *
 * Revision 1.182  2009/09/16 01:33:36  fplanque
 * so noone complained about HEAD not (really) being installable?
 *
 * Revision 1.181  2009/09/14 14:31:16  waltercruz
 * minor fix
 *
 * Revision 1.180  2009/09/14 14:10:15  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.179  2009/07/16 17:14:22  fplanque
 * doc
 *
 * Revision 1.178  2009/07/16 17:09:54  fplanque
 * noindex doc
 * added info for noobs
 *
 * Revision 1.177  2009/07/15 11:58:16  tblue246
 * - Installer:
 * 	- Check if the selected locale could be activated and fallback to en-US if not.
 * 	- Commented out table prefix option, see mailing list/fplanque ("too complex for most newbies").
 * - Added _basic_config.php to conf/.cvsignore (will replace _config_TEST.php in the future).
 *
 * Revision 1.176  2009/07/14 23:24:04  sam2kb
 * activated table prefix fieldset
 *
 * Revision 1.175  2009/07/14 16:23:32  sam2kb
 * doc
 *
 * Revision 1.174  2009/07/14 15:48:00  fplanque
 * Thx to @slalaurette for finding this
 *
 * Revision 1.173  2009/07/13 19:17:09  tblue246
 * Fixed typos
 *
 * Revision 1.172  2009/07/12 18:41:58  fplanque
 * doc / help
 *
 * Revision 1.171  2009/07/11 19:43:35  tblue246
 * Translation fix
 *
 * Revision 1.170  2009/07/10 06:49:10  sam2kb
 * Made some strings translatable
 *
 * Revision 1.169  2009/07/09 23:45:43  fplanque
 * doc
 *
 * Revision 1.168  2009/07/09 23:23:41  fplanque
 * Check that DB supports proper charset before installing.
 *
 * Revision 1.167  2009/07/09 22:57:32  fplanque
 * Fixed init of connection_charset, especially during install.
 *
 * Revision 1.166  2009/07/06 23:52:25  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.165  2009/07/02 17:33:00  fplanque
 * only activate ONE locale at install time.
 *
 * Revision 1.164  2009/07/02 15:43:56  fplanque
 * B2evolution no longer ships with _basic_config.php .
 * It ships with _basic_config.template.php instead.
 * That way, uploading a new release never overwrites the previous base config.
 * The installer now creates  _basic_config.php based on _basic_config.template.php + entered form values.
 *
 * Revision 1.163  2009/07/02 14:53:07  fplanque
 * improved some more
 *
 * Revision 1.162  2009/07/02 13:41:38  fplanque
 * fix
 *
 * Revision 1.161  2009/07/02 13:35:23  fplanque
 * Improved installer -- language/locale selection moved to a place where it's visible!
 *
 * Revision 1.160  2009/03/08 23:57:47  fplanque
 * 2009
 *
 * Revision 1.159  2009/03/05 23:38:53  blueyed
 * Merge autoload branch (lp:~blueyed/b2evolution/autoload) into CVS HEAD.
 *
 * Revision 1.158  2009/02/28 18:45:11  fplanque
 * quick cleanup of the installer
 *
 * Revision 1.157  2009/02/27 22:25:16  blueyed
 * Fix inclusion of misc.funcs. Includes load_funcs now after all.
 *
 * Revision 1.156  2009/02/26 22:33:22  blueyed
 * Fix messup in last commit.
 *
 * Revision 1.155  2009/02/26 22:16:54  blueyed
 * Use load_class for classes (.class.php), and load_funcs for funcs (.funcs.php)
 *
 * Revision 1.154  2009/02/17 16:00:25  blueyed
 * Fix doc
 *
 * Revision 1.153  2009/02/12 19:59:41  blueyed
 * - Install: define $localtimenow, so post_datemodified gets set correctly.
 * - Send Cache-Control: no-cache for install/index.php: should not get cached, e.g. when going back to "delete", it should delete!?
 * - indent fixes
 *
 * Revision 1.152  2009/01/28 21:39:10  fplanque
 * Fixed locale selection during install
 *
 * Revision 1.151  2009/01/22 23:26:45  blueyed
 * Fix install-myself test (and stuff around it). Move 'newdb' action from install/index.php to functions_install.php to call it the same as during real install.
 *
 * Revision 1.150  2008/12/22 01:56:54  fplanque
 * minor
 *
 * Revision 1.149  2008/10/05 09:59:30  tblue246
 * fixing log...
 *
 * Revision 1.148  2008/10/04 23:47:32  tblue246
 * reverting to rev 1.146
 *
 * Revision 1.147  2008/10/04 21:44:15  tblue246
 * Set a random $instance_name on installation.
 *
 * Revision 1.146  2008/09/27 00:05:54  fplanque
 * minor/version bump
 *
 * Revision 1.145  2008/09/15 11:01:15  fplanque
 * Installer now creates a demo photoblog
 *
 * Revision 1.144  2008/02/19 11:11:20  fplanque
 * no message
 *
 * Revision 1.143  2008/02/07 00:35:52  fplanque
 * cleaned up install
 *
 * Revision 1.142  2008/01/21 15:00:00  fplanque
 * let browser autodetect charset (russian utf8!!)
 *
 * Revision 1.141  2008/01/21 09:35:38  fplanque
 * (c) 2008
 *
 * Revision 1.140  2008/01/04 19:59:59  blueyed
 * Use relative path for locale flags; trim whitespace
 *
 * Revision 1.139  2007/10/08 21:31:23  fplanque
 * auto install doc
 *
 * Revision 1.138  2007/09/23 18:55:17  fplanque
 * attempting to debloat. The Log class is insane.
 *
 * Revision 1.137  2007/09/19 02:54:16  fplanque
 * bullet proof upgrade
 *
 * Revision 1.136  2007/07/14 02:44:22  fplanque
 * New default page design.
 *
 * Revision 1.135  2007/07/14 00:24:53  fplanque
 * New installer design.
 *
 * Revision 1.134  2007/07/01 18:47:11  fplanque
 * fixes
 *
 * Revision 1.133  2007/06/25 11:02:31  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.132  2007/06/24 18:28:55  fplanque
 * refactored skin install
 *
 * Revision 1.129  2007/06/12 21:00:02  blueyed
 * Added non-JS handling of deletedb confirmation
 *
 * Revision 1.128  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.127  2007/01/20 01:44:22  blueyed
 * typo
 *
 * Revision 1.126  2007/01/15 19:10:29  fplanque
 * install refactoring
 *
 * Revision 1.125  2007/01/15 18:48:44  fplanque
 * allow blank install.
 *
 * Revision 1.124  2007/01/15 03:53:24  fplanque
 * refactoring / simplified installer
 *
 * Revision 1.123  2007/01/14 03:47:53  fplanque
 * killed upgrade from b2/cafelog
 * (if people haven't upgraded yet, there's little chance they ever will,
 * no need to maintain this. We also provide an upgrade path with 1.x)
 *
 * Revision 1.122  2007/01/12 02:40:26  fplanque
 * widget default params proof of concept
 * (param customization to be done)
 *
 * Revision 1.121  2007/01/08 02:11:56  fplanque
 * Blogs now make use of installed skins
 * next step: make use of widgets inside of skins
 *
 * Revision 1.120  2006/11/30 06:13:23  blueyed
 * Moved Plugins::install() and sort() galore to Plugins_admin
 *
 * Revision 1.119  2006/11/30 05:43:40  blueyed
 * Moved Plugins::discover() to Plugins_admin::discover(); Renamed Plugins_no_DB to Plugins_admin_no_DB (and deriving from Plugins_admin)
 *
 * Revision 1.118  2006/11/14 00:47:32  fplanque
 * doc
 *
 * Revision 1.117  2006/10/31 04:44:00  blueyed
 * Fixed cafelogupgrade
 *
 * Revision 1.116  2006/10/27 20:11:24  blueyed
 * TODO
 *
 * Revision 1.115  2006/10/14 20:50:29  blueyed
 * Define EVO_IS_INSTALLING for /install/ and use it in Plugins to skip "dangerous" but unnecessary instantiating of other Plugins
 *
 * Revision 1.114  2006/10/01 15:23:28  blueyed
 * Fixed install
 */
?>
