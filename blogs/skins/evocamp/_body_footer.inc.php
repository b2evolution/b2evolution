<?php
/**
 * This is the BODY footer include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage kubrick
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>
<div id="footer">
	<p>
		<?php
		// Display a link to contact the owner of this blog (if owner accepts messages):
		$Blog->contact_link( array(
				'before'      => '',
				'after'       => ' | ',
				'text'   => T_('Contact'),
				'title'  => T_('Send a message to the owner of this blog...'),
			) );
		?>

		Powered by <a href="http://b2evolution.net/" title="b2evolution home" target="_blank">b2evolution</a>

		+ <a href="http://www.evocamp.com/" target="_blank">evoCamp skin</a>

		<br />

		Credits: <a href="http://www.solostream.com" target="_blank">Blog Design</a>
		<?php
		// Display additional credits (see /conf/):
		// If you can add your own credits without removing the defaults, you'll be very cool :))
		// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
		credits( array(
				'list_start'  => ' | ',
				'list_end'    => ' ',
				'separator'   => '|',
				'item_start'  => ' ',
				'item_end'    => ' ',
			) );
		?>
	</p>
</div>
</div>
</div>
