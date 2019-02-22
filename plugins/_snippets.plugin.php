<?php
/**
 * This file implements the Snippet Toolbar plugin for b2evolution
 *
 * This is Ron's remix!
 * Includes code from the WordPress team -
 *  http://sourceforge.net/project/memberlist.php?group_id=51422
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */
class snippets_plugin extends Plugin
{
	var $code = 'snippets';
	var $name = 'Snippets';
	var $priority = 60;
	var $version = '6.10.7';
	var $group = 'editor';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Toolbar with buttons to insert text snippets');
		$this->long_desc = T_('This plugin displays a toolbar with buttons which allow to quickly insert text snippets into items or comments.');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		return array_merge( parent::get_coll_setting_definitions( $params ), array(
			'snippets' => array(
				'label' => T_('Snippets'),
				'type' => 'array',
				'entries' => array(
					'title' => array(
						'label' => T_('Title'),
						'defaultvalue' => '',
					),
					'text' => array(
						'label' => T_('Text'),
						'type' => 'textarea',
						'defaultvalue' => '',
					),
					'min_user_level' => array(
						'label' => T_('Min user level'),
						'type' => 'integer',
						'defaultvalue' => 0,
						'valid_range'  => array(
							'min' => 0,
							'max' => 10,
						),
					),
				)
			),
		) );
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

		if( empty( $Item ) ||
		    ! ( $item_Blog = & $Item->get_Blog() ) ||
		    ! $this->get_coll_setting( 'coll_use_for_posts', $item_Blog ) )
		{	// This plugin cannot be used for current Item:
			return false;
		}

		$params['Blog'] = $item_Blog;

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

		if( empty( $Comment ) ||
		    ! ( $comment_Item = & $Comment->get_Item() ) ||
		    ! ( $item_Blog = & $comment_Item->get_Blog() ) ||
		    ! $this->get_coll_setting( 'coll_use_for_comments', $item_Blog ) )
		{	// This plugin cannot be used for current Comment:
			return false;
		}

		$params['Blog'] = $item_Blog;

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
		global $current_User;

		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		if( ! is_logged_in() )
		{	// Don't try to display any snippet because they are restricted by user level:
			return false;
		}

		// Get snippets for current Collection and current User:
		$snippets = $this->get_coll_setting( 'snippets', $params['Blog'] );
		if( is_array( $snippets ) && ! empty( $snippets ) )
		{
			foreach( $snippets as $s => $snippet )
			{
				if( $current_User->get( 'level' ) < $snippet['min_user_level'] )
				{	// Don't display the snippet because of min user level:
					unset( $snippets[ $s ] );
				}
			}
		}

		if( empty( $snippets ) )
		{	// No snippets for current user:
			return false;
		}

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?><script>
		//<![CDATA[
		var <?php echo $params['js_prefix']; ?>b2evo_snippets = new Array( <?php
		foreach( $snippets as $snippet )
		{
			echo '\''.format_to_js( $snippet['text'] ).'\',';
		} ?> );
		function <?php echo $params['js_prefix']; ?>b2evo_insert_snippet( canvas_field, i )
		{
			textarea_wrap_selection( canvas_field, <?php echo $params['js_prefix']; ?>b2evo_snippets[i], '', 0 );
		}
		//]]>
		</script><?php

		// Display toolbar with snippets:
		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_title_before' ).TS_('Snippets').': '.$this->get_template( 'toolbar_title_after' );
		echo $this->get_template( 'toolbar_group_before' );
		$snippet_index = 0; // Index of the snippets, Used in the js func b2evo_insert_snippet to get a snippet text:
		foreach( $snippets as $snippet )
		{
			echo '<input '.get_field_attribs_as_string( array(
					'type'      => 'button',
					'value'     => $snippet['title'],
					'data-func' => $params['js_prefix'].'b2evo_insert_snippet|'.$params['js_prefix'].'b2evoCanvas|'.$snippet_index,
					'class'     => $this->get_template( 'toolbar_button_class' ),
				) ).'/>';
			$snippet_index++;
		}
		echo $this->get_template( 'toolbar_group_after' );
		echo $this->get_template( 'toolbar_after' );

		return true;
	}
}
?>