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
 * @version $Id: _country.form.php 6135 2014-03-08 07:54:05Z manuel $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_currency.class.php', 'Currency' );

/**
 * @var Country
 */
global $edited_Country;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'country_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this country!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('country') ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New country') : T_('Country') );

	$Form->add_crumb( 'country' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',ctry_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->text_input( 'ctry_code', $edited_Country->code, 2, T_('Code'), '', array( 'maxlength'=> 2, 'required'=>true ) );

	$Form->text_input( 'ctry_name', $edited_Country->name, 40, T_('Name'), '', array( 'maxlength'=> 40, 'required'=>true ) );

	$CurrencyCache = & get_CurrencyCache();

	$Form->select_input_object( 'ctry_curr_ID', $edited_Country->curr_ID, $CurrencyCache, T_('Default Currency'), array( 'allow_none' => true ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>