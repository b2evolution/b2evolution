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
$translated_string = param( 'translated', 'string', '', true );
$untranslated_only = param( 'untranslated_only', 'boolean', 0, true );

$Form = new Form();
$Form->formclass = 'form-inline';

$Form->begin_form( 'fform' );

$Form->add_crumb( 'translation' );
$Form->hidden( 'ctrl', 'translation' );
$Form->hidden( 'edit_locale', $edit_locale );

if( $untranslated_only )
{
	// Create query
	$SQL = new SQL();
	$SQL->SELECT( ' NULL as itst_ID, iost_string' );
	$SQL->FROM( 'T_i18n_original_string' );
	$SQL->WHERE( 'NOT EXISTS ( SELECT 1 FROM T_i18n_translated_string WHERE itst_iost_ID = iost_ID AND itst_locale = '.$DB->quote( $edit_locale ).')' );
	$SQL->ORDER_BY( 'iost_string' );

	// Create a count sql
	$count_SQL = new SQL();
	$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( itst_ID )' );
	$count_SQL->FROM( 'T_i18n_translated_string' );
	$count_SQL->WHERE( 'NOT EXISTS ( SELECT 1 FROM T_i18n_translated_string WHERE itst_iost_ID = iost_ID AND itst_locale = '.$DB->quote( $edit_locale ).')' );
}
else
{
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

	if( ! empty( $translated_string ) )
	{
		$SQL->add_search_field( 'itst_standard' );
		$SQL->WHERE_kw_search( $translated_string, 'AND' );
		$count_SQL->add_search_field( 'itst_standard' );
		$count_SQL->WHERE_kw_search( $translated_string, 'AND' );
	}
}

if( ! empty( $original_string ) )
{
	$SQL->add_search_field( 'iost_string' );
	$SQL->WHERE_kw_search( $original_string, 'AND' );
	$count_SQL->add_search_field( 'iost_string' );
	$count_SQL->WHERE_kw_search( $original_string, 'AND' );
}

// Create result set:
$Results = new Results( $SQL->get(), 'itst_', '-A'/*, NULL, $count_SQL->get()*/ );
$Results->Form = & $Form;

$Results->title = sprintf( T_('Translation editor for locale "%s"'), $edit_locale );

$Results->global_icon( T_('Add new translated string...'), 'new', regenerate_url( '', 'action=new_strings' ), T_('Add new translated string').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_translation( & $Form )
{
	$Form->switch_layout( 'blockspan' );
	$Form->text( 'original', get_param( 'original' ), 20, T_('Original string') );
	if( get_param( 'untranslated_only' ) )
	{
		$Form->text_input( 'translated', null, 20, T_('Translated string'), '', array( 'disabled' => 'disabled' ) );
	}
	else
	{
		$Form->text_input( 'translated', get_param( 'translated' ), 20, T_('Translated string') );
	}
	$Form->checkbox( 'untranslated_only', get_param( 'untranslated_only' ), T_('Show only untranslated strings') );
	$Form->switch_layout( NULL );
}

$Results->filter_area = array(
	'callback' => 'filter_translation',
	'presets' => array(
		'all' => array( T_('All'), $admin_url.'?ctrl=translation&edit_locale='.$edit_locale ),
		)
	);

$Results->cols[] = array(
		'th' => T_('Original string'),
		'order' => 'iost_string',
		'td' => '%htmlspecialchars( #iost_string# )%',
	);

if( ! $untranslated_only )
{
	$Results->cols[] = array(
			'th' => T_('Translated string'),
			'order' => 'itst_standard',
			'td' => '%htmlspecialchars( #itst_standard# )%',
		);
}

function iost_td_actions( $translated_string_ID )
{
	if( is_null( $translated_string_ID ) )
	{
		$r = action_icon( T_('Translate this string...'), 'add',
										regenerate_url( 'action', 'iost_ID='.$translated_string_ID.'&amp;action=new' ) );
	}
	else
	{
		$r = action_icon( T_('Edit this string...'), 'edit',
											regenerate_url( 'action', 'itst_ID='.$translated_string_ID.'&amp;action=edit' ) );
		$r .= action_icon( T_('Delete this string!'), 'delete',
											regenerate_url( 'action', 'itst_ID='.$translated_string_ID.'&amp;action=delete&amp;'.url_crumb('translation') ) );
	}

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
	if( is_file( $locales_path.$locales[$edit_locale]['messages'].'/LC_MESSAGES/messages.po' ) )
	{
		$buttons[] = array( 'submit', 'actionArray[import_po]', T_('Import .PO file into DB'), 'SaveButton' );
	}
	$buttons[] = array( 'submit', 'actionArray[generate_po]', T_('Generate .PO file'), 'SaveButton' );

	$Form->end_form( $buttons );
}

?>

<script type="text/javascript">
	jQuery( '#untranslated_only' ).on( 'change', function()
		{
			var translated_string_input = jQuery( '#translated' );
			if( jQuery( this ).is( ':checked' ) )
			{
				translated_string_input.attr( 'disabled',  'disabled' );
				translated_string_input.val( null );
			}
			else
			{
				translated_string_input.removeAttr( 'disabled' );
			}
		} );
</script>