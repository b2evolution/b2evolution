<?php
/**
 * This file implements the UI view for the general settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @author fplanque: Francois PLANQUE
 * @author halton: Halton STEWART
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
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

$Form->add_crumb( 'globalsettings' );
$Form->hidden( 'ctrl', 'features' );
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );

$Form->begin_fieldset( T_('Online Help').get_manual_link('online help'));
	$Form->checkbox_input( 'webhelp_enabled', $Settings->get('webhelp_enabled'), T_('Online Help links'), array( 'note' => T_('Online help links provide context sensitive help to certain features.' ) ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('After each new post...').get_manual_link('after_each_post_settings') );
	$Form->radio_input( 'outbound_notifications_mode', $Settings->get('outbound_notifications_mode'),
		array(
			array( 'value'=>'off', 'label'=>T_('Off'), 'note'=>T_('No notification about your new content will be sent out.') ),
			array( 'value'=>'immediate', 'label'=>T_('Immediate'), 'note'=>T_('This is guaranteed to work but may create an annoying delay after each post.') ),
			array( 'value'=>'cron', 'label'=>T_('Asynchronous'), 'note'=>T_('Recommended if you have your scheduled jobs properly set up.') )
		),
		T_('Outbound pings & email notifications'),
		array( 'lines' => true ) );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Blog by email').get_manual_link('blog_by_email') );

	$Form->checkbox_input( 'eblog_enabled', $Settings->get('eblog_enabled'), T_('Enable Blog by email'),
		array( 'note' => sprintf(T_('Note: This feature needs the php_imap extension (currently %s).' ), extension_loaded( 'imap' ) ? T_('loaded') : T_('NOT loaded')), 'onclick' =>
			'document.getElementById("eblog_section").style.display = (this.checked==true ? "" : "none") ;' ) );

	// fp> TODO: this is IMPOSSIBLE to turn back on when you have no javascript!!! :((
	echo '<div id="eblog_section" style="'.( $Settings->get('eblog_enabled') ? '' : 'display:none' ).'">';

		$Form->select_input_array( 'eblog_method', $Settings->get('eblog_method'), array( 'pop3' => T_('POP3'), 'imap' => T_('IMAP'), ), // TRANS: E-Mail retrieval method
			T_('Retrieval method'), T_('Choose a method to retrieve the emails.') );

		$Form->text_input( 'eblog_server_host', $Settings->get('eblog_server_host'), 40, T_('Mail Server'), T_('Hostname or IP address of your incoming mail server.'), array( 'maxlength' => 255 ) );

		$Form->text_input( 'eblog_server_port', $Settings->get('eblog_server_port'), 5, T_('Port Number'), T_('Port number of your incoming mail server (Defaults: pop3: 110 imap: 143).'), array( 'maxlength' => 6 ) );

		$Form->radio( 'eblog_encrypt', $Settings->get('eblog_encrypt'), array(
																			array( 'none', T_('None'), ),
																			array( 'ssl', T_('SSL'), ),
																			array( 'tls', T_('TLS'), ),
																		), T_('Encryption method') );

		$Form->text_input( 'eblog_username', $Settings->get('eblog_username'), 15, T_('Account Name'), T_('User name for authenticating to your mail server.'), array( 'maxlength' => 255 ) );

		$Form->password_input( 'eblog_password', $Settings->get('eblog_password'),15,T_('Password'), array( 'maxlength' => 255, 'note' => T_('Password for authenticating to your mail server.') ) );

		//TODO: have a drop down list of available blogs and categories
		$Form->text_input( 'eblog_default_category', $Settings->get('eblog_default_category'), 5, T_('Default Category ID'), T_('By default emailed posts will have this category.'), array( 'maxlength' => 6 ) );

		$Form->text_input( 'eblog_subject_prefix', $Settings->get('eblog_subject_prefix'), 15, T_('Subject Prefix'), T_('Email subject must start with this prefix to be imported.'), array( 'maxlength' => 255 ) );

		// eblog test links
		// TODO: "cron/" is supposed to not reside in the server's DocumentRoot, therefor is not necessarily accessible
		$getmailurl = $baseurl.'cron/getmail.php?test=';
		$Form->info_field(
			T_('Perform Server Test'),
			' <a id="eblog_test" target="_blank" href="'.$getmailurl.'1" onclick=\'return pop_up_window( "'.$getmailurl.'1", "getmail" )\'>[ ' . T_('connection') . ' ]</a>'
			.' <a id="eblog_test" target="_blank" href="'.$getmailurl.'2" onclick=\'return pop_up_window( "'.$getmailurl.'2", "getmail" )\'>[ ' . T_('messages') . ' ]</a>'
			.' <a id="eblog_test" target="_blank" href="'.$getmailurl.'3" onclick=\'return pop_up_window( "'.$getmailurl.'3", "getmail" )\'>[ ' . T_('verbose') . ' ]</a>',
			array() );

//		$Form->info_field ('','<a id="eblog_test_email" href="#" onclick=\'return pop_up_window( "' . $htsrv_url . 'getmail.php?test=email", "getmail" )\'>' . T_('Test email') . '</a>',array());
		// special show / hide link
		$Form->info_field('', get_link_showhide( 'eblog_show_more','eblog_section_more', T_('Hide extra options'), T_('Show extra options...') ) );


		// TODO: provide Non-JS functionality
		echo '<div id="eblog_section_more" style="display:none">';

			$Form->checkbox( 'eblog_novalidatecert', $Settings->get('eblog_novalidatecert'), T_('Do not validate certificate'), T_('Do not validate the certificate from the TLS/SSL server. Check this if you are using a self-signed certificate.') );

			$Form->checkbox( 'eblog_add_imgtag', $Settings->get('eblog_add_imgtag'), T_('Add &lt;img&gt; tags'), T_('Display image attachments using &lt;img&gt; tags (instead of creating a link).'));

			$Form->checkbox( 'AutoBR', $Settings->get('AutoBR'), T_('Email/MMS Auto-BR'), T_('Add &lt;BR /&gt; tags to mail/MMS posts.') );

			$Form->text_input( 'eblog_body_terminator', $Settings->get('eblog_body_terminator'), 15, T_('Body Terminator'), T_('Starting from this string, everything will be ignored, including this string.'), array( 'maxlength' => 255 )  );

			$Form->checkbox_input( 'eblog_test_mode', $Settings->get('eblog_test_mode'), T_('Test Mode'), array( 'note' => T_('Check to run Blog by Email in test mode.' ) ) );

			/* tblue> this isn't used/implemented at the moment
			$Form->checkbox_input( 'eblog_phonemail', $Settings->get('eblog_phonemail'), T_('Phone Email *'),
				array( 'note' => 'Some mobile phone email services will send identical subject &amp; content on the same line. If you use such a service, check this option, and indicate a separator string when you compose your message, you\'ll type your subject then the separator string then you type your login:password, then the separator, then content.' ) );

			$Form->text_input( 'eblog_phonemail_separator', $Settings->get('eblog_phonemail_separator'), 15, T_('Phonemail Separator'), '',
												array( 'maxlength' => 255 ) );*/

		echo '</div>';

	echo '</div>';
