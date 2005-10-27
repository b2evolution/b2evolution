<?php
/**
 * This file implements the UI view for the user/group list for user/group editing.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// query which groups have users (in order to prevent deletion of groups which have users)
$usedgroups = $DB->get_col( 'SELECT grp_ID
															 FROM T_groups INNER JOIN T_users ON user_grp_ID = grp_ID
															GROUP BY grp_ID');

// get the userlist
if( !empty( $filteron ) )
{
	$filtered = true;
	$afilter = split(' ', $filteron);
	$swhere = '';
	foreach ($afilter as $sfilter)
	{

		$swhere .= 'concat(user_login, user_firstname, user_lastname, user_nickname, user_email) like "%' . $DB->escape($sfilter) . '%" and ';
	}
	$sql = "SELECT T_users.*, grp_ID, grp_name
					FROM T_users RIGHT JOIN T_groups ON user_grp_ID = grp_ID
					WHERE $swhere 1
					ORDER BY grp_name";
}
else
{
	$filteron = '';
	$filtered = false;
	$sql = "SELECT T_users.*, grp_ID, grp_name
					FROM T_users RIGHT JOIN T_groups ON user_grp_ID = grp_ID
					ORDER BY grp_name";
}

function conditional( $condition, $on_true, $on_false = '' )
{
	if( $condition )
	{
		return $on_true;
	}
	else
	{
		return $on_false;
	}
}


$Results = & new Results( $sql, 'cont_', '-A' );

$Results->title = T_('Groups & Users');

$Results->group_by = 'grp_ID';
$Results->ID_col = 'user_ID';

/*
 * Group columns:
 */
$Results->grp_cols[] = array(
						'td_start' => '<td colspan="'
														.($current_User->check_perm( 'users', 'edit', false ) ? 7 : 6)
														.'" class="firstcol'.($current_User->check_perm( 'users', 'edit', false ) ? '' : ' lastcol' ).'">',
						'td' => '<a href="b2users.php?grp_ID=$grp_ID$">$grp_name$</a>'
										.'¤conditional( (#grp_ID# == '.$Settings->get('newusers_grp_ID').'), \' <span class="notes">('.T_('default group for new users').')</span>\' )¤',
					);

function grp_actions( & $row )
{
	global $usedgroups, $Settings;

	$r = action_icon( T_('Edit this group...'), 'edit', regenerate_url( 'action', 'grp_ID='.$row->grp_ID ) );

	$r .= action_icon( T_('Duplicate this group...'), 'copy', regenerate_url( 'action', 'action=new_group&amp;grp_ID='.$row->grp_ID ) );

	if( ($row->grp_ID != 1) && ($row->grp_ID != $Settings->get('newusers_grp_ID')) && !in_array( $row->grp_ID, $usedgroups ) )
	{ // delete
		$r .= action_icon( T_('Delete this group!'), 'delete', regenerate_url( 'action', 'action=delete_group&amp;grp_ID='.$row->grp_ID ) );
	}

	return $r;
}
$Results->grp_cols[] = array(
						'td_start' => '<td class="lastcol shrinkwrap">',
						'td' => '%grp_actions( {row} )%',
					);

/*
 * Data columns:
 */
$Results->cols[] = array(
						'th' => T_('ID'),
						'th_start' => '<th class="firstcol shrinkwrap">',
						'td_start' => '<td class="firstcol shrinkwrap">',
						'order' => 'user_ID',
						'td' => '$user_ID$',
					);

$Results->cols[] = array(
						'th' => T_('Login'),
						'order' => 'user_login',
						'td' => '<a href="b2users.php?user_ID=$user_ID$">$user_login$</a>',
					);

$Results->cols[] = array(
						'th' => T_('Nickname'),
						'order' => 'user_nickname',
						'td' => '$user_nickname$',
					);

$Results->cols[] = array(
						'th' => T_('Name'),
						'order' => 'user_lastname, user_firstname',
						'td' => '$user_firstname$ $user_lastname$',
					);

$Results->cols[] = array(
						'th' => T_('Email'),
						'td_start' => '<td class="shrinkwrap">',
						'td' => '¤conditional( !empty(#user_email#), \'<a href="mailto:$user_email$" title="e-mail: $user_email$">'
								.get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => 'Email: $user_email$' ) ).'</a>\', \'&nbsp;\' )¤',
					);

