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


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><!-- InstanceBegin template="/Templates/b2evodistrib.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
<title>b2 evolution: Database tables installation</title>
<!-- InstanceEndEditable --><link href="../rsc/b2evo.css" rel="stylesheet" type="text/css" />
 
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

<h1><?php echo T_('Database tables installation')?></h1>

<div class="installSideBar">
	<p><?php echo T_('PHP version')?>: <?php echo phpversion(); ?> [<a href="phpinfo.php">PHP info</a>]</p>
	<?php
		list( $version_main, $version_minor ) = explode( '.', phpversion() );
		if( ($version_main * 100 + $version_minor) < 401 )
		{
			die( '<p class="error"><strong>'.sprintf(T_('The minimum requirement for this version of b2evolution is PHP version %s, but you have %s!'), '4.1.0', phpversion() ).'</strong></p>');
		}
	?>
	<p><?php echo T_('These are your settings from the config file:')?>
	<br />
	<?php echo T_('(If you don\'t see correct settings here, STOP before going any further, and check your configuration.)')?>
	</p>

<?php echo '<pre>',
'mySQL '.T_('Host').': '.DB_HOST." &nbsp;\n".
'mySQL '.T_('Database').': '.DB_NAME." &nbsp;\n".
'mySQL '.T_('Username').': '.DB_USER." &nbsp;\n".
'mySQL '.T_('Password').': '.((DB_PASSWORD != 'demopass' ? T_('(Set, but not shown for security reasons)') : 'demopass') ).' &nbsp;
</pre>';

if( empty($action) )
{
	?>
	<h2><?php echo T_('Language/Locale')?></h2>
	<p><?php echo T_('Choose a default locale.<br /> Clicking it should directly activate it.')?></p>
	
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
	?>
	</ul>
	<?php
}
?>
</div>

<?php

/**
 * check_db_version(-)
 *
 * Note: version number 8000 once meant 0.8.00.0, but I decided to switch to sequential
 * increments of 10 (in case we ever need to introduce intermediate versions for intermediate
 * bug fixes...)
 */
function check_db_version()
{
	global $DB, $old_db_version, $new_db_version, $tablesettings;

	echo '<p>'.T_('Checking DB schema version...').' ';
	$DB->query( "SELECT * FROM $tablesettings LIMIT 1" );
	$colinfo = $DB->get_col_info();
	
	if( $colinfo[0] == 'set_name' )
	{ // we have new table format
		$old_db_version = $DB->get_var( "SELECT set_value FROM $tablesettings WHERE set_name = 'db_version'" );
	}
	else
	{
		$old_db_version = $DB->get_var( "SELECT db_version FROM $tablesettings" );
	}
	
	if( $old_db_version == NULL ) die( T_('NOT FOUND! This is not a b2evolution database.') );
	
	echo $old_db_version, ' : ';
	
	if( $old_db_version < 8000 ) die( T_('This version is too old!') );
	if( $old_db_version > $new_db_version ) die( T_('This version is too recent! We cannot downgrade to it!') );
	echo "OK.<br />\n";
}


dbconnect() or die( '<p class="error">'.sprintf( T_('Could not connect to database! Check you settings in <code>%s</code>!'), '/conf/b2eco_config.php').'</p>' );
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
		break;
}
?>
<!-- InstanceEndEditable -->
<div id="rowfooter">
<a href="http://b2evolution.net/">official website</a> &middot; <a href="http://b2evolution.net/about/license.html">GNU GPL license</a> &middot; <a href="http://fplanque.net/About/index.html">contact: Fran&ccedil;ois PLANQUE</a>
</div>

</body>
<!-- InstanceEnd --></html>
