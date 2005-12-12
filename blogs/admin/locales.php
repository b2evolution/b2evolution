<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
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
 * @version $Id$
 */

/**
 * Includes:
 */
require dirname(__FILE__).'/_header.php';

$AdminUI->set_path( 'options', 'regional' );

param( 'action', 'string' );
param( 'edit_locale', 'string' );
param( 'loc_transinfo', 'integer', 0 );

if( in_array( $action, array( 'update', 'reset', 'updatelocale', 'createlocale', 'deletelocale', 'extract', 'prioup', 'priodown' )) )
{ // We have an action to do..
	// Check permission:
	$current_User->check_perm( 'options', 'edit', true );

	// clear settings cache
	$cache_settings = '';

	switch( $action )
	{ // switch between regional actions
		// UPDATE regional settings
		case 'update':
			$Request->param( 'newdefault_locale', 'string', true);
			$Settings->set( 'default_locale', $newdefault_locale );

			$Request->param_integer_range( 'newtime_difference', -23, 23, T_('Time difference must be between %d and %d.') );
			$Settings->set( 'time_difference', $newtime_difference );

			if( ! $Messages->count('error') )
			{
				locale_updateDB();
				$Settings->dbupdate();
				$Messages->add( T_('Regional settings updated.'), 'success' );
			}
			break;


		// CREATE/EDIT locale
		case 'updatelocale':
		case 'createlocale':
			param( 'newloc_locale', 'string', true );
			param( 'newloc_enabled', 'integer', 0);
			param( 'newloc_name', 'string', true);
			param( 'newloc_charset', 'string', true);
			param( 'newloc_datefmt', 'string', true);
			param( 'newloc_timefmt', 'string', true);
			param( 'newloc_startofweek', 'integer', true);
			param( 'newloc_messages', 'string', true);

			if( $action == 'updatelocale' )
			{
				param( 'oldloc_locale', 'string', true);

				$query = "SELECT loc_locale FROM T_locales WHERE loc_locale = '$oldloc_locale'";
				if( $DB->get_var($query) )
				{ // old locale exists in DB
					if( $oldloc_locale != $newloc_locale )
					{ // locale key was renamed, we delete the old locale in DB and remember to create the new one
						$q = $DB->query( 'DELETE FROM T_locales
																WHERE loc_locale = "'.$oldloc_locale.'"' );
						if( mysql_affected_rows() )
						{
							$Messages->add( sprintf(T_('Deleted settings for locale &laquo;%s&raquo; in database.'), $oldloc_locale), 'success' );
						}
					}
				}
				else
				{ // old locale is not in DB yet. Insert it.
					$query = "INSERT INTO T_locales
										( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_startofweek, loc_name, loc_messages, loc_priority, loc_enabled )
										VALUES ( '$oldloc_locale',
										'{$locales[$oldloc_locale]['charset']}', '{$locales[$oldloc_locale]['datefmt']}',
										'{$locales[$oldloc_locale]['timefmt']}', '{$locales[$oldloc_locale]['startofweek']}',
										'{$locales[$oldloc_locale]['name']}', '{$locales[$oldloc_locale]['messages']}',
										'{$locales[$oldloc_locale]['priority']}',";
					if( $oldloc_locale != $newloc_locale )
					{ // disable old locale
						$query .= ' 0)';
						$Messages->add( sprintf(T_('Inserted (and disabled) locale &laquo;%s&raquo; into database.'), $oldloc_locale), 'success' );
					}
					else
					{ // keep old state
						$query .= ' '.$locales[$oldloc_locale]['enabled'].')';
						$Messages->add( sprintf(T_('Inserted locale &laquo;%s&raquo; into database.'), $oldloc_locale), 'success' );
					}
					$q = $DB->query($query);
				}
			}

			$query = "REPLACE INTO T_locales
								( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_startofweek, loc_name, loc_messages, loc_priority, loc_enabled )
								VALUES ( '$newloc_locale', '$newloc_charset', '$newloc_datefmt',
								'$newloc_timefmt', '$newloc_startofweek', '$newloc_name', '$newloc_messages', '1', '$newloc_enabled')";
			$q = $DB->query($query);
			$Messages->add( sprintf(T_('Saved locale &laquo;%s&raquo;.'), $newloc_locale), 'success' );

			// reload locales: an existing one could have been renamed
			unset( $locales );
			include(  dirname(__FILE__).'/'.$admin_dirout.$conf_subdir.'_locales.php' );
			@include( dirname(__FILE__).'/'.$admin_dirout.$conf_subdir.'_overrides_TEST.php' );

			break;


		// RESET locales in DB
		case 'reset':
			// reload locales from files
			unset( $locales );
			include(  dirname(__FILE__).'/'.$admin_dirout.$conf_subdir.'_locales.php' );
			@include( dirname(__FILE__).'/'.$admin_dirout.$conf_subdir.'_overrides_TEST.php' );

			// delete everything from locales table
			$q = $DB->query( 'DELETE FROM T_locales WHERE 1' );

			if( !isset( $locales[$current_locale] ) )
			{ // activate default locale
				locale_activate( $default_locale );
			}

			// reset default_locale
			$Settings->set( 'default_locale', $default_locale );
			$Settings->dbupdate();

			$Messages->add( T_('Locales table deleted, defaults from <code>/conf/_locales.php</code> loaded.'), 'success' );
			break;


		// EXTRACT locale
		case 'extract':
			// Get PO file for that edit_locale:
			$AdminUI->append_to_titlearea( 'Extracting language file for '.$edit_locale.'...' );

			$po_file = dirname(__FILE__).'/'.$core_dirout.$locales_subdir.$locales[$edit_locale]['messages'].'/LC_MESSAGES/messages.po';
			if( ! is_file( $po_file ) )
			{
				$Messages->add( sprintf(T_('File <code>%s</code> not found.'), '/'.$locales_subdir.$locales[$edit_locale]['messages'].'/LC_MESSAGES/messages.po'), 'error' );
				break;
			}

			$outfile = dirname(__FILE__).'/'.$core_dirout.$locales_subdir.$locales[$edit_locale]['messages'].'/_global.php';
			if( !is_writable($outfile) )
			{
				$Messages->add( sprintf( 'The file &laquo;%s&raquo; is not writable.', $outfile ) );
				break;
			}


			// File exists:
			// Get PO file for that edit_locale:
			$lines = file( $po_file);
			$lines[] = '';	// Adds a blank line at the end in order to ensure complete handling of the file
			$all = 0;
			$fuzzy=0;
			$untranslated=0;
			$translated=0;
			$status='-';
			$matches = array();
			$sources = array();
			$loc_vars = array();
			$ttrans = array();
			foreach ($lines as $line)
			{
				// echo 'LINE:', $line, '<br />';
				if(trim($line) == '' )
				{ // Blank line, go back to base status:
					if( $status == 't' )
					{ // ** End of a translation **:
						if( $msgstr == '' )
						{
							$untranslated++;
							// echo 'untranslated: ', $msgid, '<br />';
						}
						else
						{
							$translated++;

							// Inspect where the string is used
							$sources = array_unique( $sources );
							// echo '<p>sources: ', implode( ', ', $sources ), '</p>';
							foreach( $sources as $source )
							{
								if( !isset( $loc_vars[$source]  ) ) $loc_vars[$source] = 1;
								else $loc_vars[$source] ++;
							}

							// Save the string
							// $ttrans[] = "\n\t'".str_replace( "'", "\'", str_replace( '\"', '"', $msgid ))."' => '".str_replace( "'", "\'", str_replace( '\"', '"', $msgstr ))."',";
							// $ttrans[] = "\n\t\"$msgid\" => \"$msgstr\",";
							$ttrans[] = "\n\t'".str_replace( "'", "\'", str_replace( '\"', '"', $msgid ))."' => \"".str_replace( '$', '\$', $msgstr)."\",";

						}
					}
					$status = '-';
					$msgid = '';
					$msgstr = '';
					$sources = array();
				}
				elseif( ($status=='-') && preg_match( '#^msgid "(.*)"#', $line, $matches))
				{ // Encountered an original text
					$status = 'o';
					$msgid = $matches[1];
					// echo 'original: "', $msgid, '"<br />';
					$all++;
				}
				elseif( ($status=='o') && preg_match( '#^msgstr "(.*)"#', $line, $matches))
				{ // Encountered a translated text
					$status = 't';
					$msgstr = $matches[1];
					// echo 'translated: "', $msgstr, '"<br />';
				}
				elseif( preg_match( '#^"(.*)"#', $line, $matches))
				{ // Encountered a followup line
					if ($status=='o')
						$msgid .= $matches[1];
					elseif ($status=='t')
						$msgstr .= $matches[1];
				}
				elseif( ($status=='-') && preg_match( '@^#:(.*)@', $line, $matches))
				{ // Encountered a source code location comment
					// echo $matches[0],'<br />';
					$sourcefiles = preg_replace( '@\\\\@', '/', $matches[1] );
					// $c = preg_match_all( '@ ../../../([^:]*):@', $sourcefiles, $matches);
					$c = preg_match_all( '@ ../../../([^/:]*/?)@', $sourcefiles, $matches);
					for( $i = 0; $i < $c; $i++ )
					{
						$sources[] = $matches[1][$i];
					}
					// echo '<br />';
				}
				elseif(strpos($line,'#, fuzzy') === 0)
					$fuzzy++;
			}

			if( $loc_vars )
			{
				ksort( $loc_vars );

				$list_counts = '';
				foreach( $loc_vars as $source => $c )
				{
					$list_counts .= "\n<li>$source = $c";
				}
				$Messages->add( 'Sources and number of strings: <ul>'.$list_counts.'</ul>', 'note' );
			}

			$fp = fopen( $outfile, 'w+' );
			fwrite( $fp, "<?php\n" );
			fwrite( $fp, "/*\n" );
			fwrite( $fp, " * Global lang file\n" );
			fwrite( $fp, " * This file was generated automatically from messages.po\n" );
			fwrite( $fp, " */\n" );
			fwrite( $fp, "if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );" );
			fwrite( $fp, "\n\n" );


			fwrite( $fp, "\n\$trans['".$locales[$edit_locale]['messages']."'] = array(" );
			// echo '<pre>';
			foreach( $ttrans as $line )
			{
				// echo htmlspecialchars( $line );
				fwrite( $fp, $line );
			}
			// echo '</pre>';
			fwrite( $fp, "\n);\n?>" );
			fclose( $fp );

			break;

		case 'deletelocale':
			// --- DELETE locale from DB
			if( $DB->query( 'DELETE FROM T_locales WHERE loc_locale = "'.$DB->escape( $edit_locale ).'"' ) )
			{
				$Messages->add( sprintf(T_('Deleted locale &laquo;%s&raquo; from database.'), $edit_locale), 'success' );
			}

			// reload locales
			unset( $locales );
			require(  dirname(__FILE__).'/'.$admin_dirout.$conf_subdir.'_locales.php' );
			@include( dirname(__FILE__).'/'.$admin_dirout.$conf_subdir.'_overrides_TEST.php' );

			break;

		// --- SWITCH PRIORITIES -----------------
		case 'prioup':
		case 'priodown':
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

					$query = "REPLACE INTO T_locales ( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name, loc_messages, loc_priority, loc_enabled )	VALUES
						( '$edit_locale', '{$locales[ $edit_locale ]['charset']}', '{$locales[ $edit_locale ]['datefmt']}', '{$locales[ $edit_locale ]['timefmt']}', '{$locales[ $edit_locale ]['name']}', '{$locales[ $edit_locale ]['messages']}', '{$locales[ $edit_locale ]['priority']}', '{$locales[ $edit_locale ]['enabled']}'),
						( '$lswitchwith', '{$locales[ $lswitchwith ]['charset']}', '{$locales[ $lswitchwith ]['datefmt']}', '{$locales[ $lswitchwith ]['timefmt']}', '{$locales[ $lswitchwith ]['name']}', '{$locales[ $lswitchwith ]['messages']}', '{$locales[ $lswitchwith ]['priority']}', '{$locales[ $lswitchwith ]['enabled']}')";
					$q = $DB->query( $query );

					$Messages->add( T_('Switched priorities.'), 'success' );
				}

			}
			break;
	}
	locale_overwritefromDB();
}


