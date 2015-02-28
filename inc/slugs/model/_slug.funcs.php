<?php
/**
 * This file implements Slug handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'slugs/model/_slug.class.php', 'Slug' );

/*
 * Generetae a tiny url (Slug)
 * The first character is always a lowercase letter
 * The second character is always a capital letter
 * The third caharacter is always a number
 * at most three letter (uppercase or lowercase) can be after each other -> every fourth character is a number
 * at most three number can be after each other -> every fourth character is a letter
 * 
 * @param string|NULL use this param from getnext_url function, when the last generated url already exists
 * @return string the newly generated tinyurl
 */
function generate_tinyurl( $last_url = NULL )
{
	global $Settings;

	/* Ascii code table for the used characters
	$zero = 48;
	$nine = 57;
	$lower_a = 97;
	$lower_z = 122;
	$upper_a = 65;
	$upper_z = 90;
	*/

	// get the last used tinyurl
	if( ! $last_url && $Settings != null )
	{ // last_url is not set
		$last_url = $Settings->get( 'tinyurl' );
	}

	if( ! $last_url )
	{ // no tinyurl is set, used the default value for the first one
		$last_url = 'aA0';
		return $last_url;
	}

	$index = strlen($last_url);
	//find the last character what can be changed
	$newurl_ending = '';
	while( $index )
	{
		$char = substr( $last_url, $index - 1, 1 );
		$char_value = ord( $char );
		if( $index <= 3 )
		{ // we have to change one of the first three character
			if( $char_value >= 65 )
			{ // the character is a letter
				if( $char_value >= 97 )
				{ // the character is a lowercase letter
					if( $char_value < 122 )
					{ // there is still unused lowercase letter
						$char_value++;
						$newurl_ending = chr( $char_value ).$newurl_ending;
						break;
						// exit from the loop,the change was done
					}
					// there is no more variation , shift and try the next character
					$newurl_ending = chr( 97 ).$newurl_ending;
					$index--;
					continue;
				}
				elseif( $char_value < 90 )
				{
					$char_value++;
					$newurl_ending = chr( $char_value ).$newurl_ending;
					break;
				}
				$newurl_ending = chr( 65 ).$newurl_ending;
				$index--;
				continue;
			}
			elseif( $char_value >= 48 )
			{
				if( $char_value < 57 )
				{
					$char_value++;
					$newurl_ending = chr( $char_value ).$newurl_ending;
					break;
				}
				$newurl_ending = chr( 48 ).$newurl_ending;
				$index--;
				continue;
			}
			else
			{
				debug_die( 'Wrong tinyurl in database!' );
			}
		}
		else
		{ // we change a character after the third position
			if( $char_value >= 48 && $char_value <= 57 )
			{
				if( $char_value != 57 )
				{
					$char_value++;
				}
				else
				{
					if( ( $index - 3) % 4 == 0 )
					{ // every fourth character must be a number
						$newurl_ending = chr( 48 ).$newurl_ending;
						$index--;
						continue;
					}
					else
					{
						$char_value = 65;
					}
				}
				$newurl_ending = chr( $char_value ).$newurl_ending;
				break;
			}
			if( $char_value >= 65 && $char_value <= 90 )
			{
				if( $char_value != 90 )
				{
					$char_value++;
				}
				else
				{
					$char_value = 97;
				}
				$newurl_ending = chr( $char_value ).$newurl_ending;
				break;
			}
			if( $char_value >= 97 && $char_value < 122 )
			{
				$char_value++;
				$newurl_ending = chr( $char_value ).$newurl_ending;
				break;
			}
			if( $char_value == 122 )
			{ // no more variation we need to shift to another position
				if( ( $index - 3 ) % 4 == 3 )
				{ // every fourth character must be a letter
					$newurl_ending = chr( 65 ).$newurl_ending;
				}
				else
				{
					$newurl_ending = chr( 48 ).$newurl_ending;
				}
				$index--;
				continue;
			}
			else
			{
				debug_die( 'Wrong tinyurl in database!' );
			}
		}
	}

	if( $index )
	{ // index is not null we change the last_url ending part
		$new_url = substr_replace( $last_url, $newurl_ending, $index - 1 );
		return $new_url;
	}

	// we need to add one more character, because we don't have any other unused variation
	$index = strlen($last_url);
	if( ( $index > 4 ) && ( ( $index - 2 ) % 4 == 3 ) )
	{
		$new_url = $newurl_ending.chr( 65 );
	}
	else
	{
		$new_url = $newurl_ending.chr( 48 );
	}
	return $new_url;
}

/*
 * Create a new unused tinyurl
 *  
 * @return string tinyurl
 */
function getnext_tinyurl()
{
	global $DB;

	$url = NULL;
	do
	{ // create a new tinyurl, until it is unique
		$url = generate_tinyurl( $url );
		$query = 'SELECT slug_ID
					FROM T_slug
					WHERE slug_title = '.$DB->quote( $url );
	}
	while( $DB->get_var( $query ) != NULL );
	return $url;
}

?>