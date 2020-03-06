<?php
/**
 * This file implements the riight sidebar for the post browsing screen.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;
/**
 * @var Blog
 */
global $Collection, $Blog;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * @var ItemList
 */
global $ItemList;

$pp = $ItemList->param_prefix;

global $tab;
global ${$pp.'flagged'}, ${$pp.'mustread'}, ${$pp.'show_past'}, ${$pp.'show_future'}, ${$pp.'show_statuses'},
		${$pp.'s'}, ${$pp.'sentence'}, ${$pp.'exact'}, ${$pp.'author'}, ${$pp.'author_login'}, ${$pp.'assgn'},
		${$pp.'assgn_login'}, ${$pp.'status'}, ${$pp.'types'};

$flagged = ${$pp.'flagged'};
$mustread = ${$pp.'mustread'};
$show_past = ${$pp.'show_past'};
$show_future = ${$pp.'show_future'};
$show_statuses = ${$pp.'show_statuses'};
$s = ${$pp.'s'};
$sentence = ${$pp.'sentence'};
$exact = ${$pp.'exact'};
$author = ${$pp.'author'};
$author_login = ${$pp.'author_login'};
$assgn = ${$pp.'assgn'};
$assgn_login = ${$pp.'assgn_login'};
$status = ${$pp.'status'};
$types = ${$pp.'types'};


load_funcs( 'skins/_skin.funcs.php' );

$Form = new Form( NULL, 'item_filter_form', 'get', 'none' );

$Form->begin_form( 'evo_sidebar_filters' );

$Form->hidden_ctrl();
$Form->hidden( 'tab', $tab );
$Form->hidden( 'blog', $Blog->ID );
// Set this hidden in order to open custom filters area when filters were applied:
$Form->hidden( $pp.'filter_preset', 'custom' );

echo '<div class="filter_buttons">';
	if( $ItemList->is_filtered() )   // NOTE: this works (contrary to UserList)
	{	// TODO: style this better:
		echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;'.$pp.'filter_preset=&amp;filter=reset" class="btn btn-warning" style="margin-right: 5px">';
		echo get_icon( 'filter' ).' '.T_('Remove filters').'</a>';
	}

	$Form->button_input( array(
			'tag'   => 'button',
			'value' => get_icon( 'filter' ).' './* TRANS: Verb */ T_('Apply filters'),
			'class' => 'search btn-info',
		) );
echo '</div>';

$UserCache = & get_UserCache();

// KEYWORDS:
$Form->begin_fieldset( T_('Keywords'), array( 'id' => 'items_filter_keywords', 'fold' => true, 'default_fold' => false ) );
?>
<div class="tile"><input type="text" name="<?php echo $pp ?>s" size="20" value="<?php echo htmlspecialchars($s) ?>" class="SearchField form-control" /></div>
<div class="tile">
	<input type="radio" name="<?php echo $pp ?>sentence" value="AND" id="sentAND" class="radio" <?php if( $sentence=='AND' ) echo 'checked="checked" '?> />
	<label for="sentAND"><?php echo T_('AND') ?></label>
</div>
<div class="tile">
	<input type="radio" name="<?php echo $pp ?>sentence" value="OR" id="sentOR" class="radio" <?php if( $sentence=='OR' ) echo 'checked="checked" '?> />
	<label for="sentOR"><?php echo T_('OR') ?></label>
</div>
<div class="tile">
	<input type="radio" name="<?php echo $pp ?>sentence" value="sentence" id="sentence" class="radio" <?php if( $sentence=='sentence' ) echo 'checked="checked" '?> />
	<label for="sentence"><?php echo T_('Entire phrase') ?></label>
</div>
<div class="tile">
	<input type="checkbox" name="<?php echo $pp ?>exact" value="1" id="exact" class="checkbox" <?php if( $exact ) echo 'checked="checked" '?> />
	<label for="exact"><?php echo T_('Exact match') ?></label>
</div>

<?php
$Form->end_fieldset();

// DATE:
if( get_param( 'tab_type' ) == 'post' )
{
	$fold_date = ( $ItemList->default_filters['ymdhms'] == $ItemList->filters['ymdhms'] );
	$Form->begin_fieldset( T_('Date'), array( 'id' => 'items_filter_date', 'fold' => true, 'default_fold' => $fold_date ) );

	// CALENDAR:
	// Call the Calendar plugin:
	$Plugins->call_by_code( 'evo_Calr', array( // Params follow:
		'block_start'     => '',
		'block_end'       => '',
		'title'           => '',        // No title.
		'link_type'       => 'context', // Preserve page context
		'itemlist_prefix' => $pp        // Prefix of the ItemList object
	) );

	$Form->end_fieldset();
}

