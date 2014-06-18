<?php
/**
 * This is the BODY footer include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage intense
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>

		<div class="footer">
			<div class="nav rounded">
				<div id="copyright">
					<?php
					// Display a link to contact the owner of this blog (if owner accepts messages):
					$Blog->contact_link( array(
							'before' => '',
							'after'  => ' &bull; ',
							'text'   => T_('Contact'),
							'title'  => T_('Send a message to the owner of this blog...'),
						) );
					// Display a link to help page:
					$Blog->help_link( array(
							'before'      => ' ',
							'after'       => ' &bull; ',
							'text'        => T_('Help'),
						) );
					?>

					<?php
					// Display footer text (text can be edited in Blog Settings):
					$Blog->footer_text( array(
							'before' => '',
							'after'  => '',
						) );
					?>

					<br/>

					<?php display_param_link(array( '' => array( 'http://themefolio.com/', array( array( 70, 'intense skin'),array( 84, 'themefolio skin'),array( 88, 'b2evolution skin'),array( 91, 'blog skin'),array( 96, 'blog theme'),array( 100, 'blog template'))))); ?>
					designed by <a href="http://saltedsugar.com/" target="_blank">Salted Sugar</a>

					<?php
					// Display additional credits:
					// If you can add your own credits without removing the defaults, you'll be very cool :))
					// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
					credits( array(
							'list_start' => '|',
							'list_end'   => ' ',
							'separator'  => '|',
							'item_start' => ' ',
							'item_end'   => ' ',
						) );
					?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------
?>