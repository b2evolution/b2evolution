<?php
/**
 * This file implements the UI view for the Collection comments properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

?>
<script type="text/javascript">
	<!--
	function show_hide_feedback_details(ob)
	{
		var fldset = jQuery( '.feedback_details_container' );
		if( ob.value == 'never' )
		{
			for( i = 0; i < fldset.length; i++ )
			{
				fldset[i].style.display = 'none';
			}
		}
		else
		{
			for( i = 0; i < fldset.length; i++ )
			{
				fldset[i].style.display = '';
			}
		}
	}
	//-->
</script>
<?php

$Form = new Form( NULL, 'coll_comments_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'comments' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Feedback options') );
	$Form->radio( 'allow_view_comments', $edited_Blog->get_setting( 'allow_view_comments' ),
						array(  array( 'any', T_('Any user'), T_('Including anonymous users') ),
								array( 'registered', T_('Registered users only') ),
								array( 'member', T_('Members only'),  T_( 'Users have to be members of this blog' ) ),
								array( 'moderator', T_('Moderators & Admins only') ),
					), T_('Comment viewing by'), true );

	$Form->radio( 'allow_comments', $edited_Blog->get_setting( 'allow_comments' ),
						array(  array( 'any', T_('Any user'), T_('Including anonymous users'),
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'registered', T_('Registered users only'),  '',
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'member', T_('Members only'),  T_( 'Users have to be members of this blog' ),
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'never', T_('Not allowed'), '',
										'', 'onclick="show_hide_feedback_details(this);"'),
					), T_('Comment posting by'), true );

	echo '<div class="feedback_details_container">';

	$Form->checkbox( 'disable_comments_bypost', $edited_Blog->get_setting( 'disable_comments_bypost' ), '', T_('Comments can be disabled on each post separately') );

	$Form->checkbox( 'allow_anon_url', $edited_Blog->get_setting( 'allow_anon_url' ), T_('Anonymous URLs'), T_('Allow anonymous commenters to submit an URL') );

	$any_option = array( 'any', T_('Any user'), T_('Including anonymous users'), '' );
	$registered_option = array( 'registered', T_('Registered users only'),  '', '' );
	$member_option = array( 'member', T_('Members only'), T_('Users have to be members of this blog'), '' );
	$never_option = array( 'never', T_('Not allowed'), '', '' );
	$Form->radio( 'allow_attachments', $edited_Blog->get_setting( 'allow_attachments' ),
						array(  $any_option, $registered_option, $member_option, $never_option,
						), T_('Allow attachments from'), true );

	$Form->radio( 'allow_rating_items', $edited_Blog->get_setting( 'allow_rating_items' ),
						array( $any_option, $registered_option, $member_option, $never_option,
						), T_('Allow star ratings from'), true );

	$Form->checkbox( 'allow_rating_comment_helpfulness', $edited_Blog->get_setting( 'allow_rating_comment_helpfulness' ), T_('Allow helpful/not helpful'), T_("Allow users to say if a comment was helpful or not.") );

	$Form->checkbox( 'blog_allowtrackbacks', $edited_Blog->get( 'allowtrackbacks' ), T_('Trackbacks'), T_("Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.") );

	$status_options = array(
			'draft'      => T_('Draft'),
			'published'  => T_('Published'),
			'deprecated' => T_('Deprecated')
		);
	$Form->select_input_array( 'new_feedback_status', $edited_Blog->get_setting('new_feedback_status'), $status_options,
				T_('New feedback status'), T_('This status will be assigned to new comments/trackbacks from non moderators (unless overriden by plugins).') );

	$Form->radio( 'comments_orderdir', $edited_Blog->get_setting('comments_orderdir'),
						array(	array( 'ASC', T_('Chronologic') ),
								array ('DESC', T_('Reverse') ),
						), T_('Display order'), true );

	$Form->checkbox( 'paged_comments', $edited_Blog->get_setting( 'paged_comments' ), T_( 'Paged comments' ), T_( 'Check to enable paged comments on the public pages.' ) );

	$Form->text( 'comments_per_page', $edited_Blog->get_setting('comments_per_page'), 4, T_('Comments/Page'),  T_('How many comments do you want to display on one page?'), 4 );

	global $default_avatar;
	$Form->radio( 'default_gravatar', $edited_Blog->get_setting('default_gravatar'),
						array(	array( 'b2evo', T_('Default image'), $default_avatar ),
								array ('', 'Gravatar' ),
								array ('identicon', 'Identicon' ),
								array ('monsterid', 'Monsterid' ),
								array ('wavatar', 'Wavatar' ),
						), T_('Default gravatars'), true, T_('Gravatar users can choose to set up a unique icon for themselves, and if they don\'t, they will be assigned a default image.') );

	echo '</div>';

	if( $edited_Blog->get_setting( 'allow_comments' ) == 'never' )
	{ ?>
	<script type="text/javascript">
		<!--
		var fldset = jQuery( '.feedback_details_container' );
		for( i = 0; i < fldset.length; i++ )
		{
			fldset[i].style.display = 'none';
		}
		//-->
	</script>
	<?php
	}

$Form->end_fieldset();

// display comments settings provided by optional modules:
// echo 'modules';
modules_call_method( 'display_collection_comments', array( 'Form' => & $Form, 'edited_Blog' => & $edited_Blog ) );

$Form->begin_fieldset( T_('RSS/Atom feeds') );
	$Form->radio( 'comment_feed_content', $edited_Blog->get_setting('comment_feed_content'),
								array(  array( 'none', T_('No feeds') ),
										array( 'excerpt', T_('Comment excerpts') ),
										array( 'normal', T_('Standard comment contents') ),
									), T_('Comment feed contents'), true, T_('How much content do you want to make available in comment feeds?') );

	$Form->text( 'comments_per_feed', $edited_Blog->get_setting('comments_per_feed'), 4, T_('Comments in feeds'),  T_('How many of the latest comments do you want to include in RSS & Atom feeds?'), 4 );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Subscriptions') );
	$Form->checkbox( 'allow_item_subscriptions', $edited_Blog->get_setting( 'allow_item_subscriptions' ), T_('Email subscriptions'), T_( 'Allow users to subscribe and receive email notifications for comments on a specific post.' ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Registration of commenters') );
	$Form->checkbox( 'comments_detect_email', $edited_Blog->get_setting( 'comments_detect_email' ), T_('Email addresses'), T_( 'Detect email addresses in comments.' ) );

	$Form->checkbox( 'comments_register', $edited_Blog->get_setting( 'comments_register' ), T_('Register after comment'), T_( 'Display the registration form right after submitting a comment.' ) );
$Form->end_fieldset();


$Form->end_form( array(
	array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
	array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

?>
<script type="text/javascript">
	jQuery( '#paged_comments' ).click( function()
	{
		if ( $('#paged_comments').is(':checked') )
		{
			$('#comments_per_page').val('20');
		}
		else
		{
			$('#comments_per_page').val('1000');
		}
	} );
</script>
<?php


/*
 * $Log$
 * Revision 1.7  2011/10/17 15:32:47  efy-yurybakh
 * Let people create an account just after posting a comment
 *
 * Revision 1.6  2011/10/17 15:10:29  efy-yurybakh
 * If there is an email address in a comment, do not allow posting the comment
 *
 * Revision 1.5  2011/10/05 12:05:02  efy-yurybakh
 * Blog settings > features tab refactoring
 *
 * Revision 1.4  2011/10/03 17:13:04  efy-yurybakh
 * review fp>yura comments
 *
 * Revision 1.3  2011/09/30 13:03:20  fplanque
 * doc
 *
 * Revision 1.2  2011/09/30 04:56:39  efy-yurybakh
 * RSS feed settings
 *
 * Revision 1.1  2011/09/28 12:09:53  efy-yurybakh
 * "comment was helpful" votes (new tab "comments")
 *
 *
 */
?>