<?php
/**
 * This file implements the UI view for those user preferences which are visible only for admin users.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of User class
 */
global $edited_User, $UserSettings, $Plugins;

global $current_User;

global $servertimenow;

if( !$current_User->check_perm( 'users', 'edit' ) )
{ // Check permission:
	debug_die( T_( 'You have no permission to see this tab!' ) );
}

// Begin payload block:
$this->disp_payload_begin();

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
		'user_tab'     => 'admin'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$user_status_icons = get_user_status_icons();

$Form = new Form( NULL, 'user_checkchanges' );

$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";

echo_user_actions( $Form, $edited_User, 'edit' );

$Form->begin_form( 'fform', get_usertab_header( $edited_User, 'admin', T_( 'User admin settings' ) ) );

$Form->add_crumb( 'user' );
$Form->hidden_ctrl();
$Form->hidden( 'user_tab', 'admin' );
$Form->hidden( 'admin_form', '1' );

$Form->hidden( 'user_ID', $edited_User->ID );
$Form->hidden( 'edited_user_login', $edited_User->login );

/***************  User permissions  **************/

$Form->begin_fieldset( T_('User permissions').get_manual_link('user_permissions'), array( 'class'=>'fieldset clear' ) );

$edited_User->get_Group();
$level_fieldnote = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://manual.b2evolution.net/User_levels"' );

if( $edited_User->ID == 1 )
{	// This is Admin user
	echo '<input type="hidden" name="edited_user_grp_ID" value="'.$edited_User->Group->ID.'" />';
	$Form->info( T_('Account status'), T_( 'Autoactivated' ) );
	$Form->info( T_('User group'), $edited_User->Group->dget('name') );

	$Form->info_field( T_('User level'), $edited_User->get('level'), array( 'note' => $level_fieldnote ) );
}
else
{
	$status_icon = '<div id="user_status_icon">'.$user_status_icons[ $edited_User->get( 'status' ) ].'</div>';
	$Form->select_input_array( 'edited_user_status', $edited_User->get( 'status' ), get_user_statuses(), T_( 'Account status' ), '', array( 'field_suffix' => $status_icon ) );
	$GroupCache = & get_GroupCache();
	$Form->select_object( 'edited_user_grp_ID', $edited_User->Group->ID, $GroupCache, T_('User group') );

	$Form->text_input( 'edited_user_level', $edited_User->get('level'), 2, T_('User level'), $level_fieldnote, array( 'required' => true ) );
}

$Form->end_fieldset(); // user permissions

$Form->begin_fieldset( T_('Email') );
	$email_fieldnote = '<a href="mailto:'.$edited_User->get( 'email' ).'" class="roundbutton">'.get_icon( 'email', 'imgtag', array('title'=>T_('Send an email')) ).'</a>';
	$Form->text_input( 'edited_user_email', $edited_User->get( 'email' ), 30, T_('Email'), $email_fieldnote, array( 'maxlength' => 100, 'required' => true ) );

	// Get status of email from T_email_blocked table
	load_class( 'tools/model/_emailblocked.class.php', 'EmailBlocked' );
	$EmailBlockedCache = & get_EmailBlockedCache();
	if( $EmailBlocked = & $EmailBlockedCache->get_by_name( $edited_User->get( 'email' ), false, false ) )
	{	// The email of this user is located in the T_email_blocked table
		$email_status = $EmailBlocked->get( 'status' );
	}
	else
	{	// There is no email address in the T_email_blocked table
		$email_status = 'unknown';
	}
	$email_status_icon = '<div id="email_status_icon">'.emblk_get_status_icon( $email_status ).'</div>';
	$Form->select_input_array( 'edited_email_status', $email_status, emblk_get_status_titles(), T_('Email status'), '', array( 'force_keys_as_values' => true, 'background_color' => emblk_get_status_colors(), 'field_suffix' => $email_status_icon ) );

	global $UserSettings;
	$Form->text_input( 'notification_sender_email', $UserSettings->get( 'notification_sender_email', $edited_User->ID ), 50, T_( 'Sender email address' ) );
	$Form->text_input( 'notification_sender_name', $UserSettings->get( 'notification_sender_name', $edited_User->ID ), 50, T_( 'Sender name' ) );

	// Last account activation email date ( reminders and requested activation emails are logged as well )
	$last_activation_email = $UserSettings->get( 'last_activation_email', $edited_User->ID );
	if( empty( $last_activation_email ) )
	{ // latest activation email date is not set, because user is already activated or email was not sent yet ( if email was not sent yet and the user is not activated and not closed probably there is some problem with the user email address )
		$last_activation_email = $edited_User->check_status( 'is_validated' ) ? T_('Account is already activated') : T_('None yet');
	}
	else
	{ // format last activation email date
		$last_activation_email = format_to_output( $last_activation_email );
	}
	$Form->info_field( T_('Latest account activation email'), $last_activation_email, array( 'note' => T_('Responsable schedule job for reminders is "Send reminders about not activated accounts".') ) );
	$last_unread_messages_reminder = $UserSettings->get( 'last_unread_messages_reminder', $edited_User->ID );
	$Form->info_field( T_('Latest unread messages reminder'), empty( $last_unread_messages_reminder ) ? T_('None yet') : format_to_output( $last_unread_messages_reminder ), array( 'note' => T_('Responsable schedule job is "Send reminders about unread messages".') ) );
	$last_notification_email = $UserSettings->get( 'last_notification_email', $edited_User->ID );
	$last_notificaiton_date = empty( $last_notification_email ) ? T_('None yet') : format_to_output( date2mysql( substr( $last_notification_email, 0, strpos( $last_notification_email, '_' ) ) ) );
	$Form->info_field( T_('Latest notification email'), $last_notificaiton_date, array( 'note' => T_('The latest between all kind of notification emails.') ) );
	$last_newsletter = $UserSettings->get( 'last_newsletter', $edited_User->ID );
	$last_newsletter_date = empty( $last_newsletter ) ? T_('None yet') : format_to_output( date2mysql( substr( $last_newsletter, 0, strpos( $last_newsletter, '_' ) ) ) );
	$Form->info_field( T_('Latest newsletter'), $last_newsletter_date );
