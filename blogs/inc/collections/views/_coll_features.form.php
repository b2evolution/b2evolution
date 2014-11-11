<?php
/**
 * This file implements the UI view for the Collection features properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 *
 * @version $Id: _coll_features.form.php 7444 2014-10-17 04:12:27Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


$Form = new Form( NULL, 'coll_features_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Post list').get_manual_link('item-list-features') );
  $Form->select_input_array( 'orderby', $edited_Blog->get_setting('orderby'), get_available_sort_options(), T_('Order by'), T_('Default ordering of posts.') );
  $Form->select_input_array( 'orderdir', $edited_Blog->get_setting('orderdir'), array(
                        'ASC'  => T_('Ascending'),
                        'DESC' => T_('Descending'), ), T_('Direction') );
  $Form->radio( 'what_to_show', $edited_Blog->get_setting('what_to_show'),
                array(  array( 'days', T_('days') ),
                        array( 'posts', T_('posts') ),
                      ), T_('Display unit'), false,  T_('Do you want to restrict on the number of days or the number of posts?') );
  $Form->text( 'posts_per_page', $edited_Blog->get_setting('posts_per_page'), 4, T_('Posts/Days per page'), T_('How many days or posts do you want to display on the home page?'), 4 );

  $Form->radio( 'timestamp_min', $edited_Blog->get_setting('timestamp_min'),
                array(  array( 'yes', T_('yes') ),
                        array( 'no', T_('no') ),
                        array( 'duration', T_('only the last') ),
                      ), T_('Show past posts'), true );
  $Form->duration_input( 'timestamp_min_duration', $edited_Blog->get_setting('timestamp_min_duration'), '' );

  $Form->radio( 'timestamp_max', $edited_Blog->get_setting('timestamp_max'),
                array(  array( 'yes', T_('yes') ),
                        array( 'no', T_('no') ),
                        array( 'duration', T_('only the next') ),
                      ), T_('Show future posts'), true );
  $Form->duration_input( 'timestamp_max_duration', $edited_Blog->get_setting('timestamp_max_duration'), '' );

  $Form->checklist( get_inskin_statuses_options( $edited_Blog, 'post' ), 'post_inskin_statuses', T_('Front office statuses'), false, false, array( 'note' => 'Uncheck the statuses that should never appear in the front office.' ) );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Post options').get_manual_link('blog_features_settings') );
	$Form->radio( 'require_title', $edited_Blog->get_setting('require_title'),
								array(  array( 'required', T_('Always'), T_('The blogger must provide a title') ),
												array( 'optional', T_('Optional'), T_('The blogger can leave the title field empty') ),
												array( 'none', T_('Never'), T_('No title field') ),
											), T_('Post titles'), true );

	$Form->checkbox( 'allow_html_post', $edited_Blog->get_setting( 'allow_html_post' ),
						T_( 'Allow HTML' ), T_( 'Check to allow HTML in posts.' ).' ('.T_('HTML code will pass several sanitization filters.').')' );

	$Form->radio( 'enable_goto_blog', $edited_Blog->get_setting( 'enable_goto_blog' ),
		array( array( 'no', T_( 'No' ), T_( 'Check this to view list of the posts.' ) ),
			array( 'blog', T_( 'View home page' ), T_( 'Check this to automatically view the blog after publishing a post.' ) ),
			array( 'post', T_( 'View new post' ), T_( 'Check this to automatically view the post page.' ) ), ),
			T_( 'View blog after publishing' ), true );

	$Form->radio( 'editing_goto_blog', $edited_Blog->get_setting( 'editing_goto_blog' ),
		array( array( 'no', T_( 'No' ), T_( 'Check this to view list of the posts.' ) ),
			array( 'blog', T_( 'View home page' ), T_( 'Check this to automatically view the blog after editing a post.' ) ),
			array( 'post', T_( 'View edited post' ), T_( 'Check this to automatically view the post page.' ) ), ),
			T_( 'View blog after editing' ), true );

	// FP> TODO:
	// -post_url  always('required')|optional|never
	// -multilingual:  true|false   or better yet: provide a list to narrow down the active locales
	// -tags  always('required')|optional|never

	$Form->radio( 'post_categories', $edited_Blog->get_setting('post_categories'),
		array( array( 'one_cat_post', T_('Allow only one category per post') ),
			array( 'multiple_cat_post', T_('Allow multiple categories per post') ),
			array( 'main_extra_cat_post', T_('Allow one main + several extra categories') ),
			array( 'no_cat_post', T_('Don\'t allow category selections'), T_('(Main cat will be assigned automatically)') ) ),
			T_('Post category options'), true );

	$Form->radio( 'post_navigation', $edited_Blog->get_setting('post_navigation'),
		array( array( 'same_blog', T_('same blog') ),
			array( 'same_category', T_('same category') ),
			array( 'same_author', T_('same author') ) ),
			T_('Default post by post navigation should stay in'), true, T_( 'Skins may override this setting!') );

	$location_options = array(
			array( 'optional', T_('Optional') ),
			array( 'required', T_('Required') ),
			array( 'hidden', T_('Hidden') )
		);

	$Form->radio( 'location_country', $edited_Blog->get_setting( 'location_country' ), $location_options, T_('Country') );

	$Form->radio( 'location_region', $edited_Blog->get_setting( 'location_region' ), $location_options, T_('Region') );

	$Form->radio( 'location_subregion', $edited_Blog->get_setting( 'location_subregion' ), $location_options, T_('Sub-region') );

	$Form->radio( 'location_city', $edited_Blog->get_setting( 'location_city' ), $location_options, T_('City') );

	$Form->checkbox( 'show_location_coordinates', $edited_Blog->get_setting( 'show_location_coordinates' ),
						T_( 'Show location coordinates' ), T_( 'Check this to be able to set the location coordinates and view on map.' ) );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Post moderation') . get_manual_link('post-moderation') );

	$Form->select_input_array( 'default_post_status', $edited_Blog->get_setting('default_post_status'), get_visibility_statuses('notes-string'), T_('Default status'), T_('Default status for new posts') );

	// Moderation statuses setting
	$not_moderation_statuses = array_diff( get_visibility_statuses( 'keys', NULL ), get_visibility_statuses( 'moderation' ) );
	// Get moderation statuses with status text
	$moderation_statuses = get_visibility_statuses( '', $not_moderation_statuses );
	$blog_moderation_statuses = $edited_Blog->get_setting( 'post_moderation_statuses' );
	$checklist_options = array();
	foreach( $moderation_statuses as $status => $status_text )
	{ // Add a checklist option for each possible modeartion status
		$is_checked = ( strpos( $blog_moderation_statuses, $status) !== false );
		$checklist_options[] = array( 'post_notif_'.$status, 1, $status_text, $is_checked );
	}
	$Form->checklist( $checklist_options, 'post_moderation_statuses', T_('Post moderation reminder statuses'), false, false, array( 'note' => 'Posts with the selected statuses will be notified on the "Send reminders about posts awaiting moderation" scheduled job.' ) );

$Form->end_fieldset();

// display features settings provided by optional modules:
modules_call_method( 'display_collection_features', array( 'Form' => & $Form, 'edited_Blog' => & $edited_Blog ) );

$Form->begin_fieldset( T_('RSS/Atom feeds').get_manual_link('item-feeds-features') );
	$Form->radio( 'feed_content', $edited_Blog->get_setting('feed_content'),
								array(  array( 'none', T_('No feeds') ),
												array( 'title', T_('Titles only') ),
												array( 'excerpt', T_('Post excerpts') ),
												array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")') ),
												array( 'full', T_('Full post contents (including after "[teaserbreak]")') ),
											), T_('Post feed contents'), true, T_('How much content do you want to make available in post feeds?') );

	$Form->text( 'posts_per_feed', $edited_Blog->get_setting('posts_per_feed'), 4, T_('Posts in feeds'),  T_('How many of the latest posts do you want to include in RSS & Atom feeds?'), 4 );

	if( isset($GLOBALS['files_Module']) )
	{
		load_funcs( 'files/model/_image.funcs.php' );
		$params['force_keys_as_values'] = true;
		$Form->select_input_array( 'image_size', $edited_Blog->get_setting('image_size') , get_available_thumb_sizes(), T_('Image size'), '', $params );
	}
$Form->end_fieldset();


$Form->begin_fieldset( T_('Custom fields').get_manual_link('item-custom-fields') );
	$custom_field_types = array(
			'double' => array( 'label' => T_('Numeric'), 'title' => T_('Add new numeric custom field'), 'note' => T_('Ex: Price, Weight, Length... &ndash; will be stored as a double floating point number.'), 'size' => 20, 'maxlength' => 40 ),
			'varchar' => array( 'label' => T_('String'), 'title' => T_('Add new text custom field'), 'note' => T_('Ex: Color, Fabric... &ndash; will be stored as a varchar(2000) field.'), 'size' => 30, 'maxlength' => 60 )
	);

	foreach( $custom_field_types as $type => $data )
	{
		echo '<div id="custom_'.$type.'_field_list">';
		// dispaly hidden count_custom_type value and increase after a new field was added
		$count_custom_field = $edited_Blog->get_setting( 'count_custom_'.$type );
		echo '<input type="hidden" name="count_custom_'.$type.'" value='.$count_custom_field.' />';
		$deleted_custom_fields = param( 'deleted_custom_'.$type, 'string', '' );
		echo '<input type="hidden" name="deleted_custom_'.$type.'" value="'.$deleted_custom_fields.'" />';
		for( $i = 1 ; $i <= $count_custom_field; $i++ )
		{ // dispaly all existing custom field name
			$field_id_suffix = 'custom_'.$type.'_'.$i;
			$custom_guid = $edited_Blog->get_setting( 'custom_'.$type.$i );
			if( !empty( $deleted_custom_fields ) && ( strpos( $deleted_custom_fields, $custom_guid ) !== false ) )
			{
				continue;
			}
			$action_delete = get_icon( 'xross', 'imgtag', array( 'id' => 'delete_'.$field_id_suffix, 'style' => 'cursor:pointer', 'title' => T_('Delete custom field') ) );
			$custom_field_name = $edited_Blog->get_setting( 'custom_fname_'.$custom_guid );
			$custom_field_value = $edited_Blog->get_setting( 'custom_'.$type.'_'.$custom_guid );
			$custom_field_value_class = '';
			$custom_field_name_class = '';
			if( empty( $custom_field_value ) )
			{ // When user saves new field without name
				$custom_field_value = get_param( $field_id_suffix );
				$custom_field_value_class = 'new_custom_field_title';
				$custom_field_name_class = 'field_error';
			}
			echo '<input type="hidden" name="custom_'.$type.'_guid'.$i.'" value="'.$custom_guid.'" />';
			$custom_field_name = ' '.T_('Name').' <input type="text" name="custom_'.$type.'_fname'.$i.'" value="'.$custom_field_name.'" class="form_text_input custom_field_name '.$custom_field_name_class.'" maxlength="36" />';
			$Form->text_input( $field_id_suffix, $custom_field_value, $data[ 'size' ], $data[ 'label' ], $action_delete, array(
					'maxlength'    => $data[ 'maxlength' ],
					'input_prefix' => T_('Title').' ',
					'input_suffix' => $custom_field_name,
					'class'        => $custom_field_value_class,
				) );
		}
		echo '</div>';
		// display link to create new custom field
		$Form->info( '', '<a onclick="return false;" href="#" id="add_new_'.$type.'_custom_field">'.$data[ 'title' ].'</a>', '( '.$data[ 'note' ].' )' );
	}
$Form->end_fieldset();


$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );


load_funcs( 'regional/model/_regional.funcs.php' );
echo_regional_required_js( 'location_' );

?>

<script type="text/javascript">
	function guidGenerator() {
		var S4 = function() {
			return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
		};
		return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4());
	}

	jQuery( '#add_new_double_custom_field' ).click( function()
	{
		var count_custom_double = jQuery( 'input[name=count_custom_double]' ).attr( 'value' );
		count_custom_double++;
		var custom_guid = guidGenerator();
		jQuery( '#custom_double_field_list' ).append( '<fieldset id="ffield_custom_double_' + count_custom_double + '">' +
				'<input type="hidden" name="custom_double_guid' + count_custom_double + '" value="' + custom_guid + '" />' +
				'<?php echo $Form->labelstart; ?><label for="custom_double_' + count_custom_double + '"<?php echo empty( $Form->labelclass ) ? '' : ' class="'.$Form->labelclass.'"'; ?>><?php echo TS_('Numeric'); ?>:</label><?php echo str_replace( "\n", '', $Form->labelend ); ?>' +
				'<?php echo $Form->inputstart; ?>' +
					'Title <input type="text" id="custom_double_' + count_custom_double + '" name="custom_double_' + count_custom_double + '" class="form_text_input new_custom_field_title" size="20" maxlength="60" />' +
					' Name <input type="text" name="custom_double_fname' + count_custom_double + '" value="" class="form_text_input custom_field_name" maxlength="36" />' +
				'<?php echo str_replace( "\n", '', $Form->inputend ); ?></fieldset>' );
		jQuery( 'input[name=count_custom_double]' ).attr( 'value', count_custom_double );
	} );

	jQuery( '#add_new_varchar_custom_field' ).click( function()
	{
		var count_custom_varchar = jQuery( 'input[name=count_custom_varchar]' ).attr( 'value' );
		count_custom_varchar++;
		var custom_guid = guidGenerator();
		jQuery( '#custom_varchar_field_list' ).append( '<fieldset id="ffield_custom_string' + count_custom_varchar + '">' +
				'<input type="hidden" name="custom_varchar_guid' + count_custom_varchar + '" value="' + custom_guid + '" />' +
				'<?php echo $Form->labelstart; ?><label for="custom_varchar_' + count_custom_varchar + '"<?php echo empty( $Form->labelclass ) ? '' : ' class="'.$Form->labelclass.'"'; ?>><?php echo TS_('String'); ?>:</label><?php echo str_replace( "\n", '', $Form->labelend ); ?>' +
				'<?php echo $Form->inputstart; ?>' +
					'Title <input type="text" id="custom_varchar_' + count_custom_varchar + '" name="custom_varchar_' + count_custom_varchar + '" class="form_text_input new_custom_field_title" size="30" maxlength="40" />' +
					' Name <input type="text" name="custom_varchar_fname' + count_custom_varchar + '" value="" class="form_text_input custom_field_name" maxlength="36" />' +
				'<?php echo str_replace( "\n", '', $Form->inputend ); ?></fieldset>' );
		jQuery( 'input[name=count_custom_varchar]' ).attr( 'value', count_custom_varchar );
	} );

	jQuery( '[id^="delete_custom_"]' ).click( function()
	{
		if( confirm( '<?php echo TS_('Are you sure want to delete this custom field?\nThe update will be performed when you will click on the \'Save changes!\' button.'); ?>' ) )
		{ // Delete custom field only from html form, This field will be removed after saving of changes
			var delete_action_id = jQuery( this ).attr('id');
			var field_parts = delete_action_id.split( '_' );
			var field_type = field_parts[2];
			var field_index = field_parts[3];
			var field_guid = jQuery( '[name="custom_' + field_type + '_guid' + field_index + '"]' ).val();
			var deleted_fields = '[name="deleted_custom_' + field_type + '"]';
			var deleted_fields_value = jQuery( deleted_fields ).val();
			if( deleted_fields_value )
			{
				deleted_fields_value = deleted_fields_value + ',';
			}
			jQuery( deleted_fields ).val( deleted_fields_value + field_guid );
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