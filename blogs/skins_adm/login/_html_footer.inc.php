<?php
/**
 * This is the footer file for login/registering services
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * Revision 1.9  2011/10/10 19:48:31  fplanque
 * i18n & login display cleaup
 *
 * Revision 1.8  2011/09/07 22:44:41  fplanque
 * UI cleanup
 *
 * Revision 1.6.6.1  2011/09/04 22:13:57  fplanque
 * copyright 2011
 *
 * Revision 1.6  2010/02/08 17:56:49  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.5  2009/03/08 23:58:00  fplanque
 * 2009
 *
 * Revision 1.4  2008/03/15 19:07:28  fplanque
 * no message
 *
 * Revision 1.3  2008/02/19 11:11:24  fplanque
 * no message
 *
 * Revision 1.2  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:18:44  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.5  2007/05/02 18:28:05  fplanque
 * copyright credits logo
 *
 * Revision 1.4  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.3  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.2  2006/04/19 20:13:51  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>
