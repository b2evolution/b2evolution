<?php
/**
 * This file implements the UI view for the translation editor.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var User
 */
global $current_User;

global $edit_locale;

// Create query
$SQL = new SQL();
$SQL->SELECT( 'iost_ID, iost_string' );
$SQL->FROM( 'T_i18n_original_string' );
$SQL->ORDER_BY( '*, iost_string' );


// Create result set:
$Results = new Results( $SQL->get(), 'iost_', 'A' );

$Results->title = sprintf( T_('Translation editor for locale "%s"'), $edit_locale );

$Results->cols[] = array(
		'th' => T_('Original string'),
		'order' => 'iost_string',
		'td' => '%htmlspecialchars( #iost_string# )%',
	);

function iost_td_actions( $translated_string_ID )
{
	$r = action_icon( T_('Translate this string...'), 'add',
										regenerate_url( 'action', 'iost_ID='.$translated_string_ID.'&amp;action=new' ) );

	return $r;
}

$Results->cols[] = array(
		'th' => T_('Actions'),
		'td' => '%iost_td_actions( #iost_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->display();

?>