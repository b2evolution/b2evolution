<?php
/**
 * This is the footer file for login/registering services
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package htsrv
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>

</div>


<?php
  echo '<div class="form_footer_notes">'.sprintf( T_('Your IP address: %s'), $Hit->IP ).'</div>';

	// Please help us promote b2evolution and leave this logo on your blog:
	powered_by( array(
			'block_start' => '<div class="center" style="margin:1em">',
			'block_end'   => '</div>',
			// Check /rsc/img/ for other possible images -- Don't forget to change or remove width & height too
			'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
			'img_width'   => 120,
			'img_height'  => 32,
		) );
?>

<p class="footer"><?php echo $app_footer_text; ?></p>
<p class="footer"><?php echo $copyright_text; ?></p>
<p class="footer">
	<?php
		// Display additional credits:
 		// If you can add your own credits without removing the defaults, you'll be very cool :))
		// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
		credits( array(
				'list_start'  => T_('Credits').': ',
				'list_end'    => ' ',
				'separator'   => '|',
				'item_start'  => ' ',
				'item_end'    => ' ',
			) );
	?>
</p>

</body>
</html>
<?php
/*
 * $Log$
 * Revision 1.10  2013/11/06 08:05:53  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>