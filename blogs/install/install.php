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
require_once( dirname(__FILE__). "/$install_dirout/$core_subdir/_vars.php" );
require_once (dirname(__FILE__). "/$install_dirout/$core_subdir/_class_db.php" );
require_once (dirname(__FILE__). "/$install_dirout/$core_subdir/_functions.php" ); // db funcs
require_once (dirname(__FILE__). "/$install_dirout/$core_subdir/_functions_cats.php" );
require_once (dirname(__FILE__). "/$install_dirout/$core_subdir/_functions_bposts.php" );
require_once (dirname(__FILE__). '/_functions_create.php' );

param( 'action', 'string' );
// explicit set locale
param('locale', 'string');

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


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/b2evodistrib.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
<title>b2 evolution: Database tables installation</title>
<!-- InstanceEndEditable --><link href="b2evo.css" rel="stylesheet" type="text/css" />

<!-- InstanceBeginEditable name="head" -->
<!-- InstanceEndEditable -->
</head>
<body>
<div id="rowheader" >
<h1><a href="http://b2evolution.net/" title="b2evolution: Home"><img src="../img/b2evolution_logo.png" alt="b2evolution" width="472" height="102" border="0" /></a></h1>
<div id="tagline">Multilingual multiuser multi-blog engine!</div>
<h1 id="version">Version: 0.8.9+CVS</h1>
<div id="quicklinks">Setup Links: <a href="../../index.html">My b2evo</a> &middot; <a href="http://b2evolution.net/man/">Online Manual</a> &middot; <a href="install.php">My DB Install</a> &middot; <a href="../index.php">My Blogs</a> &middot; <a href="../admin/b2edit.php">My Back-Office</a></div>
</div>
<!-- InstanceBeginEditable name="Main" -->

<h1>Database tables installation</h1>

<div style="float:right;border:1px dotted black;padding:1ex;">
	<p>PHP version: <?php echo phpversion(); ?> [<a href="phpinfo.php">PHP info</a>]</p>
	<?php
		list( $version_main, $version_minor ) = explode( '.', phpversion() );
		if( ($version_main * 100 + $version_minor) < 401 )
		{
			die( '<strong>The minimum requirement for this version of b2evolution is PHP Version 4.1.0!</strong>');
		}
	?>
	<p>These are your settings from the config file:<br />
	(If you don't see correct settings here, <br />
	STOP before going any further, <br />
	and check your configuration.)</p>
<pre>
mySQL Host: <?php echo DB_HOST ?> &nbsp;
mySQL Database: <?php echo DB_NAME ?> &nbsp;
mySQL Username: <?php echo DB_USER ?> &nbsp;
mySQL Password: <?php echo ((DB_PASSWORD!='demopass' ? "(Set, but not shown for security reasons)" : 'demopass') )?> &nbsp;
</pre>
	<?php
	if( empty($action) )
	{
		?>
		<h2>Language/Locale</h2>
		<p>Choose a default locale.<br />
		Clicking it should directly activate it.</p>
		
		<ul style="margin-left: 2ex;list-style:none;" >
	
		<?php
		// present available locales on first screen
		foreach( $locales as $lkey => $lvalue )
		{
			echo '<li>';
			if( $default_locale == $lkey ) echo '<strong>';	
			echo ' <a href="?locale='. $lkey. '">';
			locale_flag( $lkey );
			echo $lvalue['name'];
			echo '</a>';
			echo '</li>';
			
			if( $default_locale == $lkey ) echo '</strong>';	
		}
		echo '</ul>';
	}
	?>
</div>

<?php

$new_db_version = 8060;				// next time: 8070

/*
 * check_db_version(-)
 *
 * Note: version number 8000 once meant 0.8.00.0, but I decided to switch to sequential
 * increments of 10 (in case we ever need to introduce intermediate versions for intermediate
 * bug fixes...)
 */
function check_db_version()
{
	global $DB, $old_db_version, $new_db_version, $tablesettings;

	echo '<p>Checking DB schema version... ';
	$old_db_version = $DB->get_var( "SELECT db_version FROM $tablesettings" );
	if( $old_db_version == NULL ) die( 'NOT FOUND! This is not a b2evolution database.' );
	echo $old_db_version, ' : ';
	if( $old_db_version < 8000 ) die( 'This version is too old!' );
	if( $old_db_version > $new_db_version ) die( 'This version is too recent! We cannot downgrade to it!' );
	echo "OK.<br />\n";
}


dbconnect() or die( '<p>Could not connect to database! Check you settings in /conf/b2eco_config.php!</p>' );
$DB = new DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );


