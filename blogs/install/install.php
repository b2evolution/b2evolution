<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><!-- InstanceBegin template="/Templates/b2evodistrib.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" --> 
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>b2 evolution: Database tables installation</title>
<!-- InstanceEndEditable --><link href="b2evo.css" rel="stylesheet" type="text/css" />
 
<!-- InstanceBeginEditable name="head" -->
<!-- InstanceEndEditable -->
</head>
<body>
<div id="rowheader" >
<h1><a href="http://b2evolution.net/" title="b2evolution: Home"><img src="../img/b2evolution_logo.png" alt="b2evolution" width="472" height="102" border="0" /></a></h1>
<div id="tagline">A blog tool like it oughta be!</div>
<h1 id="version">Version: 0.8.7</h1>
<div id="quicklinks">Setup Links: <a href="../../index.html">My b2evo</a> &middot; <a href="http://b2evolution.net/man/">Online Manual</a> &middot; <a href="install.php">My DB Install</a> &middot; <a href="../index.php">My Blogs</a> &middot; <a href="../admin/b2edit.php">My Back-Office</a></div>
</div>
<!-- InstanceBeginEditable name="Main" -->
<h2>Database tables installation</h2>
<p>PHP version: <?php echo phpversion(); ?></p>
<?php
	$test = 1;

	list( $version_main, $version_minor ) = explode( '.', phpversion() );
	if( ($version_main*100+$version_minor) < 401 )
	{
		die( '<strong>The minimum requirement for this version of b2evolution is PHP Version 4.1.0!</strong>');
	}

	require_once (dirname(__FILE__).'/../conf/_config.php'); 
 
 ?>

