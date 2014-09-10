<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id: _city_import.form.php 9 2011-10-24 22:32:00Z fplanque $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

$Form = new Form( NULL, 'city_checkchanges', 'post', 'compact', 'multipart/form-data' );

$Form->global_icon( T_('Cancel importing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Import cities') );

	echo T_('Select a country and upload a CSV file with the following columns:');
	echo '<div style="padding:10px 0 10px 40px">';
	echo T_('1. Postcode').'<br />';
	echo T_('2. City name').'<br />';
	echo T_('3. Optional: sub region code');
	echo '</div>';

	$Form->add_crumb( 'city' );
	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	$CountryCache = & get_CountryCache();
	$Form->select_country( 'ctry_ID', get_param( 'ctry_ID' ), $CountryCache, T_('Country'), array( 'allow_none' => true, 'required' => true ) );

	$Form->input_field( array( 'label' => T_('CSV File'), 'name' => 'csv', 'type' => 'file', 'required' => true ) );

$Form->end_form( array( array( 'submit', 'actionArray[import]', T_('Import'), 'SaveButton' ) ) );

?>