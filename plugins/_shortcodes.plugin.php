<?php
/**
 * This file implements the Shortcodes Toolbar plugin for b2evolution
 *
 * This is Ron's remix!
 * Includes code from the WordPress team -
 *  http://sourceforge.net/project/memberlist.php?group_id=51422
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */
class shortcodes_plugin extends Plugin
{
	var $code = 'evo_shortcodes';
	var $name = 'Short Codes';
	var $priority = 40;
	var $version = '7.2.5';
	var $group = 'editor';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Short codes inserting');
		$this->long_desc = T_('This plugin will display a toolbar with buttons to quickly insert short codes.');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_comment_using' => 'disabled' ) );

		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars on post/item form.
	 *
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @todo dh> This seems to be a lot of Javascript. Please try exporting it in a
	 *       (dynamically created) .js src file. Then we could use cache headers
	 *       to let the browser cache it.
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		global $Hit;

		if( $Hit->is_lynx() )
		{ // let's deactivate quicktags on Lynx, because they don't work there.
			return false;
		}

		$Item = & $params['Item'];

		if( empty( $Item ) || $Item->is_intro() || ! $Item->get_type_setting( 'allow_breaks' ) )
		{	// Teaser and page breaks are not allowed for current item type and for all intro items:
			return false;
		}

		$item_Blog = & $Item->get_Blog();

		if( ! $this->get_coll_setting( 'coll_use_for_posts', $item_Blog ) )
		{	// This plugin is disabled to use for posts:
			return false;
		}

		// Load js to work with textarea
		require_js_defer( 'functions.js', 'blog', true );

		$js_config = array(
				'plugin_code' => $this->code,
				'js_prefix' => $params['js_prefix'],

				'btn_title_teaserbreak' => T_('Teaser break'),
				'btn_title_pagebreak'   => T_('Page break'),

				'toolbar_title_before' => $this->get_template( 'toolbar_title_before' ),
				'toolbar_title_after'  => $this->get_template( 'toolbar_title_after'),
				'toolbar_group_before' => $this->get_template( 'toolbar_group_before' ),
				'toolbar_group_after'  => $this->get_template( 'toolbar_group_after' ),
				'toolbar_button_class' => $this->get_template( 'toolbar_button_class' ),
				'toolbar_title'        => T_('Shortcodes'),
			);

		if( is_ajax_request() )
		{
			?>
			<script>
				jQuery( document ).ready( function() {
						window.evo_init_shortcodes_toolbar( <?php echo evo_json_encode( $js_config ); ?> );
					} );
			</script>
			<?php
		}
		else
		{
			expose_var_to_js( 'shortcodes_toolbar_'.$params['js_prefix'], $js_config, 'evo_init_shortcodes_toolbar_config' );
		}

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );

		return true;
	}
}

?>
