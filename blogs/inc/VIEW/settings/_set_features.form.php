<?php
/**
 * This file implements the UI view for the general settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * Halton STEWART grants Francois PLANQUE the right to license
 * Halton STEWART's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @author halton: Halton STEWART
 * @author blueyed: Daniel HAHLER
 *
 * @package admin
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

global $htsrv_url;


$Form = & new Form( NULL, 'feats_checkchanges' );

$Form->begin_form( 'fform', T_('Global Features') );

$Form->hidden( 'ctrl', 'features' );
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );

$Form->begin_fieldset( T_('Online Help') . get_web_help_link('online help'));
	$Form->checkbox_input( 'webhelp_enabled', $Settings->get('webhelp_enabled'), T_('Online Help links'), array( 'note' => T_('Online help links provide context sensitive help to certain features.' ) ) );
$Form->end_fieldset();

// --------------------------------------------
$Form->begin_fieldset( T_('Blog by email') . get_web_help_link('blog by email') );

	$Form->checkbox_input( 'eblog_enabled', $Settings->get('eblog_enabled'), T_('Enable Blog by email'),
		array( 'note' => T_('Check to enable the Blog by email feature.' ), 'onclick' => 'this.checked==true?document.getElementById("eblog_section").style.display="":document.getElementById("eblog_section").style.display="none";' ) );

	echo '<div id="eblog_section" style="'.( $Settings->get('eblog_enabled') ? '' : 'display:none' ).'">';
		$Form->select_input_array( 'eblog_method', array( 'pop3'=>T_('POP3'), 'pop3a'=>T_('POP3 (experimental)') ), // TRANS: E-Mail retrieval method
			T_('Retrieval method'), array('value' => $Settings->get('eblog_method'), 'note' => T_('Choose a method to retrieve the emails.') ) );

		$Form->text_input( 'eblog_server_host', $Settings->get('eblog_server_host'), 40, T_('Mail Server'), array( 'maxlength' => 255, 'note' => T_('Hostname or IP address of your incoming mail server.')  )  );

		$Form->text_input( 'eblog_server_port', $Settings->get('eblog_server_port'),5,T_('Port Number'), array( 'maxlength' => 6, 'note' => T_('Port number of your incoming mail server (Defaults: pop3:110 imap:143).')  )  );

		$Form->text_input( 'eblog_username', $Settings->get('eblog_username'),15,T_('Account Name'), array( 'maxlength' => 255, 'note' => T_('User name for authenticating to your mail server.')  )  );

		$Form->password_input( 'eblog_password', $Settings->get('eblog_password'),15,T_('Password'), array( 'maxlength' => 255, 'note' => T_('Password for authenticating to your mail server.')  )  );

		//TODO: have a drop down list of available blogs and categories
		$Form->text_input( 'eblog_default_category', $Settings->get('eblog_default_category'),5,T_('Default Category'), array( 'maxlength' => 6, 'note' => T_('By default emailed posts will have this category.')  )  );

		$Form->text_input( 'eblog_subject_prefix', $Settings->get('eblog_subject_prefix'),15,T_('Subject Prefix'), array( 'maxlength' => 255, 'note' => T_('Email subject must start with this prefix to be imported.')  )  );

		// eblog test links
		// TODO: provide Non-JS functionality (open in a new window).
		$Form->info_field(
			T_('Perform Server Test'),
			' <a id="eblog_test" href="#" onclick=\'return pop_up_window( "' . $htsrv_url . 'getmail.php?test=1", "getmail" );\'>[ ' . T_('connection') . ' ]</a>'
			.' <a id="eblog_test" href="#" onclick=\'return pop_up_window( "' . $htsrv_url . 'getmail.php?test=2", "getmail" );\'>[ ' . T_('messages') . ' ]</a>'
			.' <a id="eblog_test" href="#" onclick=\'return pop_up_window( "' . $htsrv_url . 'getmail.php?test=3", "getmail" );\'>[ ' . T_('verbose') . ' ]</a>',
			array() );

//		$Form->info_field ('','<a id="eblog_test_email" href="#" onclick=\'return pop_up_window( "' . $htsrv_url . 'getmail.php?test=email", "getmail" );\'>' . T_('Test email') . '</a>',array());
		// special show / hide link
		$Form->info_field('', get_link_showhide( 'eblog_show_more','eblog_section_more', T_('Hide extra options'), T_('Show extra options...') ) );


		// TODO: provide Non-JS functionality
		echo '<div id="eblog_section_more" style="display:none">';
			$Form->text_input( 'eblog_body_terminator', $Settings->get('eblog_body_terminator'), 15, T_('Body Terminator'), array( 'maxlength' => 255, 'note' => T_('Starting from this string, everything will be ignored, including this string.')  )  );

			$Form->checkbox_input( 'eblog_test_mode', $Settings->get('eblog_test_mode'), T_('Test Mode'), array( 'note' => T_('Check to run Blog by Email in test mode.' ) ) );

			$Form->checkbox_input( 'eblog_phonemail', $Settings->get('eblog_phonemail'), T_('Phone Email *'),
				array( 'note' => 'Some mobile phone email services will send identical subject &amp; content on the same line. If you use such a service, check this option, and indicate a separator string when you compose your message, you\'ll type your subject then the separator string then you type your login:password, then the separator, then content.' ) );

			$Form->text_input ( 'eblog_phonemail_separator', $Settings->get('eblog_phonemail_separator'),15,T_('Phonemail Separator'),
												array( 'maxlength' => 255 )  );

		echo '</div>';

	echo '</div>';
$Form->end_fieldset();


$Form->begin_fieldset( T_('Hit logging') . get_web_help_link('Hit logging') );
	$Form->checkbox_input( 'hit_doublecheck_referer', $Settings->get('hit_doublecheck_referer'), T_('Double-check Referer'), array( 'note' => T_('Activating this will search the requested (your) URL in the content of the referring page. This is against referer spam, but creates additional webserver traffic.') ) );
	$Form->text_input( 'auto_prune_stats', $Settings->get('auto_prune_stats'), 5, T_('Autoprune stats after'),
		array( 'note' => T_('days. (0 to disable) How many days of stats do you want to keep in the database?'), 'required'=>true ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Sessions') . get_web_help_link('Sessions') );
	// TODO: enhance UI with a general Form method
	$Form->text_input( 'timeout_sessions', $Settings->get('timeout_sessions'), 9, T_('Session timeout'),
		array( 'note' => T_('seconds. How long can a user stay inactive before automatic logout?'), 'required'=>true) );
$Form->end_fieldset();


if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array(
		array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ),
		array( 'submit', 'submit[restore_defaults]', T_('Restore defaults'), 'ResetButton' ),
		) );
}

?>