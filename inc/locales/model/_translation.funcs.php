<?php
/**
 * This file implements functions for handling translation.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
		$Messages->add( T_('No .PO file found'), 'error' );
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
	$ok = (bool) save_to_file( implode("\r\n", $po_content), $po_file_name, 'w+' );

	if( ! $ok )
	{ // Inform user about no permission to write PO file
		global $Messages;
		$Messages->add( sprintf( T_('The file %s cannot be written to disk. Please check the filesystem permissions.'), '<b>'.$po_file_name.'</b>' ), 'error' );
	}

	return $ok;
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
		if( isset( $files['trans'] ) )
		{ // Text of TRANS info
			if( is_array( $files['trans'] ) )
			{ // Multiline TRANS info
				foreach( $files['trans'] as $ft => $files_trans )
				{
					$pot_content[] = '#. '.( $ft == 0 ? 'TRANS: ' : '' ).$files_trans;
				}
			}
			else
			{ // Single TRANS info
				$pot_content[] = '#. TRANS: '.$files['trans'];
			}
			unset( $files['trans'] );
		}
		foreach( $files as $file )
		{ // File name and line number where string exists
			$pot_content[] = '#: '.$file[1].':'.$file[0];
		}
		if( strpos( $string, '%' ) !== false )
		{ // Char '%' is detected in the string
			if( preg_match( '/%(s|\d*d)/', $string ) )
			{ // The string contains a mask like %s or %d
				$pot_content[] = '#, php-format';
			}
			else
			{ // The string contains a simple char '%'
				$pot_content[] = '#, no-php-format';
			}
		}
		$pot_content[] = 'msgid "'.$string.'"';
		$pot_content[] = 'msgstr ""';
		$pot_content[] = '';
	}

	// Write to .POT file
	$ok = (bool) save_to_file( implode( "\n", $pot_content ), $pot_file_name, 'w+' );

	if( ! $ok )
	{ // Inform user about no permission to write POT file
		global $Messages;
		$Messages->add( sprintf( T_('The file %s cannot be written to disk. Please check the filesystem permissions.'), '<b>'.$pot_file_name.'</b>' ), 'error' );
	}

	return $ok;
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
 * Get substring from the selected position with shift
 *
 * @param string Source string
 * @param integer Current index
 * @param integer Shift
 * @return string
 */
function translation_get_chars( $line_string, $index, $shift )
{
	$char = '';

	if( $shift > 0 )
	{ // Get next chars
		if( strlen( $line_string ) > $index + $shift )
		{
			$char = substr( $line_string, $index + 1, $shift );
		}
	}
	elseif( $shift < 0 )
	{ // Get previous chars
		if( $index + $shift >= 0 )
		{
			$char = substr( $line_string, $index + $shift, 0 - $shift );
		}
	}
	else
	{ // Current char
		$char = $line_string[ $index ];
	}

	return $char;
}

/**
 * Find the translation strings in the file
 *
 * @param string File path
 * @param array Translation strings (by reference)
 */
