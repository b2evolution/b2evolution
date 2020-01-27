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
 * @param array Callback function. Function should return the replacement string for the variable found in the template.
 * @return boolean True if template is found and rendered, false otherwise
 */
function render_template( $code, $replace_callback, $params = array() )
{
	global $current_locale;

	$TemplateCache = & get_TemplateCache();
	$Template = & $TemplateCache->get_by_code( $code );

	// Check if the template has a child matching the current locale:
	$localized_templates = $Template->get_localized_templates( $current_locale );
	if( ! empty( $localized_templates ) )
	{	// Use localized template:
		$Template = & $localized_templates[0];
	}

	if( $Template )
	{	// Template available, replace variables using supplied callback:
		preg_match_all( '/\$[a-z_]+\$/i', $Template->template_code, $matches, PREG_OFFSET_CAPTURE );
		$current_pos = 0;
		foreach( $matches[0] as $match )
		{
			// $match[0] = $variable$, $match[1] = offset
			echo substr( $Template->template_code, $current_pos, $match[1] - $current_pos );
			$current_pos = $match[1] + strlen( $match[0] );
			echo call_user_func( $replace_callback, $match[0], $params );
		}

		// Print remaining template code:
		echo substr( $Template->template_code, $current_pos );

		return true;
	}
	else
	{
		return false;
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
