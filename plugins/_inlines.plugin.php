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
	var $name = 'Inlines';
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

		$temp_ID = empty( $params['temp_ID'] ) ? NULL : $params['temp_ID'];

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

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

		?><script type="text/javascript">
		//<![CDATA[
		var target_ID = <?php echo empty( $target_ID ) ? 'undefined' : format_to_js( $target_ID );?>;
		var temp_ID = <?php echo empty( $temp_ID ) ? 'undefined' : format_to_js( $temp_ID );?>;
		var target_type = '<?php echo format_to_js( $params['target_type'] );?>';
		var inline_buttons = new Array();

		function inline_button( id, text, type, title, style )
		{
			this.id = id;       // used to name the toolbar button
			this.text = text;   // label on button
			this.type = type;   // type of inline
			this.title = title; // title
			this.style = style; // style on button
		}

		inline_buttons[inline_buttons.length] = new inline_button(
				'inline_image', 'image', 'image', '<?php echo TS_('inline image') ?>', ''
			);

		function inline_toolbar( title )
		{
			var r = '<?php echo format_to_js( $this->get_template( 'toolbar_title_before' ) ); ?>' + title + '<?php echo format_to_js( $this->get_template( 'toolbar_title_after' ) ); ?>'
				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_before' ) ); ?>';
			for( var i = 0; i < inline_buttons.length; i++ )
			{
				var button = inline_buttons[i];
				r += '<input type="button" id="' + button.id + '" title="' + button.title + '"'
					+ ( typeof( button.style ) != 'undefined' ? ' style="' + button.style + '"' : '' ) + ' class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="insert_inline|' + button.type + '" value="' + button.text + '" />';
			}
			r += '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ) ); ?>';

			jQuery( '.<?php echo $this->code ?>_toolbar' ).html( r );
		}

		function insert_inline( inlineType )
		{

			switch( target_type )
			{
				case 'Item':
					if( ! target_ID && ! temp_ID )
					{
						alert( evo_js_lang_alert_before_insert_item  );
						return false;
					}
					break;

				case 'Comment':
					if( ! target_ID )
					{
						alert( evo_js_lang_alert_before_insert_comment );
						return false;
					}
					break;

				case 'EmailCampaign':
					if( ! target_ID )
					{
						alert( evo_js_lang_alert_before_insert_emailcampaign );
						return false;
					}
					break;

				case 'Message':
					if( ! target_ID && ! temp_ID )
					{
						alert( evo_js_lang_alert_before_insert_message );
						return false;
					}
					break;
			}

			if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
			{	// tinyMCE plugin is active now, we should focus cursor to the edit area
				tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
				tinyMCE.execCommand( 'evo_view_edit_inline', false, tinyMCE.activeEditor.id );
			}
			else
			{
				<?php
				$insert_inline_params = array(
						'target_ID' => $target_ID,
						'target_type' => $params['target_type'],
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
				?>
				openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="' + evo_js_lang_loading + '..."></span>',
						'80%', '', true, evo_js_lang_select_image_insert, '', true );

				jQuery.ajax( {
					type: 'POST',
					url: '<?php echo $this->get_htsrv_url( 'insert_inline', $insert_inline_params, '&' ); ?>',
					success: function( result )
					{
						var param_target_type, param_target_ID;
						if( temp_ID == undefined )
						{
							param_target_type = target_type;
							param_target_ID = target_ID
						}
						else
						{
							param_target_type = 'temporary';
							param_target_ID = temp_ID;
						}
						openModalWindow( result, '90%', '80%', true, 'Select image', '', '', '', '', '', function() {
									evo_link_refresh_list( param_target_type, param_target_ID );
									evo_link_fix_wrapper_height();
								} );
					}
				} );
			}
		}
		//]]>
		</script><?php

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?>
		<script type="text/javascript">inline_toolbar( '<?php echo TS_('Inlines').':'; ?>' );</script>
		<?php

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