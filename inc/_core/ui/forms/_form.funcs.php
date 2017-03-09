<?php
/**
 * This file implements Fast Form handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 *
 * @deprecated All those functions should be handled by the {@link Form Form class}.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Builds a text (or password) input field.
 *
 * @deprecated Deprecated by (@link Form::text_input())
 *
 * @param string the name of the input field
 * @param string initial value
 * @param integer size of the input field
 * @param string label displayed in front of the field
 * @param string note displayed with field
 * @param integer max length of the value (if 0 field_size will be used!)
 * @param string the CSS class to use
 * @param string input type (only 'text' or 'password' makes sense)
 * @param boolean display (default) or return
 * @return mixed true (if output) or the generated HTML if not outputting
 */
function form_text( $field_name, $field_value, $field_size, $field_label, $field_note = '',
										$field_maxlength = 0 , $field_class = '', $inputtype = 'text', $output = true  )
{
	if( $field_maxlength == 0 )
		$field_maxlength = $field_size;

	// question: is it necessary to enclose each field in a fieldset.
	// fplanque> YES, for CSS
	// shouldn't there be a fieldset for a set of field (i.e. all fields
	// in the form)?
	// fplanque>> Create a new 'simple' layout if this is what you want

	$r = "<fieldset>\n"
			.'<div class="label"><label for="'.$field_name.'">'.$field_label.":</label></div>\n"
			.'<div class="input"><input type="'.$inputtype.'" name="'.$field_name
			.'" id="'.$field_name.'" size="'.$field_size.'" maxlength="'.$field_maxlength
			.'" value="'.format_to_output($field_value, 'formvalue').'"';
	if( !empty($field_class) )
	{
		$r .= ' class="'.$field_class.'"';
	}
	$r .= " />\n";

	if( !empty( $field_note ) )
	{
		$r .= '<span class="notes">'.$field_note.'</span>';
	}

	$r .= "</div>\n</fieldset>\n\n";

	if( $output )
	{
		echo $r;
		return true;
	}
	else
	{
		return $r;
	}
}

/**
 * Display a select field and populate it with a callback function.
 *
 * @deprecated Deprecated by {@link Form::select_input()}
 *
 * @param string field name
 * @param string default field value
 * @param callback callback function
 * @param string field label to be display before the field
 * @param string note to be displayed after the field
 * @param string CSS class for select
 * waltercruz> still used by mtimport
 */
function form_select(
	$field_name,
	$field_value,
	$field_list_callback,
	$field_label,
	$field_note = '',
	$field_class = '' )
{
	echo '<fieldset>';
	echo '  <div class="label"><label for="', $field_name, '">', $field_label, (($field_label != '') ? ':' : ''), '</label></div>';
	echo '  <div class="input"><select name="', $field_name, '" id="', $field_name, '"';
	if( !empty($field_class) )
	{
		echo ' class="', $field_class,'"';
	}
	echo '>';

	// call the callback function:
	$field_list_callback( $field_value );

	echo '  </select>';
	echo '  <span class="notes">', $field_note, '</span></div>';
	echo "</fieldset>\n\n";
}


/**
 * Display a select field and populate it with a cache object.
 *
 * @deprecated Deprecated by (@link Form::select_object())
 *
 * @param string field name
 * @param string default field value
 * @param DataObjectCache Cache containing values for list (get_option_list() gets called on it)
 * @param string field label to be display before the field
 * @param string note to be displayed after the field
 * @param boolean allow to select [none] in list
 * @param string CSS class for select
 * waltercruz> still used by mtimport
 */
function form_select_object(
	$field_name,
	$field_value,
	& $field_object,
	$field_label,
	$field_note = '',
	$allow_none = false,
	$field_class = '' )
{
	echo '<fieldset>';
	echo '  <div class="label"><label for="', $field_name, '">', $field_label, ':</label></div>';
	echo '  <div class="input"><select name="', $field_name, '" id="', $field_name, '"';
	if( !empty($field_class) )
	{
		echo ' class="'.$field_class.'"';
	}
	echo '>';
	echo $field_object->get_option_list( $field_value, $allow_none );
	echo '  </select>';
	echo '  <span class="notes">'.$field_note.'</span></div>';
	echo "</fieldset>\n\n";
}

/**
 * form_checkbox(-)
 *
 * @deprecated Deprecated by (@link Form::checkbox())
 *
 * @param string the name of the checkbox
 * @param boolean initial value
 * @param string label
 * @param string note
 * @param string CSS class
 * @param boolean to output (default)  or not
 * @return mixed true (if output) or the generated HTML if not outputting
 * waltercruz > still used by mtimport
 */
function form_checkbox( $field_name, $field_value, $field_label, $field_note = '',
												$field_class = '', $output = true )
{
	$r = "<fieldset>\n"
			.'<div class="label"><label for="'.$field_name.'">'.$field_label.":</label></div>\n"
			.'<div class="input"><input type="checkbox" class="checkbox" name="'.$field_name.'" id="'
			.$field_name.'" value="1"';
	if( $field_value )
	{
		$r .= ' checked="checked"';
	}
	if( !empty($field_class) )
	{
		$r .= ' class="'.$field_class.'"';
	}
	$r .= " />\n"
				.'<span class="notes">'.$field_note."</span></div>\n"
				."</fieldset>\n\n";

	if( $output )
	{
		echo $r;
		return true;
	}
	else
	{
		return $r;
	}
}

/**
 * form_info(-)
 *
 * @deprecated Deprecated by (@link Form::info_field())
 * @internal Tblue> Still used by gettext/staticfiles.php
 */
