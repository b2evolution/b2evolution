<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: locales.ctrl.php 6665 2014-05-12 12:37:03Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

// Memorize this as the last "tab" used in the Global Settings:
$UserSettings->set( 'pref_glob_settings_tab', $ctrl );
$UserSettings->set( 'pref_glob_regional_tab', $ctrl );
$UserSettings->dbupdate();

$AdminUI->set_path( 'options', 'regional', 'locales' );

$action = param_action();
param( 'edit_locale', 'string' );
param( 'loc_transinfo', 'integer', 0 );

// Load all available locale defintions:
locales_load_available_defs();

switch( $action )
{
	case 'abort_update':
		// Update was aborted
		break;

	case 'update':
	case 'confirm_update':
		// UPDATE regional settings

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'locales' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'newdefault_locale', 'string', true );
		$Settings->set( 'default_locale', $newdefault_locale );

		if( ( ! $Messages->has_errors() ) && ( locale_updateDB() ) )
		{
			$Settings->dbupdate();
			$Messages->add( T_('Regional settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=locales'.( $loc_transinfo ? '&loc_transinfo=1' : '' ), 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;


	case 'updatelocale':
	case 'createlocale':
		// CREATE/EDIT locale

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'locales' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'newloc_locale', 'string', true );
		param_check_regexp( 'newloc_locale', '/^[a-z]{2,3}-[A-Z]{2}.*$/', T_('Please use valid locale format.') );
		param( 'newloc_enabled', 'integer', 0 );
		param( 'newloc_name', 'string', true );
		param( 'newloc_charset', 'string', true );
		param_check_not_empty( 'newloc_charset', T_('Charset cannot be empty.') );
		param( 'newloc_datefmt', 'string', true );
		param_check_not_empty( 'newloc_datefmt', T_('Date format cannot be empty.') );
		param( 'newloc_timefmt', 'string', true );
		param_check_not_empty( 'newloc_timefmt', T_('Time format cannot be empty.') );
		param( 'newloc_startofweek', 'integer', 0 );
		param( 'newloc_priority', 'integer', 1 );
		param_check_range( 'newloc_priority', 1, 255, T_('Priority must be numeric (1-255).') );
		param( 'newloc_messages', 'string', true );
		param( 'newloc_transliteration_map', 'string', true );

		if( param_errors_detected() )
		{ // Don't save locale if errors exist
			$action = 'edit';
			break;
		}

		if( $action == 'updatelocale' )
		{
			param( 'oldloc_locale', 'string', true );

			if( $DB->get_var( 'SELECT loc_locale FROM T_locales WHERE loc_locale = '.$DB->quote( $oldloc_locale ) ) )
			{ // old locale exists in DB
				if( $oldloc_locale != $newloc_locale )
				{ // locale key was renamed, we delete the old locale in DB and remember to create the new one
					$q = $DB->query( 'DELETE FROM T_locales
															WHERE loc_locale = '.$DB->quote( $oldloc_locale ) );
					if( $DB->rows_affected )
					{
						$Messages->add( sprintf( T_('Deleted settings for locale &laquo;%s&raquo; in database.'), $oldloc_locale ), 'success' );
					}
				}
			}
			elseif( isset( $locales[ $oldloc_locale ] ) )
			{ // old locale is not in DB yet. Insert it.

				$transliteration_map = '';
				if( isset( $locales[$oldloc_locale]['transliteration_map']) && is_array( $locales[$oldloc_locale]['transliteration_map'] ) )
				{
					$transliteration_map = base64_encode( serialize( $locales[$oldloc_locale]['transliteration_map'] ) );
				}

				$query = "INSERT INTO T_locales
									( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_startofweek, loc_name, loc_messages, loc_priority, loc_transliteration_map, loc_enabled )
									VALUES ( '$oldloc_locale',
									'{$locales[$oldloc_locale]['charset']}', '{$locales[$oldloc_locale]['datefmt']}',
									'{$locales[$oldloc_locale]['timefmt']}', '{$locales[$oldloc_locale]['startofweek']}',
									'{$locales[$oldloc_locale]['name']}', '{$locales[$oldloc_locale]['messages']}',
									'{$locales[$oldloc_locale]['priority']}', '$transliteration_map'";
				if( $oldloc_locale != $newloc_locale )
				{ // disable old locale
					$query .= ', 0 )';
					$Messages->add( sprintf( T_('Inserted (and disabled) locale &laquo;%s&raquo; into database.'), $oldloc_locale ), 'success' );
				}
				else
				{ // keep old state
					$query .= ', '.( $locales[$oldloc_locale]['enabled'] ).' )';
					$Messages->add( sprintf( T_('Inserted locale &laquo;%s&raquo; into database.'), $oldloc_locale ), 'success' );
				}
				$q = $DB->query( $query );
			}
		}

		$query = 'REPLACE INTO T_locales
							( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_startofweek, loc_name, loc_messages, loc_priority, loc_transliteration_map, loc_enabled )
							VALUES ( '.$DB->quote( $newloc_locale ).', '.$DB->quote( $newloc_charset ).', '.$DB->quote( $newloc_datefmt ).', '
								.$DB->quote( $newloc_timefmt ).', '.$DB->quote( $newloc_startofweek ).', '.$DB->quote( $newloc_name ).', '
								.$DB->quote( $newloc_messages ).', '.$DB->quote( $newloc_priority ).', '.$DB->quote( $newloc_transliteration_map ).', '
								.$DB->quote( $newloc_enabled ).' )';
		$q = $DB->query( $query );
		$Messages->add( sprintf(T_('Saved locale &laquo;%s&raquo;.'), $newloc_locale), 'success' );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=locales'.( $loc_transinfo ? '&loc_transinfo=1' : '' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!

		/*
		// reload locales: an existing one could have been renamed (but we keep $evo_charset, which may have changed)
		$old_evo_charset = $evo_charset;
		unset( $locales );
		include $conf_path.'_locales.php';
		if( file_exists($conf_path.'_overrides_TEST.php') )
		{ // also overwrite settings again:
			include $conf_path.'_overrides_TEST.php';
		}
		$evo_charset = $old_evo_charset;

		// Load all available locale defintions:
		locales_load_available_defs();

		// load locales from DB into $locales array:
		locale_overwritefromDB();
		*/
		break;


	case 'reset':
		// RESET locales in DB

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'locales' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// forget DB locales:
		unset( $locales );

		// delete everything from locales table
		$q = $DB->query( 'DELETE FROM T_locales' );

		if( !isset( $locales[$current_locale] ) )
		{ // activate default locale
			locale_activate( $default_locale );
		}

		// reset default_locale
		$Settings->set( 'default_locale', $default_locale );
		$Settings->dbupdate();

		// Reload locales from files:
		unset( $locales );
		include $conf_path.'_locales.php';
		if( file_exists($conf_path.'_overrides_TEST.php') )
		{ // also overwrite settings again (just in case we override some local erelated things):
			include $conf_path.'_overrides_TEST.php';
		}

		// Load all available locale defintions from locale folders:
		locales_load_available_defs();

		// Reenable default locale
		locale_insert_default();

		$Messages->add( T_('Locale definitions reset to defaults. (<code>/conf/_locales.php</code>)'), 'success' );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=locales'.( $loc_transinfo ? '&loc_transinfo=1' : '' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;


	case 'extract':
		// EXTRACT locale

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'locales' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Get PO file for that edit_locale:
		$AdminUI->append_to_titlearea( 'Extracting language file for '.$edit_locale.'...' );

		$po_file = $locales_path.$locales[$edit_locale]['messages'].'/LC_MESSAGES/messages.po';
		if( ! is_file( $po_file ) )
		{
			$Messages->add( sprintf(T_('File <code>%s</code> not found.'), '/'.$locales_subdir.$locales[$edit_locale]['messages'].'/LC_MESSAGES/messages.po'), 'error' );
			break;
		}

		$outfile = $locales_path.$locales[$edit_locale]['messages'].'/_global.php';
		if( file_exists( $outfile ) && ( !is_writable( $outfile ) ) )
		{ // The '_global.php' file exists but it is not writable
			$Messages->add( sprintf( 'The file &laquo;%s&raquo; is not writable.', $outfile ) );
			break;
		}

		load_class( 'locales/_pofile.class.php', 'POFile' );
		$POFile = new POFile($po_file);
		$POFile->read(true); // adds info about sources to $Messages
		$POFile->write_evo_trans($outfile, $locales[$edit_locale]['messages']);

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=locales'.( $loc_transinfo ? '&loc_transinfo=1' : '' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;


	case 'resetlocale':
		// Reset a specific Locale:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'locales' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// --- DELETE locale from DB
		if( $DB->query( 'DELETE FROM T_locales
											WHERE loc_locale = "'.$DB->escape( $edit_locale ).'"' ) )
		{
			$Messages->add( sprintf(T_('Deleted locale &laquo;%s&raquo; from database.'), $edit_locale), 'success' );
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=locales'.( $loc_transinfo ? '&loc_transinfo=1' : '' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!

		/*
		// reload locales
		unset( $locales );
		require $conf_path.'_locales.php';
		if( file_exists($conf_path.'_overrides_TEST.php') )
		{ // also overwrite settings again:
			include $conf_path.'_overrides_TEST.php';
		}

		// Load all available locale defintions:
		locales_load_available_defs();

		// load locales from DB into $locales array:
		locale_overwritefromDB();
		*/
		break;


	case 'prioup':
	case 'priodown':
		// --- SWITCH PRIORITIES -----------------

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'locales' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$switchcond = '';
		if( $action == 'prioup' )
		{
			$switchcond = 'return ($lval[\'priority\'] > $i && $lval[\'priority\'] < $locales[ $edit_locale ][\'priority\']);';
			$i = -1;
		}
		elseif( $action == 'priodown' )
		{
			$switchcond = 'return ($lval[\'priority\'] < $i && $lval[\'priority\'] > $locales[ $edit_locale ][\'priority\']);';
			$i = 256;
		}

		if( !empty($switchcond) )
		{ // we want to switch priorities

			foreach( $locales as $lkey => $lval )
			{ // find nearest priority
				if( eval($switchcond) )
				{
					// remember it
					$i = $lval['priority'];
					$lswitchwith = $lkey;
				}
			}
			if( $i > -1 && $i < 256 )
			{ // switch
				#echo 'Switching prio '.$locales[ $lswitchwith ]['priority'].' with '.$locales[ $lswitch ]['priority'].'<br />';
				$locales[ $lswitchwith ]['priority'] = $locales[ $edit_locale ]['priority'];
				$locales[ $edit_locale ]['priority'] = $i;

				$lswitchwith_transliteration_map = is_array($locales[ $lswitchwith ]['transliteration_map']) ? base64_encode(serialize($locales[ $lswitchwith ]['transliteration_map'])) : '';
				$edit_transliteration_map = is_array($locales[ $edit_locale ]['transliteration_map']) ? base64_encode(serialize($locales[ $edit_locale ]['transliteration_map'])) : '';

				$query = "REPLACE INTO T_locales ( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name, loc_messages, loc_priority, loc_transliteration_map, loc_enabled )	VALUES
					( '$edit_locale', '{$locales[ $edit_locale ]['charset']}', '{$locales[ $edit_locale ]['datefmt']}', '{$locales[ $edit_locale ]['timefmt']}', '{$locales[ $edit_locale ]['name']}', '{$locales[ $edit_locale ]['messages']}', '{$locales[ $edit_locale ]['priority']}', '$edit_transliteration_map', '{$locales[ $edit_locale ]['enabled']}'),
					( '$lswitchwith', '{$locales[ $lswitchwith ]['charset']}', '{$locales[ $lswitchwith ]['datefmt']}', '{$locales[ $lswitchwith ]['timefmt']}', '{$locales[ $lswitchwith ]['name']}', '{$locales[ $lswitchwith ]['messages']}', '{$locales[ $lswitchwith ]['priority']}', '$lswitchwith_transliteration_map', '{$locales[ $lswitchwith ]['enabled']}')";
				$q = $DB->query( $query );

				$Messages->add( T_('Switched priorities.'), 'success' );

				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=locales'.( $loc_transinfo ? '&loc_transinfo=1' : '' ), 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}

			// load locales from DB into $locales array:
			locale_overwritefromDB();
		}
		break;
}

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('Regional settings'), '?ctrl=locales' );
$AdminUI->breadcrumbpath_add( T_('Locales'), '?ctrl=locales' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'locales/_locale_settings.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>