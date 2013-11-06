<?php
/**
 * This file implements the UI view for the user subscriptions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
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

// Default params:
$default_params = array(
		'skin_form_params' => array(),
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
		'block_start'  => '<table class="prevnext_user"><tr>',
		'prev_start'   => '<td width="33%">',
		'prev_end'     => '</td>',
		'prev_no_user' => '<td width="33%">&nbsp;</td>',
		'back_start'   => '<td width="33%" class="back_users_list">',
		'back_end'     => '</td>',
		'next_start'   => '<td width="33%" class="right">',
		'next_end'     => '</td>',
		'next_no_user' => '<td width="33%">&nbsp;</td>',
		'block_end'    => '</tr></table>',
		'user_tab'     => 'subs'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$Form = new Form( $form_action, 'user_checkchanges' );

$Form->switch_template_parts( $params['skin_form_params'] );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	$form_title = get_usertab_header( $edited_User, 'subs', T_( 'Edit notifications' ) );
	$form_class = 'fform';
	$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";
}
else
{
	$form_title = '';
	$form_class = 'bComment';
}

$Form->begin_form( $form_class, $form_title );

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

$Form->begin_fieldset( T_('Email notifications') );

	// User notification options
	$notify_options = array();
	if( $has_messaging_perm )
	{ // show messaging notification settings only if messaging is available for edited user
		$notify_options[] = array( 'edited_user_notify_messages', 1, T_( 'I receive a private message.' ),  $UserSettings->get( 'notify_messages', $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_unread_messages', 1, T_( 'I have unread private messages for more than 24 hours.' ),  $UserSettings->get( 'notify_unread_messages', $edited_User->ID ), $disabled, T_( 'This notification is sent only once every 3 days.' ) );
	}
	if( $edited_User->check_role( 'post_owner' ) )
	{ // user has at least one post or user has right to create new post
		$notify_options[] = array( 'edited_user_notify_publ_comments', 1, T_( 'a comment is published on one of <strong>my</strong> posts.' ), $UserSettings->get( 'notify_published_comments', $edited_User->ID ), $disabled );
	}
	if( $edited_User->check_role( 'moderator' ) )
	{ // user is moderator at least in one blog
		$notify_options[] = array( 'edited_user_notify_moderation', 1, T_( 'a comment is posted in a blog where I am a moderator.' ), $UserSettings->get( 'notify_comment_moderation', $edited_User->ID ), $disabled );
	}
	if( $edited_User->group_ID == 1 )
	{ // current User is an administrator
		$notify_options[] = array( 'edited_user_send_activation_reminder', 1, T_( 'my account was deactivated or is not activated more than 24 hours. [Admin]' ), $UserSettings->get( 'send_activation_reminder', $edited_User->ID ) );
	}
	if( $edited_User->check_perm( 'users', 'edit' ) )
	{ // edited user has permission to edit all users, save notification preferences
		$notify_options[] = array( 'edited_user_notify_new_user_registration', 1, T_( 'a new user has registered.' ), $UserSettings->get( 'notify_new_user_registration', $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_activated_account', 1, T_( 'an account was activated.' ), $UserSettings->get( 'notify_activated_account',  $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_closed_account', 1, T_( 'an account was closed.' ), $UserSettings->get( 'notify_closed_account',  $edited_User->ID ), $disabled );
		$notify_options[] = array( 'edited_user_notify_reported_account', 1, T_( 'an account was reported.' ), $UserSettings->get( 'notify_reported_account',  $edited_User->ID ), $disabled );
	}
	if( $edited_User->check_perm( 'options', 'edit' ) )
	{ // edited user has permission to edit options, save notification preferences
		$notify_options[] = array( 'edited_user_notify_cronjob_error', 1, T_( 'a scheduled task ends with an error or timeout.' ), $UserSettings->get( 'notify_cronjob_error',  $edited_User->ID ), $disabled );
	}
	if( !empty( $notify_options ) )
	{
		$Form->checklist( $notify_options, 'edited_user_notification', T_( 'Notify me by email whenever' ) );
	}

	$newsletter_options = array(
		array( 'edited_user_newsletter_news', 1, T_( 'Send me news about this site.' ).' <span class="note">'.T_('Each message contains an easy 1 click unsubscribe link.').'</span>', $UserSettings->get( 'newsletter_news',  $edited_User->ID ) ),
		array( 'edited_user_newsletter_ads', 1, T_( 'I want to receive ADs that may be relevant to my interests.' ), $UserSettings->get( 'newsletter_ads',  $edited_User->ID ) )
	);
	$Form->checklist( $newsletter_options, 'edited_user_newsletter', T_( 'Newsletter' ) );

	$Form->text_input( 'edited_user_notification_email_limit', $UserSettings->get( 'notification_email_limit',  $edited_User->ID ), 3, T_( 'Limit notification emails to' ), T_( 'emails per day' ), array( 'maxlength' => 3, 'required' => true ) );
	$Form->text_input( 'edited_user_newsletter_limit', $UserSettings->get( 'newsletter_limit',  $edited_User->ID ), 3, T_( 'Limit newsletters to' ), T_( 'emails per day' ), array( 'maxlength' => 3, 'required' => true ) );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Blog subscriptions'), array( 'id' => 'subs' ) );

		// Get those blogs for which we have already subscriptions (for this user)
		$sql = 'SELECT blog_ID, blog_shortname, sub_items, sub_comments
		          FROM T_blogs INNER JOIN T_subscriptions ON ( blog_ID = sub_coll_ID AND sub_user_ID = '.$edited_User->ID.' )
		                       INNER JOIN T_coll_settings ON ( blog_ID = cset_coll_ID AND cset_name = "allow_subscriptions" AND cset_value = "1" )';
		$blog_subs = $DB->get_results( $sql );

		$encountered_current_blog = false;
		$subs_blog_IDs = array();
		foreach( $blog_subs AS $blog_sub )
		{
			if( isset( $Blog ) && $blog_sub->blog_ID == $Blog->ID )
			{
				$encountered_current_blog = true;
			}

			$subs_blog_IDs[] = $blog_sub->blog_ID;
			$subscriptions = array(
					array( 'sub_items_'.$blog_sub->blog_ID,    '1', T_('All posts'),    $blog_sub->sub_items ),
					array( 'sub_comments_'.$blog_sub->blog_ID, '1', T_('All comments'), $blog_sub->sub_comments )
				);
			$Form->checklist( $subscriptions, 'subscriptions', format_to_output( $blog_sub->blog_shortname, 'htmlbody' ) );
		}

		$Form->hidden( 'subs_blog_IDs', implode( ',', $subs_blog_IDs ) );

if( is_admin_page() && $Settings->get( 'subscribe_new_blogs' ) == 'page' )
{	// To subscribe from blog page only
	$Form->info_field( '', T_('In order to subscribe to a new blog, go to the relevant blog and subscribe from there.'), array( 'class' => 'info_full' ) );
}
else
{	// To subscribe from current list of blogs

	// Init $BlogCache object to display a select list to subscribe
	$BlogCache = new BlogCache();
	$BlogCache_SQL = $BlogCache->get_SQL_object();

	load_class( 'collections/model/_collsettings.class.php', 'CollectionSettings' );
	$CollectionSettings = new CollectionSettings();
	if( $CollectionSettings->get_default( 'allow_subscriptions' ) == 0 )
	{	// If default setting disables to subscribe on blogs, we should get only the blogs which allow the subsriptions
		$BlogCache_SQL->FROM_add( 'LEFT JOIN T_coll_settings ON cset_coll_ID = blog_ID' );
		$BlogCache_SQL->WHERE_and( 'cset_name = \'allow_subscriptions\'' );
		$BlogCache_SQL->WHERE_and( 'cset_value = 1' );
	}
	else// 'allow_subscriptions' == 1
	{	// If default setting enables to subscribe on blogs, we should exclude the blogs which don't allow the subsriptions
		$blogs_settings_SQL = new SQL( 'Get blogs which don\'t allow the subscriptions' );
		$blogs_settings_SQL->SELECT( 'cset_coll_ID' );
		$blogs_settings_SQL->FROM( 'T_coll_settings' );
		$blogs_settings_SQL->WHERE( 'cset_name = \'allow_subscriptions\'' );
		$blogs_settings_SQL->WHERE_and( 'cset_value = 0' );
		$blogs_disabled_subscriptions_IDs = $DB->get_col( $blogs_settings_SQL->get() );
		// Exclude the blogs which don't allow the subscriptions
		$BlogCache_SQL->WHERE_and( 'blog_ID NOT IN ('.$DB->quote( $blogs_disabled_subscriptions_IDs ).')' );
	}
	if( $Settings->get( 'subscribe_new_blogs' ) == 'public' )
	{	// If a subscribing to new blogs available only for the public blogs
		$BlogCache_SQL->WHERE_and( 'blog_in_bloglist = 1' );
	}
	if( !empty( $subs_blog_IDs ) )
	{	// Exclude the blogs from the list if user already is subscribed on them
		$BlogCache_SQL->WHERE_and( 'blog_ID NOT IN ('.$DB->quote( $subs_blog_IDs ).')' );
	}
	$BlogCache->load_by_sql( $BlogCache_SQL );
	if( empty( $BlogCache->cache ) )
	{	// No blogs to subscribe
		if( empty( $subs_blog_IDs ) )
		{	// Display this info if really no blogs to subscribe
			$Form->info_field( '', T_('Sorry, no blogs available to subscribe.'), array( 'class' => 'info_full' ) );
		}
	}
	else
	{	// Display a form to subscribe on new blog
		$Form->info_field( '', T_('Choose additional subscriptions:'), array( 'class' => 'info_full' ) );

		$Form->switch_layout( 'none' );
		$Form->output = false;

		$subscribe_blogs_select = $Form->select_input_object( 'subscribe_blog', param( 'subscribe_blog' , '', isset( $Blog ) ? $Blog->ID : 0 ), $BlogCache, '', array( 'object_callback' => 'get_option_list_parent' ) );
		$subscribe_blogs_button = $Form->button( array(
			'name'  => 'actionArray[subscribe]',
			'value' => T_('Subscribe'),
			'style' => 'float:left;margin-left:20px;'
		) );

		$Form->switch_layout( NULL );
		$Form->switch_template_parts( $params['skin_form_params'] );
		$Form->output = true;

		$subscriptions = array(
				array( 'sub_items_new',    '1', T_('All posts'),    0 ),
				array( 'sub_comments_new', '1', T_('All comments'), 0 )
			);
		$Form->checklist( $subscriptions, 'subscribe_blog', trim( $subscribe_blogs_select ), false, false, array(
			'field_suffix' => $subscribe_blogs_button,
			'input_prefix' => '<div class="floatleft">',
			'input_suffix' => '</div>' ) );
	}
}
$Form->end_fieldset();

$Form->begin_fieldset( T_('Individual post subscriptions') );

	$sql = 'SELECT DISTINCT post_ID, post_title, blog_ID, blog_shortname
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
		$Form->info_field( '', T_( 'You are subscribed to be notified on all updates on the following posts' ), array( 'class' => 'info_full' ) );
		$blog_name = NULL;
		foreach( $individual_posts_subs as $row )
		{
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
			$post_subs[] = array( 'item_sub_'.$row->post_ID, 1, $row->post_title, 1 );
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
	$Form->buttons( array(
		array( '', 'actionArray[update]', T_('Save !'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

$Form->end_form();


/*
 * $Log$
 * Revision 1.2  2013/11/06 08:05:04  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>