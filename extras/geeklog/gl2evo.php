<?php
/* **************************************************************************************
 * gl2evo - Geeklog to b2evolution migration tool 
 *
 * This script imports the stories and comments from geeklog to b2evolution.
 * It was written against versions: Geeklog-1.3.9 and b2evolution-0.9.0
 *
 * Released under the GNU Public License (GPL) http://www.gnu.org/copyleft/gpl.html
 * copyright (c)2004 by Jeff Bearer <mail1@jeffbearer.com>
 **************************************************************************************** */

// Geeklog Database settings
$gl_dbuser = "user";		// user that can access the geeklog database
$gl_dbpass = "pass";		// password for that user
$gl_dbhost = "localhost";	// database host for gl
$gl_database = "db";	// database name for gl
$gl_prefix = "gl_";		// geeklog prefix.

// evo database settings
$evo_dbuser = $gl_dbuser;	// user that can access the evo database
$evo_dbpass = $gl_dbpass;	// password for that user
$evo_dbhost = $gl_dbhost;	// database host for evo
$evo_database = $gl_database;	// database name for evo
$evo_prefix = "evo_";

$evo_locale = "en-US";		// default locale for the imported stories
$evo_blog_id = '5';		// Set the blogid to import into
$evo_root = "../../blogs";  // location of the evo install


/* *************************************
 * Author Translation Key
 *
 * Used to map old author id's to the new ones.
 *
 * $author['geeklogs uid'] = 'evo's user ID';
 * ************************************ */

$author['2'] = '3';

/* *************************************
 * Category Translation Key
 *
 * Used to map old categories id's to the new ones.
 *
 * $category['geeklogs tid'] = 'evo's category ID';
 * ************************************ */

$category['General'] = '14';
$category['photos'] = '16';
$category['movies'] = '19';
$category['computing'] = '17';


/* **************************************************************** */
/* **************************************************************** */
require_once($evo_root."/evocore/_item.funcs.php");


// Connect to the databases
$db_gl = mysql_connect($gl_dbhost,$gl_dbuser,$gl_dbpass);
if (!$db_gl) die('Could not connect: ' . mysql_error());
$gl_sel = mysql_select_db($gl_database, $db_gl);
if (!$gl_sel) die ('Can\'t use $gl_database : ' . mysql_error());

$db_evo = mysql_connect($evo_dbhost,$evo_dbuser,$evo_dbpass);
if (!$db_evo) die('Could not connect: ' . mysql_error());
$evo_sel = mysql_select_db($evo_database, $db_evo);
if (!$evo_sel) die ('Can\'t use $evo_database : ' . mysql_error());


/* Auto Category Code - not tested much.
   Creates new categories in evo and sets up it's own category mapping
   the mapping above should be removed

$sql = "SELECT tid,topic from ".$gl_prefix."topics ORDER BY topic";
$result = mysql_query($sql,$db_gl);
while($row = mysql_fecth_assoc($result){
	$ins = "INSERT INTO ".$evo_prefix."categories (cat_name,cat_blog_id) VALUES ('$row[topic]','$evo_blog_id');
	$inres = mysql_query($ins,$db_evo);
	$cat_id = mysql_insert_id($inres);
	$category['$row[tid]'] = $cat_id;
}
*/