function translation_find_T_strings( $file, & $translation_strings )
{
	global $basepath;

	$line_is_multiple = false;

	// Split file content with lines in order to know line number of each string
	$file_lines = explode( "\n", file_get_contents( $file ) );

	$T_line_number = 0;
	$T_string_is_opened = false;
	$T_string_text = '';
	$T_string_quote_sign = "'";
	$TRANS_is_opened = false;
	$TRANS_text = '';

	$prev_line_number = -1;
	foreach( $file_lines as $line_number => $line_string )
	{
		if( ! $T_string_is_opened && strpos( $line_string, 'T_' ) === false && strpos( $line_string, 'TS_' ) === false  &&
		    ! $TRANS_is_opened && strpos( $line_string, 'TRANS:' ) === false )
		{ // This line doesn't contain any T_ string AND TRANS info, Skip it
			continue;
		}

		for( $l = 0; $l <= strlen( $line_string ); $l++ )
		{
			$char = $line_string[ $l ];

			/************ T_ strings ************/
			$char_prev3 = translation_get_chars( $line_string, $l, -3 );
			$char_prev2 = translation_get_chars( $line_string, $l, -2 );

			if( ! $T_string_is_opened &&
			    ( $char_prev2 == 'T_' || $char_prev3 == 'TS_' || $char_prev3 == 'NT_' ) )
			{ // T_ string is detected
				$char_next1 = translation_get_chars( $line_string, $l, 1 );
				$char_next2 = translation_get_chars( $line_string, $l, 2 );

				if( $char == '(' &&
				    ( $char_next1 == '"' || $char_next1 == "'" ||
				      $char_next2 == ' "' || $char_next2 == " '" ) )
				{ // OPEN T_ string
					$T_string_quote_sign = ( $char_next1 == '"' || $char_next2 == ' "' ) ? '"' : "'";
					$l += ( $char_next1 == '"' || $char_next1 == "'" ) ? 1 : 2;
					$T_line_number = $line_number + 1;
					$T_string_is_opened = true;
					$TRANS_is_opened = false;
					continue;
				}
			}

			$char_prev1 = translation_get_chars( $line_string, $l, -1 );
			if( $T_string_is_opened && $char_prev1 != '\\' && $char == $T_string_quote_sign )
			{ // CLOSE T_ string

				// Save T_ string to array
				$T_string_text = str_replace( array( '"', "\'", "\r\n", "\n", '\n', "\t" ), array( '\"', "'", '\n', '\n', '\n"'."\n".'"', '\t' ), $T_string_text );
				if( strpos( $T_string_text, "\n" ) !== false )
				{ // Add empty new line before multiline string
					$T_string_text = '"'."\n".'"'.$T_string_text;
				}
				if( !isset( $translation_strings[ $T_string_text ] ) )
				{ // Set array for each string in order to store the file paths where this string is found
					$translation_strings[ $T_string_text ] = array();
				}
				if( ! empty( $TRANS_text ) && ! isset( $translation_strings[ $T_string_text ]['trans'] ) )
				{ // Text of TRANS info
					if( strpos( $TRANS_text, "\n" ) !== false )
					{
						$TRANS_text = explode( "\n", $TRANS_text );
						foreach( $TRANS_text as $t_i => $TRANS_text_value )
						{
							$TRANS_text[ $t_i ] = trim( $TRANS_text_value, ' 	' );
						}
					}
					$translation_strings[ $T_string_text ]['trans'] = $TRANS_text;
				}
				$translation_strings[ $T_string_text ][] = array(
						$T_line_number, // Line number
						str_replace( $basepath, '../../../', $file ), // String
					);

				// Reset vars for next T_ string
				$T_line_number = 0;
				$T_string_text = '';
				$T_string_is_opened = false;
				$TRANS_text = '';
			}

			if( $T_string_is_opened )
			{ // Store chars of the current opened T_ string
				if( $prev_line_number != $line_number )
				{ // String is multiline
					if( $T_string_text != '' )
					{
						$T_string_text .= "\n";
					}
					$prev_line_number = $line_number;
				}
				$T_string_text .= $line_string[ $l ];
			}

			/************ TRANS info ************/
			if( $T_string_is_opened )
			{ // T_ is opened skip this
				continue;
			}

			// Try to find TRANS
			$char_prev5 = translation_get_chars( $line_string, $l, -5 );
			if( $char == ':' && $char_prev5 == 'TRANS' && ( strpos( $line_string, '//' ) !== false || strpos( $line_string, '/*' ) !== false ) )
			{ // TRANS info is opened
				$TRANS_is_opened = true;
				$prev_line_number = $line_number;
				$l++;
				continue;
			}

			$char_next2 = translation_get_chars( $line_string, $l, 2 );
			if( $TRANS_is_opened &&
			    ( $char_next2 == '*/' ||
			      ( $prev_line_number != $line_number &&
			        ( strpos( $line_string, 'T_' ) !== false || strpos( $line_string, 'TS_' ) !== false ) ) ) )
			{ // CLOSE TRANS info
				$TRANS_is_opened = false;
			}

			if( $TRANS_is_opened )
			{ // Store chars of the current opened TRANS info
				if( $prev_line_number != $line_number )
				{
					$TRANS_text .= "\n";
					$prev_line_number = $line_number;
				}
				$TRANS_text .= $line_string[ $l ];
			}
		}

		$prev_line_number = $line_number;
	}
}

?>