<?php
/**
 * This file implements the UI view for the general settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal
 * Halton STEWART grants François PLANQUE the right to license
 * Halton STEWART's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @author halton: Halton STEWART
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


?>
<script type="text/javascript">
<!--
/**
 * Shows/Hides target_id, and updates text_id object with either text_when_displayed or text_when_hidden
**/
function showhide( text_id, target_id , text_when_displayed , text_when_hidden )
{
	if( document.getElementById(target_id).style.display=="" )
	{
		document.getElementById( text_id ).innerHTML = text_when_hidden;
		document.getElementById( target_id ).style.display="none";
	}
	else
	{
		document.getElementById( text_id ).innerHTML = text_when_displayed;
		document.getElementById( target_id ).style.display="";
	}
}
//-->
</script>
<?php

// --------------------------------------------
// testing the concept of online help (aka webhelp).
// this function should be relocated somewhere better if it is taken onboard by the project
function web_help_link( $topic )
{
	//todo:
	// replace [?] with icon,
	// write url suffix dynamically based on topic and language
	// discuss with Francois where on his server the docco can go ;)
	// launch new window with javascript maybe?
	global $Settings, $current_locale, $app_shortname, $app_version;

	if ( $Settings->get('webhelp_enabled') )
	{
		$webhelp = ' <a target="_blank" href="http://manual.b2evolution.net/redirect/'.$topic
							.'?lang='.$current_locale.'&amp;app='.$app_shortname.'&amp;version='.$app_version.'">[?]</a>';
		return $webhelp;
	}
	else
	{
		return '';
	}

}

// --------------------------------------------
function link_showhide ( $link_id, $target_id, $text_when_displayed , $text_when_hidden )
{
	$html = "<a id='$link_id' href='#$link_id' onclick='showhide(\"$link_id\",\"$target_id\",\"$text_when_displayed\",\"$text_when_hidden\")'>";
	$html .= $text_when_hidden;
	$html .= '</a>';
	return $html;
}


// --------------------------------------------



$Form = & new Form( 'features.php', 'form' );

$Form->begin_form( 'fform', T_('Global Features') );

$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );
$Form->begin_fieldset( T_('Online Help') . web_help_link('features_online_help'));
	$Form->checkbox_input( 'webhelp_enabled', $Settings->get('webhelp_enabled'), T_('Enable Online Help links'),
	array(	'note' => T_('Online help links provide context sensitive help to certain features.' ) ) );


$Form->end_fieldset();

// --------------------------------------------
$Form->begin_fieldset( T_('Blog by email') . web_help_link('features_blog_by_email') );

	$Form->checkbox_input( 'eblog_enabled', $Settings->get('eblog_enabled'), T_('Enable Blog by email'),
	array(	'note' => T_('Check to enable the Blog by email feature.' ),
					'onclick'=>'this.checked==true?document.getElementById("eblog_section").style.display="":document.getElementById("eblog_section").style.display="none";' ) );

	$tmpstyle = $Settings->get('eblog_enabled')==1?'':'display:none';
	echo '<div id="eblog_section" style="'. $tmpstyle .'">';