// Select stories for import
$sql="SELECT sid,uid,draft_flag,tid,date,title,introtext,bodytext,postmode FROM ".$gl_prefix."stories";
$result = mysql_query($sql,$db_gl);
while($row = mysql_fetch_assoc($result)){

	// Author translation
	if($author[$row[uid]]) $post_author = $author[$row[uid]];
	else {
		echo "Author Translation Error! SID:$row[sid], UID:$row[uid]\n";
		exit;
	}

	// Category translation
	if($category[$row[tid]]) $post_category = $category[$row[tid]];
	else {
		echo "Category Translation Error! SID:$row[sid], TID:$row[tid]\n";
		exit;
	}
	
	// Translate the draft flag
	if($row[draft_flag]==1) $post_status = "draft";
	else $post_status = "published";


	// Create the URL title, by underscoring spaces and removing all non url chars. use the function from b2evo
	$post_urltitle = urltitle_validate2( $row[title], $row[title], 0 );


	// Combine the intro and the body into the content field.
	$post_content = "$row[introtext]\n<!--more-->\n$row[bodytext]";


	// Replace some non XHTML compliant tags that I used.
	$post_content = str_replace( '<P>', '<br /><br />', $post_content );	
	$post_content = str_replace( '<p>', '<br /><br />', $post_content );	
	$post_content = str_replace( '<BR>', '<br />', $post_content );	
	$post_content = str_replace( '<br>', '<br />', $post_content );	
	$post_content = str_replace( 'HREF', 'href', $post_content );	

	// Translate the path to the images
	$post_content = str_replace( '/images/articles', '/media', $post_content );

	// Build the insert sql for the post
	$ins="INSERT INTO ".$evo_prefix."posts (
		post_creator_user_ID,
		post_datestart,
		post_datemodified,
		post_status,
		post_locale,
		post_content,
		post_title,
		post_urltitle,
		post_main_cat_ID,
		post_flags,
		post_wordcount
		) VALUES (
		'$post_author',
		'$row[date]',
		'1969-12-31 19:00:00',
		'$post_status',
		'$evo_locale',
		'".mysql_escape_string($post_content)."',
		'$row[title]',
		'$post_urltitle',
		'$post_category',
		'pingsdone',
		'".bpost_count_words($post_content)."')";


	$inresult = mysql_query($ins,$db_evo);
	if(mysql_error()) {
		echo "Insert Error on Story:$row[sid]\nSQL:$ins\n".mysql_error()."\n";;
		exit;
	}

	// Get the post ID
	$post_ID = mysql_insert_id();

	// Add the category to the extra categories
	$ins="INSERT INTO ".$evo_prefix."postcats (postcat_post_ID, postcat_cat_ID)
		VALUES ('$post_ID','$post_category')";
	$inresult = mysql_query($ins,$db_evo);
        if(mysql_error()) {
                echo "Insert Error on Story:$row[sid]\nSQL:$ins\n".mysql_error()."\n";;
                exit;
        }


	// Import the comments for this post
	$sql = "SELECT cid,date,comment,username,fullname FROM ".$gl_prefix."comments left join gl_users ON gl_comments.uid=gl_users.uid WHERE sid='$row[sid]'";
	$result2 = mysql_query($sql,$db_gl);
	if(mysql_num_rows($result2)>0){
		while($row2 = mysql_fetch_assoc($result2)){
			if($row2[fullname]=='') $row2[fullname]=$row2[username];
			$ins="INSERT INTO ".$evo_prefix."comments (comment_post_ID,comment_date,comment_author,comment_content)
		              VALUES ('$post_ID','$row2[date]','".mysql_escape_string($row2[fullname])."','".mysql_escape_string($row2[comment])."')";
		        $inresult = mysql_query($ins,$db_evo);
		        if(mysql_error()) {
		                echo "Insert Error on Comment:$row[cid]\nSQL:$ins\n".mysql_error()."\n";
       			        exit;
        		}
		}
	}
	$result2=null;
}
echo "Database migration complete!\n";
echo "Copy any images from /images/articles of geeklog to /media of b2evo";



/* *************************************************************************************
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 * ************************************************************************************ */
function urltitle_validate2( $urltitle, $title, $post_ID = 0, $query_only = false )
{
        global $db_evo;

        $urltitle = trim( $urltitle );

        if( empty( $urltitle )  ) $urltitle = $title;
        if( empty( $urltitle )  ) $urltitle = 'title';

        // echo 'staring with: ', $urltitle, '<br />';

        // Replace HTML entities
        $urltitle = htmlentities( $urltitle, ENT_NOQUOTES );
        // Keep only one char in emtities!
        $urltitle = preg_replace( '/&(.).+?;/', '$1', $urltitle );
        // Remove non acceptable chars
        $urltitle = preg_replace( '/[^A-Za-z0-9]+/', '_', $urltitle );
        $urltitle = preg_replace( '/^_+/', '', $urltitle );
        $urltitle = preg_replace( '/_+$/', '', $urltitle );
        // Uppercase the first character of each word in a string
        $urltitle = strtolower( $urltitle );

        preg_match( '/^(.*?)(_[0-9]+)?$/', $urltitle, $matches );

        $urlbase = substr( $matches[1], 0, 40 );
        $urltitle = $urlbase;
        if( isset( $matches[2] ) )
        {
                $urltitle = $urlbase . $matches[2];
        }


        // Find all occurrences of urltitle+number in the DB:
        $sql = "SELECT post_urltitle
                                        FROM evo_posts
                                        WHERE post_urltitle REGEXP '^".$urlbase."(_[0-9]+)?$'
                                          AND ID <> $post_ID";
	$result = mysql_query($sql,$db_evo);
	while($row = mysql_fetch_assoc($result)){ $rows[] = $row;}
        $exact_match = false;
        $highest_number = 0;
        if( count( $rows ) ) foreach( $rows as $row )
        {
                $existing_urltitle = $row['post_urltitle'];
                // echo "existing = $existing_urltitle <br />";
                if( $existing_urltitle == $urltitle )
                { // We have an exact match, we'll have to change the number.
                        $exact_match = true;
                }
                if( preg_match( '/_([0-9]+)$/', $existing_urltitle, $matches ) )
                {       // This one has a number, we extract it:
                        $existing_number = (integer) $matches[1];
                        if( $existing_number > $highest_number )
                        { // This is th enew high
                                $highest_number = $existing_number;
                        }
                }
        }
        // echo "highest existing number = $highest_number <br />";

        if( $exact_match && !$query_only )
        {       // We got an exact match, we need to change the number:
                $urltitle = $urlbase.'_'.($highest_number + 1);
        }

        // echo "using = $urltitle <br />";

        return $urltitle;
}
?>
