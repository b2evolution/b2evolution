<?php
/**** B2 API ****/
# this is used by w.bloggar for example...
# note: the b2 API currently consists of the Blogger API,
#       plus the following methods:
# 			b2.newPost , b2.getCategories
# Note: the b2 API will be replaced by the standard Weblogs.API once the specs are defined.

$debug = 0;

require_once(dirname(__FILE__)."/../conf/_config.php");
require_once(dirname(__FILE__)."/$xmlsrv_dirout/$core_subdir/_functions_xmlrpc.php");
require_once(dirname(__FILE__)."/$xmlsrv_dirout/$core_subdir/_functions_xmlrpcs.php");
require_once(dirname(__FILE__)."/$xmlsrv_dirout/$core_subdir/_functions_template.php");
require_once(dirname(__FILE__)."/$xmlsrv_dirout/$core_subdir/_functions.php");
require_once(dirname(__FILE__)."/$xmlsrv_dirout/$core_subdir/_vars.php");

$use_cache = 1;
$post_autobr = 1;
$post_default_title = ""; // posts submitted via the xmlrpc interface get that title
$post_default_category = 1; // posts submitted via the xmlrpc interface go into that category

$xmlrpc_logging = 0;		// Set to 1 if you want to enable logging

function logIO($io,$msg)
{
	global $xmlrpc_logging;
	if ($xmlrpc_logging) 
	{
		$fp = fopen( dirname(__FILE__).'/xmlrpc.log',"a+");
		$date = date("Y-m-d H:i:s ");
		$iot = ($io == "I") ? " Input: " : " Output: ";
		fwrite($fp, "\n\n".$date.$iot.$msg);
		fclose($fp);
	}
	return true;
}

function starify($string)
{
	$i = strlen($string);
	return str_repeat('*', $i);
}



### b2.newPost ###

$b2newpost_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString));

$b2newpost_doc='Adds a post, blogger-api like, +title +category +postdate';

function b2newpost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $blog_ID,$cache_userdata, $use_weblogsping,$post_autobr;
	global $post_default_title,$post_default_category;
	global $cafelogID, $sleep_after_edit;
	$err="";

	dbconnect();

	$username=$m->getParam(2);
	$password=$m->getParam(3);
	$content=$m->getParam(4);
	$title=$m->getParam(6);
	$category=$m->getParam(7);
	$postdate=$m->getParam(8);

	$username = $username->scalarval();
	$password = $password->scalarval();
	$content = $content->scalarval();
	$title = $title->scalarval();
	$category = $category->scalarval();
	$postdate = $postdate->scalarval();


	if (user_pass_ok($username,$password)) 
	{
		$userdata = get_userdatabylogin($username);
		$user_ID = $userdata["ID"];
		$user_level = $userdata["user_level"];
		if ($user_level < 1) 
		{
			return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
           "Sorry, level 0 users can not post");
		}

		$time_difference = get_settings("time_difference");
		if ($postdate != "") 
		{
			$now = $postdate;
		}
		else
		{
			$now = date("Y-m-d H:i:s",(time() + ($time_difference * 3600)));
		}

		// CHECK and FORMAT content	
		$post_title = format_to_post($post_title,0,0);
		$content = format_to_post($content,0,0);
	
		if( $errstring = errors_string( 'Cannot post, please correct these errors:', '' ) )
		{
			return new xmlrpcresp(0, $xmlrpcerruser+2, $errstring ); // user error 2
		}

		// INSERT NEW POST INTO DB:
		$post_ID = bpost_create( $user_ID, $post_title, $content, $now, $category );
		if (!$post_ID)
			return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
						"For some strange yet very annoying reason, your entry couldn't be posted.");


		if (!isset($blog_ID)) { $blog_ID = get_catblog($category); }
		$blogparams = get_blogparams_by_ID( $blog_ID );

		if (isset($sleep_after_edit) && $sleep_after_edit > 0) {
			sleep($sleep_after_edit);
		}

		pingback( true, $content, $post_title, '', $post_ID, $blogparams, false);
		pingb2evonet( $blogparams, $post_ID, $post_title, false );
		pingWeblogs($blogparams, false );
		pingBlogs($blogparams);
		pingCafelog($cafelogID, $post_title, $post_ID);

		return new xmlrpcresp(new xmlrpcval("$post_ID"));

	} else {
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}
}



/*
 * b2getcategories(-)
 * b2.getCategories
 *
 * fplanque: added multiblog support
 */
$b2getcategories_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));

$b2getcategories_doc='given a blogID, gives a struct that list categories in that blog, using categoryID and categoryName. categoryName is there so the user would choose a category name from the client, rather than just a number. however, when using b2.newPost, only the category ID number should be sent.';

function b2getcategories($m)
{
	global $xmlrpcerruser,$tablecategories;

	dbconnect();

	$blogid=$m->getParam(0);
	$blogid = $blogid->scalarval();

	$username=$m->getParam(1);
	$username = $username->scalarval();

	$password=$m->getParam(2);
	$password = $password->scalarval();

	$userdata = get_userdatabylogin($username);


	if (user_pass_ok($username,$password)) 
	{
		$sql = "SELECT * FROM $tablecategories ";
		if( $blogid > 1 ) $sql .= "WHERE cat_blog_ID = $blogid ";
		$sql .= "ORDER BY cat_name ASC";
		$result = mysql_query($sql) or die($sql);

		$i = 0;
		while($row = mysql_fetch_object($result))
		{
			$cat_name = $row->cat_name;
			$cat_ID = $row->cat_ID;

			$struct[$i] = new xmlrpcval(array("categoryID" => new xmlrpcval($cat_ID),
										  "categoryName" => new xmlrpcval($cat_name)
										  ),"struct");
			$i = $i + 1;
		}

		$data = array($struct[0]);
		for ($j=1; $j<$i; $j++) {
			array_push($data, $struct[$j]);
		}

		$resp = new xmlrpcval($data, "array");

		return new xmlrpcresp($resp);

	}
	else
	{
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}
}



### b2.getPostURL ###

$b2_getPostURL_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));

$b2_getPostURL_doc = 'Given a blog ID, username, password, and a post ID, returns the URL to that post.';

function b2_getPostURL($m)
{
	global $xmlrpcerruser;
	global $siteurl;

	dbconnect();

	$blog_ID = $m->getParam(0);
	$blog_ID = $blog_ID->scalarval();

	$username=$m->getParam(2);
	$username = $username->scalarval();

	$password=$m->getParam(3);
	$password = $password->scalarval();

	$post_ID = $m->getParam(4);
	$post_ID = intval($post_ID->scalarval());

	$userdata = get_userdatabylogin($username);

	if ($userdata["user_level"] < 1) {
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
	   "Sorry, users whose level is zero, can not use this method.");
	}

	if (user_pass_ok($username,$password))
	{

		// Getting current blog info (fplanque: added)
		get_blogparams();
		$blog_URL = get_bloginfo('blogurl');

		$postdata = get_postdata($post_ID);

		if (!($postdata===false))
		{
			$title = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $postdata['Title']);

			// this code is blatantly derived from permalink_link()
			$archive_mode = get_settings('archive_mode');
			switch($archive_mode)
			{
				case 'daily':
					$post_URL = $blog_URL.'?m='.substr($postdata['Date'],0,4).substr($postdata['Date'],5,2).substr($postdata['Date'],8,2).'#'.$title;
					break;
				case 'monthly':
					$post_URL = $blog_URL.'?m='.substr($postdata['Date'],0,4).substr($postdata['Date'],5,2).'#'.$title;
					break;
				case 'weekly':
					if((!isset($cacheweekly)) || (empty($cacheweekly[$postdata['Date']]))) {
						$sql = "SELECT WEEK('".$postdata['Date']."')";
						$result = mysql_query($sql);
						$row = mysql_fetch_row($result);
						$cacheweekly[$postdata['Date']] = $row[0];
					}
					$post_URL = $blog_URL.'?m='.substr($postdata['Date'],0,4).'&amp;w='.$cacheweekly[$postdata['Date']].'#'.$title;
					break;
				case 'postbypost':
					$post_URL = $blog_URL.'?p='.$post_ID;
					break;
			}
		} else {
			$err = 'This post ID ('.$post_ID.') does not correspond to any post here.';
		}

		if ($err)
		{
			return new xmlrpcresp(0, $xmlrpcerruser, $err);
		} else {
			return new xmlrpcresp(new xmlrpcval($post_URL));;
		}

	}
	else
	{
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}

}

