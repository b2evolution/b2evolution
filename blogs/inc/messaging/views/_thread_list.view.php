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

// Create result set:

$select_sql = 'SELECT mt.thrd_ID, mt.thrd_title, mt.thrd_datemodified, ms.numb_msgs AS thrd_messages
				FROM T_messaging__msgstatus AS ts
				LEFT OUTER JOIN T_messaging__thread mt ON mt.thrd_ID = ts.msta_thread_ID
				LEFT OUTER JOIN
					(SELECT msta_thread_ID, COUNT(*) AS numb_msgs
					 FROM T_messaging__msgstatus
					 WHERE msta_user_ID = '.$current_User->ID.'
					 AND msta_status = 2 GROUP BY msta_thread_ID) AS ms ON ts.msta_thread_ID = ms.msta_thread_ID
				WHERE ts.msta_user_ID = '.$current_User->ID.'
				GROUP BY ts.msta_thread_ID ORDER BY ms.numb_msgs DESC, mt.thrd_datemodified DESC';

$count_sql = 'SELECT COUNT(*)
				FROM (
					SELECT msta_thread_ID
					FROM T_messaging__msgstatus
					WHERE msta_user_ID = '.$current_User->ID.'
					GROUP BY msta_thread_ID) AS threads';

$Results = & new Results( $select_sql, 'thrd_', '', NULL, $count_sql);

$Results->Cache = & get_Cache( 'ThreadCache' );
$Results->title = T_('Threads list');

$Results->cols[] = array(
					'th' => T_('Title'),
					'td' => '¤conditional( #thrd_messages#>0, \'<strong><a href="'.$dispatcher
							.'?ctrl=messages&amp;thrd_ID=$thrd_ID$" title="'.
							T_('Show messages...').'">$thrd_title$</a></strong>\', \'<a href="'
							.$dispatcher.'?ctrl=messages&amp;thrd_ID=$thrd_ID$" title="'.T_('Show messages...').'">$thrd_title$</a>\' )¤',
					);

$Results->cols[] = array(
					'th' => T_('New Messages'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '¤conditional( #thrd_messages#>0, \'<strong>$thrd_messages$</strong>\', \'0\' )¤',
					);

if( $current_User->ID == 1 )
{	// We have permission to modify:
	// Tblue> Shouldn't this check options:edit (see controller)?
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
 * Revision 1.4  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>
