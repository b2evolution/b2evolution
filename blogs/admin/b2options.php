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


if( $action == 'update' )
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
			// UPDATE regional settings
			
			param( 'newdefault_locale', 'string', true);
			param( 'newtime_difference', 'integer', true );
			
			$templocales = $locales;
			
			$lnr = 0;
			foreach( $_POST as $pkey => $pval ) if( preg_match('/loc_(\d+)_(.*)/', $pkey, $matches) )
			{
				$lfield = $matches[2];
				
				if( $matches[1] != $lnr )
				{ // we have a new locale
					$lnr = $matches[1];
					$plocale = $pval;
					
					// checkboxes default to 0
					$templocales[ $plocale ]['enabled'] = 0;
				}
				elseif( $lnr != 0 )  // be sure to have catched a locale before
				{
					$templocales[ $plocale ][$lfield] = remove_magic_quotes( $pval );
				}
				
			}
			
			if( $locales != $templocales )
			{
				#echo 'CHANGED!';
				$locales = $templocales;
			}
			
			$query = "REPLACE INTO $tablelocales ( loc_locale, loc_charset, loc_datefmt, loc_timefmt, loc_name, loc_messages, loc_enabled ) VALUES ";
			foreach( $locales as $localekey => $lval )
			{
				if( !isset($lval['messages']) )
				{ // if not explicit messages file is given we'll translate the locale
					$lval['messages'] = strtr($localekey, '-', '_');
				}
				$query .= "(
				'$localekey',
				'{$lval['charset']}',
				'{$lval['datefmt']}',
				'{$lval['timefmt']}',
				'{$lval['name']}',
				'{$lval['messages']}',
				'{$lval['enabled']}'
				), ";
			}
			$query = substr($query, 0, -2);
			$q = $DB->query($query);
			
			if( !$locales[$newdefault_locale]['enabled'] )
			{
				$status_update[] = '<span class="error">' . T_('Default locale should be enabled.') . '</span>';
			}
			elseif( $newdefault_locale != $default_locale )
			{
				// set default locale
				$query = "UPDATE $tablesettings	SET
							default_locale = '$newdefault_locale',
							time_difference = $newtime_difference";
				$q = $DB->query($query);
				$status_update[] = T_('New default locale set.');
			}
			
			$status_update[] = T_('Regional settings updated.');
		
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
elseif( $action == 'reset' && $tab == 'regional' )
{
	unset( $locales );
	include( dirname(__FILE__).'/'.$admin_dirout.'/'.$conf_subdir.'/_locales.php' );
	
	// delete everything from locales table
	$query = "DELETE FROM $tablelocales WHERE 1";
	$q = $DB->query($query);
	echo '<div class="panelinfo"><p>'.T_('Locales table deleted, defaults from <code>/conf/_locales.php</code> loaded.').'</p></div>';
	
	// reset default_locale
	$query = "UPDATE $tablesettings SET default_locale = '$default_locale'";
	$q = $DB->query($query);
	
	// clear settings cache
	$cache_settings = '';
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