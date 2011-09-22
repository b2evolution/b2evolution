<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_currency.class.php', 'Currency' );

global $dispatcher;

// Get params from request
$s = param( 's', 'string', '', true );

// Create query
$SQL = new SQL();
$SQL->SELECT( 'ctry_ID, ctry_code, ctry_name, curr_shortcut, curr_code, ctry_enabled, ctry_preferred' );
$SQL->FROM( 'T_country	LEFT JOIN T_currency ON ctry_curr_ID=curr_ID' );

if( !empty($s) )
{	// We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$SQL->WHERE( 'CONCAT_WS( " ", ctry_code, ctry_name, curr_code ) LIKE "%'.$DB->escape($s).'%"' );
}

// Create result set:
$Results = new Results( $SQL->get(), 'ctry_', '-A' );

$Results->title = T_('Countries list').get_manual_link('countries_list');

/*
 * STATUS TD:
 */
function ctry_td_enabled( $ctry_enabled, $ctry_ID )
{

	global $dispatcher;

	$r = '';

	if( $ctry_enabled == true )
	{
		$r .= action_icon( T_('Disable the country!'), 'bullet_full',
										regenerate_url( 'action', 'action=disable_country&amp;ctry_ID='.$ctry_ID.'&amp;'.url_crumb('country') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the country!'), 'bullet_empty',
										regenerate_url( 'action', 'action=enable_country&amp;ctry_ID='.$ctry_ID.'&amp;'.url_crumb('country') ) );
	}
	return $r;

}

function ctry_td_preferred( $ctry_preferred, $ctry_ID )
{

	global $dispatcher;

	$r = '';

	if( $ctry_preferred == true )
	{
		$r .= action_icon( T_('Disable country preference!'), 'bullet_full',
										regenerate_url( 'action', 'action=disable_country_pref&amp;ctry_ID='.$ctry_ID.'&amp;'.url_crumb('country') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the preference!'), 'bullet_empty',
										regenerate_url( 'action', 'action=enable_country_pref&amp;ctry_ID='.$ctry_ID.'&amp;'.url_crumb('country') ) );
	}
	return $r;

}



$Results->cols[] = array(
		'th' => /* TRANS: shortcut for enabled */ T_('En'),
		'order' => 'ctry_enabled',
		'td' => '%ctry_td_enabled( #ctry_enabled# , #ctry_ID# )%',
		'td_class' => 'center'
	);

$Results->cols[] = array(
		'th' => /* TRANS: shortcut for enabled */ T_('Preferred'),
		'order' => 'ctry_preferred',
		'td' => '%ctry_td_preferred( #ctry_preferred# , #ctry_ID# )%',
		'td_class' => 'center'
	);


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_countries( & $Form )
{
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_countries',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=countries' ),
		)
	);

$Results->cols[] = array(
						'th' => T_('Code'),
						'td_class' => 'center',
						'order' => 'ctry_code',
						'td' => '<strong>$ctry_code$</strong>',
					);

/**
 * Template function: Display country flag
 *
 * @todo factor with locale_flag()
 *
 * @param string country code to use
 * @param string country name to use
 * @param string collection name (subdir of img/flags)
 * @param string name of class for IMG tag
 * @param string deprecated HTML align attribute
 * @param boolean to echo or not
 * @param mixed use absolute url (===true) or path to flags directory
 */
function country_flag( $country_code, $country_name, $collection = 'w16px', $class = 'flag', $align = '', $disp = true, $absoluteurl = true )
{
	global $rsc_path, $rsc_url;

	if( ! is_file( $rsc_path.'flags/'.$collection.'/'.$country_code.'.gif') )
	{ // File does not exist
		$country_code = 'default';
	}

	if( $absoluteurl !== true )
	{
		$iurl = $absoluteurl;
	}
	else
	{
		$iurl = $rsc_url.'flags';
	}

	$r = '<img src="'.$iurl.'/'.$collection.'/'.$country_code.'.gif" alt="' .
				$country_name .
				'"';
	if( !empty( $class ) ) $r .= ' class="'.$class.'"';
	if( !empty( $align ) ) $r .= ' align="'.$align.'"';
	$r .= ' /> ';

	if( $disp )
		echo $r;   // echo it
	else
		return $r; // return it

}