/**** /B2 API ****/



/**** Blogger API ****/

# as described on http://plant.blogger.com/api and in various messages in http://groups.yahoo.com/group/bloggerDev/
#
# another list of these methods is there http://www.tswoam.co.uk/blogger_method_listing.html
# so you won't have to browse the eGroup to find all the methods
#
# special note: Evan please keep _your_ API page up to date :p



### blogger.newPost ###

$bloggernewpost_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));

$bloggernewpost_doc='Adds a post, blogger-api like';

function bloggernewpost($m)
{
	global $xmlrpcerruser; // import user errcode value
	global $blog_ID,$cache_userdata, $use_weblogsping,$post_autobr;
	global $post_default_title,$post_default_category;
	global $cafelogID, $sleep_after_edit;
	$err="";

	logIO('I','Called function: blogger.newPost');

	dbconnect();

	$username=$m->getParam(2);
	$password=$m->getParam(3);
	$content=$m->getParam(4);
	$publish=$m->getParam(5);

	$username = $username->scalarval();
	$password = $password->scalarval();
	$content = $content->scalarval();
	$publish = $publish->scalarval();
	$status = $publish ? 'published' : 'draft';
	logIO('I',"Publish: $publish -> Status: $status");

	if (user_pass_ok($username,$password)) 
	{
		$userdata = get_userdatabylogin($username);
		$user_ID = $userdata["ID"];
		$user_level = $userdata["user_level"];
		if ($user_level < 1) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
           "Sorry, level 0 users can not post");
		}

		$post_title = addslashes(xmlrpc_getposttitle($content));
		$post_category = xmlrpc_getpostcategory($content);

		$content = xmlrpc_removepostdata($content);

		$time_difference = get_settings("time_difference");
		$now = date("Y-m-d H:i:s",(time() + ($time_difference * 3600)));

		// CHECK and FORMAT content	
		$post_title = format_to_post($post_title,0,0);
		$content = format_to_post($content,0,0);
	
		if( $errstring = errors_string( 'Cannot post, please correct these errors:', '' ) )
		{
			return new xmlrpcresp(0, $xmlrpcerruser+2, $errstring ); // user error 2
		}

		// INSERT NEW POST INTO DB:
		$post_ID = bpost_create( $user_ID, $post_title, $content, $now, $post_category, array( $post_category ), $status, 'en', '', 0, $publish );

		if (!$post_ID)
			return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
           "For some strange yet very annoying reason, your entry couldn't be posted.");

		logIO("O","Posted ! ID: $post_ID");

		if (isset($sleep_after_edit) && $sleep_after_edit > 0) {
			sleep($sleep_after_edit);
		}

		if( $publish )
		{	// If post is publicly published:
			logIO("O","Doing pingbacks...");
			if (!isset($blog_ID)) { $blog_ID = get_catblog($post_category); }
			$blogparams = get_blogparams_by_ID( $blog_ID );
			pingback( true, $content, $post_title, '', $post_ID, $blogparams, false);
			logIO("O","Pinging b2evolution.net...");
			pingb2evonet( $blogparams, $post_ID, $post_title, false );
			logIO("O","Pinging Weblogs...");
			pingWeblogs( $blogparams, false );
			logIO("O","Pinging Blo.gs...");
			pingBlogs($blogparams);
			logIO("O","Pinging Cafelog...");
			pingCafelog($cafelogID, $post_title, $post_ID);
		}
		
		logIO("O","All done.");

		return new xmlrpcresp(new xmlrpcval("$post_ID"));

	} 
	else
	{
		logIO("O","Wrong username/password combination <strong>$username / $password</strong>");
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}
}



### blogger.editPost ###

$bloggereditpost_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));

$bloggereditpost_doc='Edits a post, blogger-api like';

function bloggereditpost($m) 
{

	global $xmlrpcerruser; // import user errcode value
	global $blog_ID,$cache_userdata,$tableposts, $tablepostcats, $use_weblogsping,$post_autobr;
	global $post_default_title,$post_default_category;
	global $cafelogID, $sleep_after_edit;
	$err="";

	logIO('I','Called function: blogger.editPost');

	dbconnect();

	$post_ID=$m->getParam(1);
	$username=$m->getParam(2);
	$password=$m->getParam(3);
	$newcontent=$m->getParam(4);
	$publish=$m->getParam(5);

	$post_ID = $post_ID->scalarval();
	$username = $username->scalarval();
	$password = $password->scalarval();
	$newcontent = $newcontent->scalarval();
	$publish = $publish->scalarval();
	$status = $publish ? 'published' : 'draft';
	logIO('I',"Publish: $publish -> Status: $status");

	if( ! ($postdata = get_postdata($post_ID)) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, "No such post."); // user error 2
	}
	
	logIO('O','Old post Title: '.$postdata['Title']);
	$post_author_ID=$postdata['Author_ID'];
	logIO('O',"Post Author ID: $post_author_ID");
	$post_authordata=get_userdata($post_author_ID);

	$userdata = get_userdatabylogin($username);
	$user_ID = $userdata["ID"];
	$user_level = $userdata["user_level"];

	if (($user_ID != $post_author_ID) && ($user_level <= $post_authordata['user_level'])) 
	{
			return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
           "Sorry, you do not have the right to edit this post");
	}

	if (user_pass_ok($username,$password)) 
	{

		if ($user_level < 1) 
		{
			return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
           "Sorry, level 0 users can not edit posts");
		}

		$content = $newcontent;

		$post_title = xmlrpc_getposttitle($content);
		$post_category = xmlrpc_getpostcategory($content);

		$content = xmlrpc_removepostdata($content);

		// CHECK and FORMAT content	
		$post_title = format_to_post($post_title,0,0);
		$content = format_to_post($content,0,0);
	
		if( $errstring = errors_string( 'Cannot update post, please correct these errors:', '' ) )
		{
			return new xmlrpcresp(0, $xmlrpcerruser+2, $errstring ); // user error 2
		}

		// We need to check the previous flags...
		$post_flags = $postdata['Flags'];
		if( in_array( 'pingsdone', $post_flags ) )
		{	// pings have been done before
			$pingsdone = true;
		}
		elseif( !$publish )
		{	// still not publishing
			$pingsdone = false;
		}
		else
		{	// We'll be pinging now
			$pingsdone = true;
		}

		// UPDATE POST IN DB:
		if( !bpost_update( $post_ID, $post_title, $content, '', $post_category, array($post_category), $status, 'en', '', 0, $pingsdone ) )
		{
			return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
  	        "For some strange yet very annoying reason, the entry couldn't be edited.");
		}
		
		if (isset($sleep_after_edit) && $sleep_after_edit > 0) 
		{
			sleep($sleep_after_edit);
		}

		if( $publish )
		{	// If post is publicly published:

			// ping ?	
			if( in_array( 'pingsdone', $post_flags ) )
			{	// pings have been done before
				logIO("O","pings have been done before...");
			}
			else
			{	// We'll ping now
				// We have less control here as in the backoffice, so we'll actually
				// only pingback once, at the same time we do the pings!
				logIO("O","Doing pingbacks...");
				if (!isset($blog_ID)) { $blog_ID = get_catblog($post_category); }
				$blogparams = get_blogparams_by_ID( $blog_ID );
				pingback( true, $content, $post_title, '', $post_ID, $blogparams, false);
				logIO("O","Pinging b2evolution.net...");
				pingb2evonet( $blogparams, $post_ID, $post_title, false );
				logIO("O","Pinging Weblogs...");
				pingWeblogs( $blogparams, false );
				logIO("O","Pinging Blo.gs...");
				pingBlogs($blogparams);
				logIO("O","Pinging Cafelog...");
				pingCafelog($cafelogID, $post_title, $post_ID);
			}

		}
		
		return new xmlrpcresp(new xmlrpcval("1", "boolean"));

	} 
	else 
	{
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}
}



### blogger.deletePost ###

$bloggerdeletepost_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));

$bloggerdeletepost_doc='Deletes a post, blogger-api like';

