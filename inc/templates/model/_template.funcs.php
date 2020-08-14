<?php
/**
 * This file implements Template functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Render template content code depending on current locale
 * 
 * @param string Template code
 * @param array Parameters (by reference)
 * @param array Objects
 * @return string|boolean Rendered template or FALSE on wrong request
 */
function render_template_code( $code, & $params, $objects = array(), & $used_template_tags = NULL )
{
	$TemplateCache = & get_TemplateCache();
	if( $Template = & $TemplateCache->get_localized_by_code( $code, false, false ) )
	{	// Render variables in available Template:
		return render_template( $Template->template_code, $params, $objects, $used_template_tags );
	}

	return false;
}


/**
 * Render template content
 * 
 * @param string Template
 * @param array Parameters (by reference)
 * @param array Objects
 * @return string Rendered template
 */
function render_template( $template, & $params, $objects = array(), & $used_template_tags = NULL )
{
	$current_pos = 0;
	$r = '';

	// New
	preg_match_all( '/\[((?:(?:Cat|Coll|Comment|File|Form|Item|Link|Plugin|Tag|User|echo|set|param):)?([a-z0-9_]+))\|?((?:.|\n|\r|\t)*?)\]/i', $template, $matches, PREG_OFFSET_CAPTURE );
	foreach( $matches[0] as $i => $match )
	{
		// Output everything until new tag:
		$r .= substr( $template, $current_pos, $match[1] - $current_pos );
		$current_pos = $match[1] + strlen( $match[0] );

		// New tag to handle:
		$tag = $matches[1][$i][0];

		// Params specified for the tag:
		$tag_param_strings = empty( $matches[3][$i][0] ) ? NULL : $matches[3][$i][0];

		if( substr( $tag, 0, 4 ) == 'set:' || substr( $tag, 0, 6 ) == 'param:' )
		{	// Set new or default param value in the $params[] array used for the whole template (will affect all future template tags)

			// Override/Set new param OR Initialize default value?
			$override_param = ( substr( $tag, 0, 4 ) == 'set:' );

			$param_name = substr( $tag, $override_param ? 4 : 6 );
			$param_val  = substr( $tag_param_strings, strpos( $tag_param_strings, '=' ) + 1 );
			$param_strings = $param_name.'='.$param_val;
			$param_strings = explode( '|', $param_strings );

			foreach( $param_strings as $param_string )
			{
				if( empty( $param_string ) || ctype_space( $param_string ) )
				{	// Nothing here, ignore:
					continue;
				}

				$param_name = substr( $param_string, 0, strpos( $param_string, '=' ) );

				if( empty( $param_name ) )
				{	// Does not contain a param name, ignore:
					continue;
				}

				if( strpos( $param_name, '//' ) !== false )
				{	// We found a comment that we should remove:
					$param_name = preg_replace( '~(.*)//.*$~im','$1', $param_name );
				}

				// Trim off whitespace:
				$param_name = trim( $param_name );

				$param_val  = substr( $param_string, strpos( $param_string, '=' ) + 1 );

				if( $override_param || ! isset( $params[ $param_name ] ) )
				{	// Set new param or default param:
					// we MUST do this here and in & $params[] so that it sticks. This cannot be done in the callback or $this_tag_params[]
					$params[ $param_name ] = $param_val;
				}
			}
		}
		else
		{	// Process a normal template tag:

			// Decode PARAMS like |name=value|name=value]
			$this_tag_params = get_template_tag_params_from_string( $tag_param_strings, $params );

			if( is_array( $used_template_tags ) )
			{
				$used_template_tags[] = $tag; 
			}
			$r .= render_template_callback( $tag, $this_tag_params, $objects );
		}
	}

	// Print remaining template code:
	$r .= substr( $template, $current_pos );

	return $r;
}


/**
 * Get params of tempalte tag from provided string
 *
 * @param string Params in string format
 * @param array Default params
 * @return array Params in array format
 */
function get_template_tag_params_from_string( $tag_param_strings, $default_params = array() )
{
	$this_tag_params = $default_params;

	if( empty( $tag_param_strings ) )
	{	
		return $this_tag_params;
	}

	$tag_param_strings = explode( '|', $tag_param_strings );

	// Process each param individually:
	foreach( $tag_param_strings as $tag_param_string )
	{
		if( empty( $tag_param_string ) || ctype_space( $tag_param_string) )
		{
			continue;
		}

		$tag_param_name = substr( $tag_param_string, 0, strpos( $tag_param_string, '=' ) );

		if( strpos( $tag_param_name, '//' ) !== false )
		{	// We found a comment that we should remove:
			$tag_param_name = preg_replace( '~(.*)//.*$~im','$1', $tag_param_name );
		}

		// Trim off whitespace:
		$tag_param_name = trim( $tag_param_name );

		if( $tag_param_name === '' )
		{	// Skip empty param:
			continue;
		}

		$tag_param_val  = substr( $tag_param_string, strpos( $tag_param_string, '=' ) + 1 );

		if( preg_match('/\$([a-z_]+)\$/i', $tag_param_val, $tag_param_val_matches ) )
		{	// We have a variable to replace: // TODO: allow multiple variable replace
			$found_param_name = $tag_param_val_matches[1];
			if( isset( $default_params[$found_param_name] ) )
			{	// We have an original param of that name:
				$tag_param_val = $default_params[$found_param_name];
			}
		}

		// TODO: need to escape " and > from $tag_param_val, otherwise they will end up breaking something

		$this_tag_params[$tag_param_name] = $tag_param_val;
	}

	return $this_tag_params;
}

