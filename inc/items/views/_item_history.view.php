<?php
/**
 * This file implements the Item history view
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Collection, $Blog;

/**
 * @var Item
 */
global $edited_Item;

global $admin_url;

if( $lastedit_User = & $edited_Item->get_lastedit_User() )
{	// Get login of last edit user
	$lastedit_user_login = $lastedit_User->get( 'login' );
}
else
{	// User was deleted
	$lastedit_user_login = T_('(deleted user)');
}

$sql_current_version = 'SELECT "C" AS iver_ID,
		"'.$edited_Item->datemodified.'" AS iver_edit_datetime,
		"'.$edited_Item->lastedit_user_ID.'" AS iver_edit_user_ID,
		"'.T_('Current version').'" AS action,
		"'.$edited_Item->status.'" AS iver_status,
		"'.$edited_Item->title.'" AS iver_title,
		"'.str_replace( '"', '\"', $lastedit_user_login ).'" AS user_login';
$SQL = new SQL();
$SQL->SELECT( 'iver_ID, iver_edit_datetime, iver_edit_user_ID, "'.T_('Archived version').'" AS action, iver_status, iver_title, user_login' );
$SQL->FROM( 'T_items__version' );
// LEFT JOIN users to display versions edited by already deleted users
$SQL->FROM_add( 'LEFT JOIN T_users ON iver_edit_user_ID = user_ID' );
$SQL->WHERE( 'iver_itm_ID = ' . $edited_Item->ID );
// fp> not actually necessary:
// UNION
// SELECT "'.$edited_Item->datecreated.'" AS iver_edit_datetime, "'.$edited_Item->creator_user_ID.'" AS user_login, "First version" AS action';

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT(*)+1' );
$count_SQL->FROM( 'T_items__version' );
$count_SQL->WHERE( $SQL->get_where( '' ) );

$revisions_count = $DB->get_var( $count_SQL->get() );

$default_order = $revisions_count > 1 ? '---D' : '-D';

// Create result set:
$Results = new Results( $sql_current_version . ' UNION ' . $SQL->get(), 'iver_', $default_order, NULL, $count_SQL->get() );

$Results->title = T_('Item history (experimental) for:').' '.$edited_Item->get_title();

/**
 * Get radio input to select a revision to compare
 *
 * @param integer Revision ID
 * @param string Input name
 * @param integer Number of selected revision
 * @param string Direction (up | down)
 */
function iver_compare_selector( $revision_ID, $input_name, $selected, $direction = 'up' )
{
	global $iver_compare_selector;
	if( !isset( $iver_compare_selector ) )
	{
		$iver_compare_selector = array();
	}
	if( !isset( $iver_compare_selector[ $input_name ] ) )
	{
		$iver_compare_selector[ $input_name ] = 0;
	}
	$iver_compare_selector[ $input_name ]++;

	$style = '';
	if( ( $iver_compare_selector[ $input_name ] < $selected && $direction == 'up' ) ||
	    ( $iver_compare_selector[ $input_name ] > $selected && $direction == 'down' ) )
	{	// Hide inputs
		$style = ' style="display:none"';
	}

	$checked = '';
	if( $iver_compare_selector[ $input_name ] == $selected )
	{	// Select this input
		$checked = ' checked="checked"';
	}

	return '<input type="radio" name="'.$input_name.'" value="'.$revision_ID.'"'.$style.$checked.' />';
}