function bloggerdeletepost($m) {

	global $xmlrpcerruser; // import user errcode value
	global $blog_ID,$tableposts,$cache_userdata, $use_weblogsping,$post_autobr;
	global $post_default_title,$post_default_category, $sleep_after_edit;
	$err="";

	dbconnect();

	$post_ID=$m->getParam(1);
	$username=$m->getParam(2);
	$password=$m->getParam(3);
	$newcontent=$m->getParam(4);

	$post_ID = $post_ID->scalarval();
	$username = $username->scalarval();
	$password = $password->scalarval();
	$newcontent = $newcontent->scalarval();

	if (! ($postdata=get_postdata($post_ID)) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+2, "No such post.");// user error 2
	}

	$post_authordata=get_userdata($postdata["Author_ID"]);
	$post_author_ID=$postdata["Author_ID"];

	$userdata = get_userdatabylogin($username);
	$user_ID = $userdata["ID"];
	$user_level = $userdata["user_level"];


	if (($user_ID != $post_author_ID) && ($user_level <= $post_authordata["user_level"])) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
           "Sorry, you do not have the right to delete this post");
	}

	if (user_pass_ok($username,$password)) {

		if ($user_level < 1) {
			return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
           "Sorry, level 0 users can not delete posts");
		}

		// DELETE POST FROM DB:
		if( ! bpost_delete( $post_ID ) )
			return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
           "For some strange yet very annoying reason, the entry couldn't be deleted.");

		if (isset($sleep_after_edit) && $sleep_after_edit > 0) {
			sleep($sleep_after_edit);
		}

		return new xmlrpcresp(new xmlrpcval(1));

	} else {
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}
}



### blogger.getUsersBlogs ###

$bloggergetusersblogs_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));

$bloggergetusersblogs_doc='returns the user\'s blogs - this is a dummy function, just so that BlogBuddy and other blogs-retrieving apps work';

function bloggergetusersblogs($m) {
	// this function will have a real purpose with CafeLog's multiple blogs capability

	global $xmlrpcerruser;
	global $tableusers, $tableblogs, $baseurl;

	$user_login = $m->getParam(1);
	$user_login = $user_login->scalarval();

	dbconnect();

	$sql = "SELECT user_level FROM $tableusers WHERE user_login = '$user_login' AND user_level > 3";
	$result = mysql_query($sql) or die($sql."<br />".mysql_error());

	$is_admin = mysql_num_rows($result);

	$sql = "SELECT * FROM $tableblogs ORDER BY blog_name ASC";
	$result = mysql_query($sql) or die($sql);

	$i = 0;
	while($row = mysql_fetch_object($result))
	{
		$blogid = $row->blog_ID;
		$blogname = $row->blog_name;
		$siteurl = $row->blog_siteurl;
		$blogstub = $row->blog_stub;

		$struct[$i]  = new xmlrpcval(array("isAdmin" => new xmlrpcval($is_admin,"boolean"),
								"url" => new xmlrpcval($baseurl.$siteurl."/".$blogstub),
								"blogid" => new xmlrpcval($blogid),
								"blogName" => new xmlrpcval($blogname)
								),"struct");
		$i = $i + 1;
	}

	$data = array($struct[0]);
	for ($j=1; $j<$i; $j++) {
		array_push($data, $struct[$j]);
	}

	$resp = new xmlrpcval($data, "array");

	return new xmlrpcresp($resp);


}



### blogger.getUserInfo ###

$bloggergetuserinfo_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));

$bloggergetuserinfo_doc='gives the info about a user';

function bloggergetuserinfo($m) {
	global $xmlrpcerruser,$tableusers;

	dbconnect();

	$username=$m->getParam(1);
	$username = $username->scalarval();

	$password=$m->getParam(2);
	$password = $password->scalarval();

	$userdata = get_userdatabylogin($username);

	if (user_pass_ok($username,$password)) {
		$struct = new xmlrpcval(array("nickname" => new xmlrpcval($userdata["user_nickname"]),
									  "userid" => new xmlrpcval($userdata["ID"]),
									  "url" => new xmlrpcval($userdata["user_url"]),
									  "email" => new xmlrpcval($userdata["user_email"]),
									  "lastname" => new xmlrpcval($userdata["user_lastname"]),
									  "firstName" => new xmlrpcval($userdata["user_firstname"])
									  ),"struct");
		$resp = $struct;
		return new xmlrpcresp($resp);

	} else {
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}
}



### blogger.getPost ###

$bloggergetpost_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));

$bloggergetpost_doc='fetches a post, blogger-api like';

function bloggergetpost($m) {
	global $xmlrpcerruser,$tableposts;

	dbconnect();

	$post_ID=$m->getParam(1);
	$post_ID = $post_ID->scalarval();

	$username=$m->getParam(2);
	$username = $username->scalarval();

	$password=$m->getParam(3);
	$password = $password->scalarval();

	if (user_pass_ok($username,$password)) {
		$postdata = get_postdata($post_ID);

		if ($postdata["Date"] != "") {

			$post_date = mysql2date("U", $postdata["Date"]);
			$post_date = gmdate("Ymd", $post_date)."T".gmdate("H:i:s", $post_date);

			$content  = "<title>".stripslashes($postdata["Title"])."</title>";
			$content .= "<category>".$postdata["Category"]."</category>";
			$content .= stripslashes($postdata["Content"]);

			$struct = new xmlrpcval(array("userid" => new xmlrpcval($postdata["Author_ID"]),
										  "dateCreated" => new xmlrpcval($post_date,"dateTime.iso8601"),
										  "content" => new xmlrpcval($content),
										  "postid" => new xmlrpcval($postdata["ID"])
										  ),"struct");

			$resp = $struct;
			return new xmlrpcresp($resp);
		} else {
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 4
           "No such post #$post_ID");
		}
	} else {
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}
}



### blogger.getRecentPosts ###

$bloggergetrecentposts_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcInt));

$bloggergetrecentposts_doc='fetches X most recent posts, blogger-api like';

function bloggergetrecentposts($m) {
	global $xmlrpcerruser,$tableposts;

	error_reporting(0); // there is a bug in phpxmlrpc that makes it say there are errors while the output is actually valid, so let's disable errors for that function

	dbconnect();

	$blogid = 1;	// we don't need that yet

	$numposts=$m->getParam(4);
	$numposts = $numposts->scalarval();

	if ($numposts > 0) {
		$limit = " LIMIT $numposts";
	} else {
		$limit = "";
	}

	$username=$m->getParam(2);
	$username = $username->scalarval();

	$password=$m->getParam(3);
	$password = $password->scalarval();

	if (user_pass_ok($username,$password)) {

		$sql = "SELECT * FROM $tableposts WHERE post_category > 0 ORDER BY post_date DESC".$limit;
		$result = mysql_query($sql);
		if (!$result)
			return new xmlrpcresp(0, $xmlrpcerruser+2, // user error 2
           "For some strange yet very annoying reason, the entries couldn't be fetched.".mysql_error());

		$data = new xmlrpcval("","array");

		$i = 0;
		while($row = mysql_fetch_object($result)) {
			$postdata = array(
				"ID" => $row->ID,
				"Author_ID" => $row->post_author,
				"Date" => $row->post_date,
				"Content" => $row->post_content,
				"Title" => $row->post_title,
				"Category" => $row->post_category
			);

			$post_date = mysql2date("U", $postdata["Date"]);
			$post_date = gmdate("Ymd", $post_date)."T".gmdate("H:i:s", $post_date);

			$content  = "<title>".stripslashes($postdata["Title"])."</title>";
			$content .= "<category>".$postdata["Category"]."</category>";
			$content .= stripslashes($postdata["Content"]);

#			$content = convert_chars($content,"html");
#			$content = $postdata["Title"];

			$authordata = get_userdata($postdata["Author_ID"]);
			switch($authordata['user_idmode']) 
			{
				case "nickname":
					$authorname = $authordata["user_nickname"];

				case "login":
					$authorname = $authordata["user_login"];
					break;
				case "firstname":
					$authorname = $authordata["user_firstname"];
					break;
				case "lastname":
					$authorname = $authordata["user_lastname"];
					break;
				case "namefl":
					$authorname = $authordata["user_firstname"]." ".$authordata["user_lastname"];
					break;
				case "namelf":
					$authorname = $authordata["user_lastname"]." ".$authordata["user_firstname"];
					break;
				default:
					$authorname = $authordata["user_nickname"];
					break;
			}

			$struct[$i] = new xmlrpcval(array("authorName" => new xmlrpcval($authorname),
										"userid" => new xmlrpcval($postdata["Author_ID"]),
										"dateCreated" => new xmlrpcval($post_date,"dateTime.iso8601"),
										"content" => new xmlrpcval($content),
										"postid" => new xmlrpcval($postdata["ID"])
										),"struct");
			$i = $i + 1;
		}

		$data = array($struct[0]);
		for ($j=1; $j<$i; $j++) {
			array_push($data, $struct[$j]);
		}

		$resp = new xmlrpcval($data, "array");

		return new xmlrpcresp($resp);

	} else {
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}
}



