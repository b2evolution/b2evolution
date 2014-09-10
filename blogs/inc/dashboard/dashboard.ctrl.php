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
 * @version $Id: dashboard.ctrl.php 7283 2014-09-05 12:58:59Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;

global $dispatcher, $allow_evo_stats, $blog;

if( $blog )
{
	if( ! $current_User->check_perm( 'blog_ismember', 'view', false, $blog ) )
	{	// We don't have permission for the requested blog (may happen if we come to admin from a link on a different blog)
		set_working_blog( 0 );
		unset( $Blog );
	}
}

$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array(), T_('Global'), '?blog=0' );

$AdminUI->set_path( 'dashboard' );

// Load jquery UI to animate background color on change comment status and to transfer a comment to recycle bin
require_js( '#jqueryUI#' );

require_js( 'communication.js' ); // auto requires jQuery
// Load the appropriate blog navigation styles (including calendar, comment forms...):
require_css( 'blog_base.css' ); // Default styles for the blog navigation
// Colorbox (a lightweight Lightbox alternative) allows to zoom on images and do slideshows with groups of images:
require_js_helper( 'colorbox' );

$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Dashboard'), 'url' => '?ctrl=dashboard' ) );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

if( $blog )
{	// We want to look at a specific blog:

	// load dashboard functions
	load_funcs( 'dashboard/model/_dashboard.funcs.php' );

	// Begin payload block:
	// This div is to know where to display the message after overlay close:
	echo '<div class="first_payload_block">'."\n";

	$AdminUI->disp_payload_begin();

	echo '<h2>'.$Blog->dget( 'name' ).'</h2>';

	echo '<div class="row browse"><div class="col-lg-9 col-xs-12 floatleft">';

	load_class( 'items/model/_itemlist.class.php', 'ItemList' );

	$block_item_Widget = new Widget( 'dash_item' );

	$nb_blocks_displayed = 0;

	$blog_moderation_statuses = explode( ',', $Blog->get_setting( 'moderation_statuses' ) );
	$highest_publish_status = get_highest_publish_status( 'comment', $Blog->ID, false );
	$user_modeartion_statuses = array();

	foreach( $blog_moderation_statuses as $status )
	{
		if( ( $status !== $highest_publish_status ) && $current_User->check_perm( 'blog_comment!'.$status, 'edit', false, $blog ) )
		{
			$user_modeartion_statuses[] = $status;
		}
	}
	$user_perm_moderate_cmt = count( $user_modeartion_statuses );

	if( $user_perm_moderate_cmt )
	{
		/*
		 * COMMENTS:
		 */
		$CommentList = new CommentList2( $Blog );

		// Filter list:
		$CommentList->set_filters( array(
				'types' => array( 'comment','trackback','pingback' ),
				'statuses' => $user_modeartion_statuses,
				'user_perm' => 'moderate',
				'post_statuses' => array( 'published', 'community', 'protected' ),
				'order' => 'DESC',
				'comments' => 5,
			) );

		// Set param prefix for URLs
		$param_prefix = 'cmnt_fullview_';
		if( !empty( $CommentList->param_prefix ) )
		{
			$param_prefix = $CommentList->param_prefix;
		}

		// Get ready for display (runs the query):
		$CommentList->display_init();
	}

	if( $user_perm_moderate_cmt && $CommentList->result_num_rows )
	{	// We have comments awaiting moderation

		global $htsrv_url;

		?>

		<script type="text/javascript">
			<!--
			// currently midified comments id and status. After update is done, the appropiate item will be removed.
			var modifieds = new Array();

			// Process result after publish/deprecate/delete action has been completed
			function processResult(result, modifiedlist)
			{
				jQuery('#comments_container').html(result);
				for(var id in modifiedlist)
				{
					switch(modifiedlist[id])
					{
						case 'published':
							jQuery('#' + id).css( 'backgroundColor', '#339900' );
							break;
						case 'deprecated':
							jQuery('#' + id).css( 'backgroundColor', '#656565' );
							break;
						case 'deleted':
							jQuery('#' + id).css( 'backgroundColor', '#fcc' );
							break;
					}
				}

				var comments_number = jQuery('#new_badge').val();
				if(comments_number == '0')
				{
					var options = {};
					jQuery('#comments_block').effect('blind', options, 200);
					jQuery('#comments_block').remove();
				}
				else
				{
					jQuery('#badge').text(comments_number);
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

				modifieds[divid] = status;

				jQuery.ajax({
				type: 'POST',
				url: '<?php echo $htsrv_url; ?>async.php',
				data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentid=' + id + '&status=' + status + '&action=set_comment_status&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
				success: function(result)
					{
						// var divid = 'comment_' + id;
						delete modifieds[divid];
						processResult( ajax_debug_clear( result ), modifieds);
					}
				});
			}

			// Display voting tool when JS is enable
			jQuery( 'document' ).ready( function() { jQuery( '.vote_spam' ).show(); } );
			// Set comments vote
			function setCommentVote(id, type, vote)
			{
				var divid = 'comment_' + id;
				var highlight_class = '';
				var color = '';
				switch(vote)
				{
					case 'spam':
						color = fadeIn(divid, '#ffc9c9');
						highlight_class = 'roundbutton_red';
						break;
					case 'notsure':
						color = fadeIn(divid, '#bbbbbb');
						break;
					case 'ok':
						color = fadeIn(divid, '#bcffb5');
						highlight_class = 'roundbutton_green';
						break;
				};

				if( highlight_class != '' )
				{
					jQuery( '#vote_'+type+'_'+id ).find( 'a.roundbutton, span.roundbutton' ).addClass( highlight_class );
				}

				jQuery.ajax({
				type: 'POST',
				url: '<?php echo $htsrv_url; ?>anon_async.php',
				data:
					{ 'blogid': <?php echo '\''.$Blog->ID.'\''; ?>,
						'commentid': id,
						'type': type,
						'vote': vote,
						'action': 'set_comment_vote',
						'crumb_comment': <?php echo '\''.get_crumb('comment').'\''; ?>,
					},
				success: function(result)
					{
						if( color != '' )
						{ // Revert back color
							fadeIn( divid, color );
						}
						jQuery('#vote_'+type+'_'+id).after( ajax_debug_clear( result ) );
						jQuery('#vote_'+type+'_'+id).remove();
					}
				});
			}

			// Delete comment
			function deleteComment(id)
			{
				var divid = 'comment_' + id;
				fadeIn(divid, '#fcc');

				modifieds[divid] = 'deleted';

				jQuery.ajax({
				type: 'POST',
				url: '<?php echo $htsrv_url; ?>async.php',
				data: 'action=get_opentrash_link&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
				success: function(result)
					{
						var recycle_bin = jQuery('#recycle_bin');
						if( recycle_bin.length )
						{
							recycle_bin.replaceWith( ajax_debug_clear( result ) );
						}
					}
				});

				jQuery.ajax({
				type: 'POST',
				url: '<?php echo $htsrv_url; ?>async.php',
				data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentid=' + id + '&action=delete_comment&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
				success: function(result)
					{
						jQuery('#' + divid).effect('transfer', { to: jQuery('#recycle_bin') }, 700, function() {
							delete modifieds[divid];
							processResult( ajax_debug_clear( result ), modifieds);
						});
					}
				});
			}

			// Fade in background color
			function fadeIn(id, color)
			{
				var bg_color = jQuery('#' + id).css( 'backgroundColor' );
				jQuery('#' + id).animate({ backgroundColor: color }, 200);
				return bg_color;
			}

			// Delete comment author_url
			function delete_comment_url(id)
			{
				var divid = 'commenturl_' + id;
				fadeIn(divid, '#fcc');

				jQuery.ajax({
					type: 'POST',
					url: '<?php echo $htsrv_url; ?>async.php',
					data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentid=' + id + '&action=delete_comment_url' + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
					success: function(result) { jQuery('#' + divid).remove(); }
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

				// Close antispam popup if Escape key is pressed:
				var keycode_esc = 27;
				jQuery(document).keyup(function(e)
				{
					if( e.keyCode == keycode_esc )
					{
						closeAntispamSettings();
					}
				});
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
				<?php global $rsc_url; ?>
				antispamSettings( '<img src="<?php echo $rsc_url; ?>img/ajax-loader2.gif" alt="<?php echo T_('Loading...'); ?>" title="<?php echo T_('Loading...'); ?>" style="display:block;margin:auto;position:absolute;top:0;bottom:0;left:0;right:0;" />' );
				jQuery.ajax({
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

				jQuery.ajax({
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
				var comment_ids = String(deleted_ids).split(',');
				for( var i=0;i<comment_ids.length; ++i )
				{
					var divid = 'comment_' + comment_ids[i];
					fadeIn(divid, '#fcc');
				}

				jQuery.ajax({
					type: 'POST',
					url: '<?php echo $htsrv_url; ?>async.php',
					data: 'blogid=' + <?php echo $Blog->ID; ?> + '&action=refresh_comments&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
					success: function(result)
					{
						processResult( ajax_debug_clear( result ), modifieds);
					}
				});
			}

			function startRefreshComments()
			{
				jQuery('#comments_container').slideUp('fast', refreshComments());
			}

			// Absolute refresh comment list
			function refreshComments()
			{
				jQuery.ajax({
					type: 'POST',
					url: '<?php echo $htsrv_url; ?>async.php',
					data: 'blogid=' + <?php echo $Blog->ID; ?> + '&action=refresh_comments&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
					success: function(result)
					{
						processResult( ajax_debug_clear( result ), modifieds);
						jQuery('#comments_container').slideDown('fast');
					}
				});
			}

			-->
		</script>
		<?php

		$nb_blocks_displayed++;

		$opentrash_link = get_opentrash_link();
		$refresh_link = '<span class="floatright">'.action_icon( T_('Refresh comment list'), 'refresh', 'javascript:startRefreshComments()' ).'</span> ';

		$show_statuses_param = $param_prefix.'show_statuses[]='.implode( '&amp;'.$param_prefix.'show_statuses[]=', $user_modeartion_statuses );
		$block_item_Widget->title = $refresh_link.$opentrash_link.T_('Comments awaiting moderation').
			' <a href="'.$admin_url.'?ctrl=comments&amp;blog='.$Blog->ID.'&amp;'.$show_statuses_param.'" style="text-decoration:none">'.
			'<span id="badge" class="badge badge-important">'.$CommentList->get_total_rows().'</span></a>';

		echo '<div id="styled_content_block">';
		echo '<div id="comments_block">';

		$block_item_Widget->disp_template_replaced( 'block_start' );

		echo '<div id="comments_container">';

		// GET COMMENTS AWAITING MODERATION (the code generation is shared with the AJAX callback):
		show_comments_awaiting_moderation( $Blog->ID, $CommentList );

		echo '</div>';

		$block_item_Widget->disp_template_raw( 'block_end' );

		echo '</div>';
		echo '</div>';
	}

	/*
	 * RECENT POSTS awaiting moderation
	 */
	// TODO: asimo> Make this configurable per blogs the same way as we have in case of comments
	$default_moderation_statuses = get_visibility_statuses( 'moderation' );
	ob_start();
	foreach( $default_moderation_statuses as $status )
	{ // go through all statuses
		if( display_posts_awaiting_moderation( $status, $block_item_Widget ) )
		{ // a block was dispalyed for this status
			$nb_blocks_displayed++;
		}
	}
	$posts_awaiting_moderation_content = ob_get_contents();
	ob_clean();
	if( ! empty( $posts_awaiting_moderation_content ) )
	{
		echo '<div id="styled_content_block" class="items_container">';
		echo $posts_awaiting_moderation_content;
		echo '</div>';
	}

	/*
	 * RECENTLY EDITED
	 */
	// Create empty List:
	$ItemList = new ItemList2( $Blog, NULL, NULL );

	// Filter list:
	$ItemList->set_filters( array(
			'visibility_array' => get_visibility_statuses( 'keys', array('trash') ),
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

		echo '<div id="styled_content_block" class="items_container">';

		$block_item_Widget->title = T_('Recently edited');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		while( $Item = & $ItemList->get_item() )
		{
			echo '<div class="dashboard_post dashboard_post_'.($ItemList->current_idx % 2 ? 'even' : 'odd' ).'" lang="'.$Item->get('locale').'">';
			// We don't switch locales in the backoffice, since we use the user pref anyway
			// Load item's creator user:
			$Item->get_creator_User();

			$Item->status( array(
					'before' => '<div class="floatright"><span class="note status_'.$Item->status.'"><span>',
					'after'  => '</span></span></div>',
				) );

			echo '<div class="dashboard_float_actions">';
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => ' ',
					'after'     => ' ',
					'class'     => 'ActionButton btn btn-default',
					'text'      => get_icon( 'edit_button' ).' '.T_('Edit')
				) );
			echo '</div>';

			echo '<h3 class="dashboard_post_title">';
			$item_title = $Item->dget('title');
			if( ! strlen($item_title) )
			{
				$item_title = '['.format_to_output(T_('No title')).']';
			}
			echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$item_title.'</a>';
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

			echo '<span class="small">'.evo_htmlspecialchars( $Item->get_content_excerpt( 150 ), NULL, $evo_charset ).'</span>';

			echo '<div style="clear:left;">'.get_icon('pixel').'</div>'; // IE crap
			echo '</div>';
		}

		echo '</div>';

		$block_item_Widget->disp_template_raw( 'block_end' );
	}


	if( $nb_blocks_displayed == 0 )
	{	// We haven't displayed anything yet!

		$nb_blocks_displayed++;

		$block_item_Widget = new Widget( 'block_item' );
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


	echo '</div><div class="col-lg-3 col-xs-12 floatright">';

	/*
	 * RIGHT COL
	 */

	$side_item_Widget = new Widget( 'side_item' );

	echo '<div class="row dashboard_sidebar_panels"><div class="col-lg-12 col-sm-6 col-xs-12">';

	$side_item_Widget->title = T_('Manage your blog');
	$side_item_Widget->disp_template_replaced( 'block_start' );

	echo '<div class="dashboard_sidebar">';
	echo '<ul>';
		if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'">'.T_('Write a new post').' &raquo;</a></li>';
		}

 		echo '<li>'.T_('Browse').':<ul>';
		echo '<li><a href="'.$dispatcher.'?ctrl=items&tab=full&filter=restore&blog='.$Blog->ID.'">'.T_('Posts (full)').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=items&tab=list&filter=restore&blog='.$Blog->ID.'">'.T_('Posts (list)').' &raquo;</a></li>';
		if( $current_User->check_perm( 'blog_comments', 'edit', false, $Blog->ID ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=comments&amp;filter=restore&amp;blog='.$Blog->ID.'">'.T_('Comments').' &raquo;</a></li>';
		}
		echo '</ul></li>';

		if( $current_User->check_perm( 'blog_cats', '', false, $Blog->ID ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=chapters&blog='.$Blog->ID.'">'.T_('Edit categories').' &raquo;</a></li>';
		}

		echo '<li><a href="'.$Blog->get('url').'">'.T_('View this blog').'</a></li>';
	echo '</ul>';
	echo '</div>';

	$side_item_Widget->disp_template_raw( 'block_end' );

	echo '</div><div class="col-lg-12 col-sm-6 col-xs-12">';

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

	echo '</div></div>';

	/*
	 * DashboardBlogSide to be added here (anyone?)
	 */


	echo '</div><div class="clear"></div></div>';


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

	/**
	 * @var AbstractSettings
	 */
	global $global_Cache;

	// Begin payload block:
	$AdminUI->disp_payload_begin();

	echo '<div class="row browse"><div class="col-lg-9 col-xs-12 floatleft">';

	$block_item_Widget = new Widget( 'block_item' );

	$block_item_Widget->title = T_('Updates from b2evolution.net');
	$block_item_Widget->disp_template_replaced( 'block_start' );


	// Note: hopefully, the updates will have been downloaded in the shutdown function of a previous page (including the login screen)
	// However if we have outdated info, we will load updates here.
	load_funcs( 'dashboard/model/_dashboard.funcs.php' );
	// Let's clear any remaining messages that should already have been displayed before...
	$Messages->clear();

	if( b2evonet_get_updates() !== NULL )
	{	// Updates are allowed, display them:

		// Display info & error messages
		echo $Messages->display( NULL, NULL, false, 'action_messages' );

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

	// Track just the first login into b2evolution to determine how many people installed manually vs automatic installs:
	if( $current_User->ID == 1 && $UserSettings->get('first_login') == NULL )
	{
		echo 'This is the Admin\'s first ever login.';
		echo '<img src="http://b2evolution.net/htsrv/track.php?key=first-ever-login" alt="" />';
		// OK, done. Never do this again from now on:
		$UserSettings->set('first_login', $localtimenow ); // We might actually display how long the system has been running somewhere
		$UserSettings->dbupdate();
	}

	echo '</div><div class="col-lg-3 col-xs-12 floatright">';

	/*
	 * RIGHT COL
	 */
	$side_item_Widget = new Widget( 'side_item' );

	$side_item_Widget->title = T_('System stats');
	$side_item_Widget->disp_template_replaced( 'block_start' );

	$post_all_counter = intval( get_table_count( 'T_items__item' ) );
	$post_through_admin = limit_number_by_interval( $global_Cache->get( 'post_through_admin' ), 0, $post_all_counter );
	$post_through_xmlrpc = limit_number_by_interval( $global_Cache->get( 'post_through_xmlrpc' ), 0, $post_all_counter );
	$post_through_email = limit_number_by_interval( $global_Cache->get( 'post_through_email' ), 0, $post_all_counter );
	$post_through_unknown = limit_number_by_interval( ( $post_all_counter - $post_through_admin - $post_through_xmlrpc - $post_through_email ), 0, $post_all_counter );

	echo '<div class="dashboard_sidebar">';
	echo '<ul>';
		echo '<li>'.sprintf( T_('%s Users'), get_table_count( 'T_users' ) ).'</li>';
		echo '<li>'.sprintf( T_('%s Blogs'), get_table_count( 'T_blogs' ) ).'</li>';
		echo '<li>'.sprintf( T_('%s Posts'), $post_all_counter ).'</li>';
		echo '<ul>';
			echo '<li>'.sprintf( T_('%s on web'), $post_through_admin ).'</li>';
			echo '<li>'.sprintf( T_('%s by XMLRPC'), $post_through_xmlrpc ).'</li>';
			echo '<li>'.sprintf( T_('%s by email'), $post_through_email ).'</li>';
			echo '<li>'.sprintf( T_('%s unknown'), $post_through_unknown ).'</li>';
		echo '</ul>';
		echo '<li>'.sprintf( T_('%s Slugs'), get_table_count( 'T_slug' ) ).'</li>';
		echo '<li>'.sprintf( T_('%s Comments'), get_table_count( 'T_comments' ) ).'</li>';
		echo '<li>'.sprintf( T_('%s Files'), get_table_count( 'T_files' ) ).'</li>';
		echo '<li>'.sprintf( T_('%s Conversations'), get_table_count( 'T_messaging__thread' ) ).'</li>';
		echo '<li>'.sprintf( T_('%s Messages'), get_table_count( 'T_messaging__message' ) ).'</li>';
	echo '</ul>';
	echo '</div>';

	$side_item_Widget->disp_template_raw( 'block_end' );

	/*
	 * DashboardAdminSide to be added here (anyone?)
	 */

	echo '</div><div class="clear"></div></div>';

	// End payload block:
	$AdminUI->disp_payload_end();
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>