/**
 * Display page header:
 */
require dirname(__FILE__).'/_menutop.php';


// Check permission:
$current_User->check_perm( 'options', 'view', true );

// Begin payload block:
$AdminUI->disp_payload_begin();

require dirname(__FILE__).'/_set_regional.form.php';

// End payload block:
$AdminUI->disp_payload_end();

require dirname(__FILE__).'/_footer.php';

/*
 * $Log$
 * Revision 1.7  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.6  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.5  2005/10/28 02:37:37  blueyed
 * Normalized AbstractSettings API
 *
 * Revision 1.4  2005/10/15 22:23:40  blueyed
 * Beautified .po extraction
 *
 * Revision 1.3  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.2  2005/07/12 00:22:46  blueyed
 * Fixed minor eval() injection with register_globals on.
 *
 * Revision 1.1  2005/06/06 17:59:39  fplanque
 * user dialog enhancements
 *
 * Revision 1.92  2005/06/03 15:12:31  fplanque
 * error/info message cleanup
 *
 * Revision 1.91  2005/03/16 19:58:14  fplanque
 * small AdminUI cleanup tasks
 *
 * Revision 1.90  2005/03/15 19:19:46  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.89  2005/03/07 00:06:16  blueyed
 * admin UI refactoring, part three
 *
 * Revision 1.88  2005/03/04 18:40:26  fplanque
 * added Payload display wrappers to admin skin object
 *
 * Revision 1.87  2005/02/28 09:06:39  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.86  2005/02/27 20:34:49  blueyed
 * Admin UI refactoring
 *
 * Revision 1.85  2005/02/23 21:58:10  blueyed
 * fixed updating of locales
 *
 * Revision 1.84  2005/02/23 04:26:21  blueyed
 * moved global $start_of_week into $locales properties
 *
 * Revision 1.83  2005/02/21 00:34:36  blueyed
 * check for defined DB_USER!
 *
 * Revision 1.82  2004/12/17 20:38:51  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 */
?>