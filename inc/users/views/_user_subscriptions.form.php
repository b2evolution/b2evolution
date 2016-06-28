<?php
/**
 * This file implements the UI view for the user subscriptions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of UserSettings class
 */
global $UserSettings;
/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var current action
 */
global $action;
/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;
/**
 * @var Blog
 */
global $Blog;
/**
 * @var DB
 */
global $DB;

global $unread_messsage_reminder_threshold, $unread_message_reminder_delay;
global $activate_account_reminder_threshold, $comment_moderation_reminder_threshold, $post_moderation_reminder_threshold;

// Default params:
$default_params = array(
		'skin_form_params'     => array(),
		'form_class_user_subs' => 'bComment',
	);

if( isset( $params ) )
{	// Merge with default params
	$params = array_merge( $default_params, $params );
}
else
{	// Use a default params
	$params = $default_params;
}

// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'subs'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$Form = new Form( $form_action, 'user_checkchanges' );

$Form->switch_template_parts( $params['skin_form_params'] );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin_page = is_admin_page();
if( $is_admin_page )
{
	$form_text_title = T_( 'Edit notifications' ).get_manual_link( 'user-notifications-tab' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, 'subs', $form_text_title );
	$form_class = 'fform';
	$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";
	$checklist_params = array();
}
else
{
	$form_title = '';
	$form_class = $params['form_class_user_subs'];
	$checklist_params = array( 'wide' => true );
}