// ASSIGNEE:
// TODO: allow multiple selection
if( $Blog->get_setting( 'use_workflow' ) )
{	// Display only if workflow is enabled:

	// Load only first 21 users to know when we should display an input box instead of full users list
	$UserCache->load_blogmembers( $Blog->ID, 21, false );
	$user_count = count( $UserCache->cache );

	$fold_assignee = ( $ItemList->default_filters['assignees'] == $ItemList->filters['assignees'] ) || ( $ItemList->filters['assignees'] == 0 );
	$Form->begin_fieldset( T_('Assignee'), array( 'id' => 'items_filter_assignee', 'fold' => true, 'default_fold' => $fold_assignee ) );

	if( $user_count )
	{
		echo '<ul>';

		echo '<li><input type="radio" name="'.$pp.'assgn" value="0" class="radio"';
		if( empty( $assgn ) ) echo ' checked="checked"';
		echo ' /> <a href="'.regenerate_url( $pp.'assgn', $pp.'assgn=0' ).'">'.T_('Any').'</a></li>';

		echo '<li><input type="radio" name="'.$pp.'assgn" value="-" class="radio"';
		if( '-' == $assgn ) echo ' checked="checked"';
		echo ' /> <a href="'.regenerate_url( $pp.'assgn', $pp.'assgn=-' ).'">'.T_('Not assigned').'</a></li>';

		if( $user_count > 20 )
		{ // Display an input box to enter user login
			echo '<li>';
			echo T_('User').': <input type="text" class="form_text_input autocomplete_login" value="'.$assgn_login.'" name="'.$pp.'assgn_login" id="'.$pp.'assgn_login" />';
			echo '</li>';
		}
		else
		{ // Display a list of users
			foreach( $UserCache->cache as $loop_User )
			{
				echo '<li><input type="radio" name="'.$pp.'assgn" value="'.$loop_User->ID.'" class="radio"';
				if( $loop_User->ID == $assgn ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( $pp.'assgn', $pp.'assgn='.$loop_User->ID ).'" rel="bubbletip_user_'.$loop_User->ID.'">';
				echo $loop_User->get_colored_login( array( 'login_text' => 'name' ) );
				echo '</a></li>';
			}
		}
		echo '</ul>';
	}
	?>
	<script>
	jQuery( '#<?php echo $pp; ?>assgn_login' ).focus( function()
	{
		jQuery( 'input[name=<?php echo $pp; ?>assgn]' ).removeAttr( 'checked' );
	} );
	jQuery( 'input[name=<?php echo $pp; ?>assgn]' ).click( function()
	{
		jQuery( '#<?php echo $pp; ?>assgn_login' ).val( '' );
	} );
	</script>
	<?php
	$Form->end_fieldset();
}

// STATUS:
// TODO: allow multiple selection
$ItemStatusCache = & get_ItemStatusCache();
$ItemStatusCache->load_all(); // TODO: load for current blog only
if( count( $ItemStatusCache->cache ) )
{	// Display only if at least one status exists in DB:
	$fold_status = ( $ItemList->default_filters['statuses'] == $ItemList->filters['statuses'] );
	$Form->begin_fieldset( T_('Status'), array( 'id' => 'items_filter_status', 'fold' =>true, 'default_fold' => empty( $status ) ) );
	echo '<ul>';

	echo '<li><input type="radio" name="'.$pp.'status" value="-" class="radio"'.( $status == '-' ? ' checked="checked"' : '' ).' />';
	echo ' <a href="'.regenerate_url( $pp.'status', $pp.'status=-' ).'">'.T_('Without status').'</a></li>';

	foreach( $ItemStatusCache->cache as $loop_Obj )
	{
		echo '<li><input type="radio" name="'.$pp.'status" value="'.$loop_Obj->ID.'" class="radio"'.( $status == $loop_Obj->ID ? ' checked="checked"' : '' ).' />';
		echo ' <a href="'.regenerate_url( $pp.'status', $pp.'status='.$loop_Obj->ID ).'">';
		$loop_Obj->disp('name');
		echo '</a></li>';
	}
	echo '</ul>';
	$Form->end_fieldset();
}

// ITEMS TO SHOW:
$fold_flagged       = ( $ItemList->default_filters['flagged'] == $ItemList->filters['flagged'] );
$fold_mustread      = ( $ItemList->default_filters['mustread'] == $ItemList->filters['mustread'] );
$fold_show_past     = ( $ItemList->default_filters['ts_min'] == $ItemList->filters['ts_min'] );
$fold_show_future   = ( $ItemList->default_filters['ts_max'] == $ItemList->filters['ts_max'] );
$fold_show_statuses = ( $ItemList->default_filters['visibility_array'] == $ItemList->filters['visibility_array'] );
$fold_items_to_show = $fold_flagged && $fold_mustread && $fold_show_past && $fold_show_future && $fold_show_statuses;
$Form->begin_fieldset( T_('Items to show'), array( 'id' => 'items_filter_show_item', 'fold' => true, 'default_fold' => $fold_items_to_show ) );
?>
<div style="margin-bottom:5px">
	<input type="checkbox" name="<?php echo $pp ?>flagged" value="1" id="flagged" class="checkbox" <?php if( $flagged ) echo 'checked="checked" '?> />
	<label for="flagged"><?php echo T_('Flagged') ?></label><br />
</div>

<div style="margin-bottom:5px">
	<input type="checkbox" name="<?php echo $pp ?>mustread" value="1" id="mustread" class="checkbox" <?php if( $mustread ) echo 'checked="checked" '?> />
	<label for="mustread"><?php echo T_('Must read') ?></label><br />
</div>

<div style="margin-bottom:5px">

<input type="checkbox" name="<?php echo $pp ?>show_past" value="1" id="ts_min" class="checkbox" <?php if( $show_past ) echo 'checked="checked" '?> />
<label for="ts_min"><?php echo T_('Past') ?></label><br />

<input type="checkbox" name="<?php echo $pp ?>show_future" value="1" id="ts_max" class="checkbox" <?php if( $show_future ) echo 'checked="checked" '?> />
<label for="ts_max"><?php echo T_('Future') ?></label>

</div>

<?php
// Get those statuses that current User can't view in this blog, and don't display those as filters
$exclude_statuses = array_merge( get_restricted_statuses( $Blog->ID, 'blog_post!' ), array( 'trash' ) );
$statuses = get_visibility_statuses( 'notes-array', $exclude_statuses );
foreach( $statuses as $status_key => $status_name )
{ // show statuses
	?>
	<input type="checkbox" name="<?php echo $pp ?>show_statuses[]" value="<?php echo $status_key; ?>" id="sh_<?php echo $status_key; ?>" class="checkbox" <?php if( in_array( $status_key, $show_statuses ) ) echo 'checked="checked" '?> />
	<label for="sh_<?php echo $status_key; ?>" title="<?php echo substr( $status_name[1], 1, strlen( $status_name[1] ) - 2 ); ?>"><?php echo $status_name[0] ?></label><br />
	<?php
}
$Form->end_fieldset();

// ITEM TYPES:
$tab_type = ( get_param( 'tab' ) == 'type' ) ? get_param( 'tab_type' ) : '';

$item_types_SQL = new SQL();
$item_types_SQL->SELECT( 'ityp_ID AS ID, ityp_name AS name, ityp_perm_level AS perm_level,
	IF( ityp_ID = "'.$Blog->get_setting( 'default_post_type' ).'", 0, 1 ) AS fix_order' );
$item_types_SQL->FROM( 'T_items__type' );
$item_types_SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_ityp_ID = ityp_ID AND itc_coll_ID = '.$Blog->ID );
if( ! empty( $tab_type ) )
{ // Get item types only by selected back-office tab
	$item_types_SQL->WHERE( 'ityp_usage IN ( '.$DB->quote( get_item_type_usage_by_tab( $tab_type ) ).' )' );
}
$item_types_SQL->ORDER_BY( 'fix_order, ityp_ID' );
$item_types = $DB->get_results( $item_types_SQL->get() );
$fold_item_type = ( $ItemList->default_filters['types'] == $ItemList->filters['types'] );
$Form->begin_fieldset( T_('Item Type'), array( 'id' => 'items_filter_item_types', 'fold' => true, 'default_fold' => $fold_item_type ) );
echo '<ul>';
echo '<li><input type="radio" name="'.$pp.'types" value="" class="radio"'.( $types == '' || empty( $types ) ? ' checked="checked"' : '' ).' />';
echo ' <a href="'.regenerate_url( $pp.'types', $pp.'types=' ).'">'.T_('Any').'</a></li>';

foreach( $item_types as $loop_Obj )
{
	echo '<li><input type="radio" name="'.$pp.'types" value="'.$loop_Obj->ID.'" class="radio"'.( $types == $loop_Obj->ID ? ' checked="checked"' : '' ).' />';
	echo ' <a href="'.regenerate_url( $pp.'types', $pp.'types='.$loop_Obj->ID ).'">';
	echo $loop_Obj->name;
	echo '</a></li>';
}
echo '</ul>';
$Form->end_fieldset();

// AUTHOR:
// TODO: allow multiple selection
// Load only first 21 users to know when we should display an input box instead of full users list
$UserCache->load_blogmembers( $Blog->ID, 21 );
$user_count = count( $UserCache->cache );
$fold_authors = ( $ItemList->default_filters['authors'] == ( empty( $ItemList->filters['authors'] ) ? NULL : $ItemList->filters['authors'] ) );
$Form->begin_fieldset( T_('Author'), array( 'id' => 'items_filter_author', 'fold' => true, 'default_fold' => $fold_authors ) );
if( $user_count )
{
	if( $user_count > 20 )
	{	// Display an input box to enter user login:
		echo '<label for="'.$pp.'author_login">'.T_('User').':</label> <input type="text" class="form-control middle autocomplete_login" value="'.format_to_output( $author_login, 'formvalue' ).'" name="'.$pp.'author_login" id="'.$pp.'author_login" />';
	}
	else
	{	// Display a list of users:
		echo '<ul>'
			.'<li>'
				.'<input type="radio" name="'.$pp.'author" value="0" class="radio"'.( empty( $author ) ? ' checked="checked"' : '' ).' /> '
				.'<a href="'.regenerate_url( $pp.'author', $pp.'author=0' ).'">'.T_('Any').'</a>'
			.'</li>';
		foreach( $UserCache->cache as $loop_User )
		{
			echo '<li>'
				.'<input type="radio" name="'.$pp.'author" value="'.$loop_User->ID.'" class="radio"'.( $loop_User->ID == $author ? ' checked="checked"' : '' ).' /> '
				.'<a href="'.regenerate_url( $pp.'author', $pp.'author='.$loop_User->ID ).'" rel="bubbletip_user_'.$loop_User->ID.'">'
					.$loop_User->get_colored_login( array( 'login_text' => 'name' ) )
				.'</a>'
			.'</li>';
		}
		echo '</ul>';
	}
}
$Form->end_fieldset();

// CATEGORIES:
$fold_cat_array = ( $ItemList->default_filters['cat_array'] == $ItemList->filters['cat_array'] );
$fold_cat_single = ( $ItemList->default_filters['cat_single'] == $ItemList->filters['cat_single'] );
$fold_cat_modifier = ( $ItemList->default_filters['cat_modifier'] == $ItemList->filters['cat_modifier'] )
		|| !in_array( $ItemList->filters['cat_modifier'], array( '-', '*' ) );
$fold_categories = $fold_cat_array && $fold_cat_single && $fold_cat_modifier;
$Form->begin_fieldset( T_('Categories'), array( 'id' => 'items_filter_categories', 'fold' => true, 'default_fold' => $fold_categories ) );
// --------------------------------- START OF CATEGORY LIST --------------------------------
skin_widget( array(
	// CODE for the widget:
	'widget' => 'coll_category_list',
	// Optional display params
	'block_start'         => '<div class="widget_core_coll_category_list">',
	'block_end'           => '</div>',
	'title'               => '',
	'block_display_title' => false,
	'link_type'           => 'context',
	'display_checkboxes'  => 1,
	'show_locked'         => true,
	'max_colls'           => 15,
) );
// ---------------------------------- END OF CATEGORY LIST ---------------------------------
$Form->end_fieldset();

// ARCHIVE:
$fold_archives = ( $ItemList->default_filters['ymdhms'] == $ItemList->filters['ymdhms'] );
$fold_week = ( $ItemList->default_filters['week'] == $ItemList->filters['week'] );
$Form->begin_fieldset( T_('Archive'), array( 'id' => 'items_filter_archives', 'fold' => true, 'default_fold' => $fold_archives && $fold_week) );
// Call the Archives plugin:
$Plugins->call_by_code( 'evo_Arch', array( // Parameters follow:
	'block_start'     => '',
	'block_end'       => '',
	'title'           => '',
	'link_type'       => 'context', // Preserve page context
	'form'            => true,      // add form fields (radio buttons)
	'limit'           => '',        // No limit
	'more_link'       => '',        // No more link
	'itemlist_prefix' => $pp,       // Prefix of the ItemList object
) );
$Form->end_fieldset();

$Form->end_form();

// Enable JS for fieldset folding:
echo_fieldset_folding_js();
?>
