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

global $dispatcher;
global $current_User;

// Create SELECT query

$select_SQL = & new SQL();
$select_SQL->SELECT( 'mt.thrd_ID, mt.thrd_title, mt.thrd_datemodified, mts.tsta_first_unread_msg_ID
			  			AS thrd_msg_ID, mm.msg_datetime AS thrd_unread_since,' );

$select_SQL->SELECT_add( '(SELECT GROUP_CONCAT(ru.user_login ORDER BY ru.user_login SEPARATOR \', \')
							FROM T_messaging__threadstatus AS rts
							LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID
							WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_recipients' );

$select_SQL->FROM( 'T_messaging__threadstatus mts
					LEFT OUTER JOIN T_messaging__thread mt ON mts.tsta_thread_ID = mt.thrd_ID
					LEFT OUTER JOIN T_messaging__message mm ON mts.tsta_first_unread_msg_ID = mm.msg_ID' );

$select_SQL->WHERE( 'mts.tsta_user_ID = '.$current_User->ID );
$select_SQL->ORDER_BY( 'mts.tsta_first_unread_msg_ID DESC, mt.thrd_datemodified DESC' );

// Create COUNT quiery

$count_SQL = & new SQL();

$count_SQL->SELECT( 'COUNT(*)' );
$count_SQL->FROM( 'T_messaging__threadstatus' );
$count_SQL->WHERE( 'tsta_user_ID = '.$current_User->ID );

// Create result set:

$Results = & new Results( $select_SQL->get(), 'thrd_', '', NULL, $count_SQL->get() );

$Results->Cache = & get_Cache( 'ThreadCache' );
$Results->title = T_('Threads list');

$Results->cols[] = array(
					'th' => T_('With'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '$thrd_recipients$',
					//'td' => '%strmaxlen(#thrd_recipients#, 20)%',
					);

$Results->cols[] = array(
					'th' => T_('Unread since'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '¤conditional( #thrd_msg_ID#>0, \'%mysql2localedatetime(#thrd_unread_since#)%\', \'&nbsp;\')¤' );

$Results->cols[] = array(
					'th' => T_('Subject'),
					'td' => '¤conditional( #thrd_msg_ID#>0, \'<strong><a href="'.$dispatcher
							.'?ctrl=messages&amp;thrd_ID=$thrd_ID$" title="'.
							T_('Show messages...').'">$thrd_title$</a></strong>\', \'<a href="'
							.$dispatcher.'?ctrl=messages&amp;thrd_ID=$thrd_ID$" title="'.T_('Show messages...').'">$thrd_title$</a>\' )¤',
					);

if( $current_User->check_perm( 'messaging', 'delete' ) )
{	// We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => action_icon( T_('Delete this thread!'), 'delete',
	                        '%regenerate_url( \'action\', \'thrd_ID=$thrd_ID$&amp;action=delete\')%' ),
						);
}

$Results->global_icon( T_('Create a new thread...'), 'new', regenerate_url( 'action', 'action=new'), T_('New thread').' &raquo;', 3, 4  );

$Results->display();

/*
 * $Log$
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
