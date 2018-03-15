<?php
/**
 * This file implements the post browsing in tracker mode
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Collection, $Blog;
/**
 * @var ItemList2
 */
global $ItemList;

global $edit_item_url, $delete_item_url;
global $Session;

if( $highlight = param( 'highlight', 'integer', NULL ) )
{	// There are lines we want to highlight:
	$result_fadeout = array( 'post_ID' => array($highlight) );
}
elseif ( $highlight = $Session->get( 'highlight_id' ) )
{
	$result_fadeout = array( 'post_ID' => array($highlight) );
	$Session->delete( 'highlight_id' );
}
else
{	// Nothing to highlight
	$result_fadeout = NULL;
}


$ItemList->filter_area = array(
		'callback' => 'callback_filter_item_list_table',
		'hide_filter_button' => true,
	);


/*
	**
	 * Callback to add filters on top of the result set
	 *
	function filter_on_post_title( & $Form )
	{
		global $pagenow, $post_filter;

		$Form->hidden( 'filter_on_post_title', 1 );
		$Form->text( 'post_filter', $post_filter, 20, T_('Task title'), '', 60 );
	}
	$ItemList->filters_callback = 'filter_on_post_title';
*/


$ItemList->title = T_('Task list').get_manual_link( 'task-list' );

$ItemList->cols[] = array(
						'th' => /* TRANS: abbrev for Priority */ T_('Priority'),
						'order' => 'priority',
						'th_class' => 'shrinkwrap',
						'td_class' => '%item_td_task_class( #post_ID#, #post_pst_ID#, "jeditable_cell task_priority_edit" )%',
						'td' => '%item_td_task_cell( "priority", {Obj} )%',
						'extra' => array( 'rel' => '#post_ID#', 'style' => 'background-color: %item_priority_color( "#post_priority#" )%;', 'format_to_output' => false )
					);

$ItemList->cols[] = array(
						'th' => T_('Item/Task'),
						'order' => 'title',
						'td_class' => 'tskst_$post_pst_ID$',
						'td' => '<strong lang="@get(\'locale\')@">%task_title_link( {Obj}, 1, 1 )%</strong>'.
						        ( is_admin_page() ? ' @get_permanent_link( get_icon(\'permalink\'), \'\', \'\', \'auto\' )@' : '' ),
					);

$ItemList->cols[] = array(
						'th' => T_('Assigned'),
						'order' => 'assigned_user_ID',
						'th_class' => 'shrinkwrap',
						'td_class' => '%item_td_task_class( #post_ID#, #post_pst_ID#, "jeditable_cell task_assigned_edit" )%',
						'td' => '%item_td_task_cell( "assigned", {Obj} )%',
						'extra' => array( 'rel' => '#post_ID#', 'format_to_output' => false )
					);

$ItemList->cols[] = array(
						'th' => T_('Status'),
						'order' => 'pst_ID',
						'th_class' => 'shrinkwrap',
						'td_class' => '%item_td_task_class( #post_ID#, #post_pst_ID#, "jeditable_cell task_status_edit" )%',
						'td' => '%item_td_task_cell( "status", {Obj} )%',
						'extra' => array( 'rel' => '#post_ID#', 'data-post-type' => '#post_ityp_ID#', 'format_to_output' => false )
					);


/**
 * Deadline
 */
function deadline( $date )
{
	$timestamp = mysql2timestamp( $date );

	if( $timestamp <= 0 )
	{
		return '&nbsp;';	// IE needs that crap in order to display cell border :/
	}

	return mysql2localedate( $date );
}
$ItemList->cols[] = array(
						'th' => T_('Deadline'),
						'order' => 'post_datedeadline',
						'td_class' => 'shrinkwrap tskst_$post_pst_ID$',
						'td' => '%deadline( #post_datedeadline# )%',
					);


$ItemList->cols[] = array(
		'th' => /* TRANS: abbrev for info */ T_('i'),
		'order' => 'datemodified',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '@history_info_icon()@',
	);

