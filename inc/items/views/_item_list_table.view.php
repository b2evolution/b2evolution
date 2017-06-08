<?php
/**
 * This file implements the post browsing in tracker mode
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
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
global $tab, $tab_type;
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


$ItemList->title = sprintf( /* TRANS: list of "posts"/"intros"/"custom types"/etc */ T_('"%s" list'), $tab_type ).get_manual_link( 'browse-edit-tab' );

// Initialize Results object
items_results( $ItemList, array(
		'tab' => $tab,
	) );

if( $ItemList->is_filtered() )
{ // List is filtered, offer option to reset filters:
	$ItemList->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
}

// Generate global icons depending on seleted tab with item type
item_type_global_icons( $ItemList );

// EXECUTE the query now:
$ItemList->restart();

// Initialize funky display vars now:
global $postIDlist, $postIDarray;
$postIDlist = $ItemList->get_page_ID_list();
$postIDarray = $ItemList->get_page_ID_array();

// DISPLAY table now:
$ItemList->display( NULL, $result_fadeout );

?>
<script type="text/javascript">
jQuery(document).ready( function()
{
	jQuery( '.item_order_edit' ).each( function()
	{
		if( jQuery( this ).find( 'a' ).length == 0 )
		{	// To remove editable action from item which are not allowed to edit for current user:
			jQuery( this ).removeClass( 'item_order_edit' );
		}
	} );
<?php
// Print JS to edit an item order:
echo_editable_column_js( array(
	'column_selector' => '.item_order_edit',
	'ajax_url'        => get_htsrv_url().'async.php?action=item_order_edit&'.url_crumb( 'itemorder' ),
	'field_type'      => 'text',
	'new_field_name'  => 'new_item_order',
	'ID_value'        => 'jQuery( this ).attr( "rel" )',
	'ID_name'         => 'post_ID',
	'print_init_tags' => false
) );
?>
});
</script>