<p>These are your settings from the config file: (If you don't see correct settings here, STOP before going any further, and check your configuration.)</p>
<pre>
mySQL Host: <?php echo $dbhost ?> &nbsp;
mySQL Database: <?php echo $dbname ?> &nbsp;
mySQL Username: <?php echo $dbusername ?> &nbsp;
mySQL Password: <?php echo (($dbpassword!='demopass' ? "(Set, but not shown for security reasons)" : "demopass") )?> &nbsp;
</pre>

<?php
require_once (dirname(__FILE__)."/$install_dirout/$core_subdir/_functions.php" ); // db funcs
require_once (dirname(__FILE__)."/$install_dirout/$core_subdir/_functions_cats.php" );
require_once (dirname(__FILE__)."/$install_dirout/$core_subdir/_functions_bposts.php" );
require_once (dirname(__FILE__)."/_functions_create.php" );

$new_db_version = 8050;				// next time: 8060



/*
 * check_db_version(-)
 *
 * Note: version number 8000 once meant 0.8.00.0, but I decided to switch to sequential 
 * increments of 10 (in case we ever need to introduce intermediate versions for intermediate
 * bug fixes...)
 */
function check_db_version()
{
	global $old_db_version, $new_db_version, $tablesettings;

		echo "<p>Checking DB schema version... ";
		$query = "SELECT db_version FROM $tablesettings WHERE ID = 1";
		$q = mysql_query($query) or mysql_oops( $query );
		$row = mysql_fetch_assoc($q);
		if( !isset($row['db_version'] ) ) die( 'NOT FOUND! This is not a b2evolution database.' );
		$old_db_version = $row['db_version'];
		echo $old_db_version, ' : ';
		if( $old_db_version < 8000 ) die( 'This version is too old!' );
		if( $old_db_version > $new_db_version ) die( 'This version is too recent! We cannot downgrade to it!' );
		echo "OK.<br />\n";
}
 
 
dbconnect() or die( "<p>Could not connect to database! Check you settings in /conf/b2eco_config.php!</p>" );

param( 'action', 'string' );
param( 'skins', 'integer', 0 );

$timestamp = time();

if( $skins )
{
	$stub_all = 'blog_all';
	$stub_a = 'blog_a';
	$stub_b = 'blog_b';
	$stub_roll = 'blog_roll';
}
else
{
	$stub_all = 'noskin_all';
	$stub_a = 'noskin_a';
	$stub_b = 'noskin_b';
	$stub_roll = 'noskin_roll';
}

switch( $action )
{
	case 'newdb':
		/* 
		 * -----------------------------------------------------------------------------------
		 * NEW DB: Create a plain new db structure + sample contents
		 * -----------------------------------------------------------------------------------
		 */
		?>
		<h3>Installing b2evolution tables with sample data</h3>
		<?php
		create_b2evo_tables();

		echo "<p>Creating sample contents...</p>\n";
		
		blog_create( 'All Blogs', 'All', '', $stub_all.'.php', $stub_all.'.php', $stub_all.'.html', 'Tagline for All', 'All blogs on this system.', NULL, $default_language,  "This is the blogroll for the \'all blogs\' blog aggregation.", 'all blogs keywords', '' ) or mysql_oops( $query );

		blog_create( 'Demo Blog A', 'Blog A', '', $stub_a.'.php', $stub_a.'.php', $stub_a.'.html', 'Tagline for A', 'This is demo blog A', 'This is description for demo blog A. It has index #2 in the database.', $default_language, 'This is the blogroll for Blog A...', 'blog A keywords', '' ) or mysql_oops( $query );
		
		blog_create( 'Demo Blog B', 'Blog B', '', $stub_b.'.php', $stub_b.'.php', $stub_b.'.html', 'Tagline for B', 'This is demo blog B', 'This is description for demo blog B. It has index #3 in the database.', $default_language, 'This is the blogroll for Blog B...', 'blog B keywords', '') or mysql_oops( $query );

		blog_create( 'Demo Blogroll', 'Blogroll', '', $stub_roll.'.php', $stub_roll.'.php', $stub_roll.'.html', 'Tagline for Blogroll', 'This is the demo blogroll', 'This is description for blogroll. It has index #4 in the database.', $default_language, 'This is the blogroll for the blogroll... pretty funky huh? :))', 'blogroll keywords', '') or mysql_oops( $query );

		echo "<p>blogs: OK<br />\n";
		
		// Create categories for blog A
		$cat_ann_a = cat_create( "Announcements [A]", 'NULL', 2 )  or mysql_oops( $query );
		$cat_news = cat_create( "News", 'NULL', 2 )  or mysql_oops( $query );
		$cat_bg = cat_create( "Background", 'NULL', 2 )  or mysql_oops( $query );
		
		// Create categories for blog B
		$cat_ann_b = cat_create( "Announcements [B]", 'NULL', 3 )  or mysql_oops( $query );
		$cat_fun = cat_create( "Fun", 'NULL', 3 )  or mysql_oops( $query );
		$cat_life = cat_create( "In real life", $cat_fun, 3 )  or mysql_oops( $query );
		$cat_web = cat_create( "On the web", $cat_fun, 3 )  or mysql_oops( $query );
		$cat_sports = cat_create( "Sports", $cat_life, 3 )  or mysql_oops( $query );
		$cat_movies = cat_create( "Movies", $cat_life, 3 )  or mysql_oops( $query );
		$cat_music = cat_create( "Music", $cat_life, 3 )  or mysql_oops( $query );
		$cat_b2evo = cat_create( "b2evolution", 'NULL', 3 )  or mysql_oops( $query );
		echo "categories: OK<br />\n";

		// Create categories for blogroll
		$cat_blogroll_b2evo = cat_create( "b2evolution", 'NULL', 4 )  or mysql_oops( $query );
		$cat_blogroll_contrib = cat_create( "contributors", 'NULL', 4 )  or mysql_oops( $query );
		
	
		// Insert a post:
		$now = date('Y-m-d H:i:s',$timestamp++);
		bpost_create( 1, 'First Post', '<p>This is the first post.</p>
		
		<p>It appears on both blog A and blog B.</p>', $now, $cat_ann_a, array( $cat_ann_b ) ) or mysql_oops( $query );
		
		// Insert a post:
		$now = date('Y-m-d H:i:s',$timestamp++);
		bpost_create( 1, 'Second post', '<p>This is the second post.</p>
		
		<p>It appears on blog A only but in multiple categories.</p>', $now, $cat_news, array( $cat_ann_a, $cat_bg ) ) or mysql_oops( $query );
		
		// Insert a post:
		$now = date('Y-m-d H:i:s',$timestamp++);
		bpost_create( 1, 'Third post', '<p>This is the third post.</p>
		
		<p>It appears on blog B only and in a single category.</p>', $now, $cat_fun ) or mysql_oops( $query );
		
		// Insert a post:
		$now = date('Y-m-d H:i:s',$timestamp++);
		bpost_create( 1, "Matrix Reloaded", "<p>Wait until the end of the super long end credits!</p>
		
		<p>If you're patient enough, you'll a get preview of the next episode...</p>
		
		<p>Though... it's just the same anyway! :>></p>", $now, $cat_movies ) or mysql_oops( $query );

		// Insert a post:
		$now = date('Y-m-d H:i:s',$timestamp++);
		bpost_create( 1, "Clean Permalinks! :idea:", "<p>b2evolution uses old-style permalinks and feedback links by default. This is to ensure maximum compatibility with various webserver configurations. Nethertheless, if you feel comfortable, you should try activating clean permalinks in the /conf/_advanced.php file...</p>", $now, $cat_b2evo ) or mysql_oops( $query );

		// Insert a post:
		$now = date('Y-m-d H:i:s',$timestamp++);
		bpost_create( 1, "Clean Skin! :idea:", "<p>By default, b2evolution blogs are displayed in the \'standard\' skin.</p>

<p>Readers can choose a new skin by using the skin switcher integrated in most skins.</p>		

<p>You can restrict available skins by deleting some of them from the /blogs/skins folder. You can also change the default skin or force a specific skin. <strong>Actually, you should change the default skin and delete the standard skin, as this one has navigation links at the top that are only good for the sake of the demo. These would be a nonsense on production servers!</strong> Read the manual on evoSkins!</p>", $now, $cat_b2evo ) or mysql_oops( $query );
		
		// POPULATE THE BLOGROLL:
		populate_blogroll( $now, $cat_blogroll_b2evo, $cat_blogroll_contrib );

		echo "posts: OK<br />\n";
		

			
		$now = date('Y-m-d H:i:s');
		$query = "INSERT INTO $tablecomments (comment_ID, comment_post_ID, comment_type, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_karma)
		VALUES (1, 1, 'comment', 'miss b2', 'missb2@example.com', 'http://example.com', '127.0.0.1', '$now', 'Hi, this is a comment.<br />To delete a comment, just log in, and view the posts\' comments, there you will have the option to edit or delete them.', 0)";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "comments: OK<br />\n";
		

		// Populate the anti-spam table:
		populate_antispam();

		echo "anti-spam: OK<br />\n";

		
		
		// USERS !
		$User_Admin = new User();
		$User_Admin->set( 'login', 'admin' );
		$random_password = substr(md5(uniqid(microtime())),0,6);
		$User_Admin->set( 'pass', md5($random_password) );	// random
		$User_Admin->set( 'nickname', 'admin' );
		$User_Admin->set( 'email', $admin_email );
		$User_Admin->set( 'ip', '127.0.0.1' );
		$User_Admin->set( 'domain', 'localhost' );
		$User_Admin->set( 'level', 10 );
		$User_Admin->setGroup( $Group_Admins );
		$User_Admin->dbinsert();

		$User_Demo = new User();
		$User_Demo->set( 'login', 'demouser' );
		$User_Demo->set( 'pass', md5($random_password) );	// random
		$User_Demo->set( 'nickname', 'Mr. Demo' );
		$User_Demo->set( 'email', $admin_email );
		$User_Demo->set( 'ip', '127.0.0.1' );
		$User_Demo->set( 'domain', 'localhost' );
		$User_Demo->set( 'level', 0 );
		$User_Demo->setGroup( $Group_Users );
		$User_Demo->dbinsert();

		echo "users: OK</p>";


		// SETTINGS!
		$query = "INSERT INTO $tablesettings ( ID, posts_per_page, what_to_show, archive_mode, time_difference, AutoBR, db_version, last_antispam_update, pref_newusers_grp_ID ) 
		VALUES ( 1, 3, 'paged', 'monthly', '0', '1', $new_db_version, '2000-01-01 00:00:00', ".$Group_Users->get('ID')." )";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "settings: OK<br />\n";
			
		
		?>
		
		<p>Installation successful !</p>
		<br />
		Now you can <a href="../admin/b2edit.php">log in</a> with the login "admin" and password "<?php echo $random_password; ?>".<br />
		<br />
		<br />
		Note that password carefully ! It is a <em>random</em> password that is given to you when you install b2. If you lose it, you will have to delete the tables from the database yourself, and re-install b2.
	<?php
	break;


	case 'evodb':
		/*
		 * -----------------------------------------------------------------------------------
		 * EVO UPGRADE: Upgrade data from existing b2evolution database
		 * -----------------------------------------------------------------------------------
		 */
		require_once (dirname(__FILE__)."/_functions_upgrade.php" );
		?>
		<h3>Upgrading data in existing b2evolution database</h3>
		<?php
			upgrade_b2evo_tables();		
		?>
		<p>Upgrade completed successfully!</p>
		<p>Now you can <a href="../admin/b2edit.php">log in</a> with your usual b2evolution username and password.</p>
	 <?php
		break;


	case 'redocurrentupgrade':
		/* 
		 * -----------------------------------------------------------------------------------
		 * REDO the current b2evo upgrade. This is used for development only!
		 * -----------------------------------------------------------------------------------
		 */
		require_once (dirname(__FILE__)."/_functions_upgrade.php" );
		echo '<h3>Update development base by redoing latest upgarde (', $new_db_version, ')</h3>';
		devupg_b2evo_tables();
		?>
		<p>Redo completed successfully!</p>
		<p>Now you can <a href="../admin/b2edit.php">log in</a> with your usual b2evolution username and password.</p>
		<?php
		break;
	

	case 'upgradedb':
		/* 
		 * -----------------------------------------------------------------------------------
		 * UPGRADE FROM B2 : Create a new db structure + copy content from previous b2
		 * -----------------------------------------------------------------------------------
		 */
		?>
		<h3>Installing b2evolution tables and copying existing b2 data</h3>
		<?php
		
		// start benchmarking
		$time_start = gettimeofday();
		
		create_b2evo_tables();

		echo "<p>Creating default blogs...</p>\n";
		
		blog_create( 'All Blogs', 'All', '', $stub_all.'.php', $stub_all.'.php', $stub_all.'.html', 'Tagline for All', 'All blogs on this system.', NULL, $default_language,  "This is the blogroll for the \'all blogs\' blog aggregation.", 'all blogs keywords', '' ) or mysql_oops( $query );

		blog_create( 'My Upgraded Blog', 'Upgraded', '', $stub_a.'.php', $stub_a.'.php', $stub_a.'.html', 'Tagline for A', 'Upgraded blog - no description yet', 'This is description for your upgraded blog. It has index #2 in the database.', $default_language, 'This is the blogroll for Upgraded Blog...', '', '' ) or mysql_oops( $query );
		
		blog_create( 'Demo Blog B', 'Blog B', '', $stub_b.'.php', $stub_b.'.php', $stub_b.'.html', 'Tagline for B', 'This is demo blog B', 'This is description for demo blog B. It has index #3 in the database.', $default_language, 'This is the blogroll for Blog B...', 'blog B keywords', '') or mysql_oops( $query );

		blog_create( 'Demo Blogroll', 'Blogroll', '', $stub_roll.'.php', $stub_roll.'.php', $stub_roll.'.html', 'Tagline for Blogroll', 'This is the demo blogroll', 'This is description for blogroll. It has index #4 in the database.', $default_language, 'This is the blogroll for the blogroll... pretty funky huh? :))', 'blogroll keywords', '') or mysql_oops( $query );

		echo "<p>blogs: OK<br />\n";
		

		echo "<p>Copying data from original b2 tables...</p>\n";

		echo "<p>Copying settings... ";	
		// forcing paged mode because this works so much better !!!
		// You can always change it back in the options if you don't like it.
		$query = "INSERT INTO $tablesettings( ID, posts_per_page, what_to_show, archive_mode, time_difference, AutoBR, db_version, last_antispam_update) SELECT ID, 5, 'paged', archive_mode, time_difference, AutoBR, $new_db_version, '2000-01-01 00:00:00' FROM $oldtablesettings";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";
		
		echo "Copying users... ";
		$query = "INSERT INTO $tableusers( ID, user_login, user_pass, user_firstname, user_lastname,
								user_nickname, user_icq, user_email, user_url, user_ip, user_domain, user_browser,
								dateYMDhour, user_level,	user_aim, user_msn, user_yim, user_idmode ) 
							SELECT ID, user_login, MD5(user_pass), user_firstname, user_lastname, user_nickname,
								user_icq, user_email, user_url, user_ip, user_domain, user_browser, dateYMDhour,
								user_level,	user_aim, user_msn, user_yim, user_idmode 
							FROM $oldtableusers";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Setting groups...";
		$query = "UPDATE $tableusers
								 SET user_grp_ID = ".$Group_Users->get('ID')."
							 WHERE user_level = 0";
		$q = mysql_query($query) or mysql_oops( $query );

		echo "Setting groups...";
		$query = "UPDATE $tableusers
								 SET user_grp_ID = ".$Group_Bloggers->get('ID')."
							 WHERE user_level > 0 and user_level < 10 ";
		$q = mysql_query($query) or mysql_oops( $query );

		echo "OK.<br />\n";

		echo "Creating user blog permissions... ";
		$query = "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, 
								bloguser_perm_delpost, bloguser_perm_comments, bloguser_perm_poststatuses)
							SELECT 2, ID, 1, 1, 'published,deprecated,protected,private,draft'
							FROM $oldtableusers
							WHERE user_level > 0";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";
		
		echo "Copying categories... ";
		$query = "INSERT INTO $tablecategories( cat_ID, cat_parent_ID, cat_name, cat_blog_ID ) SELECT DISTINCT cat_ID, NULL, cat_name, 2 FROM $oldtablecategories";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Creating additionnal categories for Blog B... ";
		$cat_ann_b = cat_create( "Announcements [B]", 'NULL', 3 )  or mysql_oops( $query );
		$cat_fun = cat_create( "Fun", 'NULL', 3 )  or mysql_oops( $query );
		$cat_life = cat_create( "In real life", $cat_fun, 3 )  or mysql_oops( $query );
		$cat_web = cat_create( "On the web", $cat_fun, 3 )  or mysql_oops( $query );
		$cat_sports = cat_create( "Sports", $cat_life, 3 )  or mysql_oops( $query );
		$cat_movies = cat_create( "Movies", $cat_life, 3 )  or mysql_oops( $query );
		$cat_music = cat_create( "Music", $cat_life, 3 )  or mysql_oops( $query );
		$cat_b2evo = cat_create( "b2evolution", 'NULL', 3 )  or mysql_oops( $query );

		// Create categories for blogroll
		$cat_blogroll_b2evo = cat_create( "b2evolution", 'NULL', 4 )  or mysql_oops( $query );
		$cat_blogroll_contrib = cat_create( "contributors", 'NULL', 4 )  or mysql_oops( $query );
		echo "OK.<br />\n";
		
		echo "Copying posts... ";
		$query = "INSERT INTO $tableposts( ID, post_author, post_date, post_status, post_lang, post_content,post_title, post_category, post_autobr, post_flags, post_karma)  SELECT ID, post_author, post_date, 'published', '$default_language', post_content, post_title, post_category, 1, 'pingsdone,html,imported', post_karma FROM $oldtableposts";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Generating wordcounts... ";
		$query = "SELECT ID, post_content FROM $tableposts";
		$q = mysql_query($query) or mysql_oops( $query );
		
		$rows_updated = 0;
		
		while($row = mysql_fetch_assoc($q)) {
			$query_update_wordcount = "UPDATE $tableposts SET post_wordcount = " . bpost_count_words($row['post_content']) . " WHERE ID = " . $row['ID'];
			$q_update_wordcount = mysql_query($query_update_wordcount) or mysql_oops( $query_update_wordcount );
			
			$rows_updated++;
		}
		
		echo "OK. ($rows_updated rows updated)<br />\n";

		echo "Generating postcats... ";
 		$query = "INSERT INTO $tablepostcats( postcat_post_ID, postcat_cat_ID ) SELECT ID, post_category FROM $tableposts";		
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Creating a few additionnal samples for Blog B... ";
		// Insert a post:
		$now = date('Y-m-d H:i:s',$timestamp++);
		bpost_create( 1, 'Sample post', '<p>This is a sample post.</p>
		
		<p>It appears on blog B only and in a single category.</p>', $now, $cat_fun ) or mysql_oops( $query );
		
		// Insert a post:
		$now = date('Y-m-d H:i:s',$timestamp++);
		bpost_create( 1, "Matrix Reloaded", "<p>Wait until the end of the super long end credits!</p>
		
		<p>If you're patient enough, you'll a get preview of the next episode...</p>
		
		<p>Though... it's just the same anyway! :>></p>", $now, $cat_movies ) or mysql_oops( $query );
				

		// Insert a post:
		$now = date('Y-m-d H:i:s',$timestamp++);
		bpost_create( 1, "Clean Permalinks! :idea:", "<p>b2evolution uses old-style permalinks and feedback links by default. This is to ensure maximum compatibility with various webserver configurations. Nethertheless, if you feel comfortable, you should try activating clean permalinks in the /conf/_advanced.php file...</p>", $now, $cat_b2evo ) or mysql_oops( $query );

		// Insert a post:
		$now = date('Y-m-d H:i:s',$timestamp++);
		bpost_create( 1, "Clean Skin! :idea:", "<p>By default, b2evolution blogs are displayed in the \'standard\' skin.</p>

<p>Readers can choose a new skin by using the skin switcher integrated in most skins.</p>		

<p>You can restrict available skins by deleting some of them from the /blogs/skins folder. You can also change the default skin or force a specific skin. <strong>Actually, you should change the default skin and delete the standard skin, as this one has navigation links at the top that are only good for the sake of the demo. These would be a nonsense on production servers!</strong> Read the manual on evoSkins!</p>", $now, $cat_b2evo ) or mysql_oops( $query );

		// POPULATE THE BLOGROLL:
		populate_blogroll( $now, $cat_blogroll_b2evo, $cat_blogroll_contrib );

		echo "OK.<br />\n";

		
		echo "Copying comments... ";
		$query = "INSERT INTO $tablecomments( comment_ID, comment_post_ID, comment_type, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_karma ) SELECT comment_ID, comment_post_ID, 'comment', comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_karma FROM $oldtablecomments";
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Qualifying comments... Trackback...";
 		$query = "UPDATE $tablecomments SET comment_type = 'trackback' WHERE comment_content LIKE '<trackback />%'";		
		$q = mysql_query($query) or mysql_oops( $query );
		echo "Qualifying comments... Pingback...";
 		$query = "UPDATE $tablecomments SET comment_type = 'pingback' WHERE comment_content LIKE '<pingback />%'";		
		$q = mysql_query($query) or mysql_oops( $query );
		echo "OK.<br />\n";

		echo "Populating Anti-Spam table...";
		populate_antispam();
		echo "OK.<br />\n";
		

		// end benchmarking
		$time_end = gettimeofday();
		$time_total = (float)($time_end['sec'] - $time_start['sec']) + ((float)($time_end['usec'] - $time_start['usec'])/1000000);
		$time_total = round($time_total, 3);
?>
		<p>Upgrade completed successfully! (<?php echo $time_total; ?> seconds)</p>
		
		<p>Now you can <a href="../admin/b2edit.php">log in</a> with your usual b2 username and password.</p>

		
<?php		
	break;


	case 'deletedb':
		/* 
		 * -----------------------------------------------------------------------------------
		 * DELETE DB: Delete the db structure!!! (Everything will be lost)
		 * -----------------------------------------------------------------------------------
		 */
		require_once (dirname(__FILE__).'/_functions_delete.php'); 
		?>
		
		<h3>Deleting b2evolution tables from the datatase</h3>
		
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
		
			<p>Back to <a href="install.php">menu</a>.</p>
			<?php
			break;	
		}
		
		db_delete();

		?>
		<p>Reset done!</p>
		
		<p>Back to <a href="install.php">menu</a>.</p>
		<?php 
		 		
		break;


	default:
		/* 
		 * -----------------------------------------------------------------------------------
		 * Menu
		 * -----------------------------------------------------------------------------------
		 */
?>

	<h3>What do you want to install?</h3>
		
	<form action="install.php" method="post">

	<fieldset>
		<legend>The database tables installation can be done in different ways. Choose one:</legend>

 <p>The delete feature enables you to come back here later and start anew with a different option, so feel free to experiment :)</p>
 	
<p><input type="radio" name="action" value="newdb" checked="checked"> <strong>New Install</strong>: Install b2evolution tables with sample data.</p>
	
		<p><input type="radio" name="action" value="evodb"> <strong>Upgrade from a previous version of b2evolution</strong>: This will upgrade your b2evolution database in order to make it compatible with the current version!</p>

		<?php if( $test ) { ?>
		<p><input type="radio" name="action" value="redocurrentupgrade"> <strong>DEVELOPMENT ONLY</strong>: Redo the current upgrade step to match your DB with current dev base!</p>
		<?php } ?>

  	<p><input type="radio" name="action" value="upgradedb"> <strong>Upgrade from original b2</strong>: Install b2evolution tables and copy your existing b2 data into them. </p>

  	<p><input type="radio" name="action" value="deletedb"> <strong>Delete b2evolution tables</strong>: If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. <strong>WARNING: All your b2evolution tables and data will be lost!!!</strong> Your original b2 tables though, if you have some, will not be touched.</p>
		
	</fieldset>

	<fieldset>
		<legend>What display system would you like to be activated by default?</legend>
	
		<p><input type="radio" name="skins" value="1" checked="checked"> <strong>evoSkins</strong>: Blogs will be displayed with a variety of skins you can choose from at reading time. (Recommended)</p>

		<p><input type="radio" name="skins" value="0"> <strong>templates</strong>: Blogs will be displayed in an example template. (This is how the original b2 used to work)</p>
		
		
		<p>This setting just sets a default for displaying the blogs right after the install. You can fully customize this later. You can even display some blogs with evoSkins and some blogs with templates... So don't worry too much about what you choose here ;)</p>


	</fieldset>
	

	<p><input type="submit" value="Install Database Tables Now !" /></p>
	</form>

 
  <p><strong>Note for original b2 users</strong>: Feel safe: Unless you explicitely decided to use the same names in the advanced config, your original b2 tables and data will <strong>NEVER</strong> by modified by b2evolution. b2evolution can only <strong>copy</strong> their contents.</p>
  <?php 
}
?>


<!-- InstanceEndEditable -->
<div id="rowfooter">
<a href="http://b2evolution.net/">official website</a> &middot; <a href="http://b2evolution.net/about/license.html">GNU GPL license</a> &middot; <a href="http://fplanque.net/About/index.html">contact: Fran&ccedil;ois PLANQUE</a>
</div>

</body>
<!-- InstanceEnd --></html>
