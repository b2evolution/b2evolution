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
 * @version $Id: _translation.view.php 985 2012-03-05 21:59:17Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var User
 */
global $current_User;

global $edit_locale;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'translation' );
$Form->hidden( 'ctrl', 'translation' );
$Form->hidden( 'edit_locale', $edit_locale );

// Create query
$SQL = new SQL();
$SQL->SELECT( 'itst_ID, iost_string, itst_standard' );
$SQL->FROM( 'T_i18n_original_string' );
$SQL->FROM_add( 'RIGHT OUTER JOIN T_i18n_translated_string ON iost_ID = itst_iost_ID' );
$SQL->WHERE( 'itst_locale = '.$DB->quote( $edit_locale ) );
$SQL->ORDER_BY( '*, iost_string' );

// Create a count sql
$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( itst_ID )' );
$count_SQL->FROM( 'T_i18n_translated_string' );
$count_SQL->WHERE( 'itst_locale = '.$DB->quote( $edit_locale ) );

// Create result set:
$Results = new Results( $SQL->get(), 'itst_', '-A'/*, NULL, $count_SQL->get()*/ );

$Results->title = sprintf( T_('Translation editor for locale "%s"'), $edit_locale );

$Results->global_icon( T_('Add new translated string...'), 'new', regenerate_url( '', 'action=new_strings' ), T_('Add new translated string').' &raquo;', 3, 4 );

$Results->cols[] = array(
		'th' => T_('Original string'),
		'order' => 'iost_string',
		'td' => '%evo_htmlspecialchars( #iost_string# )%',
	);

$Results->cols[] = array(
		'th' => T_('Translated string'),
		'order' => 'itst_standard',
		'td' => '%evo_htmlspecialchars( #itst_standard# )%',
	);

function iost_td_actions( $translated_string_ID )
{
	$r = action_icon( T_('Edit this string...'), 'edit',
										regenerate_url( 'action', 'itst_ID='.$translated_string_ID.'&amp;action=edit' ) );
	$r .= action_icon( T_('Delete this string!'), 'delete',
										regenerate_url( 'action', 'itst_ID='.$translated_string_ID.'&amp;action=delete&amp;'.url_crumb('translation') ) );

	return $r;
}

$Results->cols[] = array(
		'th' => T_('Actions'),
		'td' => '%iost_td_actions( #itst_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->display();

echo '<br />';

if( $current_User->check_perm( 'options', 'edit' ) )
{
	global $locales_path, $locales;

	$buttons = array();
	$buttons[] = array( 'submit', 'actionArray[import_pot]', T_('Import .POT file into DB'), 'SaveButton' );
	if( is_file( $locales_path.$locales[$edit_locale]['messages'].'/LC_MESSAGES/messages.po' ) )
	{
		$buttons[] = array( 'submit', 'actionArray[import_po]', T_('Import .PO file into DB'), 'SaveButton' );
	}
	$buttons[] = array( 'submit', 'actionArray[generate_pot]', T_('Generate .POT file'), 'SaveButton' );
	$buttons[] = array( 'submit', 'actionArray[generate_po]', T_('Generate .PO file'), 'SaveButton' );

	$Form->end_form( $buttons ) ;
}

?>