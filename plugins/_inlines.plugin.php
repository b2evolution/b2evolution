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

		$item_Blog = & $Item->get_Blog();

		if( ! $this->get_coll_setting( 'coll_use_for_posts', $item_Blog ) )
		{	// This plugin is disabled to use for posts:
			return false;
		}

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?><script type="text/javascript">
		//<![CDATA[
		var post_ID = <?php echo $Item->ID;?>;
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
			if( post_ID == 0 )
			{
				alert( evo_js_lang_alert_before_insert );
				return;
			}

			if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
			{ // tinyMCE plugin is active now, we should focus cursor to the edit area
				tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
				tinyMCE.execCommand( 'evo_view_edit_inline', false, tinyMCE.activeEditor.id );
			}
			else
			{
				openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="' + evo_js_lang_loading + '..."></span>',
						'80%', '', true, evo_js_lang_select_image_insert, '', true );

				jQuery.ajax( {
					type: 'POST',
					url: '<?php echo $this->get_htsrv_url( 'insert_inline', array( 'post_ID' => $Item->ID ), '&' ); ?>',
					success: function(result)
					{
						openModalWindow( result, '90%', '80%', true, 'Select image', '' );
					}
				} );
			}
		}
		//]]>
		</script><?php

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?><script type="text/javascript">inline_toolbar( '<?php echo TS_('Inlines').':'; ?>' );</script><?php

		return true;
	}


	function GetHtsrvMethods()
	{
		return array( 'insert_inline' );
	}


	function htsrv_insert_inline( $params )
	{
		global $UserSettings, $current_User, $adminskins_path, $AdminUI, $is_admin_page, $blog;

		$is_admin_page = true;
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();
		load_funcs( 'links/model/_link.funcs.php' );

		if( ! isset( $Item->ID ) )
		{
			return;
		}

		$ItemCache = & get_ItemCache();
		$edited_Item = & $ItemCache->get_by_ID( $Item->ID );

		if( empty( $blog ) )
		{
			$blog = $edited_Item->get_Blog()->ID;
		}

		if( isset( $GLOBALS['files_Module'] )
		&& $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item )
		&& $current_User->check_perm( 'files', 'view', false ) )
		{ // Files module is enabled, but in case of creating new posts we should show file attachments block only if user has all required permissions to attach files
			load_class( 'links/model/_linkitem.class.php', 'LinkItem' );
			global $LinkOwner; // Initialize this object as global because this is used in many link functions
			$LinkOwner = new LinkItem( $edited_Item );

			// Set a different dragand drop button ID
			global $dragdropbutton_ID, $fm_mode;
			$fm_mode = 'file_select';
			$dragdropbutton_ID = 'file-uploader-modal';
			$AdminUI->disp_view( 'links/views/_link_list.view.php' );
		}
	}
}
?>