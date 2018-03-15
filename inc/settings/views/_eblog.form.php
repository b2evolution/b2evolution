<?php
/**
 * This file implements the UI view for Post by Email settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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

global $Plugins, $baseurl, $eblog_test_output, $eblog_saved_test_mode_value, $comment_allowed_tags;


$Form = new Form( NULL, 'remotepublish_checkchanges' );

$Form->begin_form('fform');

$Form->add_crumb( 'globalsettings' );
$Form->hidden( 'ctrl', 'remotepublish' );
$Form->hidden( 'tab', 'eblog' );
$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( T_('Test saved settings').get_manual_link( 'post-by-email' ) );

	$url = '?ctrl=remotepublish&amp;tab=eblog&amp;'.url_crumb('globalsettings').'&amp;action=';
	$Form->info_field( T_('Perform tests'),
				'<a href="'.$url.'test_1">['.T_('server connection').']</a>&nbsp;&nbsp;
				<a href="'.$url.'test_2">['.T_('simulate posting').']</a>&nbsp;&nbsp;
				<a href="'.$url.'test_3">['.T_('create one post').']</a>' );

	if( !empty($eblog_test_output) )
	{
		echo '<div style="margin-top:25px"></div>';
		if( $action == 'test_2' )
		{
			echo '<div class="red center">'.T_('This is just a test run. Nothing will be posted to the database nor will your inbox be altered').'</div>';
		}
		// Display scrollable div
		echo '<div style="padding: 6px; margin:5px; border: 1px solid #CCC; overflow:scroll; height: 350px">'.$eblog_test_output.'</div>';
	}

$Form->end_fieldset();

$Form->begin_fieldset( T_('General settings').get_manual_link('post-by-email-general-settings') );

	if( extension_loaded( 'imap' ) )
	{
		$imap_extenssion_status = T_('(INSTALLED)');
	}
	else
	{
		$imap_extenssion_status = '<b class="red">'.T_('(NOT INSTALLED)').'</b>';
	}

	$Form->checkbox_input( 'eblog_enabled', $Settings->get('eblog_enabled'), T_('Enable Post by email'),
		array( 'note' => sprintf(T_('Note: This feature needs the php_imap extension %s.' ), $imap_extenssion_status) ) );

	$eblog_test_mode_value = isset($eblog_saved_test_mode_value) ? $eblog_saved_test_mode_value : $Settings->get('eblog_test_mode');
	$Form->checkbox_input( 'eblog_test_mode', $eblog_test_mode_value, T_('Test Mode'),
				array( 'note' => T_('Check to run Post by Email in test mode. Nothing will be posted to the database nor will your inbox be altered.' ) ) );

	$Form->text_input( 'eblog_server_host', $Settings->get('eblog_server_host'), 25, T_('Mail Server'), T_('Hostname or IP address of your incoming mail server.'), array( 'maxlength' => 255 ) );

	$Form->radio( 'eblog_method', $Settings->get('eblog_method'), array(
			array( 'pop3', T_('POP3'), ),// TRANS: E-Mail retrieval method
			array( 'imap', T_('IMAP'), ),// TRANS: E-Mail retrieval method
		), T_('Retrieval method') );

	$Form->radio( 'eblog_encrypt', $Settings->get('eblog_encrypt'), array(
																		array( 'none', T_('None'), ),
																		array( 'ssl', T_('SSL'), ),
																		array( 'tls', T_('TLS'), ),
																	), T_('Encryption method') );

	$eblog_novalidatecert_params = array( 'lines' => true );
	if( $Settings->get('eblog_encrypt') == 'none' )
	{
		$eblog_novalidatecert_params['disabled'] = 'disabled';
	}
	$Form->radio_input( 'eblog_novalidatecert', $Settings->get( 'eblog_novalidatecert' ), array(
			array( 'value' => 1, 'label' => T_('Do not validate the certificate from the TLS/SSL server. Check this if you are using a self-signed certificate.') ),
			array( 'value' => 0, 'label' => T_('Validate that the certificate from the TLS/SSL server can be trusted. Use this if you have a correctly signed certificate.') )
		), T_('Certificate validation'), $eblog_novalidatecert_params );

	$Form->text_input( 'eblog_server_port', $Settings->get('eblog_server_port'), 5, T_('Port Number'), T_('Port number of your incoming mail server (Defaults: POP3: 110, IMAP: 143, SSL/TLS: 993).'), array( 'maxlength' => 6 ) );

	$Form->text_input( 'eblog_username', $Settings->get('eblog_username'), 25,
				T_('Account Name'), T_('User name for authenticating on your mail server. Usually it\'s your email address or a part before the @ sign.'), array( 'maxlength' => 255 ) );

	$Form->password_input( 'eblog_password', $Settings->get('eblog_password'), 25,
				T_('Password'), array( 'maxlength' => 255, 'note' => T_('Password for authenticating on your mail server.') ) );

	$Form->checkbox( 'eblog_delete_emails', $Settings->get('eblog_delete_emails'), T_('Delete processed emails'),
				T_('Check this if you want processed messages to be deleted from server after successful processing.') );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Posting settings').get_manual_link('post-by-email-posting-settings') );

	// TODO: provide a list of categories to choose from
	$Form->text_input( 'eblog_default_category', $Settings->get('eblog_default_category'), 5,
				T_('Default Category ID'), sprintf( T_('This is a default category for your posts.').'<br />'.
				T_('You can use the following code in message body to define categories: %s. In this example 2 will be main category and 5, 47 extra categories.'), '<b>&lt;category&gt;2, 5, 47&lt;/category&gt;</b>' ), array( 'maxlength' => 6 ) );

	$Form->text_input( 'eblog_default_title', $Settings->get('eblog_default_title'), 50,
				T_('Default title'), sprintf( T_('This is a default title for your posts.').'<br />'.
				T_('You can use the following code in message body to define post title: %s.'), '<b>&lt;title&gt;Post title here&lt;/title&gt;</b>' ), array( 'maxlength' => 255 ) );

	$Form->checkbox( 'eblog_add_imgtag', $Settings->get('eblog_add_imgtag'), T_('Add &lt;img&gt; tags'),
				T_('Display image attachments using &lt;img&gt; tags (instead of linking them through file manager).') );

	$Form->text_input( 'eblog_subject_prefix', $Settings->get('eblog_subject_prefix'), 15,
				T_('Subject Prefix'), T_('Email subject must start with this prefix to be imported, messages that don\'t have this tag will be skipped.'), array( 'maxlength' => 255 ) );

	$Form->text_input( 'eblog_body_terminator', $Settings->get('eblog_body_terminator'), 15,
				T_('Body Terminator'), T_('Starting from this string, everything will be ignored, including this string.').
				'<br />'.T_('You can use this to remove signature from message body.'), array( 'maxlength' => 255 ) );

	/* Automatically select a blog from where get plugins collection settings ( current_User should be able to create post on the selected blog )*/
	$autoselect_blog = autoselect_blog( 'blog_post_statuses', 'edit' );
	$BlogCache = & get_BlogCache();
	$setting_Blog = & $BlogCache->get_by_ID( $autoselect_blog );
	$Form->info( T_('Text Renderers'), $Plugins->get_renderer_checkboxes( $Settings->get('eblog_renderers'), array( 'name_prefix' => 'eblog_', 'Blog' => & $setting_Blog ) ) );

