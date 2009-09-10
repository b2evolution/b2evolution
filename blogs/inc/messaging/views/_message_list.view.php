<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher, $action, $DB, $thrd_ID;
$creating = is_create_action( $action );

// Update message statuses

$DB->query( 'UPDATE T_messaging__msgstatus
				SET msta_status = 1
				WHERE msta_status = 2 AND msta_thread_ID = '.$thrd_ID.'
				AND msta_user_ID = '.$current_User->ID );

// Create result set:

$select_sql = 'SELECT mm.msg_ID, mm.msg_datetime, u.user_login AS msg_author, mm.msg_text
				FROM T_messaging__message mm
				LEFT OUTER JOIN T_users u ON u.user_ID = mm.msg_author_user_ID
					WHERE mm.msg_thread_ID = '.$thrd_ID.'
					ORDER BY mm.msg_datetime';

$count_sql = 'SELECT COUNT(*)
				FROM T_messaging__message
					WHERE msg_thread_ID = '.$thrd_ID;

$Results = & new Results( $select_sql, 'msg_', '', 0, $count_sql);

$Results->title = T_('Messages list');

$Results->cols[] = array(
					'th' => T_('Message'),
					'td_class' => 'lastcol',
					'td' => '$msg_text$',
					);

$Results->cols[] = array(
					'th' => T_('Date / Time'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '%mysql2localedatetime(\'$msg_datetime$\')%',
					);

$Results->cols[] = array(
					'th' => T_('Author'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '$msg_author$',
					);

if( $current_User->ID == 1 )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => action_icon( T_('Delete this message!'), 'delete',
	                        '%regenerate_url( \'action\', \'msg_ID=$msg_ID$&amp;action=delete\')%' ),
						);
}

$Results->display();

$Form = & new Form( NULL, 'messages_checkchanges', 'post', 'compact' );

$Form->begin_form( 'fform', '' );

$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

$Form->textarea_input( 'msg_text', '', 10, T_('New Message'), array( 'cols'=>80, 'required'=>true ) );

$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

?>