/**
 * Callback function to replace variables in template
 * 
 * @param string Variable to be replaced
 * @param array Additional parameters (by reference)
 * @param array Objects
 * @return string Replacement string
 */
function render_template_callback( $var, $params, $objects = array() )
{
	// Get scope and var name:
	preg_match( '#^(([a-z]+):)?(.+)$#i', $var, $match_var );
	$scope = ( empty( $match_var[2] ) ? 'Item': $match_var[2] );
	$var = $scope.':'.$match_var[3];
	switch( $scope )
	{
		case 'Cat':
			global $Chapter;
			$rendered_Chapter = ( !isset( $objects['Chapter'] ) ? $Chapter : $objects['Chapter'] );
			if( empty( $rendered_Chapter ) || ! ( $rendered_Chapter instanceof Chapter ) )
			{
				return get_rendering_error( '['.$var.']: Object Chapter/Category is not defined at this moment.', 'span' );
			}
			break;

		case 'Coll':
			global $Blog;
			$rendered_Blog = ( !isset( $objects['Collection'] ) ? $Blog : $objects['Collection'] );
			if( empty( $rendered_Blog ) || ! ( $rendered_Blog instanceof Blog ) )
			{
				return get_rendering_error( '['.$var.']: Object Collection/Blog is not defined at this moment.', 'span' );
			}
			break;

		case 'Comment':
			global $Comment;
			$rendered_Comment = ( !isset( $objects['Comment'] ) ? $Comment : $objects['Comment'] );
			if( empty( $rendered_Comment ) || ! ( $rendered_Comment instanceof Comment ) )
			{
				return get_rendering_error( '['.$var.']: Object Comment is not defined at this moment.', 'span' );
			}
			break;

		case 'File':
			global $File;
			$rendered_File = ( !isset( $objects['File'] ) ? $File : $objects['File'] );
			if( empty( $rendered_File ) || ! ( $rendered_File instanceof File ) )
			{
				return get_rendering_error( '['.$var.']: Object File is not defined at this moment.', 'span' );
			}
			break;

		case 'Form':
			global $Form;
			$rendered_Form = ( !isset( $objects['Form'] ) ? $Form : $objects['Form'] );
			if( empty( $rendered_Form ) || ! ( $rendered_Form instanceof Form ) )
			{
				return get_rendering_error( '['.$var.']: Object Form is not defined at this moment.', 'span' );
			}
			break;

		case 'Item':
			global $Item;

			$rendered_Item = ( !isset( $objects['Item'] ) ? $Item : $objects['Item'] );

			if( empty( $rendered_Item ))
			{
				return get_rendering_error( '['.$var.']: Object Item is not defined at this moment.', 'span' );
			}
			if( ! ( $rendered_Item instanceof Item ) )
			{
				return get_rendering_error( 'Item object has class <code>'.get_class($rendered_Item).'</code> instead of expected <code>Item</code>.', 'span' );
			}
			break;

		case 'Link':
			// do nothing
			break;

		case 'Plugin':
			global $Plugins;

			$rendered_Plugin = & $Plugins->get_by_code( $match_var[3] );

			if( empty( $rendered_Plugin ) )
			{
				return get_rendering_error( 'Plugin <code>'.$match_var[3].'</code> is not installed.', 'span' );
			}

			$var = $scope;
			break;

		case 'Tag':
			$tag = ( !isset( $objects['tag'] ) ? $tag : $objects['tag'] );

			if( empty( $tag ))
			{
				return get_rendering_error( '['.$var.']: Tag is not defined at this moment.', 'span' );
			}
			break;

		case 'echo':
			$param_name = substr( $var, 5 );
			if( ! isset( $params[ $param_name ] ) )
			{	// Param is not found:
				return get_rendering_error( 'Param <code>'.$param_name.'</code> is not passed.', 'span' );
			}
			elseif( ! is_scalar( $params[ $param_name ] ) )
			{	// Param is not scalar and cannot be printed on screen:
				return get_rendering_error( 'Param <code>'.$param_name.'</code> is not scalar.', 'span' );
			}
			break;

		case 'User':
			global $User;
			$rendered_User = ( !isset( $objects['User'] ) ? $User : $objects['User'] );
			if( empty( $rendered_User ) || ! ( $rendered_User instanceof User ) )
			{
				return get_rendering_error( '['.$var.']: Object User is not defined at this moment.', 'span' );
			}
			break;

		default:
			return get_rendering_error( '['.$var.']: Scope "'.$scope.':" is not recognized.', 'span' );
	}

	$match_found = true;

	ob_start();
	switch( $var )
	{
		// Chapter / Category:
		case 'Cat:background_image_css':
			echo $rendered_Chapter->get_background_image_css( $params );
			break;

		case 'Cat:description':
			echo $rendered_Chapter->dget( 'description' );
			break;

		case 'Cat:image':
			echo $rendered_Chapter->get_image_tag( array_merge( array(
					'before_classes' => '', // Allow injecting additional classes into 'before'
					'size'        => 'crop-256x256',
					'link_to'     => '#category_url',
					'placeholder' => '#folder_icon',
				), $params ) );
			break;

		case 'Cat:image_url':
			echo $rendered_Chapter->get_image_url( $params );
			break;

		case 'Cat:name':
			echo $rendered_Chapter->dget( 'name' );
			break;

		case 'Cat:permalink':
			echo $rendered_Chapter->get_permanent_link( array_merge( array(
					'text'   => '#name',
					'title'  => '',
				), $params ) );
			break;

		// Collection:
		case 'Coll:shortname':
			echo $rendered_Blog->dget( 'shortname' );
			break;

		// Comment:
		case 'Comment:author':
			echo $rendered_Comment->get_author( array_merge( array(
					'link_text' => 'auto',		// select login or nice name automatically
				), $params ) );
			break;

		case 'Comment:creation_time':
			$temp_params = array_merge( array(
					'format' => '#short_date_time',
					'useGM'  => false,
				), $params );
			echo $rendered_Comment->get_creation_time( $temp_params['format'], $temp_params['useGM'] );
			break;

		case 'Comment:content':
			$temp_params =  array_merge( array(
					'format'           => 'htmlbody',
					'ban_urls'         => false,
					'show_attachments' => false,
				), $params );
			echo $rendered_Comment->content( $temp_params['format'], $temp_params['ban_urls'], $temp_params['show_attachments'], $temp_params );
			break;

		case 'Comment:excerpt':
			echo $rendered_Comment->get_excerpt( $params );
			break;

		case 'Comment:permalink':
			$rendered_Comment->permanent_link( array_merge( array(
					'text'   => '#item#',
					'title'  => '',  // No tooltip by default
				), $params ) );
			break;

		// File:
		case 'File:description':
			echo $rendered_File->get_description( $params );
			break;

		case 'File:file_size':
			echo $rendered_File->get_size_formatted();
			break;

		case 'File:icon':
			echo $rendered_File->get_icon();
			break;

		case 'File:type':
			echo $rendered_File->get_type();
			break;

		case 'File:url':
			echo $rendered_File->get_url();
			break;

		case 'File:file_link':
			echo $rendered_File->get_file_link( $params );	
			break;

		// Form:
		case 'Form:country':
			global $Settings;

			$country = param( 'country', 'integer', 0 );
			$temp_params = array(
					'name'        => 'country',
					'value'       => $country,
					'label'       => T_('Country'),
					'hide_label'  => false,
					'note'        => '',
					'bottom_note' => '',
					'class'       => '',
					'style'       => '',
					'required'    => isset( $params['reg1_required'] ) ? in_array( 'country', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
				);

			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$CountryCache = & get_CountryCache();
			$rendered_Form->select_country( $temp_params['name'], $temp_params['value'], $CountryCache, $temp_params['label'], $temp_params );
			break;

		case 'Form:email':
			global $dummy_fields;

			$email = utf8_strtolower( param( $dummy_fields['email'], 'string', '' ) );
			if( isset( $objects['register_user_data']['email'] ) )
			{
				$email = $objects['register_user_data']['email'];
			}
			$temp_params = array(
					'name'        => $dummy_fields['email'],
					'value'       => $email,
					'label'       => T_('Email'),
					'hide_label'  => false,
					'note'        => '',
					'bottom_note' => T_('We respect your privacy. Your email will remain strictly confidential.'),
					'placeholder' => $params['register_use_placeholders'] ? T_('Email address') : '',
					'size'        => 50,
					'maxlength'   => 255,
					'class'       => 'input_text wide_input',
					'style'       => '',
					'required'    => isset( $params['reg1_required'] ) ? in_array( 'email', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
			);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->email_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params );
			break;

		case 'Form:firstname':
			global $Settings;

			$firstname = param( 'firstname', 'string', '' );
			$temp_params = array(
					'name'        => 'firstname',
					'value'       => $firstname,
					'label'       => T_('First name'),
					'hide_label'  => false,
					'note'        => T_('Your real first name'),
					'bottom_note' => '',
					'placeholder' => '',
					'size'        => 18,
					'maxlength'   => 50,
					'class'       => 'input_text',
					'style'       => '',
					'required'    => isset( $params['reg1_required'] ) ? in_array( 'firstname', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->text_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params['note'], $temp_params );
			break;

		case 'Form:gender':
			global $Settings;
		
			$gender = param( 'gender', 'string', false );
			$temp_params = array(
					'name'        => 'gender',
					'value'       => $gender,
					'label'       => T_('I am'),
					'hide_label'  => false,
					'note'        => '',
					'bottom_note' => '',
					'class'       => '',
					'style'       => '',
					'required'    => isset( $params['reg1_required'] ) ? in_array( 'gender', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->radio_input( $temp_params['name'], $temp_params['value'], array(
					array( 'value' => 'M', 'label' => T_('A man') ),
					array( 'value' => 'F', 'label' => T_('A woman') ),
					array( 'value' => 'O', 'label' => T_('Other') ),
				), $temp_params['label'], $temp_params );
			break;

		case 'Form:lastname':
			global $Settings;

			$lastname = param( 'lastname', 'string', '' );
			$temp_params = array(
					'name'        => 'lastname',
					'value'       => $lastname,
					'label'       => T_('Last name'),
					'hide_label'  => false,
					'note'        => T_('Your real last name'),
					'bottom_note' => '',
					'placeholder' => '',
					'size'        => 18,
					'maxlength'   => 50,
					'class'       => 'input_text',
					'style'       => '',
					'required'    => isset( $params['reg1_required'] ) ? in_array( 'lastname', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->text_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params['note'], $temp_params );
			break;

		case 'Form:locale':
			global $Settings, $current_locale;

			$temp_params = array(
					'name'        => 'locale',
					'value'       => $current_locale,
					'label'       => T_('Locale'),
					'hide_label'  => false,
					'note'        => T_('Preferred language'),
					'bottom_note' => '',
					'class'       => '',
					'style'       => '',
					'required'    => isset( $params['reg1_required'] ) ? in_array( 'locale', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );
			
			$rendered_Form->select_input( $temp_params['name'], $temp_params['value'], 'locale_options_return', $temp_params['label'], $temp_params );
			break;

		case 'Form:login':
			global $dummy_fields;

			$login = param( $dummy_fields['login'], 'string', '' );
			if( isset( $objects['register_user_data']['login'] ) )
			{
				$login = $objects['register_user_data']['login'];
			}
			$temp_params = array(  // Here, we make sure not to modify $params
					'name'         => $dummy_fields['login'],
					'value'        => $login,
					'label'        => /* TRANS: noun */ T_('Login'),
					'hide_label'   => false,
					'note'         => $params['register_use_placeholders'] ? '' : T_('Choose a username').'.',
					'bottom_note'  => '',
					'placeholder'  => $params['register_use_placeholders'] ? T_('Choose a username') : '',
					'size'         => 22,
					'maxlength'    => 20,
					'class'        => 'input_text',
					'style'        => 'width:'.( $params['register_field_width'] - 2 ).'px',
					'required'     => isset( $params['reg1_required'] ) ? in_array( 'login', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'input_suffix' => ' <span id="login_status"></span><span class="help-inline"><div id="login_status_msg" class="red"></div></span>',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->text_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params['note'], $temp_params );
			break;

		case 'Form:password':
			global $dummy_fields;

			$temp_params = array(
					'name'         => $dummy_fields['pass1'],
					'value'        => '',
					'label'        => T_('Password'),
					'hide_label'   => false,
					'note'         => $params['register_use_placeholders'] ? '' : T_('Choose a password').'.',
					'placeholder'  => $params['register_use_placeholders'] ? T_('Choose a password') : '',
					'bottom_note'  => '',
					'size'         => 18,
					'maxlength'    => 70,
					'class'        => 'input_text',
					'style'        => 'width:'.$params['register_field_width'].'px',
					'required'     => isset( $params['reg1_required'] ) ? in_array( 'password', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'autocomplete' => 'off',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->password_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params );

			$temp_params = array(
					'name_confirm'         => $dummy_fields['pass2'],
					'value_confirm'        => '',
					'label_confirm'        => '',
					'hide_label_confirm'   => false,
					'note_confirm'         => ( $params['register_use_placeholders'] ? '' : T_('Please type your password again').'.' ).'<div id="pass2_status" class="red"></div>',
					'bottom_note_confirm'  => '',
					'placeholder_confirm'  => $params['register_use_placeholders'] ? T_('Please type your password again') : '',
					'size_confirm'         => 18,
					'maxlength_confirm'    => 70,
					'class_confirm'        => 'input_text',
					'style_confirm'        => 'width:'.$params['register_field_width'].'px',
					'required_confirm'     => isset( $params['reg1_required'] ) ? in_array( 'password', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'autocomplete_confirm' => 'off',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );
			
			$confirm_params = array();
			foreach( $temp_params as $key => $value )
			{
				if( substr( $key, -8 ) === '_confirm' )
				{
					$key = substr( $key, 0, strlen( $key ) - 8 );
				}
				$confirm_params[$key] = $value;
			}

			$rendered_Form->password_input( $confirm_params['name'], $confirm_params['value'], $confirm_params['size'], $confirm_params['label'], $confirm_params );				
			break;

		case 'Form:search_author':
			$search_author = param( 'search_author', 'string', NULL );
			$temp_params = array(  // Here, we make sure not to modify $params
					'name'         => 'search_author',
					'value'        => $search_author,
					'label'        => '',
					'hide_label'   => true,
					'note'         => '',
					'bottom_note'  => '',
					'placeholder'  => T_('Any author'),
					'size'         => '',
					'maxlength'    => '',
					'class'        => 'input_text'.is_logged_in() ? '' : ' autocomplete_login',
					'style'        => '',
					'required'     => false,
					'input_suffix' => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );
				
			$rendered_Form->text_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params['note'], $temp_params );
			break;

		case 'Form:search_content_age':
			$search_content_age = param( 'search_content_age', 'string' );
			$content_age_options = array(
					''     => T_('Any time'),
					'hour' => T_('Last hour'),
					'day'  => T_('Less than a day'),
					'week' => T_('Less than a week'),
					'30d'  => T_('Last 30 days'),
					'90d'  => T_('Last 90 days'),
					'year' => T_('Last year'),
				);

			$temp_params = array(
					'name'        => 'search_content_age',
					'value'       => $search_content_age,
					'label'       => T_('Content age'),
					'hide_label'  => true,
					'note'        => '',
					'bottom_note' => '',
					'class'       => '',
					'style'       => '',
					'required'    => false,
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );
			
			$rendered_Form->select_input_array( $temp_params['name'], $temp_params['value'], $content_age_options, $temp_params['label'], $temp_params['note'], $temp_params );
			break;

		case 'Form:search_content_type':
			global $Blog;

			if( ! $Blog )
			{
				return get_rendering_error( '['.$var.']: Object Blog is not defined at this moment.', 'span' );
			}

			$search_type = param( 'search_type', 'string', NULL );
			$content_type_options = array();
			if( $Blog->get_setting( 'search_include_posts' ) )
			{
				$content_type_options['item'] = T_('Posts');
			}
			if( $Blog->get_setting( 'search_include_cmnts' ) )
			{
				$content_type_options['comment'] = T_('Comments');
			}
			if( $Blog->get_setting( 'search_include_metas' ) &&
			    check_user_perm( 'meta_comment', 'view', false, $Blog->ID )  )
			{
				$content_type_options['meta'] = T_('Internal comments');
			}
			if( $Blog->get_setting( 'search_include_files' ) )
			{
				$content_type_options['file'] = T_('Files');
			}
			if( $Blog->get_setting( 'search_include_cats' ) )
			{
				$content_type_options['category'] = T_('Categories');
			}
			if( $Blog->get_setting( 'search_include_tags' ) )
			{
				$content_type_options['tag'] = T_('Tags');
			}

			if( count( $content_type_options ) > 1 )
			{
				$content_type_options = array( '' => T_('All') ) + $content_type_options;
				$temp_params = array(
						'name'        => 'search_type',
						'value'       => $search_type,
						'label'       => T_('Content type'),
						'hide_label'  => true,
						'note'        => '',
						'bottom_note' => '',
						'class'       => '',
						'style'       => '',
						'required'    => false,
					);
				// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
				$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );
				
				$rendered_Form->select_input_array( $temp_params['name'], $temp_params['value'], $content_type_options, $temp_params['label'], $temp_params['note'], $temp_params );
			}
			else
			{	// Do not display anything
				return;
			}
			break;

		case 'Form:search_input':
			$search_term = param('s', 'string', '');
			$temp_params = array(  // Here, we make sure not to modify $params
					'name'         => 's',
					'value'        => $search_term,
					'label'        => '',
					'hide_label'   => true,
					'note'         => '',
					'placeholder'  => '',
					'size'         => 25,
					'maxlength'    => 100,
					'class'        => 'input_text',
					'style'        => '',
					'required'     => false,
					'input_suffix' => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );
				
			$rendered_Form->text_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params['note'], $temp_params );
			break;

		case 'Form:submit':
			$temp_params = array(
					'name'       => 'submit',
					'value'      => T_('Submit'),
					'class'      => 'btn btn-primary',
					'style'      => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->submit_input( $temp_params );
			break;

		// Item:
		case 'Item:author':
			$rendered_Item->author( array_merge( array(
					'link_text' => 'auto',		// select login or nice name automatically
				), $params ) );
			break;
		
		case 'Item:background_image_css':
			echo $rendered_Item->get_background_image_css( $params );
			break;

		case 'Item:cat_name':
			if( $item_main_Chapter = & $rendered_Item->get_main_Chapter() )
			{
				echo $item_main_Chapter->dget( 'name' );
			}
			break;

		case 'Item:categories':
			$rendered_Item->categories( array_merge( array(
					'before'          => '',  // For some reason the core has ' ' as default, which is not good for templates
					'after'           => '',  // For some reason the core has ' ' as default, which is not good for templates
				), $params ) );
			break;

		case 'Item:content_extension':
			echo $rendered_Item->content_extension( $params );
			break;

		case 'Item:content_teaser':
			echo $rendered_Item->content_teaser( $params );
			break;

		case 'Item:contents_last_updated':
		case 'Item:last_updated':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_contents_last_updated_ts( $temp_params['format'] );
			break;

		case 'Item:creation_time':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_creation_time( $temp_params['format'] );
			break;

		case 'Item:custom':
			$temp_params = array_merge( array( // Here, we make sure not to modify $params
					'field' => '',
				), $params );
			$rendered_Item->custom( $temp_params );
			break;

		case 'Item:custom_fields':
			echo $rendered_Item->get_custom_fields( $params );
			break;

		case 'Item:edit_link':
			$rendered_Item->edit_link( $params );
			break;

		case 'Item:excerpt':
			$rendered_Item->excerpt( array_merge( array(
					'before'              => '',
					'after'               => '',
					'excerpt_before_more' => ' <span class="evo_post__excerpt_more_link">',
					'excerpt_after_more'  => '</span>',
					'excerpt_more_text'   => '#more+arrow',
				), $params ) );
			break;

		case 'Item:feedback_link':
			echo $rendered_Item->get_feedback_link( array_merge( array(
					'show_in_single_mode' => true,
				), $params ) );
			break;

		case 'Item:files':
			echo $rendered_Item->get_files( $params );
			break;

		case 'Item:flag_icon':
			echo $rendered_Item->get_flag( $params );
			break;

		case 'Item:footer':
			echo $rendered_Item->footer( array_merge( array( // Here, we make sure not to modify $params
					'block_start' => '<div class="evo_post_footer">',
					'block_end'   => '</div>',
				), $params ) );
			break;

		case 'Item:history_link':
			echo $rendered_Item->get_history_link( array_merge( array(
					'link_text' => T_('View change history'),
				), $params ) );
			break;

		case 'Item:id':
			echo $rendered_Item->ID;
			break;

		case 'Item:image_url':
			echo $rendered_Item->get_image_url( $params );
			break;

		case 'Item:images':
			echo $rendered_Item->get_images( array_merge( array(
					'restrict_to_image_position' => 'teaser,teaserperm,teaserlink,aftermore', 	// 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'cover'|'background',
																// '#teaser_all' => 'teaser,teaserperm,teaserlink',
																// '#cover_and_teaser_all' => 'cover,background,teaser,teaserperm,teaserlink'
					'limit'                      => 1000, // Max # of images displayed
					'before'                     => '<div>',
					'before_image'               => '<figure class="evo_image_block">',
					'before_image_classes'       => '', // Allow injecting additional classes into 'before image'
					'before_image_legend'        => '<div class="evo_image_legend">',
					'after_image_legend'         => '</div>',
					'after_image'                => '</figure>',
					'after'                      => '</div>',
					'image_size'                 => 'fit-720x500',
					'image_size_x'               => 1, // Use '2' to build 2x sized thumbnail that can be used for Retina display
					'image_sizes'                => NULL, // Simplified "sizes=" attribute for browser to select correct size from "srcset=".
																// Must be set DIFFERENTLY depending on WIDGET/CONTAINER/SKIN LAYOUT. Each time we must estimate the size the image will have on screen.
																// Sample value: (max-width: 430px) 400px, (max-width: 670px) 640px, (max-width: 991px) 720px, (max-width: 1199px) 698px, 848px
					'image_link_to'              => 'original', // Can be 'original' (image), 'single' (this post), an be URL, can be empty
					// Note: Widget MAY have set the following for same CAT navigation:
					//	'post_navigation' => 'same_category',			// Stay in the same category if Item is cross-posted
					//	'nav_target'      => $params['chapter_ID'],	// for use with 'same_category' : set the category ID as nav target
					// Note: Widget MAY have set the following for same COLL navigation:
					//	'target_blog'     => 'auto', 						// Stay in current collection if it is allowed for the Item
				), $params ) );
			break;

		case 'Item:issue_date':
			$rendered_Item->issue_date( $params );
			break;

		case 'Item:issue_time':
			$rendered_Item->issue_time( $params );
			break;

		case 'Item:last_touched':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_last_touched_ts( $temp_params['format'] );
			break;

		case 'Item:lastedit_user':
			$rendered_Item->lastedit_user( array_merge( array(
					'link_text' => 'auto',		// select login or nice name automatically
				), $params ) );
			break;

		case 'Item:location':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'before'    => '<div class="evo_post_location"><strong>'.T_('Location').': </strong>',
					'after'     => '</div>',
					'separator' => ', ',
				), $params );
			echo $rendered_Item->get_location( $temp_params['before'], $temp_params['after'], $temp_params['separator'] );
			break;

		case 'Item:mod_date':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_mod_date( $temp_params['format'] );
			break;

		case 'Item:more_link':
			// Display "more" link to "After more" or follow-up anchor
			// WARNING: does not work if no "after more" part
			// If you want a "go from excerpt to full post, use "Item:permalink"
			echo $rendered_Item->get_more_link( array_merge( array(
					'before' => '<p class="evo_post_more_link">',
					'after'  => '</p>',
				), $params ) );
			break;

		case 'Item:page_links':
			echo $rendered_Item->get_page_links( array_merge( array(
					'separator'   => '&middot; ',
				), $params ) );
			break;

		case 'Item:permalink':
		case 'Item:permanent_link':
			$rendered_Item->permanent_link( array_merge( array(
					'text'   => '#title',
					'title'  => '',  // No tooltip by default
					// Note: Widget MAY have set the following for same CAT navigation:
					//	'post_navigation' => 'same_category',			// Stay in the same category if Item is cross-posted
					//	'nav_target'      => $params['chapter_ID'],	// for use with 'same_category' : set the category ID as nav target
					// Note: Widget MAY have set the following for same COLL navigation:
					//	'target_blog'     => 'auto', 						// Stay in current collection if it is allowed for the Item
				), $params ) );
			break;

		case 'Item:permanent_url':
			$temp_params = array_merge( array(  
					'target_blog'     => '',		
					'post_navigation' => '',		
					'nav_target'      => NULL,		
				), $params );
			echo $rendered_Item->get_item_url( $temp_params['target_blog'], $temp_params['post_navigation'], $temp_params['nav_target'] );
			break;

		case 'Item:propose_change_link':
			$rendered_Item->propose_change_link( array_merge( array(
					'text'   => T_('Propose a change'),
				), $params ) );
			break;

		case 'Item:read_status':
			$rendered_Item->display_unread_status( array_merge( array(
					'style'  => 'text',
					'before' => '<span class="evo_post_read_status">',
					'after'  => '</span>'
				), $params ) );
			break;

		case 'Item:refresh_contents_last_updated_link':
			echo $rendered_Item->get_refresh_contents_last_updated_link( $params );
			break;

		case 'Item:tags':
			$rendered_Item->tags( array_merge( array(
					'before'          => '',  // For some reason the core has '<div>... ' as default, which is not good for templates
					'after'           => '',  // For some reason the core has '</div>' as default, which is not good for templates
				), $params ) );
			break;

		case 'Item:title':
			echo $rendered_Item->dget( 'title' );
			break;

		case 'Item:type':
			echo $rendered_Item->get_type_setting( 'name' );
			break;

		case 'Item:url_link':
			$rendered_Item->url_link( $params );
			break;

		case 'Item:visibility_status':
			if( $rendered_Item->status != 'published' )
			{
				$temp_params = array_merge( array(
						'status_template' => '<div class="evo_status evo_status__$status$ badge" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
					), $params );
				// Override param 'template' to avoid conflict in params in the status function,
				// because here param 'template' contains a code of quick template:
				$temp_params['template'] = $temp_params['status_template'];
				echo $rendered_Item->get_format_status( $temp_params );
			}
			break;

		// Link:
		case 'Link:disp':
			$temp_params = array_merge( array(
					'text'           => '',
					'class'          => '',
					'max_url_length' => NULL,
				), $params );

			if( empty( $temp_params['disp'] ) )
			{
				display_rendering_error( '['.$var.']: Missing required param "disp".', 'span' );
				break;
			}

			switch( $temp_params['disp'] )
			{
				case 'login':
					$source      = param( 'source', 'string', 'register form' );
					$redirect_to = param( 'redirect_to', 'url', '' );
					$return_to   = param( 'return_to', 'url', '' );

					// We are not using 
					$temp_params = array_merge( array(
							'source'             => $source,
							'redirect_to'        => $redirect_to,
							'return_to'          => $return_to,
							'force_normal_login' => false,
							'blog_ID'            => NULL,
							'blog_page'          => 'loginurl',
						), $temp_params );
	
					$disp_url = get_login_url( $temp_params['source'], $temp_params['redirect_to'], $temp_params['force_normal_login'],
							$temp_params['blog_ID'], $temp_params['blog_page'] );
					break;
				
				default:
					$temp_params = array_merge( array(
							'params' => '',
						), $temp_params );

					$disp_url = get_dispctrl_url( $temp_params['disp'], $temp_params['params'] );
			}
		
			if( $disp_url )
			{
				echo get_link_tag( $disp_url, $temp_params['text'], $temp_params['class'], $temp_params['max_url_length'] );
			}
			else
			{
				display_rendering_error( '['.$var.']: disp "'.$temp_params['disp'].'" is not recognized.', 'span' );
			}
			break;

		case 'Plugin':
			$rendered_Plugin->SkinTag( $params );
			break;

		// Tag:
		case 'Tag:name':
			echo $tag;
			break;

		case 'Tag:permalink':
			global $Blog;
			$rendered_Blog = ( !isset( $objects['Collection'] ) ? $Blog : $objects['Collection'] );
			if( empty( $rendered_Blog ) || ! ( $rendered_Blog instanceof Blog ) )
			{
				return get_rendering_error( '['.$var.']: Object Collection/Blog is not defined at this moment.', 'span' );
			}

			$temp_params = array(
						'class' => '',
						'style' => '',
						'rel'   => NULL,
						'text'  => NULL,
						'title' => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			echo $rendered_Blog->get_tag_link( $tag, $temp_params['text'], $temp_params );
			break;

		// User:
		case 'User:custom':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'field'       => NULL,
					'before'      => '',
					'before_item' => '',
					'after_item'  => '',
					'after'       => '',
					'separator'   => '',
					'limit'       => NULL,
				), $params );
			$userfield_values = $rendered_User->userfield_values_by_code( $temp_params['field'], true );

			if( is_array( $userfield_values ) )
			{	// We expect $userfield_values to be an Array:
				if( isset( $temp_params['limit'] ) )
				{
					$userfield_values = array_slice( $userfield_values, 0, ( int ) $temp_params['limit'] );
				}

				if( ! empty( $temp_params['before_item'] ) || ! empty( $temp_params['after_item'] ) )
				{
					$temp_values = array();
					foreach( $userfield_values as $userfield_value )
					{
						$temp_values[] = $temp_params['before_item'].$userfield_value.$temp_params['after_item'];
					}
					$userfield_values = $temp_values;
				}

				echo format_to_output( $temp_params['before'].implode( $temp_params['separator'], $userfield_values ).$temp_params['after'] );
			}
			else
			{
				debug_die( '$userfield_values is not an array!' );
			}
			break;

		case 'User:email':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => 'htmlbody',		
				), $params );
			$rendered_User->email( $temp_params['format'] );
			break;

		case 'User:first_name':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => 'htmlbody',		
				), $params );
			$rendered_User->first_name( $temp_params['format'] );
			break;

		case 'User:fullname':
			echo $rendered_User->get( 'fullname' );
			break;

		case 'User:id':
			echo $rendered_User->ID;
			break;

		case 'User:last_name':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => 'htmlbody',		
				), $params );
			$rendered_User->last_name( $temp_params['format'] );
			break;

		case 'User:login':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => 'htmlbody',		
				), $params );
			echo $rendered_User->login( $temp_params['format'] );
			break;

		case 'User:nick_name':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => 'htmlbody',		
				), $params );
			$rendered_User->nick_name( $temp_params['format'] );
			break;

		case 'User:picture':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'size'                => 'crop-top-64x64',
					'class'               => 'avatar',
					'align'               => '',
					'zoomable'            => false,
					'avatar_overlay_text' => '',
					'lightbox_group'      => '',
					'tag_size'            => NULL,
					'protocol'            => '',
				), $params );
			echo $rendered_User->get_avatar_imgtag( $temp_params['size'], $temp_params['class'], $temp_params['align'], $temp_params['zoomable'],
					$temp_params['avatar_overlay_text'], $temp_params['lightbox_group'], $temp_params['tag_size'], $temp_params['protocol'] );
			break;

		case 'User:preferred_name':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => 'htmlbody',		
				), $params );
			$rendered_User->preferred_name( $temp_params['format'] );
			break;

		// Others
		default:
			switch( $scope )
			{
				case 'echo':
					// Print param var value, No need check this because all done above:
					echo $params[ $param_name ];
					break;

				default:
					// Unknown template var:
					$match_found = false;
			}
	}
	$r = ob_get_clean();

	if( $match_found )
	{
		return $r;
	}
	else
	{	// Display error for not recognized variable:
		return get_rendering_error( '['.$var.'] is not recognized.', 'span' );
	}
}