$Form->end_fieldset();

$Form->begin_fieldset( T_('HTML messages').get_manual_link('post-by-email-html-messages') );

// sam2kb> TODO: display some warning message about potential risk with HTML emails
$Form->checkbox( 'eblog_html_enabled', $Settings->get('eblog_html_enabled'), T_('Enable HTML messages'),
				T_('Check this if you want HTML messages to be processed and posted in your blog.') );

$Form->checkbox( 'eblog_html_tag_limit', $Settings->get('eblog_html_tag_limit'), T_('Limit allowed tags'),
				T_('Check this if you want to limit allowed HTML tags to the following list:').
				'<br /><b>'.htmlspecialchars(str_replace( '>', '> ', $comment_allowed_tags )).'</b>' );

$Form->end_fieldset();

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( 'input[name="eblog_method"], input[name="eblog_encrypt"]' ).click( function()
	{	// Change default port depending on selected retrieval and encryption methods:
		var method = jQuery( 'input[name="eblog_method"]:checked' ).val();
		var encrypt = jQuery( 'input[name="eblog_encrypt"]:checked' ).val();

		if( method == 'pop3' )
		{
			jQuery( 'input[name="eblog_server_port"]' ).val( encrypt == 'ssl' ? '995' : '110' );
		}
		else if( method == 'imap' )
		{
			jQuery( 'input[name="eblog_server_port"]' ).val( encrypt == 'ssl' ? '993' : '143' );
		}
	} );

	jQuery( 'input[name="eblog_encrypt"]' ).click( function()
	{	// Enable/Disable "Certificate validation" options depending on encryption method
		if( jQuery( this ).val() == 'none' )
		{
			jQuery( 'input[name="eblog_novalidatecert"]' ).attr( 'disabled', 'disabled' );
		}
		else
		{
			jQuery( 'input[name="eblog_novalidatecert"]' ).removeAttr( 'disabled' );
		}
	} )
} );
</script>