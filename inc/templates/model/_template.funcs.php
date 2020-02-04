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
 * @return string|boolean Rendered template or FALSE on wrong request
 */
function render_template( $code, $params = array() )
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
	{	// Template available, replace variables using supplied callback:
		preg_match_all( '/\$[a-z_:]+\$/i', $Template->template_code, $matches, PREG_OFFSET_CAPTURE );
		$current_pos = 0;
		$r = '';
		foreach( $matches[0] as $match )
		{
			$r .= substr( $Template->template_code, $current_pos, $match[1] - $current_pos );
			$current_pos = $match[1] + strlen( $match[0] );
			$r .= call_user_func( 'render_template_callback', $match[0], $params );
		}

		// Print remaining template code:
		$r .= substr( $Template->template_code, $current_pos );

		return $r;
	}
	else
	{
		return false;
	}
}


/**
 * Callback function to replace variables in template
 * 
 * @param string Variable to be replaced
 * @param array Additional parameters
 * @return string Replacement string
 */
function render_template_callback( $var, $params )
{
	global $Chapter, $Item;

	$params = array_merge( array(
		'before_flag'         => '',
		'after_flag'          => '',
		'before_permalink'    => '',
		'after_permalink'     => '',
		'permalink_text'      => '#icon#',
		'permalink_class'     => '',
		'before_author'       => '',
		'after_author'        => '',
		'before_post_time'    => '',
		'after_post_time'     => '',
		'before_categories'   => '',
		'after_categories'    => '',
		'before_last_touched' => '',
		'after_last_touched'  => '',
		'before_last_updated' => '',
		'after_last_updated'  => '',
		'before_edit_link'    => ' &bull; ',
		'after_edit_link'     => '',
		'edit_link_text'      => '#',
		'format'              => '',
		'date_format'         => 'extended',
		'time_format'         => 'none',
		'excerpt_before_text' => '',
		'excerpt_after_text'  => '',
		'excerpt_before_more' => ' <span class="evo_post__excerpt_more_link">',
		'excerpt_after_more'  => '</span>',
		'excerpt_more_text'   => T_('more').' &raquo;',
	), $params );

	$r = $var;
	$match_found = true;

	// Get datetime format:
	switch( $params['date_format'] )
	{
		case 'extended':
			$date_format = locale_extdatefmt();
			break;

		case 'long':
			$date_format = locale_longdatefmt();
			break;

		case 'short':
			$date_format = locale_datefmt();
			break;

		default:
			$time_format = '';
	}

	switch( $params['time_format'] )
	{
		case 'long':
			$time_format = locale_timefmt();
			break;

		case 'short':
			$time_format = locale_shorttimefmt();
			break;

		case 'none':
		default:
			$time_format = '';
	}

	ob_start();
	switch( $r )
	{
		// Item:
		case '$flag_icon$':
			$Item->flag( array(
					'before' => $params['before_flag'],
					'after'  => $params['after_flag'],
				) );
			break;

		case '$permalink_icon$':
			$Item->permanent_link( array(
					'text'   => $params['permalink_text'],
					'before' => $params['before_permalink'],
					'after'  => $params['after_permalink'],
				) );
			break;

		case '$permalink$':
			$Item->permanent_link( array(
					'text'   => $params['permalink_text'],
					'class'  => $params['permalink_class'],
					'before' => $params['before_permalink'],
					'after'  => $params['after_permalink'],
				) );
			break;

		case '$author$':
			$Item->author( array(
					'before'    => $params['before_author'],
					'after'     => $params['after_author'],
					'link_text' => $params['author_link_text'],
				) );
			break;

		case '$issue_date$':
			$Item->issue_time( array(
					'before'      => $params['before_post_time'],
					'after'       => $params['after_post_time'],
					'time_format' => $date_format.( empty( $time_format ) ? '' : ' ' ).$time_format
				) );
			break;

		case '$creation_date$':
			echo $params['before_post_time'];
			echo mysql2date( $date_format.( empty( $time_format ) ? '' : ' ' ).$time_format, $Item->datecreated );
			echo $params['after_post_time'];
			break;

		case '$categories$':
			$Item->categories( array(
					'before'          => $params['before_categories'],
					'after'           => $params['after_categories'],
					'include_main'    => true,
					'include_other'   => true,
					'include_external'=> true,
					'link_categories' => true,
				) );
			break;

		case '$last_touched$':
			echo $params['before_last_touched'];
			echo mysql2date( $date_format.( empty( $date_format ) ? '' : ' ' ).$time_format, $Item->get( 'last_touched_ts' ) );
			echo $params['after_last_touched'];
			break;

		case '$last_updated$':
			echo $params['before_last_updated'];
			echo mysql2date( $date_format.( empty( $date_format ) ? '' : ' ' ).$time_format, $Item->get( 'contents_last_updated_ts' ) ).$Item->get_refresh_contents_last_updated_link();
			echo $params['after_last_updated'];
			break;

		case '$edit_link$':
			$Item->edit_link( array(
				'before' => $params['before_edit_link'],
				'after'  => $params['after_edit_link'],
				'text'   => $params['edit_link_text'],
			) );
			break;

		case '$excerpt$':
			$Item->excerpt( array(
				'before'              => $params['excerpt_before_text'],
				'after'               => $params['excerpt_after_text'],
				'excerpt_before_more' => $params['excerpt_before_more'],
				'excerpt_after_more'  => $params['excerpt_after_more'],
				'excerpt_more_text'   => $params['excerpt_more_text'],
				) );
			break;

		case '$read_status$':
			$Item->get_unread_status( array(
					'style'  => 'text',
					'before' => '<span class="evo_post_read_status">',
					'after'  => '</span>'
				) );
			break;

		case '$visibility_status$':
			if( $Item->status != 'published' )
			{
				$Item->format_status( array(
						'template' => '<div class="evo_status evo_status__$status$ badge" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
					) );
			}
			break;

		// Chapter / Category:
		case '$Cat:permalink$':
			echo '<a href="'.$Chapter->get_permanent_url().'" class="link">'.get_icon( 'expand' ).$Chapter->dget( 'name' ).'</a>';
			break;

		case '$Cat:description$':
			echo $Chapter->dget( 'description' );
			break;

		default:
			$match_found = false;
	}
	$r = ob_get_clean();

	if( $match_found )
	{
		return $r;
	}
	else
	{
		return $var;
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
function unique_template_code( $code, $ID = 0, $db_code_fieldname = 'tpl_code', $db_ID_fieldname = 'tpl_ID',	$db_table = 'T_templates' )
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
