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
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
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
{	// Base config is not done yet, try to guess some values needed for correct display:
	$rsc_url = '../rsc/';
}

require_once $conf_path.'_upgrade.php';
require_once $misc_inc_path.'_log.class.php';
$Debuglog = new Log( 'note' );
require_once $misc_inc_path.'_misc.funcs.php'; // db funcs
require_once $inc_path.'_vars.inc.php';
require_once $misc_inc_path.'_db.class.php';
require_once $model_path.'collections/_blog.funcs.php';
require_once $model_path.'collections/_category.funcs.php';
require_once $model_path.'items/_item.class.php';
require_once $model_path.'items/_item.funcs.php';
require_once $misc_inc_path.'_form.funcs.php';
require_once $model_path.'users/_user.funcs.php';
require_once $misc_inc_path.'_timer.class.php';
require_once $misc_inc_path.'_plugins.class.php';
require_once dirname(__FILE__).'/_functions_install.php';
require_once dirname(__FILE__).'/_functions_create.php';

$Timer = & new Timer('main');

param( 'action', 'string', 'default' );
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
locale_activate( $default_locale );
$io_charset = locale_charset(false);

$timestamp = time() - 120; // We start dates 2 minutes ago because their dates increase 1 second at a time and we want everything to be visible when the user watches the blogs right after install :P

header('Content-Type: text/html; charset='.$io_charset);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php echo T_('b2evo installer') ?></title>
	<link href="../rsc/css/evo_distrib.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="rowheader" >
	<h1><a href="http://b2evolution.net/" title="b2evolution: Home"><img src="<?php echo $rsc_url; ?>img/b2evolution_logo.png" alt="b2evolution" width="472" height="102" /></a></h1>
	<div id="tagline"><?php echo T_('Multilingual multiuser multi-blog engine.') ?></div>
	<h1 id="version"><?php echo T_('Installer for version '), ' ', $app_version ?></h1>
	<div id="quicklinks">
		<?php echo T_('Current installation') ?>:
		<a href="index.php?locale=<?php echo $default_locale ?>"><?php echo T_('Install menu') ?></a> &middot;
		<a href="phpinfo.php"><?php echo T_('PHP info') ?></a> &middot;
		<a href="../index.php"><?php echo T_('Go to Blogs') ?></a> &middot;
		<a href="../admin.php"><?php echo T_('Go to Admin') ?></a> &middot;
		<?php echo T_('Online') ?>:
		<a href="http://b2evolution.net/man/"><?php echo T_('Manual') ?></a> &middot;
		<a href="http://b2evolution.net/man/supportfaq.html"><?php echo T_('Support') ?></a>
	</div>
</div>


<?php
// Locales selector:
if( ($action == 'start') || ($action == 'default') || ($action == 'conf') || ($action == 'menu') )
{
	?>
	<div class="installSideBar">
	<h2><?php echo T_('Language / Locale')?></h2>
	<p><?php echo T_('Choose a default language/locale for your b2evo installation.')?></p>

	<ul>

	<?php
	// present available locales on first screen
	foreach( $locales as $lkey => $lvalue )
	{
		echo "\n<li>";
		if( $default_locale == $lkey ) echo '<strong>';
		echo ' <a href="index.php?action='.$action.'&amp;locale='.$lkey.'">';
		locale_flag( $lkey, 'w16px', 'flag', '', true, $rsc_url.'flags' );
		echo T_( $lvalue['name'] );
		echo '</a>';
		if( $default_locale == $lkey ) echo '</strong>';
		echo '</li>';

	}
	?>
	</ul>
	</div>
	<?php
}


