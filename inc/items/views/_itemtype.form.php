<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
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
	if( ! $edited_Itemtype->is_special() && ! in_array( $edited_Itemtype->ID, $default_ids ) )
	{ // Allow delete post type only if it is not default of blogs
		$Form->global_icon( T_('Delete this Post Type!'), 'delete', regenerate_url( 'action', 'action=delete&amp;crumb_itemtype='.get_crumb( 'itemtype' ) ) );
	}
}
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,ityp_ID' ) );

$Form->begin_form( 'fform', ( $edited_Itemtype->ID > 0 ? T_('Edit post type') : T_('New post type') ) );

$Form->add_crumb( 'itemtype' );
$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',ityp_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

$Form->begin_fieldset( $creating ?  T_('New Post Type') : T_('Post type').get_manual_link('item-type-form') );

	if( $creating )
	{
		$Form->text_input( 'new_ityp_ID', get_param( 'new_ityp_ID' ), 8, T_('ID'), '', array( 'maxlength'=> 10, 'required'=>true ) );
	}
	else
	{
		$Form->hidden( 'ityp_ID', $edited_Itemtype->ID );
	}

	if( $edited_Itemtype->is_special() )
	{ // Don't edit a name of special post types
		$Form->info( T_('Name'), $edited_Itemtype->name );
	}
	else
	{ // Display a field to edit a name
		$Form->text_input( 'ityp_name', $edited_Itemtype->name, 50, T_('Name'), '', array( 'maxlength' => 30, 'required' => true ) );
	}

	$Form->textarea_input( 'ityp_description', $edited_Itemtype->description, 2, T_('Description'), array( 'cols' => 47 ) );
	$Form->text_input( 'ityp_backoffice_tab', $edited_Itemtype->backoffice_tab, 25, T_('Back-office tab'), T_('Items of this type will be listed in this back-office tab. If empty, item will be found only in the "All" tab.'), array( 'maxlength' => 30 ) );
	$Form->text_input( 'ityp_template_name', $edited_Itemtype->template_name, 25, T_('Template name'), T_('b2evolution will automatically append .main.php or .disp.php'), array( 'maxlength' => 40 ) );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Features').get_manual_link('item-type-features'), array( 'id' => 'itemtype_features' ) );

	$options = array(
			array( 'required', T_('Required') ),
			array( 'optional', T_('Optional') ),
			array( 'never', T_('Never') )
		);

	$Form->radio( 'ityp_use_title', $edited_Itemtype->use_title, $options, T_('Use title') );
	$Form->radio( 'ityp_use_url', $edited_Itemtype->use_url, $options, T_('Use URL') );
	$Form->radio( 'ityp_use_text', $edited_Itemtype->use_text, $options, T_('Use text') );
	$Form->checkbox( 'ityp_allow_html', $edited_Itemtype->allow_html, T_('Allow HTML'), T_( 'Check to allow HTML in posts.' ).' ('.T_('HTML code will pass several sanitization filters.').')' );
	$Form->checkbox( 'ityp_allow_attachments', $edited_Itemtype->allow_attachments, T_('Allow attachments') );
	$Form->radio( 'ityp_use_excerpt', $edited_Itemtype->use_excerpt, $options, T_('Use excerpt') );
	$Form->radio( 'ityp_use_title_tag', $edited_Itemtype->use_title_tag, $options, htmlspecialchars( T_('Use <title> tag') ) );
	$Form->radio( 'ityp_use_meta_desc', $edited_Itemtype->use_meta_desc, $options, htmlspecialchars( T_('Use <meta> description') ) );
	$Form->radio( 'ityp_use_meta_keywds', $edited_Itemtype->use_meta_keywds, $options, htmlspecialchars( T_('Use <meta> keywords') ) );
	$Form->radio( 'ityp_use_tags', $edited_Itemtype->use_tags, $options, T_('Use tags') );
	$Form->checkbox( 'ityp_allow_featured', $edited_Itemtype->allow_featured, T_('Allow featured') );
	$Form->radio( 'ityp_use_country', $edited_Itemtype->use_country, $options, T_('Use country') );
	$Form->radio( 'ityp_use_region', $edited_Itemtype->use_region, $options, T_('Use region') );
	$Form->radio( 'ityp_use_sub_region', $edited_Itemtype->use_sub_region, $options, T_('Use sub-region') );
	$Form->radio( 'ityp_use_city', $edited_Itemtype->use_city, $options, T_('Use city') );
	$Form->radio( 'ityp_use_coordinates', $edited_Itemtype->use_coordinates, $options, T_('Use coordinates'), false, T_('Turn this on to be able to set the location coordinates and view on map.') );
	$Form->checkbox( 'ityp_use_custom_fields', $edited_Itemtype->use_custom_fields, T_('Use custom fields') );
	$Form->checkbox( 'ityp_use_comments', $edited_Itemtype->use_comments, T_('Use comments'), T_('Also see collection\'s feedback options') );
	$Form->checkbox( 'ityp_allow_closing_comments', $edited_Itemtype->allow_closing_comments, T_('Allow closing comments'), T_('Check to allow closing comments on individual items/posts.') );
	$Form->checkbox( 'ityp_allow_disabling_comments', $edited_Itemtype->allow_disabling_comments, T_('Allow disabling comments'), T_('Check to allow disabling comments on individual items/posts.') );
	$Form->radio( 'ityp_use_comment_expiration', $edited_Itemtype->use_comment_expiration, $options, T_('Use comment expiration') );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Custom fields').get_manual_link('item-custom-fields') );
	$custom_field_types = array(
			'double' => array( 'label' => T_('Numeric'), 'title' => T_('Add new numeric custom field'), 'note' => T_('Ex: Price, Weight, Length... &ndash; will be stored as a double floating point number.'), 'size' => 20, 'maxlength' => 40 ),
			'varchar' => array( 'label' => T_('String'), 'title' => T_('Add new text custom field'), 'note' => T_('Ex: Color, Fabric... &ndash; will be stored as a varchar(2000) field.'), 'size' => 30, 'maxlength' => 60 )
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
			$custom_field_name = ' '.T_('Name').' <input type="text" name="custom_'.$type.'_fname'.$i.'" value="'.$custom_field_name.'" class="form_text_input custom_field_name '.$custom_field_name_class.'" maxlength="36" />';
			$custom_field_name .= ' '.T_('Order').' <input type="text" name="custom_'.$type.'_order'.$i.'" value="'.$custom_field['order'].'" class="form_text_input custom_field_order" maxlength="11" size="3" />';
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

jQuery( '#add_new_double_custom_field' ).click( function()
{
	var count_custom_double = jQuery( 'input[name=count_custom_double]' ).attr( 'value' );
	count_custom_double++;
	var custom_ID = guidGenerator();
	jQuery( '#custom_double_field_list' ).append( '<fieldset id="ffield_custom_double_' + count_custom_double + '">' +
			'<input type="hidden" name="custom_double_ID' + count_custom_double + '" value="' + custom_ID + '" />' +
			'<input type="hidden" name="custom_double_new' + count_custom_double + '" value="1" />' +
			'<?php echo $Form->labelstart; ?><label for="custom_double_' + count_custom_double + '"<?php echo empty( $Form->labelclass ) ? '' : ' class="'.$Form->labelclass.'"'; ?>><?php echo TS_('Numeric'); ?>:</label><?php echo str_replace( "\n", '', $Form->labelend ); ?>' +
			'<?php echo $Form->inputstart; ?>' +
				'<?php echo TS_('Title'); ?> <input type="text" id="custom_double_' + count_custom_double + '" name="custom_double_' + count_custom_double + '" class="form_text_input new_custom_field_title" size="20" maxlength="60" />' +
				' <?php echo TS_('Name'); ?> <input type="text" name="custom_double_fname' + count_custom_double + '" value="" class="form_text_input custom_field_name" maxlength="36" />' +
				' <?php echo TS_('Order'); ?> <input type="text" name="custom_double_order' + count_custom_double + '" value="" class="form_text_input custom_field_order" maxlength="36" size="3" />' +
			'<?php echo str_replace( "\n", '', $Form->inputend ); ?></fieldset>' );
	jQuery( 'input[name=count_custom_double]' ).attr( 'value', count_custom_double );
} );

jQuery( '#add_new_varchar_custom_field' ).click( function()
{
	var count_custom_varchar = jQuery( 'input[name=count_custom_varchar]' ).attr( 'value' );
	count_custom_varchar++;
	var custom_ID = guidGenerator();
	jQuery( '#custom_varchar_field_list' ).append( '<fieldset id="ffield_custom_string' + count_custom_varchar + '">' +
			'<input type="hidden" name="custom_varchar_ID' + count_custom_varchar + '" value="' + custom_ID + '" />' +
			'<input type="hidden" name="custom_varchar_new' + count_custom_varchar + '" value="1" />' +
			'<?php echo $Form->labelstart; ?><label for="custom_varchar_' + count_custom_varchar + '"<?php echo empty( $Form->labelclass ) ? '' : ' class="'.$Form->labelclass.'"'; ?>><?php echo TS_('String'); ?>:</label><?php echo str_replace( "\n", '', $Form->labelend ); ?>' +
			'<?php echo $Form->inputstart; ?>' +
				'<?php echo TS_('Title'); ?> <input type="text" id="custom_varchar_' + count_custom_varchar + '" name="custom_varchar_' + count_custom_varchar + '" class="form_text_input new_custom_field_title" size="30" maxlength="40" />' +
				' <?php echo TS_('Name'); ?> <input type="text" name="custom_varchar_fname' + count_custom_varchar + '" value="" class="form_text_input custom_field_name" maxlength="36" />' +
				' <?php echo TS_('Order'); ?> <input type="text" name="custom_varchar_order' + count_custom_varchar + '" value="" class="form_text_input custom_field_order" maxlength="36" size="3" />' +
			'<?php echo str_replace( "\n", '', $Form->inputend ); ?></fieldset>' );
	jQuery( 'input[name=count_custom_varchar]' ).attr( 'value', count_custom_varchar );
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