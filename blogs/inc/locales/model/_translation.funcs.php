<?php
/**
 * This file implements functions for handling translation.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _translation.funcs.php 985 2012-03-05 21:59:17Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Update a table T_i18n_original_string from the file messages.pot
 *
 * @param boolean TRUE - if process is OK
 */
function translation_update_table_pot()
{
	global $DB, $locales_path;

	// Reset all previous strings
	$DB->query( 'UPDATE T_i18n_original_string SET iost_inpotfile = 0' );

	$status = '-';

	$lines = file( $locales_path.'messages.pot' );
	$lines[] = '';	// Adds a blank line at the end in order to ensure complete handling of the file

	foreach( $lines as $line )
	{
		if( trim( $line ) == '' )
		{	// Blank line, go back to base status:
			if( $status == 't' && !empty( $msgid ) )
			{	// ** End of an original text ** :
				translation_update_table_pot_row( trim( $msgid ) );
			}
			$msgid = '';
			$msgstr = '';
			$status = '-';
		}
		elseif( ( $status == '-' ) && preg_match( '#^msgid "(.*)"#', $line, $matches ) )
		{	// Encountered an original text
			$status = 'o';
			$msgid = $matches[1];
		}
		elseif( ( $status == 'o' ) && preg_match( '#^msgstr "(.*)"#', $line, $matches ) )
		{	// Encountered a translated text
			$status = 't';
			$msgstr = $matches[1];
		}
		elseif( preg_match( '#^"(.*)"#', $line, $matches ) )
		{	// Encountered a followup line
			if( $status == 'o' )
				$msgid .= $matches[1];
			elseif( $status == 't' )
				$msgstr .= $matches[1];
		}
	}

	return true;
}


/**
 * Update/Insert a string from .POT file into the table T_i18n_original_string
 *
 * @param string Original string
 */
function translation_update_table_pot_row( $string )
{
	global $DB;

	// Get original string ID
	$SQL = new SQL();
	$SQL->SELECT( 'iost_ID' );
	$SQL->FROM( 'T_i18n_original_string' );
	$SQL->WHERE( 'iost_string = '.$DB->quote( $string ) );
	$original_string_ID = $DB->get_var( $SQL->get() );

	if( $original_string_ID )
	{	// Update already existing string
		$DB->query( 'UPDATE T_i18n_original_string SET iost_inpotfile = 1 WHERE iost_ID = '.$DB->quote( $original_string_ID ) );
	}
	else
	{	// Insert new string
		$DB->query( 'INSERT INTO T_i18n_original_string ( iost_string, iost_inpotfile ) VALUES ( '.$DB->quote( $string ).', 1 )' );
	}
}


/**
 * Update a table T_i18n_translated_string from the file messages.pot
 *
 * @param string Locale
 * @param boolean TRUE - if process is OK
 */
function translation_update_table_po( $locale )
{
	global $DB, $locales_path, $locales;

	$po_file_name = $locales_path.$locales[$locale]['messages'].'/LC_MESSAGES/messages.po';

	// Reset all previous strings
	$DB->query( 'UPDATE T_i18n_translated_string SET itst_inpofile = 0 WHERE itst_locale = '.$DB->quote( $locale ) );

	$status = '-';

	if( !file_exists( $po_file_name ) )
	{	// No locale file, Exit here
		global $Messages;
		$Messages->add( T_('No found .PO file'), 'error' );
		return false;
	}

	$lines = file( $po_file_name );
	$lines[] = '';	// Adds a blank line at the end in order to ensure complete handling of the file

	foreach( $lines as $line )
	{
		if( trim( $line ) == '' )
		{	// Blank line, go back to base status:
			if( $status == 't' && !empty( $msgstr ) )
			{	// ** End of an original text ** :
				translation_update_table_po_row( $locale, trim( $msgid ), trim( $msgstr ) );
			}
			$msgid = '';
			$msgstr = '';
			$status = '-';
		}
		elseif( ( $status == '-' ) && preg_match( '#^msgid "(.*)"#', $line, $matches ) )
		{	// Encountered an original text
			$status = 'o';
			$msgid = $matches[1];
		}
		elseif( ( $status == 'o' ) && preg_match( '#^msgstr "(.*)"#', $line, $matches ) )
		{	// Encountered a translated text
			$status = 't';
			$msgstr = $matches[1];
		}
		elseif( preg_match( '#^"(.*)"#', $line, $matches ) )
		{	// Encountered a followup line
			if( $status == 'o' )
				$msgid .= $matches[1];
			elseif( $status == 't' )
				$msgstr .= $matches[1];
		}
	}

	return true;
}


/**
 * Update/Insert a string from .PO file into the table T_i18n_translated_string
 *
 * @param string Locale
 * @param string Original string
 * @param string Translated string
 */