$Results->cols[] = array(
						'th' => T_('URL'),
						'td_start' => '<td class="shrinkwrap">',
						'td' => '¤conditional( (#user_url# != \'http://\') && (#user_url# != \'\'), \'<a href="$user_url$" title="Website: $user_url$">'
								.get_icon( 'www', 'imgtag', array( 'class' => 'middle', 'title' => 'Website: $user_url$' ) ).'</a>\', \'&nbsp;\' )¤',
					);

if( ! $current_User->check_perm( 'users', 'edit', false ) )
{
 	$Results->cols[] = array(
						'th' => T_('Level'),
						'td_start' => '<td class="right">',
						'order' => 'user_level',
						'td' => '$user_level$',
					);
}
else
{
	$Results->cols[] = array(
						'th' => T_('Level'),
						'td_start' => '<td class="right">',
						'order' => 'user_level',
						'td' => '¤conditional( (#user_level# > 0), \''
											.action_icon( TS_('Decrease user level'), 'arrow_down',
												'%regenerate_url( \'action\', \'action=promote&amp;prom=down&amp;id=$user_ID$\' )%' ).'\' )¤'
										.'$user_level$ '
										.'¤conditional( (#user_level# < 10), \''
											.action_icon( TS_('Increase user level'), 'arrow_up',
												'%regenerate_url( \'action\', \'action=promote&amp;prom=up&amp;id=$user_ID$\' )%' ).'\' )¤',
					);


	$Results->cols[] = array(
						'th' => T_('Actions'),
						'td' => action_icon( T_('Edit this user...'), 'edit', '%regenerate_url( \'action\', \'user_ID=$user_ID$\' )%' )
										.action_icon( T_('Duplicate this user...'), 'copy', '%regenerate_url( \'action\', \'action=new_user&amp;user_ID=$user_ID$\' )%' )
										.'¤conditional( (#user_ID# != 1) && (#user_ID# != '.$current_User->ID.'), \''
											.action_icon( T_('Delete this user!'), 'delete', '%regenerate_url( \'action\', \'action=delete_user&amp;user_ID=$user_ID$\' )%' ).'\' )¤'

					);
}


if( $current_User->check_perm( 'users', 'edit', false ) )
{ // create new user link
	$Results->global_icon( T_('Add a user...'), 'new', '?action=new_user', T_('User') );
	$Results->global_icon( T_('Add a group...'), 'new', '?action=new_group', T_('Group') );
}

//Display filter/search block
echo '<center>';
$Form = & new Form( 'b2users.php', 'filter', 'get', '' );
$Form->begin_form('fform');
$Form->text( 'filteron', $filteron, 30, '', '', 80 );
$Form->end_form( array( array( 'submit', 'filter', T_('Filter'), 'SaveButton' ),array('submit','filter',T_('Clear'),'SaveButton' ) ) );
echo '</center>';

// Display result :
$Results->display();

/*
 * $Log$
 * Revision 1.51  2005/10/27 15:25:03  fplanque
 * Normalization; doc; comments.
 *
 * Revision 1.50  2005/10/20 16:35:18  halton
 * added search / filtering to user list
 *
 * Revision 1.49  2005/10/03 17:26:43  fplanque
 * synched upgrade with fresh DB;
 * renamed user_ID field
 *
 * Revision 1.48  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.47  2005/06/02 18:50:52  fplanque
 * no message
 *
 * Revision 1.46  2005/05/24 15:26:51  fplanque
 * cleanup
 *
 * Revision 1.45  2005/05/04 18:16:55  fplanque
 * Normalizing
 *
 * Revision 1.44  2005/05/03 14:38:14  fplanque
 * finished multipage userlist
 *
 * Revision 1.43  2005/05/02 19:06:45  fplanque
 * started paging of user list..
 *
 * Revision 1.42  2005/04/28 20:44:18  fplanque
 * normalizing, doc
 *
 * Revision 1.41  2005/04/21 18:01:28  fplanque
 * CSS styles refactoring
 *
 * Revision 1.40  2005/04/07 17:55:48  fplanque
 * minor changes
 *
 * Revision 1.39  2005/04/06 13:33:28  fplanque
 * minor changes
 *
 * Revision 1.38  2005/03/22 19:17:30  fplanque
 * cleaned up some nonsense...
 *
 */
?>