$Form->end_fieldset(); // Email info

$Form->begin_fieldset( T_('Additional info') );

	$activity_tab_url = '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab=activity';

	$Form->info_field( T_('ID'), $edited_User->ID );

	// Other users reports from the edited User 
	$Form->info_field( T_('Reports'), count_reports_from( $edited_User->ID ) );

	// Number of blogs owned by the edited User
	$blogs_owned = $edited_User->get_num_blogs();
	if( $blogs_owned > 0 )
	{
		$blogs_owned .= ' - <a href="'.$activity_tab_url.'#owned_blogs_result" class="roundbutton middle" title="'.format_to_output( T_('View blogs...'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => T_('View blogs...') ) ).'</a>';
	}
	$Form->info_field( T_('Blogs owned'), $blogs_owned );

	// Number of post created by the edited User
	$posts_created = $edited_User->get_num_posts();
	if( $posts_created > 0 )
	{
		$posts_created .= ' - <a href="'.$activity_tab_url.'#created_posts_result" class="roundbutton middle" title="'.format_to_output( T_('View posts...'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => T_('View posts...') ) ).'</a>';
	}
	$Form->info_field( T_('Posts created'), $posts_created );

	// Number of other users post edited by the edited User
	$posts_edited = $edited_User->get_num_edited_posts();
	if( $posts_edited > 0 )
	{
		$posts_edited .= ' - <a href="'.$activity_tab_url.'#edited_posts_result" class="roundbutton middle" title="'.format_to_output( T_('View posts...'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => T_('View posts...') ) ).'</a>';
	}
	$Form->info_field( T_('Posts edited'), $posts_edited );

	// Number of comments created by the edited User
	flush(); // The following might take a while on systems with many comments
	$comments_created = $edited_User->get_num_comments();
	if( $comments_created > 0 )
	{
		$comments_created .= ' - <a href="'.$activity_tab_url.'#comments_result" class="roundbutton middle" title="'.format_to_output( T_('View comments...'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => T_('View comments...') ) ).'</a>';
	}
	$Form->info_field( T_('Comments'), $comments_created );

	// Number of edited User's sessions
	$Form->info_field( T_('# of sessions'), $edited_User->get_num_sessions( true ) );

	// Number of sent and received private messages
	$messages_sent = $edited_User->get_num_messages( 'sent' );
	if( $messages_sent > 0 )
	{
		$messages_sent .= ' - <a href="'.$activity_tab_url.'#threads_result" class="roundbutton middle" title="'.format_to_output( T_('View messages...'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => T_('View messages...') ) ).'</a>';
		if( $current_User->check_perm( 'perm_messaging', 'abuse' ) )
		{
			$messages_sent .= ' - <a href="?ctrl=abuse&amp;colselect_submit=Filter+list&amp;u='.$edited_User->login.'">'.T_('Go to abuse management').' &raquo;</a>';
		}
	}
	$messages_received = $edited_User->get_num_messages( 'received' );
	$Form->info_field( T_('# of private messages sent'), $messages_sent );
	$Form->info_field( T_('# of private messages received'), $messages_received );

	$edited_user_lastseen = $edited_User->get( 'lastseen_ts' );
	$Form->info_field( T_('Last seen on'), ( empty( $edited_user_lastseen ) ? '' : mysql2localedatetime( $edited_user_lastseen ) ) );
	$Form->info_field( T_('On IP'), $edited_User->get_last_session_param('ipaddress') );