$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

	$Form->add_crumb( 'user' );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'subs' );
	$Form->hidden( 'subscriptions_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );
	$Form->hidden( 'edited_user_login', $edited_User->login );
	if( isset( $Blog ) )
	{
		$Form->hidden( 'blog', $Blog->ID );
	}

if( $action != 'view' )
{	// We can edit the values:
	$disabled = false;
}
else
{	// display only
	$disabled = true;
}

$has_messaging_perm = $edited_User->check_perm( 'perm_messaging', 'reply', false );

$Form->begin_fieldset( T_('Email').( is_admin_page() ? get_manual_link( 'user-notifications-tab' ) : '' ) );

	$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'" class="'.button_class().'">'.get_icon( 'email', 'imgtag', array('title'=>T_('Send an email')) ).'</a>';

	if( $action != 'view' )
	{ // We can edit the values:
		$Form->text_input( 'edited_user_email', $edited_User->email, 30, T_('Email address'), $email_fieldnote, array( 'maxlength' => 255, 'required' => true ) );
		$disabled = false;
	}
	else
	{ // display only
		$Form->info( T_('Email'), $edited_User->get('email'), $email_fieldnote );
		$disabled = true;
	}

	$Form->radio_input( 'edited_user_email_format', $UserSettings->get( 'email_format',  $edited_User->ID ), array(
				array(
					'value'   => 'auto',
					'label'   => T_('Automatic (HTML + Plain text)') ),
				array(
					'value'   => 'html',
					'label'   => T_('HTML') ),
				array(
					'value'   => 'text',
					'label'   => T_('Plain text') ),
			), T_('Email format'), array( 'lines' => true ) );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Communications') );

	$has_messaging_perm = $edited_User->check_perm( 'perm_messaging', 'reply', false );
	$messaging_options = array(	array( 'PM', 1, T_( 'private messages on this site.' ), ( ( $UserSettings->get( 'enable_PM', $edited_User->ID ) ) && ( $has_messaging_perm ) ), !$has_messaging_perm || $disabled ) );
	$emails_msgform = $Settings->get( 'emails_msgform' );

	$email_messaging_note = '';
	if( ! $UserSettings->get( 'enable_email', $edited_User->ID ) &&
			( $emails_msgform == 'userset' || $emails_msgform == 'adminset' ) )
	{ // Check if user has own blog and display a red note
		$user_own_blogs_count = $edited_User->get_own_blogs_count();
		if( $user_own_blogs_count > 0 )
		{
			$email_messaging_note = '<span class="red">'.sprintf( T_('You are the owner of %d collections. Visitors of these collections will <b>always</b> be able to contact you through a message form if needed (your email address will NOT be revealed).'),
				$user_own_blogs_count ).'</span>';
		}
	}

	$msgform_checklist_params = $checklist_params;
	if( $emails_msgform == 'userset' )
	{ // user can set
		$messaging_options[] = array( 'email', 2, T_( 'emails through a message form that will NOT reveal my email address.' ), $UserSettings->get( 'enable_email', $edited_User->ID ), $disabled, $email_messaging_note );
	}
	elseif( ( $emails_msgform == 'adminset' ) && ( $current_User->check_perm( 'users', 'edit' ) ) )
	{ // only administrator users can set and current User is in 'Administrators' group
		$messaging_options[] = array( 'email', 2, T_( 'emails through a message form that will NOT reveal my email address.' ).get_admin_badge( 'user' ), $UserSettings->get( 'enable_email', $edited_User->ID ), $disabled, $email_messaging_note );
	}
	elseif( ! empty( $email_messaging_note ) )
	{	// Display red message to inform user when he don't have a permission to edit the setting:
		$msgform_checklist_params['note'] = $email_messaging_note;
	}
	$Form->checklist( $messaging_options, 'edited_user_msgform', T_('Other users can send me'), false, false, $msgform_checklist_params );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Notifications') );

	// User notification options
	$notify_options = array();
	if( $has_messaging_perm )
	{ // show messaging notification settings only if messaging is available for edited user
		$notify_options[] = array( 'edited_user_notify_messages', 1, T_('I receive a private message.'),  $UserSettings->get( 'notify_messages', $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_unread_messages', 1, sprintf( T_('I have unread private messages for more than %s.'), seconds_to_period( $unread_messsage_reminder_threshold ) ),  $UserSettings->get( 'notify_unread_messages', $edited_User->ID ), $disabled, sprintf( T_('This notification is sent only once every %s days.'), array_shift( $unread_message_reminder_delay ) ) );
	}
	if( $edited_User->check_role( 'post_owner' ) )
	{ // user has at least one post or user has right to create new post
		$notify_options[] = array( 'edited_user_notify_publ_comments', 1, T_('a comment is published on one of <strong>my</strong> posts.'), $UserSettings->get( 'notify_published_comments', $edited_User->ID ), $disabled );
	}
	$is_comment_moderator = $edited_User->check_role( 'comment_moderator' );
	if( $is_comment_moderator || $edited_User->check_role( 'comment_editor' ) )
	{	// edited user has permission to edit other than his own comments at least in one status in one collection:
		$notify_options[] = array( 'edited_user_notify_cmt_moderation', 1, T_('a comment is posted and I have permissions to moderate it.'), $UserSettings->get( 'notify_comment_moderation', $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_edit_cmt_moderation', 1, T_('a comment is modified and I have permissions to moderate it.'), $UserSettings->get( 'notify_edit_cmt_moderation', $edited_User->ID ), $disabled );
	}
	if( $edited_User->check_perm( 'admin', 'restricted', false ) )
	{ // edited user has a permission to back-office
		$notify_options[] = array( 'edited_user_notify_meta_comments', 1, T_('a meta comment is posted.'), $UserSettings->get( 'notify_meta_comments', $edited_User->ID ), $disabled );
	}
	if( $is_comment_moderator )
	{ // edited user is comment moderator at least in one blog
		$notify_options[] = array( 'edited_user_send_cmt_moderation_reminder', 1, sprintf( T_('comments are awaiting moderation for more than %s.'), seconds_to_period( $comment_moderation_reminder_threshold ) ), $UserSettings->get( 'send_cmt_moderation_reminder', $edited_User->ID ), $disabled );
	}
	if( $edited_User->check_role( 'post_moderator' ) )
	{ // edited user is post moderator at least in one blog
		$notify_options[] = array( 'edited_user_notify_post_moderation', 1, T_('a post is created and I have permissions to moderate it.'), $UserSettings->get( 'notify_post_moderation', $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_edit_pst_moderation', 1, T_('a post is modified and I have permissions to moderate it.'), $UserSettings->get( 'notify_edit_pst_moderation', $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_send_pst_moderation_reminder', 1, sprintf( T_('posts are awaiting moderation for more than %s.'), seconds_to_period( $post_moderation_reminder_threshold ) ), $UserSettings->get( 'send_pst_moderation_reminder', $edited_User->ID ), $disabled );
	}
	if( $current_User->check_perm( 'users', 'edit' ) )
	{ // current User is an administrator
		$notify_options[] = array( 'edited_user_send_activation_reminder', 1, sprintf( T_('my account was deactivated or is not activated for more than %s.').get_admin_badge( 'user' ), seconds_to_period( $activate_account_reminder_threshold ) ), $UserSettings->get( 'send_activation_reminder', $edited_User->ID ) );
	}
	if( $edited_User->check_perm( 'users', 'edit' ) )
	{ // edited user has permission to edit all users, save notification preferences
		$notify_options[] = array( 'edited_user_notify_new_user_registration', 1, T_( 'a new user has registered.' ), $UserSettings->get( 'notify_new_user_registration', $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_activated_account', 1, T_( 'an account was activated.' ), $UserSettings->get( 'notify_activated_account', $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_closed_account', 1, T_( 'an account was closed.' ), $UserSettings->get( 'notify_closed_account', $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_reported_account', 1, T_( 'an account was reported.' ), $UserSettings->get( 'notify_reported_account', $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_changed_account', 1, T_( 'an account was changed.' ), $UserSettings->get( 'notify_changed_account', $edited_User->ID ), $disabled );
	}
	if( $edited_User->check_perm( 'options', 'edit' ) )
	{ // edited user has permission to edit options, save notification preferences
		$notify_options[] = array( 'edited_user_notify_cronjob_error', 1, T_( 'a scheduled task ends with an error or timeout.' ), $UserSettings->get( 'notify_cronjob_error',  $edited_User->ID ), $disabled );
	}
	if( !empty( $notify_options ) )
	{
		$Form->checklist( $notify_options, 'edited_user_notification', T_( 'Notify me by email whenever' ), false, false, $checklist_params );
	}

	// Limit notifications:
	if( $is_admin_page )
	{ // Back office view
		$Form->text_input( 'edited_user_notification_email_limit', $UserSettings->get( 'notification_email_limit',  $edited_User->ID ), 3, T_( 'Limit notifications to' ), '', array( 'maxlength' => 3, 'required' => true, 'input_suffix' => ' <b>'.T_('emails per day').'</b>' ) );
	}
	else
	{ // Front office view
		$Form->text_input( 'edited_user_notification_email_limit', $UserSettings->get( 'notification_email_limit',  $edited_User->ID ), 3, T_( 'Limit notifications to %s emails per day' ), '', array( 'maxlength' => 3, 'required' => true, 'inline' => true ) );
	}

$Form->end_fieldset();

$Form->begin_fieldset( T_('Newsletters') );

	$newsletter_options = array(
		array( 'edited_user_newsletter_news', 1, T_( 'Send me news about this site.' ).' <span class="note">'.T_('Each message contains an easy 1 click unsubscribe link.').'</span>', $UserSettings->get( 'newsletter_news',  $edited_User->ID ) ),
		array( 'edited_user_newsletter_ads', 1, T_( 'I want to receive ADs that may be relevant to my interests.' ), $UserSettings->get( 'newsletter_ads',  $edited_User->ID ) )
	);
	$Form->checklist( $newsletter_options, 'edited_user_newsletter', T_( 'Newsletter' ), false, false, $checklist_params );

	// Limit newsletters:
	if( $is_admin_page )
	{ // Back office view
		$Form->text_input( 'edited_user_newsletter_limit', $UserSettings->get( 'newsletter_limit',  $edited_User->ID ), 3, T_( 'Limit newsletters to' ), '', array( 'maxlength' => 3, 'required' => true, 'input_suffix' => ' <b>'.T_('emails per day').'</b>' ) );
	}
	else
	{ // Front office view
		$Form->text_input( 'edited_user_newsletter_limit', $UserSettings->get( 'newsletter_limit',  $edited_User->ID ), 3, T_( 'Limit newsletters to %s emails per day' ), '', array( 'maxlength' => 3, 'required' => true, 'inline' => true ) );
	}

$Form->end_fieldset();

$Form->begin_fieldset( T_('Blog subscriptions'), array( 'id' => 'subs' ) );

		// Get those blogs for which we have already subscriptions (for this user)
		$blog_subs_SQL = new SQL( 'Get those blogs for which we have already subscriptions for this user #'.$edited_User->ID );
		$blog_subs_SQL->SELECT( 'blog_ID, sub_items, sub_comments' );
		$blog_subs_SQL->FROM( 'T_blogs' );
		$blog_subs_SQL->FROM_add( 'INNER JOIN T_subscriptions ON blog_ID = sub_coll_ID AND sub_user_ID = '.$edited_User->ID );
		$blog_subs_SQL->WHERE( 'sub_items = 1' );
		$blog_subs_SQL->WHERE_or( 'sub_comments = 1' );
		$blog_subs = $DB->get_results( $blog_subs_SQL->get(), OBJECT, $blog_subs_SQL->title );

		$BlogCache = & get_BlogCache();
		$subs_blog_IDs = array();
		foreach( $blog_subs AS $blog_sub )
		{
			if( ! ( $sub_Blog = & $BlogCache->get_by_ID( $blog_sub->blog_ID, false, false ) ) )
			{	// Skip wrong collection:
				continue;
			}
			if( ! ( $sub_Blog->get_setting( 'allow_subscriptions' ) && $blog_sub->sub_items ) &&
			    ! ( $sub_Blog->get_setting( 'allow_comment_subscriptions' ) && $blog_sub->sub_comments ) )
			{	// Skip because the collection doesn't allow any subscription:
				continue;
			}

			$subs_blog_IDs[] = $sub_Blog->ID;
			$subscriptions = array();
			if( $sub_Blog->get_setting( 'allow_subscriptions' ) )
			{	// If subscription is allowed for new posts:
				$subscriptions[] = array( 'sub_items_'.$sub_Blog->ID, '1', T_('All posts'), $blog_sub->sub_items );
			}
			if( $sub_Blog->get_setting( 'allow_comment_subscriptions' ) )
			{	// If subscription is allowed for new comments:
				$subscriptions[] = array( 'sub_comments_'.$sub_Blog->ID, '1', T_('All comments'), $blog_sub->sub_comments );
			}
			$Form->checklist( $subscriptions, 'subscriptions', $sub_Blog->dget( 'shortname', 'htmlbody' ) );
		}

		$Form->hidden( 'subs_blog_IDs', implode( ',', $subs_blog_IDs ) );

if( $is_admin_page && $Settings->get( 'subscribe_new_blogs' ) == 'page' )
{	// To subscribe from blog page only
	$Form->info_field( '', T_('In order to subscribe to a new blog, go to the relevant blog and subscribe from there.'), array( 'class' => 'info_full' ) );
}
else
{	// To subscribe from current list of blogs

	// Load collections which have the enabled settings to subscribe on new posts or comments:
	$BlogCache = new BlogCache();
	$BlogCache->load_subscription_colls( $edited_User, $subs_blog_IDs );

	if( empty( $BlogCache->cache ) )
	{	// No blogs to subscribe
		if( empty( $subs_blog_IDs ) )
		{	// Display this info if really no blogs to subscribe
			$Form->info_field( '', T_('Sorry, no blogs available to subscribe.'), array( 'class' => 'info_full' ) );
		}
	}
	else
	{ // Display a form to subscribe on new blog
		$Form->info_field( '', T_('Choose additional subscriptions').':', array( 'class' => 'info_full' ) );
		$label_blogs_prefix = $label_blogs_suffix = '';

		$Form->switch_layout( 'none' );
		$Form->output = false;

		$subscribe_blog_ID = param( 'subscribe_blog' , '', isset( $Blog ) ? $Blog->ID : 0 );
		$subscribe_blogs_select = $Form->select_input_object( 'subscribe_blog', $subscribe_blog_ID, $BlogCache, '', array( 'object_callback' => 'get_option_list_parent' ) ).'</span>';
		$subscribe_blogs_button = $Form->button( array(
			'name'  => 'actionArray[subscribe]',
			'value' => T_('Subscribe'),
			'style' => 'float:left;margin-left:20px;'
		) );

		$Form->switch_layout( NULL );
		$Form->switch_template_parts( $params['skin_form_params'] );
		$Form->output = true;

		// Get collection to set proper active checkboxes on page loading:
		if( isset( $BlogCache->cache[ $subscribe_blog_ID ] ) )
		{	// Get selected collection:
			$selected_subscribe_Blog = $BlogCache->cache[ $subscribe_blog_ID ];
		}
		else
		{	// Get first collection from list:
			foreach( $BlogCache->cache as $selected_subscribe_Blog )
			{
				break;
			}
		}

		foreach( $BlogCache->cache as $subscribe_Blog )
		{	// These hidden fields are used only by JS to know what subscription settings are enabled for each collection:
			$enabled_subs_settings = $subscribe_Blog->get_setting( 'allow_subscriptions' ) ? 'p' : '';
			$enabled_subs_settings .= $subscribe_Blog->get_setting( 'allow_comment_subscriptions' ) ? 'c' : '';
			$Form->hidden( 'coll_subs_settings_'.$subscribe_Blog->ID, $enabled_subs_settings );
		}

		$subscriptions = array(
				array( 'sub_items_new',    '1', T_('All posts'),    0, $selected_subscribe_Blog->get_setting( 'allow_subscriptions' ) ? 0 : 1 ),
				array( 'sub_comments_new', '1', T_('All comments'), 0, $selected_subscribe_Blog->get_setting( 'allow_comment_subscriptions' ) ? 0 : 1 )
			);
		$label_suffix = $Form->label_suffix;
		$Form->label_suffix = '';
		$Form->checklist( $subscriptions, 'subscribe_blog', trim( $subscribe_blogs_select ), false, false, array(
			'field_suffix' => $subscribe_blogs_button,
			'input_prefix' => '<div class="floatleft">',
			'input_suffix' => '</div>' ) );
		$Form->label_suffix = $label_suffix;
	}
}
$Form->end_fieldset();

$Form->begin_fieldset( T_('Individual post subscriptions') );

	$sql = 'SELECT DISTINCT post_ID, blog_ID, blog_shortname
				FROM T_items__subscriptions
					INNER JOIN T_items__item ON isub_item_ID = post_ID
					INNER JOIN T_categories ON post_main_cat_ID = cat_ID
					INNER JOIN T_blogs ON cat_blog_ID = blog_ID
				WHERE isub_user_ID = '.$edited_User->ID.' AND isub_comments <> 0
				ORDER BY blog_ID, post_ID ASC';
	$individual_posts_subs = $DB->get_results( $sql );
	$subs_item_IDs = array();
	if( empty( $individual_posts_subs ) )
	{
		$Form->info_field( '', T_( 'You are not subscribed to any updates on specific posts yet.' ), array( 'class' => 'info_full' ) );
	}
	else
	{
		global $admin_url;
		$ItemCache = & get_ItemCache();

		$Form->info_field( '', T_( 'You are subscribed to be notified of all new comments on the following posts' ).':', array( 'class' => 'info_full' ) );
		$blog_name = NULL;
		foreach( $individual_posts_subs as $row )
		{
			if( ! ( $Item = $ItemCache->get_by_ID( $row->post_ID, false, false ) ) )
			{ // Item doesn't exist anymore
				continue;
			}
			$subs_item_IDs[] = $row->post_ID;
			if( $blog_name != $row->blog_shortname )
			{
				if( !empty( $blog_name ) )
				{
					$Form->checklist( $post_subs, 'item_subscriptions', $blog_name );
				}
				$blog_name = $row->blog_shortname;
				$post_subs = array();
			}
			if( is_admin_page() && $current_User->check_perm( 'item_post!CURSTATUS', 'view', false, $Item ) )
			{ // Link title to back-office if user has a permission
				$item_title = '<a href="'.$admin_url.'?ctrl=items&amp;blog='.$row->blog_ID.'&amp;p='.$Item->ID.'">'.format_to_output( $Item->title ).'</a>';
			}
			else
			{ // Link title to front-office
				$item_title = $Item->get_permanent_link( '#title#' );
			}
			$post_subs[] = array( 'item_sub_'.$row->post_ID, 1, $item_title, 1 );
		}
		// display individual post subscriptions from the last Blog
		$Form->checklist( $post_subs, 'item_subscriptions', $blog_name );
	}
	$Form->hidden( 'subs_item_IDs', implode( ',', $subs_item_IDs ) );
	$Form->info_field( '', T_( 'To subscribe to notifications on a specifc post, go to that post and click "Notify me when someone comments" at the end of the comment list.' ), array( 'class' => 'info_full' ) );

$Form->end_fieldset();

	/***************  Buttons  **************/

if( $action != 'view' )
{	// Edit buttons
	$Form->buttons( array( array( '', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}

$Form->end_form();

?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( 'select#subscribe_blog' ).change( function()
	{	// Enable/Disable collection additional subscription checkboxes depending on settings:
		var coll_ID = jQuery( this ).val();
		var coll_settings = jQuery( 'input[name=coll_subs_settings_' + coll_ID + ']' ).val();
		if( coll_settings == 'pc' || coll_settings == 'p' )
		{	// Enable if subscription is allowed for new posts:
			jQuery( 'input[name=sub_items_new]' ).removeAttr( 'disabled' );
		}
		else
		{	// Disable otherwise:
			jQuery( 'input[name=sub_items_new]' ).attr( 'disabled', 'disabled' );
		}
		if( coll_settings == 'pc' || coll_settings == 'c' )
		{ // Enable if subscription is allowed for new comments:
			jQuery( 'input[name=sub_comments_new]' ).removeAttr( 'disabled' );
		}
		else
		{	// Disable otherwise:
			jQuery( 'input[name=sub_comments_new]' ).attr( 'disabled', 'disabled' );
		}
	} );
} );
</script>