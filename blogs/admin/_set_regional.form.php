<?php
/**
 * This file implements the UI view for the regional settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


if( $action == 'edit' )
{
	param( 'template', 'string', '' );

	$Form = & new Form( 'b2options.php?tab=regional', 'form' );

	$Form->begin_form( 'fform' );

	$Form->buttons( array( array( 'hidden', 'loc_transinfo', $loc_transinfo ),
												 array( 'hidden', 'action', ($edit_locale == '_new_') ? 'createlocale' : 'updatelocale' ) ) );

	$Form->fieldset( ($edit_locale == '_new_') ? T_('Create new locale') : T_('Edit locale'), 'createnew' );

		// read template

		if( isset($locales[$template]) )
		{
			$ltemplate = $locales[ $template ];
			$newlocale = $template;
		}
		elseif( $edit_locale != '_new_' && isset($locales[ $edit_locale ]) )
		{
			$ltemplate = $locales[ $edit_locale ];
			$newlocale = $edit_locale;
		}
		else
		{
			$newlocale = '';
		}

		if( $edit_locale != '_new_' )
		{ // we need to remember this for updating locale
			echo '<input type="hidden" name="oldloc_locale" value="'.$newlocale.'" />';
		}
		$Form->text( 'newloc_locale', $newlocale, 20, T_('Locale'), sprintf(T_('The first two letters should be a <a %s>ISO 639 language code</a>. The last two letters should be a <a %s>ISO 3166 country code</a>.'), 'href="http://www.gnu.org/software/gettext/manual/html_chapter/gettext_15.html#SEC221"', 'href="http://www.gnu.org/software/gettext/manual/html_chapter/gettext_16.html#SEC222"'), 20 );
		$Form->checkbox( 'newloc_enabled', (isset($ltemplate['enabled']) && $ltemplate['enabled']), T_('Enabled'),	T_('Should this locale be available to users?') );
		$Form->text( 'newloc_name', (isset($ltemplate['name']) ? $ltemplate['name'] : ''), 40, T_('Name'),
			T_('name of the locale'), 40 );
		$Form->text( 'newloc_charset', (isset($ltemplate['charset']) ? $ltemplate['charset'] : ''), 20, T_('Charset'), T_('Must match the lang file charset.'), 15 );
		$Form->text( 'newloc_datefmt', (isset($ltemplate['datefmt']) ? $ltemplate['datefmt'] : ''), 20, T_('Date format'), T_('See below.'), 10 );
		$Form->text( 'newloc_timefmt', (isset($ltemplate['timefmt']) ? $ltemplate['timefmt'] : ''), 20, T_('Time format'), T_('See below.'), 10 );
		$Form->dayOfWeek( 'newloc_startofweek', (isset($ltemplate['startofweek']) ? $ltemplate['startofweek'] : 0), T_('Start of week'), T_('Day at the start of the week.') );
		$Form->text( 'newloc_messages', (isset($ltemplate['messages']) ? $ltemplate['messages'] : ''), 20, T_('Lang file'),
			T_('the lang file to use, from the <code>locales</code> subdirectory'), 20 );
		$Form->text( 'newloc_priority', (isset($ltemplate['priority']) ? $ltemplate['priority'] : ''), 3, T_('Priority'),
			T_('1 is highest. Priority is important when selecting a locale from a language code and several locales match the same language; this can happen when detecting browser language. Priority also affects the order in which locales are displayed in dropdown boxes, etc.'), 5 );

		// generate Javascript array of locales to warn in case of overwriting
		$l_warnfor = "'".implode("', '", array_keys($locales))."'";
		if( $edit_locale != '_new_' )
		{ // remove the locale we want to edit from the generated array
			$l_warnfor = str_replace("'$newlocale'", "'thiswillneverevermatch'", $l_warnfor);
		}

		$Form->buttons( array( array( '', '', ($edit_locale == '_new_') ? T_('Create') : T_('Update'),
																	'search', 'var Locales = new Array('.$l_warnfor.'); while( Locales.length > 0 ){ check = Locales.shift(); if( document.createnew.newloc_locale.value == check ){ c = \''. /* TRANS: Warning this is a javascript string */ T_("This will replace locale \'%s\'. Ok?").'\'.replace(/%s/, check); return confirm( c )}};' ),
													 array( 'reset', '', format_to_output(T_('Reset'), 'formvalue'), 'search' ) ) );

		?>
		<div class="panelinfo">
			<h3><?php echo T_('Flags') ?></h3>
			<p><?php printf(T_('The flags are stored in subdirectories of <code>%s</code>. Their filename is equal to the country part of the locale (characters 4-5); file extension is .gif .'), '/'.$img_subdir.'flags/'); ?></p>
			<h3><?php echo T_('Date/Time Formats') ?></h3>
			<p><?php echo T_('The following characters are recognized in the format strings:') ?></p>
			<p>
			<?php echo T_('a - "am" or "pm"') ?><br />
			<?php echo T_('A - "AM" or "PM"') ?><br />
			<?php echo T_('B - Swatch Internet time') ?><br />
			<?php echo T_('c - ISO 8601 date (Requires PHP 5); i.e. "2004-02-12T15:19:21+00:00"') ?><br />
			<?php echo T_('d - day of the month, 2 digits with leading zeros; i.e. "01" to "31"') ?><br />
			<?php echo T_('D - day of the week, textual, 3 letters; i.e. "Fri"') ?><br />
			<?php echo T_('e - day of the week, 1 letter; i.e. "F"') ?><br />
			<?php echo T_('F - month, textual, long; i.e. "January"') ?><br />
			<?php echo T_('g - hour, 12-hour format without leading zeros; i.e. "1" to "12"') ?><br />
			<?php echo T_('G - hour, 24-hour format without leading zeros; i.e. "0" to "23"') ?><br />
			<?php echo T_('h - hour, 12-hour format; i.e. "01" to "12"') ?><br />
			<?php echo T_('H - hour, 24-hour format; i.e. "00" to "23"') ?><br />
			<?php echo T_('i - minutes; i.e. "00" to "59"') ?><br />
			<?php echo T_('I (capital i) - "1" if Daylight Savings Time, "0" otherwise.') ?><br />
			<?php echo T_('j - day of the month without leading zeros; i.e. "1" to "31"') ?><br />
			<?php echo T_('l (lowercase "L") - day of the week, textual, long; i.e. "Friday"') ?><br />
			<?php echo T_('L - boolean for whether it is a leap year; i.e. "0" or "1"') ?><br />
			<?php echo T_('m - month; i.e. "01" to "12"') ?><br />
			<?php echo T_('M - month, textual, 3 letters; i.e. "Jan"') ?><br />
			<?php echo T_('n - month without leading zeros; i.e. "1" to "12"') ?><br />
			<?php echo T_('O - Difference to Greenwich time (GMT) in hours; i.e. "+0200"') ?><br />
			<?php echo T_('r - RFC 822 formatted date; i.e. "Thu, 21 Dec 2000 16:01:07 +0200"') ?><br />
			<?php echo T_('s - seconds; i.e. "00" to "59"') ?><br />
			<?php echo T_('S - English ordinal suffix, textual, 2 characters; i.e. "th", "nd"') ?><br />
			<?php echo T_('t - number of days in the given month; i.e. "28" to "31"') ?><br />
			<?php echo T_('T - Timezone setting of this machine; i.e. "MDT"') ?><br />
			<?php echo T_('U - seconds since the epoch') ?><br />
			<?php echo T_('w - day of the week, numeric, i.e. "0" (Sunday) to "6" (Saturday)') ?><br />
			<?php echo T_('W - ISO-8601 week number of year, weeks starting on Monday; i.e. "42"') ?><br />
			<?php echo T_('Y - year, 4 digits; i.e. "1999"') ?><br />
			<?php echo T_('y - year, 2 digits; i.e. "99"') ?><br />
			<?php echo T_('z - day of the year; i.e. "0" to "365"') ?><br />
			<?php echo T_('Z - timezone offset in seconds (i.e. "-43200" to "43200"). The offset for timezones west of UTC is always negative, and for those east of UTC is always positive.') ?>
			</p>
			<?php echo T_('isoZ - full ISO 8601 format, equivalent to Y-m-d\TH:i:s\Z') ?><br />
			<p><?php echo T_('Unrecognized characters in the format string will be printed as-is.<br />
			You can escape characters by preceding them with a \ to print them as-is.') ?></p>
		</div>
		</fieldset>
	</form>
	<?php
}
else
{ // show main form
	if( !$locales[$Settings->get('default_locale')]['enabled'] )
	{ // default locale is not enabled
		echo '<div class="error">' . T_('Note: default locale is not enabled.') . '</div>';
	}

	$Form = & new Form( 'b2options.php?tab=regional', 'form' );

	$Form->begin_form( 'fform',T_('Regional Settings') );

	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'loc_transinfo', $loc_transinfo );

	$Form->fieldset( T_('Regional settings') );
	$Form->text( 'newtime_difference', $Settings->get('time_difference'), 3, T_('Time difference'), sprintf( '['. T_('in hours'). '] '. T_('If you\'re not on the timezone of your server. Current server time is: %s.'), date_i18n( locale_timefmt(), $servertimenow ) ), 3 );
	$Form->select( 'newdefault_locale', $Settings->get('default_locale'), 'locale_options_return', T_('Default locale'), T_('Overridden by browser config, user locale or blog locale (in this order).'));
	$Form->fieldset_end();

	$Form->fieldset( T_('Available locales') );

		?>

		<p class="center">

		<?php
		if( $loc_transinfo )
		{
			echo '<a href="b2options.php?tab=regional">' . T_('Hide translation info'), '</a>';
		}
		else
		{
			echo '<a href="b2options.php?tab=regional&amp;loc_transinfo=1">' . T_('Show translation info'), '</a>';
		}
		?>
		</p>

		<table class="grouped" cellspacing="0">
		<tr>
			<th><?php echo T_('Locale') ?></th>
			<th><?php echo T_('Enabled') ?></th>
			<th><?php echo T_('Name') ?></th>
			<th><?php echo T_('Date fmt') ?></th>
			<th><?php echo T_('Time fmt') ?></th>
			<th title="<?php echo T_('Day at the start of the week: 0 for Sunday, 1 for Monday, 2 for Tuesday, etc');
				?>"><?php echo T_('Start of week') ?></th>
			<?php if( $current_User->check_perm( 'options', 'edit' ) )
			{ ?>
				<th><?php echo T_('Edit') ?></th>
				<?php
			}
			if( $loc_transinfo )
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
			<tr <?php if($i%2 == 1) echo 'class="odd"' ?>>
			<td class="left" title="<?php echo T_('Priority').': '.$locales[$lkey]['priority'].', '.T_('Charset').': '.$locales[$lkey]['charset'].', '.T_('Lang file').': '.$locales[$lkey]['messages'] ?>">
				<?php
				echo '<input type="hidden" name="loc_'.$i.'_locale" value="'.$lkey.'" />';
				locale_flag( $lkey );
				echo'
				<strong>';
				if( $current_User->check_perm( 'options', 'edit' ) )
				{
					echo '<a href="?tab=regional'.($loc_transinfo ? '&amp;loc_transinfo=1' : '')
								.'&amp;action=edit&amp;edit_locale='.$lkey.'" title="'.T_('Edit locale').'">';
				}
				echo $lkey;
				if( $current_User->check_perm( 'options', 'edit' ) )
				{
					echo '</a>';
				}
				echo '</strong>
			</td>
			<td class="center">
				<input type="checkbox" name="loc_'.$i.'_enabled" value="1"'. ( $locales[$lkey]['enabled'] ? 'checked="checked"' : '' ).' />
			</td>
			<td>
				<input type="text" name="loc_'.$i.'_name" value="'.format_to_output( $locales[$lkey]['name'], 'formvalue' ).'" maxlength="40" size="17" />
			</td>
			<td>
				<input type="text" name="loc_'.$i.'_datefmt" value="'.format_to_output( $locales[$lkey]['datefmt'], 'formvalue' ).'" maxlength="10" size="6" />
			</td>
			<td>
				<input type="text" name="loc_'.$i.'_timefmt" value="'.format_to_output( $locales[$lkey]['timefmt'], 'formvalue' ).'" maxlength="10" size="6" />
			</td>
			<td>
				';
				// bad hack to avoid fieldstart/fieldend
				$old_fieldstart = $Form->fieldstart;
				$old_fieldend = $Form->fieldend;
				$Form->fieldstart = '';
				$Form->fieldend = '';
				$Form->dayOfWeek( 'loc_'.$i.'_startofweek', $locales[$lkey]['startofweek'], '', '' );
				$Form->fieldstart = $old_fieldstart;
				$Form->fieldend = $old_fieldend;
				echo '
			</td>';

			if( $current_User->check_perm( 'options', 'edit' ) )
			{
				echo '<td class="left">';
				if( $i > 1 )
				{ // show "move prio up"
					echo '<a href="?tab=regional'.($loc_transinfo ? '&amp;loc_transinfo=1' : '')
								.'&amp;edit_locale='.$lkey
								.'&amp;action=prioup"><img src="img/arrowup.png" alt="'.T_('up').'" title="'.T_('Move priority up').'" width="14" height="14" class="middle" /></a>';
				}
				else
				{
					echo '<img src="img/blank.gif" width="14" alt="" />';
				}

				if( $i < count($locales) )
				{ // show "move prio down"
					echo '<a href="?tab=regional'.($loc_transinfo ? '&amp;loc_transinfo=1' : '')
								.'&amp;edit_locale='.$lkey
								.'&amp;action=priodown"><img src="img/arrowdown.png" alt="'.T_('down').'" title="'.T_('Move priority down').'" width="14" height="14" class="middle" /></a>';
				}
				else
				{
					echo '<img src="img/blank.gif" width="14" alt="" />';
				}
				echo '
				<a href="?tab=regional'.($loc_transinfo ? '&amp;loc_transinfo=1' : '')
					.'&amp;action=edit&amp;edit_locale=_new_&amp;template='.$lkey.'" title="'.T_('Copy locale').'"><img src="img/copy.gif" width="13" height="13" class="middle" alt="'.T_('Copy').'" title="'.T_('Copy locale').'" /></a>

				<a href="?tab=regional'.($loc_transinfo ? '&amp;loc_transinfo=1' : '')
					.'&amp;action=edit&amp;edit_locale='.$lkey.'" title="'.T_('Edit locale').'"><img src="img/properties.png" width="18" height="13" alt="'.T_('Edit').'" title="'.T_('Edit locale').'" class="middle" /></a>
				';
				if( isset($lval[ 'fromdb' ]) )
				{ // allow to delete locales loaded from db
					$l_atleastonefromdb = 1;
					echo '<a href="?tab=regional'.($loc_transinfo ? '&amp;loc_transinfo=1' : '').'&amp;delete='.$lkey.'"><img src="img/xross.gif" height="13" width="13" class="middle" alt="'.T_('Reset').'" title="'.T_('Reset custom settings').'" /></a>';
				}
				echo '</td>';
			}

			if( $loc_transinfo )
			{
				// Get PO file for that locale:
				$po_file = dirname(__FILE__).'/'.$core_dirout.$locales_subdir.$locales[$lkey]['messages'].'/LC_MESSAGES/messages.po';
				if( ! is_file( $po_file ) )
				{
					echo '<td class="center" colspan="'.(2 + (int)$allow_po_extraction).'">'.T_('No language file...').'</td>';
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
					echo "\n\t<td class=\"center\">". $all ."</td>";
					$percent_done = round(($translated-$fuzzy/2)/$all*100);
					$color = sprintf( '%02x%02x00', 255 - round($percent_done * 2.55), round($percent_done * 2.55) );
					echo "\n\t<td class=\"center\" style=\"background-color:#". $color . "\">". $percent_done ." %</td>";
				}

				if( $current_User->check_perm( 'options', 'edit' ) && $allow_po_extraction  )
				{ // Translator options:
					if( is_file( $po_file ) )
					{
						echo "\n\t<td>".'[<a href="b2options.php?tab=regional&amp;action=extract&amp;edit_locale='.$lkey.($loc_transinfo ? '&amp;loc_transinfo=1' : '').'" title="'.T_('Extract .po file into b2evo-format').'">'.T_('Extract').'</a>]</td>';
					}
				}
			} // show message file percentage/extraction

			echo '</tr>';
		}
		?>
		</table>

		<?php
		if( $current_User->check_perm( 'options', 'edit' ) )
		{
			?>
			<p class="center"><a href="b2options.php?tab=regional&amp;action=edit<?php if( $loc_transinfo ) echo '&amp;loc_transinfo=1'?>&amp;edit_locale=_new_"><img src="img/new.gif" width="13" height="13" class="middle" alt="" /> <?php echo T_('Create new locale');?></a></p>
			<?php if( isset($l_atleastonefromdb) )
			{ ?>
				<p class="center"><a href="?tab=regional<?php if( $loc_transinfo ) echo '&amp;loc_transinfo=1'?>&amp;action=reset" onclick="return confirm('<?php echo /* TRANS: Warning this is a javascript string */ T_('Are you sure you want to reset?');?>')"><img src="img/xross.gif" height="13" width="13" class="middle" alt="" /> <?php echo T_('Reset to defaults (delete database table)');?></a></p>
				<?php
			}
		}

	$Form->fieldset_end();

	if( $current_User->check_perm( 'options', 'edit' ) )
	{
		$Form->end_form( array( array( 'submit', '', T_('Save !'), 'SaveButton' ),
														array( 'reset', '', T_('Reset'), 'ResetButton' ) ) ) ;
	}
}
?>