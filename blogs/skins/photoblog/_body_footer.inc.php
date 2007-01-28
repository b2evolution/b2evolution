<?php
/**
 * This is the footer include template.
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage photoblog
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>

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
		?>

		<a href="<?php $Blog->disp( 'lastcommentsurl', 'raw' ) ?>"><?php echo T_('Latest comments') ?></a>
		|
		<a href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>">RSS 2.0</a> /
		<a href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>"><?php echo T_('Atom Feed') ?></a> /
		<a href="http://webreference.fr/2006/08/30/rss_atom_xml" title="External - English"><?php echo T_('What is RSS?') ?></a>
	</p>

</div>


<div id="pageFooter">

	<p class="baseline">
		Powered by <a href="http://b2evolution.net/" title="b2evolution home" target="_blank">b2evolution</a>
		|
		Skin by <a href="http://skinfaktory.com/" target="_blank">The Skin Faktory</a>
		|
		<?php
			// Display additional credits (see /conf/_advanced.php):
 			// If you can add your own credits without removing the defaults, you'll be very cool :))
		 	// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			display_list( $credit_links, T_('Credits').': ', ' ', '|', ' ', ' ' );
		?>
	</p>

</div>