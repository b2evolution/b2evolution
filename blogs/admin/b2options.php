<?php
/**
 * This file implements the UI controller for settings management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */

/**
 * Includes:
 */
require( dirname(__FILE__). '/_header.php' );
$admin_tab = 'options';
$admin_pagetitle = T_('Settings');

param( 'action', 'string' );
param( 'tab', 'string', 'general' );
param( 'prioup', 'string', '' );
param( 'priodown', 'string', '' );
param( 'delete', 'string', '' );
param( 'notransext', 'integer', 0 );

switch( $tab )
{
	case 'general':
		$admin_pagetitle .= ' :: '. T_('General');
		break;
	case 'files':
		$admin_pagetitle .= ' :: '. T_('Files');
		break;
	case 'regional':
		$admin_pagetitle .= ' :: '. T_('Regional');
		break;
	case 'plugins':
		$admin_pagetitle .= ' :: '. T_('Plug-ins');
		break;
}

require( dirname(__FILE__). '/_menutop.php' );
require( dirname(__FILE__). '/_menutop_end.php' );


if( in_array( $action, array('update', 'reset', 'updatelocale', 'createlocale', 'extract', 'install', 'uninstall' ))
		|| !empty($prioup) || !empty($priodown) || !empty($delete)
	)
{ // We have an action to do..
	// Check permission:
	$current_User->check_perm( 'options', 'edit', true );

	// clear settings cache
	$cache_settings = '';

	switch( $tab )
	{
		case 'general':
			// UPDATE general settings:

			param( 'default_blog_ID', 'integer', true );
			$Settings->set( 'default_blog_ID', $default_blog_ID );
			param( 'posts_per_page', 'integer', true );
			$Settings->set( 'posts_per_page', $posts_per_page );
			param( 'what_to_show', 'string', true );
			$Settings->set( 'what_to_show', $what_to_show );
			param( 'archive_mode', 'string', true );
			$Settings->set( 'archive_mode', $archive_mode );
			param( 'AutoBR', 'integer', 0 );
			$Settings->set( 'AutoBR', $AutoBR );
			param( 'newusers_canregister', 'integer', 0 );
			$Settings->set( 'newusers_canregister', $newusers_canregister );
			param( 'newusers_grp_ID', 'integer', true );
			$Settings->set( 'newusers_grp_ID', $newusers_grp_ID );
			param( 'newusers_level', 'integer', true );
			$Settings->set( 'newusers_level', $newusers_level );
			param( 'links_extrapath', 'integer', 0 );
			$Settings->set( 'links_extrapath', $links_extrapath );
			param( 'permalink_type', 'string', true );
			$Settings->set( 'permalink_type', $permalink_type );
			param( 'user_minpwdlen', 'integer', true );
			$Settings->set( 'user_minpwdlen', $user_minpwdlen );
			param( 'reloadpage_timeout', 'integer', true );
			$Settings->set( 'reloadpage_timeout', $reloadpage_timeout );

			if( $Settings->updateDB() )
			{
				$Messages->add( T_('General settings updated.'), 'note' );
			}

			break;


		case 'plugins':
			// UPDATE plug-ins:
			switch( $action )
			{
				case 'install':
					// Install plugin:
					param( 'plugin', 'string', true );
					echo '<div class="panelinfo">';
					echo '<h3>Installing '.$plugin.'...</h3>';
					$Plugins->install( $plugin );
					echo '</div>';
					break;

				case 'uninstall':
					// Uninstall plugin:
					param( 'plugin_ID', 'int', true );
					echo '<div class="panelinfo">';
					echo '<h3>Uninstalling plgin #'.$plugin_ID.'...</h3>';
					$Plugins->uninstall( $plugin_ID );
					echo '</div>';
					break;
			}
			break;


		case 'files':
			param( 'upload_enabled', 'integer', 0 );
			$Settings->set( 'upload_enable', $reloadpage_timeout );
			param( 'upload_realpath', 'string', true );
			$Settings->set( 'upload_realpath', $reloadpage_realpath );
			param( 'upload_url', 'string', true );
			$Settings->set( 'upload_url', $reloadpage_url );

			param( 'upload_allowedext', 'string', true );
			$Settings->set( 'upload_allowedext', trim($reloadpage_allowedext) );
			param( 'upload_maxkb', 'integer', 0 );
			$Settings->set( 'upload_maxkb', $reloadpage_maxkb );

			#param( 'upload_minlevel', 'integer', true );
			#$Settings->set( 'upload_minlevel', $reloadpage_minlevel );

			break;


		case 'regional':
		switch( $action )
		{ // in case of regional actions
			// UPDATE regional settings
			case 'update':
				param( 'newdefault_locale', 'string', true);
				param( 'newtime_difference', 'integer', true );

				locale_updateDB();
				$Settings->set( 'default_locale', $newdefault_locale );
				$Settings->set( 'time_difference', $newtime_difference );
				$Settings->updateDB();

				$Messages->add( T_('Regional settings updated.'), 'note' );
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
								$Messages->add( sprintf(T_('Deleted settings for locale [%s] in database.'), $oldloc_locale), 'note' );
							}
						}
					}
					else
					{ // old locale is not in DB yet. Insert it.
						$query = "INSERT INTO T_locales
											( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name, loc_messages, loc_priority, loc_enabled )
											VALUES ( '$oldloc_locale',
											'{$locales[$oldloc_locale]['charset']}', '{$locales[$oldloc_locale]['datefmt']}',
											'{$locales[$oldloc_locale]['timefmt']}', '{$locales[$oldloc_locale]['name']}',
											'{$locales[$oldloc_locale]['messages']}', '{$locales[$oldloc_locale]['priority']}',";
						if( $oldlocale != $newlocale )
						{ // disable old locale
							$query .= ' 0)';
							$Messages->add( sprintf(T_('Inserted (and disabled) locale [%s] into database.'), $oldloc_locale), 'note' );
						}
						else
						{ // keep old state
							$query .= ' '.$locales[$oldloc_locale]['enabled'].')';
							$Messages->add( sprintf(T_('Inserted locale [%s] into database.'), $oldloc_locale), 'note' );
						}
						$q = $DB->query($query);
					}
				}

				$query = "REPLACE INTO T_locales
									( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name, loc_messages, loc_priority, loc_enabled )
									VALUES ( '$newloc_locale', '$newloc_charset', '$newloc_datefmt',
									'$newloc_timefmt', '$newloc_name', '$newloc_messages', '1', '$newloc_enabled')";
				$q = $DB->query($query);
				$Messages->add( sprintf(T_('Saved locale [%s].'), $newloc_locale), 'note' );

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
				$query = 'DELETE FROM T_locales WHERE 1';
				$q = $DB->query($query);

				// reset default_locale
				$Settings->set( 'default_locale', $default_locale );
				$Settings->updateDB();

				$Messages->add( T_('Locales table deleted, defaults from <code>/conf/_locales.php</code> loaded.'), 'note' );
				break;


			// EXTRACT locale
			case 'extract':
				param( 'locale', 'string', true );
				// Get PO file for that locale:
				echo '<div class="panelinfo">';
				echo '<h3>Extracting language file for ', $locale, '...</h3>';
				$po_file = dirname(__FILE__).'/'.$core_dirout.$locales_subdir.$locales[$locale]['messages'].'/LC_MESSAGES/messages.po';
				if( ! is_file( $po_file ) )
				{
					echo '<p class="error">'.sprintf(T_('File <code>%s</code> not found.'), '/'.$locales_subdir.$locales[$locale]['messages'].'/LC_MESSAGES/messages.po').'</p>';
				}
				else
				{ // File exists:
					// Get PO file for that locale:
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
							$c = preg_match_all( '@ ../../../([^/:]*)@', $sourcefiles, $matches);
							for( $i = 0; $i < $c; $i++ )
							{
								$sources[] = $matches[1][$i];
							}
							// echo '<br />';
						}
						elseif(strpos($line,'#, fuzzy') === 0)
							$fuzzy++;
					}

					ksort( $loc_vars );
					foreach( $loc_vars as $source => $c )
					{
						echo $source, ' = ', $c, '<br />';
					}

					$outfile = dirname(__FILE__).'/'.$core_dirout.$locales_subdir.$locales[$locale]['messages'].'/_global.php';
					$fp = fopen( $outfile, 'w+' );
					fwrite( $fp, "<?php\n" );
					fwrite( $fp, "/*\n" );
					fwrite( $fp, " * Global lang file\n" );
					fwrite( $fp, " * This file was generated automatically from messages.po\n" );
					fwrite( $fp, " */\n" );
					fwrite( $fp, "\n\$trans['".$locales[$locale]['messages']."'] = array(" );
					// echo '<pre>';
					foreach( $ttrans as $line )
					{
						// echo htmlspecialchars( $line );
						fwrite( $fp, $line );
					}
					// echo '</pre>';
					fwrite( $fp, "\n);\n?>" );
					fclose( $fp );
				}
				echo '</div>';

				break;

			default:
				// --- DELETE locale from DB
				if( !empty($delete) )
				{
					$query = "DELETE FROM T_locales WHERE loc_locale = '$delete'";
					$q = $DB->query( $query );

					// reload locales
					unset( $locales );
					require(  dirname(__FILE__).'/'.$admin_dirout.$conf_subdir.'_locales.php' );
					@include( dirname(__FILE__).'/'.$admin_dirout.$conf_subdir.'_overrides_TEST.php' );

					$Messages->add( sprintf(T_('Deleted locale [%s] from database.'), $delete) );
				}

				// --- SWITCH PRIORITIES -----------------
				elseif( !empty($prioup) )
				{
					$switchcond = 'return ($lval[\'priority\'] > $i && $lval[\'priority\'] < $locales[ $prioup ][\'priority\']);';
					$i = -1;
					$lswitch = $prioup;
				}
				elseif( !empty($priodown) )
				{
					$switchcond = 'return ($lval[\'priority\'] < $i && $lval[\'priority\'] > $locales[ $priodown ][\'priority\']);';
					$i = 256;
					$lswitch = $priodown;
				}

				if( isset($switchcond) )
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
						$locales[ $lswitchwith ]['priority'] = $locales[ $lswitch ]['priority'];
						$locales[ $lswitch ]['priority'] = $i;

						$query = "REPLACE INTO T_locales ( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name, loc_messages, loc_priority, loc_enabled )	VALUES
							( '$lswitch', '{$locales[ $lswitch ]['charset']}', '{$locales[ $lswitch ]['datefmt']}', '{$locales[ $lswitch ]['timefmt']}', '{$locales[ $lswitch ]['name']}', '{$locales[ $lswitch ]['messages']}', '{$locales[ $lswitch ]['priority']}', '{$locales[ $lswitch ]['enabled']}'),
							( '$lswitchwith', '{$locales[ $lswitchwith ]['charset']}', '{$locales[ $lswitchwith ]['datefmt']}', '{$locales[ $lswitchwith ]['timefmt']}', '{$locales[ $lswitchwith ]['name']}', '{$locales[ $lswitchwith ]['messages']}', '{$locales[ $lswitchwith ]['priority']}', '{$locales[ $lswitchwith ]['enabled']}')";
						$q = $DB->query( $query );

						$Messages->add( T_('Switched priorities.'), 'note' );
					}

				}
				break;
			}
			locale_overwritefromDB();
			break;



	}

	if( $msg = $Messages->display( '', '', true, 'note', 'panelinfo', '<p>' ) );
}