if( $config_is_done || (($action != 'start') && ($action != 'default') && ($action != 'conf')) )
{ // Connect to DB:
	$tmp_evoconf_db = $db_config;
	// We want a friendly message if we can't connect:
	$tmp_evoconf_db['halt_on_error'] = false;
	$tmp_evoconf_db['show_errors'] = false;
	$DB = new DB( $tmp_evoconf_db );
	unset($tmp_evoconf_db);

	if( $DB->error )
	{ // restart conf
		// TODO: Use title/headline, or just:
		// Log::display( T_('MySQL error!'), '', T_('Check your database config settings below and update them if necessary...') );
		echo '<p class="error">'.T_('Check your database config settings below and update them if necessary...').'</p>';
		$action = 'start';
	}
	else
	{
		$DB->halt_on_error = true;  // From now on, halt on errors.
		$DB->show_errors = true;    // From now on, show errors (they're helpful in case of errors!).

		// Check MySQL version
		$mysql_version = $DB->get_var( 'SELECT VERSION()' );
		list( $mysl_version_main, $mysl_version_minor ) = explode( '.', $mysql_version );
		if( ($mysl_version_main * 100 + $mysl_version_minor) < 323 )
		{
			die( '<div class="error"><p class="error"><strong>'.sprintf(T_('The minimum requirement for this version of b2evolution is %s version %s but you are trying to use version %s!'), 'MySQL', '3.23', $mysql_version ).'</strong></p></div>');
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
	echo '<p class="error"><strong>'.'b2evolution cannot be installed, because of the following errors:'.'</strong></p>';
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
		param( 'conf_db_user', 'string', true );
		param( 'conf_db_password', 'string', true );
		param( 'conf_db_name', 'string', true );
		param( 'conf_db_host', 'string', true );
		param( 'conf_db_tableprefix', 'string', true );
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
			$conf_filepath = $conf_path.'_basic_config.php';
			// Read original:
			$conf = implode( '', file( $conf_filepath ) );

			if( empty( $conf ) )
			{ // This should actually never happen, just in case...
				printf( '<p class="error">Could not load original conf file [%s]. Is it missing?</p>', $conf_filepath );
				break;
			}

			// File loaded...
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
						."\t'user'     => '$conf_db_user',\$1"
						."\t'password' => '$conf_db_password',\$2"
						."\t'name'     => '$conf_db_name',\$3"
						."\t'host'     => '$conf_db_host',\$4",
					"tableprefix = '$conf_db_tableprefix';",
					"baseurl = '$conf_baseurl';",
					"admin_email = '$conf_admin_email';",
					'config_is_done = 1;',
				), $conf );

			$f = @fopen( $conf_filepath , 'w' );
			if( $f == false )
			{
				?>
				<h1><?php echo T_('Config file update') ?></h1>
				<p><strong><?php printf( T_('We cannot automatically update your config file [%s]!'), $conf_filepath ); ?></strong></p>
				<p><?php echo T_('There are two ways to deal with this:') ?></p>
				<ul>
					<li><strong><?php echo T_('You can allow the installer to update the config file by changing its permissions:') ?></strong>
						<ol>
							<li><?php printf( T_('<code>chmod 666 %s</code>. If needed, see the <a %s>online manual about permissions</a>.'), $conf_filepath, 'href="http://b2evolution.net/man/install/file_permissions.html" target="_blank"' ); ?></li>
							<li><?php echo T_('Come back to this page and refresh/reload.') ?></li>
						</ol>
						<br />
					</li>
					<li><strong><?php echo T_('Alternatively, you can update the config file manually:') ?></strong>
						<ol>
							<li><?php echo T_('Open the _basic_config.php file locally with a text editor.') ?></li>
							<li><?php echo T_('Delete all contents!') ?></li>
							<li><?php echo T_('Copy the contents from the box below.') ?></li>
							<li><?php echo T_('Paste them into your local text editor. <strong>ATTENTION: make sure there is ABSOLUTELY NO WHITESPACE after the final <code>?&gt;</code> in the file.</strong> Any space, tab, newline or blank line at the end of the conf file may prevent cookies from being set when you try to log in later.') ?></li>
							<li><?php echo T_('Save the new _basic_config.php file locally.') ?></li>
							<li><?php echo T_('Upload the file to your server, into the /_conf folder.') ?></li>
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

				printf( '<p>'.T_('Your configuration file [%s] has been successfully updated.').'</p>', $conf_filepath );

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
		if( (($action == 'start') && ($allow_evodb_reset == 1)) || (!$config_is_done) )
		{
			// Set default params if not provided otherwise:
			param( 'conf_db_user', 'string', $db_config['user'] );
			param( 'conf_db_password', 'string', $db_config['password'] );
			param( 'conf_db_name', 'string', $db_config['name'] );
			param( 'conf_db_host', 'string', $db_config['host'] );
			param( 'conf_db_tableprefix', 'string', $tableprefix );
			// Guess baseurl:
			$baseurl = 'http://'.( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'yourserver.com' );
			if( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) )
				$baseurl .= ':'.$_SERVER['SERVER_PORT'];
			$baseurl .= preg_replace( '#/install(/(index.php)?)?$#', '', $ReqPath ).'/';
			param( 'conf_baseurl', 'string', $baseurl );
			param( 'conf_admin_email', 'string', $admin_email );

			?>
			<h1><?php echo T_('Base configuration') ?></h1>

			<p><?php echo T_('Your base config file has not been edited yet. You can do this by filling in the form below.') ?></p>

			<p><?php echo T_('This is the minimum info we need to set up b2evolution on this server:') ?></p>

			<form class="fform" name="form" action="index.php" method="post">
				<input type="hidden" name="action" value="conf" />
				<input type="hidden" name="locale" value="<?php echo $default_locale; ?>" />

				<fieldset>
					<legend><?php echo T_('Database you want to install into') ?></legend>
					<?php
						form_text( 'conf_db_user', $conf_db_user, 16, T_('MySQL Username'), sprintf( T_('Your username to access the database' ) ), 100 );
						form_text( 'conf_db_password', $conf_db_password, 16, T_('MySQL Password'), sprintf( T_('Your password to access the database' ) ), 100, '', 'password' );
						form_text( 'conf_db_name', $conf_db_name, 16, T_('MySQL Database'), sprintf( T_('Name of the database you want to use' ) ), 100);
						form_text( 'conf_db_host', $conf_db_host, 16, T_('MySQL Host'), sprintf( T_('You probably won\'t have to change this' ) ), 120 );
						form_text( 'conf_db_tableprefix', $conf_db_tableprefix, 16, T_('MySQL tables prefix'), sprintf( T_('All DB tables will be prefixed with this. You need to change this only if you want to have multiple b2evo installations in the same DB.' ) ), 30 );
					?>
				</fieldset>

				<fieldset>
					<legend><?php echo T_('Additional settings') ?></legend>
					<?php
						form_text( 'conf_baseurl', $conf_baseurl, 50, T_('Base URL'), sprintf( T_('This is where b2evo and your blogs reside by default. CHECK THIS CAREFULLY or not much will work. If you want to test b2evolution on your local machine, in order for login cookies to work, you MUST use http://<strong>localhost</strong>/path... Do NOT use your machine\'s name!' ) ), 120 );

						form_text( 'conf_admin_email', $conf_admin_email, 50, T_('Your email'), sprintf( T_('Will be used in severe error messages so that users can contact you. You will also receive notifications for new user registrations.' ) ), 80 );
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
		// if config was already done, move on to main menu:

	case 'menu':
		/*
		 * -----------------------------------------------------------------------------------
		 * Menu
		 * -----------------------------------------------------------------------------------
		 */
		?>
		<h1><?php echo T_('How do you want to install b2evolution?') ?></h1>

		<form action="index.php" method="get">
			<input type="hidden" name="locale" value="<?php echo $default_locale ?>" />

			<p><?php echo T_('The installation can be done in different ways. Choose one:')?></p>

			<p><input type="radio" name="action" id="newdb" value="newdb" checked="checked" />
				<label for="newdb"><?php echo T_('<strong>New Install</strong>: Install b2evolution database tables with sample data.')?></label></p>

			<p><input type="radio" name="action" id="evoupgrade" value="evoupgrade" />
				<label for="evoupgrade"><?php echo T_('<strong>Upgrade from a previous version of b2evolution</strong>: Upgrade your b2evolution database tables in order to make them compatible with the current version. <strong>WARNING:</strong> If you have modified your database, this operation may fail. Make sure you have a backup.').' '.T_('<strong>NOTE:</strong> Your stats will be reset.')?></label></p>

			<p><input type="radio" name="action" id="cafelogupgrade" value="cafelogupgrade" />
				<label for="cafelogupgrade"><?php echo T_('<strong>Upgrade from Cafelog/b2 v 0.6.x</strong>: Install b2evolution database tables and copy your existing Cafelog/b2 data into them.')?></label></p>

			<?php
				if( $allow_evodb_reset == 1 )
				{
					?>
					<p><input type="radio" name="action" id="deletedb" value="deletedb" />
					<label for="deletedb"><strong><?php echo T_('Delete b2evolution tables')?></strong>:
					<?php echo T_('If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. <strong>WARNING: All your b2evolution tables and data will be lost!!!</strong> Your Cafelog/b2 or any other tables though, if you have some, will not be touched in any way.')?></label></p>

					<p><input type="radio" name="action" id="start" value="start" />
					<label for="start"><?php echo T_('<strong>Change your base configuration</strong> (see recap below): You only want to do this in rare occasions where you may have moved your b2evolution files or database to a different location...')?></label></p>
					<?php
				}
			?>

			<p>
			<input type="submit" value="&nbsp; <?php echo T_('GO!')?> &nbsp;"
				onclick="if( document.getElementById( 'deletedb' ).checked ) { return confirm( '<?php
					printf( TS_( 'Are you sure you want to delete your existing %s tables?\nDo you have a backup?' ), $app_name );
					?>' ); }" />
			</p>
			</form>
		<?php
		if( $allow_evodb_reset != 1 )
		{
			?>
			<br />
			<h2><?php echo T_('Need to start anew?') ?></h2>
			<p><?php echo T_('If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. b2evolution can delete its own tables for you, but for obvious security reasons, this feature is disabled by default.');
			echo '</p>';
			echo( '<p>To enable it, please go to the /conf/_basic_config.php file and change:</p>
<pre>$allow_evodb_reset = 0;</pre>
to
<pre>$allow_evodb_reset = 1;</pre>
<p>Then reload this page and a reset option will appear.</p>
<p>This will also allow you to change your base configuration.</p>');
		}
		?>

		<hr />
		<h2><?php echo T_('Base config recap...')?></h2>

		<p><?php printf( T_('If you don\'t see correct settings here, STOP before going any further, and <a %s>update your base configuration</a>.'), 'href="index.php?action=start&amp;locale='.$default_locale.'"' ) ?></p>

		<?php
		if( !isset($conf_db_user) ) $conf_db_user = $db_config['user'];
		if( !isset($conf_db_password) ) $conf_db_password = $db_config['password'];
		if( !isset($conf_db_name) ) $conf_db_name = $db_config['name'];
		if( !isset($conf_db_host) ) $conf_db_host = $db_config['host'];

		echo '<pre>',
		T_('MySQL Username').': '.$conf_db_user."\n".
		T_('MySQL Password').': '.(($conf_db_password != 'demopass' ? T_('(Set, but not shown for security reasons)') : 'demopass') )."\n".
		T_('MySQL Database').': '.$conf_db_name."\n".
		T_('MySQL Host').': '.$conf_db_host."\n".
		T_('MySQL tables prefix').': '.$tableprefix."\n\n".
		T_('Base URL').': '.$baseurl."\n\n".
		T_('Admin email').': '.$admin_email.
		'</pre>';
		break;


	case 'newdb':
		/*
		 * -----------------------------------------------------------------------------------
		 * NEW DB: Create a plain new db structure + sample contents
		 * -----------------------------------------------------------------------------------
		 */

		// Inserting sample data triggers events: instead of checking if $Plugins is an object there, just use a fake one..
		$Plugins = new Plugins_no_DB();
		?>
		<h2><?php echo T_('Installing b2evolution tables with sample data')?></h2>
		<?php
		create_b2evo_tables();
		populate_main_tables();
		install_basic_plugins();
		?>
		<h2><?php echo T_('Installation successful!')?></h2>

		<p>
		<strong>
		<?php
		printf( T_('Now you can <a %s>log in</a> with the login "admin" and password "%s".'), 'href="'.$admin_url.'"', $random_password );
		?>
		</strong>
		</p>

		<p>
		<?php
		echo T_('Note that password carefully! It is a <em>random</em> password that is given to you when you install b2evolution. If you lose it, you will have to delete the database tables and re-install anew.');
		?>
		</p>

		<?php
		break;


	case 'evoupgrade':
		/*
		 * -----------------------------------------------------------------------------------
		 * EVO UPGRADE: Upgrade data from existing b2evolution database
		 * -----------------------------------------------------------------------------------
		 */
		require_once( dirname(__FILE__). '/_functions_evoupgrade.php' );
		?>
		<h2><?php echo T_('Upgrading data in existing b2evolution database')?></h2>
		<?php
		if( upgrade_b2evo_tables() )
		{
			?>
			<p><?php echo T_('Upgrade completed successfully!')?></p>
			<p><?php printf( T_('Now you can <a %s>log in</a> with your usual %s username and password.'), 'href="'.$admin_url.'"', 'b2evolution')?></p>
			<?php
		}
		break;


	case 'cafelogupgrade':
		/*
		 * -----------------------------------------------------------------------------------
		 * UPGRADE FROM B2 : Create a new db structure + copy content from previous b2
		 * -----------------------------------------------------------------------------------
		 */
		require_once( dirname(__FILE__). '/_functions_cafelogupgrade.php' );
		?>
		<h2><?php printf( T_('Installing b2evolution tables and copying existing %s data'), 'b2' ) ?></h2>
		<?php
			create_b2evo_tables();
			upgrade_cafelog_tables();
			install_basic_plugins();
		?>
		<p><?php echo T_('Upgrade completed successfully!')?></p>
		<p><?php printf( T_('Now you can <a %s>log in</a> with your usual %s username and password.'), 'href="'.$admin_url.'"', 'b2')?></p>
		<?php
		break;


	case 'deletedb':
		/*
		 * -----------------------------------------------------------------------------------
		 * DELETE DB: Delete the db structure!!! (Everything will be lost)
		 * -----------------------------------------------------------------------------------
		 */
		require_once( dirname(__FILE__). '/_functions_delete.php' );
		?>
		<h2><?php echo T_('Deleting b2evolution tables from the datatase') ?></h2>
		<?php
		if( $allow_evodb_reset != 1 )
		{
			echo '<p>'.T_('For security reasons, the reset feature is disabled by default.' ).'</p>';
			echo( '<p>To enable it, please go to the /conf/_basic_config.php file and change:</p>
<pre>$allow_evodb_reset = 0;</pre>
to
<pre>$allow_evodb_reset = 1;</pre>
<p>Then reload this page and a reset option will appear.</p>');
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
		<p><a href="index.php?locale=<?php echo $default_locale ?>"><?php echo T_('Back to menu')?></a>.</p>
		<?php
		break;
}

?>

<div id="rowfooter">
	<a href="http://b2evolution.net/"><?php echo T_('official website') ?></a> &middot;
	<a href="http://b2evolution.net/about/license.html"><?php echo T_('GNU GPL license') ?></a> &middot;
	<a href="http://fplanque.net/About/index.html"><?php echo T_('contact') ?>: Fran&ccedil;ois PLANQUE</a>
</div>

<?php
	debug_info(); // output debug info if requested


	// the following comment gets checked in the automatic install script of demo.b2evolution.net:
?>
<!-- b2evo-install-end -->
</body>
</html>


<?php
/*
 * $Log$
 * Revision 1.106  2006/06/25 23:41:58  blueyed
 * The archive plugin requires Results itself now.
 *
 * Revision 1.105  2006/06/19 20:59:38  fplanque
 * noone should die anonymously...
 *
 * Revision 1.103  2006/06/14 17:26:13  fplanque
 * minor
 *
 * Revision 1.102  2006/05/30 21:53:06  blueyed
 * Replaced $EvoConfig->DB with $db_config
 *
 * Revision 1.101  2006/05/28 22:27:13  blueyed
 * Basic config file
 *
 * Revision 1.100  2006/05/19 18:15:06  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.99  2006/05/02 03:01:15  blueyed
 * fix
 *
 * Revision 1.98  2006/04/29 01:24:05  blueyed
 * More decent charset support;
 * unresolved issues include:
 *  - front office still forces the blog's locale/charset!
 *  - if there's content in utf8, it cannot get displayed with an I/O charset of latin1
 *
 * Revision 1.97  2006/04/11 22:28:58  blueyed
 * cleanup
 *
 * Revision 1.96  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 * Revision 1.95  2006/04/10 09:27:04  blueyed
 * Fix adding default itemtypes when upgrading from 0.9.x; cleaned up plugins install
 *
 * Revision 1.94  2006/04/06 08:52:27  blueyed
 * Validate install "misc" requirements ("tokenizer" support for now)
 *
 * Revision 1.93  2006/03/10 19:04:58  fplanque
 * minor
 *
 * Revision 1.92  2006/02/23 22:17:31  blueyed
 * fix path
 *
 * Revision 1.91  2006/02/23 21:12:33  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.89  2006/02/11 01:08:20  blueyed
 * Oh what fun it is to drop some "e".
 *
 * Revision 1.88  2006/02/03 19:36:40  fplanque
 * Log::display is insane compared to the simplicity of echo :]
 *
 * Revision 1.86  2006/02/02 00:49:33  blueyed
 * Use class Plugins_no_DB for $Plugins on "newdb" action
 *
 * Revision 1.85  2006/01/30 16:09:34  blueyed
 * doc
 *
 * Revision 1.83  2006/01/26 23:08:36  blueyed
 * Plugins enhanced.
 *
 * Revision 1.82  2006/01/14 20:45:10  blueyed
 * do not dump function trace on DB errors during install/config when not appropriate.
 *
 */
?>