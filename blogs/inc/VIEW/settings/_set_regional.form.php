<?php
/**
 * This file implements the UI view for the regional settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

global $rsc_subdir, $pagenow, $locales_path, $locales, $action, $edit_locale, $loc_transinfo, $template, $allow_po_extraction, $servertimenow, $allow_po_extraction;


if( $action == 'edit' )
{
	param( 'template', 'string', ($edit_locale == '_new_') ? T_('Create new locale') : T_('Edit locale'), 'createnew' );

	$Form = & new Form( $pagenow, 'loc_checkchanges' );

	$Form->begin_form( 'fform', T_('Regional Settings') );

	$Form->hidden( 'ctrl', 'locales' );
	$Form->hidden( 'loc_transinfo', $loc_transinfo );
	$Form->hidden( 'action', ($edit_locale == '_new_') ? 'createlocale' : 'updatelocale' );

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

	$Form->end_form( array( array( 'submit', 'submit', ($edit_locale == '_new_') ? T_('Create') : T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

	?>
	<div class="panelinfo">
		<h3><?php echo T_('Flags') ?></h3>
		<p><?php printf(T_('The flags are stored in subdirectories of <code>%s</code>. Their filename is equal to the country part of the locale (characters 4-5); file extension is .gif .'), '/'.$rsc_subdir.'flags/'); ?></p>
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
<?php
}
else
{ // show main form
	if( !$locales[$Settings->get('default_locale')]['enabled'] )
	{ // default locale is not enabled
		echo '<div class="error">' . T_('Note: default locale is not enabled.') . '</div>';
	}

	$Form = & new Form( $pagenow, 'loc_checkchanges' );

	$Form->begin_form( 'fform', T_('Regional Settings') );

	$Form->hidden( 'ctrl', 'locales' );
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'loc_transinfo', $loc_transinfo );

	$Form->begin_fieldset( T_('Regional settings') );
	$Form->text_input( 'newtime_difference', $Settings->get('time_difference'), 3, T_('Time difference'),
				array( 'note'=>sprintf( '['. T_('in hours'). '] '. T_('If you\'re not on the timezone of your server. Current server time is: %s.'), date_i18n( locale_timefmt(), $servertimenow ) ), 'maxlength'=> 3, 'required'=>true ) );
	$Form->select( 'newdefault_locale', $Settings->get('default_locale'), 'locale_options_return', T_('Default locale'), T_('Overridden by browser config, user locale or blog locale (in this order).'));
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Available locales') );

	?>

	<p class="center">

	<?php
	if( $loc_transinfo )
	{
		echo '<a href="'.$pagenow.'?ctrl=locales">' . T_('Hide translation info'), '</a>';
	}
	else
	{
		echo '<a href="'.$pagenow.'?ctrl=locales&amp;loc_transinfo=1">' . T_('Show translation info'), '</a>';
	}
	?>
	</p>

	<table class="grouped" cellspacing="0">

	<tr>
		<th class="firstcol"><?php echo T_('Locale') ?></th>
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
			{
				echo '<th class="lastcol">'.T_('Extract').'</th>';
			}
		} ?>
	</tr>


	<?php
	$i = 0; // counter to distinguish POSTed locales later
	foreach( $locales as $lkey => $lval )
	{
		$i++;
		?>
		<tr class="<?php echo (($i%2 == 1) ? 'odd' : 'even') ?>">
		<td class="firstcol left" title="<?php echo T_('Priority').': '.$locales[$lkey]['priority'].', '.T_('Charset').': '.$locales[$lkey]['charset'].', '.T_('Lang file').': '.$locales[$lkey]['messages'] ?>">
			<?php
			echo '<input type="hidden" name="loc_'.$i.'_locale" value="'.$lkey.'" />';
			locale_flag( $lkey );
			echo'
			<strong>';
			if( $current_User->check_perm( 'options', 'edit' ) )
			{
				echo '<a href="'.$pagenow.'?ctrl=locales&amp;action=edit&amp;edit_locale='.$lkey.($loc_transinfo ? '&amp;loc_transinfo=1' : '').'" title="'.T_('Edit locale').'">';
			}
			echo $lkey;
			if( $current_User->check_perm( 'options', 'edit' ) )
			{
				echo '</a>';
			}
			echo '</strong></td>
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
				<td>';
			$Form->switch_layout( 'none' );
			$Form->dayOfWeek( 'loc_'.$i.'_startofweek', $locales[$lkey]['startofweek'], '', '' );
			$Form->switch_layout( NULL ); // Restore layout
			echo '</td>';

		if( $current_User->check_perm( 'options', 'edit' ) )
		{
			if( $loc_transinfo )
			{
				echo '<td class="shrinkwrap">';
			}
			else
			{
				echo '<td class="lastcol shrinkwrap">';
			}
			if( $i > 1 )
			{ // show "move prio up"
				echo action_icon( T_('Move priority up'), 'move_up', $pagenow.'?ctrl=locales&amp;action=prioup&amp;edit_locale='.$lkey.($loc_transinfo ? '&amp;loc_transinfo=1' : '') );
			}
			else
			{
				echo get_icon( 'nomove' ).' ';
			}

			if( $i < count($locales) )
			{ // show "move prio down"
				echo action_icon( T_('Move priority down'), 'move_down', $pagenow.'?ctrl=locales&amp;action=priodown&amp;edit_locale='.$lkey.($loc_transinfo ? '&amp;loc_transinfo=1' : '') );
			}
			else
			{
				echo get_icon( 'nomove' ).' ';
			}

			echo action_icon( T_('Copy locale'), 'copy', $pagenow.'?ctrl=locales&amp;action=edit&amp;edit_locale=_new_&amp;template='.$lkey.($loc_transinfo ? '&amp;loc_transinfo=1' : '' ) );

			echo action_icon( T_('Edit locale'), 'edit', $pagenow.'?ctrl=locales&amp;action=edit&amp;edit_locale=_new_&amp;template='.$lkey.($loc_transinfo ? '&amp;loc_transinfo=1' : '' ) );

			if( isset($lval[ 'fromdb' ]) )
			{ // allow to delete locales loaded from db
				$l_atleastonefromdb = 1;
				echo action_icon( T_('Reset custom settings'), 'delete', $pagenow.'?ctrl=locales&amp;action=deletelocale&amp;edit_locale='.$lkey.($loc_transinfo ? '&amp;loc_transinfo=1' : '' ) );
			}
			echo '</td>';
		}

		if( $loc_transinfo )
		{
			// Get PO file for that locale:
			$po_file = $locales_path.$locales[$lkey]['messages'].'/LC_MESSAGES/messages.po';
			if( ! is_file( $po_file ) )
			{
				echo '<td class="lastcol center" colspan="'.(2 + (int)($current_User->check_perm( 'options', 'edit' ) && $allow_po_extraction)).'">'.T_('No language file...').'</td>';
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
				$percent_done = ($all > 0) ? round(($translated-$fuzzy/2)/$all*100) : 0;
				$color = sprintf( '%02x%02x00', 255 - round($percent_done * 2.55), round($percent_done * 2.55) );
				echo "\n\t<td class=\"center\" style=\"background-color:#". $color . "\">". $percent_done ." %</td>";
			}

			if( $current_User->check_perm( 'options', 'edit' ) && $allow_po_extraction  )
			{ // Translator options:
				if( is_file( $po_file ) )
				{
					echo "\n\t".'<td class="lastcol">[<a href="'.$pagenow.'?ctrl=locales&amp;action=extract&amp;edit_locale='.$lkey.($loc_transinfo ? '&amp;loc_transinfo=1' : '').'" title="'.T_('Extract .po file into b2evo-format').'">'.T_('Extract').'</a>]</td>';
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
		echo '<p class="center"><a href="'.$pagenow.'?ctrl=locales&amp;action=edit'.( $loc_transinfo ? '&amp;loc_transinfo=1' : '' ).'&amp;edit_locale=_new_">'.get_icon( 'new' ).' '.T_('Create new locale').'</a></p>';

		if( isset($l_atleastonefromdb) )
		{
			echo '<p class="center"><a href="'.$pagenow.'?ctrl=locales&amp;action=reset'.( $loc_transinfo ? '&amp;loc_transinfo=1' : '' ).'" onclick="return confirm(\''.TS_('Are you sure you want to reset?').'\')">'.get_icon( 'delete' ).' '.T_('Reset to defaults (delete database table)').'</a></p>';
		}
	}

	$Form->end_fieldset();

	if( $current_User->check_perm( 'options', 'edit' ) )
	{
		$Form->end_form( array( array( 'submit', '', T_('Save !'), 'SaveButton' ),
														array( 'reset', '', T_('Reset'), 'ResetButton' ) ) ) ;
	}
}

/*
 * $Log$
 * Revision 1.5  2006/05/19 18:15:05  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.4.2.1  2006/05/19 15:06:25  fplanque
 * dirty sync
 *
 * Revision 1.4  2006/05/06 21:52:44  blueyed
 * trans
 *
 * Revision 1.3  2006/05/05 23:25:52  blueyed
 * fix
 *
 * Revision 1.2  2006/04/19 20:13:52  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>