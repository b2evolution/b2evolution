<?php
/**
 * This is the main install menu
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */

// include config and default functions
require_once( dirname(__FILE__). '/../conf/_config.php' );
require_once( dirname(__FILE__). "/$install_dirout/$core_subdir/_functions.php" ); // db funcs
require_once( dirname(__FILE__). "/$install_dirout/$core_subdir/_vars.php" );
require_once( dirname(__FILE__). "/$install_dirout/$core_subdir/_class_db.php" );
require_once( dirname(__FILE__). "/$install_dirout/$core_subdir/_functions.php" ); // db funcs
require_once( dirname(__FILE__). "/$install_dirout/$core_subdir/_functions_cats.php" );
require_once( dirname(__FILE__). "/$install_dirout/$core_subdir/_functions_bposts.php" );
require_once( dirname(__FILE__). "/$install_dirout/$core_subdir/_functions_forms.php" );
require_once( dirname(__FILE__). '/_functions_install.php' );
require_once( dirname(__FILE__). '/_functions_create.php' );

param( 'action', 'string', 'default' );
param( 'locale', 'string' );

if( preg_match('/[a-z]{2}-[A-Z]{2}(-.{1,14})?/', $locale) )
{
	$default_locale = $locale;
}
else		
{ // detect language
	$default_locale = locale_from_httpaccept();
	#echo 'detected locale: ' . $default_locale. '<br />';
}
// Activate default locale:
locale_activate( $default_locale );

$timestamp = time() - 120; // We start dates 2 minutes ago because their dates increase 1 second at a time and we want everything to be visible when the user watches the blogs right after install :P

$stub_all = 'blog_all';
$stub_a = 'blog_a';
$stub_b = 'blog_b';
$stub_roll = 'blog_roll';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo T_('b2evo installer') ?></title>
	<link href="../rsc/b2evo.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="rowheader" >
	<h1><a href="http://b2evolution.net/" title="b2evolution: Home"><img src="../img/b2evolution_logo.png" alt="b2evolution" width="472" height="102" border="0" /></a></h1>
	<div id="tagline"><?php echo T_('Multilingual multiuser multi-blog engine.') ?></div>
	<h1 id="version"><?php echo T_('Installer for version '), $b2_version ?></h1>
	<div id="quicklinks">
		<?php echo T_('Current installation') ?>: 
		<a href="index.php?locale=<?php echo $default_locale ?>"><?php echo T_('Install menu') ?></a> &middot; 
		<a href="phpinfo.php"><?php echo T_('PHP info') ?></a> &middot; 
		<a href="../index.php"><?php echo T_('Go to Blogs') ?></a> &middot; 
		<a href="../admin/"><?php echo T_('Go to Admin') ?></a> &middot; 
		<?php echo T_('Online') ?>: 
		<a href="http://b2evolution.net/man/"><?php echo T_('Manual') ?></a> &middot; 
		<a href="http://b2evolution.net/man/supportfaq.html"><?php echo T_('Support') ?></a> 
	</div>
</div>

<?php
if( ($action == 'start') || ($action == 'default') || ($action == 'menu') )
{
	?>
	<div class="installSideBar">
	<h2><?php echo T_('Language/Locale')?></h2>
	<p><?php echo T_('Choose a default language/locale for your b2evo installation.')?></p>
	
	<ul style="margin-left: 2ex;list-style:none;" >

	<?php
	// present available locales on first screen
	foreach( $locales as $lkey => $lvalue )
	{
		echo '<li>';
		if( $default_locale == $lkey ) echo '<strong>';	
		echo ' <a href="?action='.$action.'&amp;locale='.$lkey.'">';
		locale_flag( $lkey );
		echo T_( $lvalue['name'] );
		echo '</a></li>';
		
		if( $default_locale == $lkey ) echo '</strong>';	
	}
	?>
	</ul>
	</div>
	<?php
}
else
{
	// Connect to DB:
	$DB = new DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST, false );
	if( $DB->error )
	{	// restart conf
		echo '<p class="error">'.T_('Check your database config settings below and update them if necessary...').'</p>';
		$action = 'start';
	}
	$DB->halt_on_error = true;	// From now on, halt on errors.
}

