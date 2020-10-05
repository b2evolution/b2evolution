<?php
/**
 * This file implements the Inlines Toolbar plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */
class inlines_plugin extends Plugin
{
	var $code = 'evo_inlines';
	var $name = 'Inline Short Tags';
	var $priority = 50;
	var $version = '6.9.4';
	var $group = 'editor';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Inline short tags inserting');
		$this->long_desc = T_('This plugin will display a toolbar with buttons to quickly insert inline short tags.');
	}


	/**
	 * Event handler: Called when displaying editor toolbars on post/item form.
	 *
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		$Item = & $params['Item'];

		if( empty( $Item ) )
		{
			return false;
		}

		$item_Blog = & $Item->get_Blog();

		if( ! $this->get_coll_setting( 'coll_use_for_posts', $item_Blog ) )
		{	// This plugin is disabled to use for posts:
			return false;
		}

		$params['target_type'] = 'Item';
		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		$Comment = & $params['Comment'];
		if( $Comment )
		{	// Get a post of the comment:
			$comment_Item = & $Comment->get_Item();
		}

		if( empty( $comment_Item ) )
		{
			return false;
		}

		$item_Blog = & $comment_Item->get_Blog();

		if( ! $this->get_coll_setting( 'coll_use_for_comments', $item_Blog ) )
		{	// This plugin is disabled to use for comments:
			return false;
		}

		$params['target_type'] = 'Comment';
		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars for email.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayEmailToolbar( & $params )
	{
		$params['target_type'] = 'EmailCampaign';
		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars for message.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayMessageToolbar( & $params )
	{
		$params['target_type'] = 'Message';
		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Display a code toolbar
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCodeToolbar( & $params )
	{
		global $Hit, $blog;

		if( $Hit->is_lynx() )
		{	// let's deactivate quicktags on Lynx, because they don't work there.
			return false;
		}

		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		$temp_ID = empty( $params['temp_ID'] ) ? NULL : $params['temp_ID'];

		// Load js to work with textarea
		require_js_defer( 'functions.js', 'blog', true );

		switch( $params['target_type'] )
		{
			case 'Item':
				$Item = & $params['Item'];
				$target_ID = $Item->ID;
				if( empty( $target_ID ) && empty( $temp_ID ) )
				{
					return false;
				}
				break;

			case 'Comment':
				$Comment = & $params['Comment'];
				$target_ID = $Comment->ID;
				if( empty( $target_ID ) )
				{
					return false;
				}
				break;

			case 'EmailCampaign':
				$EmailCampaign = & $params['EmailCampaign'];
				$target_ID = $EmailCampaign->ID;
				if( empty( $target_ID ) )
				{
					return false;
				}
				break;

			case 'Message':
				$Message = & $params['Message'];
				$target_ID = empty( $Message ) ? NULL : $Message->ID;
				if( empty( $target_ID ) && empty( $temp_ID ) )
				{
					return false;
				}
				break;

			default:
				return false;
		}

		$insert_inline_params = array(
			'target_ID'    => $target_ID,
			'target_type'  => $params['target_type'],
			'request_from' => is_admin_page() ? 'back' : 'front',
		);
		if( isset( $blog ) )
		{
			$insert_inline_params['blog'] = $blog;
		}
		if( isset( $temp_ID ) )
		{
			$insert_inline_params['temp_ID'] = $temp_ID;
		}

		$js_config = array(
			'prefix'               => $params['js_prefix'],
			'plugin_code'          => $this->code,

			'target_ID'            => empty( $target_ID ) ? NULL : format_to_js( $target_ID ),
			'temp_ID'              => empty( $temp_ID ) ? NULL : format_to_js( $temp_ID ),
			'target_type'          => format_to_js( $params['target_type'] ),

			'toolbar_title_before' => format_to_js( $this->get_template( 'toolbar_title_before' ) ),
			'toolbar_title_after'  => format_to_js( $this->get_template( 'toolbar_title_after' ) ),
			'toolbar_group_before' => format_to_js( $this->get_template( 'toolbar_group_before' ) ),
			'toolbar_group_after'  => format_to_js( $this->get_template( 'toolbar_group_after' ) ),
			'toolbar_title'        => T_('Inlines').': ',
			
			'button_title'         => T_('inline image'),
			'button_class'         => $this->get_template( 'toolbar_button_class' ),

			'insert_inline_url'    => $this->get_htsrv_url( 'insert_inline', $insert_inline_params, '&' ),
		);

		if( is_ajax_request() )
		{
			?>
			<script>
				jQuery( document ).ready( function() {
						window.evo_init_inlines_toolbar( <?php echo evo_json_encode( $js_config ); ?> );
					} );
			</script>
			<?php
		}
		else
		{
			expose_var_to_js( 'inlines_toolbar_'.$params['js_prefix'], $js_config, 'evo_init_inlines_toolbar_config' );
		}

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		
		return true;
	}


	function GetHtsrvMethods()
	{
		return array( 'insert_inline' );
	}


	/**
	 * Load insert image links
	 *
	 * @param array Params
	 */
	function htsrv_insert_inline( $params )
	{
		insert_image_links_block( $params );
	}
}
?>
