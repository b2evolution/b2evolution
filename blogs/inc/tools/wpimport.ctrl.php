<?php
/* WordPress 2.3 to b2evolution 2.0 alpha converter
   Copyright (C) 2007 V.Harishankar.

   Please use this with care and at your own discretion. This script will try and import the following from
   WP to b2evolution:
	1. posts
	2. comments
	3. categories
	4. users
	
   This is alpha software and subject to change.
*/
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

	// set error reporting to full in order to get useful info when something fails
	$prevlevel = error_reporting (E_ALL);

	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
  <title>WP to b2evolution Converter</title>
  <meta name="GENERATOR" content="Quanta Plus">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body bgcolor="#EEEEEE" text="#000000" link="#0000FF" alink="#FF0000" vlink="#7E0089">
	<h1>WordPress 2.3 to b2evolution importer</h1>
	[<a href="admin.php?ctrl=tools">Back to b2evolution</a>]
	<p><FONT SIZE="-2">Copyright &copy; 2007 <a href="http://hari.literaryforums.org/2007/10/04/wordpress-to-b2evolution-import-script">V.Harishankar</a>.  Released under the GNU GPL.</FONT></p>
	<?php

	// Check if user is logged in and is in group #1 (admins)
	if( !is_logged_in() || $current_User->Group->ID != 1 )
	{	// login failed
		debug_die( 'You must login with an administrator (group #1) account.' );
	}

	// The form has not yet been posted
	if ( ! isset ( $_POST['wp_db'] ) ) { ?>
		<P>Before running this importer, you must ensure that a proper <font color="#00CC00"><strong><em>NEW, EMPTY</em></strong></font> installation of b2evolution 2 exists! <strong><font color="#FF0000">IMPORTANT</font></strong>: This works <strong>only</strong> with WordPress 2.3 and above.</P>
		
		<p><strong><font color="#FF0000">Warning!!</strong> Your existing b2evolution posts, categories, tags, comments and users (except admin) will be removed if you run this script. Make sure you have a backup before you proceed.</font></p>
		
		<FORM action="admin.php?ctrl=wpimport" enctype="multipart/form-data" method="POST" >
		<h2>DB Settings</h2>
		<table>
			<tbody>
			<tr>
			<td>WordPress database name</td>
			<td><INPUT type="text" name="wp_db"><br></td>
			</tr>

			<tr>
			<td>WordPress table prefix</td>
			<td><INPUT type="text" name="wp_prefix" value="wp_"><br></td>
			</tr>
			
			<tr>
			<td>b2evolution database</td>
			<td><INPUT type="text" name="b2evo_db" value="<?php echo $db_config['name'] ?>"></td>
			</tr>

			<td>b2evolution table prefix</td>
			<td><INPUT type="text" name="b2evo_prefix" value="<?php echo $tableprefix ?>"></td>
			</tr>
			<tr>

			<tr>
			<td>Database host</td>
			<td><INPUT type="text" name="db_host" value="<?php echo $db_config['host'] ?>"></td>
			</tr>

			<tr>
			<td>Username</td>
			<td><INPUT type="text" name="db_user" value="<?php echo $db_config['user'] ?>"></td>
			</tr>

			<tr>
			<td>Password</td>
			<td><INPUT type="password" name="db_pass" value="<?php echo $db_config['password'] ?>"></td>
			</tr>
					
			<tr>
			<td>Default locale for imported posts</td>
			<td><INPUT type="text" name="locale" value="en-US"></td>
			</td>

			<tr>
			<td></td>
			<td><INPUT type="submit" value="import"></td>
			</tr>
			</tbody>
			</table>
		
		</FORM>
	<?php // The form has been posted; do the conversion
		}
		else
		{
			// Try to obtain some serious time to do some serious processing (15 minutes)
			@set_time_limit( 900 );

			// required fields initialization
			$wp_db = $_POST['wp_db'];
			$evo_db = $_POST['b2evo_db'];
			$host = $_POST['db_host'];
			$user = $_POST['db_user'];
			$password = $_POST['db_pass'];
			$wp = $_POST['wp_prefix'];
			$b2 = $_POST['b2evo_prefix'];
			$locale = $_POST['locale'];

			// establish database connection
			$con = mysql_connect ($host, $user, $password);
			if (! $con ) 
				die ( 'Error connecting to MySQL. Please check whether the server is running and the host, username and password fields are correct!' );
				
			// First remove existing database items in categories, users, postcats, items__item, comments, blogusers
			$db = mysql_select_db ($evo_db, $con);
			if (! $db)
				die ('b2evolution database name is incorrect. Please check your b2evolution installation.');
				
			$query = 'DELETE FROM '.$b2.'categories;';
			$flag = mysql_query ($query);
			if (! $flag )
				die ('Existing categories deleting failed. Cannot proceed.');
			
			$query = 'DELETE FROM '.$b2.'items__item;';
			$flag = mysql_query ($query);
			if (! $flag )
				die ('Existing posts deletion failed. Cannot proceed.');

			$query = 'DELETE FROM '.$b2.'postcats;';
			$flag = mysql_query ($query);
			if (! $flag )
				die ('Existing post categories deletion failed. Cannot proceed.');

			$query = 'DELETE FROM '.$b2.'comments;';
			$flag = mysql_query ($query);
			if (! $flag )
				die ('Existing comments deletion failed. Cannot proceed.');

			$query = 'DELETE FROM '.$b2.'items__itemtag;';
			$flag = mysql_query ($query);
			if (! $flag )
				die ('Existing post tags deletion failed. Cannot proceed.');

			$query = 'DELETE FROM '.$b2.'items__tag;';
			$flag = mysql_query ($query);
			if (! $flag )
				die ('Existing tags deletion failed. Cannot proceed.');

			$query = 'DELETE FROM '.$b2.'users WHERE user_ID <> 1;';
			$flag = mysql_query ($query);
			if (! $flag )
				die ('Existing users deletion failed. Cannot proceed.');
			
			$query = 'DELETE FROM '.$b2.'blogusers WHERE bloguser_user_ID <> 1;';
			$flag = mysql_query ($query);
			if (! $flag )
				die ('Existing user permissions deletion failed. Cannot proceed.');

			// CATEGORIES + TAGS
			echo '<h2>Trying to import categories and tags:</h2>';
			$cats = array();
			$tags = array();

			// select the wordpress database
			$db = mysql_select_db ($wp_db, $con);
			if (! $db)
				die ('WordPress database name is incorrect. Please check the name and try again.');
			
			// get the list of taxonomy terms. includes categories, link cats and tags as well
			$query = 'SELECT *
									FROM '.$wp.'terms;' ;
			$res = mysql_query ($query);
			if (! $res )
				die ('Query failed. Please check your WordPress installation.');
			
			$i = 0;
			while( $row = mysql_fetch_array ($res, MYSQL_ASSOC) )
			{
				// in order to establish whether a term is a category or not
				$query2 = 'SELECT *
								     FROM '.$wp.'term_taxonomy
								    WHERE term_id='.$row['term_id'].';';
				$res2 = mysql_query ($query2);
				if (! $res2)
					die ('Query 2 failed. Please check your WordPress installation.');
				$row2 = mysql_fetch_array ($res2, MYSQL_ASSOC);

				// if it is a category only then import. ignore tags and link categories
				switch( $row2['taxonomy'] )
				{
					case 'category':
						echo 'Reading cat: '.$row['name'].'<br>';
						$cats[$i]['name'] = $row['name'];
						$cats[$i]['slug'] = $row['slug'];
						$cats[$i]['description'] = $row2['description'];
						$cats[$i]['cat_id'] = $row2['term_taxonomy_id'];
						$i ++;
						break;

					case 'post_tag':
						echo 'Reading tag: '.$row['name'].'<br>';
						$tag_id = $row2['term_taxonomy_id'];
						$tags[$tag_id]['name'] = strtolower( $row['name'] );
						$tags[$tag_id]['slug'] = $row['slug'];
						$tags[$tag_id]['description'] = $row2['description'];
						break;
				}
				mysql_free_result ($res2);
			}
			mysql_free_result ($res);

			if( empty($cats) )
			{
				die( 'There must be at least one category!' );
			}

			// Use the first category as the default category in case we find uncategorized posts later on.
			$default_category_ID = $cats[0]['cat_id'];

			// select the evolution database
			$db = mysql_select_db ($evo_db, $con);
			if (! $db)
				die ('b2evolution database name is incorrect. Please check the name and try again.');
			foreach ($cats as $category)
			{
				// insert each category into the evolution database
				$query = 'INSERT INTO '.$b2.'categories (cat_ID, cat_name, cat_urlname, cat_blog_ID, cat_description) VALUES ("'.$category['cat_id'].'", "'.$category['name'].'", "'.$category['slug'].'", "1", "'.$category['description'].'");';

				$flag = mysql_query ($query);

				if (! $flag )
					die ('Category importing failed. Please check your b2evolution installation.');
			}
			echo '<font color="#00CC00">Categories inserted successfully!</font><br>';

			// INSERT TAGS:
			foreach( $tags as $tag_id => $tag )
			{
				// insert each tags into the evolution database
				$query = 'INSERT INTO '.$b2.'items__tag(tag_ID, tag_name)
									VALUES ( '.$tag_id.', "'.$tag['name'].'" );';
				$flag = mysql_query ($query);

				if (! $flag )
					die ('Tag importing failed. Please check your b2evolution installation.');
			}
			echo '<font color="#00CC00">Tags inserted successfully!</font><br>';


			// Now import the posts into b2evolution
			echo '<h2>Trying to import posts</h2>';
			
			$posts = array ();
			$db = mysql_select_db ($wp_db, $con);
			if (! $db)
				die ('WordPress database name is incorrect. Please check the name and try again.');
			
			$query = 'SELECT * FROM '.$wp.'posts WHERE post_type="post" OR post_type="page";' ;
			$res = mysql_query ($query);
			if (! $res )
				die ('Query failed. Please check your WordPress installation.');

			$i = 0;
			while ( $row = mysql_fetch_array ($res, MYSQL_ASSOC) )
			{
				$posts[$i]['post_id'] = $row['ID'];
				$posts[$i]['slug'] = $row['post_name'];
				$posts[$i]['title'] = $row['post_title'];
				$posts[$i]['status'] = $row['post_status'];
				$posts[$i]['create_date'] = $row['post_date'];
				$posts[$i]['modified_date'] = $row['post_modified'];
				$posts[$i]['excerpt'] = $row['post_excerpt'];
				$posts[$i]['comment_status'] = $row['comment_status'];
				$posts[$i]['content'] = $row['post_content'];
				$posts[$i]['author'] = $row['post_author'];
				$posts[$i]['type'] = 1;

				if (strcmp ($row['post_type'], 'page') == 0)
					$posts[$i]['type'] = 1000;

				echo 'Reading: '.$posts[$i]['title'].'<br>';

				// Now to get the cats for each post. Includes both CATS and TAGS
				$j = 0;
				$posts[$i]['cats'] = array ();
				$posts[$i]['tags'] = array ();

				// Get all reltated terms:
				$query2 = 'SELECT *
										 FROM '.$wp.'term_relationships
										WHERE object_id='.$row['ID'].'; ';
				$res2 = mysql_query ($query2);
				if (! $res2)
					die ('Query 2 failed. Please check your WordPress installation.');

				// Lop through terms
				while( $row2 = mysql_fetch_array ($res2, MYSQL_ASSOC) )
				{
					// Get each specific term:
					$query3 = 'SELECT *
											 FROM '.$wp.'term_taxonomy
											WHERE term_taxonomy_id='.$row2['term_taxonomy_id'].';';
					$res3 = mysql_query ($query3);
					if (! $res3 )
						die ('Query 3 failed. Please check your WordPress installation.');
					$row3 = mysql_fetch_array ($res3, MYSQL_ASSOC);
					switch( $row3['taxonomy'] )
					{
						case 'category':
							$posts[$i]['cats'][$j] = $row2['term_taxonomy_id'];
							$j ++;
							break;

						case 'post_tag':
							$posts[$i]['tags'][] = $row2['term_taxonomy_id'];
							break;
					}

					mysql_free_result ($res3);
				}
				mysql_free_result ($res2);
				$i ++;
			}
			mysql_free_result ($res);

			// select the evolution database
			$db = mysql_select_db ($evo_db, $con);
			if (! $db)
				die ('b2evolution database name is incorrect. Please check the name and try again.');

			foreach ($posts as $post)
			{
				echo '<br/>Inserting: '.$post['title'];

				// Check that we have at least one category:
				if( empty($post['cats']) )
				{	// Use default category:
					$post['cats'][0] = $default_category_ID;
				}

				// set the post rendering options. TODO: this could probably be an option for the user before importing
				$postrenderers = 'b2evSmil.b2evALnk.b2WPAutP';

				// Check that slug is not empty. Mind you, in WP it CAN happen!
				if( empty( $post['slug'] ) )
				{
					$post['slug'] = preg_replace( '¤[^A-Za-z0-9]¤', '-', $post['post_id'].'-'.$post['title'] );
					echo '<br /> ** WARNING: generated automatic slug: '.$post['slug'];
				}

				// query to insert the posts into the b2evolution table
				$query = 'INSERT INTO '.$b2.'items__item (post_ptyp_ID, post_ID, post_main_cat_ID, post_creator_user_ID, post_lastedit_user_ID, post_datestart, post_datecreated, post_datemodified, post_status, post_locale, post_content, post_excerpt, post_title, post_urltitle, post_comment_status, post_renderers)
				VALUES ("'. $post['type'].'", "'.$post['post_id'].'", "'.$post['cats'][0].'", "'. $post['author'].'", "'.$post['author'].'", "'.$post['create_date'].'", "'.$post['create_date'].'", "'.$post['modified_date'].'", "'.'published'.'", "'.$locale.'", "'.mysql_real_escape_string($post['content']).'", "'.mysql_real_escape_string($post['excerpt']).'", "'.mysql_real_escape_string($post['title']).'", "'.$post['slug'].'", "'.$post['comment_status'].'", "'.$postrenderers.'");';

				$flag = mysql_query ($query);
				if (! $flag )
				{
					pre_dump( $query );
					die ('Post importing failed.');
				}


				// insert the post categories into the postcats table
				foreach($post['cats'] as $cat)
				{
					// query to insert each category for the particular post
					$query = 'INSERT INTO '.$b2.'postcats (postcat_post_ID, postcat_cat_ID) VALUES ("'.$post['post_id']. '", "'.$cat.'");';
					$flag = mysql_query ($query);
					if (! $flag )
						die ('Post categories insertion failed. Please check your b2evolution installation.');
				}

				// insert the post tags
				foreach($post['tags'] as $tag_id)
				{
					$query = 'INSERT INTO '.$b2.'items__itemtag (itag_itm_ID, itag_tag_ID)
												 VALUES ('.$post['post_id'].', '.$tag_id.');';
					$flag = mysql_query ($query);
					if (! $flag )
						die ('Post tags insertion failed. Please check your b2evolution installation.');
				}

			}
			echo '<font color="#00CC00">Posts and post categories imported successfully!</font>';

			// Now import the comments
			echo '<h2>Trying to import comments</h2>';
			$comments = array ();

			// select the wordpress database
			$db = mysql_select_db ($wp_db);
			if (! $db)
				die ('WordPress database name is incorrect. Please check the name and try again.');

			// discard the spam comments. select only comments where status is either 'in moderation' or 'approved'
			$query = 'SELECT * FROM '.$wp.'comments WHERE comment_approved="0" OR comment_approved="1" ORDER BY comment_date ASC;';
			
			$res = mysql_query ($query);
			if (! $res )
				die ('Query failed. Please check your WordPress installation.');
			
			$i = 0;
			while ($row = mysql_fetch_array ($res, MYSQL_ASSOC) )
			{
				// set the values from comments table
				$comments[$i]['comment_id'] = $row['comment_ID'];
				$comments[$i]['post_id'] = $row['comment_post_ID'];
				$comments[$i]['author'] = $row['comment_author'];
				$comments[$i]['email'] = $row['comment_author_email'];
				$comments[$i]['url'] = $row['comment_author_url'];
				$comments[$i]['ip'] = $row['comment_author_IP'];
				$comments[$i]['date'] = $row['comment_date'];
				$comments[$i]['content'] = $row['comment_content'];
				$comments[$i]['author_id'] = $row['user_id'];
				
				// set default comment status to published
				$comments[$i]['status'] = 'published';
				// if the comment isn't approved set it to draft
				if ($row['comment_approved'] == 0)
					$comments[$i]['status'] = 'draft';
					
				// default comment type is 'comment
				$comments[$i]['type'] = 'comment';
				// if it is a pingback or trackback change the type accordingly
				if ($row['comment_type'] == 'pingback' || $row['comment_type'] == 'trackback')
					$comments[$i]['type'] = 'pingback';

				$i ++;
			}
			// free the query result set
			mysql_free_result ($res);
			
			// select the evolution db
			$db = mysql_select_db ($evo_db, $con);
			if (! $db)
				die ('b2evolution database name is incorrect. Please check the name and try again.');
				
			foreach ($comments as $comment)
			{
				// escape the string and replace UNIX newlines to line breaks in order to 
				// render properly in b2evolution
				$ccontent = mysql_real_escape_string ($comment['content']);
				$ccontent = str_replace ('\r\n', '<br />', $ccontent);

				// query to insert the comments into the b2evolution table
				$query = 'INSERT INTO '.$b2.'comments (comment_ID, comment_post_ID, comment_type, comment_status, comment_author_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content, comment_allow_msgform)
				VALUES ("'. $comment['comment_id'].'", "'.$comment['post_id'].'", "'.$comment['type'].'", "'.$comment['status'].'", "'.$comment['author_id'].'", "'.$comment['author'].'", "'.$comment['email'].'", "'.$comment['url'].'", "'.$comment['ip'].'", "'.$comment['date'].'", "'.$ccontent.'", "1");';
				
				$flag = mysql_query ($query);
				if (! $flag)
					die ('Comment importing failed. Please check your b2evolution installation.');
			
			}
			echo '<font color="#00CC00">Comments imported successfully!</font>';

			// Now to import users. Note: all users other than admin will be imported and then they will be set to the default level
			// of 0
			echo '<h2>Trying to import all users (except admin)</h2>';
			$users = array ();
			
			// select the wordpress database
			$db = mysql_select_db ($wp_db, $con);
			if (! $db)
				die ('WordPress database name is incorrect. Please check the name and try again.');
			
			// select all users except the admin user
			$query = 'SELECT * FROM '. $wp.'users WHERE user_login <> "admin";';
			$res = mysql_query ($query);
			if (! $res )
				die ('Query failed. Please check your WordPress installation.');
			
			$i = 0;
			while ( $row = mysql_fetch_array ($res, MYSQL_ASSOC) )
			{
				// set all the values from the user table
				$users[$i]['id'] = $row['ID'];
				$users[$i]['login'] = $row['user_login'];
				$users[$i]['password'] = $row['user_pass'];
				$users[$i]['nickname'] = $row['user_nicename'];
				$users[$i]['email'] = $row['user_email'];
				$users[$i]['url'] = $row['user_url'];
				$users[$i]['date'] = $row['user_registered'];
				$users[$i]['firstname'] = $row['display_name'];
				echo 'Reading: '.$users[$i]['login'].'<br>';
				$i ++;
			}
			mysql_free_result ($res);

			// select the evolution db
			$db = mysql_select_db ($evo_db, $con);
			if (! $db )
				die ('b2evolution database name is incorrect. Please check the name and try again.');

			foreach ($users as $a_user)
			{
				// Import the user
				$query = 'INSERT INTO '.$b2.'users (user_ID, user_login, user_pass, user_firstname, user_nickname, user_email, user_url, dateYMDhour, user_validated, user_grp_ID) VALUES ("'.$a_user['id'].'", "'.$a_user['login'].'", "'.$a_user['password'].'", "'.$a_user['firstname'].'", "'.$a_user['nickname'].'", "'.$a_user['email'].'", "'.$a_user['url'].'", "' .$a_user['date'].'", "1", "4");';
				
				$flag = mysql_query ($query);
				if (! $flag)
					die ('User importing failed. Please check your b2evolution installation.');
					
				// Import the permissions for blog for the user
				$query = 'INSERT INTO '.$b2.'blogusers (bloguser_blog_ID, bloguser_user_ID, bloguser_ismember) VALUES ("1", "'.$a_user['id'].'", "1");';

				$flag = mysql_query ($query);
				if (! $flag)
					die ('User (permissions) importing failed. Please check you b2evolution installation.');
			}
			echo '<font color="#00CC00">Users imported successfully! <strong>NOTE:</strong> all users are set to basic users level by default. You should probably reconfigure user permissions in the admin control panel if you want to give them higher privileges.</font>';

			// All done
			echo '<br><br>';
			echo '<strong><font color="#00CC00">Everything imported correctly. Try out your new b2evolution blog!</font></strong>';

			// close the connection to the MySQL server
			mysql_close ($con);
	 } ?>
	 <?php	// reset the PHP error reporting to the previous level
	 	 error_reporting ($prevlevel); ?>
</body>
</html>

