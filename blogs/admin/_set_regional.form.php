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

param( 'locale', 'string', '' );

if( !empty($locale) && $action != 'extract' )
{
	param( 'template', 'string', '' );
	?>
	<form class="fform" method="post" action="b2options.php?tab=regional" name="createnew">
		<input type="hidden" name="notransext" value="<?php echo $notransext;?>" />
		<input type="hidden" name="action" value="<?php echo ( ($locale == '_new_') ? 'createlocale' : 'updatelocale' ) ?>" />
		<fieldset id="createnew">
		<legend><?php echo ( ($locale == '_new_') ? T_('Create new locale') : T_('Edit locale') ) ?></legend>

		<?php
		// read template

		if( isset($locales[$template]) )
		{
			$ltemplate = $locales[ $template ];
			$newlocale = $template;
		}
		elseif( $locale != '_new_' && isset($locales[ $locale ]) )
		{
			$ltemplate = $locales[ $locale ];
			$newlocale = $locale;
		}
		else
		{
			$newlocale = '';
		}

		if( $locale != '_new_' )
		{ // we need to remember this for updating locale
			echo '<input type="hidden" name="oldloc_locale" value="'.$newlocale.'" />';
		}
		form_text( 'newloc_locale', $newlocale, 20, T_('Locale'), sprintf(T_('The first two letters should be a <a %s>ISO 639 language code</a>. The last two letters should be a <a %s>ISO 3166 country code</a>.'), 'href="http://www.gnu.org/software/gettext/manual/html_chapter/gettext_15.html#SEC221"', 'href="http://www.gnu.org/software/gettext/manual/html_chapter/gettext_16.html#SEC222"'), 20 );
		form_checkbox( 'newloc_enabled', (isset($ltemplate['enabled']) && $ltemplate['enabled']), T_('Enabled'),	T_('Should this locale be available to users?') );
		form_text( 'newloc_name', (isset($ltemplate['name']) ? $ltemplate['name'] : ''), 40, T_('Name'),
			T_('name of the locale'), 40 );
		form_text( 'newloc_charset', (isset($ltemplate['charset']) ? $ltemplate['charset'] : ''), 20, T_('Charset'),
			sprintf( T_('the charset for this locale. see <a %s>the specs</a>'), 'href="link"'), 15 );
		form_text( 'newloc_datefmt', (isset($ltemplate['datefmt']) ? $ltemplate['datefmt'] : ''), 20, T_('Date format'),
			sprintf( T_('the date format, see <a %s>the specs</a>'), 'href="link"'), 10 );
		form_text( 'newloc_timefmt', (isset($ltemplate['timefmt']) ? $ltemplate['timefmt'] : ''), 20, T_('Time format'),
			sprintf( T_('the time format, see <a %s>the specs</a>'), 'href="link"'), 10 );
		form_text( 'newloc_messages', (isset($ltemplate['messages']) ? $ltemplate['messages'] : ''), 20, T_('Lang file'),
			T_('the message file being used, from <code>locales</code> subdirectory'), 20 );
		form_text( 'newloc_priority', (isset($ltemplate['priority']) ? $ltemplate['priority'] : ''), 3, T_('Priority'),
			T_('1 is highest. It\'s use is for locale autodetect (from HTTP_ACCEPT_LANGUAGE header), when we have more than locale with the requested language. It also affects the order in which locales are shown in dropdown boxes etc.'), 5 );


		// generate Javascript array of locales to warn in case of overwriting
		$l_warnfor = "'".implode("', '", array_keys($locales))."'";
		if( $locale != '_new_' )
		{ // remove the locale we want to edit from the generated array
			$l_warnfor = str_replace("'$newlocale'", "'thiswillneverevermatch'", $l_warnfor);
		}
		echo '
		<div class="input">
		<input type="submit" name="submit" value="'.( ($locale == '_new_') ? T_('Create') : T_('Update') ).'" class="search" onClick="var Locales = new Array('.$l_warnfor.'); while( Locales.length > 0 ){ check = Locales.shift(); if( document.createnew.newloc_locale.value == check ){ c = \''. /* TRANS: this is a Javascript string */ T_("This will replace locale \'%s\'. Ok?").'\'.replace(/%s/, check); return confirm( c )}};" />
		<input type="reset" value="'.format_to_output(T_('Reset'), 'formvalue').'" class="search" />
		</div>
		<div class="panelinfo">
			<p>'.sprintf(T_('We\'ll use the flag out of subdirectories from <code>%s</code>, where the filename is equal to the language part of the locale (characters 4-5; file extension is gif).'), '/'.$img_subdir.'/flags/').'</p>
		</div>
		</fieldset>
	</form>
	';
}
else
{ // show main form
	if( !$locales[$Settings->get('default_locale')]['enabled'] )
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
			form_text( 'newtime_difference', $Settings->get('time_difference'), 3, T_('Time difference'), sprintf( '['. T_('in hours'). '] '. T_('If you\'re not on the timezone of your server. Current server time is: %s.'), date_i18n( locale_timefmt(), $servertimenow ) ), 3 );
			form_select( 'newdefault_locale', $Settings->get('default_locale'), 'locale_options', T_('Default locale'), T_('Default locale. Overwritten from HTTP_ACCEPT_LANGUAGE header, user locale or blog locale (in this order).'));
			?>

		</fieldset>

		<fieldset>
		<legend><?php echo T_('Available locales'); ?></legend>

		<p class="center"><?php
		if( !$notransext )
		{
			echo '<a href="b2options.php?tab=regional&amp;notransext=1">' . T_('Hide translation info'), '</a>';
			$showtranslationpercentage = 1;
		}
		else
		{
			echo '<a href="b2options.php?tab=regional">' . T_('Show translation info'), '</a>';
			$showtranslationpercentage = 0;
		}
		?></p>

		<table class="thin" border="1">
		<tr>
			<th><?php echo T_('Locale') ?></th>
			<th><?php echo T_('Enabled') ?></th>
			<th><?php echo T_('Name') ?></th>
			<th><?php echo T_('Date fmt') ?></th>
			<th><?php echo T_('Time fmt') ?></th>
			<th><?php echo T_('Edit') ?></th>
			<?php if( $showtranslationpercentage )
			{
				?>
				<th><?php echo T_('Strings') ?></th>
				<th><?php echo T_('Translated') ?></th>
				<?php
				if( $current_User->check_perm( 'options', 'edit' ) && $allow_po_extraction )
				{ ?>
					<th><?php echo T_('Extract') ?></th>
					<?php
				}
			} ?>
		</tr>
		<?php
		$i = 0; // counter to distinguish POSTed locales later
		foreach( $locales as $lkey => $lval )
		{
			$i++;
			?>
			<tr style="text-align:center">
			<td style="text-align:left" title="<?php echo T_('Priority').': '.$locales[$lkey]['priority'].', '.T_('Charset').': '.$locales[$lkey]['charset'].', '.T_('Lang file').': '.$locales[$lkey]['messages'] ?>">
				<?php
				echo '<input type="hidden" name="loc_'.$i.'_locale" value="'.$lkey.'" />';
				locale_flag( $lkey );
				echo'
				<strong>';
				if( $current_User->check_perm( 'options', 'edit' ) )
				{
					echo '<a href="?tab=regional'.($notransext ? '&amp;notransext=1' : '').'&amp;locale='.$lkey.'" title="'.T_('Edit locale').'">';
				}
				echo $lkey;
				if( $current_User->check_perm( 'options', 'edit' ) )
				{
					echo '</a>';
				}
				echo '</strong>
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

			if( $current_User->check_perm( 'options', 'edit' ) )
			{
				echo '<td align="left">';
				if( $i > 1 )
				{ // show "move prio up"
					echo '<a href="?tab=regional'.($notransext ? '&amp;notransext=1' : '').'&amp;prioup='.$lkey.'"><img src="img/arrowup.png" border="0" alt="'.T_('up').'" title="'.T_('Move priority up').'" width="14" height="14" class="middle" /></a>';
				}
				else
				{
					echo '<img src="img/blank.gif" width="14" border="0" />';
				}

				if( $i < count($locales) )
				{ // show "move prio down"
					echo '<a href="?tab=regional'.($notransext ? '&amp;notransext=1' : '').'&amp;priodown='.$lkey.'"><img src="img/arrowdown.png" border="0" alt="'.T_('down').'" title="'.T_('Move priority down').'" width="14" height="14" class="middle" /></a>';
				}
				else
				{
					echo '<img src="img/blank.gif" width="14" border="0" />';
				}
				echo '
				<a href="?tab=regional'.($notransext ? '&amp;notransext=1' : '').'&amp;locale=_new_&amp;template='.$lkey.'" title="'.T_('Copy locale').'"><img src="img/copy.gif" width="13" height="13" class="middle" alt="'.T_('Copy').'" title="'.T_('Copy locale').'" /></a>

				<a href="?tab=regional'.($notransext ? '&amp;notransext=1' : '').'&amp;locale='.$lkey.'" title="'.T_('Edit locale').'"><img src="img/properties.png" width="18" height="13" alt="'.T_('Edit').'" title="'.T_('Edit locale').'" class="middle" /></a>
				';
				if( isset($lval[ 'fromdb' ]) )
				{ // allow to delete locales loaded from db
					$l_atleastonefromdb = 1;
					echo '<a href="?tab=regional'.($notransext ? '&amp;notransext=1' : '').'&amp;delete='.$lkey.'"><img src="img/xross.gif" height="13" width="13" class="middle" alt="'.T_('Reset').'" title="'.T_('Reset custom settings').'" /></a>';
				}
				echo '</td>';
			}

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
				}

				if( $allow_po_extraction  )
				{ // Translator options:
					if( is_file( $po_file ) )
					{
						echo "\n\t<td>".'[<a href="b2options.php?tab=regional&amp;action=extract&amp;locale='.$lkey.'" title="'.T_('Extract .po file into b2evo-format').'">'.T_('Extract').'</a>]</td>';
					}
				}
			} // show message file percentage/extraction

			echo '</tr>';
		}
		?>
		</table>
		<?php if( $current_User->check_perm( 'options', 'edit' ) )
		{
			?>
			<p class="center"><a href="b2options.php?tab=regional<?php if( $notransext ) echo '&amp;notransext=1'?>&amp;locale=_new_"><img src="img/new.gif" width="13" height="13" class="middle" alt="" /> <?php echo T_('Create new locale');?></a></p>
			<?php if( isset($l_atleastonefromdb) )
			{ ?>
				<p class="center"><a href="?tab=regional<?php if( $notransext ) echo '&amp;notransext=1'?>&amp;action=reset" onClick="return confirm('<?php echo T_('Are you sure you want to reset?');?>')"><img src="img/xross.gif" height="13" width="13" class="middle" alt="" /> <?php echo T_('Reset to defaults (delete database table)');?></a></p>
				<?php
			}
		}
		?>
	</fieldset>

	<?php if( $current_User->check_perm( 'options', 'edit' ) )
	{ ?>
	<fieldset>
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search" />
				<input type="reset" value="<?php echo T_('Reset') ?>" class="search" />
			</div>
		</fieldset>
	</fieldset>
	<?php } ?>
	</div>

</form>
<?php
}
?>
