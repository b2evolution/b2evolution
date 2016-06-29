<?php
/**
 * This file implements the UI view for the Collection comments properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog, $AdminUI;

?>
<script type="text/javascript">
	<!--
	function show_hide_feedback_details(ob)
	{
		if( ob.value == 'never' )
		{
			jQuery( '.feedback_details_container' ).hide();
		}
		else
		{
			jQuery( '.feedback_details_container' ).show();
		}
	}
	//-->
</script>
<?php

// This warning is used for 'Trackbacks' and 'New feedback status'
$spammers_warning = '<span class="red"$attrs$>'.get_icon( 'warning_yellow' ).' '.T_('Warning: this makes your site a preferred target for spammers!').'<br /></span>';

// Permission to edit advanced admin settings
$perm_blog_admin = $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID );

$Form = new Form( NULL, 'coll_comments_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'comments' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Comment viewing options') . get_manual_link('comment-viewing-options') );

	$Form->radio( 'allow_view_comments', $edited_Blog->get_setting( 'allow_view_comments' ),
						array(  array( 'any', T_('Any user'), T_('Including anonymous users') ),
								array( 'registered', T_('Registered users only') ),
								array( 'member', T_('Members only'),  T_( 'Users have to be members of this blog' ) ),
								array( 'moderator', T_('Moderators & Admins only') ),
					), T_('Comment viewing by'), true );

	// put this on feedback details container, this way it won't be displayed if comment posting is not allowed
	echo '<div class="feedback_details_container">';

	$Form->radio( 'comments_orderdir', $edited_Blog->get_setting('comments_orderdir'),
						array(	array( 'ASC', T_('Chronologic') ),
								array ('DESC', T_('Reverse') ),
						), T_('Display order'), true );

	$Form->checkbox( 'threaded_comments', $edited_Blog->get_setting( 'threaded_comments' ), T_('Threaded comments'), T_('Check to enable hierarchical threads of comments.') );

	$paged_comments_disabled = (boolean) $edited_Blog->get_setting( 'threaded_comments' );
	$Form->checkbox( 'paged_comments', $edited_Blog->get_setting( 'paged_comments' ), T_( 'Paged comments' ), T_( 'Check to enable paged comments on the public pages.' ), '', 1, $paged_comments_disabled );

	$Form->text( 'comments_per_page', $edited_Blog->get_setting('comments_per_page'), 4, T_('Comments/Page'),  T_('How many comments do you want to display on one page?'), 4 );

	$Form->checkbox( 'comments_avatars', $edited_Blog->get_setting( 'comments_avatars' ), T_('Display profile pictures'), T_('Display profile pictures/avatars for comments.') );

	$Form->checkbox( 'comments_latest', $edited_Blog->get_setting( 'comments_latest' ), T_('Latest comments'), T_('Check to enable viewing of the latest comments') );

	$Form->checklist( get_inskin_statuses_options( $edited_Blog, 'comment' ), 'comment_inskin_statuses', T_('Front office statuses'), false, false, array( 'note' => 'Uncheck the statuses that should never appear in the front office.' ) );

	echo '</div>';

$Form->end_fieldset();

$Form->begin_fieldset( T_('Feedback options') . get_manual_link('comment-feedback-options') );

	$Form->radio( 'allow_comments', $edited_Blog->get_setting( 'allow_comments' ),
						array(  array( 'any', T_('Any user'), T_('Including anonymous users'),
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'registered', T_('Registered users only'),  '',
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'member', T_('Members only'),  T_( 'Users have to be members of this blog' ),
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'never', T_('Not allowed'), '',
										'', 'onclick="show_hide_feedback_details(this);"'),
					), T_('Comment posting by'), true, $edited_Blog->get_advanced_perms_warning() );

	echo '<div class="feedback_details_container">';

	$Form->checkbox( 'allow_anon_url', $edited_Blog->get_setting( 'allow_anon_url' ), T_('Anonymous URLs'), T_('Allow anonymous commenters to submit an URL') );

	$Form->checkbox( 'allow_html_comment', $edited_Blog->get_setting( 'allow_html_comment' ),
						T_( 'Allow HTML' ), T_( 'Check to allow HTML in comments.' ).' ('.T_('HTML code will pass several sanitization filters.').')' );

	$any_option = array( 'any', T_('Any user'), T_('Including anonymous users'), '' );
	$registered_option = array( 'registered', T_('Registered users only'),  '', '' );
	$member_option = array( 'member', T_('Members only'), T_('Users have to be members of this blog'), '' );
	$never_option = array( 'never', T_('Not allowed'), '', '' );
	$Form->radio( 'allow_attachments', $edited_Blog->get_setting( 'allow_attachments' ),
						array(  $any_option, $registered_option, $member_option, $never_option,
						), T_('Allow attachments from'), true );

	$max_attachments_params = array();
	if( $edited_Blog->get_setting( 'allow_attachments' ) == 'any' )
	{	// Disable field "Max # of attachments" when Allow attachments from Any user
		$max_attachments_params['disabled'] = 'disabled';
	}
	$Form->text_input( 'max_attachments', $edited_Blog->get_setting( 'max_attachments' ), 10, T_('Max # of attachments per User per Post'), T_('(leave empty for no limit)'), $max_attachments_params );

	if( $perm_blog_admin || $edited_Blog->get( 'allowtrackbacks' ) )
	{ // Only admin can turn ON this setting
		$trackbacks_warning_attrs = ' id="trackbacks_warning" style="display:'.( $edited_Blog->get( 'allowtrackbacks' ) ? 'inline' : 'none' ).'"';
		$trackbacks_warning = str_replace( '$attrs$', $trackbacks_warning_attrs, $spammers_warning );
		$trackbacks_title = !$edited_Blog->get( 'allowtrackbacks' ) ? get_admin_badge() : '';
		$Form->checkbox( 'blog_allowtrackbacks', $edited_Blog->get( 'allowtrackbacks' ), T_('Trackbacks').$trackbacks_title, $trackbacks_warning.T_('Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.') );
	}

	$Form->checkbox( 'autocomplete_usernames', $edited_Blog->get_setting( 'autocomplete_usernames' ),
		T_( 'Autocomplete usernames in back-office' ), T_( 'Check to enable auto-completion of usernames entered after a "@" sign in the comment forms' ) );

	echo '</div>';

	if( $edited_Blog->get_setting( 'allow_comments' ) == 'never' )
	{ ?>
	<script type="text/javascript">
		<!--
		jQuery( '.feedback_details_container' ).hide();
		//-->
	</script>
	<?php
	}

$Form->end_fieldset();

$Form->begin_fieldset( T_('Voting options') . get_manual_link('comment-voting-options'), array( 'class' => 'feedback_details_container' ) );

	$Form->checkbox( 'display_rating_summary', $edited_Blog->get_setting( 'display_rating_summary' ), T_('Display summary'), T_('Display a summary of ratings above the comments') );

	$Form->radio( 'allow_rating_items', $edited_Blog->get_setting( 'allow_rating_items' ),
						array( $any_option, $registered_option, $member_option, $never_option,
						), T_('Allow star ratings from'), true );

	$Form->textarea_input( 'rating_question', $edited_Blog->get_setting( 'rating_question' ), 3, T_('Star rating question') );

	$Form->checkbox( 'allow_rating_comment_helpfulness', $edited_Blog->get_setting( 'allow_rating_comment_helpfulness' ), T_('Allow helpful/not helpful'), T_('Allow users to say if a comment was helpful or not.') );

$Form->end_fieldset();


// display comments settings provided by optional modules:
// echo 'modules';
modules_call_method( 'display_collection_comments', array( 'Form' => & $Form, 'edited_Blog' => & $edited_Blog ) );

$Form->begin_fieldset( T_('Comment moderation') . get_manual_link('comment-moderation') );

	// Get max allowed visibility status:
	$max_allowed_status = get_highest_publish_status( 'comment', $edited_Blog->ID, false );

	$is_bootstrap_skin = ( isset( $AdminUI, $AdminUI->skin_name ) && $AdminUI->skin_name == 'bootstrap' );
	$newstatus_warning_attrs = ' id="newstatus_warning" style="display:'.( $edited_Blog->get_setting('new_feedback_status') == 'published' ? 'inline' : 'none' ).'"';
	$newstatus_warning = str_replace( '$attrs$', $newstatus_warning_attrs, $spammers_warning );
	$status_options = get_visibility_statuses( '', array( 'redirected', 'trash' ) );
	if( $edited_Blog->get_setting('new_feedback_status') != 'published' )
	{
		if( $perm_blog_admin )
		{ // Only admin can set this setting to 'Public'
			$status_options['published'] .= $is_bootstrap_skin ? get_admin_badge( 'coll', false ) : ' ['.T_('Admin').']';
		}
		else
		{ // Remove published status for non-admin users
			unset( $status_options['published'] );
		}
	}
	// Set this flag to false in order to find first allowed status below:
	$status_is_allowed = false;
	foreach( $status_options as $status_key => $status_option )
	{
		if( $status_key == $max_allowed_status )
		{	// This is first allowed status, then all next statuses are also allowed:
			$status_is_allowed = true;
		}
		if( ! $status_is_allowed && $edited_Blog->get_setting( 'new_feedback_status' ) != $status_key )
		{	// Don't allow to select this status because it is not allowed by collection restriction:
			unset( $status_options[ $status_key ] );
		}
	}
	// put this on feedback details container, this way it won't be displayed if comment posting is not allowed
	echo '<div class="feedback_details_container">';

	if( $is_bootstrap_skin )
	{	// Use dropdown for bootstrap skin:
		$new_status_field = get_status_dropdown_button( array(
				'name'    => 'new_feedback_status',
				'value'   => $edited_Blog->get_setting('new_feedback_status'),
				'options' => $status_options,
			) );
		$Form->info( T_('Status for new Anonymous comments'), $new_status_field, $newstatus_warning.T_('Logged in users will get the highest possible status allowed by their permissions. Plugins may also override this default.') );
		$Form->hidden( 'new_feedback_status', $edited_Blog->get_setting('new_feedback_status') );
		echo_form_dropdown_js();
	}
	else
	{	// Use standard select element for other skins:
		$Form->select_input_array( 'new_feedback_status', $edited_Blog->get_setting('new_feedback_status'), $status_options,
				T_('Status for new Anonymous comments'), $newstatus_warning.T_('Logged in users will get the highest possible status allowed by their permissions. Plugins may also override this default.') );
	}
	echo '</div>';

	// Moderation statuses setting:
	$all_statuses = get_visibility_statuses( 'keys', NULL );
	$not_moderation_statuses = array_diff( $all_statuses, get_visibility_statuses( 'moderation' ) );
	// Get moderation statuses with status text:
	$moderation_statuses = get_visibility_statuses( '', $not_moderation_statuses );
	$moderation_status_icons = get_visibility_statuses( 'icons', $not_moderation_statuses );
	$blog_moderation_statuses = $edited_Blog->get_setting( 'moderation_statuses' );
	$checklist_options = array();
	// Set this flag to false in order to find first allowed status below:
	$status_is_hidden = true;
	foreach( $all_statuses as $status )
	{	// Add a checklist option for each possible moderation status:
		if( $status == $max_allowed_status )
		{	// This is first allowed status, then all next statuses are also allowed:
			$status_is_hidden = false;
		}
		if( ! isset( $moderation_statuses[ $status ] ) )
		{	// Don't display a checkbox for non moderation status:
			continue;
		}
		$checklist_options[] = array(
				'notif_'.$status, // Field name of checkbox
				1, // Field value
				$moderation_status_icons[ $status ].' '.$moderation_statuses[ $status ], // Text
				( strpos( $blog_moderation_statuses, $status ) !== false ), // Checked?
				'', // Disabled?
				'', // Note
				'', // Class
				$status_is_hidden, // Hidden field instead of checkbox?
			);
	}
	$Form->checklist( $checklist_options, 'moderation_statuses', T_('"Require moderation" statuses'), false, false, array( 'note' => T_('Comments with the selected statuses will be considered to require moderation. They will trigger "moderation required" notifications and will appear as such on the collection dashboard.') ) );

	$Form->radio( 'comment_quick_moderation', $edited_Blog->get_setting( 'comment_quick_moderation' ),
					array(  array( 'never', T_('Never') ),
							array( 'expire', T_('Links expire on first edit action') ),
							array( 'always', T_('Always available') )
						), T_('Comment quick moderation'), true );
$Form->end_fieldset();

$Form->begin_fieldset( T_('RSS/Atom feeds') . get_manual_link('comment-rss-atom-feeds') );
	$Form->radio( 'comment_feed_content', $edited_Blog->get_setting('comment_feed_content'),
								array(  array( 'none', T_('No feeds') ),
										array( 'excerpt', T_('Comment excerpts') ),
										array( 'normal', T_('Standard comment contents') ),
									), T_('Comment feed contents'), true, T_('How much content do you want to make available in comment feeds?') );

	$Form->text( 'comments_per_feed', $edited_Blog->get_setting('comments_per_feed'), 4, T_('Comments in feeds'),  T_('How many of the latest comments do you want to include in RSS & Atom feeds?'), 4 );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Subscriptions') . get_manual_link('comment-subscriptions') );
	$Form->checkbox( 'allow_comment_subscriptions', $edited_Blog->get_setting( 'allow_comment_subscriptions' ), T_('Email subscriptions'), T_('Allow users to subscribe and receive email notifications for each new comment.') );
	$Form->checkbox( 'allow_item_subscriptions', $edited_Blog->get_setting( 'allow_item_subscriptions' ), '', T_( 'Allow users to subscribe and receive email notifications for comments on a specific post.' ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Registration of commenters') . get_manual_link('comment-registration-of-commenters') );
	$Form->checkbox( 'comments_detect_email', $edited_Blog->get_setting( 'comments_detect_email' ), T_('Email addresses'), T_( 'Detect email addresses in comments.' ) );

	$Form->checkbox( 'comments_register', $edited_Blog->get_setting( 'comments_register' ), T_('Register after comment'), T_( 'Display the registration form right after submitting a comment.' ) );
$Form->end_fieldset();


$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>
<script type="text/javascript">
	var paged_comments_is_checked = jQuery( '#paged_comments' ).is( ':checked' );
	jQuery( '#threaded_comments' ).click( function()
	{ // Disable checkbox "Paged comments" if "Threaded comments" is ON
		if( jQuery( this ).is( ':checked' ) )
		{
			jQuery( '#paged_comments' ).attr( 'disabled', 'disabled' );
			paged_comments_is_checked = jQuery( '#paged_comments' ).is( ':checked' );
			jQuery( '#paged_comments' ).removeAttr( 'checked' );
			jQuery( '#comments_per_page' ).val( '1000' );
		}
		else
		{
			jQuery( '#paged_comments' ).removeAttr( 'disabled' );
			if( paged_comments_is_checked )
			{
				jQuery( '#paged_comments' ).attr( 'checked', 'checked' );
				jQuery( '#comments_per_page' ).val( '20' );
			}
		}
	} );

	jQuery( '#paged_comments' ).click( function()
	{
		if( jQuery( this ).is( ':checked' ) )
		{
			jQuery( '#comments_per_page' ).val( '20' );
		}
		else
		{
			jQuery( '#comments_per_page' ).val( '1000' );
		}
	} );

	jQuery( 'input[name=allow_attachments]' ).click( function()
	{	// Disable field "Max # of attachments" when Allow attachments from Any user
		if( jQuery( this ).val() == 'any' )
		{
			jQuery( '#max_attachments' ).attr( 'disabled', 'disabled' );
		}
		else
		{
			jQuery( '#max_attachments' ).removeAttr( 'disabled' );
		}
	} );

	jQuery( '#blog_allowtrackbacks' ).click( function()
	{ // Show/Hide warning for 'Trackbacks'
		if( jQuery( this ).is( ':checked' ) )
		{
			jQuery( '#trackbacks_warning' ).css( 'display', 'inline' );
		}
		else
		{
			jQuery( '#trackbacks_warning' ).hide();
		}
	} );

	jQuery( '#new_feedback_status' ).change( function()
	{ // Show/Hide warning for 'New feedback status'
		if( jQuery( this ).val() == 'published' )
		{
			jQuery( '#newstatus_warning' ).css( 'display', 'inline' );
		}
		else
		{
			jQuery( '#newstatus_warning' ).hide();
		}
	} );
</script>