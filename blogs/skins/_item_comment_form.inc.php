<?php
/**
 * This is the template that displays the comment form for a post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $cookie_name, $cookie_email, $cookie_url;
global $comment_allowed_tags, $comments_use_autobr;
global $comment_cookies, $comment_allow_msgform;
global $PageCache;
global $Blog;

// Default params:
$params = array_merge( array(
		'disp_comment_form'	   =>	true,
		'form_title_start'     => '<h3>',
		'form_title_end'       => '</h3>',
		'form_title_text'      => T_('Leave a comment'),
		'policy_text'          => '',
		'textarea_lines'       => 10,
		'default_text'         => '',
		'preview_start'        => '<div class="bComment" id="comment_preview">',
		'comment_template'     => '_item_comment.inc.php',	// The template used for displaying individual comments (including preview)
		'preview_end'          => '</div>',
		'before_comment_error' => '<p><em>',
		'after_comment_error'  => '</em></p>',
		'before_comment_form'  => '',
		'after_comment_form'   => '',
	), $params );

/*
 * Comment form:
 */
$section_title = $params['form_title_start'].$params['form_title_text'].$params['form_title_end'];
if( $params['disp_comment_form'] && $Item->can_comment( $params['before_comment_error'], $params['after_comment_error'], '#', '#', $section_title ) )
{ // We want to display the comments form and the item can be commented on:

	echo $params['before_comment_form'];

	// INIT/PREVIEW:
	if( $Comment = $Session->get('core.preview_Comment') )
	{	// We have a comment to preview
		if( $Comment->item_ID == $Item->ID )
		{ // display PREVIEW:

			// We do not want the current rendered page to be cached!!
			if( !empty( $PageCache ) )
			{
				$PageCache->abort_collect();
			}

			// ------------------ PREVIEW COMMENT INCLUDED HERE ------------------
			skin_include( $params['comment_template'], array(
					'Comment'              => & $Comment,
					'comment_start'        => $params['preview_start'],
					'comment_end'          => $params['preview_end'],
				) );
			// Note: You can customize the default item feedback by copying the generic
			// /skins/_item_comment.inc.php file into the current skin folder.
			// ---------------------- END OF PREVIEW COMMENT ---------------------

			// Form fields:
			$comment_content = $Comment->original_content;
			$comment_attachments = $Comment->preview_attachments;
			// for visitors:
			$comment_author = $Comment->author;
			$comment_author_email = $Comment->author_email;
			$comment_author_url = $Comment->author_url;
		}

		// delete any preview comment from session data:
		$Session->delete( 'core.preview_Comment' );
	}
	else
	{ // New comment:
		$Comment = new Comment();
		if( ( !empty( $PageCache ) ) && ( $PageCache->is_collecting ) )
		{	// This page is going into the cache, we don't want personal data cached!!!
			// fp> These fields should be filled out locally with Javascript tapping directly into the cookies. Anyone JS savvy enough to do that?
			$comment_author = '';
			$comment_author_email = '';
			$comment_author_url = '';
		}
		else
		{
			$comment_author = isset($_COOKIE[$cookie_name]) ? trim($_COOKIE[$cookie_name]) : '';
			$comment_author_email = isset($_COOKIE[$cookie_email]) ? trim($_COOKIE[$cookie_email]) : '';
			$comment_author_url = isset($_COOKIE[$cookie_url]) ? trim($_COOKIE[$cookie_url]) : '';
		}
		if( empty($comment_author_url) )
		{	// Even if we have a blank cookie, let's reset this to remind the bozos what it's for
			$comment_author_url = 'http://';
		}

		$comment_content =  $params['default_text'];
	}


	if( ( !empty( $PageCache ) ) && ( $PageCache->is_collecting ) )
	{	// This page is going into the cache, we don't want personal data cached!!!
		// fp> These fields should be filled out locally with Javascript tapping directly into the cookies. Anyone JS savvy enough to do that?
	}
	else
	{
		// Get values that may have been passed through after a preview
		param( 'comment_cookies', 'integer', NULL );
		param( 'comment_allow_msgform', 'integer', NULL ); // checkbox

		if( is_null($comment_cookies) )
		{ // "Remember me" checked, if remembered before:
			$comment_cookies = isset($_COOKIE[$cookie_name]) || isset($_COOKIE[$cookie_email]) || isset($_COOKIE[$cookie_url]);
		}
	}

	echo $params['form_title_start'];
	echo $params['form_title_text'];
	echo $params['form_title_end'];


	echo '<script type="text/javascript">
/* <![CDATA[ */
function validateCommentForm(form)
{
	if( form.p.value.replace(/^\s+|\s+$/g,"").length == 0 )
	{
		alert("'.TS_('Please do not send empty comments.').'");
		return false;
	}
}

function fadeIn( id, color )
{
	var bg_color = jQuery("#" + id).css( "backgroundColor" );
	jQuery("#" + id).animate({ backgroundColor: color }, 200).animate({ backgroundColor: bg_color }, 200);
}

// Set comments vote
function setCommentVote( id, type, vote )
{
	var divid = "vote_useful_" + id;
	switch(vote)
	{
		case "no":
			fadeIn(divid, "#ffc9c9");
			break;
		case "yes":
			fadeIn(divid, "#bcffb5");
			break;
	};

	$.ajax({
	type: "POST",
	url: "'.$htsrv_url.'anon_async.php",
	data:
		{ "blogid": "'.$Blog->ID.'",
			"commentid": id,
			"type": type,
			"vote": vote,
			"action": "set_comment_vote",
			"crumb_comment": "'.get_crumb('comment').'",
		},
	success: function(result)
		{
			$("#vote_"+type+"_"+id).after( result );
			$("#vote_"+type+"_"+id).remove();
		}
	});
}
/* ]]> */
</script>';
	
	$Form = new Form( $samedomain_htsrv_url.'comment_post.php', 'bComment_form_id_'.$Item->ID, 'post', NULL, 'multipart/form-data' );
	$Form->begin_form( 'bComment', '', array( 'target' => '_self', 'onsubmit' => 'return validateCommentForm(this);' ) );

	// TODO: dh> a plugin hook would be useful here to add something to the top of the Form.
	//           Actually, the best would be, if the $Form object could be changed by a plugin
	//           before display!

	$Form->add_crumb( 'comment' );
	$Form->hidden( 'comment_post_ID', $Item->ID );
	$Form->hidden( 'redirect_to',
			// Make sure we get back to the right page (on the right domain)
			// fp> TODO: check if we can use the permalink instead but we must check that application wide,
			// that is to say: check with the comments in a pop-up etc...
			// url_rel_to_same_host(regenerate_url( '', '', $Blog->get('blogurl'), '&' ), $htsrv_url)
			// fp> what we need is a regenerate_url that will work in permalinks
			// fp> below is a simpler approach:
			$Item->get_feedback_url( $disp == 'feedback-popup', '&' )
		);

	if( is_logged_in() )
	{ // User is logged in:
		$Form->info_field( T_('User'), '<strong>'.$current_User->get_identity_link( array( 'link_text' => 'text' ) ).'</strong>'
			.' '.get_user_profile_link( ' [', ']', T_('Edit profile') ) );
	}
	else
	{ // User is not logged in:
		// Note: we use funky field names to defeat the most basic guestbook spam bots
		$Form->text( 'u', $comment_author, 40, T_('Name'), '', 100, 'bComment' );

		$Form->text( 'i', $comment_author_email, 40, T_('Email'), '<br />'.T_('Your email address will <strong>not</strong> be revealed on this site.'), 100, 'bComment' );

		$Item->load_Blog();
		if( $Item->Blog->get_setting( 'allow_anon_url' ) )
		{
			$Form->text( 'o', $comment_author_url, 40, T_('Website'), '<br />'.T_('Your URL will be displayed.'), 100, 'bComment' );
		}
	}

	if( $Item->can_rate() )
	{	// Comment rating:
		echo $Form->begin_field( NULL, T_('Your vote'), true );
		$Comment->rating_input();
		echo $Form->end_field();
	}

	if( !empty($params['policy_text']) )
	{	// We have a policy text to display
		$Form->info_field( '', $params['policy_text'] );
	}

	echo '<div class="comment_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'DisplayCommentToolbar', array() );
	echo '</div>';

	// Message field:
	$note = '';
	// $note = T_('Allowed XHTML tags').': '.htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags));
	$Form->textarea( 'p', $comment_content, $params['textarea_lines'], T_('Comment text'), $note, 38, 'bComment' );

	// set b2evoCanvas for plugins
	echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "p" );</script>';

	// Attach files:
	if( $Item->can_attach() )
	{
		if( !isset( $comment_attachments ) )
		{
			$comment_attachments = '';
		}
		$Form->hidden( 'preview_attachments', $comment_attachments );
		$Form->input_field( array( 'label' => T_('Attach files'), 'note' => '<br />'.get_upload_restriction(), 'name' => 'uploadfile[]', 'type' => 'file', 'size' => '30' ) );
	}

	$comment_options = array();

	if( substr($comments_use_autobr,0,4) == 'opt-')
	{
		$comment_options[] = '<label><input type="checkbox" class="checkbox" name="comment_autobr" tabindex="6"'
													.( ($comments_use_autobr == 'opt-out') ? ' checked="checked"' : '' )
													.' value="1" /> '.T_('Auto-BR').'</label>'
													.' <span class="note">('.T_('Line breaks become &lt;br /&gt;').')</span>';
	}

	if( ! is_logged_in() )
	{ // User is not logged in:
		$comment_options[] = '<label><input type="checkbox" class="checkbox" name="comment_cookies" tabindex="7"'
													.( $comment_cookies ? ' checked="checked"' : '' ).' value="1" /> '.T_('Remember me').'</label>'
													.' <span class="note">('.T_('For my next comment on this site').')</span>';
		// TODO: If we got info from cookies, Add a link called "Forget me now!" (without posting a comment).

		$comment_options[] = '<label><input type="checkbox" class="checkbox" name="comment_allow_msgform" tabindex="8"'
													.( $comment_allow_msgform ? ' checked="checked"' : '' ).' value="1" /> '.T_('Allow message form').'</label>'
													.' <span class="note">('.T_('Allow users to contact me through a message form -- Your email will <strong>not</strong> be revealed!').')</span>';
		// TODO: If we have an email in a cookie, Add links called "Add a contact icon to all my previous comments" and "Remove contact icon from all my previous comments".
	}

	if( ! empty($comment_options) )
	{
		echo $Form->begin_field( NULL, T_('Options'), true );
		echo implode( '<br />', $comment_options );
		echo $Form->end_field();
	}

	$Plugins->trigger_event( 'DisplayCommentFormFieldset', array( 'Form' => & $Form, 'Item' => & $Item ) );

	$Form->begin_fieldset();
		echo '<div class="input">';

		$Form->button_input( array( 'name' => 'submit_comment_post_'.$Item->ID.'[save]', 'class' => 'submit', 'value' => T_('Send comment'), 'tabindex' => 10 ) );
		$Form->button_input( array( 'name' => 'submit_comment_post_'.$Item->ID.'[preview]', 'class' => 'preview', 'value' => T_('Preview'), 'tabindex' => 9 ) );

		$Plugins->trigger_event( 'DisplayCommentFormButton', array( 'Form' => & $Form, 'Item' => & $Item ) );

		echo '</div>';
	$Form->end_fieldset();
	?>

	<div class="clear"></div>

	<?php
	$Form->end_form();

	echo $params['after_comment_form'];
}