### blogger.getTemplate ###

# note: on b2, it fetches your $blogfilename, or b2.php if you didn't specify the variable

$bloggergettemplate_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));

$bloggergettemplate_doc='returns the default template file\'s code';

function bloggergettemplate($m) 
{
	global $xmlrpcerruser,$tableusers;

	error_reporting(0); // there is a bug in phpxmlrpc that makes it say there are errors while the output is actually valid, so let's disable errors for that function

	dbconnect();

	$blog_ID = $m->getParam(1);
	$blog_ID = $blog_ID->scalarval();

	$templateType=$m->getParam(4);
	$templateType = $templateType->scalarval();

	$username=$m->getParam(2);
	$username = $username->scalarval();

	$password=$m->getParam(3);
	$password = $password->scalarval();

	$userdata = get_userdatabylogin($username);

	if ($userdata["user_level"] < 3) {
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
	   "Sorry, users whose level is less than 3, can not edit the template.");
	}

	if (user_pass_ok($username,$password)) 
	{
		global $xmlsrv_subdir;

		// Determine the edit folder:
		$edit_folder = get_path('skins').'/custom';

		if ($templateType == "main")
		{
			// $blogparams = get_blogparams_by_ID($blog_ID);
			$file = $edit_folder.'/_main.php';
		}
		elseif ($templateType == "archiveIndex")
		{
			$file = $edit_folder.'/_archives.php';
		}
		else return; // TODO: handle this cleanly
	
		$f = fopen($file,"r");
		$content = fread($f,filesize($file));
		fclose($file);
	
		$content = str_replace("\n","\r\n",$content);	// so it is actually editable with a windows/mac client, instead of being returned as a looooooooooong line of code
	
		return new xmlrpcresp(new xmlrpcval("$content"));
	
		} else {
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}
}



### blogger.setTemplate ###

# note: on b2, it saves that in your $blogfilename, or b2.php if you didn't specify the variable

$bloggersettemplate_sig=array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));

$bloggersettemplate_doc='saves the default template file\'s code';

function bloggersettemplate($m) {
	global $xmlrpcerruser,$tableusers,$blogfilename;

	error_reporting(0); // there is a bug in phpxmlrpc that makes it say there are errors while the output is actually valid, so let's disable errors for that function

	dbconnect();

	$blog_ID = $m->getParam(1);
	$blog_ID = $blog_ID->scalarval();

	$template=$m->getParam(4);
	$template = $template->scalarval();

	$templateType=$m->getParam(5);
	$templateType = $templateType->scalarval();

	$username=$m->getParam(2);
	$username = $username->scalarval();

	$password=$m->getParam(3);
	$password = $password->scalarval();

	$userdata = get_userdatabylogin($username);

	if ($userdata["user_level"] < 3) {
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
	   "Sorry, users whose level is less than 3, can not edit the template.");
	}

	if (user_pass_ok($username,$password)) 
	{
		global $xmlsrv_subdir;
		// Determine the edit folder:
		$edit_folder = get_path('skins').'/custom';

		if ($templateType == "main")
		{
			// $blogparams = get_blogparams_by_ID($blog_ID);
			$file = $edit_folder.'/_main.php';
		}
		elseif ($templateType == "archiveIndex")
		{
			$file = $edit_folder.'/_archives.php';
		}
		else return; // TODO: handle this cleanly

		$f = fopen($file,"w+");
		fwrite($f, $template);
		fclose($file);
	
		return new xmlrpcresp(new xmlrpcval("1", "boolean"));

	} else {
		return new xmlrpcresp(0, $xmlrpcerruser+3, // user error 3
           'Wrong username/password combination '.$username.' / '.starify($password));
	}
}

/**** /Blogger API ****/



/**** Pingback functions ****/

function strip_all_but_one_link($text, $mylink, $log)
{
	debug_fwrite($log, 'Searching '.$mylink.' in text block #####'.$text."####\n\n");

	$match_link = '#(<a.+?href.+?'.'>)(.+?)(</a>)#';
	preg_match_all($match_link, $text, $matches);
	$count = count($matches[0]);
	for ($i=0; $i<$count; $i++) 
	{
		$thislink = $matches[0][$i];
		debug_fwrite($log, 'Analyzing link : '.$thislink."\n");

		if(strstr($thislink, $mylink)) 
		{
			debug_fwrite($log, "MATCH!\n");
		}
		else
		{	// this link doesn't contain what we're looking for
			$text = str_replace($matches[0][$i], $matches[2][$i], $text);
		}
	}
	return $text;
}

$pingback_ping_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString));

$pingback_ping_doc = 'gets a pingback and registers it as a comment prefixed by &lt;pingback /&gt;';


/*
 * pingback_ping(-)
 *
 * This is the pingback receiver!
 *
 * original code by Mort (http://mort.mine.nu:8080)
 * fplanque: terrible bug found in there: if link doesn't appear in 1st 4096 bytes of referer it would not be found!
 */