/**
 * Validate Template code for uniqueness. This will add a numeric suffix if the specified template code is already in use.
 *
 * @param string Template code to validate
 * @param integer ID of template
 * @param string The name of the template code column
 * @param string The name of the template ID column
 * @param string The name of the template table to use
 * @return string Unique template code
 */
function unique_template_code( $code, $ID = 0, $db_code_fieldname = 'tpl_code', $db_ID_fieldname = 'tpl_ID', $db_table = 'T_templates' )
{
	global $DB, $Messages;
	
	load_funcs( 'locales/_charset.funcs.php' );

	// Convert code:
	$code = strtolower( replace_special_chars( $code, NULL, false, '_' ) );
	$base = preg_replace( '/_[0-9]+$/', '', $code );

	// CHECK FOR UNIQUENESS:
	// Find all occurrences of code-number in the DB:
	$SQL = new SQL( 'Find all occurrences of template code "'.$base.'..."' );
	$SQL->SELECT( $db_code_fieldname.', '.$db_ID_fieldname );
	$SQL->FROM( $db_table );
	$SQL->WHERE( $db_code_fieldname." REGEXP '^".$base."(_[0-9]+)?$'" );

	$exact_match = false;
	$highest_number = 0;
	$use_existing_number = NULL;

	foreach( $DB->get_results( $SQL->get(), ARRAY_A ) as $row )
	{
		$existing_code = $row[$db_code_fieldname];
		if( ( $existing_code == $code ) && ( $row[$db_ID_fieldname] != $ID ) )
		{	// Specified code already in use by another template, we'll have to change the number.
			$exact_match = true;
		}
		if( preg_match( '/_([0-9]+)$/', $existing_code, $matches ) )
		{	// This template code already has a number, we extract it:
			$existing_number = (int)$matches[1];

			if( ! isset( $use_existing_number ) && $row[$db_ID_fieldname] == $ID )
			{	// if there is a numbered entry for the current ID, use this:
				$use_existing_number = $existing_number;
			}

			if( $existing_number > $highest_number )
			{	// This is the new high
				$highest_number = $existing_number;
			}
		}
	}

	if( $exact_match )
	{	// We got an exact (existing) match, we need to change the number:
		$number = $use_existing_number ? $use_existing_number : ( $highest_number + 1 );
		$code = $base.'_'.$number;
	}

	return $code;
}


/**
 * Get list of context available to templates
 */
function get_template_contexts( $format = 'keys', $exclude = array() )
{
	$template_contexts = array(
			'custom1'               => sprintf( T_('Custom %d'), 1 ),
			'custom2'               => sprintf( T_('Custom %d'), 2 ),
			'custom3'               => sprintf( T_('Custom %d'), 3 ),
			'content_list_master'   => T_('Content List Master'),
			'content_list_item'     => T_('Content List Item'),
			'content_list_category' => T_('Content List Category'),
			'content_block'         => T_('Content Block'),
			'item_details'          => T_('Item Details'),
			'item_content'          => T_('Item Content'),
			'registration_master'   => T_('Registration Master'),
			'registration'          => T_('Registration'),
			'search_form'           => T_('Search Form'),
			'search_result'         => T_('Search Result')
		);

	if( !empty( $exclude ) )
	{
		$template_contexts = array_diff_key( $template_contexts, array_fill_keys( $exclude, NULL ) );
	}

	switch( $format )
	{
		case 'keys':
			$template_contexts = array_keys( $template_contexts );
			break;

		case 'raw':
		default:
			// Do nothing
	}

	return $template_contexts;
}
?>
