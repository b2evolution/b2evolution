<?php
/**
 * This file implements the settings page
 *
 * b2evolution - {@link http://b2evolution.net/}
 * This file built upon code from original b2 - {@link http://cafelog.com/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
require( dirname(__FILE__). '/_header.php' );
$admin_tab = 'options';
$admin_pagetitle = T_('Settings');

param( 'action', 'string' );
param( 'tab', 'string', 'general' );
param( 'prioup', 'string', '' );
param( 'priodown', 'string', '' );
param( 'delete', 'string', '' );

switch( $tab )
{
	case 'general':
		$admin_pagetitle .= ' :: '. T_('General');
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


if( in_array( $action, array('update', 'reset', 'updatelocale', 'createlocale', 'extract' ))
		|| !empty($prioup) || !empty($priodown) || !empty($delete)
	)
{
	// Check permission:
	$current_User->check_perm( 'options', 'edit', true );
	
	$status_update = array();
	
	// clear settings cache
	$cache_settings = '';
	
	switch( $tab )
	{
		case 'general':
			// UPDATE general settings:
			param( 'newposts_per_page', 'integer', true );
			param( 'newwhat_to_show', 'string', true );
			param( 'newarchive_mode', 'string', true );
			param( 'newautobr', 'integer', 0 );
			param( 'pref_newusers_canregister', 'integer', 0 );
			param( 'pref_newusers_grp_ID', 'integer', true );
			param( 'pref_newusers_level', 'integer', true );
			param( 'pref_links_extrapath', 'integer', 0 );
			param( 'pref_permalink_type', 'string', true );
	
			$query = "UPDATE $tablesettings
								SET posts_per_page = $newposts_per_page,
										what_to_show = '".$DB->escape($newwhat_to_show)."',
										archive_mode = '".$DB->escape($newarchive_mode)."',
										AutoBR = $newautobr,
										pref_newusers_canregister = $pref_newusers_canregister,
										pref_newusers_level = $pref_newusers_level,
										pref_newusers_grp_ID = $pref_newusers_grp_ID,
										pref_links_extrapath = $pref_links_extrapath,
										pref_permalink_type = '".$DB->escape($pref_permalink_type)."'";
			
			$q = $DB->query( $query );
			
			$status_update[] = T_('General settings updated.') . '<br />';
			break;


		case 'regional':
		switch( $action )
			{ // in case of regional actions
				// UPDATE regional settings
				case 'update':
					param( 'newdefault_locale', 'string', true);
					param( 'newtime_difference', 'integer', true );
					
					if( locale_updateDB() )
					{
						$status_update[] = T_('Regional settings updated.');
					}
					
					if( $newdefault_locale != $default_locale )
					{
						// set default locale
						$query = "UPDATE $tablesettings	SET
									default_locale = '$newdefault_locale',
									time_difference = $newtime_difference";
						$q = $DB->query($query);
						
						$default_locale = $newdefault_locale;
						
						$status_update[] = T_('New default locale set.');
					}
					break;
				
				// CREATE/EDIT locale
				case 'updatelocale':
				case 'createlocale':
					param( 'newloc_locale', 'string', true);
					if( empty($newloc_locale) )
					{
						$status_update[] = '<span class="error">'.T_('You must not create empty locales!').'</span>';
						break;
					}
					
					param( 'newloc_enabled', 'integer', 0);
					param( 'newloc_name', 'string', true);
					param( 'newloc_charset', 'string', true);
					param( 'newloc_datefmt', 'string', true);
					param( 'newloc_timefmt', 'string', true);
					param( 'newloc_messages', 'string', true);
					
					if( $action == 'updatelocale' )
					{
						param( 'oldloc_locale', 'string', true);
						
						$query = "SELECT loc_locale FROM $tablelocales WHERE loc_locale = '$oldloc_locale'";
						if( !$DB->get_var( $query ) )
						{ // old locale is not in DB yet. Insert and disable it.
							$query = "INSERT INTO $tablelocales
												( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name, loc_messages, loc_priority, loc_enabled )
												VALUES ( '$oldloc_locale',
												'{$locales[$oldloc_locale]['charset']}', '{$locales[$oldloc_locale]['datefmt']}',
												'{$locales[$oldloc_locale]['timefmt']}', '{$locales[$oldloc_locale]['name']}',
												'{$locales[$oldloc_locale]['messages']}', '{$locales[$oldloc_locale]['priority']}', 0)";
							$q = $DB->query($query);
							$status_update[] = sprintf(T_("Inserted locale '%s' into database."), $oldloc_locale);
						}
						
						if( $oldloc_locale != $newloc_locale )
						{ // locale key was renamed, we remember to create the new one
							$l_insertnew = 1;
						}
						else
						{	// update database
							$query = "UPDATE $tablelocales
												SET loc_locale = '$newloc_locale', loc_charset = '$newloc_charset', loc_datefmt = '$newloc_datefmt',
												loc_timefmt = '$newloc_timefmt', loc_name = '$newloc_name', loc_messages = '$newloc_messages',
												loc_enabled = '$newloc_enabled'
												WHERE loc_locale = '$oldloc_locale'";
							$q = $DB->query($query);
							$status_update[] = sprintf(T_("Updated locale '%s'."), $newloc_locale);
						}
					}
					
					if( $action == 'createlocale' || isset( $l_insertnew ) )
					{
						$query = "REPLACE INTO $tablelocales
											( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name, loc_messages, loc_priority, loc_enabled )
											VALUES ( '$newloc_locale', '$newloc_charset', '$newloc_datefmt',
											'$newloc_timefmt', '$newloc_name', '$newloc_messages', '1', '$newloc_enabled')";
						$q = $DB->query($query);
						if( mysql_affected_rows() == 1)
						{
							$status_update[] = sprintf(T_("Created locale '%s'."), $newloc_locale);
						}
						else
						{
							$status_update[] = sprintf(T_("Updated locale '%s'."), $newloc_locale);
						}
					}

					break;


				// RESET locales in DB
				case 'reset':
					// reload locales
					unset( $locales );
					include( dirname(__FILE__).'/'.$admin_dirout.'/'.$conf_subdir.'/_locales.php' );
					@include( dirname(__FILE__).'/'.$admin_dirout.'/'.$conf_subdir.'/_overrides_TEST.php' );
					
					// delete everything from locales table
					$query = "DELETE FROM $tablelocales WHERE 1";
					$q = $DB->query($query);
					
					// reset default_locale
					$query = "UPDATE $tablesettings SET default_locale = '$default_locale'";
					$q = $DB->query($query);
					
					$status_update[] = T_('Locales table deleted, defaults from <code>/conf/_locales.php</code> loaded.');
					break;
				
				// EXTRACT locale
				case 'extract':
					param( 'locale', 'string', true );
					// Get PO file for that locale:
					echo '<div class="panelinfo">';
					echo '<h3>Extracting language file for ', $locale, '...</h3>';
					$po_file = dirname(__FILE__).'/'.$core_dirout.'/'.$locales_subdir.'/'.$locales[$locale]['messages'].'/LC_MESSAGES/messages.po';
					if( ! is_file( $po_file ) )
					{
						echo '<p class="error">'.sprintf(T_('File <code>%s</code> not found.'), '/'.$locales_subdir.'/'.$locales[$locale]['messages'].'/LC_MESSAGES/messages.po').'</p>';
					}
					else
					{	// File exists:
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
						$trans = array();
						foreach ($lines as $line) 
						{
							// echo 'LINE:', $line, '<br />';
							if(trim($line) == '' )	
							{	// Blank line, go back to base status:
								if( $status == 't' )
								{	// ** End of a translation **:
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
										// $trans[] = "\n\t'".str_replace( "'", "\'", str_replace( '\"', '"', $msgid ))."' => '".str_replace( "'", "\'", str_replace( '\"', '"', $msgstr ))."',";
										// $trans[] = "\n\t\"$msgid\" => \"$msgstr\",";
										$trans[] = "\n\t'".str_replace( "'", "\'", str_replace( '\"', '"', $msgid ))."' => \"".str_replace( '$', '\$', $msgstr)."\",";
						
									}
								}
								$status = '-';
								$msgid = '';
								$msgstr = '';
								$sources = array();
							}
							elseif( ($status=='-') && preg_match( '#^msgid "(.*)"#', $line, $matches)) 
							{	// Encountered an original text
								$status = 'o';
								$msgid = $matches[1];
								// echo 'original: "', $msgid, '"<br />';
								$all++;
							}
							elseif( ($status=='o') && preg_match( '#^msgstr "(.*)"#', $line, $matches)) 
							{	// Encountered a translated text
								$status = 't';
								$msgstr = $matches[1];
								// echo 'translated: "', $msgstr, '"<br />';
							}
							elseif( preg_match( '#^"(.*)"#', $line, $matches)) 
							{	// Encountered a followup line
								if ($status=='o') 
									$msgid .= $matches[1];
								elseif ($status=='t')
									$msgstr .= $matches[1];
							}
							elseif( ($status=='-') && preg_match( '@^#:(.*)@', $line, $matches)) 
							{	// Encountered a source code location comment
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
						
						$outfile = dirname(__FILE__).'/'.$core_dirout.'/'.$locales_subdir.'/'.$locales[$locale]['messages'].'/_global.php';
						$fp = fopen( $outfile, 'w+' );
						fwrite( $fp, "<?php\n" );
						fwrite( $fp, "/*\n" );
						fwrite( $fp, " * Global lang file\n" );
						fwrite( $fp, " * This file was generated automatically from messages.po\n" );
						fwrite( $fp, " */\n" );
						fwrite( $fp, "\n\$trans['".$locales[$locale]['messages']."'] = array(" );
						// echo '<pre>';
						foreach( $trans as $line )
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
						$query = "DELETE FROM $tablelocales WHERE loc_locale = '$delete'";
						$q = $DB->query( $query );
						
						// reload locales
						unset( $locales );
						include( dirname(__FILE__).'/'.$admin_dirout.'/'.$conf_subdir.'/_locales.php' );
						@include( dirname(__FILE__).'/'.$admin_dirout.'/'.$conf_subdir.'/_overrides_TEST.php' );
						
						$status_update[] = sprintf(T_("Deleted locale '%s' from database."), $delete);
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
						{	// switch
							#echo 'Switching prio '.$locales[ $lswitchwith ]['priority'].' with '.$locales[ $lswitch ]['priority'].'<br />';
							$locales[ $lswitchwith ]['priority'] = $locales[ $lswitch ]['priority'];
							$locales[ $lswitch ]['priority'] = $i;
							
							$query = "REPLACE INTO $tablelocales ( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name, loc_messages, loc_priority, loc_enabled )	VALUES
								( '$lswitch', '{$locales[ $lswitch ]['charset']}', '{$locales[ $lswitch ]['datefmt']}', '{$locales[ $lswitch ]['timefmt']}', '{$locales[ $lswitch ]['name']}', '{$locales[ $lswitch ]['messages']}', '{$locales[ $lswitch ]['priority']}', '{$locales[ $lswitch ]['enabled']}'),
								( '$lswitchwith', '{$locales[ $lswitchwith ]['charset']}', '{$locales[ $lswitchwith ]['datefmt']}', '{$locales[ $lswitchwith ]['timefmt']}', '{$locales[ $lswitchwith ]['name']}', '{$locales[ $lswitchwith ]['messages']}', '{$locales[ $lswitchwith ]['priority']}', '{$locales[ $lswitchwith ]['enabled']}')";
							$q = $DB->query( $query );
							
							$status_update[] = T_('Switched priorities.');
						}
						
					}
					break;
			}
			locale_overwritefromDB();
			break;


		case 'plugins':
			// UPDATE plug-ins:
			break;
		
	}
	
	if( count($status_update) )
	{
		echo '<div class="panelinfo">';
		foreach( $status_update as $stmsg ) echo '<p>'.$stmsg.'</p>';
		echo '</div>';
	}
}

// Check permission:
$current_User->check_perm( 'options', 'view', true );
?>


	<div class="pt" >
		<ul class="tabs">
			<!-- Yes, this empty UL is needed! It's a DOUBLE hack for correct CSS display -->
		</ul>
		<div class="panelblocktabs">
			<ul class="tabs">
			<?php
				if( $tab == 'general' )
					echo '<li class="current">';
				else
					echo '<li>';
				echo '<a href="b2options.php">'. T_('General'). '</a></li>';
				
				if( $tab == 'regional' )
					echo '<li class="current">';
				else
					echo '<li>';
				echo '<a href="b2options.php?tab=regional">'. T_('Regional'). '</a></li>';
		
				if( $tab == 'plugins' )
					echo '<li class="current">';
				else
					echo '<li>';
				echo '<a href="b2options.php?tab=plugins">'. T_('Plug-ins'). '</a></li>';
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