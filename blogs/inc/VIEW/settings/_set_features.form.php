<?php
/**
 * This file implements the UI view for the general settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Halton STEWART grants Francois PLANQUE the right to license
 * Halton STEWART's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author halton: Halton STEWART
 * @author blueyed: Daniel HAHLER
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

global $baseurl;


$Form = & new Form( NULL, 'feats_checkchanges' );

$Form->begin_form( 'fform', T_('Global Features') );

$Form->hidden( 'ctrl', 'features' );
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );

$Form->begin_fieldset( T_('Online Help') . get_web_help_link('online help'));
	$Form->checkbox_input( 'webhelp_enabled', $Settings->get('webhelp_enabled'), T_('Online Help links'), array( 'note' => T_('Online help links provide context sensitive help to certain features.' ) ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('After each new post...') . get_web_help_link('After each post settings'));
	$Form->radio_input( 'outbound_notifications_mode', $Settings->get('outbound_notifications_mode'), array(
			array( 'value'=>'off', 'label'=>T_('Off'), 'note'=>T_('No notification about your new content will be sent out.'), 'suffix' => '<br />' ),
			array( 'value'=>'immediate', 'label'=>T_('Immediate'), 'note'=>T_('This is guaranteed to work but may create an annoying delay after each post.'), 'suffix' => '<br />' ),
			array( 'value'=>'cron', 'label'=>T_('Asynchronous'), 'note'=>T_('Recommended if you have your scheduled jobs properly set up. You could notify news every minute.') ) ),
								T_('Outbound pings & email notifications') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Blog by email') . get_web_help_link('blog by email') );

	$Form->checkbox_input( 'eblog_enabled', $Settings->get('eblog_enabled'), T_('Enable Blog by email'),
		array( 'note' => T_('Check to enable the Blog by email feature.' ), 'onclick' =>
			'document.getElementById("eblog_section").style.display = (this.checked==true ? "" : "none") ;' ) );

	// fp> TODO: this is IMPOSSIBLE to turn back on when you have no javascript!!! :((
	echo '<div id="eblog_section" style="'.( $Settings->get('eblog_enabled') ? '' : 'display:none' ).'">';

		$Form->select_input_array( 'eblog_method', array( 'pop3'=>T_('POP3'), 'pop3a' => T_('POP3 through IMAP extension (experimental)') ), // TRANS: E-Mail retrieval method
			T_('Retrieval method'), array('value' => $Settings->get('eblog_method'), 'note' => T_('Choose a method to retrieve the emails.') ) );

		$Form->text_input( 'eblog_server_host', $Settings->get('eblog_server_host'), 40, T_('Mail Server'), T_('Hostname or IP address of your incoming mail server.'), array( 'maxlength' => 255 ) );

		$Form->text_input( 'eblog_server_port', $Settings->get('eblog_server_port'), 5, T_('Port Number'), T_('Port number of your incoming mail server (Defaults: pop3:110 imap:143).'), array( 'maxlength' => 6 ) );

		$Form->text_input( 'eblog_username', $Settings->get('eblog_username'), 15, T_('Account Name'), T_('User name for authenticating to your mail server.'), array( 'maxlength' => 255 ) );

		$Form->password_input( 'eblog_password', $Settings->get('eblog_password'),15,T_('Password'), array( 'maxlength' => 255, 'note' => T_('Password for authenticating to your mail server.') ) );

		//TODO: have a drop down list of available blogs and categories
		$Form->text_input( 'eblog_default_category', $Settings->get('eblog_default_category'), 5, T_('Default Category ID'), T_('By default emailed posts will have this category.'), array( 'maxlength' => 6 ) );

		$Form->text_input( 'eblog_subject_prefix', $Settings->get('eblog_subject_prefix'), 15, T_('Subject Prefix'), T_('Email subject must start with this prefix to be imported.'), array( 'maxlength' => 255 ) );

		// eblog test links
		// TODO: provide Non-JS functionality (open in a new window).
		// TODO: "cron/" is supposed to not reside in the server's DocumentRoot, therefor is not necessarily accessible
		$Form->info_field(
			T_('Perform Server Test'),
			' <a id="eblog_test" href="#" onclick=\'return pop_up_window( "'.$baseurl.'cron/getmail.php?test=1", "getmail" );\'>[ ' . T_('connection') . ' ]</a>'
			.' <a id="eblog_test" href="#" onclick=\'return pop_up_window( "'.$baseurl.'cron/getmail.php?test=2", "getmail" );\'>[ ' . T_('messages') . ' ]</a>'
			.' <a id="eblog_test" href="#" onclick=\'return pop_up_window( "'.$baseurl.'cron/getmail.php?test=3", "getmail" );\'>[ ' . T_('verbose') . ' ]</a>',
			array() );

//		$Form->info_field ('','<a id="eblog_test_email" href="#" onclick=\'return pop_up_window( "' . $htsrv_url . 'getmail.php?test=email", "getmail" );\'>' . T_('Test email') . '</a>',array());
		// special show / hide link
		$Form->info_field('', get_link_showhide( 'eblog_show_more','eblog_section_more', T_('Hide extra options'), T_('Show extra options...') ) );


		// TODO: provide Non-JS functionality
		echo '<div id="eblog_section_more" style="display:none">';

			$Form->checkbox( 'AutoBR', $Settings->get('AutoBR'), T_('Email/MMS Auto-BR'), T_('Add &lt;BR /&gt; tags to mail/MMS posts.') );

			$Form->text_input( 'eblog_body_terminator', $Settings->get('eblog_body_terminator'), 15, T_('Body Terminator'), T_('Starting from this string, everything will be ignored, including this string.'), array( 'maxlength' => 255 )  );

			$Form->checkbox_input( 'eblog_test_mode', $Settings->get('eblog_test_mode'), T_('Test Mode'), array( 'note' => T_('Check to run Blog by Email in test mode.' ) ) );

			$Form->checkbox_input( 'eblog_phonemail', $Settings->get('eblog_phonemail'), T_('Phone Email *'),
				array( 'note' => 'Some mobile phone email services will send identical subject &amp; content on the same line. If you use such a service, check this option, and indicate a separator string when you compose your message, you\'ll type your subject then the separator string then you type your login:password, then the separator, then content.' ) );

			$Form->text_input( 'eblog_phonemail_separator', $Settings->get('eblog_phonemail_separator'), 15, T_('Phonemail Separator'), '',
												array( 'maxlength' => 255 ) );

		echo '</div>';

	echo '</div>';
$Form->end_fieldset();


$Form->begin_fieldset( T_('Hit & session logging') . get_web_help_link('Hit logging') );

	$Form->checklist( array(
			array( 'log_public_hits', 1, T_('on every public page'), $Settings->get('log_public_hits') ),
			array( 'log_admin_hits', 1, T_('on every admin page'), $Settings->get('log_admin_hits') ) ),
		'log_hits', T_('Log hits') );

	// TODO: draw a warning sign if set to off
	$Form->radio_input( 'auto_prune_stats_mode', $Settings->get('auto_prune_stats_mode'), array(
			array( 'value'=>'off', 'label'=>T_('Off'), 'note'=>T_('Not recommended! Your database will grow very large!!'),
				'suffix' => '<br />',	'params' => array('onclick'=>'$("#auto_prune_stats_container").hide();') ),
			array( 'value'=>'page', 'label'=>T_('On every page'), 'note'=>T_('This is guaranteed to work but uses extra resources with every page displayed.'), 'suffix' => '<br />',
				'params' => array('onclick'=>'$("#auto_prune_stats_container").show();') ),
			array( 'value'=>'cron', 'label'=>T_('With a scheduled job'), 'note'=>T_('Recommended if you have your scheduled jobs properly set up.'),
				'params' => array('onclick'=>'$("#auto_prune_stats_container").show();') ) ),
		T_('Auto pruning'),
		array( 'note' => T_('Note: Even if you don\'t log hits, you still need to prune sessions!') ) );

	echo '<div id="auto_prune_stats_container">';
	$Form->text_input( 'auto_prune_stats', $Settings->get('auto_prune_stats'), 5, T_('Prune after'), T_('days. How many days of hits & sessions do you want to keep in the database for stats?') );
	echo '</div>';

	if( $Settings->get('auto_prune_stats_mode') == 'off' )
	{ // hide the "days" input field, if mode set to off:
		echo '<script type="text/javascript">$("#auto_prune_stats_container").hide();</script>';
	}

$Form->end_fieldset();

$Form->begin_fieldset( T_('Categories') . get_web_help_link('categories'), array( 'id'=>'categories') );
	$Form->checkbox_input( 'allow_moving_chapters', $Settings->get('allow_moving_chapters'), T_('Allow moving categories'), array( 'note' => T_('Check to allow moving categories accross blogs. (Caution: can break pre-existing permalinks!)' ) ) );
$Form->end_fieldset();


if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array(
		array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ),
		array( 'submit', 'submit[restore_defaults]', T_('Restore defaults'), 'ResetButton' ),
		) );
}


/*
 * $Log$
 * Revision 1.20  2006/12/12 20:41:41  blueyed
 * Whitespace
 *
 * Revision 1.19  2006/12/11 00:32:26  fplanque
 * allow_moving_chapters stting moved to UI
 * chapters are now called categories in the UI
 *
 * Revision 1.18  2006/12/09 01:55:36  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.17  2006/12/07 00:55:52  fplanque
 * reorganized some settings
 *
 * Revision 1.16  2006/12/06 18:06:18  fplanque
 * an experiment with JS hiding/showing form parts
 *
 * Revision 1.15  2006/12/03 01:25:49  blueyed
 * Use & instead of &amp; when it gets encoded for output
 *
 * Revision 1.14  2006/12/03 00:22:17  fplanque
 * doc
 *
 * Revision 1.13  2006/11/27 00:07:57  blueyed
 * Hide auto_prune_stats field, if ~_mode set to off
 *
 * Revision 1.12  2006/11/26 23:47:42  blueyed
 * Wording and "and" instead of "&amp;"
 *
 * Revision 1.11  2006/11/26 23:43:20  blueyed
 * whitespace
 *
 * Revision 1.10  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.9  2006/08/21 21:33:36  fplanque
 * scheduled pings part 1
 *
 * Revision 1.8  2006/08/21 00:03:13  fplanque
 * obsoleted some dirty old thing
 *
 * Revision 1.7  2006/07/06 19:59:08  fplanque
 * better logs, better stats, better pruning
 *
 * Revision 1.6  2006/05/05 17:53:29  blueyed
 * Fixes for blog by email: made tests work and use default port, if not given
 *
 * Revision 1.5  2006/04/29 17:37:48  blueyed
 * Added basic_antispam_plugin; Moved double-check-referers there; added check, if trackback links to us
 *
 * Revision 1.4  2006/04/19 20:13:52  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>