<?php
/**
 * This is the footer file for login/registering services
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>
	</div>
</div>

<?php
	echo '<div class="form_footer_notes">'
			.sprintf( T_('Your IP address: %s'), $Hit->IP ).'<br />'
			.T_('You will have to accept cookies in order to log in.')
		.'</div>';

	// Please help us promote b2evolution and leave this logo on your blog:
	powered_by( array(
			'block_start' => '<div class="center" style="margin:1em 1em .5ex">',
			'block_end'   => '</div>',
			// Check /rsc/img/ for other possible images -- Don't forget to change or remove width & height too
			'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
			'img_width'   => 120,
			'img_height'  => 32,
		) );
?>

<p class="footer"><?php echo $app_footer_text; ?></p>
<p class="footer"><?php echo $copyright_text; ?></p>

</body>
</html>