$ItemList->cols[] = array(
		'th' => T_('Actions'),
		'td_class' => 'shrinkwrap',
		'td' => '%item_edit_actions( {Obj} )%',
	);

if( $ItemList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$ItemList->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
}

if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
{	// We have permission to add a post with at least one status:
	$ItemList->global_icon( T_('Create a new task...'), 'new', '?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'&amp;redirect_to='.rawurlencode( regenerate_url( '', '', '', '&' ) ), T_('New task').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}


// EXECUTE the query now:
$ItemList->restart();

// Initialize funky display vars now:
global $postIDlist, $postIDarray;
$postIDlist = $ItemList->get_page_ID_list();
$postIDarray = $ItemList->get_page_ID_array();

// DISPLAY table now:
$ItemList->display( NULL, $result_fadeout );

// Print JS to edit a task priority
echo_editable_column_js( array(
	'column_selector' => '.task_priority_edit',
	'ajax_url'        => get_htsrv_url().'async.php?action=item_task_edit&field=priority&'.url_crumb( 'itemtask' ),
	'options'         => item_priority_titles(),
	'new_field_name'  => 'new_priority',
	'ID_value'        => 'jQuery( this ).attr( "rel" )',
	'ID_name'         => 'post_ID',
	'colored_cells'   => true ) );

// Print JS to edit a task assigned
// Load current blog members into cache:
$UserCache = & get_UserCache();
// Load only first 21 users to know when we should display an input box instead of full users list
$UserCache->load_blogmembers( $Blog->ID, 21, false );
// Init this array only for <select> when we have less than 21 users, otherwise we use <input> field with autocomplete feature
$field_type = count( $UserCache->cache ) < 21 ? 'select' : 'text';

$task_assignees = array( 0 => T_('No user') );
if( $field_type == 'select' )
{
	foreach( $UserCache->cache as $User )
	{
		$task_assignees[ $User->ID ] = $User->login;
	}
}
echo_editable_column_js( array(
	'column_selector' => '.task_assigned_edit',
	'ajax_url'        => get_htsrv_url().'async.php?action=item_task_edit&field=assigned&'.url_crumb( 'itemtask' ),
	'options'         => $task_assignees,
	'new_field_name'  => $field_type == 'select' ? 'new_assigned_ID' : 'new_assigned_login',
	'ID_value'        => 'jQuery( this ).attr( "rel" )',
	'ID_name'         => 'post_ID',
	'field_type'      => $field_type,
	'field_class'     => 'autocomplete_login only_assignees',
	'null_text'       => TS_('No user') ) );

// Print JS to edit a task status
global $DB;
$post_status_types = $DB->get_results( 'SELECT its_ityp_ID, its_pst_ID, pst_name FROM T_items__status_type LEFT JOIN T_items__status ON pst_ID = its_pst_ID' );
$post_statuses = array();
foreach( $post_status_types as $post_status_type )
{
	if( ! isset( $post_statuses[$post_status_type->its_ityp_ID] ) )
	{
		$post_statuses[$post_status_type->its_ityp_ID] = array();
	}

	// Add '_' to don't break a sorting by name on jeditable:
	$post_statuses[$post_status_type->its_ityp_ID]['_'.$post_status_type->its_pst_ID] = $post_status_type->pst_name;
}

?>
<script type="text/javascript">
	var itemStatuses = <?php echo json_encode( $post_statuses );?>;

	function getApplicableStatus( el, selected ) {
		var postType = el.attr( "data-post-type" );
		var statuses = itemStatuses[postType];
		statuses[0] = 'No status';
		statuses['selected'] = selected;

		return statuses;
	}
</script>
<?php
echo_editable_column_js( array(
	'column_selector' => '.task_status_edit',
	'ajax_url'        => get_htsrv_url().'async.php?action=item_task_edit&field=status&'.url_crumb( 'itemtask' ),
	'options'         => 'getApplicableStatus( jQuery( this ), result[1] );',
	'new_field_name'  => 'new_status',
	'ID_value'        => 'jQuery( this ).attr( "rel" )',
	'ID_name'         => 'post_ID' ) );
?>