function form_info( $field_label, $field_info, $field_note = '' )
{
	echo '<fieldset>';
	echo '  <div class="label">', $field_label, ':</div>';
	echo '  <div class="info">', $field_info;
	if( !empty($field_note) )
	{
		echo '&nbsp; <small class="notes">', $field_note, '</small>';
	}
	echo '</div>';
	echo "</fieldset>\n\n";
}

/**
 * Builds a form header and puts GET params of $action into hidden form inputs
 *
 * @deprecated Deprecated by (@link Form::begin_form())
 * waltercruz> still used by inc/widgets/widgets/_coll_search_form.widget.php
 */
function form_formstart( $action, $class = '', $name = '', $method = 'get', $id = '' )
{
	if( $method == 'get' )
	{
		$action = explode( '?', $action );
		if( isset($action[1]) )
		{ // we have GET params in $action
			$getparams = preg_split( '/&amp;|&/i', $action[1], -1, PREG_SPLIT_NO_EMPTY );
		}
		$action = $action[0];
	}

	echo '<form action="'.$action.'" method="'.$method.'"';

	// this is not xhtml strict, see: http://forums.b2evolution.net//viewtopic.php?t=8475
	// if( !empty($name) ) echo ' name="'.$name.'"';
	if( !empty($id) ) echo ' id="'.$id.'"';
	if( !empty($class) ) echo ' class="'.$class.'"';

	echo '>';

	if( isset($getparams) )
	{ // These need to be wrapped in a div to validate xhtml strict
		echo '<div>';
		foreach( $getparams as $param)
		{
			$param = explode( '=', $param );
			if( isset($param[1]) )
			{
				echo '<input type="hidden" name="'.$param[0].'" value="'.$param[1].'" />';
			}
		}
		// close the div
		echo '</div>';
	}
}


/**
 * Builds a textarea field.
 *
 * @deprecated Deprecated by (@link Form::textarea_input())
 *
 * @param string the name of the input field
 * @param string initial value
 * @param integer rows of the textarea field
 * @param string label displayed in front of the field
 * @param array params
 */
function form_textarea( $field_name, $field_value, $field_rows, $field_label, $field_params = array() )
{

	$textarea_rows = '';
	if( !empty( $field_rows ) )
	{
		$textarea_rows = ' rows="'.$field_rows.'"';
	}

	$textarea_cols = '';
	if( !empty( $field_params['cols'] ) )
	{
		$textarea_cols = ' cols="'.$field_params['cols'].'"';
	}

	$textarea_class = '';
	if( !empty( $field_params['class'] ) )
	{
		$textarea_class = ' class="'.$field_params['class'].'"';
	}

	$r = "<fieldset>\n"
			.'<div class="label"><label for="'.$field_name.'">'.$field_label.":</label></div>\n"
			.'<div class="input"><textarea name="'.$field_name.'" id="'.$field_name.'"'.$textarea_rows.$textarea_cols.$textarea_class.'>'
			.format_to_output($field_value, 'formvalue')
			.'</textarea>'."\n";

	if( !empty( $field_params['note'] ) )
	{
		$r .= '<span class="notes">'.$field_params['note'].'</span>';
	}

	$r .= "</div>\n</fieldset>\n\n";

	echo $r;
}


/**
 * Builds a fileselect item
 *
 * @param integer ID of file to generate thumbnail
 * @param array params
 */
function file_select_item( $file_ID, $params = array() )
{
	$FileCache = & get_FileCache();
	$File = & $FileCache->get_by_ID( $file_ID, false );

	$params = array_merge( array(
			'field_item_start' => '<div class="file_select_item" data-item-value="%value%">',
			'field_item_end' => '</div>',
			'size_name' => 'crop-64x64',
			'class' => '',
			'remove_file_text' => T_('Remove file'),
			'edit_file_text' => T_('Select another'),
			'max_file_num' => 1
		), $params );

	$r = str_replace( '%value%', $file_ID, $params['field_item_start'] );
	$r .= $params['max_file_num'] > 1 ? '<div>' : '';
	if( $File )
	{
		$r .= $File->get_thumb_imgtag( $params['size_name'], $params['class'] );
	}
	else
	{
		$r .= '<div class="bg-danger">'.T_('Not found').'</div>';
	}
	$blog_param = empty( $blog ) ? '' : '&amp;blog='.$blog;
	if( $params['max_file_num'] > 1 )
	{
		$r .= '<div>';
	}
	else
	{
		$r .= '<div class="item_actions">';
	}
	// Display a button to select another file:
	$r .= action_icon( $params['edit_file_text'], 'edit',
			'', ' '.T_('Select another'), NULL, $params['max_file_num'] > 1 ? NULL : 4,
			array( 'onclick' => 'return window.parent.file_select_attachment_window( this, true );',
			       'class' => 'btn btn-sm btn-info' ),
			array( 'class' => 'edit_file_icon' ) );
	// Display a button to remove current selected file:
	$r .= action_icon( $params['remove_file_text'], 'remove',
			'', ' '.T_('Remove'), NULL, $params['max_file_num'] > 1 ? NULL : 4,
			array( 'onclick' => 'return file_select_delete( this );',
			       'class' => 'btn btn-sm btn-default' ),
			array( 'class' => 'remove_file_icon' ) );
	$r .= '</div>';
	$r .= $params['max_file_num'] > 1 ? '</div>' : '';
	$r .= $params['field_item_end'];

	return $r;
}
?>