/*
 * $Log$
 * Revision 1.33  2011/10/03 15:58:18  efy-yurybakh
 * Add User::get_identity_link() everywhere
 *
 * Revision 1.32  2011/10/03 07:02:22  efy-yurybakh
 * bubbletips & identity_links cleanup
 *
 * Revision 1.31  2011/09/29 16:42:19  efy-yurybakh
 * colored login
 *
 * Revision 1.30  2011/09/29 10:19:40  efy-yurybakh
 * background color for voting for spam
 *
 * Revision 1.29  2011/09/28 16:15:56  efy-yurybakh
 * "comment was helpful" votes
 *
 * Revision 1.28  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.27  2011/09/18 00:58:44  fplanque
 * forms cleanup
 *
 * Revision 1.26  2011/09/17 02:31:58  fplanque
 * Unless I screwed up with merges, this update is for making all included files in a blog use the same domain as that blog.
 *
 * Revision 1.25  2011/09/06 03:25:41  fplanque
 * i18n update
 *
 * Revision 1.24  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.23  2011/08/25 05:40:57  efy-asimo
 * Allow comments for "Members only" - display disabled comment form
 *
 * Revision 1.22  2011/06/29 13:14:01  efy-asimo
 * Use ajax to display comment and contact forms
 *
 * Revision 1.21  2011/03/03 12:47:29  efy-asimo
 * comments attachments
 *
 * Revision 1.20  2011/03/02 09:45:59  efy-asimo
 * Update collection features allow_comments, disable_comments_bypost, allow_attachments, allow_rating
 *
 * Revision 1.19  2010/06/07 19:05:58  sam2kb
 * Added missing params
 *
 * Revision 1.18  2010/03/24 02:56:28  sam2kb
 * JS comment form validation, checks if text area is empty
 *
 * Revision 1.17  2010/02/08 17:56:12  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.16  2010/01/30 18:55:37  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.15  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.14  2009/11/21 17:33:05  sam2kb
 * Configurable form title text
 *
 * Revision 1.13  2009/05/20 13:56:57  fplanque
 * Huh? Textarea is styled wide with CSS. On non CSS supporting browsers you don't want it to be larger than the screen might be.
 *
 * Revision 1.12  2009/05/20 13:53:51  fplanque
 * Return to a clean url after posting a comment
 *
 * Revision 1.11  2009/05/19 19:08:35  blueyed
 * Make comment textarea wider (40 cols => 80 cols). This provides a better editing experience - most users do not have Vimperator installed to work around such a small textarea (RTE enabling is another thing) ;)
 *
 * Revision 1.10  2009/03/08 23:57:56  fplanque
 * 2009
 *
 * Revision 1.9  2008/09/28 08:06:09  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.8  2008/09/27 08:14:02  fplanque
 * page level caching
 *
 * Revision 1.7  2008/07/07 05:59:26  fplanque
 * minor / doc / rollback of overzealous indetation "fixes"
 *
 * Revision 1.6  2008/06/22 18:19:24  blueyed
 * - "Remember me" checked, if name, url or email has been remembered before
 * - Fix indent
 *
 * Revision 1.5  2008/02/11 23:46:35  fplanque
 * cleanup
 *
 * Revision 1.4  2008/02/05 01:52:37  fplanque
 * enhanced comment form
 *
 * Revision 1.3  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.2  2008/01/19 14:53:06  yabs
 * bugfix : checkboxes remember settings after preview
 *
 * Revision 1.1  2007/12/22 16:41:05  fplanque
 * Modular feedback template.
 *
 */
?>