// Check permission:
$current_User->check_perm( 'options', 'view', true );
?>


	<div class="pt" >
		<ul class="hack">
			<li><!-- Yes, this empty UL is needed! It's a DOUBLE hack for correct CSS display --></li>
		</ul>
		<div class="panelblocktabs">
			<ul class="tabs">
			<?php
				foreach( array(
									'general' => array( T_('General'), '' ),
									'files' => array( T_('Files'), '' ),
									'regional' => array( T_('Regional'), ( $notransext ? '&amp;notransext=1' : '' ) ),
									'plugins' => array( T_('Plug-ins'), '')
									) as $ltab => $lvalue )
				{
					echo $tab == $ltab ? '<li class="current">' : '<li>';
					echo '<a href="b2options.php?tab='.$ltab.$lvalue[1].'">'.$lvalue[0].'</a></li>';
				}
			?>
			</ul>
		</div>
	</div>
	<div class="tabbedpanelblock">
	<?php
		switch( $tab )
		{
			case 'general':
				// ---------- GENERAL OPTIONS ----------
				require_once dirname(__FILE__).'/_set_general.form.php';
				break;

			case 'files':
				// ---------- FILE MANAGEMENT OPTIONS ----------
				require_once dirname(__FILE__).'/_set_files.form.php';
				break;

			case 'regional':
				// ---------- REGIONAL OPTIONS ----------
				require_once dirname(__FILE__).'/_set_regional.form.php';
				break;

			case 'plugins':
				// ---------- PLUGIN OPTIONS ----------
				require_once dirname(__FILE__).'/_set_plugins.form.php';
				break;
		}
		?>
	</div>
<?php
	require( dirname(__FILE__). '/_footer.php' );
?>