function pingback_ping($m) {
	//
	global $tableposts, $tablecomments, $comments_notify, $notify_from;
	global $baseurl, $b2_version, $use_pingback;
	global $default_locale;

	if (!$use_pingback) 
	{
		return new xmlrpcresp(new xmlrpcval('Sorry, this weblog does not allow you to pingback its posts.'));
	}

	// return new xmlrpcresp(new xmlrpcval('hello.'));

	dbconnect();

	$log = debug_fopen('./xmlrpc.log', 'w');

	$title='';

	$pagelinkedfrom = $m->getParam(0);
	$pagelinkedfrom = $pagelinkedfrom->scalarval();

	$pagelinkedto = $m->getParam(1);
	$pagelinkedto = $pagelinkedto->scalarval();

	$pagelinkedfrom = str_replace('&amp;', '&', $pagelinkedfrom);
	$pagelinkedto = preg_replace('#&([^amp\;])#is', '&amp;$1', $pagelinkedto);

	debug_fwrite($log, 'BEGIN '.time().' - '.date('Y-m-d H:i:s')."\n\n");
	debug_fwrite($log, 'Page linked from: '.$pagelinkedfrom."\n");
	debug_fwrite($log, 'Page linked to: '.$pagelinkedto."\n");

	$messages = array(
		htmlentities("Pingback from ".$pagelinkedfrom." to ".$pagelinkedto." registered. Keep the web talking! :-)"),
		htmlentities("We can't find the URL to the post you are trying to link to in your entry. Please check how you wrote the post's permalink in your entry."),
		htmlentities("We can't find the post you are trying to link to. Please check the post's permalink.")
	);

	$message = $messages[0];

	// Check if the page linked to is in our site
	// fplanque: TODO: coz we don't have a single siteurl any longer
	$pos1 = strpos($pagelinkedto, str_replace('http://', '', str_replace('www.', '', $baseurl)));
	if($pos1) {

		// let's find which post is linked to
		$urltest = parse_url($pagelinkedto);
		if (preg_match('#/p[0-9]{1,}#', $urltest['path'], $match)) {
			// the path defines the post_ID (yyyy/mm/dd/pXXXX)
			$blah = explode('p', $match[0]);
			$post_ID = $blah[1];
			$way = 'from the path';
		} elseif (preg_match('#p/[0-9]{1,}#', $urltest['path'], $match)) {
			// the path defines the post_ID (archives/p/XXXX)
			$blah = explode('/', $match[0]);
			$post_ID = $blah[1];
			$way = 'from the path';
		} elseif (preg_match('#p=[0-9]{1,}#', $urltest['query'], $match)) {
			// the querystring defines the post_ID (?p=XXXX)
			$blah = explode('=', $match[0]);
			$post_ID = $blah[1];
			$way = 'from the querystring';
		}
		elseif (isset($urltest['fragment'])) 
		{
			// an #anchor is there, it's either...
			if (intval($urltest['fragment'])) {
				// ...an integer #XXXX (simpliest case)
				$post_ID = $urltest['fragment'];
				$way = 'from the fragment (numeric)';
			} elseif (is_string($urltest['fragment'])) {
				// ...or a string #title, a little more complicated
				$title = preg_replace('/[^a-zA-Z0-9]/', '.', $urltest['fragment']);
				$sql = "SELECT ID FROM $tableposts WHERE post_title RLIKE '$title'";
				$result = mysql_query($sql) or die("Query: $sql\n\nError: ".mysql_error());
				$blah = mysql_fetch_array($result);
				$post_ID = $blah['ID'];
				$way = 'from the fragment (title)';
			}
		} else {
			$post_ID = -1;
		}

		debug_fwrite($log, "Found post ID $way: $post_ID\n");

		$sql = 'SELECT post_author FROM '.$tableposts.' WHERE ID = '.$post_ID;
		$result = mysql_query($sql);

		if (mysql_num_rows($result)) 
		{
			debug_fwrite($log, 'Post exists'."\n");

			// Let's check that the remote site didn't already pingback this entry
			$sql = "SELECT * FROM $tablecomments WHERE comment_post_ID = $post_ID AND comment_author_url = '.$pagelinkedfrom.' AND comment_type = 'pingback'";
			$result = mysql_query($sql);

			if (mysql_num_rows($result) || (1==1))
			{

				// very stupid, but gives time to the 'from' server to publish !
				sleep(1);

				// Let's check the remote site
				$fp = @fopen($pagelinkedfrom, 'r');

				$puntero = 4096;
				$linea = "";
				while($fbuffer = fread($fp, $puntero))
				{ // fplanque: dis is da place where da bug was >:[
					$linea .= $fbuffer;		// dis is da fix!
				}
				fclose($fp);
				$linea = strip_tags($linea, '<a><title>');

				preg_match('|<title>([^<]*?)</title>|is', $linea, $matchtitle);

				$linea = convert_chars( $linea, 'html' );		// warning: this also removes title!

				$pagelinkedto = convert_chars( $pagelinkedto, 'html' );
				$linea = strip_all_but_one_link($linea, $pagelinkedto, $log);
				// fplanque: removed $linea = preg_replace('#&([^amp\;])#is', '&amp;$1', $linea);
				
				debug_fwrite($log, 'SECOND SEARCH '.convert_chars($pagelinkedto).' in text block #####'.$linea."####\n\n");
				$pos2 = strpos($linea, convert_chars($pagelinkedto));
				$pos3 = strpos($linea, str_replace('http://www.', 'http://', convert_chars($pagelinkedto)));
				if (is_integer($pos2) || is_integer($pos3))
				{
					debug_fwrite($log, 'The page really links to us :)'."\n");
					$pos4 = (is_integer($pos2)) ? $pos2 : $pos3;
					$start = $pos4-100;
					$context = substr($linea, $start, 250);
					$context = str_replace("\n", ' ', $context);
					$context = str_replace('&amp;', '&', $context);

					global $admin_url, $comments_allowed_uri_scheme;
				
					$pagelinkedfrom = preg_replace('#&([^amp\;])#is', '&amp;$1', $pagelinkedfrom);
					$title = (!strlen($matchtitle[1])) ? $pagelinkedfrom : $matchtitle[1];
					$original_context = $context;
					$context = '[...] '.trim($context).' [...]';

					// CHECK and FORMAT content	
					if( $error = validate_url( $pagelinkedfrom, $comments_allowed_uri_scheme ) )
					{
						errors_add( T_('Supplied URL is invalid: ').$error );	
					}
					$context = format_to_post($context,1,1);
				
					if( ! ($message = errors_string( 'Cannot insert pingback, please correct these errors:', '' )) )
					{	// No validation error:
						$original_pagelinkedfrom = $pagelinkedfrom;
						$pagelinkedfrom = addslashes($pagelinkedfrom);
						$original_title = $title;
						$title = addslashes(strip_tags(trim($title)));
						$sql = "INSERT INTO $tablecomments(comment_post_ID, comment_type, comment_author, comment_author_url, comment_date, comment_content) VALUES ($post_ID, 'pingback', '$title', '$pagelinkedfrom', NOW(), '".addslashes($context)."')";
						$consulta = mysql_query($sql);
	
						if ($comments_notify)
						{
							$postdata = get_postdata($post_ID);
							$blog = $postdata['Blog'];
							$authordata = get_userdata($postdata['Author_ID']);
							$recipient = $authordata['user_email'];
							$subject = sprintf( T_('New pingback on your post #%d "%s"', $default_locale), $post_ID, $postdata['Title'] );
							// fplanque added:
							$comment_blogparams = get_blogparams_by_ID( $blog );
	
							$notify_message  = sprintf( T_('New pingback on your post #%d "%s"', $default_locale), $post_ID, $postdata['Title'] )."\n";
							$notify_message .= get_bloginfo('blogurl', $comment_blogparams)."?p=".$post_ID."&pb=1\n\n";
							$notify_message .= T_('Website', $default_locale). ": $original_title\n";
							$notify_message .= T_('Url', $default_locale). ": $original_pagelinkedfrom\n";
							$notify_message .= T_('Excerpt', $default_locale). ": \n[...] $original_context [...]\n\n";
							$notify_message .= T_('Edit/Delete', $default_locale).': '.$admin_url.'/b2browse.php?blog='.$blog.'&p='.$post_ID."&c=1\n\n";
	
							@mail($recipient, $subject, $notify_message, "From: $notify_from\nX-Mailer: b2evolution $b2_version - PHP/".phpversion() );
	
						}
					}
				} 
				else 
				{	// URL pattern not found - page doesn't link to us:
					debug_fwrite($log, 'The page doesn\'t link to us!'."\n");
					$message = "Page linked to: $pagelinkedto\nPage linked from: $pagelinkedfrom\nTitle: $title\n\n".$messages[1];

				}
			} else {
				// We already have a Pingback from this URL
				$message = "Sorry, you already did a pingback to $pagelinkedto from $pagelinkedfrom.";
			}
		} else {
			// Post_ID not found
			$message = $messages[2];
			debug_fwrite($log, 'Post doesn\'t exist'."\n");
		}
	}  // / in siteurl
	return new xmlrpcresp(new xmlrpcval($message));
}

/**** /Pingback functions ****/



/**** Legacy functions ****/

// a PHP version
// of the state-number server
// send me an integer and i'll sell you a state

$stateNames=array(
"Alabama", "Alaska", "Arizona", "Arkansas", "California",
"Colorado", "Columbia", "Connecticut", "Delaware", "Florida",
"Georgia", "Hawaii", "Idaho", "Illinois", "Indiana", "Iowa", "Kansas",
"Kentucky", "Louisiana", "Maine", "Maryland", "Massachusetts", "Michigan",
"Minnesota", "Mississippi", "Missouri", "Montana", "Nebraska", "Nevada",
"New Hampshire", "New Jersey", "New Mexico", "New York", "North Carolina",
"North Dakota", "Ohio", "Oklahoma", "Oregon", "Pennsylvania", "Rhode Island",
"South Carolina", "South Dakota", "Tennessee", "Texas", "Utah", "Vermont",
"Virginia", "Washington", "West Virginia", "Wisconsin", "Wyoming");

$findstate_sig=array(array($xmlrpcString, $xmlrpcInt));

$findstate_doc='When passed an integer between 1 and 51 returns the
name of a US state, where the integer is the index of that state name
in an alphabetic order.';

function findstate($m) {
  global $xmlrpcerruser, $stateNames;
  $err="";
  // get the first param
  $sno=$m->getParam(0);
  // if it's there and the correct type

  if (isset($sno) && ($sno->scalartyp()=="int")) {
	// extract the value of the state number
	$snv=$sno->scalarval();
	// look it up in our array (zero-based)
	if (isset($stateNames[$snv-1])) {
	  $sname=$stateNames[$snv-1];
	} else {
	  // not, there so complain
	  $err="I don't have a state for the index '" . $snv . "'";
	}
  } else {
	// parameter mismatch, complain
	$err="One integer parameter required";
  }

  // if we generated an error, create an error return response
  if ($err) {
		return new xmlrpcresp(0, $xmlrpcerruser, $err);
  } else {
		// otherwise, we create the right response
		// with the state name
		return new xmlrpcresp(new xmlrpcval($sname));
  }
}

