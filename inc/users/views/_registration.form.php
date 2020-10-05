<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var GeneralSettings
 */
global $Settings;

global $collections_Module, $Plugins;

global $baseurl, $admin_url, $Blog;

$Form = new Form( NULL, 'settings_checkchanges' );
$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

	$Form->add_crumb( 'registration' );
	$Form->hidden( 'ctrl', 'registration' );
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'tab', 'registration' );

// --------------------------------------------

$Form->begin_fieldset( TB_('Login & Registration Security').get_manual_link('registration-security-settings'), array( 'id' => 'security_options' ) );

	$Form->checkbox( 'require_ssl', (bool)$Settings->get( 'require_ssl' ), TB_('Require SSL'), TB_('Force all login, registration, password recovery & password change forms to use <code>https</code>, even if they would normally use <code>http</code>.') );

	$plugins_note = '';
	$plugin_params = $Plugins->trigger_event_first_true( 'LoginAttemptNeedsRawPassword' );
	if( ! empty( $plugin_params ) )
	{
		$Plugin = & $Plugins->get_by_ID( $plugin_params['plugin_ID'] );
		$plugins_note = '<div class="red">'.sprintf( TB_('WARNING: Plugin "%s" cannot use password hashing and will automatically disable this option during Login.'), $Plugin->name ).'</div>';
	}
	$Form->checkbox_input( 'js_passwd_hashing', (bool)$Settings->get('js_passwd_hashing'), TB_('Password hashing during Login'), array( 'note' => TB_('Check to enable the login form to hash the password with Javascript before transmitting it. This provides extra security on non-SSL connections.').$plugins_note ) );

	$Form->checklist( array(
			array( 'http_auth_require', 1, TB_('Check this to require HTTP basic authentication on any login page.'), $Settings->get( 'http_auth_require' ) ),
			array( 'http_auth_accept', 1, TB_('Check this to accept HTTP authentication headers (with any request when user is not already logged in).'), $Settings->get( 'http_auth_accept' ), $Settings->get( 'http_auth_require' ) ),
		), 'http_auth', TB_('HTTP Authentication') );

	$Form->text_input( 'user_minpwdlen', (int)$Settings->get('user_minpwdlen'), 2, TB_('Minimum password length'), TB_('characters.'), array( 'maxlength'=>2, 'required'=>true ) );

	$Form->checkbox_input( 'passwd_special', (bool)$Settings->get('passwd_special'), TB_('Require specials characters'), array( 'note'=>TB_('Check to require at least 1 special character (not a letter nor a digit).')) );

	$Form->checkbox_input( 'strict_logins', (bool)$Settings->get('strict_logins'), TB_('Require strict logins'), array( 'note'=>sprintf( TB_('Check to require only plain ACSII characters in user logins. Uncheck to allow any characters and symbols. The following characters are never allowed for security reasons: %s'), '\', ", >, <, @, &') ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Standard registration').get_manual_link('default-user-permissions-settings') );

	$Form->radio( 'newusers_canregister', $Settings->get( 'newusers_canregister' ), array(
					array( 'no', TB_( 'No (Only admins can create new users)' ) ),
					array( 'invite', TB_( 'Users can register only with an Invitation code/link' ) ),
					array( 'yes', TB_( 'Users can register themselves freely' ) )
				), TB_( 'New users can register' ), true );

	$disabled_param_links = array();
	if( $Settings->get( 'newusers_canregister' ) == 'no' )
	{ // Disable the field below when registration is not allowed
		$disabled_param_links['disabled'] = 'disabled';
	}
	$Form->checkbox_input( 'registration_is_public', $Settings->get( 'registration_is_public' ), TB_('Registration links'), array_merge( array( 'note' => TB_('Check to show self-registration links to the public.' ) ), $disabled_param_links ) );

	$disabled_param_grouplevel = array();
	if( $Settings->get( 'newusers_canregister' ) != 'yes' )
	{ // Disable group and level fields below when registration is not allowed freely
		$disabled_param_grouplevel['disabled'] = 'disabled';
	}

	$context = 'registration_master';
	$TemplateCache = & get_TemplateCache();
	$TemplateCache->load_by_context( $context );

	$template_input_suffix = ( check_user_perm( 'options', 'edit' ) ? '&nbsp;'
		.action_icon( '', 'edit', $admin_url.'?ctrl=templates&amp;context='.$context.( isset( $Blog ) ? '&amp;blog='.$Blog->ID : '' ), NULL, NULL, NULL, array( 'onclick' => 'return b2template_list_highlight( this )' ), array( 'title' => TB_('Manage templates').'...' ) ) : '' );
	$Form->select_input_array( 'registration_master_template', $Settings->get( 'registration_master_template' ), $TemplateCache->get_code_option_array(), TB_('Registration master template'), NULL, array( 'input_suffix' => $template_input_suffix ) );

$Form->end_fieldset();


// --------------------------------------------

$Form->begin_fieldset( TB_('Quick registration / Email capture').get_manual_link('quick-registration-settings') );

	$Form->checkbox_input( 'quick_registration', $Settings->get( 'quick_registration' ), TB_('Quick registration'), array_merge( array( 'note' => TB_('Check to allow registering with email only (no username, no password) using the quick registration widget.' ) ), $disabled_param_grouplevel ) );

	$Form->radio( 'registration_after_quick', $Settings->get( 'registration_after_quick' ), array(
					array( 'regform', TB_('Display additional registration screen as normal registration') ),
					array( 'continue', TB_('Continue directly to next page (note: user will have no password)') ),
				), TB_('After quick registration'), true );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Other registration methods').get_manual_link('plugin-registration-settings') );

	$Form->info( TB_('Plugins'), TB_('Other registration methods may be provided by plugins. See plugin settings.') );

// TODO: Add social login / LDAP settings links here

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Options for all registration methods').get_manual_link('other-registration-settings') );

	$Form->radio( 'registration_no_username', $Settings->get( 'registration_no_username'), array(
					array( 'firstname', sprintf( TB_('Use %s'), '<code>firstname</code>').' ('.TB_('default').')' ),
					array( 'firstname.lastname', sprintf( TB_('Use %s'), '<code>firstname.lastname</code>') )
				), TB_('If no username provided'), true );

	if( $Settings->get( 'after_registration' ) == 'return_to_original' || $Settings->get( 'after_registration' ) == 'specific_slug' )
	{ // return to original url
		$after_registration = $Settings->get( 'after_registration' );
		$after_registration_url = url_add_param( $baseurl, 'disp=profile' );
	}
	else
	{ // set specific URL
		$after_registration = 'specific_url';
		$after_registration_url = $Settings->get( 'after_registration' );
	}
	$Form->radio( 'after_registration', $after_registration, array(
					array( 'return_to_original', TB_( 'Return to original page' ) ),
					array( 'specific_url', TB_( 'Go to specific URL' ).':', '',
						'<input type="text" id="specific_after_registration_url" class="form_text_input form-control" name="specific_after_registration_url" size="50" maxlength="120" value="'
						.format_to_output( $after_registration_url, 'formvalue' ).'"
						onfocus="document.getElementsByName(\'after_registration\')[1].checked=true;" />' ),
					array( 'specific_slug', TB_( 'Go to specific Item slug' ).':', '',
						'<input type="text" id="specific_after_registration_slug" class="form_text_input form-control" name="specific_after_registration_slug" size="25" maxlength="120" value="'
						.format_to_output( $Settings->get( 'after_registration_slug' ), 'formvalue' ).'"
						onfocus="document.getElementsByName(\'after_registration\')[2].checked=true;" />' )
				), TB_( 'After registration' ), true );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Account activation (after registration)').get_manual_link('account-activation-settings'), array( 'id' => 'account_activation' ) );

	$Form->checkbox( 'newusers_mustvalidate', $Settings->get('newusers_mustvalidate'), TB_('New users must activate by email'), TB_('Check to require users to activate their account by clicking a link sent to them via email.' ) );

	$Form->checkbox( 'newusers_revalidate_emailchg', $Settings->get('newusers_revalidate_emailchg'), TB_('Reactivate after email change'), TB_('Check to require users to re-activate their account when they change their email address.' ) );

	$Form->radio( 'validation_process', $Settings->get( 'validation_process' ), array(
					array( 'secure', TB_( 'Secure account activation process' ), TB_( 'Users must validate their account in the same session. Prevents activation of an account by someone else if an incorrect email address is entered. No reminder emails can be sent.' ) ),
					array( 'easy', TB_( 'Easy account activation process' ), TB_( 'Allows to send reminder emails to unregistered accounts.' ) )
				), TB_( 'Activation process' ), true );

	$Form->duration_input( 'activate_requests_limit', $Settings->get( 'activate_requests_limit' ), TB_('Limit activation email requests to'), 'minutes', 'minutes', array( 'minutes_step' => 5, 'required' => true, 'note' => TB_('Only one activation email can be sent to the same email address in every given minutes.') ) );

	$Form->checkbox( 'newusers_findcomments', $Settings->get('newusers_findcomments'), TB_('Find old comments'), TB_('After each activation, find comments left by the user based on the validated email address and attach them to the user account.' ) );

	if( $Settings->get( 'after_email_validation' ) == 'return_to_original' )
	{ // return to original url
		$after_email_validation = 'return_to_original';
		$after_validation_url = $baseurl;
	}
	else
	{ // set specific URL
		$after_email_validation = 'specific_url';
		$after_validation_url = $Settings->get( 'after_email_validation' );
	}
	$Form->radio( 'after_email_validation', $after_email_validation, array(
					array( 'return_to_original', TB_( 'Return to original page' ) ),
					array( 'specific_url', TB_( 'Go to specific URL' ).':', '',
						'<input type="text" id="specific_after_validation_url" class="form_text_input form-control" name="specific_after_validation_url" size="50" maxlength="120" value="'
						.format_to_output( $after_validation_url, 'formvalue' ).'"
						onfocus="document.getElementsByName(\'after_email_validation\')[1].checked=true;" />' )
				), TB_( 'After email activation' ), true );

	$Form->checklist( array(
			array( 'pass_after_quick_reg', 1, TB_('If no password has been set yet (email capture/quick registration), go to password setting page first.'), $Settings->get( 'pass_after_quick_reg' ) )
		), '', '' );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Default settings for new accounts').get_manual_link('default-user-settings') );

	$GroupCache = & get_GroupCache();
	$GroupCache->clear();
	$GroupCache->load_where( 'grp_usage = "primary"' );
	$GroupCache->all_loaded = true;
	$Form->select_input_object( 'newusers_grp_ID', $Settings->get( 'newusers_grp_ID' ), $GroupCache, sprintf( TB_('<span %s>Primary</span> user group'), 'class="label label-primary"' ), array_merge( array( 'note' => TB_('Groups determine user roles and permissions.') ), $disabled_param_grouplevel ) );

	$Form->text_input( 'newusers_level', $Settings->get( 'newusers_level' ), 1, TB_('User level'), TB_('Levels determine hierarchy of users in blogs.' ), array_merge( array( 'maxlength' => 1, 'required' => true ), $disabled_param_grouplevel ) );



	$messaging_options = array( array( 'enable_PM', 1, TB_( 'private messages on this site.' ), $Settings->get( 'def_enable_PM' ) ) );
	if( $Settings->get( 'emails_msgform' ) != 'never' )
	{
		$messaging_options[] = array( 'enable_email', 1, TB_( 'emails through a message form that will NOT reveal my email address.' ), $Settings->get( 'def_enable_email' ) );
	}
	$Form->checklist( $messaging_options, 'default_user_msgform', TB_( 'Other users can send me' ) );

	$Form->checklist( array(), 'edited_user_notification', TB_('Notify me by email when the following events occur') );
	$Form->checklist( array(
			array( 'notify_messages', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'I receive a private message.' ),  $Settings->get( 'def_notify_messages' ) ),
			array( 'notify_unread_messages', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'I have unread private messages for more than 24 hours.' ),  $Settings->get( 'def_notify_unread_messages' ), false, TB_( 'This notification is sent only once every 3 days.' ) ),
		), 'default_user_notification', TB_('Messaging') );
	$Form->checklist( array(
			array( 'notify_comment_mentioned', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'I have been mentioned on a comment.' ), $Settings->get( 'def_notify_comment_mentioned' ) ),
			array( 'notify_published_comments', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'a comment is published on one of <strong>my</strong> posts.' ), $Settings->get( 'def_notify_published_comments' ) ),
			array( 'notify_comment_moderation', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'a comment is posted and I have permissions to moderate it.' ), $Settings->get( 'def_notify_comment_moderation' ) ),
			array( 'notify_edit_cmt_moderation', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'a comment is modified and I have permissions to moderate it.' ), $Settings->get( 'def_notify_edit_cmt_moderation' ) ),
			array( 'notify_spam_cmt_moderation', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'a comment is reported as spam and I have permissions to moderate it.' ), $Settings->get( 'def_notify_spam_cmt_moderation' ) ),
			array( 'notify_meta_comment_mentioned', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'I have been mentioned on an internal comment.' ), $Settings->get( 'def_notify_meta_comment_mentioned' ) ),
			array( 'notify_meta_comments', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'an internal comment is posted and I have permission to view it.' ), $Settings->get( 'def_notify_meta_comments' ) ),
		), 'default_user_notification', TB_('Comments') );
	$Form->checklist( array(
			array( 'notify_post_mentioned', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'I have been mentioned on a post.' ), $Settings->get( 'def_notify_post_mentioned' ) ),
			array( 'notify_post_moderation', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'a post is created and I have permissions to moderate it.' ), $Settings->get( 'def_notify_post_moderation' ) ),
			array( 'notify_edit_pst_moderation', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'a post is modified and I have permissions to moderate it.' ), $Settings->get( 'def_notify_edit_pst_moderation' ) ),
			array( 'notify_post_proposed', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'someone proposed a change on a post and I have permissions to moderate it.' ), $Settings->get( 'def_notify_post_proposed' ) ),
			array( 'notify_post_assignment', 1, /* TRANS: Here we imply "Notify me when:" */ TB_( 'a post was assigned to me.' ), $Settings->get( 'def_notify_post_assignment' ) ),
		), 'default_user_notification', TB_('Posts') );

	$NewsletterCache = & get_NewsletterCache();
	$NewsletterCache->load_where( 'enlt_active = 1' );

	if( count( $NewsletterCache->cache ) )
	{	// If at least one newsletter is active:
		$def_newsletters = ( $Settings->get( 'def_newsletters' ) == '' ? array() : explode( ',', $Settings->get( 'def_newsletters' ) ) );
		$newsletter_options = array();
		foreach( $NewsletterCache->cache as $Newsletter )
		{
			$newsletter_options[] = array( 'def_newsletters[]', $Newsletter->ID, $Newsletter->get( 'name' ).': '.$Newsletter->get( 'label' ), in_array( $Newsletter->ID, $def_newsletters ) );
		}
		$Form->checklist( $newsletter_options, 'def_newsletters', TB_( 'Newsletter' ) );
	}

	$Form->text_input( 'notification_email_limit', $Settings->get( 'def_notification_email_limit' ), 3, TB_( 'Limit notification emails to' ), TB_( 'emails per day' ), array( 'maxlength' => 3, 'required' => true ) );
	$Form->text_input( 'newsletter_limit', $Settings->get( 'def_newsletter_limit' ), 3, TB_( 'Limit lists to' ), TB_( 'emails per day' ), array( 'maxlength' => 3, 'required' => true ) );

$Form->end_fieldset();

// --------------------------------------------

if( check_user_perm( 'users', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );
}

?>
<script>
jQuery( 'input[name=newusers_canregister]' ).click( function()
{
	if( jQuery( this ).val() == 'yes' )
	{
		jQuery( '#newusers_grp_ID, #newusers_level, #quick_registration' ).removeAttr( 'disabled' );
	}
	else
	{
		jQuery( '#newusers_grp_ID, #newusers_level, #quick_registration' ).attr( 'disabled', 'disabled' );
	}

	if( jQuery( this ).val() == 'no' )
	{
		jQuery( '#registration_is_public' ).attr( 'disabled', 'disabled' );
	}
	else
	{
		jQuery( '#registration_is_public' ).removeAttr( 'disabled' );
	}
} );

jQuery( 'input[name=http_auth_require]' ).click( function()
{
	if( jQuery( this ).is( ':checked' ) )
	{
		jQuery( 'input[name=http_auth_accept]' ).prop( 'checked', true ).prop( 'disabled', true );
	}
	else
	{
		jQuery( 'input[name=http_auth_accept]' ).prop( 'disabled', false );
	}
} );
</script>