function translation_update_table_po_row( $locale, $original_string, $translated_string )
{
	global $DB;

	// Get original string ID
	$SQL = new SQL();
	$SQL->SELECT( 'iost_ID' );
	$SQL->FROM( 'T_i18n_original_string' );
	$SQL->WHERE( 'iost_string = '.$DB->quote( $original_string ) );
	$original_string_ID = $DB->get_var( $SQL->get() );

	if( !$original_string_ID )
	{	// No original string, Exit here
		return;
	}

	// Get translated string
	$SQL = new SQL();
	$SQL->SELECT( 'itst_ID' );
	$SQL->FROM( 'T_i18n_translated_string' );
	$SQL->WHERE( 'itst_standard = '.$DB->quote( $translated_string ) );
	$SQL->WHERE_and( 'itst_iost_ID = '.$DB->quote( $original_string_ID ) );
	$translated_string_ID = $DB->get_var( $SQL->get() );

	if( $translated_string_ID )
	{	// Update already existing string
		$DB->query( 'UPDATE T_i18n_translated_string SET itst_inpofile = 1 WHERE itst_ID = '.$DB->quote( $translated_string_ID ) );
	}
	else
	{	// Insert new string
		$DB->query( 'INSERT INTO T_i18n_translated_string ( itst_iost_ID, itst_locale, itst_standard, itst_inpofile ) VALUES ( '.$DB->quote( $original_string_ID ).', '.$DB->quote( $locale ).', '.$DB->quote( $translated_string ).', 1 )' );
	}
}


/**
 * Generate .PO file
 *
 * @param string Locale
 */
function translation_generate_po_file( $locale )
{
	global $DB, $locales_path, $locales;

	$po_folder_name = $locales_path.$locales[$locale]['messages'].'/LC_MESSAGES/';
	$po_file_name = $po_folder_name.'messages.po';

	if( !file_exists( $po_file_name ) )
	{
		if( !file_exists( $locales_path.$locales[$locale]['messages'] ) )
		{
			evo_mkdir( $locales_path.$locales[$locale]['messages'] );
		}
		if( !file_exists( $locales_path.$locales[$locale]['messages'].'/LC_MESSAGES' ) )
		{
			evo_mkdir( $locales_path.$locales[$locale]['messages'].'/LC_MESSAGES' );
		}
	}

	$locale_name = explode( ' ', $locales[$locale]['name'] );

	$po_content = array();
	$po_content[] = '# b2evolution - '.$locale_name[0].' language file';
	$po_content[] = '# Copyright (C) '.date( 'Y' ).' Francois PLANQUE';
	$po_content[] = '# This file is distributed under the same license as the b2evolution package.';
	$po_content[] = '';

	// Get the translated strings from DB
	$SQL = new SQL();
	$SQL->SELECT( 'iost_string, itst_standard' );
	$SQL->FROM( 'T_i18n_original_string' );
	$SQL->FROM_add( 'RIGHT OUTER JOIN T_i18n_translated_string ON iost_ID = itst_iost_ID' );
	$SQL->WHERE( 'itst_locale = '.$DB->quote( $locale ) );
	$SQL->ORDER_BY( 'iost_string' );
	$translated_strings = $DB->get_results( $SQL->get() );

	foreach( $translated_strings as $string )
	{
		$po_content[] = 'msgid "'.$string->iost_string.'"';
		$po_content[] = 'msgstr "'.$string->itst_standard.'"';
		$po_content[] = '';
	}

	// Write to .PO file
	$ok = save_to_file( implode("\r\n", $po_content), $po_file_name, 'w+' );

	return (bool) $ok;
}


/**
 * Generate .POT file
 */
function translation_generate_pot_file()
{
	global $DB, $locales_path;

	$pot_file_name = $locales_path.'messages.pot';

	$pot_content = array();
	$pot_content[] = '# b2evolution - Language file';
	$pot_content[] = '# Copyright (C) '.date( 'Y' ).' Francois PLANQUE';
	$pot_content[] = '# This file is distributed under the same license as the b2evolution package.';
	$pot_content[] = '';

	global $basepath;
	$translation_strings = array();
	translation_scandir( $basepath, $translation_strings );

	foreach( $translation_strings as $string => $files )
	{ // Format the translation strings to write in .POT file
		foreach( $files as $file )
		{
			$pot_content[] = '#: '.$file;
		}
		$pot_content[] = 'msgid "'.$string.'"';
		$pot_content[] = 'msgstr ""';
		$pot_content[] = '';
	}

	// Write to .POT file
	$ok = save_to_file( implode("\r\n", $pot_content), $pot_file_name, 'w+' );

	return (bool) $ok;
}


/**
 * Scan dir to find the translation strings
 *
 * @param string Path
 * @param array Translation strings (by reference)
 */
function translation_scandir( $path, & $translation_strings )
{
	$files = scandir( $path );
	foreach( $files as $file )
	{
		if( is_file( $path.$file ) && preg_match( '/\.php$/i', $path.$file ) )
		{	// PHP file; Find all translation strings in current file
			translation_find_T_strings( $path.$file, $translation_strings );
		}
		elseif( $file != '.' && $file != '..' && is_dir( $path.$file ) )
		{	// Directory; Scan each directory recursively to find all PHP files
			translation_scandir( $path.$file.'/', $translation_strings );
		}
	}
}


/**
 * Find the translation strings in the file
 *
 * @param string File path
 * @param array Translation strings (by reference)
 */
function translation_find_T_strings( $file, & $translation_strings )
{
	if( preg_match_all( '/(NT|T|TS)_\( ?\'([^\)]+)\' ?\)/i', file_get_contents( $file ), $t_matches ) )
	{	// The matches is found
		global $basepath;

		foreach( $t_matches[2] as $t_m )
		{
			$t_m = str_replace( array( "\r\n", '\n' ), array( '\n', '\n"'."\r\n".'"' ), $t_m );

			if( !isset( $translation_strings[ $t_m ] ) )
			{	// Set array for each string in order to store the file paths where this string is found
				$translation_strings[ $t_m ] = array();
			}
			$translation_strings[ $t_m ][] = str_replace( $basepath, '/', $file );
		}
	}
}

?>