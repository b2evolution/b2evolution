<?php
/**
 * This is the footer include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage photoblog
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>

<!-- =================================== START OF FOOTER =================================== -->
<div id="pageFooter">

	<p class="baseline">
		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before'      => '',
					'after'       => ' | ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
			// Display a link to help page:
			$Blog->help_link( array(
					'before'      => ' ',
					'after'       => ' | ',
					'text'        => T_('Help'),
				) );
		?>

		<?php
		if( $Blog->get_setting( 'comments_latest' ) )
		{
		?>
		<a href="<?php $Blog->disp( 'lastcommentsurl', 'raw' ) ?>"><?php echo T_('Latest comments') ?></a> |
		<?php
		}
		if( $Blog->get_setting( 'feed_content' ) != 'none' )
		{
		?>
			<a href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>">RSS 2.0</a> /
			<a href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>"><?php echo T_('Atom Feed') ?></a> /
			<a href="http://webreference.fr/2006/08/30/rss_atom_xml" title="External - English"><?php echo T_('What is RSS?') ?></a>
			| <a href="http://b2evolution.net/" title="b2evolution: next generation blog software" target="_blank">Powered by b2evolution</a>
		<?php
		}
		?>
	</p>


	<p class="baseline">
		<?php
			// Display footer text (text can be edited in Blog Settings):
			$Blog->footer_text( array(
					'before'      => '',
					'after'       => ' | ',
				) );
		?>

		<?php display_param_link( $skin_links ) ?>

		<?php
			// Display additional credits:
 			// If you can add your own credits without removing the defaults, you'll be very cool :))
		 	// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			credits( array(
					'list_start'  => ' | ',
					'list_end'    => '',
					'separator'   => ' | ',
					'item_start'  => ' ',
					'item_end'    => ' ',
				) );
		?>
	</p>

</div>