$Form->end_fieldset();

$from_country = '';
if( !empty( $edited_User->reg_ctry_ID ) )
{	// Get country that was defined by GeoIP Plugin on registration
	load_class( 'regional/model/_country.class.php', 'Country' );
	load_funcs( 'regional/model/_regional.funcs.php' );
	$CountryCache = & get_CountryCache();
	$Country = $CountryCache->get_by_ID( $edited_User->reg_ctry_ID );
	$from_country = country_flag( $Country->get( 'code' ), $Country->get_name(), 'w16px', 'flag', '', false, true, 'margin-bottom:3px;vertical-align:middle;' ).' '.$Country->get_name();
}

// Get field suffix for a field 'From Country' from the Plugins
$user_from_country_suffix = '';
$Plugins->restart();
while( $loop_Plugin = & $Plugins->get_next() )
{
	$user_from_country_suffix .= $loop_Plugin->GetUserFromCountrySuffix( $tmp_params = array( 'User' => & $edited_User ) );
}

$Form->begin_fieldset( T_('Registration info') );
	$Form->info_field( T_('Account registered on'), $edited_User->dget('datecreated'), array( 'note' => '('.date_ago( strtotime( $edited_User->get( 'datecreated' ) ) ).')') );
	$Form->info_field( T_('From IP'), format_to_output( int2ip( $UserSettings->get( 'created_fromIPv4', $edited_User->ID ) ) ) );

	// Get status and name of IP range
	$IPRangeCache = & get_IPRangeCache();
	if( $IPRange = & $IPRangeCache->get_by_ip( int2ip( $UserSettings->get( 'created_fromIPv4', $edited_User->ID ) ) ) )
	{	// IP range exists in DB
		$iprange_status = $IPRange->get( 'status' );
		$iprange_name = $IPRange->get_name();
	}
	else
	{	// There is no IP range in DB
		$iprange_status = '';
		$iprange_name = '';
	}
	$Form->info_field( T_('IP range'), $iprange_name );
	$email_status_icon = '<div id="iprange_status_icon">'.aipr_status_icon( $iprange_status ).'</div>';
	$Form->select_input_array( 'edited_iprange_status', $iprange_status, aipr_status_titles( empty( $iprange_status ) ? true : false ), T_( 'IP range status' ), '', array( 'force_keys_as_values' => true, 'background_color' => aipr_status_colors(), 'field_suffix' => $email_status_icon ) );

	$Form->info_field( T_('From Country'), $from_country, array( 'field_suffix' => $user_from_country_suffix ) );
	$Form->info_field( T_('From Domain'), format_to_output( $UserSettings->get( 'user_domain', $edited_User->ID ) ) );
	$Form->info_field( T_('With Browser'), format_to_output( $UserSettings->get( 'user_browser', $edited_User->ID ) ) );

	$Form->text_input( 'edited_user_source', $edited_User->source, 30, T_('Source link/code'), '', array( 'maxlength' => 30 ) );

	$Form->info_field( T_('Registration trigger Page'), $UserSettings->get( 'registration_trigger_url', $edited_User->ID ) );

	$Form->info_field( T_('Initial Blog ID'), $UserSettings->get( 'initial_blog_ID', $edited_User->ID ) );
	$Form->info_field( T_('Initial URI'), $UserSettings->get( 'initial_URI', $edited_User->ID ) );
	$Form->info_field( T_('Initial referer'), $UserSettings->get( 'initial_referer', $edited_User->ID ) );

	//$registration_ts = strtotime( $edited_User->get( 'datecreated' ) );
	if( $edited_User->check_status( 'is_closed' ) )
	{
		$account_close_ts = $UserSettings->get( 'account_close_ts', $edited_User->ID );
		$account_close_date =  empty( $account_close_ts ) ? T_( 'Unknown date' ) : format_to_output( date2mysql( $account_close_ts ) );
		//$days_on_site = empty( $account_close_ts ) ? T_( 'Unknown' ) : ( round( ( $account_close_ts - $registration_ts ) / 86400/* 60*60*24 */) ); 
	}
	else
	{
		$account_close_date = 'n/a';
		//$days_on_site = ( round( ( $servertimenow - $registration_ts ) / 86400/* 60*60*24 */) ); 
	}

	$Form->info_field( T_('Account closed on'), $account_close_date );
	$textarea_params = array( 'cols' => 40, 'class' => 'large', 'maxlength' => 255, 'style' =>'resize: none' );
	if( $edited_User->ID == 1 )
	{
		$textarea_params['disabled'] = "disabled";
	}
	$Form->textarea_input( 'account_close_reason', $UserSettings->get( 'account_close_reason', $edited_User->ID ), 4, T_('Account close reason'), $textarea_params );
	//$Form->info_field( T_('Days on site'), $days_on_site );