$timestamp = time() - 120; // We start dates 2 minutes ago because their dates increase 1 second at a time and we want everything to be visible when the user watches the blogs right after install :P

$stub_all = 'blog_all';
$stub_a = 'blog_a';
$stub_b = 'blog_b';
$stub_roll = 'blog_roll';

switch( $action )
{
	case 'newdb':
		/*
		 * -----------------------------------------------------------------------------------
		 * NEW DB: Create a plain new db structure + sample contents
		 * -----------------------------------------------------------------------------------
		 */
		?>
		<h2>Installing b2evolution tables with sample data</h2>
		<?php
			create_b2evo_tables();
			populate_main_tables();
		?>
		<p>Installation successful!</p>
		<p>Now you can <a href="../admin/b2edit.php">log in</a> with the login "admin" and password "<?php echo $random_password; ?>".</p>
		<p>Note that password carefully ! It is a <em>random</em> password that is given to you when you install b2evolution. If you lose it, you will have to delete the tables and re-install anew.</p>
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
		<h2>Upgrading data in existing b2evolution database</h2>
		<?php
			upgrade_b2evo_tables();
		?>
		<p>Upgrade completed successfully!</p>
		<p>Now you can <a href="../admin/b2edit.php">log in</a> with your usual b2evolution username and password.</p>
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
		<h2>Installing b2evolution tables and copying existing b2 data</h2>
		<?php
			create_b2evo_tables();
			upgrade_cafelog_tables();
		?>
		<p>Upgrade completed successfully!</p>
		<p>Now you can <a href="../admin/b2edit.php">log in</a> with your usual b2 username and password.</p>
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
			<p>For security reasons, the reset feature is disabled by default.</p>
			<p>To enable it, please go back the /conf/_config.php file and change:</p>
			<pre>$allow_evodb_reset = 0;</pre>
			to
			<pre>$allow_evodb_reset = 1;</pre>
			<p>Then reload this page and resetting will take place.</p>
			<p>Back to <a href="install.php?locale=<?php echo $default_locale ?>">menu</a>.</p>
			<?php
			break;
		}
		db_delete();
		?>
		<p>Reset done!</p>
		<p>Back to <a href="install.php?locale=<?php echo $default_locale ?>">menu</a>.</p>
		<?php
		break;


	default:
		/*
		 * -----------------------------------------------------------------------------------
		 * Menu
		 * -----------------------------------------------------------------------------------
		 */
		?>
		<h2>What do you want to install?</h2>
		<form action="install.php" method="get">
			<input type="hidden" name="locale" value="<?php echo $default_locale ?>" />
			<p>The database tables installation can be done in different ways. Choose one:</p>
			<p><input type="radio" name="action" value="newdb" checked="checked"> <strong>New Install</strong>: Install b2evolution database tables with sample data.</p>
			<p><input type="radio" name="action" value="evoupgrade"> <strong>Upgrade from a previous version of b2evolution</strong>: Upgrade your b2evolution database tables in order to make them compatible with the current version!</p>
			<p><input type="radio" name="action" value="cafelogupgrade"> <strong>Upgrade from Cafelog/b2 v 0.6.x</strong>: Install b2evolution database tables and copy your existing Cafelog/b2 data into them.</p>

			<?php
				if( $allow_evodb_reset == 1 )
				{
			?>
			<p><input type="radio" name="action" value="deletedb"> <strong>Delete b2evolution tables</strong>: If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. <strong>WARNING: All your b2evolution tables and data will be lost!!!</strong> Your Cafelog/b2 or any other tables though, if you have some, will not be touched in any way.</p>
			<?php
				}
			?>
			<p><input type="submit" value="Install Database Tables Now !" /></p>
		</form>
		<?php
		if( $allow_evodb_reset != 1 )
		{
			?>
			<br />

			<h2>Need to start anew?</h2>
			<p>If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. b2evolution can delete its own tables for you, but for obvious security reasons, this feature is disabled by default.</p>
			<p>To enable it, please go to the /conf/_config.php file and change:</p>
			<pre>$allow_evodb_reset = 0;</pre>
			to
			<pre>$allow_evodb_reset = 1;</pre>
			<p>Then reload this page and a reset option will appear.</p>
			<?php
		}
		break;
}
?>
<!-- InstanceEndEditable -->
<div id="rowfooter">
<a href="http://b2evolution.net/">official website</a> &middot; <a href="http://b2evolution.net/about/license.html">GNU GPL license</a> &middot; <a href="http://fplanque.net/About/index.html">contact: Fran&ccedil;ois PLANQUE</a>
</div>

</body>
<!-- InstanceEnd --></html>
