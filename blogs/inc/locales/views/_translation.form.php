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
 * @version $Id: _region.form.php 9 2011-10-24 22:32:00Z fplanque $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

/**
 * @var String
 */
global $edited_String;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'region_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this translated string!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('translation') ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('Add new translated string') : T_('Edit a translated string') );

	$Form->add_crumb( 'translation' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',itst_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->info( T_('Original string'), evo_htmlspecialchars( $edited_String->iost_string ) );

	$Form->info( T_('Locale'), $edited_String->itst_locale );

	$Form->textarea( 'itst_standard', $edited_String->itst_standard, 5, T_('Translated string'), '', 100, '', true );

$Form->end_form( array( array( 'submit', 'actionArray[update]', $creating ? T_('Add') : T_('Save Changes!'), 'SaveButton' ) ) );

?>