if( $revisions_count > 1 )
{	// Dispay the selectors to compare the revisions
	$Results->cols[] = array(
							'th' => '',
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%iver_compare_selector( #iver_ID#, "r1", 2 )%',
						);

	$Results->cols[] = array(
							'th' => '',
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%iver_compare_selector( #iver_ID#, "r2", 1, "down" )%',
						);
}

$Results->cols[] = array(
						'th' => T_('ID'),
						'order' => 'iver_ID',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '$iver_ID$',
					);

$Results->cols[] = array(
						'th' => T_('Date'),
						'order' => 'iver_edit_datetime',
						'default_dir' => 'D',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%mysql2localedatetime_spans( #iver_edit_datetime# )%',
					);

/**
 * Get item version editor login with link to user profile
 *
 * @param integer editor user ID
 * @return string user profile link or 'Deleted user' text if the user doesn't exist anymore
 */
function iver_editor_login( $user_ID )
{
	$r = get_user_identity_link( NULL, $user_ID );
	if( empty( $r ) )
	{
		return T_('(deleted user)');
	}
	return $r;
}
$Results->cols[] = array(
						'th' => T_('User'),
						'order' => 'user_login',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%iver_editor_login( #iver_edit_user_ID# )%',
					);

/**
 * Get post status label from DB value
 *
 * @param string Status value
 * @return string Status label
 */
function iver_status_label( $iver_status )
{
	$statuses = get_visibility_statuses();
	return isset( $statuses[ $iver_status ] ) ? $statuses[ $iver_status ] : $iver_status;
}
$Results->cols[] = array(
						'th' => T_('Status'),
						'order' => 'iver_status',
						'td' => '%iver_status_label( #iver_status# )%',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
					);

$Results->cols[] = array(
						'th' => T_('Note'),
						'order' => 'action',
						'td' => '<a href="'.url_add_param( $admin_url, 'ctrl=items&amp;action=history_details&amp;p='.$edited_Item->ID.'&amp;r=' ).'$iver_ID$">$action$</a>',
					);

$Results->cols[] = array(
						'th' => T_('Title'),
						'order' => 'title',
						'td' => '$iver_title$',
					);

function history_td_actions( $iver_ID )
{
	if( (int)$iver_ID == 0 )
	{	// Dont' display a restore link for current version
		return;
	}

	$restore_link = '<a href="'.regenerate_url( 'action', 'action=history_restore&amp;r='.$iver_ID.'&amp;'.url_crumb( 'item' ) ).'">'.T_('Restore').'</a>';

	return $restore_link;
}

$Results->cols[] = array(
						'th' => T_('Actions'),
						'td' => '%history_td_actions( #iver_ID# )%',
						'td_class' => 'shrinkwrap',
					);

$Form = new Form( NULL, '', 'get' );

$Form->hidden_ctrl();
$Form->hidden( 'p', get_param( 'p' ) );
$Form->hidden( 'action', 'history_compare' );

$Form->begin_form();

$Results->display();

$Form->buttonsstart = '';
$Form->buttonsend = '';

$buttons = array();
if( $revisions_count > 1 )
{	// Button to compare the revisions
	$buttons = array( array( 'submit', '', T_('Compare selected revisions'), 'SaveButton' ) );
	echo get_icon( 'multi_action', 'imgtag', array( 'style' => 'margin-left:18px;position:relative;top:-5px;') );
}
$Form->end_form( $buttons );

if( $revisions_count > 2 )
{	// Print JS code for selectors to compare the revisions
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( 'input[name=r1]:eq(1)' ).attr( 'checked', 'checked' );
	jQuery( 'input[name=r2]:eq(0)' ).attr( 'checked', 'checked' );
	<?php /*jQuery( 'input[name=r1]:lt(1)' ).hide();
	jQuery( 'input[name=r2]:gt(0)' ).hide();*/ ?>
} );
jQuery( 'input[name=r1]' ).click( function()
{
	var index = jQuery( 'input[name=r1]' ).index( jQuery( this ) );
	jQuery( 'input[name=r2]:lt(' + index + ')' ).show();
	jQuery( 'input[name=r2]:gt(' + ( index - 1 ) + ')' ).hide();
} );

jQuery( 'input[name=r2]' ).click( function()
{
	var index = jQuery( 'input[name=r2]' ).index( jQuery( this ) );
	jQuery( 'input[name=r1]:gt(' + index + ')' ).show();
	jQuery( 'input[name=r1]:lt(' + ( index + 1 ) + ')' ).hide();
} );
</script>
<noscript><?php /* Display all selectors for browsers withOUT JavaScript */ ?>
<style>
div.results td.shrinkwrap input {
	display: block !important;
}
</style>
</noscript>
<?php
}

?>