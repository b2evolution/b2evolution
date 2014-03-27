<?php
/**
 * This file implements the Markdown plugin for b2evolution
 *
 * Markdown formatting
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 * @ignore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class markdown_plugin extends Plugin
{
	var $code = 'b2evMark';
	var $name = 'Markdown formatting';
	var $priority = 25;
	var $version = '5.0.0';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'markdown-plugin';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		require_once( dirname( __FILE__ ).'/Parsedown.php' );

		$this->short_desc = T_('Markdown formatting');
		$this->long_desc = T_(
						'Accepted formats:<br />' .
						'# h1 #<br />' .
						'## h2 ##<br />' .
						'### h3 ###<br />' .
						'#### h4 ####<br />' .
						'##### h5 #####<br />' .
						'###### h6 ######<br />' .
						'--- (horizontal rule)<br />' .
						'* * * (horizontal rule)<br />' .
						'- - - - (horizontal rule)<br />' .
						'`code spans`<br />' .
						'> blockquote');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array(
				'default_comment_rendering' => 'never',
				'default_post_rendering' => 'opt-out'
			) );

		return array_merge( parent::get_coll_setting_definitions( $default_params ),
			array(
				'links' => array(
						'label' => T_( 'Links' ),
						'type' => 'checkbox',
						'note' => T_( 'Create the links.' ),
						'defaultvalue' => 0,
					),
				'images' => array(
						'label' => T_( 'Images' ),
						'type' => 'checkbox',
						'note' => T_( 'Create the images.' ),
						'defaultvalue' => 0,
					),
				'text_styles' => array(
						'label' => T_( 'Italic & Bold styles' ),
						'type' => 'checkbox',
						'note' => T_( 'Create bold and italic styles for text formatting.' ),
						'defaultvalue' => 0,
					),
			)
		);
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 *   'data': the data (by reference). You probably want to modify this.
	 *   'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		if( !empty( $params['Item'] ) )
		{ // Get Item from params
			$Item = & $params['Item'];
		}
		elseif( !empty( $params['Comment'] ) )
		{ // Get Item from Comment
			$Comment = & $params['Comment'];
			$Item = & $Comment->get_Item();
		}
		else
		{ // Item and Comment are not defined, Exit here
			return;
		}
		$item_Blog = & $Item->get_Blog();

		// Init parser class with blog settings
		$Parsedown = Parsedown::instance();
		$Parsedown->parse_font_styles = $this->get_coll_setting( 'text_styles', $item_Blog );
		$Parsedown->parse_links = $this->get_coll_setting( 'links', $item_Blog );
		$Parsedown->parse_images = $this->get_coll_setting( 'images', $item_Blog );

		// Parse markdown code to HTML
		if( stristr( $content, '<code' ) !== false || stristr( $content, '<pre' ) !== false )
		{ // Call replace_content() on everything outside code/pre:
			$content = callback_on_non_matching_blocks( $content,
				'~<(code|pre)[^>]*>.*?</\1>~is',
				array( $Parsedown, 'parse' ) );
		}
		else
		{ // No code/pre blocks, replace on the whole thing
			$content = $Parsedown->parse( $content );
		}

		return true;
	}
}

?>