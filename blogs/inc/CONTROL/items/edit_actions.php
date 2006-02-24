<?php
/**
 * This file implements the UI-Action controller for post/comment editing.
 *
 * Performs one of the following:
 * - Insert new post
 * - Update existing post
 * - Publish existing post
 * - Delete existing post
 * - Update existing comment
 * - Delete existing comment
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$AdminUI->set_path( 'edit' );

param( 'action', 'string', '' );
param( 'mode', 'string', '' );

param( 'aa', 'integer', 2000 );
param( 'mm', 'integer', 1 );
param( 'jj', 'integer', 1 );
param( 'hh', 'integer', 20 );
param( 'mn', 'integer', 30 );
param( 'ss', 'integer', 0 );
$jj = ($jj > 31) ? 31 : $jj;
$hh = ($hh > 23) ? $hh - 24 : $hh;
$mn = ($mn > 59) ? $mn - 60 : $mn;
$ss = ($ss > 59) ? $ss - 60 : $ss;

// All statuses are allowed for acting on:
$show_statuses = array( 'published', 'protected', 'private', 'draft', 'deprecated' );

switch($action)
{
	case 'create':
		/*
		 * --------------------------------------------------------------------
		 * INSERT POST & more
		 */
		// We need early decoding of these in order to check permissions:
		$Request->param( 'post_status', 'string', 'published' );
		$Request->param( 'post_category', 'integer', true );
		$Request->param( 'post_extracats', 'array', array() );
		// make sure main cat is in extracat list and there are no duplicates
		$post_extracats[] = $post_category;
		$post_extracats = array_unique( $post_extracats );
		// Check permission on statuses:
		$current_User->check_perm( 'cats_post_statuses', $post_status, true, $post_extracats );


		// Mumby funky old style navigation stuff:
		$blog = get_catblog( $post_category );
		param( 'mode', 'string', '' );
		switch($mode)
		{
			case 'sidebar':
				$location="b2sidebar.php?blog=$blog";
				break;

			default:
				$location = url_add_param( $admin_url, "ctrl=browse&amp;blog=$blog&amp;filter=restore" );
				break;
		}
		$AdminUI->title = T_('Adding new post...');


    // Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
		$AdminUI->disp_html_head();

		// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
		$AdminUI->disp_body_top();


		// CREATE NEW POST:
		$edited_Item = & new Item();

		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'main_cat_ID', $post_category );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		// Set object params:
		$edited_Item->load_from_Request();

		// Post post-publishing stuff:
		param( 'post_pingback', 'integer', 0 );
		param( 'trackback_url', 'string' );
		$post_trackbacks = & $trackback_url;


		if( $Messages->count() )
		{
			echo '<div class="panelinfo">';
			$Messages->display( T_('Cannot post, please correct these errors:'),
				'[<a href="javascript:history.go(-1)">' . T_('Back to post editing') . '</a>]' );
			echo '</div>';
			break;
		}


		// Are we going to do the pings or not?
		$edited_Item->set( 'pingsdone', $edited_Item->status == 'published' );


		// INSERT NEW POST INTO DB:
		echo '<div class="panelinfo">'."\n";
		echo '<h3>', T_('Recording post...'), "</h3>\n";
		$edited_Item->dbinsert();
		echo "</div>\n";


		// post post-publishing operations:
		if( $edited_Item->status != 'published' )
		{
			echo "<div class=\"panelinfo\">\n";
			echo '<p>', T_('Post not publicly published: skipping trackback, pingback and blog pings...'), "</p>\n";
			echo "</div>\n";
		}
		else
		{ // We do all the pinging now!
			$blogparams = get_blogparams_by_ID( $blog );
			// trackback
			trackbacks( $post_trackbacks, $edited_Item->content, $edited_Item->title, $edited_Item->ID);
			// pingback
			pingback( $post_pingback, $edited_Item->content, $edited_Item->title, $edited_Item->url, $edited_Item->ID, $blogparams);

			// Send email notifications now!
			$edited_Item->send_email_notifications();

			pingb2evonet($blogparams, $edited_Item->ID, $edited_Item->title);
			pingWeblogs($blogparams);
			pingBlogs($blogparams);
			pingTechnorati($blogparams);
		}
		echo '<div class="panelinfo"><p>', T_('Posting Done...'), "</p></div>\n";
		break;


	case 'update':
		/*
		 * --------------------------------------------------------------------
		 * UPDATE POST
		 */
		// We need early decoding of these in order to check permissions:
		$Request->param( 'post_status', 'string', 'published' );
		$Request->param( 'post_category', 'integer', true );
		$Request->param( 'post_extracats', 'array', array() );
		// make sure main cat is in extracat list and there are no duplicates
		$post_extracats[] = $post_category;
		$post_extracats = array_unique( $post_extracats );
		// Check permission on statuses:
		$current_User->check_perm( 'cats_post_statuses', $post_status, true, $post_extracats );


		// Mumby funky old style navigation stuff:
		$blog = get_catblog($post_category);
		$location = url_add_param( $admin_url, 'ctrl=browse&amp;blog='.$blog.'&amp;filter=restore' );
		$AdminUI->title = T_('Updating post...');

		// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
		$AdminUI->disp_html_head();

		// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
		$AdminUI->disp_body_top();


		// UPDATE POST:
		$Request->param( 'post_ID', 'integer', true );
		$edited_Item = & $ItemCache->get_by_ID( $post_ID );

		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'main_cat_ID', $post_category );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		// Set object params:
		$edited_Item->load_from_Request( true );

		// Post post-publishing stuff:
		param( 'post_pingback', 'integer', 0 );
		param( 'trackback_url', 'string' );
		$post_trackbacks = $trackback_url;


		if( $Messages->count() )
		{
			echo '<div class="panelinfo">';
			$Messages->display( T_('Cannot update, please correct these errors:'),
				'[<a href="javascript:history.go(-1)">' . T_('Back to post editing') . '</a>]' );
			echo '</div>';
			break;
		}


		// Check the previous ping state...
		$pings_already_done = $edited_Item->get( 'pingsdone' );
		// Will we have pinged in a minute?
		$edited_Item->set( 'pingsdone', ($pings_already_done || $edited_Item->status == 'published' ) );


		// UPDATE POST IN DB:
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>'.T_('Updating post...')."</h3>\n";
		$edited_Item->dbupdate();
		echo '<p>'.T_('Done.').'</p></div>';


		// post post-publishing operations:
		if( $edited_Item->status != 'published' )
		{
			echo "<div class=\"panelinfo\">\n";
			echo '<p>', T_('Post not publicly published: skipping trackback, pingback and blog pings...'), "</p>\n";
			echo "</div>\n";
		}
		else
		{ // We may do some pinging now!
			$blogparams = get_blogparams_by_ID( $blog );

			// trackback
			trackbacks( $post_trackbacks, $edited_Item->content,  $edited_Item->title, $edited_Item->ID );
			// pingback
			pingback( $post_pingback, $edited_Item->content, $edited_Item->title, $edited_Item->url, $edited_Item->ID, $blogparams);

			// ping ?
			if( $pings_already_done )
			{ // pings have been done before
				echo "<div class=\"panelinfo\">\n";
				echo '<p>', T_('Post had already pinged: skipping blog pings...'), "</p>\n";
				echo "</div>\n";
			}
			else
			{ // We'll ping now

				// Send email notifications now!
				$edited_Item->send_email_notifications();

				pingb2evonet( $blogparams, $edited_Item->post_ID, $edited_Item->title );
				pingWeblogs( $blogparams );
				pingBlogs( $blogparams );
				pingTechnorati( $blogparams );
			}
		}
		echo '<div class="panelinfo"><p>', T_('Updating done...'), "</p></div>\n";
		break;


	case 'publish':
		/*
		 * --------------------------------------------------------------------
		 * PUBLISH POST NOW
		 */
		$Request->param( 'post_ID', 'integer', true );
		$edited_Item = & $ItemCache->get_by_ID( $post_ID );

		$post_cat = $edited_Item->main_cat_ID;
		$blog = get_catblog($post_cat);
		$blogparams = get_blogparams_by_ID( $blog );
		$location = url_add_param( $admin_url, 'ctrl=browse&amp;blog='.$blog.'&amp;filter=restore' );

		$AdminUI->title = T_('Updating post status...');

		// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
		$AdminUI->disp_html_head();

		// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
		$AdminUI->disp_body_top();

		$post_status = 'published';
		// Check permissions:
		/* TODO: Check extra categories!!! */
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );
		$current_User->check_perm( 'edit_timestamp', 'any', true ) ;

		$edited_Item->set( 'status', $post_status );

		$post_date = date('Y-m-d H:i:s', $localtimenow);

		echo "<div class=\"panelinfo\">\n";
		echo '<h3>'.T_('Updating post status...')."</h3>\n";

		// We need to check the previous flags...
		$post_flags = explode(',', $edited_Item->flags );
		if( in_array( 'pingsdone', $post_flags ) )
		{ // pings have been done before
			$pingsdone = true;
		}
		elseif( $edited_Item->status != 'published' )
		{ // still not publishing
			$pingsdone = false;
		}
		else
		{ // We'll be pinging now
			$pingsdone = true;
			$edited_Item->set( 'flags', 'pingsdone' );
		}

		// UPDATE POST IN DB:
		$edited_Item->set( 'datestart', $post_date );
		$edited_Item->set( 'datemodified', date('Y-m-d H:i:s',$localtimenow) );
		$edited_Item->dbupdate();
		echo '<p>', T_('Done.'), "</p>\n";
		echo "</div>\n";

		// pOST POST6PUBLISHING OPERATIONS/
		if( $edited_Item->status != 'published' )
		{
			echo "<div class=\"panelinfo\">\n";
			echo '<p>', T_('Post not publicly published: skipping trackback, pingback and blog pings...'), "</p>\n";
			echo "</div>\n";
		}
		else
		{ // We may do some pinging now!
			$blogparams = get_blogparams_by_ID( $blog );

			// ping ?
			if( in_array( 'pingsdone', $post_flags ) )
			{ // pings have been done before
				echo "<div class=\"panelinfo\">\n";
				echo '<p>', T_('Post had already pinged: skipping blog pings...'), "</p>\n";
				echo "</div>\n";
			}
			else
			{ // We'll ping now

				// Send email notifications now!
				$edited_Item->send_email_notifications();

				pingb2evonet( $blogparams, $post_ID, $edited_Item->title);
				pingWeblogs($blogparams);
				pingBlogs($blogparams);
				pingTechnorati($blogparams);
			}
		}

		echo '<div class="panelinfo"><p>'.T_('Updating done...').'</p></div>';

		break;


	case 'deprecate':
		/*
		 * --------------------------------------------------------------------
		 * DEPRECATE POST
		 */
		$Request->param( 'post_ID', 'integer', true );
		$edited_Item = & $ItemCache->get_by_ID( $post_ID );

		$post_cat = $edited_Item->main_cat_ID;
		$blog = get_catblog($post_cat);
		$blogparams = get_blogparams_by_ID( $blog );
		$location = url_add_param( $admin_url, 'ctrl=browse&amp;blog='.$blog.'&amp;filter=restore' );

		$AdminUI->title = T_('Updating post status...');


		// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
		$AdminUI->disp_html_head();

		// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
		$AdminUI->disp_body_top();


		$post_status = 'deprecated';
		// Check permissions:
		/* TODO: Check extra categories!!! */
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );

		$edited_Item->set( 'status', $post_status );

		echo "<div class=\"panelinfo\">\n";
		echo '<h3>'.T_('Updating post status...')."</h3>\n";

		// UPDATE POST IN DB:
		$edited_Item->set( 'datemodified', date('Y-m-d H:i:s',$localtimenow) );
		$edited_Item->dbupdate();
		echo '<p>', T_('Done.'), "</p>\n";
		echo "</div>\n";

		echo '<div class="panelinfo"><p>'.T_('Updating done...').'</p></div>';

		break;


	case 'delete':
		/*
		 * --------------------------------------------------------------------
		 * DELETE a post from db
		 */
		$AdminUI->title = T_('Deleting post...');

    // Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
		$AdminUI->disp_html_head();

		// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
		$AdminUI->disp_body_top();

		param( 'post', 'integer' );
		// echo $post;
		if( ! ($edited_Item = & $ItemCache->get_by_ID( $post, false ) ) )
		{
			echo '<div class="panelinfo"><p class="error">'.( T_('Oops, no post with this ID!') ).'</p></div>';
			break;
		}
		$blog = $edited_Item->blog_ID;
		$location = url_add_param( $admin_url, 'ctrl=browse&amp;blog='.$blog.'&amp;filter=restore' );

		// Check permission:
		$current_User->check_perm( 'blog_del_post', '', true, $blog );


		// DELETE POST FROM DB:
		echo "<div class=\"panelinfo\">\n";
		echo '<h3>', T_('Deleting post...'), "</h3>\n";
		$edited_Item->dbdelete();
		echo '<p>'.T_('Deleting Done...')."</p>\n";
		echo '</div>';

		break;


	case 'editedcomment':
		/*
		 * --------------------------------------------------------------------
		 * UPDATE comment in db:
		 */
		param( 'comment_ID', 'integer', true );
		// echo $comment_ID;
		$edited_Comment = Comment_get_by_ID( $comment_ID );
		$blog = $edited_Comment->Item->get( 'blog_ID' );

		// Check permission:
		$current_User->check_perm( 'blog_comments', '', true, $blog );

		if( $edited_Comment->author_User === NULL )
		{ // If this is not a member comment
			param( 'newcomment_author', 'string', true );
			param( 'newcomment_author_email', 'string' );
			param( 'newcomment_author_url', 'string' );

			// CHECK url
			if( $error = validate_url( $newcomment_author_url, $allowed_uri_scheme ) )
			{
				$Messages->add( T_('Supplied URL is invalid: ').$error, 'error' );
			}
		}
		param( 'content', 'html' );
		param( 'post_autobr', 'integer', ($comments_use_autobr == 'always')?1:0 );


		// CHECK and FORMAT content
		$content = format_to_post( $content, $post_autobr, 0); // We are faking this NOT to be a comment

		if( $Messages->display( T_('Cannot update comment, please correct these errors:'),
				'[<a href="javascript:history.go(-1)">' . T_('Back to post editing') . '</a>]' ) )
		{
			break;
		}

		$edited_Comment->set( 'content', $content );

		if( $edited_Comment->author_User === NULL )
		{ // If this is not a member comment
			$edited_Comment->set( 'author', $newcomment_author );
			$edited_Comment->set( 'author_email', $newcomment_author_email );
			$edited_Comment->set( 'author_url', $newcomment_author_url );
		}

		$Request->param( 'edit_date', 'integer', 0 );
		if( $edit_date && $current_User->check_perm( 'edit_timestamp' ))
		{ // We use user date
			$edited_Comment->set( 'date', date('Y-m-d H:i:s', mktime( $hh, $mn, $ss, $mm, $jj, $aa ) ) );
		}

		$edited_Comment->dbupdate();	// Commit update to the DB

	 	$comment_post_ID = $edited_Comment->Item->ID;
		$location = url_add_param( $admin_url, 'ctrl=browse&blog='.$blog.'&p=$comment_post_ID&c=1#comments', '&' );
		header ("Location: $location");
		exit();


	case 'deletecomment':
		/*
		 * --------------------------------------------------------------------
		 * DELETE comment from db:
		 */
		param( 'comment_ID', 'integer', true );
		// echo $comment_ID;
		$edited_Comment = Comment_get_by_ID( $comment_ID );
    $comment_post_ID = $edited_Comment->Item->ID;
		$blog = $edited_Comment->Item->get( 'blog_ID' );

		// Check permission:
		$current_User->check_perm( 'blog_comments', '', true, $blog );

		// Delete from Db:
		$edited_Comment->dbdelete();

		$location = url_add_param( $admin_url, 'ctrl=browse&blog='.$blog.'&p=$comment_post_ID&c=1#comments', '&' );
		header ("Location: $location");
		exit();


	default:
		// This can happen if we were displaying an action result, then the user logs out
		// and logs in again: he comes back here with no action parameter set.
		// Residrect to browse
		$location = url_add_param( $admin_url, 'ctrl=browse&blog=0', '&' );
		header ("Location: $location");
		exit();
}

echo '<div class="panelinfo">';
if( empty( $mode ) )
{ // Normal mode:
	if( isset($location) )
	{
		echo '<p><strong>[<a href="'.$location.'">'.T_('Back to posts!').'</a>]</strong></p>';
	}
	echo '<p>' . T_('You may also want to generate static pages or view your blogs...') . '</p>';
	echo '</div>';
	// List the blogs:
	// Display VIEW:
	$AdminUI->disp_view( 'collections/_blogs_list' );
}
else
{ // Special mode:
?>
	<p><strong>[<a href="?ctrl=edit&amp;blog=<?php echo $blog ?>&amp;mode=<?php echo $mode ?>"><?php echo T_('New post') ?></a>]</strong></p>
<?php
}

echo '</div>';


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.2  2006/02/24 00:27:14  blueyed
 * fix
 *
 * Revision 1.1  2006/02/23 21:11:56  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.82  2006/01/06 16:47:42  fplanque
 * no message
 *
 * Revision 1.81  2005/12/12 19:44:09  fplanque
 * Use cached objects by reference instead of copying them!!
 *
 * Revision 1.80  2005/11/21 18:16:29  fplanque
 * okay, a TWO liner :P
 *
 */
?>