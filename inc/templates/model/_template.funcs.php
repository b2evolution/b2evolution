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
		$template_params = & $params;
		$var = $matches[1][$i][0];
		$temp_params = empty( $matches[3][$i][0] ) ? NULL : $matches[3][$i][0];
		if( substr( $var, 0, 4 ) == 'set:' )
		{	// For "set:" scope we should pass value through temp params:
			$temp_params = 'set_value'.$temp_params;
		}
		if( ! empty( $temp_params ) )
		{	// Template has specified parameters, use it to override:
			$temp_params = explode( '|', $temp_params );
			foreach( $temp_params as $temp_param )
			{
				$prop = substr( $temp_param, 0, strpos( $temp_param, '=' ) );
				// TODO: need to ensure string assigned to $val below is single quote and properly escaped?
				$val  = substr( $temp_param, strpos( $temp_param, '=' ) + 1 );
				$template_params[$prop] = $val;
			}
		}
		$r .= substr( $template, $current_pos, $match[1] - $current_pos );
		$current_pos = $match[1] + strlen( $match[0] );
		$r .= render_template_callback( $var, $template_params, $objects );
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
function render_template_callback( $var, & $params, $objects = array() )
{
	$params = array_merge( array(
		// default date/time format:
		'date_format'         => '#extended_date',
		'time_format'         => '#none',

		// flag icon:
		'before_flag'         => '',
		'after_flag'          => '',

		// permalink_icon:
		// permalink:
		// permanent_link:
		'before_permalink'    => '',
		'after_permalink'     => '',
		'permalink_text'      => '#title#',
		'permalink_class'     => '',
		'post_navigation'     => '',
		'target_blog'         => '',
		'nav_target'          => NULL,

		// author:
		'author_link_text'    => 'auto',
		'before_author'       => '',
		'after_author'        => '',

		// author_avatar:
		'author_avatar_size'   => '',
		'author_avatar_class'  => '',
		'before_author_avatar' => '',
		'after_author_avatar'  => '',

		// issue_time:
		'before_issue_time'    => '',
		'after_issue_time'     => '',
		'issue_time_format'    => '', // empty by default - use default date/time format

		// creation_time:
		'before_creation_time' => '',
		'after_creation_time'  => '',
		'creation_time_format' => '', // empty by default - use default date/time format

		// mod_date:
		'before_mod_date'     => '',
		'after_mod_date'      => '',
		'mod_date_format'     => '', // empty by default - use default date/time format

		// categories:
		'before_categories'           => '',
		'after_categories'            => '',
		'categories_include_main'     => true,
		'categories_include_other'    => true,
		'categories_include_external' => true,
		'categories_link_categories'  => true,

		// lastedit_user:
		'lastedit_user_link_text' => 'auto',
		'before_lastedit_user'    => '',
		'after_lastedit_user'     => '',

		// last_touched:
		'before_last_touched' => '',
		'after_last_touched'  => '',
		'last_touched_format' => '', // empty by default - use default date/time format

		// last_updated:
		'before_last_updated' => '',
		'after_last_updated'  => '',
		'last_updated_format' => '', // empty by default - use default date/time format

		// edit_link:
		'before_edit_link'    => '',
		'after_edit_link'     => '',
		'edit_link_text'      => '#',

		// history_link:
		'before_history_link' => '',
		'after_history_link'  => '',
		'history_link_text'   => T_('View change history'),

		// propose_change_link:
		'before_propose_change_link' => '',
		'after_propose_change_link'  => '',
		'propose_change_link_text'   => T_('Propose a change'),

		// tags:
		'before_tags'    => '',
		'after_tags'     => '',
		'tags_separator' => ', ',

		'excerpt_before_text' => '',
		'excerpt_after_text'  => '',
		'excerpt_before_more' => ' <span class="evo_post__excerpt_more_link">',
		'excerpt_after_more'  => '</span>',
		'excerpt_more_text'   => T_('more').' &raquo;',
	), $params );

	$objects = array_merge( array(
		'Chapter' => NULL, // NULL to use current global $Chapter
		'Item'    => NULL, // NULL to use current global $Item
	), $objects );

	// Get scope and var name:
	preg_match( '#^(([a-z]+):)?(.+)$#i', $var, $match_var );
	$scope = ( empty( $match_var[2] ) ? 'Item': $match_var[2] );
	$var = $scope.':'.$match_var[3];
	switch( $scope )
	{
		case 'Cat':
			global $Chapter;
			$rendered_Chapter = ( $objects['Chapter'] === NULL ? $Chapter : $objects['Chapter'] );
			if( empty( $rendered_Chapter ) || ! ( $rendered_Chapter instanceof Chapter ) )
			{
				return '<span class="evo_param_error">['.$var.']: Object Chapter is not defined at this moment.</span>';
			}
			break;

		case 'Item':
			global $Item;
			$rendered_Item = ( $objects['Item'] === NULL ? $Item : $objects['Item'] );
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

		case 'set':
			$param_name = substr( $var, 4 );
			break;

		default:
			return '<span class="evo_param_error">['.$var.']: Scope "'.$scope.':" is not recognized.</span>';
	}

	$match_found = true;

	// Resolve default date/time formats:
	// TODO: LATER: remove this code from the callback. Templates will specify their own format. Infoline widget can compute $datetime_format internally.
	$params['date_format'] = $date_format = locale_resolve_datetime_fmt( $params['date_format'] );
	$params['time_format'] = $time_format = locale_resolve_datetime_fmt( $params['time_format'] );
	$datetime_format = $date_format.( empty( $time_format ) ? '' : ' ' ).$time_format;

	ob_start();
	switch( $var )
	{
		// Item:
		case 'Item:flag_icon':
			$rendered_Item->flag( array_merge( array(
					'before' => $params['before_flag'],
					'after'  => $params['after_flag'],
				), $params ) );
			break;

		case 'Item:permalink_icon':	// Temporary
			$rendered_Item->permanent_link( array_merge( array(
					'text'   => '#icon',
					'before' => $params['before_permalink'],
					'after'  => $params['after_permalink'],
					'post_navigation' => $params['post_navigation'],
					'nav_target'      => $params['nav_target'],
					'target_blog'     => $params['target_blog'],
				), $params ) );
			break;

		case 'Item:permalink':
		case 'Item:permanent_link':
			$rendered_Item->permanent_link( array_merge( array(
					'text'   => '#title',
					'class'  => $params['permalink_class'],
					'before' => $params['before_permalink'],
					'after'  => $params['after_permalink'],
					'post_navigation' => $params['post_navigation'],
					'nav_target'      => $params['nav_target'],
					'target_blog'     => $params['target_blog'],
				), $params ) );
			break;

		case 'Item:author_avatar':
			$rendered_Item->author( array_merge( array(
					'before'      => $params['before_author_avatar'],
					'after'       => $params['after_author_avatar'],
					'link_text'   => 'only_avatar',
					'link_rel'    => 'nofollow',
					'thumb_size'  => $params['author_avatar_size'],
					'thumb_class' => $params['author_avatar_class'],
				), $params ) );
			break;

		case 'Item:author':
			$rendered_Item->author( array_merge( array(
					'before'    => $params['before_author'],
					'after'     => $params['after_author'],
					'link_text' => $params['author_link_text'],
			), $params ) );
			break;

		case 'Item:lastedit_user':
			$rendered_Item->lastedit_user( array_merge( array(
					'before'    => $params['before_lastedit_user'],
					'after'     => $params['after_lastedit_user'],
					'link_text' => $params['lastedit_user_link_text'],
				), $params ) );
			break;

		// Date/Time:
		case 'Item:issue_date':    // TODO: remove from all templates
		case 'Item:issue_time':
			// We are only using the "time_format" param for this:
			unset( $params['date_format'] );

			$rendered_Item->issue_time( array_merge( array(
					'before'      => $params['before_issue_time'],
					'after'       => $params['after_issue_time'],
					'time_format' => empty( $params['issue_time_format'] ) ? $datetime_format : locale_resolve_datetime_fmt( $params['issue_time_format'] ),
				), $params ) );
			break;

		case 'Item:creation_time':
			$creation_time_format = empty( $params['creation_time_format'] ) ? $datetime_format : locale_resolve_datetime_fmt( $params['creation_time_format'] );
			echo $params['before_creation_time'];
			echo $rendered_Item->get_creation_time( $creation_time_format );
			echo $params['after_creation_time'];
			break;

		case 'Item:mod_date':
			$mod_date_format = empty( $params['mod_date_format'] ) ? $datetime_format : locale_resolve_datetime_fmt( $params['mod_date_format'] );
			echo $params['before_mod_date'];
			echo $rendered_Item->get_mod_date( $mod_date_format );
			echo $params['after_mod_date'];
			break;

		case 'Item:last_touched':
			$last_touched_ts_format = empty( $params['last_touched_format'] ) ? $datetime_format : locale_resolve_datetime_fmt( $params['last_touched_format'] );
			echo $params['before_last_touched'];
			echo $rendered_Item->get_last_touched_ts( $last_touched_ts_format );
			echo $params['after_last_touched'];
			break;

		case 'Item:last_updated':
		case 'Item:contents_last_updated':
			$contents_last_updated_ts_format = empty( $params['last_updated_format'] ) ? $datetime_format : locale_resolve_datetime_fmt( $params['last_updated_format'] );
			echo $params['before_last_updated'];
			echo $rendered_Item->get_contents_last_updated_ts( $contents_last_updated_ts_format ).$rendered_Item->get_refresh_contents_last_updated_link();
			echo $params['after_last_updated'];
			break;

		// Links:
		case 'Item:edit_link':
			$rendered_Item->edit_link( array_merge( array(
					'before' => $params['before_edit_link'],
					'after'  => $params['after_edit_link'],
					'text'   => $params['edit_link_text'],
				), $params ) );
			break;

		case 'Item:history_link':
			echo $rendered_Item->get_history_link( array_merge( array(
					'before'    => $params['before_history_link'],
					'after'     => $params['after_history_link'],
					'link_text' => $params['history_link_text'],
				), $params ) );
			break;

		case 'Item:propose_change_link':
			$rendered_Item->propose_change_link( array_merge( array(
					'before' => $params['before_propose_change_link'],
					'after'  => $params['after_propose_change_link'],
					'text'   => $params['propose_change_link_text'],
				), $params ) );
			break;

		case 'Item:excerpt':
			$rendered_Item->excerpt( array_merge( array(
					'before'              => $params['excerpt_before_text'],
					'after'               => $params['excerpt_after_text'],
					'excerpt_before_more' => $params['excerpt_before_more'],
					'excerpt_after_more'  => $params['excerpt_after_more'],
					'excerpt_more_text'   => $params['excerpt_more_text'],
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
					'before'           => $params['before_categories'],
					'after'            => $params['after_categories'],
					'include_main'     => $params['categories_include_main'],
					'include_other'    => $params['categories_include_other'],
					'include_external' => $params['categories_include_external'],
					'link_categories'  => $params['categories_link_categories'],
			), $params ) );
			break;

		// Tags:
		case 'Item:tags':
			$rendered_Item->tags( array_merge( array(
					'before'    => $params['before_tags'],
					'after'     => $params['after_tags'],
					'separator' => $params['tags_separator'],
				), $params ) );
			break;

		case 'Item:feedback_link':
			echo $rendered_Item->get_feedback_link();
			break;

		case 'Item:images':
			echo $rendered_Item->get_images( $params );
			break;

		case 'Item:content_teaser':
			echo $rendered_Item->content_teaser( $params );
			break;

		// Chapter / Category:
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

		default:
			switch( $scope )
			{
				case 'echo':
					// Print param var value, No need check this because all done above:
					echo $params[ $param_name ];
					break;

				case 'set':
					// Set param:
					$params[ $param_name ] = ( isset( $params['set_value'] ) ? $params['set_value'] : NULL );
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