$addtwo_sig=array(array($xmlrpcInt, $xmlrpcInt, $xmlrpcInt));

$addtwo_doc='Add two integers together and return the result';

function addtwo($m) {
  $s=$m->getParam(0);
	$t=$m->getParam(1);
  return new xmlrpcresp(new xmlrpcval($s->scalarval()+$t->scalarval(),
																			"int"));
}

$addtwodouble_sig=array(array($xmlrpcDouble, $xmlrpcDouble, $xmlrpcDouble));

$addtwodouble_doc='Add two doubles together and return the result';

function addtwodouble($m) {
  $s=$m->getParam(0);
	$t=$m->getParam(1);
  return new xmlrpcresp(new xmlrpcval($s->scalarval()+$t->scalarval(),
																			"double"));
}

$stringecho_sig=array(array($xmlrpcString, $xmlrpcString));

$stringecho_doc='Accepts a string parameter, returns the string.';

function stringecho($m) {
  // just sends back a string
  $s=$m->getParam(0);
  return new xmlrpcresp(new xmlrpcval($s->scalarval()));
}

$echoback_sig=array(array($xmlrpcString, $xmlrpcString));

$echoback_doc='Accepts a string parameter, returns the entire incoming payload';

function echoback($m) {
  // just sends back a string with what i got
  // send to me, just escaped, that's all
  //
  // $m is an incoming message
  $s="I got the following message:\n" . $m->serialize();
  return new xmlrpcresp(new xmlrpcval($s));
}

$echosixtyfour_sig=array(array($xmlrpcString, $xmlrpcBase64));

$echosixtyfour_doc='Accepts a base64 parameter and returns it decoded as a string';

function echosixtyfour($m) {
	// accepts an encoded value, but sends it back
	// as a normal string. this is to test base64 encoding
	// is working as expected
	$incoming=$m->getParam(0);
	return new xmlrpcresp(new xmlrpcval($incoming->scalarval(), "string"));
}

$bitflipper_sig=array(array($xmlrpcArray, $xmlrpcArray));

$bitflipper_doc='Accepts an array of booleans, and returns them inverted';

function bitflipper($m) {
	global $xmlrpcArray;

	$v=$m->getParam(0);
	$sz=$v->arraysize();
	$rv=new xmlrpcval(array(), $xmlrpcArray);

	for($j=0; $j<$sz; $j++) {
		$b=$v->arraymem($j);
		if ($b->scalarval()) {

			$rv->addScalar(false, "boolean");
		} else {

			$rv->addScalar(true, "boolean");
		}
	}

	return new xmlrpcresp($rv);
}

// Sorting demo
//
// send me an array of structs thus:
//
// Dave 35
// Edd  45
// Fred 23
// Barney 37
//
// and I'll return it to you in sorted order

function agesorter_compare($a, $b) {
  global $agesorter_arr;


  // don't even ask me _why_ these come padded with
  // hyphens, I couldn't tell you :p
  $a=ereg_replace("-", "", $a);
  $b=ereg_replace("-", "", $b);

  if ($agesorter_arr[$a]==$agesorter[$b]) return 0;
  return ($agesorter_arr[$a] > $agesorter_arr[$b]) ? -1 : 1;
}

$agesorter_sig=array(array($xmlrpcArray, $xmlrpcArray));

$agesorter_doc='Send this method an array of [string, int] structs, eg:
<PRE>
 Dave   35
 Edd    45
 Fred   23
 Barney 37
</PRE>
And the array will be returned with the entries sorted by their numbers.
';

function agesorter($m) {
  global $agesorter_arr, $xmlrpcerruser, $s;

	xmlrpc_debugmsg("Entering 'agesorter'");
  // get the parameter
  $sno=$m->getParam(0);
  // error string for [if|when] things go wrong
  $err="";
  // create the output value
  $v=new xmlrpcval();
  $agar=array();

  if (isset($sno) && $sno->kindOf()=="array") {
	$max=$sno->arraysize();
	// TODO: create debug method to print can work once more
	// print "<!-- found $max array elements -->\n";
	for($i = 0; $i < $max; $i = $i + 1) {
	  $rec=$sno->arraymem($i);
	  if ($rec->kindOf()!="struct") {
		$err="Found non-struct in array at element $i";
		break;
	  }
	  // extract name and age from struct
	  $n=$rec->structmem("name");
	  $a=$rec->structmem("age");
	  // $n and $a are xmlrpcvals,
	  // so get the scalarval from them
	  $agar[$n->scalarval()]=$a->scalarval();
	}

	$agesorter_arr=$agar;
	// hack, must make global as uksort() won't
	// allow us to pass any other auxilliary information
	uksort($agesorter_arr, agesorter_compare);
	$outAr=array();
	while (list( $key, $val ) = each( $agesorter_arr ) ) {
	  // recreate each struct element
	  $outAr[]=new xmlrpcval(array("name" =>
								   new xmlrpcval($key),
								   "age" =>
								   new xmlrpcval($val, "int")), "struct");
	}
	// add this array to the output value
	$v->addArray($outAr);
  } else {
	  $err="Must be one parameter, an array of structs";
  }

  if ($err) {
		return new xmlrpcresp(0, $xmlrpcerruser, $err);
  } else {
		return new xmlrpcresp($v);
  }
}


// signature and instructions, place these in the dispatch
// map

$mail_send_sig=array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString,
													 $xmlrpcString, $xmlrpcString, $xmlrpcString,
													 $xmlrpcString, $xmlrpcString));

$mail_send_doc='mail.send(recipient, subject, text, sender, cc, bcc, mimetype)
<br />recipient, cc, and bcc are strings, comma-separated lists of email addresses, as described above.
<br />subject is a string, the subject of the message.
<br />sender is a string, it\'s the email address of the person sending the message. This string can not be
a comma-separated list, it must contain a single email address only.
text is a string, it contains the body of the message.
<br />mimetype, a string, is a standard MIME type, for example, text/plain.
';

// WARNING; this functionality depends on the sendmail -t option
// it may not work with Windows machines properly; particularly
// the Bcc option.  Sneak on your friends at your own risk!
function mail_send($m) {
  global $xmlrpcerruser, $xmlrpcBoolean;
	$err="";

  $mTo=$m->getParam(0);
	$mSub=$m->getParam(1);
	$mBody=$m->getParam(2);
	$mFrom=$m->getParam(3);
	$mCc=$m->getParam(4);
	$mBcc=$m->getParam(5);
	$mMime=$m->getParam(6);

	if ($mTo->scalarval()=="")
		$err="Error, no 'To' field specified";

	if ($mFrom->scalarval()=="")
		$err="Error, no 'From' field specified";

	$msghdr="From: " . $mFrom->scalarval() . "\n";
	$msghdr.="To: ". $mTo->scalarval() . "\n";

	if ($mCc->scalarval()!="")
		$msghdr.="Cc: " . $mCc->scalarval(). "\n";
	if ($mBcc->scalarval()!="")
		$msghdr.="Bcc: " . $mBcc->scalarval(). "\n";
	if ($mMime->scalarval()!="")
		$msghdr.="Content-type: " . $mMime->scalarval() . "\n";

	$msghdr.="X-Mailer: XML-RPC for PHP mailer 1.0";

	if ($err=="") {
		/*
		if (!mail("",
							$mSub->scalarval(),
							$mBody->scalarval(),
							$msghdr)) {
			$err="Error, could not send the mail.";
		}
		*/
		$err = 'Just in case someone wants to use this for spam, this method is disabled';
	}

  if ($err) {
		return new xmlrpcresp(0, $xmlrpcerruser, $err);
  } else {
		return new xmlrpcresp(new xmlrpcval("true", $xmlrpcBoolean));
  }
}

$v1_arrayOfStructs_sig=array(array($xmlrpcInt, $xmlrpcArray));

$v1_arrayOfStructs_doc='This handler takes a single parameter, an array of structs, each of which contains at least three elements named moe, larry and curly, all <i4>s. Your handler must add all the struct elements named curly and return the result.';

function v1_arrayOfStructs($m) {
  $sno=$m->getParam(0);
	$numcurly=0;
	for($i = 0; $i < $sno->arraysize(); $i = $i + 1) {
		$str=$sno->arraymem($i);
		$str->structreset();
		while(list($key,$val)=$str->structeach())
			if ($key=="curly")
				$numcurly+=$val->scalarval();
	}
	return new xmlrpcresp(new xmlrpcval($numcurly, "int"));
}

