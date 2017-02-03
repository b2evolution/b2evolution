<?php
/**
 * This file implements the UI controller for translation management.
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

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

load_funcs('locales/model/_translation.funcs.php');

$AdminUI->set_path( 'options', 'regional', 'locales' );

param_action();
param( 'edit_locale', 'string', '', true );

// Load all available locale defintions:
locales_load_available_defs();

if( !isset( $locales[$edit_locale] ) )
{	// Check for correct locale
	$Messages->add( T_('The locale is incorrect!'), 'error' );
	header_redirect( '?ctrl=locales&loc_transinfo=1', 303 );
}

/* Set charset of edited locale in order to display the special UTF symbols correctly */
global $locales, $io_charset, $evo_charset;
if( $locales[$edit_locale]['charset'] == 'utf-8' && $io_charset != 'utf-8' )
{
	// Set encoding for MySQL connection
	$DB->set_connection_charset( $locales[$edit_locale]['charset'] );
	// Set charset for html format
	$io_charset = $locales[$edit_locale]['charset'];
	$evo_charset = $locales[$edit_locale]['charset'];
}

switch( $action )
{
	case 'import_po':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'translation' );

		if( translation_update_table_po( $edit_locale ) )
		{
			$Messages->add( T_('The file .PO was imported into database successfully'), 'success' );
		}
		header_redirect( '?ctrl=translation&edit_locale='.$edit_locale, 303 );
		break;

	case 'generate_po':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'translation' );

		if( translation_generate_po_file( $edit_locale ) )
		{
			$Messages->add( T_('The file .PO was generated successfully'), 'success' );
		}
		header_redirect( '?ctrl=translation&edit_locale='.$edit_locale, 303 );
		break;

	case 'new':
		param( 'iost_ID', 'integer', 0, true );

		$SQL = new SQL();
		$SQL->SELECT( '*, "'.$edit_locale.'" AS itst_locale, "" AS itst_standard' );
		$SQL->FROM( 'T_i18n_original_string' );
		$SQL->WHERE( 'iost_ID = '.$DB->quote( $iost_ID ) );
		$edited_String = $DB->get_row( $SQL->get() );
		break;

	case 'edit':
		param( 'itst_ID', 'integer', 0, true );

		$SQL = new SQL();
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_i18n_translated_string' );
		$SQL->FROM_add( 'LEFT JOIN T_i18n_original_string ON iost_ID = itst_iost_ID' );
		$SQL->WHERE( 'itst_ID = '.$DB->quote( $itst_ID ) );
		$edited_String = $DB->get_row( $SQL->get() );
		break;

	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'translation' );

		param( 'itst_ID', 'integer' );
		param( 'itst_standard', 'string' );

		if( $itst_ID > 0 )
		{	// Update translated string
			$DB->query( 'UPDATE T_i18n_translated_string
				  SET itst_standard = '.$DB->quote( $itst_standard ).'
				WHERE itst_ID = '.$DB->quote( $itst_ID ) );

			$Messages->add( T_('A translated string was updated.'), 'success' );
			header_redirect( '?ctrl=translation&edit_locale='.$edit_locale, 303 );
		}
		else
		{	// Insert new translated string
			param( 'iost_ID', 'integer' );

			$DB->query( 'INSERT T_i18n_translated_string
				( itst_iost_ID, itst_locale, itst_standard, itst_inpofile ) VALUES
				( '.$iost_ID.', '.$DB->quote( $edit_locale ).', '.$DB->quote( $itst_standard ).', 1 )' );

			$Messages->add( T_('New translated string was added.'), 'success' );
			header_redirect( '?ctrl=translation&edit_locale='.$edit_locale, 303 );
		}
		break;

	case 'delete':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'translation' );

		param( 'itst_ID', 'integer' );

		$DB->query( 'DELETE FROM T_i18n_translated_string WHERE itst_ID = '.$DB->quote( $itst_ID ) );

		$Messages->add( T_('A translated string was deleted.'), 'success' );
		header_redirect( '?ctrl=translation&edit_locale='.$edit_locale, 303 );
		break;
}

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), $admin_url.'?ctrl=system',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('Regional'), $admin_url.'?ctrl=locales' );
$AdminUI->breadcrumbpath_add( T_('Locales'), $admin_url.'?ctrl=locales' );
$AdminUI->breadcrumbpath_add( T_('Translation editor'), $admin_url.'?ctrl=translation&locale='.$locale );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
switch( $action )
{
	case 'new_strings':
		param( 'action', 'string', '', true );
		$AdminUI->disp_view( 'locales/views/_translation_new.view.php' );
		break;

	case 'new':
	case 'edit':
		$AdminUI->disp_view( 'locales/views/_translation.form.php' );
		break;

	default:
		$AdminUI->disp_view( 'locales/views/_translation.view.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>