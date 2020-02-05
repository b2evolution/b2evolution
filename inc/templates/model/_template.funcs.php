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
function render_template_code( $code, $params = array() )
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
	{	// Template available, replace variables:
		return render_template( $Template->template_code, $params );
	}
	else
	{
		return false;
	}
}


/**
 * Render template content
 * 
 * @param string Template
 * @return string Rendered template
 */
function render_template( $template, $params = array() )
{
	$current_pos = 0;
	$r = '';

	preg_match_all( '/\$[a-z_:]+\$/i', $template, $matches, PREG_OFFSET_CAPTURE );
	foreach( $matches[0] as $match )
	{
		$r .= substr( $template, $current_pos, $match[1] - $current_pos );
		$current_pos = $match[1] + strlen( $match[0] );
		$r .= call_user_func( 'render_template_callback', $match[0], $params );
	}

	// Print remaining template code:
	$r .= substr( $template, $current_pos );

	return $r;
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
		// default date/time format:
		'date_format'         => '#extended_date',
		'time_format'         => '#none',

		// flag icon:
		'before_flag'         => '',
		'after_flag'          => '',

		// permalink_icon:
		'before_permalink'    => '',
		'after_permalink'     => '',
		'permalink_text'      => '#icon#',
		'permalink_class'     => '',

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

	$r = $var;
	$match_found = true;

	// Resolve default date/time formats:
	$date_format = locale_resolve_datetime_fmt( $params['date_format'] );
	$time_format = locale_resolve_datetime_fmt( $params['time_format'] );
	$datetime_format = $date_format.( empty( $time_format ) ? '' : ' ' ).$time_format;

// TODO: a variable is like `$Cat:description$` or `$Item:excerpt$`
// If we have just $excerpt$, it is the equivalent of `$Item:excerpt$`
// So step 1 is to isolate the Prefix `Item` or `Cat` and check if $Item or $Chapter is defined
// If NOT, then return error

 
	// Trim '$' from variable:
	$r = trim( $r , '$' );

	ob_start();
	switch( $r )
	{
		// Item:
		case 'flag_icon':
// TODO: should be  case 'Item:flag_icon':
			$Item->flag( array(
					'before' => $params['before_flag'],
					'after'  => $params['after_flag'],
				) );
			break;

		case 'permalink_icon':	// Temporary
// TODO: should be  case 'Item:permalink_icon':
			$Item->permanent_link( array(
					'text'   => '#icon#',
					'before' => $params['before_permalink'],
					'after'  => $params['after_permalink'],
				) );
			break;

		case 'permalink':
		case 'permanent_link':
			$Item->permanent_link( array(
					'text'   => $params['permalink_text'],
					'class'  => $params['permalink_class'],
					'before' => $params['before_permalink'],
					'after'  => $params['after_permalink'],
				) );
			break;

		case 'author_avatar':
			$Item->author( array(
					'before'      => $params['before_author_avatar'],
					'after'       => $params['after_author_avatar'],
					'link_text'   => 'only_avatar',
					'link_rel'    => 'nofollow',
					'thumb_size'  => $params['author_avatar_size'],
					'thumb_class' => $params['author_avatar_class'],
				) );
			break;

		case 'author':
			$Item->author( array(
					'before'    => $params['before_author'],
					'after'     => $params['after_author'],
					'link_text' => $params['author_link_text'],
				) );
			break;

		case 'lastedit_user':
			$Item->lastedit_user( array(
					'before'    => $params['before_lastedit_user'],
					'after'     => $params['after_lastedit_user'],
					'link_text' => $params['lastedit_user_link_text'],
				) );
			break;

		// Date/Time:
		case 'issue_time':
			$Item->issue_time( array(
					'before'      => $params['before_issue_time'],
					'after'       => $params['after_issue_time'],
					'time_format' => empty( $params['issue_time_format'] ) ? $datetime_format : locale_resolve_datetime_fmt( $params['issue_time_format'] ),
				) );
			break;

		case 'creation_time':
			$creation_time_format = empty( $params['creation_time_format'] ) ? $datetime_format : locale_resolve_datetime_fmt( $params['creation_time_format'] );
			echo $params['before_creation_time'];
			echo $Item->get_creation_time( $creation_time_format );
			echo $params['after_creation_time'];
			break;

		case 'mod_date':
			$mod_date_format = empty( $params['mod_date_format'] ) ? $datetime_format : locale_resolve_datetime_fmt( $params['mod_date_format'] );
			echo $params['before_mod_date'];
			echo $Item->get_mod_date( $mod_date_format );
			echo $params['after_mod_date'];
			break;

		case 'last_touched':
			$last_touched_ts_format = empty( $params['last_touched_format'] ) ? $datetime_format : locale_resolve_datetime_fmt( $params['last_touched_format'] );
			echo $params['before_last_touched'];
			echo $Item->get_last_touched_ts( $last_touched_ts_format );
			echo $params['after_last_touched'];
			break;

		case 'last_updated':
		case 'contents_last_updated':
			$contents_last_updated_ts_format = empty( $params['last_updated_format'] ) ? $datetime_format : locale_resolve_datetime_fmt( $params['last_updated_format'] );
			echo $params['before_last_updated'];
			echo $Item->get_contents_last_updated_ts( $contents_last_updated_ts_format ).$Item->get_refresh_contents_last_updated_link();
			echo $params['after_last_updated'];
			break;

		// Links:
		case 'edit_link':
			$Item->edit_link( array(
					'before' => $params['before_edit_link'],
					'after'  => $params['after_edit_link'],
					'text'   => $params['edit_link_text'],
				) );
			break;

		case 'history_link':
			echo $Item->get_history_link( array(
					'before'    => $params['before_history_link'],
					'after'     => $params['after_history_link'],
					'link_text' => $params['history_link_text'],
				) );
			break;

		case 'propose_change_link':
			$Item->propose_change_link( array(
					'before' => $params['before_propose_change_link'],
					'after'  => $params['after_propose_change_link'],
					'text'   => $params['propose_change_link_text'],
				) );
			break;

		case 'excerpt':
			$Item->excerpt( array(
					'before'              => $params['excerpt_before_text'],
					'after'               => $params['excerpt_after_text'],
					'excerpt_before_more' => $params['excerpt_before_more'],
					'excerpt_after_more'  => $params['excerpt_after_more'],
					'excerpt_more_text'   => $params['excerpt_more_text'],
				) );
			break;

		// Read Status:
		case 'read_status':
			echo $Item->get_unread_status( array(
					'style'  => 'text',
					'before' => '<span class="evo_post_read_status">',
					'after'  => '</span>'
				) );
			break;

		// Visibility Status:
		case 'visibility_status':
			if( $Item->status != 'published' )
			{
				$Item->format_status( array(
						'template' => '<div class="evo_status evo_status__$status$ badge" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
					) );
			}
			break;

		// Chapter / Category:
		case 'Cat:permalink':
			echo '<a href="'.$Chapter->get_permanent_url().'" class="link">'.get_icon( 'expand' ).$Chapter->dget( 'name' ).'</a>';
			break;

		case 'Cat:description':
			echo $Chapter->dget( 'description' );
			break;

		// Categories:
		case 'categories':
			$Item->categories( array(
					'before'           => $params['before_categories'],
					'after'            => $params['after_categories'],
					'include_main'     => $params['categories_include_main'],
					'include_other'    => $params['categories_include_other'],
					'include_external' => $params['categories_include_external'],
					'link_categories'  => $params['categories_link_categories'],
				) );
			break;

		// Tags:
		case 'tags':
			$Item->tags( array(
					'before'    => $params['before_tags'],
					'after'     => $params['after_tags'],
					'separator' => $params['tags_separator'],
				) );
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
