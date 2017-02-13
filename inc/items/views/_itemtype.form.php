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

/**
 * @var Itemtype
 */
global $edited_Itemtype;

// Determine if we are creating or updating...
global $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'itemtype_checkchanges' );

if( $edited_Itemtype->ID > 0 )
{
	$default_ids = ItemType::get_default_ids();
	if( ! in_array( $edited_Itemtype->ID, $default_ids ) )
	{	// Allow delete post type only if it is not default of blogs:
		$Form->global_icon( T_('Delete this Post Type!'), 'delete', regenerate_url( 'action', 'action=delete&amp;crumb_itemtype='.get_crumb( 'itemtype' ) ) );
	}
}
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,ityp_ID' ) );

$Form->begin_form( 'fform', ( $edited_Itemtype->ID > 0 ? T_('Edit post type') : T_('New post type') ) );

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

$Form->begin_fieldset( T_('Use of Instructions').get_manual_link( 'item-type-instructions' ), array( 'id' => 'itemtype_instructions' ) );
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


$Form->begin_fieldset( T_('Use of Custom Fields').get_manual_link( 'item-type-custom-fields' ) );
	$Form->checkbox( 'ityp_use_custom_fields', $edited_Itemtype->use_custom_fields, T_('Use custom fields') );

	$custom_field_types = array(
			'double' => array(
					'label'     => T_('Numeric'),
					'title'     => T_('Add new numeric custom field'),
					'note'      => T_('Ex: Price, Weight, Length... &ndash; will be stored as a double floating point number.'),
					'size'      => 20,
					'maxlength' => 40
				),
			'varchar' => array(
					'label'     => T_('String'),
					'title'     => T_('Add new string custom field'),
					'note'      => T_('Ex: Color, Fabric... &ndash; will be stored as a varchar(10000) field.'),
					'size'      => 30,
					'maxlength' => 60
				),
			'text' => array(
					'label'     => T_('Text'),
					'title'     => T_('Add new text custom field'),
					'note'      => T_('Ex: Content, Description... &ndash; will be stored as a varchar(10000) field.'),
					'size'      => 30,
					'maxlength' => 60
				),
			'html' => array(
					'label'     => 'HTML',
					'title'     => T_('Add new HTML custom field'),
					'note'      => T_('Ex: Content, Description... &ndash; will be stored as a varchar(10000) field.'),
					'size'      => 30,
					'maxlength' => 60
				)
	);

	$custom_fields_names = array();
	foreach( $custom_field_types as $type => $data )
	{
		echo '<div id="custom_'.$type.'_field_list">';
		// dispaly hidden count_custom_type value and increase after a new field was added
		$custom_fields = $edited_Itemtype->get_custom_fields( $type, 'ID' );
		$deleted_custom_fields = param( 'deleted_custom_'.$type, 'string', '' );
		$i = 1;
		foreach( $custom_fields as $custom_field )
		{ // dispaly all existing custom field name
			if( isset( $custom_field['temp_i'] ) )
			{ // Get i from this temp number when form was is submitted
				$i = $custom_field['temp_i'];
			}
			$field_id_suffix = 'custom_'.$type.'_'.$i;
			$custom_ID = $custom_field['ID'];
			if( !empty( $deleted_custom_fields ) && ( strpos( $deleted_custom_fields, $custom_ID ) !== false ) )
			{
				continue;
			}
			$action_delete = get_icon( 'remove', 'imgtag', array( 'id' => 'delete_'.$field_id_suffix, 'style' => 'cursor:pointer', 'title' => T_('Remove custom field') ) );
			$custom_field_name = $custom_field['name'];
			$custom_field_label = $custom_field['label'];
			$custom_field_label_class = '';
			$custom_field_name_class = '';
			if( empty( $custom_field_label ) )
			{ // When user saves new field without title
				$custom_field_label = get_param( $field_id_suffix );
				$custom_field_label_class = 'field_error new_custom_field_title';
			}
			if( empty( $custom_field_name ) )
			{ // When user saves new field without name
				$custom_field_name = get_param( 'custom_'.$type.'_fname'.$i );
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
			echo '<input type="hidden" name="custom_'.$type.'_ID'.$i.'" value="'.$custom_ID.'" />';
			if( $action == 'new' || param( 'custom_'.$type.'_new'.$i, 'integer', 0 ) )
			{ // Create this <hidden> to know this custom field is new created field
				echo '<input type="hidden" name="custom_'.$type.'_new'.$i.'" value="1" />';
			}
			$custom_field_name = ' '.T_('Name').' <input type="text" name="custom_'.$type.'_fname'.$i.'" value="'.$custom_field_name.'" class="form_text_input form-control custom_field_name '.$custom_field_name_class.'" maxlength="36" />';
			$custom_field_name .= ' '.T_('Order').' <input type="text" name="custom_'.$type.'_order'.$i.'" value="'.$custom_field['order'].'" class="form_text_input form-control custom_field_order" maxlength="11" size="3" />';
			$Form->text_input( $field_id_suffix, $custom_field_label, $data[ 'size' ], $data[ 'label' ], $action_delete, array(
					'maxlength'    => $data[ 'maxlength' ],
					'input_prefix' => T_('Title').' ',
					'input_suffix' => $custom_field_name,
					'class'        => $custom_field_label_class,
				) );
			$i++;
		}
		echo '</div>';
		echo '<input type="hidden" name="count_custom_'.$type.'" value='.( $i - 1 ).' />';
		echo '<input type="hidden" name="deleted_custom_'.$type.'" value="'.$deleted_custom_fields.'" />';
		// display link to create new custom field
		$Form->info( '', '<a onclick="return false;" href="#" id="add_new_'.$type.'_custom_field">'.$data[ 'title' ].'</a>', '( '.$data[ 'note' ].' )' );
	}
$Form->end_fieldset();

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

$Results->display( $display_params );


$item_status_IDs = array();
if( $Results->result_num_rows > 0 )
{	// If at least one post status exists in DB:
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
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}

load_funcs( 'regional/model/_regional.funcs.php' );
echo_regional_required_js( 'ityp_use_' );

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

function add_new_custom_field( type, title, title_size )
{
	var count_custom = jQuery( 'input[name=count_custom_' + type + ']' ).attr( 'value' );
	count_custom++;
	var custom_ID = guidGenerator();
	jQuery( '#custom_' + type + '_field_list' ).append( '<?php echo str_replace( array( '$ID$', "\n" ), array( 'ffield_custom_\' + type + \'_\' + count_custom + \'', '' ), $Form->fieldstart ); ?>' +
			'<input type="hidden" name="custom_' + type + '_ID' + count_custom + '" value="' + custom_ID + '" />' +
			'<input type="hidden" name="custom_' + type + '_new' + count_custom + '" value="1" />' +
			'<?php echo $Form->labelstart; ?><label for="custom_' + type + '_' + count_custom + '"<?php echo empty( $Form->labelclass ) ? '' : ' class="'.$Form->labelclass.'"'; ?>>' + title + ':</label><?php echo str_replace( "\n", '', $Form->labelend ); ?>' +
			'<?php echo $Form->inputstart; ?>' +
				'<?php echo TS_('Title'); ?> <input type="text" id="custom_' + type + '_' + count_custom + '" name="custom_' + type + '_' + count_custom + '" class="form_text_input form-control new_custom_field_title" maxlength="255" size="' + title_size + '" />' +
				' <?php echo TS_('Name'); ?> <input type="text" name="custom_' + type + '_fname' + count_custom + '" value="" class="form_text_input form-control custom_field_name" maxlength="255" />' +
				' <?php echo TS_('Order'); ?> <input type="text" name="custom_' + type + '_order' + count_custom + '" value="" class="form_text_input form-control custom_field_order" maxlength="11" size="3" />' +
			'<?php echo str_replace( "\n", '', $Form->inputend.$Form->fieldend ); ?>' );
	jQuery( 'input[name=count_custom_' + type + ']' ).attr( 'value', count_custom );
}

