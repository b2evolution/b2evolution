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
?>

<form class="fform" name="form" action="b2options.php" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tab" value="<?php echo $tab; ?>" />
	
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
	<a href="?tab=regional&amp;action=reset" onClick="return confirm(\''.T_('Are you sure you want to reset?').'\')"><img src="img/xross.gif" height="13" width="13" alt="'.T_('Reset to defaults').'" title="'.T_('Reset to defaults').'" border="0" /></a> '.T_('Reset to defaults').'!';
	?>
	</fieldset>

	<?php if( $current_User->check_perm( 'options', 'edit' ) )
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
