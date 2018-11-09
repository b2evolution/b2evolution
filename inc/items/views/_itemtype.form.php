<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'items/model/_itemtype.class.php', 'ItemType' );

global $edited_Itemtype, $thumbnail_sizes, $admin_url;

// Determine if we are creating or updating...
global $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'itemtype_checkchanges' );

if( $edited_Itemtype->ID > 0 )
{
	$default_ids = ItemType::get_default_ids();
	if( ! in_array( $edited_Itemtype->ID, $default_ids ) )
	{	// Allow delete item type only if it is not default of blogs:
		$Form->global_icon( T_('Delete this Item Type!'), 'delete', regenerate_url( 'action', 'action=delete&amp;crumb_itemtype='.get_crumb( 'itemtype' ) ) );
	}
}
$Form->global_icon( T_('Cancel editing').'!', 'close', regenerate_url( 'action,ityp_ID' ) );

$Form->begin_form( 'fform', ( $edited_Itemtype->ID > 0 ? T_('Edit Item Type') : T_('New Item Type') ) );

$Form->add_crumb( 'itemtype' );
$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',ityp_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

$Form->begin_fieldset( T_('General').get_manual_link('item-type-general') );

	$Form->hidden( 'ityp_ID', $edited_Itemtype->ID );

	$ItemTypeCache = & get_ItemTypeCache();
	$Form->select_input_array( 'ityp_usage', $edited_Itemtype->usage, $ItemTypeCache->get_usage_option_array(), T_('Usage'), '', array( 'required' => true ) );

	// Display a field to edit a name:
	$Form->text_input( 'ityp_name', $edited_Itemtype->name, 50, T_('Name'), '', array( 'maxlength' => 30, 'required' => true ) );

	$Form->textarea_input( 'ityp_description', $edited_Itemtype->description, 2, T_('Description'), array( 'cols' => 47 ) );
	$Form->radio( 'ityp_perm_level', $edited_Itemtype->perm_level, array(
			array( 'standard',   T_('Standard') ),
			array( 'restricted', T_('Restricted') ),
			array( 'admin',      T_('Admin') )
		), T_('Permission level') );
	$Form->text_input( 'ityp_template_name', $edited_Itemtype->template_name, 25, T_('Template name'), T_('b2evolution will automatically append .main.php or .disp.php'), array( 'maxlength' => 40 ) );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Structured Data').get_manual_link( 'item-type-structured-data' ) );
	$Form->select_input_array( 'ityp_schema', $edited_Itemtype->get( 'schema' ), ityp_schema_titles( true, true ), T_('Schema'), '', array( 'force_keys_as_values' => true ) );
	$Form->checkbox( 'ityp_add_aggregate_rating', $edited_Itemtype->add_aggregate_rating, '', T_('Add Aggregate Rating') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Use of Instructions').get_manual_link( 'item-type-instructions' ), array( 'id' => 'itemtype_instructions' ) );
	$Form->text_input( 'ityp_evobar_link_text', $edited_Itemtype->evobar_link_text, 25, T_('New Item link in evobar'), T_('Leave empty for default') );
	$Form->text_input( 'ityp_skin_btn_text', $edited_Itemtype->skin_btn_text, 25, T_('New Item button in skin'), T_('Leave empty for default') );
	$Form->checklist( array(
		array( 'ityp_front_instruction', 1, T_('In front-office edit screen'),$edited_Itemtype->front_instruction ),
		array( 'ityp_back_instruction', 1, T_('In back-office edit screen'), $edited_Itemtype->back_instruction )
	), 'ityp_instruction_enable', T_('Display instructions') );
	$Form->textarea_input( 'ityp_instruction', $edited_Itemtype->instruction, 5, T_('Instructions'), array( 'cols' => 47 ) );
$Form->end_fieldset();

$options = array(
		array( 'required', T_('Required') ),
		array( 'optional', T_('Optional') ),
		array( 'never', T_('Never') )
	);

// Check if current type is intro and set specific params for the fields "ityp_allow_breaks" and "ityp_allow_featured":
$intro_type_disabled = $edited_Itemtype->is_intro();
$intro_type_note = $intro_type_disabled ? T_('This feature is not compatible with Intro posts.') : '';

$Form->begin_fieldset( T_('Features').get_manual_link( 'item-type-features' ), array( 'id' => 'itemtype_features' ) );
	$Form->radio( 'ityp_use_short_title', $edited_Itemtype->use_short_title, array(
			array( 'optional', T_('Optional') ),
			array( 'never', T_('Never') ),
		), T_('Use short title') );
	$Form->radio( 'ityp_use_title', $edited_Itemtype->use_title, $options, T_('Use title') );
	$Form->radio( 'ityp_use_text', $edited_Itemtype->use_text, $options, T_('Use text') );
	$Form->checkbox( 'ityp_allow_html', $edited_Itemtype->allow_html, T_('Allow HTML'), T_( 'Check to allow HTML in posts.' ).' ('.T_('HTML code will pass several sanitization filters.').')' );
	$Form->checkbox( 'ityp_allow_breaks', $edited_Itemtype->allow_breaks, T_('Allow Teaser and Page breaks'), $intro_type_note, '', 1, $intro_type_disabled );
	$Form->checkbox( 'ityp_allow_attachments', $edited_Itemtype->allow_attachments, T_('Allow attachments') );
	$Form->checkbox( 'ityp_allow_featured', $edited_Itemtype->allow_featured, T_('Allow featured'), $intro_type_note, '', 1, $intro_type_disabled );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Use of Advanced Properties').get_manual_link( 'item-type-advanced-properties' ), array( 'id' => 'itemtype_advprops' ) );
	$Form->radio( 'ityp_use_tags', $edited_Itemtype->use_tags, $options, T_('Use tags') );
	$Form->radio( 'ityp_use_excerpt', $edited_Itemtype->use_excerpt, $options, T_('Use excerpt') );
	$Form->radio( 'ityp_use_url', $edited_Itemtype->use_url, $options, T_('Use URL') );
	$Form->checkbox( 'ityp_podcast', $edited_Itemtype->podcast, '', T_('Treat as Podcast Media') );
	$Form->radio( 'ityp_use_parent', $edited_Itemtype->use_parent, $options, T_('Use Parent ID') );
	$Form->radio( 'ityp_use_title_tag', $edited_Itemtype->use_title_tag, $options, htmlspecialchars( T_('Use <title> tag') ) );
	$Form->radio( 'ityp_use_meta_desc', $edited_Itemtype->use_meta_desc, $options, htmlspecialchars( T_('Use <meta> description') ) );
	$Form->radio( 'ityp_use_meta_keywds', $edited_Itemtype->use_meta_keywds, $options, htmlspecialchars( T_('Use <meta> keywords') ) );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Use of Location').get_manual_link( 'item-type-location' ), array( 'id' => 'itemtype_location' ) );
	$Form->radio( 'ityp_use_country', $edited_Itemtype->use_country, $options, T_('Use country') );
	$Form->radio( 'ityp_use_region', $edited_Itemtype->use_region, $options, T_('Use region') );
	$Form->radio( 'ityp_use_sub_region', $edited_Itemtype->use_sub_region, $options, T_('Use sub-region') );
	$Form->radio( 'ityp_use_city', $edited_Itemtype->use_city, $options, T_('Use city') );
	$Form->radio( 'ityp_use_coordinates', $edited_Itemtype->use_coordinates, $options, T_('Use coordinates'), false, T_('Turn this on to be able to set the location coordinates and view on map.') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Use of Comments').get_manual_link( 'item-type-comments' ), array( 'id' => 'itemtype_comments' ) );
	$Form->checkbox( 'ityp_use_comments', $edited_Itemtype->use_comments, T_('Use comments'), T_('Also see collection\'s feedback options') );
	$Form->textarea_input( 'ityp_comment_form_msg', $edited_Itemtype->comment_form_msg, 3, T_('Message before comment form') );
	$Form->checkbox( 'ityp_allow_comment_form_msg', $edited_Itemtype->allow_comment_form_msg, T_('Allow custom message for each post'), T_('Check to allow a different custom message before comment form for each post.') );
	$Form->checkbox( 'ityp_allow_closing_comments', $edited_Itemtype->allow_closing_comments, T_('Allow closing comments'), T_('Check to allow closing comments on individual items/posts.') );
	$Form->checkbox( 'ityp_allow_disabling_comments', $edited_Itemtype->allow_disabling_comments, T_('Allow disabling comments'), T_('Check to allow disabling comments on individual items/posts.') );
	$Form->radio( 'ityp_use_comment_expiration', $edited_Itemtype->use_comment_expiration, $options, T_('Use comment expiration') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Item Availability').get_manual_link( 'item-type-availability' ), array( 'id' => 'itemtype_availability' ) );
	$Form->checkbox_input( 'ityp_can_be_purchased_instore', $edited_Itemtype->get( 'can_be_purchased_instore' ), T_('Can be purchased in store') );
	$Form->checkbox_input( 'ityp_can_be_purchased_online', $edited_Itemtype->get( 'can_be_purchased_online' ), T_('Can be purchased online') );
$Form->end_fieldset();

// Custom fields:
$Table = new Table( 'Results' );
$Table->title = T_('Custom Fields').get_manual_link( 'item-type-custom-fields' );

$Table->cols = array(
	array( 'th' => T_('Order'), 'th_class' => 'shrinkwrap' ),
	array( 'th' => T_('Title') ),
	array( 'th' => T_('Name'), 'th_class' => 'shrinkwrap' ),
	array( 'th' => T_('Type'), 'th_class' => 'shrinkwrap' ),
	array( 'th' => T_('Format'), 'th_class' => 'shrinkwrap' ),
	array( 'th' => T_('Public'), 'td_class' => 'shrinkwrap' ),
	array( 'th' => T_('Line highlight'), 'th_class' => 'shrinkwrap' ),
	array( 'th' => T_('Green highlight'), 'th_class' => 'shrinkwrap' ),
	array( 'th' => T_('Red highlight'), 'th_class' => 'shrinkwrap' ),
	array( 'th' => T_('Actions'), 'td_class' => 'shrinkwrap' ),
);

$custom_field_types = get_item_type_field_types();

$Table->display_init();

// ******** START OF Custom Field Templates,
// Used for existing custom field row in the table below and also for JS code to add new custom field:
$custom_field_templates = array();

/**
 * Store input elements depending on custom field type in the array
 *
 * @param string|array New template
 * @param string|array Include field types
 * @param array All templates
 */
function custom_field_edit_form_template( $new_templates, $limit_field_types, & $custom_field_templates )
{
	$custom_field_types = get_item_type_field_types();
	$c = count( $custom_field_templates ) + 1;
	// End previous template:
	$custom_field_templates[ $c - 1 ] = ob_get_clean();

	if( ! is_array( $new_templates ) )
	{
		$new_templates = array( $new_templates );
		$limit_field_types = array( $limit_field_types );
	}

	foreach( $new_templates as $n => $new_template )
	{
		$exclude_field_types = array();
		$include_field_types = explode( ',', $limit_field_types[ $n ] );
		foreach( $include_field_types as $i => $field_type )
		{	// Find which types should be excluded:
			if( substr( $field_type, 0, 1 ) == '-' )
			{	// Exclude this field type:
				$exclude_field_types[] = substr( $field_type, 1 );
				unset( $include_field_types[ $i ] );
			}
		}
		foreach( $custom_field_types as $custom_field_type => $custom_field_type_title )
		{
			if( in_array( $custom_field_type, $include_field_types ) ||
			    ( ! empty( $exclude_field_types ) && ! in_array( $custom_field_type, $exclude_field_types ) ) )
			{	// The given template is applied for the field type:
				$custom_field_templates[ $c ][ $custom_field_type ] = ( $new_template == '$custom_field_type_title$' ? $custom_field_type_title : $new_template );
			}
		}
	}

	// Start next template:
	ob_start();
}

ob_start();

$Table->display_line_start();

// Order
$Table->display_col_start();
echo '<input type="text" name="cf_order$cf_num$" value="$cf_order$" class="form_text_input form-control custom_field_order" maxlength="11" size="3" />';
// Hidden options which are stored in DB or used as additional data:
echo '<input type="hidden" name="cf_schema_prop$cf_num$" value="$cf_schema_prop$" />';
echo '<input type="hidden" name="cf_ID$cf_num$" value="$cf_ID$" />';
echo '<input type="hidden" name="cf_type$cf_num$" value="$cf_type$" />';
echo '<input type="hidden" name="cf_note$cf_num$" value="$cf_note$" />';
custom_field_edit_form_template( '<input type="hidden" name="cf_formula$cf_num$" value="$cf_formula$" />', 'computed', $custom_field_templates );
echo '<input type="hidden" name="cf_header_class$cf_num$" value="$cf_header_class$" />';
custom_field_edit_form_template( '<input type="hidden" name="cf_cell_class$cf_num$" value="$cf_cell_class$" />', '-separator', $custom_field_templates );
custom_field_edit_form_template( '<input type="hidden" name="cf_link$cf_num$" value="$cf_link$" />'
	.'<input type="hidden" name="cf_link_nofollow$cf_num$" value="$cf_link_nofollow$" />'
	.'<input type="hidden" name="cf_link_class$cf_num$" value="$cf_link_class$" />', '-text,-html,-separator', $custom_field_templates );
echo '<input type="hidden" name="cf_description$cf_num$" value="$cf_description$" />';
custom_field_edit_form_template( '<input type="hidden" name="cf_merge$cf_num$" value="$cf_merge$" />', '-separator', $custom_field_templates );
// Create this <hidden> to know this custom field is new created field:
echo '<input type="hidden" name="cf_new$cf_num$" value="$cf_new$" />';
$Table->display_col_end();

// Title
$Table->display_col_start();
echo '<input type="text" name="cf_label$cf_num$" value="$cf_label$" class="form_text_input form-control custom_field_label $cf_label_class$" maxlength="255" />';
$Table->display_col_end();

// Name
$Table->display_col_start();
echo '<input type="text" name="cf_name$cf_num$" value="$cf_name$" class="form_text_input form-control custom_field_name $cf_name_class$" maxlength="255" />';
$Table->display_col_end();

// Type
$Table->display_col_start();
custom_field_edit_form_template( '$custom_field_type_title$', '-', $custom_field_templates );
$Table->display_col_end();

// Format
$Table->display_col_start();
custom_field_edit_form_template( array(
		'<input type="text" name="cf_format$cf_num$" value="$cf_format$" class="form_text_input form-control custom_field_format" size="20" maxlength="2000" />',
		'<select name="cf_format$cf_num$" class="form-control custom_field_format">'.Form::get_select_options_string( array_keys( $thumbnail_sizes ) ).'</select>'
	), array( 'double,computed,separator,url', 'image' ), $custom_field_templates );
$Table->display_col_end();

// Public
$Table->display_col_start();
echo '<input type="checkbox" name="cf_public$cf_num$" value="1" />';
$Table->display_col_end();

// Line highlight
$Table->display_col_start();
custom_field_edit_form_template( '<select name="cf_line_highlight$cf_num$" class="form-control custom_field_line_highlight">'
		.Form::get_select_options_string( get_item_type_field_highlight_options( 'line' ), NULL, true )
	.'</select>', '-separator', $custom_field_templates );
$Table->display_col_end();

// Green highlight
$Table->display_col_start();
custom_field_edit_form_template( '<select name="cf_green_highlight$cf_num$" class="form-control custom_field_green_highlight">'
		.Form::get_select_options_string( get_item_type_field_highlight_options( 'green' ), NULL, true )
	.'</select>', '-separator', $custom_field_templates );
$Table->display_col_end();

// Red highlight
$Table->display_col_start();
custom_field_edit_form_template( '<select name="cf_red_highlight$cf_num$" class="form-control custom_field_red_highlight">'
		.Form::get_select_options_string( get_item_type_field_highlight_options( 'red' ), NULL, true )
	.'</select>', '-separator', $custom_field_templates );
$Table->display_col_end();

// Actions
$Table->display_col_start();
echo get_icon( 'edit', 'imgtag', array( 'title' => T_('Edit custom field'), 'class' => 'edit_custom_field action_icon', 'style' => 'color:#337ab7' ) ).' ';
echo get_icon( 'minus', 'imgtag', array( 'title' => T_('Remove custom field'), 'class' => 'delete_custom_field action_icon' ) ).' ';
echo get_icon( 'add', 'imgtag', array( 'title' => T_('Duplicate custom field'), 'class' => 'duplicate_custom_field action_icon' ) );
$Table->display_col_end();

$Table->display_line_end();

$custom_field_templates[] = ob_get_clean();
// ******** END OF Custom Field Templates.

echo '<div class="custom_fields_edit_table">';

echo $Table->params['before'];

$custom_fields_names = array();
$deleted_custom_fields = param( 'deleted_custom_fields', 'string', '' );
$i = 1;

// TITLE:
$Table->display_head();

$custom_fields = $edited_Itemtype->get_custom_fields( 'all', 'ID' );

// TABLE START:
$Table->display_list_start();

if( empty( $custom_fields ) )
{	// Hide table header when no custom fields yet:
	$Table->params['head_start'] = update_html_tag_attribs( $Table->params['head_start'], array( 'style' => 'display:none' ) );
}
// COLUMN HEADERS:
$Table->display_col_headers();

// BODY START:
$Table->display_body_start();

foreach( $custom_fields as $custom_field )
{
	$type = $custom_field['type'];
	if( isset( $custom_field['temp_i'] ) )
	{ // Get i from this temp number when form was is submitted
		$i = $custom_field['temp_i'];
	}
	$custom_ID = $custom_field['ID'];
	if( !empty( $deleted_custom_fields ) && ( strpos( $deleted_custom_fields, $custom_ID ) !== false ) )
	{
		continue;
	}
	$custom_field_name = $custom_field['name'];
	$custom_field_label = $custom_field['label'];
	$custom_field_label_class = '';
	$custom_field_name_class = '';
	$custom_fields_data = get_param( 'custom_fields_data' );
	if( empty( $custom_field_label ) )
	{ // When user saves new field without title
		$custom_field_label = ( isset( $custom_fields_data->{'label'.$i} ) ? $custom_fields_data->{'label'.$i} : NULL );
		$custom_field_label_class = 'field_error new_custom_field_title';
	}
	if( empty( $custom_field_name ) )
	{ // When user saves new field without name
		$custom_field_name = ( isset( $custom_fields_data->{'name'.$i} ) ? $custom_fields_data->{'name'.$i} : NULL );
		$custom_field_name_class = 'field_error';
		if( empty( $custom_field_label_class ) )
		{ // The field "Title" mast have this class to auto-fill the field "Name"
			$custom_field_label_class = 'new_custom_field_title';
		}
	}
	if( empty( $custom_field_name_class ) && in_array( $custom_field_name, $custom_fields_names ) )
	{ // Mark the duplicated name
		$custom_field_name_class = 'field_error';
	}
	$custom_fields_names[] = $custom_field_name;

	// Display row of custom field:
	$custom_field_type_template = '';
	foreach( $custom_field_templates as $custom_field_template )
	{
		if( is_array( $custom_field_template ) )
		{
			if( isset( $custom_field_template[ $type ] ) )
			{
				$custom_field_type_template .= $custom_field_template[ $type ];
			}
		}
		else
		{
			$custom_field_type_template .= $custom_field_template;
		}
	}
	// Replace masks with values of the custom field:
	$cf_input_replacements = array(
		'$cf_ID$'            => $custom_ID,
		'$cf_new$'           => ( isset( $custom_fields_data->{'new'.$i} ) ? $custom_fields_data->{'new'.$i} : 0 ),
		'$cf_num$'           => $i,
		'$cf_type$'          => format_to_output( $custom_field['type'], 'htmlattr' ),
		'$cf_order$'         => format_to_output( $custom_field['order'], 'htmlattr' ),
		'$cf_label$'         => format_to_output( $custom_field['label'], 'htmlattr' ),
		'$cf_name$'          => format_to_output( $custom_field_name, 'htmlattr' ),
		'$cf_schema_prop$'   => format_to_output( $custom_field['schema_prop'], 'htmlattr' ),
		'$cf_label_class$'   => $custom_field_label_class,
		'$cf_name_class$'    => $custom_field_name_class,
		'$cf_format$'        => format_to_output( $custom_field['format'], 'htmlattr' ),
		'$cf_formula$'       => format_to_output( $custom_field['formula'], 'htmlattr' ),
		'$cf_header_class$'  => format_to_output( $custom_field['header_class'], 'htmlattr' ),
		'$cf_cell_class$'    => format_to_output( $custom_field['cell_class'], 'htmlattr' ),
		'$cf_link$'          => format_to_output( $custom_field['link'], 'htmlattr' ),
		'$cf_link_nofollow$' => format_to_output( $custom_field['link_nofollow'], 'htmlattr' ),
		'$cf_link_class$'    => format_to_output( $custom_field['link_class'], 'htmlattr' ),
		'$cf_note$'          => format_to_output( $custom_field['note'], 'htmlattr' ),
		'$cf_description$'   => format_to_output( $custom_field['description'], 'htmlspecialchars' ),
		'$cf_merge$'         => format_to_output( $custom_field['merge'], 'htmlattr' ),
	);
	$cf_select_replacements = array( 'format', 'line_highlight', 'green_highlight', 'red_highlight' );
	$custom_field_type_template = str_replace( array_keys( $cf_input_replacements ), $cf_input_replacements, $custom_field_type_template );
	foreach( $cf_select_replacements as $cf_select_field )
	{	// Set a selected option:
		$custom_field_type_template = preg_replace( '/(<select[^>]+name="cf_'.$cf_select_field.'.+<option value="'.preg_quote( $custom_field[ $cf_select_field ], '/' ).'")/', '$1 selected="selected"', $custom_field_type_template );
	}
	if( $custom_field[ 'public' ] )
	{	// Enabled public option:
		$custom_field_type_template = preg_replace( '/(<input type="checkbox"[^>]+name="cf_public[^"]+")/', '$1 checked="checked"', $custom_field_type_template );
	}
	echo $custom_field_type_template;

	$i++;
	evo_flush();
}

// BODY END:
$Table->display_body_end();

// TABLE END:
$Table->display_list_end();

// Display a button to add new custom field:
$add_custom_fields_button = '<div class="btn-group dropdown" id="add_custom_field">'
	.'<span class="btn-primary btn btn-sm" data-type="double">'.get_icon( 'new' ).' '.T_('Numeric').'</span>'
	.'<button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"> <span class="caret"></span></button>'
	.'<ul class="dropdown-menu dropdown-menu-left" role="menu">';
	foreach( $custom_field_types as $custom_field_type => $custom_field_type_title )
	{
		$add_custom_fields_button .= '<li role="presentation"><a href="#" role="menuitem" tabindex="-1" data-type="'.$custom_field_type.'">'.get_icon( 'new' ).' '.$custom_field_type_title.'</a></li>';
	}
$add_custom_fields_button .= '</ul></div>';
$Form->info_field( T_('Add new field of type'), $add_custom_fields_button, array( 'class' => 'info_full_height' ) );

// Add fields from another item type:
$SQL = new SQL( 'Get Item Types with custom fields' );
$SQL->SELECT( 'ityp_ID, ityp_name' );
$SQL->FROM( 'T_items__type' );
$SQL->FROM_add( 'INNER JOIN T_items__type_custom_field ON itcf_ityp_ID = ityp_ID' );
$SQL->WHERE( 'ityp_ID != '.$DB->quote( $edited_Itemtype->ID ) );
$SQL->GROUP_BY( 'ityp_ID' );
$SQL->ORDER_BY( 'ityp_name' );
$item_type_with_custom_fields = $DB->get_assoc( $SQL );
$Form->select_input_array( 'another_item_type', '', $item_type_with_custom_fields, T_('Add fields from another item type'), '', array(
		'force_keys_as_values' => true,
		'field_suffix'         => '<button id="select_other_fields" type="button" class="btn btn-default">'.T_('Select fields').'...</button>',
	) );

echo '<input type="hidden" name="count_custom_fields'.'" value='.( $i - 1 ).' />';
echo '<input type="hidden" name="deleted_custom_fields" value="'.$deleted_custom_fields.'" />';

echo $Table->params['after'];
echo '</div>';

// Item Statuses allowed for the editing Item Type:
$SQL = new SQL();
if( $edited_Itemtype->ID )
{
	$SQL->SELECT( 'pst_ID, pst_name, its_ityp_ID' );
	$SQL->FROM( 'T_items__status' );
	$SQL->FROM_add( 'JOIN T_items__type' );
	$SQL->FROM_add( 'LEFT JOIN T_items__status_type ON its_ityp_ID = ityp_ID AND its_pst_ID = pst_ID' );
	$SQL->WHERE( 'ityp_ID = '.$edited_Itemtype->ID );
}
else
{
	$SQL->SELECT( 'pst_ID, pst_name, NULL AS its_ityp_ID' );
	$SQL->FROM( 'T_items__status' );
}

$Results = new Results( $SQL->get(), 'pst_' );
$Results->title = T_('Item Statuses allowed for this Item Type').get_manual_link( 'item-statuses-allowed-per-item-type' );
$Results->cols[] = array(
		'th' => T_('ID'),
		'th_class' => 'shrinkwrap',
		'td' => '$pst_ID$',
		'td_class' => 'center'
	);

function item_status_type_checkbox( $row )
{
	$title = $row->pst_name;
	$r = '<input type="checkbox"';
	$r .= ' name="status_'.$row->pst_ID.'"';

	if( isset( $row->its_ityp_ID ) && ! empty( $row->its_ityp_ID ) )
	{
		$r .= ' checked="checked"';
	}

	$r .= ' class="checkbox" value="1" title="'.$title.'" />';

	return $r;
}

$Results->cols[] = array(
		'th' => T_('Allowed Item Status'),
		'th_class' => 'shrinkwrap',
		'td' => '%item_status_type_checkbox( {row} )%',
		'td_class' => 'center'
	);

function get_name_for_itemstatus( $id, $name )
{
	global $current_User;

	if( $current_User->check_perm( 'options', 'edit' ) )
	{ // Not reserved id AND current User has permission to edit the global settings
		$ret_name = '<a href="'.regenerate_url( 'ctrl,action,ID,pst_ID', 'ctrl=itemstatuses&amp;pst_ID='.$id.'&amp;action=edit' ).'">'.$name.'</a>';
	}
	else
	{
		$ret_name = $name;
	}

	return '<strong>'.$ret_name.'</strong>';
}

$Results->cols[] = array(
		'th' => T_('Name'),
		'td' => '%get_name_for_itemstatus( #pst_ID#, #pst_name# )%'
	);

$display_params = array(
		'page_url' => 'admin.php?ctrl=itemtypes&ityp_ID='.$edited_Itemtype->ID.'&action=edit'
	);

$Results->checkbox_toggle_selectors = 'input[name^=status_]:checkbox';
$Results->display( $display_params );


$item_status_IDs = array();
if( $Results->result_num_rows > 0 )
{	// If at least one item status exists in DB:
	foreach( $Results->rows as $row )
	{
		$item_status_IDs[] = $row->pst_ID;
	}
}
$Form->hidden( 'item_status_IDs', implode( ',', $item_status_IDs ) );


if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ),
													array( 'submit', 'actionArray[update_edit]', T_('Save and continue editing...'), 'SaveButton' ) ) );
}