$v1_easyStruct_sig=array(array($xmlrpcInt, $xmlrpcStruct));

$v1_easyStruct_doc='This handler takes a single parameter, a struct, containing at least three elements named moe, larry and curly, all &lt;i4&gt;s. Your handler must add the three numbers and return the result.';

function v1_easyStruct($m) {
  $sno=$m->getParam(0);
	$moe=$sno->structmem("moe");
	$larry=$sno->structmem("larry");
	$curly=$sno->structmem("curly");
	$num=$moe->scalarval()+
		$larry->scalarval()+
		$curly->scalarval();
	return new xmlrpcresp(new xmlrpcval($num, "int"));
}

$v1_echoStruct_sig=array(array($xmlrpcStruct, $xmlrpcStruct));

$v1_echoStruct_doc='This handler takes a single parameter, a struct. Your handler must return the struct.';

function v1_echoStruct($m) {
  $sno=$m->getParam(0);
	return new xmlrpcresp($sno);
}

$v1_manyTypes_sig=array(array($xmlrpcArray, $xmlrpcInt, $xmlrpcBoolean,
															$xmlrpcString, $xmlrpcDouble, $xmlrpcDateTime,
															$xmlrpcBase64));

$v1_manyTypes_doc='This handler takes six parameters, and returns an array containing all the parameters.';

function v1_manyTypes($m) {
	return new xmlrpcresp(new xmlrpcval(array(
																						$m->getParam(0),
																						$m->getParam(1),
																						$m->getParam(2),
																						$m->getParam(3),
																						$m->getParam(4),
																						$m->getParam(5)),
																			"array"));
}

$v1_moderateSizeArrayCheck_sig=array(array($xmlrpcString, $xmlrpcArray));

$v1_moderateSizeArrayCheck_doc='This handler takes a single parameter, which is an array containing between 100 and 200 elements. Each of the items is a string, your handler must return a string containing the concatenated text of the first and last elements.';

function v1_moderateSizeArrayCheck($m) {
	$ar=$m->getParam(0);
	$sz=$ar->arraysize();
	$first=$ar->arraymem(0);
	$last=$ar->arraymem($sz-1);
	return new xmlrpcresp(new xmlrpcval($first->scalarval() .
																			$last->scalarval(), "string"));
}

$v1_simpleStructReturn_sig=array(array($xmlrpcStruct, $xmlrpcInt));

$v1_simpleStructReturn_doc='This handler takes one parameter, and returns a struct containing three elements, times10, times100 and times1000, the result of multiplying the number by 10, 100 and 1000.';

function v1_simpleStructReturn($m) {
  $sno=$m->getParam(0);
	$v=$sno->scalarval();
	return new xmlrpcresp(new xmlrpcval(array(
																						"times10" =>
																						new xmlrpcval($v*10, "int"),
																						"times100" =>
																						new xmlrpcval($v*100, "int"),
																						"times1000" =>
																						new xmlrpcval($v*1000, "int")),
																			"struct"));
}

$v1_nestedStruct_sig=array(array($xmlrpcInt, $xmlrpcStruct));

$v1_nestedStruct_doc='This handler takes a single parameter, a struct, that models a daily calendar. At the top level, there is one struct for each year. Each year is broken down into months, and months into days. Most of the days are empty in the struct you receive, but the entry for April 1, 2000 contains a least three elements named moe, larry and curly, all &lt;i4&gt;s. Your handler must add the three numbers and return the result.';

function v1_nestedStruct($m) {
  $sno=$m->getParam(0);

	$twoK=$sno->structmem("2000");
	$april=$twoK->structmem("04");
	$fools=$april->structmem("01");
	$curly=$fools->structmem("curly");
	$larry=$fools->structmem("larry");
	$moe=$fools->structmem("moe");
	return new xmlrpcresp(new xmlrpcval($curly->scalarval()+
																			$larry->scalarval()+
																			$moe->scalarval(), "int"));

}

$v1_countTheEntities_sig=array(array($xmlrpcStruct, $xmlrpcString));

$v1_countTheEntities_doc='This handler takes a single parameter, a string, that contains any number of predefined entities, namely &lt;, &gt;, &amp; \' and ".<br />Your handler must return a struct that contains five fields, all numbers:  ctLeftAngleBrackets, ctRightAngleBrackets, ctAmpersands, ctApostrophes, ctQuotes.';

function v1_countTheEntities($m) {
  $sno=$m->getParam(0);
	$str=$sno->scalarval();
	$gt=0; $lt=0; $ap=0; $qu=0; $amp=0;
	for($i = 0; $i < strlen($str); $i = $i + 1) {
		$c=substr($str, $i, 1);
		switch($c) {
		case ">":
			$gt++;
			break;
		case "<":
			$lt++;
			break;
		case "\"":
			$qu++;
			break;
		case "'":
			$ap++;
			break;
		case "&":
			$amp++;
			break;
		default:
			break;
		}
	}
	return new xmlrpcresp(new xmlrpcval(array("ctLeftAngleBrackets" =>
														 new xmlrpcval($lt, "int"),
														 "ctRightAngleBrackets" =>
														 new xmlrpcval($gt, "int"),
														 "ctAmpersands" =>
														 new xmlrpcval($amp, "int"),
														 "ctApostrophes" =>
														 new xmlrpcval($ap, "int"),
														 "ctQuotes" =>
														 new xmlrpcval($qu, "int")),
											 "struct"));
}

// trivial interop tests
// http://www.xmlrpc.com/stories/storyReader$1636

$i_echoString_sig=array(array($xmlrpcString, $xmlrpcString));
$i_echoString_doc="Echoes string.";

$i_echoStringArray_sig=array(array($xmlrpcArray, $xmlrpcArray));
$i_echoStringArray_doc="Echoes string array.";

$i_echoInteger_sig=array(array($xmlrpcInt, $xmlrpcInt));
$i_echoInteger_doc="Echoes integer.";

$i_echoIntegerArray_sig=array(array($xmlrpcArray, $xmlrpcArray));
$i_echoIntegerArray_doc="Echoes integer array.";

$i_echoFloat_sig=array(array($xmlrpcDouble, $xmlrpcDouble));
$i_echoFloat_doc="Echoes float.";

$i_echoFloatArray_sig=array(array($xmlrpcArray, $xmlrpcArray));
$i_echoFloatArray_doc="Echoes float array.";

$i_echoStruct_sig=array(array($xmlrpcStruct, $xmlrpcStruct));
$i_echoStruct_doc="Echoes struct.";

$i_echoStructArray_sig=array(array($xmlrpcArray, $xmlrpcArray));
$i_echoStructArray_doc="Echoes struct array.";

$i_echoValue_doc="Echoes any value back.";

$i_echoBase64_sig=array(array($xmlrpcBase64, $xmlrpcBase64));
$i_echoBase64_doc="Echoes base64.";

$i_echoDate_sig=array(array($xmlrpcDateTime, $xmlrpcDateTime));
$i_echoDate_doc="Echoes dateTime.";

function i_echoParam($m) {
	$s=$m->getParam(0);
	return new xmlrpcresp($s);
}

function i_echoString($m) { return i_echoParam($m); }
function i_echoInteger($m) { return i_echoParam($m); }
function i_echoFloat($m) { return i_echoParam($m); }
function i_echoStruct($m) { return i_echoParam($m); }
function i_echoStringArray($m) { return i_echoParam($m); }
function i_echoIntegerArray($m) { return i_echoParam($m); }
function i_echoFloatArray($m) { return i_echoParam($m); }
function i_echoStructArray($m) { return i_echoParam($m); }
function i_echoValue($m) { return i_echoParam($m); }
function i_echoBase64($m) { return i_echoParam($m); }
function i_echoDate($m) { return i_echoParam($m); }

$i_whichToolkit_doc="Returns a struct containing the following strings:  toolkitDocsUrl, toolkitName, toolkitVersion, toolkitOperatingSystem.";

function i_whichToolkit($m) {
	global $xmlrpcName, $xmlrpcVersion,$SERVER_SOFTWARE,
		$xmlrpcStruct;
	$ret=array(
						 "toolkitDocsUrl" => "http://xmlrpc.usefulinc.com/php.html",
						 "toolkitName" => $xmlrpcName,
						 "toolkitVersion" => $xmlrpcVersion,
						 "toolkitOperatingSystem" => $SERVER_SOFTWARE);
	return new xmlrpcresp ( xmlrpc_encode($ret));
}