$Form->end_fieldset(); // Registration info

$action_buttons = array(
		array( '', 'actionArray[update]', T_('Save !'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) );

$Form->buttons( $action_buttons );

$Form->end_form();

// End payload block:
$this->disp_payload_end();
?>
<script type="text/javascript">
var user_status_icons = new Array;
<?php
foreach( $user_status_icons as $status => $icon )
{	// Init js array with user status icons
?>
user_status_icons['<?php echo $status; ?>'] = '<?php echo $icon; ?>';
<?php } ?>

jQuery( '#edited_user_status' ).change( function()
{	// Change icon of the user status
	if( typeof user_status_icons[ jQuery( this ).val() ] != 'undefined' )
	{
		jQuery( '#user_status_icon' ).html( user_status_icons[ jQuery( this ).val() ] );
	}
	else
	{
		jQuery( '#user_status_icon' ).html( '' );
	}
} );

var email_status_icons = new Array;
<?php
$email_status_icons = emblk_get_status_icons();
foreach( $email_status_icons as $status => $icon )
{	// Init js array with email status icons
?>
email_status_icons['<?php echo $status; ?>'] = '<?php echo $icon; ?>';
<?php } ?>

jQuery( '#edited_email_status' ).change( function()
{	// Change icon of the email status
	if( typeof email_status_icons[ jQuery( this ).val() ] != 'undefined' )
	{
		jQuery( '#email_status_icon' ).html( email_status_icons[ jQuery( this ).val() ] );
	}
	else
	{
		jQuery( '#email_status_icon' ).html( '' );
	}
} );

var current_email = '<?php echo $edited_User->get( 'email' ); ?>';
jQuery( 'input#edited_user_email' ).keyup( function()
{	// Disable/Enable to select email status when email address is changed
	if( current_email != jQuery( this ).val() )
	{	// Disable
		if( jQuery( '#edited_email_status' ).html() != '' )
		{
			email_status_selected = jQuery( '#edited_email_status option:selected' ).val();
			email_status_options = jQuery( '#edited_email_status' ).html();
		}
		//alert(email_status_options);
		jQuery( '#edited_email_status' ).html( '' )
			.attr( 'disabled', 'disabled' );
		jQuery( '#email_status_icon' ).hide();
	}
	else
	{	// Enable
		jQuery( '#edited_email_status' ).removeAttr( 'disabled' )
			.html( email_status_options );
		jQuery( '#edited_email_status option[value=' + email_status_selected + ']' ).attr( 'selected', 'selected' );
		jQuery( '#email_status_icon' ).show();
	}
} );

var iprange_status_icons = new Array;
<?php
$iprange_status_icons = aipr_status_icons();
foreach( $iprange_status_icons as $status => $icon )
{	// Init js array with IP range status icons
?>
iprange_status_icons['<?php echo $status; ?>'] = '<?php echo $icon; ?>';
<?php } ?>

jQuery( '#edited_iprange_status' ).change( function()
{	// Change icon of the ip range status
	if( typeof iprange_status_icons[ jQuery( this ).val() ] != 'undefined' )
	{
		jQuery( '#iprange_status_icon' ).html( iprange_status_icons[ jQuery( this ).val() ] );
	}
	else
	{
		jQuery( '#iprange_status_icon' ).html( '' );
	}
} );
</script>
<?php

/*
 * $Log$
 * Revision 1.9  2013/11/06 08:05:04  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>