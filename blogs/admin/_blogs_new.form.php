<?php
/**
 * New blog form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
?>
<form class="fform" method="post">
	<input type="hidden" name="action" value="create" />
	<input type="hidden" name="blog" value="<?php echo $blog; ?>" />

	<?php require( dirname(__FILE__) . '/_blogs_main.subform.php' ); ?>
		
	<fieldset>
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Create new blog!') ?>" class="search">
				<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
			</div>
		</fieldset>
	</fieldset>

	<div class="clear"></div>
</form>