jQuery( '#add_new_double_custom_field' ).click( function()
{
	add_new_custom_field( 'double', '<?php echo TS_('Numeric'); ?>', 20 );
} );

jQuery( '#add_new_varchar_custom_field' ).click( function()
{
	add_new_custom_field( 'varchar', '<?php echo TS_('String'); ?>', 30 );
} );

jQuery( '#add_new_text_custom_field' ).click( function()
{
	add_new_custom_field( 'text', '<?php echo TS_('Text'); ?>', 30 );
} );

jQuery( '#add_new_html_custom_field' ).click( function()
{
	add_new_custom_field( 'html', 'HTML', 30 );
} );

jQuery( '[id^="delete_custom_"]' ).click( function()
{
	if( confirm( '<?php echo TS_('Are you sure want to delete this custom field?\nThe update will be performed when you will click on the \'Save Changes!\' button.'); ?>' ) )
	{ // Delete custom field only from html form, This field will be removed after saving of changes
		var delete_action_id = jQuery( this ).attr('id');
		var field_parts = delete_action_id.split( '_' );
		var field_type = field_parts[2];
		var field_index = field_parts[3];
		var field_ID = jQuery( '[name="custom_' + field_type + '_ID' + field_index + '"]' ).val();
		var deleted_fields = '[name="deleted_custom_' + field_type + '"]';
		var deleted_fields_value = jQuery( deleted_fields ).val();
		if( deleted_fields_value )
		{
			deleted_fields_value = deleted_fields_value + ',';
		}
		jQuery( deleted_fields ).val( deleted_fields_value + field_ID );
		jQuery( '#ffield_custom_' + field_type + '_' + field_index ).remove();
	}
} );

jQuery( document ).on( 'keyup', '.new_custom_field_title', function()
{ // Prefill new field name
	jQuery( this ).parent().find( '.custom_field_name' ).val( parse_custom_field_name( jQuery( this ).val() ) );
} );

jQuery( document ).on( 'blur', '.custom_field_name', function()
{ // Remove incorrect chars from field name on blur event
	jQuery( this ).val( parse_custom_field_name( jQuery( this ).val() ) );
} );

function parse_custom_field_name( field_name )
{
	return field_name.substr( 0, 36 ).replace( /[^a-z0-9\-_]/ig, '_' ).toLowerCase();
}
</script>