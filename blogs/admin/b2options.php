<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */
require( dirname(__FILE__). '/_header.php' );
$admin_tab = 'options';
$admin_pagetitle = T_('Options');
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
	
	switch( $tab ){
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
			
			$status_update[] = T_('General settings updated.<br />');
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
elseif( $action == 'reset' && $tab == 'locales' )
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

		<form class="fform" name="form" action="b2options.php" method="post">
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="tab" value="<?php echo $tab; ?>" />

		<?php
		switch( $tab )
		{
			// ---------- GENERAL OPTIONS ----------
			case 'general':?>
			<fieldset>
				<legend><?php echo T_('Default user rights') ?></legend>
				<?php
	
				form_checkbox( 'pref_newusers_canregister', get_settings('pref_newusers_canregister'), T_('New users can register'), sprintf( T_('Check to allow new users to register themselves.' ) ) );
	
				form_select_object( 'pref_newusers_grp_ID', get_settings('pref_newusers_grp_ID'), $GroupCache, T_('Group for new users'), T_('Groups determine user roles and permissions.') );
	
				form_text( 'pref_newusers_level', get_settings('pref_newusers_level'), 1, T_('Level for new users'), sprintf( T_('Levels determine hierarchy of users in blogs.' ) ), 1 );
				?>
			</fieldset>
	
			<fieldset>
				<legend><?php echo T_('Display options') ?></legend>
				<?php
					form_radio( 'newwhat_to_show', get_settings('what_to_show'),
							array(  array( 'days', T_('days') ),
											array( 'posts', T_('posts') ),
											array( 'paged', T_('posts paged') )
										), T_('Display mode') );
	
					form_text( 'newposts_per_page', get_settings('posts_per_page'), 4, T_('Posts/Days per page'), '', 4 );
	
					form_radio( 'newarchive_mode', get_settings('archive_mode'),
							array(  array( 'monthly', T_('monthly') ),
											array( 'weekly', T_('weekly') ),
											array( 'daily', T_('daily') ),
											array( 'postbypost', T_('post by post') )
										), T_('Archive mode') );
	
					form_checkbox( 'newautobr', get_settings('AutoBR'), T_('Auto-BR'), T_('This option is deprecated, you should avoid using it.') );
				?>
			</fieldset>
	
			<fieldset>
				<legend><?php echo T_('Link options') ?></legend>
				<?php
					form_checkbox( 'pref_links_extrapath', get_settings('pref_links_extrapath'), T_('Use extra-path info'), sprintf( T_('Recommended if your webserver supports it. Links will look like stub/2003/05/20/post_title instead of stub?title=post_title&c=1&tb=1&pb=1&more=1' ) ) );
	
					form_radio( 'pref_permalink_type', get_settings('pref_permalink_type'),
							array(  array( 'urltitle', T_('Post called up by its URL title (Recommended)') ),
											array( 'pid', T_('Post called up by its ID') ),
											array( 'archive#id', T_('Post on archive page, located by its ID') ),
											array( 'archive#title', T_('Post on archive page, located by its title (for Cafelog compatibility)') )
										), T_('Permalink type'), true );
				?>
			</fieldset>
	
				<?php
				break;
			
			// ---------- REGIONAL OPTIONS ----------
			case 'regional':?>
			
			<fieldset>
				<legend><?php echo T_('Regional settings') ?></legend>
	
				<?php
				form_text( 'newtime_difference', get_settings('time_difference'), 3, T_('Time difference'), sprintf( '['. T_('in hours'). '] '. T_('If you\'re not on the timezone of your server. Current server time is: %s.'), date_i18n( locale_timefmt(), $servertimenow ) ), 3 );
				form_select( 'newdefault_locale', get_settings('default_locale'), 'locale_options', T_('Default locale'), T_('Default locale used for backoffice and notification messages.'));
				?>
				
			</fieldset>
			
			<fieldset>
			<legend><?php echo T_('Available locales'); ?></legend>
			<table class="thin" border="1"><tr>
			<?php echo '<th>' . T_('locale') . '</th><th>' . T_('enabled')
				. '</th><th>' . T_('name') . '</th><th>' . T_('charset')
				. '</th><th>' . T_('date format') . '</th><th>' . T_('time<br /> format')
				. '</th><th>' . T_('messages') . '</th>
				</tr>';
			$i = 0; // counter to distinguish POSTed locales later, array trick (name="loc_enabled[]") fails for unchecked boxes
			foreach( $locales as $lkey => $lval )
			{
				$i++;
				echo '<tr>
				<td style="text-align:center"><input type="hidden" name="loc_'.$i.'_locale" value="'.$lkey.'" />
				<strong>'.$lkey.'</strong>
				</td><td style="text-align:center">
				<input type="checkbox" name="loc_'.$i.'_enabled" value="1"'. ( $locales[$lkey]['enabled'] ? 'checked="checked"' : '' ).' />
				'#<input type="text" name="loc_'.$i.'_locale" value="'.$lkey.'" />
				.'
				</td><td>
				<input type="text" name="loc_'.$i.'_name" value="'.$locales[$lkey]['name'].'" maxlength="40" />
				</td><td>
				<input type="text" name="loc_'.$i.'_charset" value="'.$locales[$lkey]['charset'].'" maxlength="15" />
				</td><td>
				<input type="text" name="loc_'.$i.'_datefmt" value="'.$locales[$lkey]['datefmt'].'" maxlength="10" size="10" />
				</td><td>
				<input type="text" name="loc_'.$i.'_timefmt" value="'.$locales[$lkey]['timefmt'].'" maxlength="10" size="10" />
				</td><td>
				<input type="text" name="loc_'.$i.'_messages" value="'.$locales[$lkey]['messages'].'" maxlength="10" size="10" />
				</td>
				';
				#form_text( 'loc_'.$key.'[]', $value, 20, $key, sprintf( T_('Levels determine hierarchy of users in blogs.' ) ), 1 );
				echo '</td></tr>';
			}
			echo '</table>
			<br />
			<div align="center">
			<a href="?tab=locales&amp;action=reset"><img src="img/xross.gif" height="13" width="13" alt="'.T_('Reset to defaults').'" title="'.T_('Reset to defaults').'" border="0" /></a>
			<br />'.T_('Reset to defaults').'!
			</div>';
			
			break;
			
			case 'plugins':
				// ---------- PLUGIN OPTIONS ----------
				// Note: tables will be different!
				?>
				<fieldset>
					<legend><?php echo T_('Rendering plug-ins') ?></legend>
					<table class="thin">
						<tr>
							<th><?php echo T_('Plug-in') ?></th>
							<th><?php echo T_('Apply') ?></th>
							<th><?php echo T_('Description') ?></th>
							<th><?php echo T_('Code') ?></th>
						</tr>
						<?php
						$Renderer->restart();	 // make sure iterator is at start position
						while( $loop_RendererPlugin = $Renderer->get_next() )
						{
						?>
						<tr>
							<td><?php	$loop_RendererPlugin->name(); ?></td>
							<td><?php	echo $loop_RendererPlugin->apply; ?></td>
							<td><?php	$loop_RendererPlugin->short_desc(); ?></td>
							<td><?php	$loop_RendererPlugin->code(); ?></td>
						</tr>
						<?php
						}
						?>
					</table>
				</fieldset>

				<fieldset>
					<legend><?php echo T_('Toolbar plug-ins') ?></legend>
					<table class="thin">
						<tr>
							<th><?php echo T_('Plug-in') ?></th>
							<th><?php echo T_('Description') ?></th>
							<th><?php echo T_('Code') ?></th>
						</tr>
						<?php
						$Toolbars->restart();	 // make sure iterator is at start position
						while( $loop_ToolbarPlugin = $Toolbars->get_next() )
						{
						?>
						<tr>
							<td><?php	$loop_ToolbarPlugin->name(); ?></td>
							<td><?php	$loop_ToolbarPlugin->short_desc(); ?></td>
							<td><?php	$loop_ToolbarPlugin->code(); ?></td>
						</tr>
						<?php
						}
						?>
					</table>
				</fieldset>
			<?php
			break;
		}
		
		if( $current_User->check_perm( 'options', 'edit' ) )
		{ ?>
		<fieldset>
			<fieldset>
				<div <?php echo ( $tab == 'regional' ) ? 'style="text-align:center"' : 'class="input"'?>>
					<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search">
					<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
				</div>
			</fieldset>
		</fieldset>
		<?php } ?>

		</form>
	</div>

	<?php

require( dirname(__FILE__). '/_footer.php' );

?>