load_funcs( 'regional/model/_regional.funcs.php' );
echo_regional_required_js( 'ityp_use_' );

// Initialize JavaScript to build and open window:
echo_modalwindow_js();
?>
<script type="text/javascript">
function guidGenerator()
{
	var S4 = function()
	{
		return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
	};
	return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4());
}

function add_new_custom_field( type, duplicated_field_obj, duplicated_field_data )
{
	var new_field_mode = 'new';
	// Set values:
	var field_value_label = '';
	var field_value_name = '';
	var field_value_schema_prop = '';
	var field_value_order = '';
	var field_value_note = '';
	var field_value_format = '';
	var field_value_formula = '';
	var field_value_header_class = ( type == 'separator' ? 'left' : 'right' ) + ' nowrap';
	var field_value_cell_class = ( type == 'double' || type == 'computed' ) ? 'right' : ( type == 'separator' ? '' : 'center' );
	var field_value_link = 'nolink';
	var field_value_link_nofollow = 0;
	var field_value_link_class = '';
	var field_value_line_highlight = '';
	var field_value_green_highlight = '';
	var field_value_red_highlight = '';
	var field_value_public = '';
	var field_value_description = '';
	var field_value_merge = '';
	if( typeof( duplicated_field_obj ) != 'undefined' && duplicated_field_obj !== false && duplicated_field_obj.length > 0 )
	{	// Get data from duplicated field of the current editing Item Type:
		new_field_mode = 'duplicate_empty';
		if( typeof( duplicated_count_custom_field ) == 'undefined' )
		{
			duplicated_count_custom_field = 0;
		}
		duplicated_count_custom_field++;
		field_value_label = duplicated_field_obj.find( 'input[name^="cf_label"]' ).val();
		field_value_name = duplicated_field_obj.find( 'input[name^="cf_name"]' ).val() + '_' + duplicated_count_custom_field;
		field_value_schema_prop = duplicated_field_obj.find( 'input[name^="cf_schema_prop"]' ).val();
		field_value_order = duplicated_field_obj.find( 'input[name^="cf_order"]' ).val();
		field_value_note = duplicated_field_obj.find( 'input[name^="cf_note"]' ).val();
		field_value_format = duplicated_field_obj.find( '[name^="cf_format"]' ).val();
		field_value_formula = duplicated_field_obj.find( 'input[name^="cf_formula"]' ).val();
		field_value_header_class = duplicated_field_obj.find( 'input[name^="cf_header_class"]' ).val();
		field_value_cell_class = duplicated_field_obj.find( 'input[name^="cf_cell_class"]' ).val();
		field_value_link = duplicated_field_obj.find( 'input[name^="cf_link"]' ).val();
		field_value_link_nofollow = duplicated_field_obj.find( 'input[name^="cf_link_nofollow"]' ).is( ':checked' );
		field_value_link_class = duplicated_field_obj.find( 'input[name^="cf_link_class"]' ).val();
		field_value_line_highlight = duplicated_field_obj.find( 'select[name^="cf_line_highlight"]' ).val();
		field_value_green_highlight = duplicated_field_obj.find( 'select[name^="cf_green_highlight"]' ).val();
		field_value_red_highlight = duplicated_field_obj.find( 'select[name^="cf_red_highlight"]' ).val();
		field_value_public = duplicated_field_obj.find( 'input[name^="cf_public"]' ).is( ':checked' );
		field_value_description = duplicated_field_obj.find( 'input[name^="cf_description"]' ).val();
		field_value_merge = duplicated_field_obj.find( 'input[name^="cf_merge"]' ).val();
	}
	else if( typeof( duplicated_field_data ) != 'undefined' && duplicated_field_data.length > 0 )
	{	// Get data from duplicated field from another selected Item Type:
		new_field_mode = 'duplicate_from';
		field_value_label = duplicated_field_data.data( 'label' );
		field_value_name = duplicated_field_data.data( 'name' );
		field_value_schema_prop = duplicated_field_data.data( 'schema_prop' );
		field_value_order = duplicated_field_data.data( 'order' );
		field_value_note = duplicated_field_data.data( 'note' );
		field_value_format = duplicated_field_data.data( 'format' );
		field_value_formula = duplicated_field_data.data( 'formula' );
		field_value_header_class = duplicated_field_data.data( 'header_class' );
		field_value_cell_class = duplicated_field_data.data( 'cell_class' );
		field_value_link = duplicated_field_data.data( 'link' );
		field_value_link_nofollow = duplicated_field_data.data( 'link_nofollow' );
		field_value_link_class = duplicated_field_data.data( 'link_class' );
		field_value_line_highlight = duplicated_field_data.data( 'line_highlight' );
		field_value_green_highlight = duplicated_field_data.data( 'green_highlight' );
		field_value_red_highlight = duplicated_field_data.data( 'red_highlight' );
		field_value_public = duplicated_field_data.data( 'public' );
		field_value_description = duplicated_field_data.data( 'description' );
		field_value_merge = duplicated_field_data.data( 'merge' );
	}

	var count_custom = jQuery( 'input[name=count_custom_fields]' ).val();
	count_custom++;

	var cf_inputs = {};
	<?php
	// Initialize JS var for each custom field type:
	foreach( $custom_field_types as $custom_field_type => $custom_field_type_title )
	{
		$custom_field_type_template = '';
		foreach( $custom_field_templates as $custom_field_template )
		{
			if( is_array( $custom_field_template ) )
			{
				if( isset( $custom_field_template[ $custom_field_type ] ) )
				{
					$custom_field_type_template .= $custom_field_template[ $custom_field_type ];
				}
			}
			else
			{
				$custom_field_type_template .= $custom_field_template;
			}
		}
		echo 'cf_inputs["'.$custom_field_type.'"] = \''.format_to_js( $custom_field_type_template )."';\r\n";
	}
	?>
	// Replace masks with values:
	var custom_field_type_inputs = cf_inputs[ type ]
		.replace( '$cf_ID$', guidGenerator() )
		.replace( '$cf_new$', 1 )
		.replace( /\$cf_num\$/g, count_custom )
		.replace( '$cf_type$', type )
		.replace( '$cf_order$', field_value_order )
		.replace( '$cf_label$', field_value_label )
		.replace( '$cf_name$', field_value_name )
		.replace( '$cf_schema_prop$', field_value_schema_prop )
		.replace( '$cf_label_class$', 'new_custom_field_title' )
		.replace( '$cf_name_class$', '' )
		.replace( '$cf_format$', field_value_format )
		.replace( '$cf_formula$', field_value_formula )
		.replace( '$cf_header_class$', field_value_header_class )
		.replace( '$cf_cell_class$', field_value_cell_class )
		.replace( '$cf_link$', field_value_link )
		.replace( '$cf_link_nofollow$', field_value_link_nofollow ? 1 : 0 )
		.replace( '$cf_link_class$', field_value_link_class )
		.replace( '$cf_note$', field_value_note )
		.replace( '$cf_description$', field_value_description )
		.replace( '$cf_merge$', field_value_merge );

	if( new_field_mode == 'new' )
	{	// Set values of the select and hidden inputs for new creating field:
		var cf_select_defaults = {
		// Default values for select options depending on custom field type:
			double:   { line_highlight: 'differences', link: 'nolink' },
			computed: { line_highlight: 'differences', link: 'nolink' },
			varchar:  { line_highlight: 'differences', link: 'nolink' },
			text:     { line_highlight: 'differences' },
			html:     { line_highlight: 'differences' },
			url:      { line_highlight: 'differences', link: 'fieldurl' },
			image:    { format: 'fit-192x192', link: 'linkpermzoom' },
		};
		if( typeof( cf_select_defaults[ type ] ) != 'undefined' )
		{
			for( var cf_select_field in cf_select_defaults[ type ] )
			{	// Set default value for select options:
				var cf_field_regexp = new RegExp( '(<select[^>]+name="cf_' + cf_select_field + '.+<option value="' + cf_select_defaults[ type ][ cf_select_field ] + '")' );
				custom_field_type_inputs = custom_field_type_inputs.replace( cf_field_regexp, '$1 selected="selected"' );
			}
		}
		custom_field_type_inputs = custom_field_type_inputs.replace( /(<input type="checkbox"[^>]+name="cf_public[^"]+")/, '$1 checked="checked"' );
	}

	// Insert a row of new adding field:
	if( new_field_mode == 'new' || new_field_mode == 'duplicate_from' )
	{	// Insert in the end of the custom fields table:
		jQuery( '.custom_fields_edit_table table tbody' ).append( custom_field_type_inputs );
	}
	else
	{	// Insert right after the duplicated field:
		duplicated_field_obj.after( custom_field_type_inputs );
	}

	if( new_field_mode == 'duplicate_empty' || new_field_mode == 'duplicate_from' )
	{	// Set values of the select and hidden inputs for new duplicated field:
		var new_field_obj = ( new_field_mode == 'duplicate_empty' ?
			duplicated_field_obj.next() :
			jQuery( '.custom_fields_edit_table table tbody tr:last' ) );
		new_field_obj.find( 'select[name^="cf_format"]' ).val( field_value_format );
		new_field_obj.find( 'select[name^="cf_line_highlight"]' ).val( field_value_line_highlight );
		new_field_obj.find( 'select[name^="cf_green_highlight"]' ).val( field_value_green_highlight );
		new_field_obj.find( 'select[name^="cf_red_highlight"]' ).val( field_value_red_highlight );
		new_field_obj.find( 'input[name^="cf_public"]' ).prop( 'checked', field_value_public );
	}

	// Update a count of custom fields:
	jQuery( 'input[name=count_custom_fields]' ).attr( 'value', count_custom );

	if( jQuery( '.custom_fields_edit_table table thead' ).is( ':hidden' ) )
	{	// Display table column headers when first row has been added:
		jQuery( '.custom_fields_edit_table table thead' ).show();
	}
}

jQuery( '#add_custom_field [data-type]' ).click( function()
{
	add_new_custom_field( jQuery( this ).data( 'type' ) );
	return false;
} );

// Duplicate custom field:
jQuery( document ).on( 'click', '.duplicate_custom_field', function()
{
	var field_row_obj = jQuery( this ).closest( 'tr' );
	var field_type = field_row_obj.find( '[name^=cf_type]' ).val();
	add_new_custom_field( field_type, field_row_obj );
} );

// Delete custom field:
jQuery( document ).on( 'click', '.delete_custom_field', function()
{
	if( confirm( '<?php echo TS_('Are you sure want to delete this custom field?\nThe update will be performed when you will click on the \'Save Changes!\' button.'); ?>' ) )
	{ // Delete custom field only from html form, This field will be removed after saving of changes
		var field_row_obj = jQuery( this ).closest( 'tr' );
		if( field_row_obj.find( 'input[name^=cf_new][value=0]' ).length )
		{
			var deleted_fields_value = jQuery( '[name=deleted_custom_fields]' ).val();
			if( deleted_fields_value )
			{
				deleted_fields_value = deleted_fields_value + ',';
			}
			jQuery( '[name=deleted_custom_fields]' ).val( deleted_fields_value + field_row_obj.find( '[name^=cf_ID]' ).val() );
		}
		field_row_obj.remove();
		if( jQuery( '.custom_fields_edit_table table tbody tr' ).length == 0 )
		{
			jQuery( '.custom_fields_edit_table table thead' ).hide();
		}
	}
} );

// Edit custom field:
jQuery( document ).on( 'click', '.edit_custom_field', function()
{
	var field_row_obj = jQuery( this ).closest( 'tr' );
	openModalWindow( '<span class="loader_img absolute_center" title="<?php echo T_('Loading...'); ?>"></span>',
		'80%', '', true,
		'<?php echo TS_('Edit custom field'); ?>: ' + field_row_obj.find( '[name^=cf_label]' ).val(),
		'<?php echo TS_('Update'); ?>', true, true );
	var field_options = {};
	field_row_obj.find( '[name^=cf_]' ).each( function()
	{
		var option_val = jQuery( this ).attr( 'type' ) == 'checkbox' ? ( jQuery( this ).prop( 'checked' ) ? 1 : 0 ) : jQuery( this ).val();
		field_options[ 'itcf_' + jQuery( this ).attr( 'name' ).replace( /^cf_([^\d]+)\d+$/, '$1' ) ] = option_val;
	} );
	jQuery.ajax(
	{
		type: 'GET',
		url: '<?php echo $admin_url; ?>',
		data: jQuery.extend(
		{
			'ctrl': 'itemtypes',
			'action': 'edit_custom_field',
			'display_mode': 'js',
		}, field_options ),
		success: function( result )
		{
			openModalWindow( result, '80%', '', true,
				'<?php echo TS_('Edit custom field'); ?>: ' + field_row_obj.find( '[name^=cf_label]' ).val(),
				'<?php echo TS_('Update'); ?>', false, true );
		}
	} );
	field_row_obj.removeClass( 'evo_highlight' );
	return false;
} );
jQuery( document ).on( 'submit', 'form#itemtype_edit_field', function()
{
	var field_ID = jQuery( '[name=itcf_ID]', this ).val();
	var field_row_obj = jQuery( '[name^=cf_ID][value=' + field_ID + ']' ).closest( 'tr' );
	if( field_row_obj.length > 0 )
	{	// Update field options:
		jQuery( '[name^=itcf_]', this ).each( function()
		{
			var option_name = jQuery( this ).attr( 'name' ).replace( 'itcf_', '' );
			if( jQuery( this ).attr( 'type' ) == 'checkbox' )
			{	// Checkbox:
				if( field_row_obj.find( '[name^=cf_' + option_name + ']' ).attr( 'type' ) == 'checkbox' )
				{
					field_row_obj.find( '[name^=cf_' + option_name + ']' ).prop( 'checked', jQuery( this ).prop( 'checked' ) );
				}
				else
				{
					field_row_obj.find( '[name^=cf_' + option_name + ']' ).val( jQuery( this ).prop( 'checked' ) ? 1 : 0 );
				}
			}
			else
			{	// Input, select, textarea:
				field_row_obj.find( '[name^=cf_' + option_name + ']' ).val( jQuery( this ).val() );
			}
		} );
		field_row_obj.addClass( 'evo_highlight' );
	}
	closeModalWindow();
	return false;
} );

jQuery( document ).on( 'keyup', '.new_custom_field_title', function()
{ // Prefill new field name
	jQuery( this ).closest( 'tr' ).find( '.custom_field_name' ).val( parse_custom_field_name( jQuery( this ).val() ) );
} );

jQuery( document ).on( 'blur', '.custom_field_name', function()
{ // Remove incorrect chars from field name on blur event
	jQuery( this ).val( parse_custom_field_name( jQuery( this ).val() ) );
} );

function parse_custom_field_name( field_name )
{
	return field_name.substr( 0, 36 ).replace( /[^a-z0-9\-_]/ig, '_' ).toLowerCase();
}

// Add fields from another item type:
jQuery( '#select_other_fields' ).click( function()
{
	var selected_item_type_obj = jQuery( this ).prev();
	openModalWindow( '<span class="loader_img absolute_center" title="<?php echo T_('Loading...'); ?>"></span>',
		'80%', '', true,
		'<?php echo TS_('Add fields from another item type'); ?>: ' + selected_item_type_obj.find( ':selected' ).html(),
		'<?php echo TS_('Add fields now!'); ?>', true, true );
	var custom_fields = '';
	jQuery( 'input.custom_field_name' ).each( function()
	{	// Get all custom field names from current form in order to don't check them automatically, to avoid double adding:
		custom_fields += jQuery( this ).val() + ',';
	} );
	jQuery.ajax(
	{
		type: 'GET',
		url: '<?php echo $admin_url; ?>',
		data:
		{
			'ctrl': 'itemtypes',
			'action': 'select_custom_fields',
			'ityp_ID': selected_item_type_obj.val(),
			'custom_fields': custom_fields,
			'display_mode': 'js',
		},
		success: function( result )
		{
			openModalWindow( result, '80%', '', true,
				'<?php echo TS_('Add fields from another item type'); ?>: ' + selected_item_type_obj.find( ':selected' ).html(),
				'<?php echo TS_('Add fields now!'); ?>', false, true );
		}
	} );
} );
jQuery( document ).on( 'submit', 'form#itemtype_select_fields', function()
{
	jQuery( 'input[type=checkbox]:checked', this ).each( function()
	{
		var field_data_obj = jQuery( 'input[name=cf_data][data-name=' + jQuery( this ).val() + ']' );
		if( ! field_data_obj.length )
		{
			return;
		}
		var existing_field = null;
		jQuery( 'input.custom_field_name[name^="cf_name"]' ).each( function()
		{
			if( jQuery( this ).val() == field_data_obj.data( 'name' ) )
			{
				existing_field = jQuery( this );
			}
		} );
		if( existing_field !== null )
		{	// If the selected custom field already exists then update it:
			var field_row = existing_field.closest( 'tr' );
			field_row.find( 'input[name^="cf_label"]' ).val( field_data_obj.data( 'label' ) );
			field_row.find( 'input[name^="cf_order"]' ).val( field_data_obj.data( 'order' ) );
			field_row.find( 'input[name^="cf_note"]' ).val( field_data_obj.data( 'note' ) );
			field_row.find( '[name^="cf_format"]' ).val( field_data_obj.data( 'format' ) );
			field_row.find( 'input[name^="cf_schema_prop"]' ).val( field_data_obj.data( 'schema_prop' ) );
			field_row.find( 'input[name^="cf_formula"]' ).val( field_data_obj.data( 'formula' ) );
			field_row.find( 'input[name^="cf_header_class"]' ).val( field_data_obj.data( 'header_class' ) );
			field_row.find( 'input[name^="cf_cell_class"]' ).val( field_data_obj.data( 'cell_class' ) );
			field_row.find( 'input[name^="cf_link"]' ).val( field_data_obj.data( 'link' ) );
			field_row.find( 'input[name^="cf_link_nofollow"]' ).val( field_data_obj.data( 'link_nofollow' ) );
			field_row.find( 'input[name^="cf_link_class"]' ).val( field_data_obj.data( 'link_class' ) );
			field_row.find( 'select[name^="cf_line_highlight"]' ).val( field_data_obj.data( 'line_highlight' ) );
			field_row.find( 'select[name^="cf_green_highlight"]' ).val( field_data_obj.data( 'green_highlight' ) );
			field_row.find( 'select[name^="cf_red_highlight"]' ).val( field_data_obj.data( 'red_highlight' ) );
			field_row.find( 'input[name^="cf_public"]' ).prop( 'checked', field_data_obj.data( 'public' ) );
			field_row.find( 'input[name^="cf_description"]' ).val( field_data_obj.data( 'description' ) );
			field_row.find( 'input[name^="cf_merge"]' ).val( field_data_obj.data( 'merge' ) );
		}
		else
		{	// If the selected custom field doens't exist then duplicate it to current editing Item Type:
			add_new_custom_field( field_data_obj.data( 'type' ), false, field_data_obj );
		}
	} );
	closeModalWindow();
	return false;
} );

// Serialize all custom fields in single input before submit to avoid php error of max_input_vars:
jQuery( '#itemtype_checkchanges' ).submit( function()
{
	//console.time( 'Timer TOTAL' );

	//console.time( 'Timer 1' );
	var custom_fields = {};
	jQuery( '[name^=cf_]' ).each( function()
	{
		custom_fields[ jQuery( this ).attr( 'name' ).substr( 3 ) ] =
			jQuery( this ).attr( 'type' ) == 'checkbox'
				? ( jQuery( this ).prop( 'checked' ) ? 1 : 0 )
				: jQuery( this ).val();
	} );
	//console.timeEnd( 'Timer 1' );

	//console.time( 'Timer 2' );
	// Put all custom fields data in single input:
	jQuery( this ).append( '<input type="hidden" name="custom_fields_data" />' );
	jQuery( '[name=custom_fields_data]' ).val( JSON.stringify( custom_fields ) );
	//console.timeEnd( 'Timer 2' );

	//console.time( 'Timer 3' );
	// Remove name attribute of all custom fields inputs in order to don't post them all:
	jQuery( '[name^=cf_]' ).removeAttr( 'name' );
	//console.timeEnd( 'Timer 3' );

	//console.timeEnd( 'Timer TOTAL' );
} );
</script>