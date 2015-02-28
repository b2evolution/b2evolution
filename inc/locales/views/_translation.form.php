<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
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

	$Form->info( T_('Original string'), htmlspecialchars( $edited_String->iost_string ) );

	$Form->info( T_('Locale'), $edited_String->itst_locale );

	$Form->textarea( 'itst_standard', $edited_String->itst_standard, 5, T_('Translated string'), '', 100, '', true );

$Form->end_form( array( array( 'submit', 'actionArray[update]', $creating ? T_('Add') : T_('Save Changes!'), 'SaveButton' ) ) );

?>