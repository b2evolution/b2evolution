<?php
require_once( dirname(__FILE__).'/_header.php' );

param( 'action', 'string', '' );
param( 'mode', 'string', '' );

// All statuses are allowed for acting on:
$show_statuses = array( 'published', 'protected', 'private', 'draft', 'deprecated' );

switch($action) 
{
	
case 'post':
	/*
	 * --------------------------------------------------------------------
	 * INSERT POST & more
	 */
	param( 'post_category', 'integer', true );
	$blog = get_catblog($post_category); 

	$title = T_('Adding new post...');
	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');

	param( "post_autobr", 'integer', 0 );
	param( "post_pingback", 'integer', 0 );
	param( 'trackback_url', 'string' );
	$post_trackbacks = & $trackback_url;
	param( 'content', 'html' );
	param( 'post_title', 'html' );
	param( 'post_url', 'string' );
	param( 'post_status', 'string', 'published' );
	param( 'extracats', array() );
	$post_extracats = & $extracats;
	param( 'post_lang', 'string', $default_language );

	if ($user_level == 0)	die (T_('Cheatin\' uh ?'));

	param( 'edit_date', 'integer' );
	if (($user_level > 4) && $edit_date) 
	{	// We use user date
		param( 'aa', 'string' );
		param( 'mm', 'string' );
		param( 'jj', 'string' );
		param( 'hh', 'string' );
		param( 'mn', 'string' );
		param( 'ss', 'string' );
		$jj = ($jj > 31) ? 31 : $jj;
		$hh = ($hh > 23) ? $hh - 24 : $hh;
		$mn = ($mn > 59) ? $mn - 60 : $mn;
		$ss = ($ss > 59) ? $ss - 60 : $ss;
		$now = "$aa-$mm-$jj $hh:$mn:$ss";
	}
	else
	{	// We use current time
		$now = date("Y-m-d H:i:s",(time() + ($time_difference * 3600)));
	}

	// CHECK and FORMAT content
	$post_title = format_to_post($post_title,0,0);
	if( !validate_url( $post_url, $allowed_uri_scheme ) )
		errors_add( T_('Supplied URL is invalid.') );	
	$content = format_to_post($content,$post_autobr,0);

	if( errors_display( T_('Cannot post, please correct these errors:'), 
			'[<a href="javascript:history.go(-1)">'.T_('Back to post editing').'</a>]' ) )
	{
		break;
	}

	echo "<div class=\"panelinfo\">\n";
	echo '<h3>', T_('Recording post...'), "</h3>\n";

	// Are we going to do the pings or not?
	$pingsdone = ( $post_status == 'published' ) ? true : false;

	// INSERT NEW POST INTO DB:
	$post_ID = bpost_create( $user_ID, $post_title, $content, $now, $post_category,	$post_extracats, $post_status, $post_lang, '',	$post_autobr, $pingsdone, $post_url ) or mysql_oops($query);

	if (isset($sleep_after_edit) && $sleep_after_edit > 0) 
	{
		echo '<p>', T_('Sleeping...'), "</p>\n";
		flush();
		sleep($sleep_after_edit);
	}
	echo '<p>', T_('Done.'), "</p>\n";
	echo "</div>\n";

	if( $post_status != 'published' )
	{
		echo "<div class=\"panelinfo\">\n";
		echo '<p>', T_('Post not publicly published: skipping trackback, pingback and blog pings...'), "</p>\n";
		echo "</div>\n";
	}
	else
	{	// We do all the pinging now!
		$blogparams = get_blogparams_by_ID( $blog );
		// trackback
		trackbacks( $post_trackbacks, $content, $post_title, $post_ID);
		// pingback
		pingback( $post_pingback, $content, $post_title, $post_url, $post_ID, $blogparams);
		pingb2evonet($blogparams, $post_ID, $post_title);
		pingWeblogs($blogparams);
		pingBlogs($blogparams);		
		pingCafelog($cafelogID, $post_title, $post_ID);
	}

	param( 'mode', 'string', '' );
	switch($mode) 
	{
		case "sidebar":
			$location="b2sidebar.php?a=b&blog=$blog";
			break;
			
		default:
			$location="b2browse.php?blog=$blog";
			break;
	}

	?>
	
	<p>Posting Done...<p>

	<?php
	break;




case "editpost":
	/*
	 * --------------------------------------------------------------------
	 * UPDATE POST 
	 */
	$title = "Updating post...";
	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');
	
	if ($user_level == 0)
	die ("Cheatin' uh ?");

	param( "post_ID", 'integer', true );
	param( "post_category", 'integer', true );
	$blog = get_catblog($post_category); 
	param( "post_autobr", 'integer', 0 );
	param( "post_pingback", 'integer', 0 );
	param( 'trackback_url', 'string' );
	$post_trackbacks = $trackback_url;
	param( 'content', 'html' );
	param( 'post_title', 'html' );
	param( 'post_url', 'string' );
	param( 'post_status', 'string', 'published' );
	param( 'extracats', 'array' );
	$post_extracats = & $extracats;
	param( 'post_lang', 'string', $default_language );

	param( 'edit_date', 'integer' );
	if (($user_level > 4) && $edit_date) 
	{
		param( 'aa', 'string' );
		param( 'mm', 'string' );
		param( 'jj', 'string' );
		param( 'hh', 'string' );
		param( 'mn', 'string' );
		param( 'ss', 'string' );
		$jj = ($jj > 31) ? 31 : $jj;
		$hh = ($hh > 23) ? $hh - 24 : $hh;
		$mn = ($mn > 59) ? $mn - 60 : $mn;
		$ss = ($ss > 59) ? $ss - 60 : $ss;
		$datemodif = "$aa-$mm-$jj $hh:$mn:$ss";
	}
	else 
	{
		$datemodif = "";
	}

	// CHECK and FORMAT content	
	$post_title = format_to_post($post_title,0,0);
	if( !validate_url( $post_url, $allowed_uri_scheme ) )
		errors_add( T_('Supplied URL is invalid.') );	
	$content = format_to_post($content,$post_autobr,0);

	if( errors_display( T_('Cannot update, please correct these errors:'), 
			'[<a href="javascript:history.go(-1)">'.T_('Back to post editing').'</a>]' ) )
	{
		break;
	}

	echo "<div class=\"panelinfo\">\n";
	echo "<h3>Updating post...</h3>\n";

	// We need to check the previous flags...
	$postdata = get_postdata($post_ID) or die(T_('Oops, no post with this ID.'));
	$post_flags = $postdata['Flags'];
	if( in_array( 'pingsdone', $post_flags ) )
	{	// pings have been done before
		$pingsdone = true;
	}
	elseif( $post_status != 'published' )
	{	// still not publishing
		$pingsdone = false;
	}
	else
	{	// We'll be pinging now
		$pingsdone = true;
	}

	// UPDATE POST IN DB:
	bpost_update( $post_ID, $post_title, $content, $datemodif, $post_category, $post_extracats, 	$post_status, $post_lang, '',	$post_autobr, $pingsdone, $post_url ) or mysql_oops($query);

	if (isset($sleep_after_edit) && $sleep_after_edit > 0) 
	{
		echo "<p>Sleeping...</p>\n";
		flush();
		sleep($sleep_after_edit);
	}
	echo '<p>', T_('Done.'), "</p>\n";
	echo "</div>\n";

	if( $post_status != 'published' )
	{
		echo "<div class=\"panelinfo\">\n";
		echo '<p>', T_('Post not publicly published: skipping trackback, pingback and blog pings...'), "</p>\n";
		echo "</div>\n";
	}
	else
	{	// We may do some pinging now!
		$blogparams = get_blogparams_by_ID( $blog );

		// trackback
		trackbacks( $post_trackbacks, $content,  $post_title, $post_ID );	
		// pingback
		pingback( $post_pingback, $content, $post_title, $post_url, $post_ID, $blogparams);
	
		// ping ?	
		if( in_array( 'pingsdone', $post_flags ) )
		{	// pings have been done before
			echo "<div class=\"panelinfo\">\n";
			echo '<p>', T_('Post had already pinged: skipping blog pings...'), "</p>\n";
			echo "</div>\n";
		}
		else
		{	// We'll ping now
			pingb2evonet( $blogparams, $post_ID, $post_title);
			pingWeblogs($blogparams);
			pingBlogs($blogparams);		
			pingCafelog($cafelogID, $post_title, $post_ID);
		}
	}	
	?>
	
	<p>Posting Done...<p>

	<?php
		$location="b2browse.php?blog=$blog";
	break;


case "delete":
	/*
	 * --------------------------------------------------------------------
	 * DELETE a post from db
	 */
	$title = "Deleting post...";
	require(dirname(__FILE__).'/_menutop.php');
	require(dirname(__FILE__).'/_menutop_end.php');

	if ($user_level == 0)
	die ("Cheatin' uh ?");

	param( 'post', 'integer' );
	// echo $post;
	$postdata = get_postdata($post) or die(T_('Oops, no post with this ID!'));
	$authordata = get_userdata($postdata["Author_ID"]);

	if ($user_level < $authordata[13])
	die (sprintf( T_('You don\'t have the right to delete <strong>%s</strong>\'s posts.'), $authordata[1] ));

	echo "<div class=\"panelinfo\">\n";
	echo '<h3>', T_('Deleting post...'), "</h3>\n";

	// DELETE POST FROM DB:
	bpost_delete( $post ) or mysql_oops($query);

	if (isset($sleep_after_edit) && $sleep_after_edit > 0) {
		echo '<p>', T_('Sleeping...'), "</p>\n";
		flush();
		sleep($sleep_after_edit);
	}
	echo '<p>', T_('Done.'), "</p>\n";
	echo "</div>\n";

	?>
	
	<p><?php echo T_('Deleting Done...') ?><p>

	<?php
		$location="b2browse.php?blog=$blog";
	break;




case "deletecomment":
	/*
	 * --------------------------------------------------------------------
	 * DELETE comment from db:
	 */

	if ($user_level == 0)
		die ("Cheatin' uh ?");

	param( 'comment', 'html' );
	param( 'p', 'integer' );
	$commentdata=get_commentdata($comment) or die(T_('Oops, no comment with this ID.'));

	$query = "DELETE FROM $tablecomments WHERE comment_ID=$comment";
	$result = mysql_query($query) or mysql_oops( $query );

	header ("Location: b2browse.php?blog=$blog&p=$p&c=1#comments"); //?a=dc");
	exit();


case "editedcomment":
	/*
	 * --------------------------------------------------------------------
	 * UPDATE comment in db:
	 */

	if ($user_level == 0)
		die (T_("Cheatin' uh ?"));

	param( 'blog', 'integer', true );
	param( 'comment_ID', 'integer', true );
	param( 'comment_post_ID', 'integer', true );
	param( 'newcomment_author', 'string', true );
	param( 'newcomment_author_email', 'string' );
	param( 'newcomment_author_url', 'string' );
	param( 'content', 'html' );
	param( "post_autobr", 'integer', ($comments_use_autobr == 'always')?1:0 );

	param( 'edit_date', 'integer' );
	if (($user_level > 4) && $edit_date) 
	{
		param( 'aa', 'string' );
		param( 'mm', 'string' );
		param( 'jj', 'string' );
		param( 'hh', 'string' );
		param( 'mn', 'string' );
		param( 'ss', 'string' );
		$jj = ($jj > 31) ? 31 : $jj;
		$hh = ($hh > 23) ? $hh - 24 : $hh;
		$mn = ($mn > 59) ? $mn - 60 : $mn;
		$ss = ($ss > 59) ? $ss - 60 : $ss;
		$datemodif = ", comment_date=\"$aa-$mm-$jj $hh:$mn:$ss\"";
	} else {
		$datemodif = "";
	}

	// CHECK and FORMAT content	
	if( !validate_url( $newcomment_author_url, $allowed_uri_scheme ) )
		errors_add( T_('Supplied URL is invalid.') );	
	$content = format_to_post($content,$post_autobr,0); // We are faking this NOT to be a comment

	if( errors_display( T_('Cannot update comment, please correct these errors:'), 
			'[<a href="javascript:history.go(-1)">'.T_('Back to post editing').'</a>]' ) )
	{
		break;
	}

	$newcomment_author = addslashes($newcomment_author);
	$newcomment_author_email = addslashes($newcomment_author_email);
	$newcomment_author_url = addslashes($newcomment_author_url);
	$query = "UPDATE $tablecomments SET comment_content=\"$content\", comment_author=\"$newcomment_author\", comment_author_email=\"$newcomment_author_email\", comment_author_url=\"$newcomment_author_url\"".$datemodif." WHERE comment_ID=$comment_ID";
	$result = mysql_query($query) or mysql_oops($query);

	header ("Location: b2browse.php?blog=$blog&p=$comment_post_ID&c=1#comments"); //?a=ec");
	exit();


default:
	echo "nothing to do!";
	$location="b2browse.php?blog=$blog";
}

if( ! errors() )
{
?>
<p><strong>[<a href="<?php echo $location ?>"><?php echo T_('Back to posts!') ?></a>]</strong></p>

<p><?php echo T_('You may also want to generate static pages or view your blogs...') ?></p>
<?php
	// List the blogs:
	require( dirname(__FILE__).'/_blogs_list.php'); 
}
require( dirname(__FILE__).'/_footer.php' ); 
?>