<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User;
global $unread_messages_count;
global $read_unread_recipients;
global $DB, $Blog;
global $perm_abuse_management; // TRUE if we go from Abuse Management

if( !isset( $display_params ) )
{ // init display_params
	$display_params = array();
}
// set default values
$display_params = array_merge( array(
	'show_only_date' => 0,
	), $display_params );

// Select read/unread users for each thread

$recipients_SQL = get_threads_recipients_sql();

foreach( $DB->get_results( $recipients_SQL->get() ) as $row )
{
	$read_by = '';

	if( !empty( $row->thr_read ) )
	{
		$read_by .= get_avatar_imgtags( $row->thr_read, false, false, 'crop-15x15', '', '', true, false );
	}

	if( !empty( $row->thr_unread ) )
	{
		$read_by .= get_avatar_imgtags( $row->thr_unread, false, false, 'crop-15x15', '', '', false, false );
	}

	$read_unread_recipients[$row->thr_ID] = $read_by;
}

// Create result set:
$Results = get_threads_results();

$Results->Cache = & get_ThreadCache();

$Results->title = T_('Conversations list');

if( $unread_messages_count > 0 )
{
	$Results->title = $Results->title.' <span class="badge">'.$unread_messages_count.'</span></b>';
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_recipients( & $Form )
{
	$Form->text( 's', get_param('s'), 20, T_('Search'), '', 255 );
	$Form->text( 'u', get_param('u'), 10, T_('User'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_recipients',
	'presets' => array(
		'all' => array( T_('All'), get_messaging_url( $perm_abuse_management ? 'abuse' : 'threads' ) ),
		)
	);

if( isset($Blog) )
{
	$image_size = $Blog->get_setting('image_size_messaging');
}
else
{
	$image_size = 'crop-32x32';
}
$Results->cols[] = array(
					'th' => T_( $perm_abuse_management ? 'Between' : 'With' ),
					'th_class' => 'thread_with shrinkwrap',
					'td_class' => 'thread_with',
					'td' => '%get_avatar_imgtags( #thrd_recipients#, true, true, "'.$image_size.'", "avatar_before_login_middle mb1" )%',
					);

/**
 * Get subject as link with icon (read or unread)
 *
 * @param thread ID
 * @param thread title
 * @param message ID (If ID > 0 - message is still unread)
 * @return string link with subject
 */
function message_subject_link( $thrd_ID, $thrd_title, $thrd_msg_ID )
{
	global $perm_abuse_management;

	$messages_url = get_messaging_url( 'messages' );
	if( $thrd_title == '' )
	{
		$thrd_title = '<i>(no subject)</i>';
	}
	if( $thrd_msg_ID > 0 )
	{	// Message is unread
		$read_icon = get_icon( 'bullet_red', 'imgtag', array( 'style' => 'margin:0 2px' ) );
	}
	else
	{	// Mesage is read
		$read_icon = get_icon( 'allowback', 'imgtag' );
	}

	$tab = '';
	if( $perm_abuse_management )
	{	// We are in Abuse Management
		$tab = '&amp;tab=abuse';
	}

	$link = $read_icon.'<a href="'.$messages_url.'&amp;thrd_ID='.$thrd_ID.$tab.'" title="'.T_('Show messages...').'">';
	$link .= '<strong>'.$thrd_title.'</strong>';
	$link .= '</a>';

	return $link;
}

$Results->cols[] = array(
					'th' => T_('Subject'),
					'th_class' => 'thread_subject',
					'td_class' => 'thread_subject',
					'td' => '%message_subject_link( #thrd_ID#, #thrd_title#, #thrd_msg_ID# )%',
					);

function convert_date( $date, $show_only_date )
{
	if( $show_only_date )
	{
		return mysql2localedate( $date );
	}

	return mysql2localedatetime( $date );
}

$show_only_date = $display_params[ 'show_only_date' ];
$Results->cols[] = array(
					'th' => T_('Last msg'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '~conditional( #thrd_msg_ID#>0, \'%convert_date(#thrd_unread_since#,'.$show_only_date.')%\', \'%convert_date(#thrd_datemodified#,'.$show_only_date.')%\')~' );


/**
 * Read? column
 *
 * @param mixed $thread_ID
 * @return mixed
 */
function get_read_by( $thread_ID )
{
	global $read_unread_recipients;

	return $read_unread_recipients[$thread_ID];
}
$Results->cols[] = array(
					'th' => T_('Read?'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'center',
					'td' => '%get_read_by( #thrd_ID# )%',
					);

function delete_action( $thread_ID )
{
	global $Blog, $samedomain_htsrv_url;

	if( is_admin_page() )
	{
		return action_icon( T_( 'Delete'), 'delete', regenerate_url( 'action', 'thrd_ID='.$thread_ID.'&action=delete&'.url_crumb( 'thread' ) ) );
	}
	else
	{
		$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=threads' );
		return action_icon( T_( 'Delete'), 'delete', $samedomain_htsrv_url.'messaging.php?thrd_ID='.$thread_ID.'&action=delete&redirect_to='.$redirect_to.'&'.url_crumb( 'thread' ) );
	}
}

if( $current_User->check_perm( 'perm_messaging', 'delete' ) )
{	// We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Del'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%delete_action(  #thrd_ID#  )%',
						);
}

if( ! $perm_abuse_management )
{	// Show link to create a new conversation
	if( is_admin_page() )
	{
		$newmsg_url = regenerate_url( 'action', 'action=new' );
	}
	else
	{
		$newmsg_url = regenerate_url( 'disp', 'disp=threads&action=new' );
	}

	$Results->global_icon( T_('Create a new conversation...'), 'new', $newmsg_url, T_('Compose new').' &raquo;', 3, 4  );
}

$Results->display( $display_params );

/*
 * $Log$
 * Revision 1.42  2011/10/15 09:15:24  efy-yurybakh
 * messaging UI design changes
 *
 * Revision 1.41  2011/10/15 07:15:02  efy-yurybakh
 * Messaging Abuse Management
 *
 * Revision 1.40  2011/10/14 19:02:14  efy-yurybakh
 * Messaging Abuse Management
 *
 * Revision 1.39  2011/10/11 02:05:42  fplanque
 * i18n/wording cleanup
 *
 * Revision 1.38  2011/10/08 01:12:16  fplanque
 * small changes
 *
 * Revision 1.37  2011/10/07 13:14:45  efy-yurybakh
 * Small messaging UI design changes (changed specs)
 *
 * Revision 1.36  2011/10/06 16:45:55  efy-yurybakh
 * small messaging UI design changes (additional email)
 *
 * Revision 1.35  2011/10/06 04:52:14  efy-asimo
 * fix
 *
 * Revision 1.34  2011/10/05 21:24:06  fplanque
 * fix
 *
 * Revision 1.33  2011/10/05 12:05:02  efy-yurybakh
 * Blog settings > features tab refactoring
 *
 * Revision 1.32  2011/10/03 12:00:33  efy-yurybakh
 * Small messaging UI design changes
 *
 * Revision 1.31  2011/10/02 15:25:03  efy-yurybakh
 * small messaging UI design changes
 *
 * Revision 1.30  2011/09/27 07:45:58  efy-asimo
 * Front office messaging hot fixes
 *
 * Revision 1.29  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.28  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.27  2011/09/07 00:28:26  sam2kb
 * Replace non-ASCII character in regular expressions with ~
 *
 * Revision 1.26  2011/08/11 09:05:09  efy-asimo
 * Messaging in front office
 *
 * Revision 1.25  2010/01/30 18:55:32  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.24  2009/10/27 18:48:48  fplanque
 * minor
 *
 * Revision 1.23  2009/10/11 12:15:51  efy-maxim
 * filter by author of the message and message text
 *
 * Revision 1.22  2009/10/11 11:31:32  efy-maxim
 * Extend filter of thread list. Search by user login, user full name, user nuckname and thread title/subject.
 *
 * Revision 1.21  2009/10/10 10:45:44  efy-maxim
 * messaging module - @action_icon()@
 *
 * Revision 1.20  2009/10/08 20:05:52  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.19  2009/10/04 08:26:57  efy-maxim
 * messaging module improvements
 *
 * Revision 1.18  2009/10/02 15:07:27  efy-maxim
 * messaging module improvements
 *
 * Revision 1.17  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.16  2009/09/25 07:32:53  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.15  2009/09/20 02:02:45  fplanque
 * fixed read/unread colors
 *
 * Revision 1.14  2009/09/18 10:38:31  efy-maxim
 * 15x15 icons next to login in messagin module
 *
 * Revision 1.13  2009/09/17 10:54:21  efy-maxim
 * Read/Unread (green/red) users columns in thread list
 *
 * Revision 1.12  2009/09/16 15:14:48  efy-maxim
 * badge for unread message number
 *
 * Revision 1.11  2009/09/15 23:17:12  fplanque
 * minor
 *
 * Revision 1.10  2009/09/15 15:49:32  efy-maxim
 * "read by" column
 *
 * Revision 1.9  2009/09/14 19:33:02  efy-maxim
 * Some queries has been wrapped by SQL object
 *
 * Revision 1.8  2009/09/14 13:52:07  tblue246
 * Translation fixes; removed now pointless doc comment.
 *
 * Revision 1.7  2009/09/14 10:33:20  efy-maxim
 * messagin module improvements
 *
 * Revision 1.6  2009/09/14 07:31:43  efy-maxim
 * 1. Messaging permissions have been fully implemented
 * 2. Messaging has been added to evo bar menu
 *
 * Revision 1.5  2009/09/12 18:44:11  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.4  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>