// Check PHP version
list( $version_main, $version_minor ) = explode( '.', phpversion() );
if( ($version_main * 100 + $version_minor) < 401 )
{
	die( '<p class="error"><strong>'.sprintf(T_('The minimum requirement for this version of b2evolution is PHP version %s, but you have %s!'), '4.1.0', phpversion() ).'</strong></p>');
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
		param( 'conf_baseurl', 'string', true );
		$conf_baseurl = preg_replace( '#(/)?$#', '', $conf_baseurl ); // remove trailing slash
		param( 'conf_admin_email', 'string', true );

		// Connect to DB:
		$DB = new DB( $conf_db_user, $conf_db_password, $conf_db_name, $conf_db_host, false );
		if( $DB->error )
		{	// restart conf
			echo '<p class="error">'.T_('It seems that the database config settings you entered don\'t work. Please check them carefully and try again...').'</p>';
			$action = 'start';
		}
		else
		{ 
			$conf_filepath = $conf_path.'/_config.php';	
			// Read original:
			$conf = file( $conf_filepath );
			if( empty( $conf ) )
			{	// This should actually never happen, just in case...
				printf( '<p class="error">'.T_('Could not load original conf file [%s]. Is it missing?').'</p>', $conf_filepath );
				break;
			}
	
			// File loaded...
			// Update conf:
			$conf = preg_replace( 
														array(
																		"#(define\(\s*'DB_USER',\s*')(.*?)('\s*\);)#",
																		"#(define\(\s*'DB_PASSWORD',\s*')(.*?)('\s*\);)#",
																		"#(define\(\s*'DB_NAME',\s*')(.*?)('\s*\);)#",
																		"#(define\(\s*'DB_HOST',\s*')(.*?)('\s*\);)#",
																		"#(baseurl\s*=\s*')(.*?)(';)#",
																		"#(admin_email\s*=\s*')(.*?)(';)#",
																		"#config_is_done\s*=.*?;#",
																	), 
														array(
																		'$1'.$conf_db_user.'$3',
																		'$1'.$conf_db_password.'$3',
																		'$1'.$conf_db_name.'$3',
																		'$1'.$conf_db_host.'$3',
																		'$1'.$conf_baseurl.'$3',
																		'$1'.$conf_admin_email.'$3',
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
							<li><?php echo T_('Open the _config.php file locally with a text editor.') ?></li>
							<li><?php echo T_('Delete all contents!') ?></li>
							<li><?php echo T_('Copy the contents from the box below.') ?></li>
							<li><?php echo T_('Paste them into your local text editor. <strong>ATTENTION: make sure there is ABSOLUTELY NO WHITESPACE after the final <code>?&gt;</code> in the file.</strong> Any space, tab, newline or blank line at the end of the conf file may prevent cookies from being set when you try to log in later.') ?></li>
							<li><?php echo T_('Save the new _config.php file locally.') ?></li>
							<li><?php echo T_('Upload the file to your server, into the /_conf folder.') ?></li>
							<li><?php printf( T_('<a %s>Call the installer from scratch</a>.'), 'href="index.php?locale='.$default_locale.'"') ?></li>
						</ol>
					</li>
				</ul>
				<p><?php echo T_('This is how your _config.php should look like:') ?></p>
				<blockquote>
				<pre><?php 
					foreach( $conf as $conf_line )
					{
						echo htmlspecialchars( $conf_line );
					}
				?></pre>
				</blockquote>
				<?php
				break;
			}
			else
			{	// Write new contents:
				foreach( $conf as $conf_line )
				{
					fwrite( $f, $conf_line );
				}
				fclose($f);

				sprintf( '<p>'.T_('Your configuration file [%s] has been successfully updated.').'</p>', $conf_filepath );
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
		if( ($action == 'start') || (!$config_is_done) )
		{
			// Set default params if not provided otherwise:
			param( 'conf_db_user', 'string', DB_USER );
			param( 'conf_db_password', 'string', DB_PASSWORD );
			param( 'conf_db_name', 'string', DB_NAME );
			param( 'conf_db_host', 'string', DB_HOST );
			// Guess baseurl:
			$baseurl = 'http://'.( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'yourserver.com' );
			if( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) )
				$baseurl .= ':'.$_SERVER['SERVER_PORT'];
			$baseurl .= preg_replace( '#/install(/(index.php)?)?$#', '', $ReqPath );
			param( 'conf_baseurl', 'string', $baseurl );
			param( 'conf_admin_email', 'string', $admin_email );
		 
			?>
			<h1><?php echo T_('Base configuration') ?></h1>
			
			<p><?php echo T_('Your config file has not been edited yet. You can do this by filling in the form below.') ?></p>

			<p><?php echo T_('This is the minimum info we need to set up b2evolution on this server:') ?></p>

			<form class="fform" name="form" action="index.php" method="post">
				<input type="hidden" name="action" value="conf" />
				<input type="hidden" name="locale" value="<?php echo $default_locale; ?>" />
				
				<fieldset>
					<legend><?php echo T_('Database you want to install into (These settings should be provided by your host)') ?></legend>
					<?php
						form_text( 'conf_db_user', $conf_db_user, 16, T_('mySQL Username'), sprintf( T_('Your username to access the database' ) ), 16 );
						form_text( 'conf_db_password', $conf_db_password, 16, T_('mySQL Password'), sprintf( T_('Your password to access the database' ) ), 16 );
						form_text( 'conf_db_name', $conf_db_name, 16, T_('mySQL Database'), sprintf( T_('Name of the database you want to use' ) ), 16 );
						form_text( 'conf_db_host', $conf_db_host, 16, T_('mySQL Host'), sprintf( T_('You probably won\'t have to change this' ) ), 16 );
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
	  <h1><?php echo T_('What do you want to install?') ?></h1>
	  <form action="index.php" method="get">
			<input type="hidden" name="locale" value="<?php echo $default_locale ?>" />

			<p><?php echo T_('The database tables installation can be done in different ways. Choose one:')?></p>

			<p><input type="radio" name="action" id="newdb" value="newdb" checked="checked" />
				<label for="newdb"><?php echo T_('<strong>New Install</strong>: Install b2evolution database tables with sample data.')?></label></p>

			<p><input type="radio" name="action" id="evoupgrade" value="evoupgrade" />
				<label for="evoupgrade"><?php echo T_('<strong>Upgrade from a previous version of b2evolution</strong>: Upgrade your b2evolution database tables in order to make them compatible with the current version!')?></label></p>

			<p><input type="radio" name="action" id="cafelogupgrade" value="cafelogupgrade" />
				<label for="cafelogupgrade"><?php echo T_('<strong>Upgrade from Cafelog/b2 v 0.6.x</strong>: Install b2evolution database tables and copy your existing Cafelog/b2 data into them.')?></label></p>

			<?php
				if( $allow_evodb_reset == 1 )
				{
					?>
					<p><input type="radio" name="action" id="deletedb" value="deletedb" /> <strong><?php echo T_('Delete b2evolution tables')?></strong>:
					<label for="deletedb"><?php echo T_('If you have installed b2evolution tables before and wish to start anew, you must delete the
					b2evolution tables before you can start a new installation. <strong>WARNING: All your b2evolution
					tables and data will be lost!!!</strong> Your Cafelog/b2 or any other tables though, if you have
					some, will not be touched in any way.')?></label></p>
					<?php
				}
			?>
			
			<p><input type="submit" value="<?php echo T_('Install Database Tables Now !')?>" /></p>
		  </form>
	  <?php
		if( $allow_evodb_reset != 1 )
		{
			?>
			<br />
			<h2><?php echo T_('Need to start anew?') ?></h2>
			<?php echo T_('<p>If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. b2evolution can delete its own tables for you, but for obvious security reasons, this feature is disabled by default.</p>');
			echo( '<p>To enable it, please go to the /conf/_config.php file and change:</p>
<pre>$allow_evodb_reset = 0;</pre>
to
<pre>$allow_evodb_reset = 1;</pre>
<p>Then reload this page and a reset option will appear.</p>');
		}
		?>
		
		<hr /> 
		<h2><?php echo T_('Config file recap...')?></h2>
		
		<p><?php printf( T_('If you don\'t see correct settings here, STOP before going any further, and <a %s>redo your configuration</a>.'), 'href="index.php?action=start&amp;locale='.$default_locale.'"' ) ?></p>
	
		<?php echo '<pre>',
		T_('mySQL Username').': '.DB_USER."\n".
		T_('mySQL Password').': '.((DB_PASSWORD != 'demopass' ? T_('(Set, but not shown for security reasons)') : 'demopass') )."\n".
		T_('mySQL Database').': '.DB_NAME."\n".
		T_('mySQL Host').': '.DB_HOST."\n\n".
		T_('Base URL').': '.$baseurl.
		'</pre>'; 
		break;


	case 'newdb':
		/*
		 * -----------------------------------------------------------------------------------
		 * NEW DB: Create a plain new db structure + sample contents
		 * -----------------------------------------------------------------------------------
		 */
		?>
		<h2><?php echo T_('Installing b2evolution tables with sample data')?></h2>
		<?php
			create_b2evo_tables();
			populate_main_tables();
		?>
		<p><?php echo T_('Installation successful!')?></p>
		<?php printf( T_('<p>Now you can <a %s>log in</a> with the login "admin" and password "%s".</p>
		<p>Note that password carefully! It is a <em>random</em> password that is given to you when you install b2evolution. If you lose it, you will have to delete the database tables and re-install anew.</p>'), 'href="../admin/b2edit.php"', $random_password) ?>
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
			upgrade_b2evo_tables();
		?>
		<p><?php echo T_('Upgrade completed successfully!')?></p>
		<p><?php printf( T_('Now you can <a %s>log in</a> with your usual %s username and password.'), 'href="../admin/b2edit.php"', 'b2evolution')?></p>
		<?php
		break;


	case 'cafelogupgrade':
		/*
		 * -----------------------------------------------------------------------------------
		 * UPGRADE FROM B2 : Create a new db structure + copy content from previous b2
		 * -----------------------------------------------------------------------------------
		 */
		require_once( dirname(__FILE__). '/_functions_cafelogupgrade.php' );
		?>
		<h2><?php echo T_('Installing b2evolution tables and copying existing b2 data')?></h2>
		<?php
			create_b2evo_tables();
			upgrade_cafelog_tables();
		?>
		<p><?php echo T_('Upgrade completed successfully!')?></p>
		<p><?php printf( T_('Now you can <a %s>log in</a> with your usual %s username and password.'), 'href="../admin/b2edit.php"', 'b2')?></p>
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
			echo T_('<p>For security reasons, the reset feature is disabled by default.</p>' );
			echo( '<p>To enable it, please go to the /conf/_config.php file and change:</p>
<pre>$allow_evodb_reset = 0;</pre>
to
<pre>$allow_evodb_reset = 1;</pre>
<p>Then reload this page and a reset option will appear.</p>');
			break;
		}
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

</body>
</html>
