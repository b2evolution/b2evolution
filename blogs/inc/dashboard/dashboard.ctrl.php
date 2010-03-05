<?php
/**
 * This file implements the UI controller for the dashboard.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @todo add 5 plugin hooks. Will be widgetized later (same as SkinTag became Widgets)
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;

global $dispatcher, $allow_evo_stats;

if( $blog )
{
	if( ! $current_User->check_perm( 'blog_ismember', 1, false, $blog ) )
	{	// We don't have permission for the requested blog (may happen if we come to admin from a link on a different blog)
		set_working_blog( 0 );
		unset( $Blog );
	}
}

$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array(), T_('Global'), '?blog=0' );

$AdminUI->set_path( 'dashboard' );

require_js( 'communication.js' ); // auto requires jQuery

$AdminUI->breadcrumbpath_init();

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

if( $blog )
{	// We want to look at a specific blog:
	// Begin payload block:
	
	// fp> why do we need the following div?
	echo '<div class="first_payload_block">'."\n";	

	$AdminUI->disp_payload_begin();

	echo '<h2>'.$Blog->dget( 'name' ).'</h2>';

	echo '<table class="browse" cellspacing="0" cellpadding="0" border="0"><tr><td>';

	load_class( 'items/model/_itemlist.class.php', 'ItemList' );

	$block_item_Widget = new Widget( 'dash_item' );

	$nb_blocks_displayed = 0;

	/*
	 * COMMENTS:
	 */
	$CommentList = new CommentList( $Blog, "'comment','trackback','pingback'", array( 'draft' ), '',	'',	'DESC',	'',	5 );

	if( $CommentList->result_num_rows )
	{	// We have drafts

		global $htsrv_url;

		?>

		<script type="text/javascript">
			<!--

			var commentIds = new Array();
			var commentsInd = 0;

			// Get comma separated IDs
			function getCommentsIds()
			{
				var ids = '';
				for(var id in commentIds)
				{
					commentId = commentIds[id];
					if(commentId)
					{
						ids = ids + commentId + ',';
					}
				}
				if(ids.length > 0)
				{
					ids = ids.substring(0, ids.length - 1);
				}
				commentsInd++;

				return ids;
			}

			// Process result after publish/deprecate/delete action has been completed
			function processResult(result, ids)
			{
				$('#comments_container').html($('#comments_container').html() + result);

				var comments_number = $('#badge_' + commentsInd).val();
				if(comments_number == '0')
				{
					var options = {};
					$('#comments_block').effect('blind', options, 200);
					$('#comments_block').remove();
				}
				else
				{
					$('#badge').text(comments_number);
					var newCommentIds = $('#comments_' + commentsInd).val().split(',');
					for(index = 0; index < newCommentIds.length; index++)
					{
						var arrayIndex = 'comment_' + newCommentIds[index];
						commentIds[arrayIndex] = newCommentIds[index];
					}
				}

				for( var i=0;i<ids.length; ++i )
				{
					var divid = 'comment_' + ids[i];
					var options = {};
					$('#' + divid).effect('blind', options, 200);
					$('#' + divid).remove();
				}
			}

			// Set comments status
			function setCommentStatus(id, status)
			{
				var divid = 'comment_' + id;
				switch(status)
				{
					case 'published':
						fadeIn(divid, '#339900');
						break;
					case 'deprecated':
						fadeIn(divid, '#656565');
						break;
				};

				delete commentIds[divid];
				var ids = getCommentsIds();

				$.ajax({
				type: 'POST',
				url: '<?php echo $htsrv_url; ?>async.php',
				data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentid=' + id + '&status=' + status + '&action=set_comment_status' + '&ids=' + ids + '&ind=' + commentsInd + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
				success: function(result) 
					{
						modified_ids = new Array();
						modified_ids.push(id);
						processResult(result, modified_ids);	
					}
				});
			}

			// Delete comment
			function deleteComment(id)
			{
				var divid = 'comment_' + id;
				fadeIn(divid, '#EE0000');

				delete commentIds[divid];
				var ids = getCommentsIds();

				$.ajax({
				type: 'POST',
				url: '<?php echo $htsrv_url; ?>async.php',
				data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentid=' + id + '&action=delete_comment' + '&ids=' + ids + '&ind=' + commentsInd + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
				success: function(result) 
					{
						var deleted_ids = new Array();
						deleted_ids.push(id);
						processResult(result, deleted_ids); 
					} 
				});
			}

			// Fade in background color
			function fadeIn(id, color)
			{
				jQuery('#' + id).animate({ backgroundColor: color }, 200);
			}

			// Delete comment author_url
			function delete_comment_url(id)
			{
				var divid = 'commenturl_' + id;
				fadeIn(divid, '#EE0000');
				
				$.ajax({
					type: 'POST',
					url: '<?php echo $htsrv_url; ?>async.php',
					data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentid=' + id + '&action=delete_comment_url' + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
					success: function(result) { $('#' + divid).remove(); }
				});
			}

			// This is called when we get the response from the server:
			function antispamSettings( the_html )
			{
				// add placeholder for antispam settings form:
				jQuery( 'body' ).append( '<div id="screen_mask" onclick="closeAntispamSettings()"></div><div id="overlay_page"></div>' );
				var evobar_height = jQuery( '#evo_toolbar' ).height();
				jQuery( '#screen_mask' ).css({ top: evobar_height });
				jQuery( '#screen_mask' ).fadeTo(1,0.5).fadeIn(200);
				jQuery( '#overlay_page' ).html( the_html ).addClass( 'overlay_page_active' );
				AttachServerRequest( 'antispam_ban' ); // send form via hidden iframe
				jQuery( '#close_button' ).bind( 'click', closeAntispamSettings );
				jQuery( '.SaveButton' ).bind( 'click', refresh_overlay );
			}

			// This is called to close the antispam ban overlay page
			function closeAntispamSettings()
			{
				jQuery( '#overlay_page' ).hide();
				jQuery( '.action_messages').remove();
				jQuery( '#server_messages' ).insertBefore( '.first_payload_block' );
				jQuery( '#overlay_page' ).remove();
				jQuery( '#screen_mask' ).remove();
				return false;
			}

			// Ban comment url
			function ban_url(authorurl)
			{
				$.ajax({
					type: 'POST',
					url: '<?php echo $admin_url; ?>',
					data: 'ctrl=antispam&action=ban&display_mode=js&mode=iframe&request=checkban&keyword=' + authorurl +
						  '&' + <?php echo '\''.url_crumb('antispam').'\''; ?>,
					success: function(result) 
					{
						antispamSettings( result );
					}
				});
			}

			// Refresh overlay page after Check&ban button click
			function refresh_overlay()
			{
				var parameters = jQuery( '#antispam_add' ).serialize();
				
				$.ajax({
					type: 'POST',
					url: '<?php echo $admin_url; ?>',
					data: 'action=ban&display_mode=js&mode=iframe&request=checkban&' + parameters,
					success: function(result) 
					{
						antispamSettings( result );
					}
				});
				return false;
			}

			// Refresh comments on dashboard after ban url -> delete comment
			function refreshAfterBan(deleted_ids)
			{
				var ids = getCommentsIds();
				var comment_ids = String(deleted_ids).split(',');
				for( var i=0;i<comment_ids.length; ++i )
				{
					var divid = 'comment_' + comment_ids[i];
					fadeIn(divid, '#EE0000');

					delete commentIds[divid];
				}
				
				$.ajax({
					type: 'POST',
					url: '<?php echo $htsrv_url; ?>async.php',
					data: 'blogid=' + <?php echo $Blog->ID; ?> + '&action=refresh_comments&ids=' + ids + '&ind=' + commentsInd + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
					success: function(result) 
					{
						processResult(result, comment_ids);
					}
				});
			}

			function startRefreshComments()
			{
				$('#comments_container').slideUp('fast', refreshComments());
			}

			// Absolute refresh comment list
			function refreshComments()
			{	
				var ids = new Array();
				for(var id in commentIds)
				{
					var divid = commentIds[id];
					delete commentIds[divid];
				}

				$.ajax({
					type: 'POST',
					url: '<?php echo $htsrv_url; ?>async.php',
					data: 'blogid=' + <?php echo $Blog->ID; ?> + '&action=refresh_comments&ids=' + ids + '&ind=' + commentsInd + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
					success: function(result)
					{
						$('#comments_container').html(result);
						var comments_number = $('#badge_' + commentsInd).val();
						$('#badge').text(comments_number);
						$('#comments_container').slideDown('fast');
					}
				});
			}

			-->
		</script>
		<?php

		$nb_blocks_displayed++;
		
		$refresh_link = '<span class="floatright">'.action_icon( T_('Refresh comment list'), 'refresh', 'javascript:startRefreshComments()' ).'</span> ';
		
		$block_item_Widget->title = $refresh_link.T_('Comments awaiting moderation').' <span id="badge" class="badge">'.get_comments_awaiting_moderation_number( $Blog->ID ).'</span>';

		echo '<div id="comments_block">';

		$block_item_Widget->disp_template_replaced( 'block_start' );

		echo '<div id="comments_container">';

		load_funcs( 'dashboard/model/_dashboard.funcs.php' );
		// GET COMMENTS AWAITING MODERATION (the code generation is shared with the AJAX callback):
		show_comments_awaiting_moderation( $Blog->ID );

		echo '</div>';

		$block_item_Widget->disp_template_raw( 'block_end' );

		echo '</div>';
	}

	/*
	 * RECENT DRAFTS
	 */
	// Create empty List:
	$ItemList = new ItemList2( $Blog, NULL, NULL );

	// Filter list:
	$ItemList->set_filters( array(
			'visibility_array' => array( 'draft' ),
			'orderby' => 'datemodified',
			'order' => 'DESC',
			'posts' => 5,
		) );

	// Get ready for display (runs the query):
	$ItemList->display_init();

	if( $ItemList->result_num_rows )
	{	// We have drafts

		$nb_blocks_displayed++;

		$block_item_Widget->title = T_('Recent drafts');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		while( $Item = & $ItemList->get_item() )
		{
			echo '<div class="dashboard_post dashboard_post_'.($ItemList->current_idx % 2 ? 'even' : 'odd' ).'" lang="'.$Item->get('locale').'">';
			// We don't switch locales in the backoffice, since we use the user pref anyway
			// Load item's creator user:
			$Item->get_creator_User();

			echo '<div class="dashboard_float_actions">';
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => ' ',
					'after'     => ' ',
					'class'     => 'ActionButton'
				) );
			$Item->publish_link( '', '', '#', '#', 'PublishButton' );
			echo '<img src="'.$rsc_url.'/img/blank.gif" alt="" />';
			echo '</div>';

			echo '<h3 class="dashboard_post_title">';
			$item_title = $Item->dget('title');
			if( ! strlen($item_title) )
			{
				$item_title = '['.format_to_output(T_('No title')).']';
			}
			echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$item_title.'</a>';
			echo ' <span class="dashboard_post_details">';
			$Item->status( array(
					'before' => '<div class="floatright"><span class="status_'.$Item->status.'">',
					'after'  => '</span></div>',
				) );
			echo '</span>';
			echo '</h3>';

			echo '</div>';

		}

		$block_item_Widget->disp_template_raw( 'block_end' );
	}


	/*
	 * RECENTLY EDITED
	 */
	// Create empty List:
	$ItemList = new ItemList2( $Blog, NULL, NULL );

	// Filter list:
	$ItemList->set_filters( array(
			'visibility_array' => array( 'published', 'protected', 'private', 'deprecated', 'redirected' ),
			'orderby' => 'datemodified',
			'order' => 'DESC',
			'posts' => 5,
		) );

	// Get ready for display (runs the query):
	$ItemList->display_init();

	if( $ItemList->result_num_rows )
	{	// We have recent edits

		$nb_blocks_displayed++;

		if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
		{	// We have permission to add a post with at least one status:
			$block_item_Widget->global_icon( T_('Write a new post...'), 'new', '?ctrl=items&amp;action=new&amp;blog='.$Blog->ID, T_('New post').' &raquo;', 3, 4 );
		}

		$block_item_Widget->title = T_('Recently edited');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		while( $Item = & $ItemList->get_item() )
		{
			echo '<div class="dashboard_post dashboard_post_'.($ItemList->current_idx % 2 ? 'even' : 'odd' ).'" lang="'.$Item->get('locale').'">';
			// We don't switch locales in the backoffice, since we use the user pref anyway
			// Load item's creator user:
			$Item->get_creator_User();

			echo '<div class="dashboard_float_actions">';
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => ' ',
					'after'     => ' ',
					'class'     => 'ActionButton'
				) );
			echo '</div>';

			echo '<h3 class="dashboard_post_title">';
			$item_title = $Item->dget('title');
			if( ! strlen($item_title) )
			{
				$item_title = '['.format_to_output(T_('No title')).']';
			}
			echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$item_title.'</a>';
			echo ' <span class="dashboard_post_details">';
			$Item->status( array(
					'before' => '<div class="floatright"><span class="status_'.$Item->status.'">',
					'after'  => '</span></div>',
				) );
			$Item->views();
			echo '</span>';
			echo '</h3>';

			// Display images that are linked to this post:
			$Item->images( array(
					'before' =>              '<div class="dashboard_thumbnails">',
					'before_image' =>        '',
					'before_image_legend' => NULL,	// No legend
					'after_image_legend' =>  NULL,
					'after_image' =>         '',
					'after' =>               '</div>',
					'image_size' =>          'fit-80x80',
					'restrict_to_image_position' => 'teaser',	// Optionally restrict to files/images linked to specific position: 'teaser'|'aftermore'
				) );

			echo '<div class="small">'.$Item->get_content_excerpt( 150 ).'</div>';

			echo '<div style="clear:left;">'.get_icon('pixel').'</div>'; // IE crap
			echo '</div>';
		}

		$block_item_Widget->disp_template_raw( 'block_end' );
	}


	if( $nb_blocks_displayed == 0 )
	{	// We haven't displayed anything yet!

		$nb_blocks_displayed++;

		$block_item_Widget->title = T_('Getting started');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		echo '<p><strong>'.T_('Welcome to your new blog\'s dashboard!').'</strong></p>';

		echo '<p>'.T_('Use the links on the right to write a first post or to customize your blog.').'</p>';

		echo '<p>'.T_('You can see your blog page at any time by clicking "See" in the b2evolution toolbar at the top of this page.').'</p>';

 		echo '<p>'.T_('You can come back here at any time by clicking "Manage" in that same evobar.').'</p>';

		$block_item_Widget->disp_template_raw( 'block_end' );
	}


	/*
	 * DashboardBlogMain to be added here (anyone?)
	 */


	echo '</td><td>';

	/*
	 * RIGHT COL
	 */

	$side_item_Widget = new Widget( 'side_item' );

	$side_item_Widget->title = T_('Manage your blog');
	$side_item_Widget->disp_template_replaced( 'block_start' );

	echo '<div class="dashboard_sidebar">';
	echo '<ul>';
		echo '<li><a href="'.$dispatcher.'?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'">'.T_('Write a new post').' &raquo;</a></li>';

 		echo '<li>'.T_('Browse').':<ul>';
		echo '<li><a href="'.$dispatcher.'?ctrl=items&tab=full&filter=restore&blog='.$Blog->ID.'">'.T_('Posts (full)').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=items&tab=list&filter=restore&blog='.$Blog->ID.'">'.T_('Posts (list)').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=comments&amp;blog='.$Blog->ID.'">'.T_('Comments').' &raquo;</a></li>';
		echo '</ul></li>';

		if( $current_User->check_perm( 'blog_cats', '', false, $Blog->ID ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=chapters&blog='.$Blog->ID.'">'.T_('Edit categories').' &raquo;</a></li>';
		}

		if( $current_User->check_perm( 'blog_genstatic', 'any', false, $Blog->ID ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=collections&amp;action=GenStatic&amp;blog='.$Blog->ID.'&amp;redir_after_genstatic='.rawurlencode(regenerate_url( '', '', '', '&' )).'">'.T_('Generate static page!').'</a></li>';
		}

 		echo '<li><a href="'.$Blog->get('url').'">'.T_('View this blog').'</a></li>';
	echo '</ul>';
	echo '</div>';

	$side_item_Widget->disp_template_raw( 'block_end' );

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
	{
		$side_item_Widget->title = T_('Customize your blog');
		$side_item_Widget->disp_template_replaced( 'block_start' );

		echo '<div class="dashboard_sidebar">';
		echo '<ul>';

		echo '<li><a href="'.$dispatcher.'?ctrl=coll_settings&amp;tab=general&amp;blog='.$Blog->ID.'">'.T_('Blog properties').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=coll_settings&amp;tab=features&amp;blog='.$Blog->ID.'">'.T_('Blog features').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$Blog->ID.'">'.T_('Blog skin').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=widgets&amp;blog='.$Blog->ID.'">'.T_('Blog widgets').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=coll_settings&amp;tab=urls&amp;blog='.$Blog->ID.'">'.T_('Blog URLs').' &raquo;</a></li>';

		echo '</ul>';
		echo '</div>';

		$side_item_Widget->disp_template_raw( 'block_end' );
	}


	/*
	 * DashboardBlogSide to be added here (anyone?)
	 */


 	echo '</td></tr></table>';


	// End payload block:
	$AdminUI->disp_payload_end();
	
	echo '</div>'."\n";
}
else
{	// We're on the GLOBAL tab...

	$AdminUI->disp_payload_begin();
	echo '<h2>'.T_('Select a blog').'</h2>';
	// Display blog list VIEW:
	$AdminUI->disp_view( 'collections/views/_coll_list.view.php' );
	$AdminUI->disp_payload_end();


	/*
	 * DashboardGlobalMain to be added here (anyone?)
	 */
}


/*
 * Administrative tasks
 */

if( $current_User->check_perm( 'options', 'edit' ) )
{	// We have some serious admin privilege:
	// Begin payload block:
	$AdminUI->disp_payload_begin();

	echo '<table class="browse" cellspacing="0" cellpadding="0" border="0"><tr><td>';

	$block_item_Widget = new Widget( 'block_item' );

	$block_item_Widget->title = T_('Updates from b2evolution.net');
	$block_item_Widget->disp_template_replaced( 'block_start' );


	// Note: hopefully, the update swill have been downloaded in the shutdown function of a previous page (including the login screen)
	// However if we have outdated info, we will load updates here.
	load_funcs( 'dashboard/model/_dashboard.funcs.php' );
	// Let's clear any remaining messages that should already have been displayed before...
	$Messages->clear( 'all' );

	if( b2evonet_get_updates() !== NULL )
	{	// Updates are allowed, display them:

		// Display info & error messages
		echo $Messages->display( NULL, NULL, false, 'all', NULL, NULL, 'action_messages' );

		/**
		 * @var AbstractSettings
		 */
		global $global_Cache;
		$version_status_msg = $global_Cache->get( 'version_status_msg' );
		if( !empty($version_status_msg) )
		{	// We have managed to get updates (right now or in the past):
			echo '<p>'.$version_status_msg.'</p>';
			$extra_msg = $global_Cache->get( 'extra_msg' );
			if( !empty($extra_msg) )
			{
				echo '<p>'.$extra_msg.'</p>';
			}
		}


		$block_item_Widget->disp_template_replaced( 'block_end' );

		/*
		 * DashboardAdminMain to be added here (anyone?)
		 */

	}
	else
	{
		echo '<p>Updates from b2evolution.net are disabled!</p>';
		echo '<p>You will <b>NOT</b> be alerted if you are running an insecure configuration.</p>';
	}

	echo '</td><td>';

	/*
	 * RIGHT COL
	 */
	$side_item_Widget = new Widget( 'side_item' );

	$side_item_Widget->title = T_('Administrative tasks');
	$side_item_Widget->disp_template_replaced( 'block_start' );

	echo '<div class="dashboard_sidebar">';
	echo '<ul>';
		if( $current_User->check_perm( 'users', 'edit' ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=user&amp;user_tab=identity&amp;action=new">'.T_('Create new user').' &raquo;</a></li>';
		}
		if( $current_User->check_perm( 'blogs', 'create' ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=collections&amp;action=new">'.T_('Create new blog').' &raquo;</a></li>';
		}
		echo '<li><a href="'.$dispatcher.'?ctrl=skins">'.T_('Install a skin').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=plugins">'.T_('Install a plugin').' &raquo;</a></li>';
		// TODO: remember system date check and only remind every 3 months
		echo '<li><a href="'.$dispatcher.'?ctrl=system">'.T_('Check system &amp; security').' &raquo;</a></li>';
		echo '<li><a href="'.$baseurl.'default.php">'.T_('View default page').' &raquo;</a></li>';
	echo '</ul>';
	echo '</div>';

	$side_item_Widget->disp_template_raw( 'block_end' );

	/*
	 * DashboardAdminSide to be added here (anyone?)
	 */

 	echo '</td></tr></table>';

 	// End payload block:
	$AdminUI->disp_payload_end();
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.61  2010/03/05 16:00:16  efy-asimo
 * collapse/expand comment list before/after refresh
 *
 * Revision 1.60  2010/03/05 09:22:26  efy-asimo
 * modify refresh comments visual effect on dashboard
 *
 * Revision 1.59  2010/03/02 12:37:23  efy-asimo
 * remove show_comments_awaiting_moderation function from _misc_funcs.php to _dashboard.func.php
 *
 * Revision 1.58  2010/03/02 11:59:17  efy-asimo
 * refresh icon for dashboard comment list
 *
 * Revision 1.57  2010/02/28 23:38:38  fplanque
 * minor changes
 *
 * Revision 1.56  2010/02/26 15:52:20  efy-asimo
 * combine skin and skin settings tab into one single tab
 *
 * Revision 1.55  2010/02/26 08:34:34  efy-asimo
 * dashboard -> ban icon should be javascripted task
 *
 * Revision 1.54  2010/01/31 17:40:04  efy-asimo
 * delete url from comments in dashboard and comments form
 *
 * Revision 1.53  2010/01/30 18:55:23  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.52  2010/01/29 17:21:38  efy-yury
 * add: crumbs in ajax calls
 *
 * Revision 1.51  2010/01/12 15:56:11  fplanque
 * crumbs
 *
 * Revision 1.50  2009/12/13 17:08:52  efy-maxim
 * ajax - smooth comments disappearing
 *
 * Revision 1.49  2009/12/12 19:14:09  fplanque
 * made avatars optional + fixes on img props
 *
 * Revision 1.48  2009/12/10 21:32:47  efy-maxim
 * 1. single ajax call
 * 2. comments of protected post fix
 *
 * Revision 1.47  2009/12/06 22:55:22  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.46  2009/12/03 11:38:37  efy-maxim
 * ajax calls have been improved
 *
 * Revision 1.45  2009/11/26 10:30:58  efy-maxim
 * ajax actions have been moved to async.php
 *
 * Revision 1.44  2009/11/25 16:05:02  efy-maxim
 * comments awaiting moderation improvements
 *
 * Revision 1.43  2009/11/25 01:33:37  blueyed
 * Dashboard: display 'no title' if there is an empty title.
 *
 * Revision 1.42  2009/11/24 22:09:24  efy-maxim
 * dashboard comments - ajax
 *
 * Revision 1.41  2009/11/22 20:05:12  fplanque
 * highlight statuses on dashboard
 *
 * Revision 1.40  2009/11/22 18:20:10  fplanque
 * Dashboard CSS enhancements
 *
 * Revision 1.39  2009/11/21 13:31:58  efy-maxim
 * 1. users controller has been refactored to users and user controllers
 * 2. avatar tab
 * 3. jQuery to show/hide custom duration
 *
 * Revision 1.38  2009/11/15 19:05:45  fplanque
 * no message
 *
 * Revision 1.37  2009/11/11 03:24:52  fplanque
 * misc/cleanup
 *
 * Revision 1.36  2009/10/28 13:45:47  tblue246
 * Do not display evonet updates if disabled by admin
 *
 * Revision 1.35  2009/10/12 22:11:28  blueyed
 * Fix blank.gif some: use conditional comments, where marked as being required for IE. Add ALT tags and close tags.
 *
 * Revision 1.34  2009/10/11 03:00:10  blueyed
 * Add "position" and "order" properties to attachments.
 * Position can be "teaser" or "aftermore" for now.
 * Order defines the sorting of attachments.
 * Needs testing and refinement. Upgrade might work already, be careful!
 *
 * Revision 1.33  2009/09/20 02:08:52  fplanque
 * badge demo
 *
 * Revision 1.32  2009/09/14 12:54:17  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.31  2009/07/06 23:52:24  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.30  2009/07/06 06:16:11  sam2kb
 * minor
 *
 * Revision 1.29  2009/02/25 22:17:53  blueyed
 * ItemLight: lazily load blog_ID and main_Chapter.
 * There is more, but I do not want to skim the diff again, after
 * "cvs ci" failed due to broken pipe.
 *
 * Revision 1.28  2008/12/18 00:34:13  blueyed
 * - Add Comment::get_author() and make Comment::author() use it
 * - Add Comment::get_title() and use it in Dashboard and Admin comment list
 *
 * Revision 1.27  2008/09/15 03:10:40  fplanque
 * simplified updates
 *
 * Revision 1.26  2008/09/13 11:07:43  fplanque
 * speed up display of dashboard on first login of the day
 *
 * Revision 1.25  2008/04/15 21:53:31  fplanque
 * minor
 *
 * Revision 1.24  2008/03/31 00:27:49  fplanque
 * Enhanced comment moderation
 *
 * Revision 1.23  2008/03/15 19:07:25  fplanque
 * no message
 *
 * Revision 1.22  2008/02/05 01:51:54  fplanque
 * minors
 *
 * Revision 1.21  2008/01/14 07:22:08  fplanque
 * Refactoring
 *
 * Revision 1.20  2008/01/11 19:18:30  fplanque
 * bugfixes
 *
 * Revision 1.19  2008/01/05 02:28:17  fplanque
 * enhanced blog selector (bloglist_buttons)
 *
 * Revision 1.18  2007/12/27 21:40:31  fplanque
 * improved default page
 *
 * Revision 1.17  2007/11/28 17:29:45  fplanque
 * Support for getting updates from b2evolution.net
 *
 * Revision 1.16  2007/11/04 21:22:25  fplanque
 * version bump
 *
 * Revision 1.15  2007/11/03 23:54:39  fplanque
 * skin cleanup continued
 *
 * Revision 1.14  2007/11/03 21:04:26  fplanque
 * skin cleanup
 *
 * Revision 1.13  2007/11/02 02:47:06  fplanque
 * refactored blog settings / UI
 *
 * Revision 1.12  2007/09/12 01:18:32  fplanque
 * translation updates
 *
 * Revision 1.11  2007/09/10 14:53:35  fplanque
 * doc
 *
 * Revision 1.10  2007/09/08 20:23:04  fplanque
 * action icons / wording
 *
 * Revision 1.9  2007/09/07 21:11:11  fplanque
 * superstylin' (not even close)
 *
 * Revision 1.8  2007/09/07 20:11:40  fplanque
 * minor
 *
 * Revision 1.7  2007/09/04 22:16:33  fplanque
 * in context editing of posts
 *
 * Revision 1.6  2007/09/04 19:50:04  fplanque
 * dashboard cleanup
 *
 * Revision 1.5  2007/09/04 15:36:07  fplanque
 * minor
 *
 * Revision 1.4  2007/09/03 18:32:50  fplanque
 * enhanced dashboard / comment moderation
 *
 * Revision 1.3  2007/09/03 16:44:31  fplanque
 * chicago admin skin
 *
 * Revision 1.2  2007/06/30 20:37:37  fplanque
 * UI changes
 *
 * Revision 1.1  2007/06/25 10:59:50  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.21  2007/06/22 23:46:43  fplanque
 * bug fixes
 *
 * Revision 1.20  2007/06/13 23:29:03  fplanque
 * minor
 *
 * Revision 1.19  2007/06/13 20:56:02  fplanque
 * minor
 *
 * Revision 1.18  2007/05/09 01:01:29  fplanque
 * permissions cleanup
 *
 * Revision 1.17  2007/04/26 00:11:15  fplanque
 * (c) 2007
 *
 * Revision 1.16  2007/03/11 23:57:07  fplanque
 * item editing: allow setting to 'redirected' status
 *
 * Revision 1.15  2007/03/05 04:48:15  fplanque
 * IE crap
 *
 * Revision 1.14  2007/03/05 02:13:25  fplanque
 * improved dashboard
 *
 * Revision 1.13  2007/01/28 23:31:57  blueyed
 * todo
 *
 * Revision 1.12  2007/01/19 08:20:57  fplanque
 * bugfix
 *
 * Revision 1.11  2007/01/14 22:43:29  fplanque
 * handled blog view perms.
 *
 * Revision 1.10  2006/12/17 02:42:22  fplanque
 * streamlined access to blog settings
 *
 * Revision 1.9  2006/12/15 22:53:26  fplanque
 * cleanup
 *
 * Revision 1.8  2006/12/12 21:19:31  fplanque
 * UI fixes
 *
 * Revision 1.7  2006/12/12 02:53:56  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.6  2006/12/11 17:26:21  fplanque
 * some cross-linking
 *
 * Revision 1.5  2006/12/09 02:01:48  fplanque
 * temporary / minor
 *
 * Revision 1.4  2006/12/07 23:59:31  fplanque
 * basic dashboard stuff
 *
 * Revision 1.3  2006/12/07 23:21:00  fplanque
 * dashboard blog switching
 *
 * Revision 1.2  2006/12/07 23:13:10  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.1  2006/12/07 22:29:26  fplanque
 * reorganized menus / basic dashboard
 *
 */
?>