if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'ctry_name',
										'td' => '<a href="?ctrl=countries&amp;ctry_ID=$ctry_ID$&amp;action=edit" title="'.T_('Edit this country...')
											.'">%country_flag( #ctry_code#, #ctry_name# )%
								<strong>$ctry_name$</strong></a>',
						);
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'ctry_name',
							'td' => '%country_flag( #ctry_code#, #ctry_name# )%  $ctry_name$',
						);

}
$Results->cols[] = array(
						'th' => T_('Default Currency'),
						'td_class' => 'center',
						'order' => 'curr_code',
						'td' => '$curr_shortcut$ $curr_code$',
					);

/*
 * ACTIONS TD:
 */
function ctry_td_actions($ctry_enabled, $ctry_ID )
{
	global $dispatcher;

	$r = '';

	if( $ctry_enabled == true )
	{
		$r .= action_icon( T_('Disable the country!'), 'deactivate', 
										regenerate_url( 'action', 'action=disable_country&amp;ctry_ID='.$ctry_ID.'&amp;'.url_crumb('country') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the country!'), 'activate',
										regenerate_url( 'action', 'action=enable_country&amp;ctry_ID='.$ctry_ID.'&amp;'.url_crumb('country') ) );
	}
	$r .= action_icon( T_('Edit this country...'), 'edit',
										regenerate_url( 'action', 'ctry_ID='.$ctry_ID.'&amp;action=edit' ) );
	$r .= action_icon( T_('Duplicate this country...'), 'copy',
										regenerate_url( 'action', 'ctry_ID='.$ctry_ID.'&amp;action=new' ) );
	$r .= action_icon( T_('Delete this country!'), 'delete',
										regenerate_url( 'action', 'ctry_ID='.$ctry_ID.'&amp;action=delete&amp;'.url_crumb('country') ) );

	return $r;
}
if( $current_User->check_perm( 'options', 'edit', false ) )
{
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td' => '%ctry_td_actions( #ctry_enabled#, #ctry_ID# )%',
			'td_class' => 'shrinkwrap',
		);

	$Results->global_icon( T_('Create a new country ...'), 'new',
				regenerate_url( 'action', 'action=new'), T_('New country').' &raquo;', 3, 4  );
}

$Results->display();

/*
 * $Log$
 * Revision 1.21  2011/09/22 13:03:11  efy-vitalij
 * add country pref column, clickable En column in countries and currencies results  tables
 *
 * Revision 1.20  2010/03/01 07:52:51  efy-asimo
 * Set manual links to lowercase
 *
 * Revision 1.19  2010/02/14 14:18:39  efy-asimo
 * insert manual links
 *
 * Revision 1.18  2010/01/30 18:55:33  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.17  2010/01/16 14:16:32  efy-asimo
 * Currencies/Countries cosmetics and regenerate_url after Enable/Disable
 *
 * Revision 1.16  2010/01/03 12:03:17  fplanque
 * More crumbs...
 *
 * Revision 1.15  2009/09/29 03:14:22  fplanque
 * doc
 *
 * Revision 1.14  2009/09/28 20:55:00  efy-khurram
 * Implemented support for enabling disabling countries.
 *
 * Revision 1.13  2009/09/16 00:26:03  fplanque
 * no message
 *
 * Revision 1.12  2009/09/15 16:25:24  efy-sasha
 * *** empty log message ***
 *
 * Revision 1.11  2009/09/14 22:18:27  fplanque
 * tssss.... cleaned up ith proper merge.
 *
 * Revision 1.10  2009/09/14 18:32:51  efy-sasha
 * *** empty log message ***
 *
 * Revision 1.9  2009/09/12 18:44:03  efy-sergey
 * Added a search field to the countries tables
 *
 * Revision 1.8  2009/09/12 18:18:02  efy-sergey
 * Changed query creation to using an SQL object
 *
 * Revision 1.7  2009/09/12 00:21:02  fplanque
 * search cleanup
 *
 * Revision 1.6  2009/09/11 11:19:03  efy-sergey
 * Displaying currency code
 *
 * Revision 1.5  2009/09/10 19:14:08  tblue246
 * Re-added CVS log block; coding style.
 *
 */
?>