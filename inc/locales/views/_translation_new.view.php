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
global $admin_url, $current_User;

global $edit_locale;

$original_string = param( 'original', 'string', '', true );

// Create query
$SQL = new SQL();
$SQL->SELECT( 'iost_ID, iost_string' );
$SQL->FROM( 'T_i18n_original_string' );
$SQL->WHERE( 'NOT EXISTS ( SELECT 1 FROM T_i18n_translated_string WHERE itst_iost_ID = iost_ID AND itst_locale = '.$DB->quote( $edit_locale ).')' );
$SQL->ORDER_BY( '*, iost_string' );

if( ! empty( $original_string ) )
{
	$SQL->add_search_field( 'iost_string' );
	$SQL->WHERE_kw_search( $original_string, 'AND' );
}


// Create result set:
$Results = new Results( $SQL->get(), 'iost_', 'A' );

$Results->title = sprintf( T_('Adding a translated string for locale "%s"'), $edit_locale );

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_translation( & $Form )
{
	$Form->text( 'original', get_param( 'original' ), 20, T_('Original string') );
}

$Results->filter_area = array(
	'callback' => 'filter_translation',
	'presets' => array(
		'all' => array( T_('All'), $admin_url.'?ctrl=translation&edit_locale='.$edit_locale.'&action=new_strings' ),
		)
	);

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