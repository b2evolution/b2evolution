<?php
/**
 * This file implements the general settings form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
param( 'notransext', 'int', 0 );
param( 'newtemplate', 'string', '' );

if( !$locales[$default_locale]['enabled'] )
{ // default locale is not enabled
	echo '<div class="error">' . T_('Note: default locale is not enabled.') . '</div>';
}
?>
<form class="fform" name="form" action="b2options.php?tab=regional" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="notransext" value="<?php echo $notransext;?>" />
	
	<fieldset>
		<legend><?php echo T_('Regional settings') ?></legend>

		<?php
		form_text( 'newtime_difference', get_settings('time_difference'), 3, T_('Time difference'), sprintf( '['. T_('in hours'). '] '. T_('If you\'re not on the timezone of your server. Current server time is: %s.'), date_i18n( locale_timefmt(), $servertimenow ) ), 3 );
		form_select( 'newdefault_locale', get_settings('default_locale'), 'locale_options', T_('Default locale'), T_('Default locale used for backoffice and notification messages.'));
		?>
		
	</fieldset>
	
	<fieldset>
	<legend><?php echo T_('Available locales'); ?></legend>
	
	<div style="text-align:center;padding:1em;">
	<a href="?tab=regional<?php
	if( !$notransext )
	{
		echo '&amp;notransext=1">' . T_('Hide translation percentages');
		$showtranslationpercentage = 1;
	}
	else
	{
		echo '">' . T_('Show translation percentages');
		$showtranslationpercentage = 0;
	}
	?></a>
	</div>
	
	<table class="thin" border="1">
	<tr>
		<th><?php echo T_('Locale') ?></th>
		<th><?php echo T_('Enabled') ?></th>
		<th><?php echo T_('Name') ?></th>
		<th><?php echo T_('Date fmt') ?></th>
		<th><?php echo T_('Time fmt') ?></th>
		<?php if( $showtranslationpercentage )
		{?>
		<th><?php echo T_('Strings') ?></th>
		<th><?php echo T_('Translated') ?></th>
		<?php if( $current_User->check_perm( 'options', 'edit' ) && $allow_po_extraction )
		{ ?>
		<th><?php echo T_('Extract') ?></th>
		<?php }
		} ?>
		<th></th>
	</tr>
	<?php
	$i = 0; // counter to distinguish POSTed locales later
	foreach( $locales as $lkey => $lval )
	{
		$i++;
		?>
		<tr style="text-align:center">
		<td style="text-align:left">
			<?php
			echo '<input type="hidden" name="loc_'.$i.'_locale" value="'.$lkey.'" />'
			#.$lval['priority'].'. '
			;
			locale_flag( $lkey );
			echo'
			<strong>'.$lkey.'</strong>
		</td>
		<td>
			<input type="checkbox" name="loc_'.$i.'_enabled" value="1"'. ( $locales[$lkey]['enabled'] ? 'checked="checked"' : '' ).' />
		</td>
		<td>
			<input type="text" name="loc_'.$i.'_name" value="'.$locales[$lkey]['name'].'" maxlength="40" size="17" />
		</td>
		<td>
			<input type="text" name="loc_'.$i.'_datefmt" value="'.$locales[$lkey]['datefmt'].'" maxlength="10" size="6" />
		</td>
		<td>
			<input type="text" name="loc_'.$i.'_timefmt" value="'.$locales[$lkey]['timefmt'].'" maxlength="10" size="6" />
		</td>';

		if( $showtranslationpercentage )
		{
			// Get PO file for that locale:
			$po_file = dirname(__FILE__).'/'.$core_dirout.'/'.$locales_subdir.'/'.$locales[$lkey]['messages'].'/LC_MESSAGES/messages.po';
			if( ! is_file( $po_file ) )
			{
				echo '<td colspan="'.(2 + (int)$allow_po_extraction).'">'.T_('No language file...').'</td>';
			}
			else
			{	// File exists:
				$lines = file( $po_file );
				$lines[] = '';	// Adds a blank line at the end in order to ensure complete handling of the file
				$all = 0;
				$fuzzy = 0;
				$this_fuzzy = false;
				$untranslated=0;
				$translated=0;
				$status='-';
				$matches = array();
				foreach ($lines as $line) 
				{
					// echo 'LINE:', $line, '<br />';
					if(trim($line) == '' )	
					{	// Blank line, go back to base status:
						if( $status == 't' )
						{	// ** End of a translation ** :
							if( $msgstr == '' )
							{
								$untranslated++;
								// echo 'untranslated: ', $msgid, '<br />';
							}
							else
							{
								$translated++;
							}
							if( $msgid == '' && $this_fuzzy )
							{	// It's OK if first line is fuzzy
								$fuzzy--;
							}
							$msgid = '';
							$msgstr = '';
							$this_fuzzy = false;
						}
						$status = '-';
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
					elseif(strpos($line,'#, fuzzy') === 0) 
					{
						$this_fuzzy = true;
						$fuzzy++;
					}
				}
				// $all=$translated+$fuzzy+$untranslated;
				echo "\n\t<td>". $all ."</td>";
				$percent_done = round(($translated-$fuzzy/2)/$all*100);
				$color = sprintf( '%02x%02x00', 255 - round($percent_done * 2.55), round($percent_done * 2.55) );
				echo "\n\t<td style=\"background-color:#". $color . "\">". $percent_done ." %</td>";
				if( $current_User->check_perm( 'options', 'edit' ) && $allow_po_extraction  )
				{ // Translator options:
					echo "\n\t<td>", '[<a href="b2options.php?tab=regional&amp;action=extract&amp;locale='.$lkey.'" title="'.T_('Extract .po file into b2evo-format').'">'.T_('Extract').'</a>]</td>';
				}
			}
		} // show message file percentage/extraction
		echo '
		<td align="left">
		';
		if( $i > 1 )
		{ // show "move prio up"
			echo '<a href="?tab=regional'.($notransext ? '&amp;notransext=1' : '').'&amp;prioup='.$lkey.'"><img src="img/arrowup.png" border="0" alt="'.T_('up').'" title="'.T_('Move priority up').'" /></a>';
		}
		else
		{
			echo '<img src="img/blank.gif" width="14" border="0" />';
		}

		if( $i < count($locales) )
		{ // show "move prio down"
			echo '<a href="?tab=regional'.($notransext ? '&amp;notransext=1' : '').'&amp;priodown='.$lkey.'"><img src="img/arrowdown.png" border="0" alt="'.T_('down').'" title="'.T_('Move priority down').'" /></a>';
		}
		else
		{
			echo '<img src="img/blank.gif" width="14" border="0" />';
		}
		echo '
		<a href="?tab=regional'.($notransext ? '&amp;notransext=1' : '').'&amp;newtemplate='.$lkey.'#createnew"><img src="img/new.png" border="0" alt="'.T_('new').'" title="'.T_('Use as template for &quot;Create New&quot; form').'" /></a>
		';
		if( isset($lval[ 'fromdb' ]) )
		{ // allow to delete locales loaded from db
			echo '<a href="?tab=regional'.($notransext ? '&amp;notransext=1' : '').'&amp;delete='.$lkey.'"><img src="img/xross.gif" height="13" width="13" border="0" alt="X" title="'.T_('Delete from DB!').'" /></a>';
		}
		echo '
		</tr>';
	}
	?>
	</table>
	<div style="text-align:center;padding:1em;">
	<a href="?tab=regional<?php if( $notransext ) echo '&amp;notransext=1'?>&amp;action=reset" onClick="return confirm('<?php echo T_('Are you sure you want to reset?');?>')"><img src="img/xross.gif" height="13" width="13" alt="X" title="<?php echo T_('Reset to defaults');?>" border="0" /></a>
	<br /><?php echo T_('Reset to defaults');?>!
	</div>
	<?php if( $current_User->check_perm( 'options', 'edit' ) )
	{
	?>
	<div style="text-align:center;padding-bottom:1em;">
		<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search">
		<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
	</div>
	</fieldset>
	</form>
	
	<fieldset style="text-align:center;" id="createnew">
		<legend><?php echo T_('Edit / Create new Locale') ?></legend>
		<p><?php echo T_('This form lets you edit an existing locale or create a new one, if the locale does not exist yet. Use the &quot;New&quot; icon next to the existing locales to use them as template here.')?></p>
		<form method="post" action="b2options.php?tab=regional" name="createnew">
			<input type="hidden" name="notransext" value="<?php echo $notransext;?>" />

			<?php
			// read template
			
			if( isset($locales[$newtemplate]) )
			{
				$ltemplate = $locales[ $newtemplate ];
			}?>
			<table>
			<tr>
			<th><?php echo T_('Locale') ?></th>
			<th><?php echo T_('Enabled') ?></th>
			<th><?php echo T_('Name') ?></th>
			<th><?php echo T_('Charset') ?></th>
			<th><?php echo T_('Date fmt') ?></th>
			<th><?php echo T_('Time fmt') ?></th>
			<th><?php echo T_('Lang file') ?></th>
			</tr><tr>
			<?php
			echo '
			<input type="hidden" name="action" value="createnew" />
			<td><input type="text" name="newloc_locale" value="'.$newtemplate.'" maxlength="20" size="6" /></td>
			<td><input type="checkbox" name="newloc_enabled" value="1"'.(isset($ltemplate['enabled']) && $ltemplate['enabled'] ? 'checked="checked"' : '').' /></td>
			<td><input type="text" name="newloc_name" value="'.(isset($ltemplate['name']) ? $ltemplate['name'] : '').'" maxlength="40" size="17" /></td>
			<td><input type="text" name="newloc_charset" value="'.(isset($ltemplate['charset']) ? $ltemplate['charset'] : '').'" maxlength="15" size="12" /></td>
			<td><input type="text" name="newloc_datefmt" value="'.(isset($ltemplate['datefmt']) ? $ltemplate['datefmt'] : '').'" maxlength="10" size="6" /></td>
			<td><input type="text" name="newloc_timefmt" value="'.(isset($ltemplate['timefmt']) ? $ltemplate['timefmt'] : '').'" maxlength="10" size="6" /></td>
			<td><input type="text" name="newloc_messages" value="'.(isset($ltemplate['messages']) ? $ltemplate['messages'] : '').'" maxlength="10" size="6" /></td>
			<td><input type="submit" name="submit" value="'.T_('Create').'" class="search" onClick="var Locales = new Array(\''.implode("', '", array_keys($locales)).'\'); while( Locales.length > 0 ){ check = Locales.shift(); if( document.createnew.newloc_locale.value == check ){ c = \''. /* TRANS: this is a Javascript string */ T_("This will replace locale \'%s\'. Ok?").'\'.replace(/%s/, check); return confirm( c )}};"></td>
			
			</tr>
			</table>
			
			<p>'.sprintf(T_('We\'ll use the flag out of subdirectories from <code>%s</code>, where the filename is equal to the language part of the locale (characters 4-5; file extension is gif).'), '/'.$img_subdir.'/flags/').'</p>
			
		</form>
	</fieldset>';
	
	}?>
</form>
