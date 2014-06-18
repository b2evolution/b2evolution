<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
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
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-bogdan: Evo Factory / Bogdan.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _registration.form.php 6405 2014-04-07 03:01:41Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

global $dispatcher;

global $collections_Module;

global $baseurl;

$Form = new Form( NULL, 'settings_checkchanges' );
$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

	$Form->add_crumb( 'registration' );
	$Form->hidden( 'ctrl', 'registration' );
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'tab', 'registration' );

// --------------------------------------------

$Form->begin_fieldset( T_('Default user permissions').get_manual_link('default-user-permissions') );

	$Form->checkbox( 'newusers_canregister', $Settings->get('newusers_canregister'), T_('New users can register'), T_('Check to allow new users to register themselves.' ) );

	$Form->checkbox( 'registration_is_public', $Settings->get('registration_is_public'), T_('Registration links'), T_('Check to show self-registration links to the public.' ), '', 1, ! $Settings->get('newusers_canregister') );

	$GroupCache = & get_GroupCache();
	$Form->select_object( 'newusers_grp_ID', $Settings->get('newusers_grp_ID'), $GroupCache, T_('Group for new users'), T_('Groups determine user roles and permissions.') );

	$Form->text_input( 'newusers_level', $Settings->get('newusers_level'), 1, T_('Level for new users'), T_('Levels determine hierarchy of users in blogs.' ), array( 'maxlength'=>1, 'required'=>true ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Default user settings').get_manual_link('default-user-settings') );

	$messaging_options = array( array( 'enable_PM', 1, T_( 'private messages on this site.' ), $Settings->get( 'def_enable_PM' ) ) );
	if( $Settings->get( 'emails_msgform' ) != 'never' )
	{
		$messaging_options[] = array( 'enable_email', 1, T_( 'emails through a message form that will NOT reveal my email address.' ), $Settings->get( 'def_enable_email' ) );
	}
	$Form->checklist( $messaging_options, 'default_user_msgform', T_( 'Other users can send me' ) );

	$notify_options = array(
		array( 'notify_messages', 1, T_( 'I receive a private message.' ),  $Settings->get( 'def_notify_messages' ) ),
		array( 'notify_unread_messages', 1, T_( 'I have unread private messages for more than 24 hours.' ),  $Settings->get( 'def_notify_unread_messages' ), false, T_( 'This notification is sent only once every 3 days.' ) ),
		array( 'notify_published_comments', 1, T_( 'a comment is published on one of <strong>my</strong> posts.' ), $Settings->get( 'def_notify_published_comments' ) ),
		array( 'notify_comment_moderation', 1, T_( 'a comment is posted and I have permissions to moderate it.' ), $Settings->get( 'def_notify_comment_moderation' ) ),
		array( 'notify_post_moderation', 1, T_( 'a post is created and I have permissions to moderate it.' ), $Settings->get( 'def_notify_post_moderation' ) ),
	);
	$Form->checklist( $notify_options, 'default_user_notification', T_( 'Notify me by email whenever' ) );

	$newsletter_options = array(
		array( 'newsletter_news', 1, T_( 'Send me news about this site.' ).' <span class="note">'.T_('Each message contains an easy 1 click unsubscribe link.').'</span>', $Settings->get( 'def_newsletter_news' ) ),
		array( 'newsletter_ads', 1, T_( 'I want to receive ADs that may be relevant to my interests.' ), $Settings->get( 'def_newsletter_ads' ) )
	);
	$Form->checklist( $newsletter_options, 'default_user_newsletter', T_( 'Newsletter' ) );

	$Form->text_input( 'notification_email_limit', $Settings->get( 'def_notification_email_limit' ), 3, T_( 'Limit notification emails to' ), T_( 'emails per day' ), array( 'maxlength' => 3, 'required' => true ) );
	$Form->text_input( 'newsletter_limit', $Settings->get( 'def_newsletter_limit' ), 3, T_( 'Limit newsletters to' ), T_( 'emails per day' ), array( 'maxlength' => 3, 'required' => true ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Account activation').get_manual_link('account-activation-settings'), array( 'id' => 'account_activation' ) );

	$Form->checkbox( 'newusers_mustvalidate', $Settings->get('newusers_mustvalidate'), T_('New users must activate by email'), T_('Check to require users to activate their account by clicking a link sent to them via email.' ) );

	$Form->checkbox( 'newusers_revalidate_emailchg', $Settings->get('newusers_revalidate_emailchg'), T_('Reactivate after email change'), T_('Check to require users to re-activate their account when they change their email address.' ) );

	$Form->radio( 'validation_process', $Settings->get( 'validation_process' ), array(
					array( 'secure', T_( 'Secure account activation process' ), T_( 'Users must validate their account in the same session. Prevents activation of an account by someone else if an incorrect email address is entered. No reminder emails can be sent.' ) ),
					array( 'easy', T_( 'Easy account activation process' ), T_( 'Allows to send reminder emails to unregistered accounts.' ) )
				), T_( 'Activation process' ), true );

	$Form->duration_input( 'activate_requests_limit', $Settings->get( 'activate_requests_limit' ), T_('Limit activation email requests to'), 'minutes', 'minutes', array( 'minutes_step' => 5, 'required' => true, 'note' => T_('Only one activation email can be sent to the same email address in every given minutes.') ) );

	$Form->checkbox( 'newusers_findcomments', $Settings->get('newusers_findcomments'), T_('Find old comments'), T_('After each activation, find comments left by the user based on the validated email address and attach them to the user account.' ) );

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
					array( 'return_to_original', T_( 'Return to original page' ) ),
					array( 'specific_url', T_( 'Go to specific URL' ).':', '',
						'<input type="text" id="specific_after_validation_url" class="form_text_input form-control" name="specific_after_validation_url" size="50" maxlength="120" value="'
						.format_to_output( $after_validation_url, 'formvalue' ).'"
						onfocus="document.getElementsByName(\'after_email_validation\')[1].checked=true;" />' )
				), T_( 'After email activation' ), true );

$Form->end_fieldset();

// --------------------------------------------


$Form->begin_fieldset( T_('Security options').get_manual_link('registration-security-settings') );

	$Form->text_input( 'user_minpwdlen', (int)$Settings->get('user_minpwdlen'), 2, T_('Minimum password length'), T_('characters.'), array( 'maxlength'=>2, 'required'=>true ) );

	$Form->checkbox_input( 'js_passwd_hashing', (bool)$Settings->get('js_passwd_hashing'), T_('Login password hashing'), array( 'note'=>T_('Check to enable the login form to hash the password with Javascript before transmitting it. This provides extra security on non-SSL connections.')) );

	$Form->checkbox_input( 'passwd_special', (bool)$Settings->get('passwd_special'), T_('Require specials characters'), array( 'note'=>T_('Check to require at least 1 special character (not a letter nor a digit).')) );

	$Form->checkbox_input( 'strict_logins', (bool)$Settings->get('strict_logins'), T_('Require strict logins'), array( 'note'=>sprintf( T_('Check to require only plain ACSII characters in user logins. Uncheck to allow any characters and symbols. The following characters are never allowed for security reasons: %s'), '\', ", >, <, @') ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Other options').get_manual_link('other-registration-settings') );

	$Form->checkbox_input( 'registration_require_country', $Settings->get('registration_require_country'), T_('Require country'), array( 'note'=>T_('New users will have to specify their country in order to register.') ) );

	$Form->checkbox_input( 'registration_require_firstname', $Settings->get('registration_require_firstname'), T_('Require first name'), array( 'note'=>T_('New users will have to specify their first name in order to register.') ) );

	$Form->checkbox_input( 'registration_ask_locale', $Settings->get('registration_ask_locale'), T_('Ask for language'), array( 'note'=>T_('New users will be prompted for their preferred language/locale.') ) );

	$Form->radio( 'registration_require_gender',$Settings->get('registration_require_gender'), array(
					array( 'hidden', T_('Hidden') ),
					array( 'optional', T_('Optional') ),
					array( 'required', T_('Required') ),
				), T_('Gender'), true );

	if( $Settings->get( 'after_registration' ) == 'return_to_original' )
	{ // return to original url
		$after_registration = 'return_to_original';
		$after_registration_url = url_add_param( $baseurl, 'disp=profile' );
	}
	else
	{ // set specific URL
		$after_registration = 'specific_url';
		$after_registration_url = $Settings->get( 'after_registration' );
	}
	$Form->radio( 'after_registration', $after_registration, array(
					array( 'return_to_original', T_( 'Return to original page' ) ),
					array( 'specific_url', T_( 'Go to specific URL' ).':', '',
						'<input type="text" id="specific_after_registration_url" class="form_text_input form-control" name="specific_after_registration_url" size="50" maxlength="120" value="'
						.format_to_output( $after_registration_url, 'formvalue' ).'"
						onfocus="document.getElementsByName(\'after_registration\')[1].checked=true;" />' )
				), T_( 'After registration' ), true );

$Form->end_fieldset();
// --------------------------------------------

if( $current_User->check_perm( 'users', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>
<script type="text/javascript">
jQuery( '#newusers_canregister' ).click( function()
{
	if( jQuery( this ).is( ':checked' ) )
	{
		jQuery( '#registration_is_public' ).removeAttr( 'disabled' );
	}
	else
	{
		jQuery( '#registration_is_public' ).attr( 'disabled', 'disabled' );
	}
} );
</script>
