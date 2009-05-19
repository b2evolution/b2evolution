<?php
/**
 * This is the template that displays the comment form for a post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $cookie_name, $cookie_email, $cookie_url;
global $comment_allowed_tags, $comments_use_autobr;
global $comment_cookies, $comment_allow_msgform;
global $PageCache;

// Default params:
$params = array_merge( array(
		'disp_comment_form'	   =>	true,
		'form_title_start'     => '<h3>',
		'form_title_end'       => '</h3>',
		'policy_text'          => '',
		'textarea_lines'       => 10,
		'default_text'         => '',
		'preview_start'        => '<div class="bComment" id="comment_preview">',
		'comment_template'     => '_item_comment.inc.php',	// The template used for displaying individual comments (including preview)
		'preview_end'          => '</div>',
	), $params );

/*
 * Comment form:
 */
if( $params['disp_comment_form'] && $Item->can_comment() )
{ // We want to display the comments form and the item can be commented on:

	// INIT/PREVIEW:
	if( $Comment = $Session->get('core.preview_Comment') )
	{	// We have a comment to preview
		if( $Comment->item_ID == $Item->ID )
		{ // display PREVIEW:

			// We do not want the current rendered page to be cached!!
			$PageCache->abort_collect();

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
		$Comment = & new Comment();
		if( $PageCache->is_collecting )
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


	if( $PageCache->is_collecting )
	{	// This page is going into the cache, we don't want personal data cached!!!
		// fp> These fields should be filled out locally with Javascript tapping directly into the cookies. Anyone JS savvy enough to do that?
	}
	else
	{
		if( is_null($comment_cookies) )
		{ // "Remember me" checked, if remembered before:
			$comment_cookies = isset($_COOKIE[$cookie_name]) || isset($_COOKIE[$cookie_email]) || isset($_COOKIE[$cookie_url]);
		}
	}

	echo $params['form_title_start'];
	echo T_('Leave a comment');
	echo $params['form_title_end'];


	$Form = & new Form( $htsrv_url.'comment_post.php', 'bComment_form_id_'.$Item->ID, 'post' );
	$Form->begin_form( 'bComment', '', array( 'target' => '_self' ) );

	// TODO: dh> a plugin hook would be useful here to add something to the top of the Form.
	//           Actually, the best would be, if the $Form object could be changed by a plugin
	//           before display!

	$Form->hidden( 'comment_post_ID', $Item->ID );
	$Form->hidden( 'redirect_to',
			// Make sure we get back to the right page (on the right domain)
			// fp> TODO: check if we can use the permalink instead but we must check that application wide,
			// that is to say: check with the comments in a pop-up etc...
			url_rel_to_same_host(regenerate_url( '', '', $Blog->get('blogurl'), '&' ), $htsrv_url)
			// fp> what we need is a regenerate_url that will work in permalinks
			);

	if( is_logged_in() )
	{ // User is logged in:
		$Form->info_field( T_('User'), '<strong>'.$current_User->get_preferred_name().'</strong>'
			.' '.get_user_profile_link( ' [', ']', T_('Edit profile') ) );
	}
	else
	{ // User is not logged in:
		// Note: we use funky field names to defeat the most basic guestbook spam bots
		$Form->text( 'u', $comment_author, 40, T_('Name'), '', 100, 'bComment' );
		$Form->text( 'i', $comment_author_email, 40, T_('Email'), '<br />'.T_('Your email address will <strong>not</strong> be revealed on this site.'), 100, 'bComment' );
		$Form->text( 'o', $comment_author_url, 40, T_('Website'), '<br />'.T_('Your URL will be displayed.'), 100, 'bComment' );
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
	$Form->textarea( 'p', $comment_content, $params['textarea_lines'], T_('Comment text'), $note, 80, 'bComment' );

	// set b2evoCanvas for plugins
	echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "p" );</script>';

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
													.' <span class="note">('.T_('Name, email &amp; website').')</span>';
		// TODO: If we got info from cookies, Add a link called "Forget me now!" (without posting a comment).

		$comment_options[] = '<label><input type="checkbox" class="checkbox" name="comment_allow_msgform" tabindex="8"'
													.( $comment_allow_msgform ? ' checked="checked"' : '' ).' value="1" /> '.T_('Allow message form').'</label>'
													.' <span class="note">('.T_('Allow users to contact you through a message form (your email will <strong>not</strong> be revealed.').')</span>';
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
}


/*
 * $Log$
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
