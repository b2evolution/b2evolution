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
function render_template_code( $code, & $params, $objects = array() )
{
	global $current_locale;

	$TemplateCache = & get_TemplateCache();
	if( ! ( $Template = & $TemplateCache->get_by_code( $code, false, false ) ) )
	{
		return false;
	}

	// Check if the template has a child matching the current locale:
	$localized_templates = $Template->get_localized_templates( $current_locale );
	if( ! empty( $localized_templates ) )
	{	// Use localized template:
		$Template = & $localized_templates[0];
	}

	if( $Template )
	{	// Render variables in available Template:
		return render_template( $Template->template_code, $params, $objects );
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
function render_template( $template, & $params, $objects = array() )
{
	$current_pos = 0;
	$r = '';

	/*
	// Old
	preg_match_all( '/\$([a-z_\:]+)\$/i', $template, $matches, PREG_OFFSET_CAPTURE );
	foreach( $matches[0] as $i => $match )
	{
		$r .= substr( $template, $current_pos, $match[1] - $current_pos );
		$current_pos = $match[1] + strlen( $match[0] );
		$r .= render_template_callback( $matches[1][$i][0], $params, $objects );
	}
	*/

	// New
	preg_match_all( '/\[((?:(?:Item|Cat|echo|set):)?([a-z_]+))\|?(.*?)\]/i', $template, $matches, PREG_OFFSET_CAPTURE );
	foreach( $matches[0] as $i => $match )
	{
		// Output everything until new tag:
		$r .= substr( $template, $current_pos, $match[1] - $current_pos );
		$current_pos = $match[1] + strlen( $match[0] );

		// New tag to handle:
		$tag = $matches[1][$i][0];

		// Params specified for the tag:
		$tag_param_strings = empty( $matches[3][$i][0] ) ? NULL : $matches[3][$i][0];

		if( substr( $tag, 0, 4 ) == 'set:' )
		{	// Set a param value in the $params[] array used for the whole template (will affect all future template tags)

			$param_name = substr( $tag, 4 );
			$param_val  = substr( $tag_param_strings, strpos( $tag_param_strings, '=' ) + 1 );

			// Set param:
			// we MUST do this here and in & $params[] so that it sticks. This cannot be done in the callback or $this_tag_params[]
			$params[ $param_name ] = $param_val;
		}
		else
		{	// Process a normal template tag:

			$this_tag_params = $params;

			if( ! empty( $tag_param_strings ) )
			{	// Template Tag has specified parameters, use temp to override:
				$tag_param_strings = explode( '|', $tag_param_strings );
				foreach( $tag_param_strings as $tag_param_string )
				{
					$tag_param_name = substr( $tag_param_string, 0, strpos( $tag_param_string, '=' ) );
					// TODO: need to ensure string assigned to $tag_param_val below is single quote and properly escaped?
					$tag_param_val  = substr( $tag_param_string, strpos( $tag_param_string, '=' ) + 1 );
					$this_tag_params[$tag_param_name] = $tag_param_val;
				}
			}
			$r .= render_template_callback( $tag, $this_tag_params, $objects );
		}
	}

	// Print remaining template code:
	$r .= substr( $template, $current_pos );

	return $r;
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
			$rendered_Chapter = ( !isset($objects['Chapter']) ? $Chapter : $objects['Chapter'] );
			if( empty( $rendered_Chapter ) || ! ( $rendered_Chapter instanceof Chapter ) )
			{
				return '<span class="evo_param_error">['.$var.']: Object Chapter is not defined at this moment.</span>';
			}
			break;

		case 'Item':
			global $Item;
			$rendered_Item = ( !isset($objects['Item']) ? $Item : $objects['Item'] );
			if( empty( $rendered_Item ) || ! ( $rendered_Item instanceof Item ) )
			{
				return '<span class="evo_param_error">['.$var.']: Object Item is not defined at this moment.</span>';
			}
			break;

		case 'echo':
			$param_name = substr( $var, 5 );
			if( ! isset( $params[ $param_name ] ) )
			{	// Param is not found:
				return '<span class="evo_param_error">Param <code>'.$param_name.'</code> is not passed.</span>';
			}
			elseif( ! is_scalar( $params[ $param_name ] ) )
			{	// Param is not scalar and cannot be printed on screen:
				return '<span class="evo_param_error">Param <code>'.$param_name.'</code> is not scalar.</span>';
			}
			break;

		default:
			return '<span class="evo_param_error">['.$var.']: Scope "'.$scope.':" is not recognized.</span>';
	}

	$match_found = true;

	ob_start();
	switch( $var )
	{
		// Item:
		case 'Item:title':
			echo $rendered_Item->dget( 'title' );
			break;

		case 'Item:flag_icon':
			echo $rendered_Item->get_flag( $params );
			break;

		case 'Item:permalink':
		case 'Item:permanent_link':
			$rendered_Item->permanent_link( array_merge( array(
					'text'   => '#title',
				), $params ) );
				// Note: Cat content list widget will have set:
				//	'post_navigation' => 'same_category',			// Stay in the same category if Item is cross-posted
				//	'nav_target'      => $params['chapter_ID'],	// for use with 'same_category' : set the category ID as nav target
				//	'target_blog'     => 'auto', 						// Stay in current collection if it is allowed for the Item
			break;

		case 'Item:author':
			$rendered_Item->author( array_merge( array(
					'link_text' => 'auto',		// select login or nice name automatically
				), $params ) );
			break;

		case 'Item:lastedit_user':
			$rendered_Item->lastedit_user( array_merge( array(
					'link_text' => 'auto',		// select login or nice name automatically
				), $params ) );
			break;

		// Date/Time:
		case 'Item:issue_date':
			$rendered_Item->issue_date( $params );
			break;

		case 'Item:issue_time':
			$rendered_Item->issue_time( $params );
			break;

		case 'Item:creation_time':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_creation_time( $temp_params['format'] );
			break;

		case 'Item:mod_date':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_mod_date( $temp_params['format'] );
			break;

		case 'Item:last_touched':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_last_touched_ts( $temp_params['format'] );
			break;

		case 'Item:last_updated':
		case 'Item:contents_last_updated':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_contents_last_updated_ts( $temp_params['format'] );
			break;

		case 'Item:refresh_contents_last_updated_link':
			echo $rendered_Item->get_refresh_contents_last_updated_link( $params );
			break;

		// Links:
		case 'Item:edit_link':
			$rendered_Item->edit_link( $params );
			break;

		case 'Item:history_link':
			echo $rendered_Item->get_history_link( array_merge( array(
					'link_text' => T_('View change history'),
				), $params ) );
			break;

		case 'Item:propose_change_link':
			$rendered_Item->propose_change_link( array_merge( array(
					'text'   => T_('Propose a change'),
				), $params ) );
			break;

		case 'Item:excerpt':
			$rendered_Item->excerpt( array_merge( array(
					'before'              => '',
					'after'               => '',
					'excerpt_before_more' => ' <span class="evo_post__excerpt_more_link">',
					'excerpt_after_more'  => '</span>',
					'excerpt_more_text'   => T_('more').' &raquo;',
				), $params ) );
			break;

		// Read Status:
		case 'Item:read_status':
			$rendered_Item->display_unread_status( array_merge( array(
					'style'  => 'text',
					'before' => '<span class="evo_post_read_status">',
					'after'  => '</span>'
				), $params ) );
			break;

		// Visibility Status:
		case 'Item:visibility_status':
			if( $rendered_Item->status != 'published' )
			{
				$rendered_Item->format_status( array_merge( array(
						'template' => '<div class="evo_status evo_status__$status$ badge" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
					), $params ) );
			}
			break;

		// Categories:
		case 'Item:categories':
			$rendered_Item->categories( array_merge( array(
					'before'          => '',  // For some reason the core has ' ' as default, which is not good for templates
					'after'           => '',  // For some reason the core has ' ' as default, which is not good for templates
				), $params ) );
			break;

		// Tags:
		case 'Item:tags':
			$rendered_Item->tags( array_merge( array(
					'before'          => '',  // For some reason the core has '<div>... ' as default, which is not good for templates
					'after'           => '',  // For some reason the core has '</div>' as default, which is not good for templates
				), $params ) );
			break;

		case 'Item:feedback_link':
			echo $rendered_Item->get_feedback_link();
			break;

		case 'Item:images':
			echo $rendered_Item->get_images( $params );
			break;

		case 'Item:image_url':
			echo $rendered_Item->get_image_url( $params );
			break;

		case 'Item:content_teaser':
			echo $rendered_Item->content_teaser( $params );
			break;

		case 'Item:cat_name':
			if( $item_main_Chapter = & $rendered_Item->get_main_Chapter() )
			{
				echo $item_main_Chapter->dget( 'name' );
			}
			break;

		// Chapter / Category:
		case 'Cat:name':
			echo $rendered_Chapter->dget( 'name' );
			break;

		case 'Cat:permalink':
			echo $rendered_Chapter->get_permanent_link( array_merge( array(
					'text'   => '#name',
				), $params ) );
			break;
			break;

		case 'Cat:description':
			echo $rendered_Chapter->dget( 'description' );
			break;

		case 'Cat:image':
			echo $rendered_Chapter->get_image_tag( array_merge( array(
					'size'       => 'crop-256x256',
				), $params ) );
			break;

		case 'Cat:image_url':
			echo $rendered_Chapter->get_image_url( $params );
			break;

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
		return '<span class="evo_param_error">['.$var.'] is not recognized.</span>';
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

?>