/**** SERVER FUNCTIONS ARRAY ****/

$s=new xmlrpc_server( 
				array( "blogger.newPost" =>
							 array("function" => "bloggernewpost",
										 "signature" => $bloggernewpost_sig,
										 "docstring" => $bloggernewpost_doc),


							 "blogger.editPost" =>
							 array("function" => "bloggereditpost",
										 "signature" => $bloggereditpost_sig,
										 "docstring" => $bloggereditpost_doc),


							 "blogger.deletePost" =>
							 array("function" => "bloggerdeletepost",
										 "signature" => $bloggerdeletepost_sig,
										 "docstring" => $bloggerdeletepost_doc),


							 "blogger.getUsersBlogs" =>
							 array("function" => "bloggergetusersblogs",
										 "signature" => $bloggergetusersblogs_sig,
										 "docstring" => $bloggergetusersblogs_doc),

							 "blogger.getUserInfo" =>
							 array("function" => "bloggergetuserinfo",
										 "signature" => $bloggergetuserinfo_sig,
										 "docstring" => $bloggergetuserinfo_doc),

 							 "blogger.getPost" =>
							 array("function" => "bloggergetpost",
										 "signature" => $bloggergetpost_sig,
										 "docstring" => $bloggergetpost_doc),

							 "blogger.getRecentPosts" =>
							 array("function" => "bloggergetrecentposts",
										 "signature" => $bloggergetrecentposts_sig,
										 "docstring" => $bloggergetrecentposts_doc),

							 "blogger.getTemplate" =>
							 array("function" => "bloggergettemplate",
										 "signature" => $bloggergettemplate_sig,
										 "docstring" => $bloggergettemplate_doc),

							 "blogger.setTemplate" =>
							 array("function" => "bloggersettemplate",
										 "signature" => $bloggersettemplate_sig,
										 "docstring" => $bloggersettemplate_doc),

							 "b2.newPost" =>
							 array("function" => "b2newpost",
										 "signature" => $b2newpost_sig,
										 "docstring" => $b2newpost_doc),
							 "b2.getCategories" =>
							 array("function" => "b2getcategories",
										 "signature" => $b2getcategories_sig,
										 "docstring" => $b2getcategories_doc),

//							 "b2.ping" =>
//							 array("function" => "b2ping",
//										 "signature" => $b2ping_sig,
//										 "docstring" => $b2ping_doc),

							 "pingback.ping" =>
							 array("function" => "pingback_ping",
										 "signature" => $pingback_ping_sig,
										 "docstring" => $pingback_ping_doc),

							 "b2.getPostURL" =>
							 array("function" => "pingback_getPostURL",
										 "signature" => $b2_getPostURL_sig,
										 "docstring" => $b2_getPostURL_doc),


							 "examples.getStateName" =>
							 array("function" => "findstate",
										 "signature" => $findstate_sig,
										 "docstring" => $findstate_doc),

							 "examples.sortByAge" =>
							 array("function" => "agesorter",
										 "signature" => $agesorter_sig,
										 "docstring" => $agesorter_doc),

							 "examples.addtwo" =>
							 array("function" => "addtwo",
										 "signature" => $addtwo_sig,
										 "docstring" => $addtwo_doc),

							 "examples.addtwodouble" =>
							 array("function" => "addtwodouble",
										 "signature" => $addtwodouble_sig,
										 "docstring" => $addtwodouble_doc),

							 "examples.stringecho" =>
							 array("function" => "stringecho",
										 "signature" => $stringecho_sig,
										 "docstring" => $stringecho_doc),

							 "examples.echo" =>
							 array("function" => "echoback",
										 "signature" => $echoback_sig,
										 "docstring" => $echoback_doc),

							 "examples.decode64" =>
							 array("function" => "echosixtyfour",
										 "signature" => $echosixtyfour_sig,
										 "docstring" => $echosixtyfour_doc),

							 "examples.invertBooleans" =>
							 array("function" => "bitflipper",
										 "signature" => $bitflipper_sig,
										 "docstring" => $bitflipper_doc),

							 "mail.send" =>
							 array("function" => "mail_send",
										 "signature" => $mail_send_sig,
										 "docstring" => $mail_send_doc),

							 "validator1.arrayOfStructsTest" =>
							 array("function" => "v1_arrayOfStructs",
										 "signature" => $v1_arrayOfStructs_sig,
										 "docstring" => $v1_arrayOfStructs_doc),

							 "validator1.easyStructTest" =>
							 array("function" => "v1_easyStruct",
										 "signature" => $v1_easyStruct_sig,
										 "docstring" => $v1_easyStruct_doc),

							  "validator1.echoStructTest" =>
							 array("function" => "v1_echoStruct",
										 "signature" => $v1_echoStruct_sig,
										 "docstring" => $v1_echoStruct_doc),

							  "validator1.manyTypesTest" =>
							 array("function" => "v1_manyTypes",
										 "signature" => $v1_manyTypes_sig,
										 "docstring" => $v1_manyTypes_doc),

							  "validator1.moderateSizeArrayCheck" =>
							 array("function" => "v1_moderateSizeArrayCheck",
										 "signature" => $v1_moderateSizeArrayCheck_sig,
										 "docstring" => $v1_moderateSizeArrayCheck_doc),
							  "validator1.simpleStructReturnTest" =>
							 array("function" => "v1_simpleStructReturn",
										 "signature" => $v1_simpleStructReturn_sig,
										 "docstring" => $v1_simpleStructReturn_doc),

							 "validator1.nestedStructTest" =>
							 array("function" => "v1_nestedStruct",
										 "signature" => $v1_nestedStruct_sig,
										 "docstring" => $v1_nestedStruct_doc),

							 "validator1.countTheEntities" =>
							 array("function" => "v1_countTheEntities",
										 "signature" => $v1_countTheEntities_sig,
										 "docstring" => $v1_countTheEntities_doc),

							 "interopEchoTests.echoString" =>
							 array("function" => "i_echoString",
										 "signature" => $i_echoString_sig,
										 "docstring" => $i_echoString_doc),

							 "interopEchoTests.echoStringArray" =>
							 array("function" => "i_echoStringArray",
										 "signature" => $i_echoStringArray_sig,
										 "docstring" => $i_echoStringArray_doc),

							 "interopEchoTests.echoInteger" =>
							 array("function" => "i_echoInteger",
										 "signature" => $i_echoInteger_sig,
										 "docstring" => $i_echoInteger_doc),

							 "interopEchoTests.echoIntegerArray" =>
							 array("function" => "i_echoIntegerArray",
										 "signature" => $i_echoIntegerArray_sig,
										 "docstring" => $i_echoIntegerArray_doc),

							 "interopEchoTests.echoFloat" =>
							 array("function" => "i_echoFloat",
										 "signature" => $i_echoFloat_sig,
										 "docstring" => $i_echoFloat_doc),

							 "interopEchoTests.echoFloatArray" =>
							 array("function" => "i_echoFloatArray",
										 "signature" => $i_echoFloatArray_sig,
										 "docstring" => $i_echoFloatArray_doc),

							 "interopEchoTests.echoStruct" =>
							 array("function" => "i_echoStruct",
										 "signature" => $i_echoStruct_sig,
										 "docstring" => $i_echoStruct_doc),

							 "interopEchoTests.echoStructArray" =>
							 array("function" => "i_echoStructArray",
										 "signature" => $i_echoStructArray_sig,
										 "docstring" => $i_echoStructArray_doc),

							  "interopEchoTests.echoValue" =>
							 array("function" => "i_echoValue",
										 // no sig as takes anytype
										 "docstring" => $i_echoValue_doc),

							  "interopEchoTests.echoBase64" =>
							 array("function" => "i_echoBase64",
										 "signature" => $i_echoBase64_sig,
										 "docstring" => $i_echoBase64_doc),

							  "interopEchoTests.echoDate" =>
							 array("function" => "i_echoDate",
										 "signature" => $i_echoDate_sig,
										 "docstring" => $i_echoDate_doc),

							 "interopEchoTests.whichToolkit" =>
							 array("function" => "i_whichToolkit",
										 // no sig as no parameters
										 "docstring" => $i_whichToolkit_doc),


						)
				);

?>