// fplanque>>TODO: there is something VERY broken with the page structure here. Please make it a priority to fix this or I'll remove everything weird until it works.

		$Form->select_input_array( 'eblog_method', array( 'pop3'=>T_('POP3'), 'pop3a'=>T_('POP3 (experimental)') ), T_('Email retrieval method'), array('value' => $Settings->get('eblog_method')) );

		$Form->text_input	( 'eblog_server_host', $Settings->get('eblog_server_host'),40,T_('Mail Server'),
												array( 'maxlength' => 255, 'note' => T_('Hostname or IP Address of your incomming mail server.')  )  );

		$Form->text_input	( 'eblog_server_port', $Settings->get('eblog_server_port'),5,T_('Port Number'),
												array( 'maxlength' => 6, 'note' => T_('Port number of your incomming mail server (defaults pop3:110 imap:143).')  )  );

		$Form->text_input	( 'eblog_username', $Settings->get('eblog_username'),15,T_('Account Name'),
												array( 'maxlength' => 255, 'note' => T_('User name for authenticating to your mail server.')  )  );

		$Form->password_input	( 'eblog_password', $Settings->get('eblog_password'),15,T_('Password'),
													array( 'maxlength' => 255, 'note' => T_('Password for authenticating to your mail server.')  )  );

		//TODO: have a drop down list of available blogs and categories
		$Form->text_input	( 'eblog_default_category', $Settings->get('eblog_default_category'),5,T_('Default Category'),
												array( 'maxlength' => 6, 'note' => T_('By default email blogs will have this category.')  )  );

		$Form->text_input ( 'eblog_subject_prefix', $Settings->get('eblog_subject_prefix'),15,T_('Subject Prefix'),
												array( 'maxlength' => 255, 'note' => T_('Email subject must start with this prefix to be imported.')  )  );

		// eblog test links
		$Form->info_field (T_('Perform Server Test'),' <a id="eblog_test" href="#eblog_test" onclick=\'pop_up_window( "' . $htsrv_url . 'getmail.php?test=1", "getmail" );\'>[ ' . T_('connection') . ' ]</a>' .
																	' <a id="eblog_test" href="#eblog_test" onclick=\'pop_up_window( "' . $htsrv_url . 'getmail.php?test=2", "getmail" );\'>[ ' . T_('messages') . ' ]</a>' .
																	' <a id="eblog_test" href="#eblog_test" onclick=\'pop_up_window( "' . $htsrv_url . 'getmail.php?test=3", "getmail" );\'>[ ' . T_('verbose') . ' ]</a>',
																	array());

//		$Form->info_field ('','<a id="eblog_test_email" href="#eblog_test_emails" onclick=\'pop_up_window( "' . $htsrv_url . 'getmail.php?test=email", "getmail" );\'>' . T_('Test email') . '</a>',array());
		// special show / hide link
		$Form->info_field ('', link_showhide( 'eblog_show_more','eblog_section_more', T_('hide extra options'), T_('show extra options...') ) ,array());

		echo '<div name="eblog_section_more" id="eblog_section_more" style="display:none">';
			$Form->text_input(	'eblog_body_terminator', $Settings->get('eblog_body_terminator'),15,T_('Body Terminator'),
													array( 'maxlength' => 255, 'note' => T_('starting from this string, everything will be ignored, including this string.')  )  );

			$Form->checkbox_input( 	'eblog_test_mode', $Settings->get('eblog_test_mode'), T_('Test Mode'),
											array(	'note' => T_('Check to run Blog by Email in test mode.' ) ) );

			$Form->checkbox_input( 	'eblog_phonemail', $Settings->get('eblog_phonemail'), T_('Phone Email *'),
											array(	'note' => "<br/>some mobile phone email services will send identical subject &amp; content on the same line
												<br/> if you use such a service, check this option, and indicate a separator string
 												<br/> when you compose your message, you'll type your subject then the separator string
                        <br/> then you type your login:password, then the separator, then content." ) );

			$Form->text_input ( 'eblog_phonemail_separator', $Settings->get('eblog_phonemail_separator'),15,T_('Phonemail Separator'),
												array( 'maxlength' => 255 )  );

		echo '</div>';


  echo '</div>';
$Form->end_fieldset();


$Form->begin_fieldset( T_('Statistics') );
	$Form->checkbox_input( 'hit_doublecheck_referer', $Settings->get('hit_doublecheck_referer'), T_('Double-check Referer'), array( 'note' => 'Activating this will search the requested (your) URL in the content of the referring page. This is against referer spam, but creates additional webserver traffic.' ) );
$Form->end_fieldset();


// --------------------------------------------

// --------------------------------------------

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( 	array( 	array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
														array( 'reset', '', T_('Reset'), 'ResetButton' ),
														array( 'submit', 'submit', T_('Restore defaults'), 'ResetButton' ),
											) );
}

?>