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
require_once (dirname(__FILE__). "/$install_dirout/$core_subdir/_functions.php" ); // db funcs
require_once( dirname(__FILE__). "/$install_dirout/$core_subdir/_vars.php" );
require_once (dirname(__FILE__). "/$install_dirout/$core_subdir/_class_db.php" );
require_once (dirname(__FILE__). "/$install_dirout/$core_subdir/_functions.php" ); // db funcs
require_once (dirname(__FILE__). "/$install_dirout/$core_subdir/_functions_cats.php" );
require_once (dirname(__FILE__). "/$install_dirout/$core_subdir/_functions_bposts.php" );
require_once (dirname(__FILE__). "/$install_dirout/$core_subdir/_functions_forms.php" );
require_once (dirname(__FILE__). '/_functions_install.php' );
require_once (dirname(__FILE__). '/_functions_create.php' );

param( 'action', 'string', 'start' );
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
		Current installation : 
		<a href="index.php?locale=<?php echo $default_locale ?>">Install menu</a> &middot; 
		<a href="phpinfo.php">PHP info</a> &middot; 
		<a href="../index.php">Go to Blogs</a> &middot; 
		<a href="../admin/">Go to Admin</a> &middot; 
		Online : 
		<a href="http://b2evolution.net/man/supportfaq.html">Support</a>
	</div>
</div>

<?php
	if( ($action == 'start') || ($action == 'menu') )
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
			echo $lvalue['name'];
			echo '</a>';
			echo '</li>';
			
			if( $default_locale == $lkey ) echo '</strong>';	
		}
		?>
		</ul>
		</div>
		<?php
	}

list( $version_main, $version_minor ) = explode( '.', phpversion() );
if( ($version_main * 100 + $version_minor) < 401 )
{
	die( '<p class="error"><strong>'.sprintf(T_('The minimum requirement for this version of b2evolution is PHP version %s, but you have %s!'), '4.1.0', phpversion() ).'</strong></p>');
}

if( $action != 'start' )
{
	dbconnect() or die( '<p class="error">'.sprintf( T_('Could not connect to database! Check you settings in <code>%s</code>!'), '/conf/b2eco_config.php').'</p>' );
	$DB = new DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
}

switch( $action )
{
	case 'start':
		/*
		 * -----------------------------------------------------------------------------------
		 * Start of install procedure:
		 * -----------------------------------------------------------------------------------
		 */
		if( ! $config_is_done )
		{
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
						form_text( 'conf_db_user', DB_USER, 16, T_('mySQL Username'), sprintf( T_('Your username to access the database' ) ), 16 );
						form_text( 'conf_db_password', DB_PASSWORD, 16, T_('mySQL Password'), sprintf( T_('Your password to access the database' ) ), 16 );
						form_text( 'conf_db_name', DB_NAME, 16, T_('mySQL Database'), sprintf( T_('Name of the database you want to use' ) ), 16 );
						form_text( 'conf_db_host', DB_HOST, 16, T_('mySQL Host'), sprintf( T_('You probably won\'t have to change this' ) ), 16 );
					?>
				</fieldset>

				<fieldset>
					<legend><?php echo T_('Additional settings') ?></legend>
					<?php
						$baseurl = 'http://'.( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'yourserver.com' );
						if( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) )
							$baseurl .= ':'.$_SERVER['SERVER_PORT'];
						$baseurl .= preg_replace( '#/install(/(index.php)?)?$#', '', $ReqPath );
					
						form_text( 'conf_baseurl', $baseurl, 50, T_('Base URL'), sprintf( T_('This is where b2evo and your blogs reside by default. CHECK THIS CAREFULLY or not much will work. If you want to test b2evolution on your local machine, in order for login cookies to work, you MUST use http://<strong>localhost</strong>/path... Do NOT use your machine\'s name!' ) ), 120 );

						form_text( 'conf_admin_email', $admin_email, 50, T_('Your email'), sprintf( T_('Will be used in severe error messages so that users can contact you. You will also receive notifications for new user registrations.' ) ), 80 );
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
				
				
			<a href="index.php?action=menu&amp;locale=<?php echo $default_locale ?>">Next step</a>
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
			echo '<br />
			'.T_('<h2>Need to start anew?</h2>
			<p>If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. b2evolution can delete its own tables for you, but for obvious security reasons, this feature is disabled by default.</p>
			<p>To enable it, please go to the /conf/_config.php file and change:</p>
			<pre>$allow_evodb_reset = 0;</pre>
			to
			<pre>$allow_evodb_reset = 1;</pre>
			<p>Then reload this page and a reset option will appear.</p>');
		}
		?>
		
		<hr /> 
		<h2><?php echo T_('Config file recap...')?></h2>
		
		<p><?php echo T_('If you don\'t see correct settings here, STOP before going any further, and check your configuration.')?></p>
	
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
		<h2>Deleting b2evolution tables from the datatase</h2>
		<?php
		if( $allow_evodb_reset != 1 )
		{
			?>
			<?php echo T_('<p>For security reasons, the reset feature is disabled by default.</p>
			<p>To enable it, please go back the /conf/_config.php file and change:</p>
			<pre>$allow_evodb_reset = 0;</pre>
			to
			<pre>$allow_evodb_reset = 1;</pre>
			<p>Then reload this page and resetting will take place.</p>')?>
			<p><a href="install.php?locale=<?php echo $default_locale ?>"><?php echo T_('Back to menu')?></a>.</p>
			<?php
			break;
		}
		db_delete();
		?>
	  <p><?php echo T_('Reset done!')?></p>
	  <p><a href="install.php?locale=<?php echo $default_locale ?>"><?php echo T_('Back to menu')?></a>.</p>
	  <?php
		break;
}
?>

<div id="rowfooter">
<a href="http://b2evolution.net/">official website</a> &middot; <a href="http://b2evolution.net/about/license.html">GNU GPL license</a> &middot; <a href="http://fplanque.net/About/index.html">contact: Fran&ccedil;ois PLANQUE</a>
</div>

</body>
</html>
