<?php
/**
 * This file implements the UI controller for settings management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );


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
			param( 'newdefault_locale', 'string', true);
			$Settings->set( 'default_locale', $newdefault_locale );

			param( 'newtime_difference', 'string', '' );
			$newtime_difference = trim($newtime_difference);
			if( $newtime_difference == '' )
			{
				$newtime_difference = 0;
			}
			if( strpos($newtime_difference, ':') !== false )
			{ // hh:mm:ss format:
				$ntd = explode(':', $newtime_difference);
				if( count($ntd) > 3 )
				{
					param_error( 'newtime_difference', T_('Invalid time format.') );
				}
				else
				{
					$newtime_difference = $ntd[0]*3600 + ($ntd[1]*60);

					if( count($ntd) == 3 )
					{ // add seconds:
						$newtime_difference += $ntd[2];
					}
				}
			}
			else
			{ // just hours:
				$newtime_difference = $newtime_difference*3600;
			}

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
						if( $DB->rows_affected )
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

			$query = 'REPLACE INTO T_locales
								( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_startofweek, loc_name, loc_messages, loc_priority, loc_enabled )
								VALUES ( '.$DB->quote($newloc_locale).', '.$DB->quote($newloc_charset).', '.$DB->quote($newloc_datefmt).', '
									.$DB->quote($newloc_timefmt).', '.$DB->quote($newloc_startofweek).', '.$DB->quote($newloc_name).', '
									.$DB->quote($newloc_messages).', "1", '.$DB->quote($newloc_enabled).' )';
			$q = $DB->query($query);
			$Messages->add( sprintf(T_('Saved locale &laquo;%s&raquo;.'), $newloc_locale), 'success' );

			// reload locales: an existing one could have been renamed (but we keep $evo_charset, which may have changed)
			$old_evo_charset = $evo_charset;
			unset( $locales );
			include $conf_path.'_locales.php';
			if( file_exists($conf_path.'_overrides_TEST.php') )
			{ // also overwrite settings again:
				include $conf_path.'_overrides_TEST.php';
			}
			$evo_charset = $old_evo_charset;

			break;


		// RESET locales in DB
		case 'reset':
			// reload locales from files
			unset( $locales );
			include $conf_path.'_locales.php';
			if( file_exists($conf_path.'_overrides_TEST.php') )
			{ // also overwrite settings again:
				include $conf_path.'_overrides_TEST.php';
			}

			// delete everything from locales table
			$q = $DB->query( 'DELETE FROM T_locales WHERE 1=1' );

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

			$po_file = $locales_path.$locales[$edit_locale]['messages'].'/LC_MESSAGES/messages.po';
			if( ! is_file( $po_file ) )
			{
				$Messages->add( sprintf(T_('File <code>%s</code> not found.'), '/'.$locales_subdir.$locales[$edit_locale]['messages'].'/LC_MESSAGES/messages.po'), 'error' );
				break;
			}

			$outfile = $locales_path.$locales[$edit_locale]['messages'].'/_global.php';
			if( !is_writable($outfile) )
			{
				$Messages->add( sprintf( 'The file &laquo;%s&raquo; is not writable.', $outfile ) );
				break;
			}


			// File exists:
			// Get PO file for that edit_locale:
			$lines = file($po_file);
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
			require $conf_path.'_locales.php';
			if( file_exists($conf_path.'_overrides_TEST.php') )
			{ // also overwrite settings again:
				include $conf_path.'_overrides_TEST.php';
			}

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


/*
 * $Log$
 * Revision 1.1  2007/06/25 11:00:40  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.13  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.12  2007/03/12 14:09:25  waltercruz
 * Changing the WHERE 1 queries to boolean (WHERE 1=1) queries to satisfy the standarts
 *
 * Revision 1.11  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.10  2006/11/15 00:35:14  blueyed
 * Fix
 */
?>