<?php
/**
 * This file implements the UI view for the translation editor.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _translation_new.view.php 985 2012-03-05 21:59:17Z yura $
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
		'td' => '%evo_htmlspecialchars( #iost_string# )%',
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