$Form->end_fieldset();

// fp> TODO: it would be awesome to be able to enable the different APIs individually
// that way you minimalize security/spam risks by enable just what you need.
$Form->begin_fieldset( T_('Remote publishing').get_manual_link('remote_publishing') );
	$Form->checkbox_input( 'general_xmlrpc', $Settings->get('general_xmlrpc'), T_('Enable XML-RPC'), array( 'note' => T_('Enable the Movable Type, MetaWeblog, Blogger and B2 XML-RPC publishing protocols.') ) );
	$Form->text_input( 'xmlrpc_default_title', $Settings->get('xmlrpc_default_title'), 50, T_('Default title'), T_('Default title for items created with a XML-RPC API that doesn\'t send a post title (e. g. the Blogger API).'), array( 'maxlength' => 255 ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Hit & session logging').get_manual_link('hit_logging') );

	$Form->checklist( array(
			array( 'log_public_hits', 1, T_('on every public page'), $Settings->get('log_public_hits') ),
			array( 'log_admin_hits', 1, T_('on every admin page'), $Settings->get('log_admin_hits') ) ),
		'log_hits', T_('Log hits') );

	// TODO: draw a warning sign if set to off
	$Form->radio_input( 'auto_prune_stats_mode', $Settings->get('auto_prune_stats_mode'), array(
			array(
				'value'=>'off',
				'label'=>T_('Off'),
				'note'=>T_('Not recommended! Your database will grow very large!'),
				'onclick'=>'jQuery("#auto_prune_stats_container").hide();' ),
			array(
				'value'=>'page',
				'label'=>T_('On every page'),
				'note'=>T_('This is guaranteed to work but uses extra resources with every page displayed.'),
				'onclick'=>'jQuery("#auto_prune_stats_container").show();' ),
			array(
				'value'=>'cron',
				'label'=>T_('With a scheduled job'),
				'note'=>T_('Recommended if you have your scheduled jobs properly set up.'), 'onclick'=>'jQuery("#auto_prune_stats_container").show();' ) ),
		T_('Auto pruning'),
		array( 'note' => T_('Note: Even if you don\'t log hits, you still need to prune sessions!'),
		'lines' => true ) );

	echo '<div id="auto_prune_stats_container">';
	$Form->text_input( 'auto_prune_stats', $Settings->get('auto_prune_stats'), 5, T_('Prune after'), T_('days. How many days of hits & sessions do you want to keep in the database for stats?') );
	echo '</div>';

	if( $Settings->get('auto_prune_stats_mode') == 'off' )
	{ // hide the "days" input field, if mode set to off:
		echo '<script type="text/javascript">jQuery("#auto_prune_stats_container").hide();</script>';
	}

$Form->end_fieldset();

$Form->begin_fieldset( T_('Categories').get_manual_link('categories_global_settings'), array( 'id'=>'categories') );
	$Form->checkbox_input( 'allow_moving_chapters', $Settings->get('allow_moving_chapters'), T_('Allow moving categories'), array( 'note' => T_('Check to allow moving categories accross blogs. (Caution: can break pre-existing permalinks!)' ) ) );
	$Form->radio_input( 'chapter_ordering', $Settings->get('chapter_ordering'), array(
					array( 'value'=>'alpha', 'label'=>T_('Alphabetical') ),
					array( 'value'=>'manual', 'label'=>T_('Manual ') ),
			 ), T_('Ordering of categories') );
$Form->end_fieldset();


if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array(
		array( 'submit', '', T_('Update'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ),
		) );
}


/*
 * $Log$
 * Revision 1.24  2010/01/02 21:11:59  fplanque
 * fat reduction / cleanup
 *
 * Revision 1.23  2010/01/02 17:24:30  fplanque
 * Crumbs - Proof of concept
 *
 * Revision 1.22  2009/09/02 14:05:32  tblue246
 * minor
 *
 * Revision 1.21  2009/09/02 13:47:34  waltercruz
 * Setting the default title fot posts created through blogger API
 *
 * Revision 1.20  2009/08/31 18:07:12  fplanque
 * minor/doc
 *
 * Revision 1.19  2009/08/31 16:32:26  tblue246
 * Check whether XML-RPC is enabled in xmlsrv/xmlrpc.php
 *
 * Revision 1.18  2009/08/31 15:56:39  waltercruz
 * Adding setting to enable/disable xmlrc
 *
 * Revision 1.17  2009/07/04 15:43:44  sam2kb
 * "Manual " with space, like in _chapter_list.view.php
 *
 * Revision 1.16  2009/05/18 03:59:39  fplanque
 * minor/doc
 *
 * Revision 1.15  2009/05/06 06:40:17  sam2kb
 * minor
 *
 * Revision 1.14  2009/04/13 11:33:34  tblue246
 * Fix translation conflict
 *
 * Revision 1.13  2009/04/11 17:18:56  tblue246
 * "You could notify news every minute.": What is this supposed to mean exactly? Wouldn't the wiki be a better place for this note?
 *
 * Revision 1.12  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.11  2009/01/28 21:23:22  fplanque
 * Manual ordering of categories
 *
 * Revision 1.10  2008/12/27 21:09:28  fplanque
 * minor
 *
 * Revision 1.9  2008/10/06 11:02:27  tblue246
 * Blog by mail now supports POP3 & IMAP, SSL & TLS
 *
 * Revision 1.8  2008/10/05 10:55:46  tblue246
 * Blog by mail: We've only one working method => removed the drop-down box and added automatical change to pop3a.
 * The default value for this setting was in the wrong file, moved.
 *
 * Revision 1.7  2008/10/05 06:28:32  fplanque
 * no message
 *
 * Revision 1.6  2008/10/04 14:25:25  tblue246
 * Code improvements in blog/cron/getmail.php, e. g. option to add <img> tags for image attachments.
 * All attachments now get added to the post if the filename is valid (validate_filename()). Not sure if this is secure, but should be.
 *
 * Revision 1.5  2008/02/13 11:34:20  blueyed
 * Explicitly call jQuery(), not the shortcut ($())
 *
 * Revision 1.4  2008/02/09 20:06:10  blueyed
 * Use "lines"=true for radio_input() fields, instead of BR-suffix for each option. Fixes display of group note with "auto_prune_stats"
 *
 * Revision 1.3  2008/01/21 09:35:34  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/12 21:00:32  fplanque
 * UI improvements
 *
 * Revision 1.1  2007/06/25 11:01:23  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.27  2007/05/27 20:01:42  fplanque
 * keeping help string
 *
 * Revision 1.26  2007/05/15 20:46:36  blueyed
 * trans-fix: save an extra sentence by using the same phrase as below for note
 *
 * Revision 1.25  2007/05/15 20:42:00  blueyed
 * trans-todo
 *
 * Revision 1.24  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.23  2007/03/09 15:18:42  blueyed
 * Removed bloated "params" usage in Form::radio_input() for $field_options. Now the attribs/params for each radio input are directly in the $field_options entry instead.
 *
 * Revision 1.22  2007/01/26 02:12:09  fplanque
 * cleaner popup windows
 *
 * Revision 1.21  2007/01/23 08:57:36  fplanque
 * decrap!
 *
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
 */
?>
