<?php
/**
 * This file implements the Auto Anchors plugin for b2evolution
 *
 * @author blueyed: Daniel HAHLER - {@link http://daniel.hahler.de/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * The Auto Anchors Plugin.
 *
 * It adds attribute "id" for header tags <h1-6> for auto anchor
 *
 * @package plugins
 */
class auto_anchors_plugin extends Plugin
{
	var $code = 'auto_anchors';
	var $name = 'Auto Anchors';
	var $priority = 33;
	var $version = '7.0.1';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'auto-anchors-plugin';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Automatic creating of anchors from header tags');
		$this->long_desc = T_('This renderer automatically append attribute "id" for header tags.');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params,
			array(
				'default_comment_rendering' => 'opt-in',
				'default_post_rendering' => 'opt-out'
			)
		);
		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Define here default message settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_msg_setting_definitions( & $params )
	{
		// set params to allow rendering for messages by default
		$default_params = array_merge( $params, array( 'default_msg_rendering' => 'opt-in' ) );
		return parent::get_msg_setting_definitions( $default_params );
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		// set params to allow rendering for emails by default:
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'opt-in' ) );
		return parent::get_email_setting_definitions( $default_params );
	}


	/**
	 * Define here default shared settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_shared_setting_definitions( & $params )
	{
		// set params to allow rendering for shared container widgets by default:
		$default_params = array_merge( $params, array( 'default_shared_rendering' => 'opt-in' ) );
		return parent::get_shared_setting_definitions( $default_params );
	}


	/**
	 * Perform rendering
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		// Load for replace_special_chars():
		load_funcs( 'locales/_charset.funcs.php' );

		// Replace content outside of <code></code>, <pre></pre> and markdown codeblocks:
		$content = replace_content_outcode( '#(<h([1-6])((?!\sid\s*=).)*?)>(.+?)(</h\2>)#i', array( $this, 'callback_auto_anchor' ), $content, 'replace_content_callback' );

		return true;
	}


	/**
	 * Callback function to generate anchor from header text
	 *
	 * @param array Match data
	 * @return string
	 */
	function callback_auto_anchor( $m )
	{
		// Remove all HMTL tags from header text:
		$anchor = utf8_strip_tags( $m[4] );

		// Convert special chars/umlauts to ASCII,
		// and replace all non-letter and non-digit chars to single char "-":
		$anchor = replace_special_chars( $anchor );

		// Make anchor lowercase:
		$anchor = utf8_strtolower( $anchor );

		return $m[1].( empty( $anchor ) ? '' : ' id="'.$anchor.'"' ).'>'.$m[4].$m[5];
	}
}

?>