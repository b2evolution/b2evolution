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

global $dispatcher, $action, $current_User, $edited_Thread;

global $read_by_list;

$creating = is_create_action( $action );

// Update message statuses

$DB->query( 'UPDATE T_messaging__threadstatus
				SET tsta_first_unread_msg_ID = NULL
				WHERE tsta_thread_ID = '.$edited_Thread->ID.'
				AND tsta_user_ID = '.$current_User->ID );

// Select all recipients

$recipients_SQL = & new SQL();

$recipients_SQL->SELECT( 'GROUP_CONCAT(u.user_login ORDER BY u.user_login SEPARATOR \',\')' );

$recipients_SQL->FROM( 'T_messaging__threadstatus mts
								LEFT OUTER JOIN T_users u ON mts.tsta_user_ID = u.user_ID' );

$recipients_SQL->WHERE( 'mts.tsta_thread_ID = '.$edited_Thread->ID.'
								AND mts.tsta_user_ID <> '.$current_User->ID );

$recipients = explode( ',', $DB->get_var( $recipients_SQL->get() ) );

// Select unread recipients

$unread_recipients_SQL = & new SQL();

$unread_recipients_SQL->SELECT( 'mm.msg_ID, GROUP_CONCAT(uu.user_login ORDER BY uu.user_login SEPARATOR \',\') AS msg_unread' );

$unread_recipients_SQL->FROM( 'T_messaging__message mm
										LEFT OUTER JOIN T_messaging__threadstatus tsu ON mm.msg_ID = tsu.tsta_first_unread_msg_ID
										LEFT OUTER JOIN T_users uu ON tsu.tsta_user_ID = uu.user_ID' );

$unread_recipients_SQL->WHERE( 'mm.msg_thread_ID = '.$edited_Thread->ID );

$unread_recipients_SQL->GROUP_BY( 'mm.msg_ID' );

$unread_recipients_SQL->ORDER_BY( 'mm.msg_datetime' );

$unread_recipients = array();

// Create array for read by

foreach( $DB->get_results( $unread_recipients_SQL->get() ) as $row )
{
	if( !empty( $row->msg_unread ) )
	{
		$unread_recipients = array_merge( $unread_recipients, explode( ',', $row->msg_unread ) );
	}

	$read_recipiens = array_diff( $recipients, $unread_recipients );

	$read_by = '';
	if( !empty( $read_recipiens ) )
	{
		$read_by .= '<span style="color:green">'.implode( ', ', $read_recipiens );
		if( !empty ( $unread_recipients ) )
		{
			$read_by .= ', ';
		}
		$read_by .= '</span>';
	}

	if( !empty ( $unread_recipients ) )
	{
		$read_by .= '<span style="color:red">'.implode( ', ', $unread_recipients ).'</span>';
	}

	$read_by_list[$row->msg_ID] = $read_by ;
}

// Create SELECT query:

$select_SQL = & new SQL();

$select_SQL->SELECT( 'mm.msg_ID, mm.msg_datetime, u.user_login AS msg_author,
						u.user_firstname AS msg_firstname, u.user_lastname AS msg_lastname, mm.msg_text' );

$select_SQL->FROM( 'T_messaging__message mm
						LEFT OUTER JOIN T_users u ON u.user_ID = mm.msg_author_user_ID' );

$select_SQL->WHERE( 'mm.msg_thread_ID = '.$edited_Thread->ID );

$select_SQL->ORDER_BY( 'mm.msg_datetime' );

// Create COUNT query

$count_SQL = & new SQL();

$count_SQL->SELECT( 'COUNT(*)' );
$count_SQL->FROM( 'T_messaging__message' );
$count_SQL->WHERE( 'msg_thread_ID = '.$edited_Thread->ID );

// Create result set:

$Results = & new Results( $select_SQL->get(), 'msg_', '', 0, $count_SQL->get() );

$Results->title = $edited_Thread->title;

$Results->cols[] = array(
					'th' => T_('Author'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '<b>$msg_author$</b><br/>$msg_firstname$ $msg_lastname$<br/>
							<span class="note">%mysql2localedatetime(#msg_datetime#)%</span>',
					);

$Results->cols[] = array(
					'th' => T_('Message'),
					'td_class' => 'lastcol',
					'td' => '¤conditional( empty(#msg_text#), \''.$edited_Thread->title.'\', \'%nl2br(#msg_text#)%\')¤',
					);

function get_read_by( $message_ID )
{
	global $read_by_list;

	return $read_by_list[$message_ID];
}

$Results->cols[] = array(
					'th' => T_('Read by'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '%get_read_by( #msg_ID# )%',
					);

if( $current_User->check_perm( 'messaging', 'delete' ) )
{
	// We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							// Do not display the icon if the message cannot be deleted
							'td' => $Results->total_rows == 1 ? '' :
										action_icon( T_('Delete this message!'), 'delete',
											'%regenerate_url( \'action\', \'msg_ID=$msg_ID$&amp;action=delete\')%' ),
						);
}

$Results->display();

$Form = & new Form( NULL, 'messages_checkchanges', 'post', 'compact' );

$Form->begin_form( 'fform', '' );

$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

$Form->info_field(T_('Reply to'), implode( ', ', $recipients ), array('required'=>true));

$Form->textarea('msg_text', '', 10, '', '', 80, '', true);

$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
/*
 * $Log$
 * Revision 1.12  2009/09/15 15:49:32  efy-maxim
 * "read by" column
 *
 * Revision 1.11  2009/09/14 19:33:02  efy-maxim
 * Some queries has been wrapped by SQL object
 *
 * Revision 1.10  2009/09/14 15:18:00  efy-maxim
 * 1. Recipients can be separated by commas or spaces.
 * 2. Message list: author, full name date in the first column.
 * 3. Message list: message in the second column
 *
 * Revision 1.9  2009/09/14 13:52:07  tblue246
 * Translation fixes; removed now pointless doc comment.
 *
 * Revision 1.8  2009/09/14 10:33:20  efy-maxim
 * messagin module improvements
 *
 * Revision 1.7  2009/09/14 07:31:43  efy-maxim
 * 1. Messaging permissions have been fully implemented
 * 2. Messaging has been added to evo bar menu
 *
 * Revision 1.6  2009/09/13 15:56:12  fplanque
 * minor
 *
 * Revision 1.5  2009/09/12 18:44:11  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.4  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>
