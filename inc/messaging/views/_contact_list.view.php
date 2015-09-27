<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package messaging
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;
global $current_User, $Settings;
global $unread_messages_count;
global $DB;

if( !isset( $display_params ) )
{
	$display_params = array();
}
$display_params = array_merge( array(
	'show_only_date' => 0,
	'show_columns' => 'login,nickname,name',
	), $display_params );

// show following optional colums
$show_columns = explode( ',', $display_params['show_columns'] );

// Create SELECT query
$select_SQL = new SQL();
$select_SQL->SELECT( 	'mc.mct_to_user_ID, mc.mct_blocked, mc.mct_last_contact_datetime,
						u.user_login AS mct_to_user_login, u.user_nickname AS mct_to_user_nickname,
						CONCAT_WS( " ", u.user_firstname, u.user_lastname ) AS mct_to_user_name,
						u.user_email AS mct_to_user_email, u.user_status AS mct_to_user_status' );

$select_SQL->FROM( 'T_messaging__contact mc
						INNER JOIN T_users u
						ON mc.mct_to_user_ID = u.user_ID' );

$select_SQL->WHERE( 'mc.mct_from_user_ID = '.$current_User->ID );

// Create COUNT quiery

$count_SQL = new SQL();

$count_SQL->SELECT( 'COUNT(*)' );
$count_SQL->FROM( 'T_messaging__contact' );
$count_SQL->WHERE( 'mct_from_user_ID = '.$current_User->ID );

// Get params from request
$s = param( 's', 'string', '', true );
$g = param( 'g', 'integer', 0, true );
$item_ID = param( 'item_ID', 'integer', 0, true );

if( !empty( $s ) )
{	// Filter by keyword
	$select_SQL->WHERE_and( 'CONCAT_WS( " ", u.user_login, u.user_firstname, u.user_lastname, u.user_nickname ) LIKE "%'.$DB->escape($s).'%"' );

	$count_SQL->FROM_add( 'INNER JOIN T_users ON mct_to_user_ID = user_ID' );
	$count_SQL->WHERE_and( 'CONCAT_WS( " ", user_login, user_firstname, user_lastname, user_nickname ) LIKE "%'.$DB->escape($s).'%"' );
}

if( !empty( $g ) )
{	// Filter by group
	$select_SQL->FROM_add( 'LEFT JOIN T_messaging__contact_groupusers ON cgu_user_ID = mct_to_user_ID' );
	$select_SQL->FROM_add( 'LEFT JOIN T_messaging__contact_groups ON cgr_ID = cgu_cgr_ID' );
	$select_SQL->WHERE_and( 'cgu_cgr_ID = '.$DB->quote( $g ) );
	$select_SQL->WHERE_and( 'cgr_user_ID = '.$current_User->ID );

	$count_SQL->FROM_add( 'LEFT JOIN T_messaging__contact_groupusers ON cgu_user_ID = mct_to_user_ID' );
	$count_SQL->FROM_add( 'LEFT JOIN T_messaging__contact_groups ON cgr_ID = cgu_cgr_ID' );
	$count_SQL->WHERE_and( 'cgu_cgr_ID = '.$DB->quote( $g ) );
	$count_SQL->WHERE_and( 'cgr_user_ID = '.$current_User->ID );

	// Get info of filtered group
	$group_filtered_SQL = new SQL();
	$group_filtered_SQL->SELECT( 'cgr_ID AS ID, cgr_name AS name, COUNT(cgu_user_ID) AS count_users' );
	$group_filtered_SQL->FROM( 'T_messaging__contact_groups' );
	$group_filtered_SQL->FROM_add( 'LEFT JOIN T_messaging__contact_groupusers ON cgu_cgr_ID = cgr_ID' );
	$group_filtered_SQL->WHERE( 'cgr_ID = '.$DB->quote( $g ) );
	$group_filtered_SQL->WHERE_and( 'cgr_user_ID = '.$current_User->ID );
	$group_filtered_SQL->GROUP_BY( 'cgr_ID' );

	$group_filtered = $DB->get_row( $group_filtered_SQL->get() );
}

// Create result set:
if( $Settings->get('allow_avatars') )
{
	$default_order = '--A';
}
else
{
	$default_order = '-A';
}


$Results = new Results( $select_SQL->get(), 'mct_', $default_order, NULL, $count_SQL->get() );

$Results->title = T_('Contacts list');
if( is_admin_page() )
{ // Set an url for manual page:
	$Results->title .= get_manual_link( 'contacts-list' );
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_contacts( & $Form )
{
	global $item_ID;

	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );

	$Form->select_input( 'g', get_param('g'), 'get_contacts_groups_options', T_('Group') );

	if( $item_ID > 0 )
	{	// Save item ID for the filter request
		$Form->hidden( 'item_ID', $item_ID );
	}
}

// Get all groups of current user
$user_groups_SQL = new SQL();
$user_groups_SQL->SELECT( 'cgr_ID AS ID, cgr_name AS name' );
$user_groups_SQL->FROM( 'T_messaging__contact_groups' );
$user_groups_SQL->WHERE( 'cgr_user_ID = '.$current_User->ID );
$user_groups_SQL->ORDER_BY( 'cgr_ID' );
$user_groups = $DB->get_results( $user_groups_SQL->get() );

if( $item_ID > 0 )
{	// Save item ID in the filter url
	$filter_url = url_add_param( get_dispctrl_url( 'contacts' ), 'item_ID='.$item_ID );
}
else
{
	$filter_url = get_dispctrl_url( 'contacts' );
}

$filter_presets = array(
		'all' => array( T_('All'), $filter_url ),
	);
foreach( $user_groups as $g => $group )
{	// Set user groups to quick filter
	$filter_presets[] = array( $group->name, url_add_param( $filter_url, 'g='.$group->ID ) );
	if( $g >= 6 )
	{	// Use only first 7 groups
		break;
	}
}

$Results->filter_area = array(
	'callback' => 'filter_contacts',
	'presets' => $filter_presets
	);

/**
 * Get block/unblock icon
 *
 * @param block value
 * @param user ID
 * @return icon
 */
function contact_block( $block, $user_ID, $user_status )
{
	if( $user_status == 'closed' )
	{
		return '';
	}

	// set action url
	$action_url = regenerate_url();
	if( !is_admin_page() )
	{ // in front office the action will be processed by messaging module handle_htsrv_action() through action.php
		$action_url = get_samedomain_htsrv_url().'action.php?mname=messaging&disp=contacts&redirect_to='.rawurlencode( $action_url );
	}

	if( $block == 0 )
	{
		return action_icon( T_('Block contact'), 'file_allowed', $action_url.'&action=block&user_ID='.$user_ID.'&amp;'.url_crumb('messaging_contacts') );
	}
	else
	{
		return action_icon( T_('Unblock contact'), 'file_not_allowed', $action_url.'&action=unblock&user_ID='.$user_ID.'&amp;'.url_crumb('messaging_contacts') );
	}
}

function contacts_checkbox( $user_ID, $user_status )
{
	if( $user_status == 'closed' )
	{ // contact account was closed
		return '<input type="checkbox" name="contacts[]" title="'.T_('This contact is closed').'" value="'.$user_ID.'" disabled="disabled"/>';
	}

	$selected_users = explode( ',', param( 'users', 'string', '' ) );
	if( in_array( $user_ID, $selected_users ) )
	{
		$checked = ' checked="checked"';
	}
	else
	{
		$checked = '';
	}
	return '<input type="checkbox" name="contacts[]" title="'.T_('Select this contact').'" value="'.$user_ID.'"'.$checked.' />';
}
$Results->cols[] = array(
					'th' => '',
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '%contacts_checkbox( #mct_to_user_ID#, #mct_to_user_status# )%',
					);

if( $Settings->get('allow_avatars') )
{
	/**
	 * Get user avatar
	 *
	 * @param integer user ID
	 * @return string
	 */
	function user_avatar( $user_ID )
	{
		global $Blog;

		$UserCache = & get_UserCache();
		$User = & $UserCache->get_by_ID( $user_ID, false, false );
		if( $User )
		{
			if( empty( $Blog ) )
			{
				$avatar_size = 'crop-top-32x32';
			}
			else
			{
				$avatar_size = $Blog->get_setting('image_size_messaging');
			}
			$avatar_tag = $User->get_avatar_imgtag( $avatar_size );
			$identity_url = get_user_identity_url( $user_ID );
			if( !empty( $avatar_tag ) )
			{
				if( empty( $identity_url ) )
				{ // current_User has no permission to view user settings, and Blog is empty
					return $avatar_tag;
				}
				return '<a href="'.$identity_url.'">'.$avatar_tag.'</a>';
			}
		}
		return '';
	}
	$Results->cols[] = array(
						'th' => T_('Picture'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%user_avatar( #mct_to_user_ID# )%',
						);
}

if( in_array( 'login', $show_columns ) )
{
	function user_login( $user_ID, $link = true )
	{
		$UserCache = & get_UserCache();
		$User = & $UserCache->get_by_ID( $user_ID, false, false );
		if( $User )
		{
			if( $link )
			{
				$login_text = get_user_identity_link( $User->login, $User->ID, 'user', 'login' );
				if( $User->check_status( 'is_closed' ) )
				{ // add (closed account) note to corresponding contacts!
					$login_text .= '<span class="note">('.T_( 'closed account' ).')</span>';
				}
				return $login_text;
			}
			return $User->login;
		}
		return '';
	}
	$Results->cols[] = array(
						'th' => T_('Login'),
						'order' => 'mct_to_user_login',
						'td' => '%user_login( #mct_to_user_ID# )%',
						);
}

$Results->cols[] = array(
					'th' => T_('S'),
					'th_title' => T_('Status'),
					'order' => 'mct_blocked',
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '%contact_block( #mct_blocked#, #mct_to_user_ID#, #mct_to_user_status# )%',
					);

if( in_array( 'nickname', $show_columns ) )
{
$Results->cols[] = array(
					'th' => T_('Nickname'),
					'order' => 'mct_to_user_nickname',
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '$mct_to_user_nickname$',
					);
}

if( in_array( 'name', $show_columns ) )
{
$Results->cols[] = array(
					'th' => T_('Name'),
					'order' => 'mct_to_user_name',
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '$mct_to_user_name$',
					);
}


/**
 * Get user email
 *
 * @param email
 * @return string
 */
function user_mailto( $email )
{
	if( !empty( $email ) )
	{
		return action_icon( T_('Email').': '.$email, 'email', 'mailto:'.$email, T_('Email') );
	}
	return '';
}

function last_contact( $date, $show_only_date, $user_ID )
{
	//global $show_only_date;
	if( $show_only_date )
	{
		$data = mysql2localedate( $date );
	}
	else
	{
		$data = mysql2localedatetime( $date );
	}

	$login = user_login( $user_ID, false );
	if( $login != '' )
	{
		$threads_url = get_dispctrl_url( 'threads', 'colselect_submit=Filter+list&amp;u='.$login );
		$data = '<a href="'.$threads_url.'">'.$data.'</a>';
	}

	return $data;
}

$Results->cols[] = array(
	'th' => /* TRANS: time related */ T_('Last contact'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'shrinkwrap',
	'td' => '%last_contact(#mct_last_contact_datetime#, '.$display_params[ 'show_only_date' ].', #mct_to_user_ID#)%'
);


function contacts_groups( $user_ID )
{
	global $current_User, $DB, $cache_user_contacts_groups;

	if( !is_array( $cache_user_contacts_groups ) )
	{	// Execute only first time to init cache
		$cache_user_contacts_groups = array();

		// Get contacts of current user
		$groups_SQL = new SQL();
		$groups_SQL->SELECT( 'cgr_ID AS ID, cgu_user_ID AS user_ID, cgr_name AS name' );
		$groups_SQL->FROM( 'T_messaging__contact_groupusers' );
		$groups_SQL->FROM_add( 'LEFT JOIN T_messaging__contact_groups ON cgu_cgr_ID = cgr_ID' );
		$groups_SQL->WHERE( 'cgr_user_ID = '.$current_User->ID );
		$groups_SQL->ORDER_BY( 'cgr_name' );
		$groups = $DB->get_results( $groups_SQL->get() );

		$remove_link = url_add_param( get_dispctrl_url( 'contacts' ), 'action=remove_user&amp;view=contacts&amp;'.url_crumb( 'messaging_contacts' ) );

		foreach( $groups as $group )
		{	// Init cache for groups for each contact of current user
			$group_name = $group->name.action_icon( T_('Remove user from this group'), 'remove', url_add_param( $remove_link, 'user_ID='.$group->user_ID.'&amp;group_ID='.$group->ID ) );
			if( isset( $cache_user_contacts_groups[$group->user_ID] ) )
			{	// nth group of this user
				$cache_user_contacts_groups[$group->user_ID] .= '<br />'.$group_name;
			}
			else
			{	// first group of this user
				$cache_user_contacts_groups[$group->user_ID] = $group_name;
			}
		}
	}

	if( isset( $cache_user_contacts_groups[$user_ID] ) )
	{	// user has groups
		echo $cache_user_contacts_groups[$user_ID];
	}
}

$Results->cols[] = array(
					'th' => T_('Groups'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'left nowrap',
					'td' => '%contacts_groups( #mct_to_user_ID# )%',
					);

$Results->display( $display_params );

if( count( $Results->rows ) > 0 )
{	// Display actions buttons
	global $module_contacts_list_params;
	modules_call_method( 'get_contacts_list_params' );

	$Form = new Form( get_dispctrl_url( 'contacts' ), 'add_group_contacts' );

	echo '<div class="form_send_contacts">';

	$multi_action_icon = get_icon( 'multi_action', 'imgtag', array( 'style' => 'margin:0 2px 0 14px;position:relative;top:-5px;') );

	$Form->button_input( array(
			'type'    => 'button',
			'value'   => $module_contacts_list_params['title_selected'],
			'onclick' => 'location.href=\''.$module_contacts_list_params['recipients_link'].'\'',
			'id'      => 'send_selected_recipients',
			'input_prefix' => $multi_action_icon
		) );

	echo '</div>';

	$Form->switch_layout( 'none' );
	$Form->switch_template_parts( array(
			'formstart'  => '<div class="form_add_contacts">',
			'labelstart' => '<span class="label">',
			'labelend'   => '</span> <span class="controls">',
			'formend'    => '</div>',
	) );

	$Form->begin_form();

	$Form->add_crumb( 'messaging_contacts' );
	$Form->hidden( 'users', '' );
	if( isset( $module_contacts_list_params['form_hiddens'] ) && !empty( $module_contacts_list_params['form_hiddens'] ) )
	{	// Append the hidden input elements from module
		foreach( $module_contacts_list_params['form_hiddens'] as $hidden_input )
		{
			$Form->hidden( $hidden_input['name'], $hidden_input['value'] );
		}
	}

	$Form->combo_box( 'group', param( 'group_combo', 'string', '' ), get_contacts_groups_options( param( 'group', 'string', '-1' ), false ), $multi_action_icon.T_('Add all selected contacts to this group'), array( 'new_field_size' => '8' ) );

	$Form->buttons( array( array( 'submit', 'actionArray[add_group]', T_('Add'), 'SaveButton btn-primary btn-sm' ) ) );

	echo '</span>';

	if( isset( $group_filtered ) )
	{	// Contacts list is filtered by group
		echo '<div id="edit_group_contacts" style="white-space:normal">';

		$Form->hidden( 'group_ID', $group_filtered->ID );

		echo '<p class="center">'.sprintf( T_( 'Selected group: <b>%s</b>' ), $group_filtered->name ).'</p>';

		echo '<input id="send_group_recipients" type="button" onclick="location.href=\''.$module_contacts_list_params['recipients_link'].'&amp;group_ID='.$group_filtered->ID.'\'" value="'.sprintf( $module_contacts_list_params['title_group'], $group_filtered->count_users, $group_filtered->name ).'" style="margin: 1ex 0" /><br />';

		$Form->text_input( 'name', $group_filtered->name, 20, T_('Rename this group to') );

		$Form->button_input( array(
				'name' => 'actionArray[rename_group]',
				'value' => T_('Rename'),
				'class' => 'SaveButton'
			) );
		echo ' &nbsp; <b class="nowrap" style="padding-top:1em;line-height:32px">'.T_('or').' &nbsp; ';
		$Form->button_input( array(
				'name' => 'actionArray[delete_group]',
				'value' => T_('Delete this group'),
				'class' => 'SaveButton',
				'onclick' => 'return confirm("'.TS_('Are you sure want to delete this group?').'")'
			) );
		echo '</b>';

		echo '</div>';
	}

	$Form->end_form();
	$Form->switch_layout( NULL );

?>
<script type="text/javascript">
jQuery( '#send_selected_recipients' ).click( function()
{ // Add selected users to this link
	var recipients_param = '';
	var recipients = get_selected_users();
	if( recipients.length > 0 )
	{
		recipients_param = '&recipients=' + recipients;
	}
	location.href = '<?php echo str_replace( '&amp;', '&', $module_contacts_list_params['recipients_link'] ); ?>' + recipients_param;
	return false;
} );

jQuery( '#add_group_contacts' ).submit( function()
{
	jQuery( 'input[name=users]' ).val( get_selected_users() );
} );

function get_selected_users()
{
	var users = '';
	jQuery( 'input[name^=contacts]' ).each( function()
	{
		if( jQuery( this ).is( ':checked' ) )
		{
			users += jQuery( this ).val() + ',';
		}
	} );

	if( users.length > 0 )
	{ // Delete last comma
		users = users.substr( 0, users.length-1 );
	}

	return users;
}
</script>
<?php
}

?>