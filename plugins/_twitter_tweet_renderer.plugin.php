<?php
/**
 * This file implements the Twitter Tweet renderer plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Twitter Tweet Renderer plugin.
 *
 * @package plugins
 */
class twitter_tweet_renderer_plugin extends Plugin
{
	var $code = 'embTweetRdr';
	var $name = 'Twitter Tweet renderer';
	var $priority = 60;
	var $version = '7.1.0';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'twitter-tweet-renderer-plugin';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = $this->T_('Automatically create embedded Tweets in posts.');
		$this->long_desc = $this->T_('This renderer automatically create embedded Tweets from Twitter URLs in your posts. You can customize how the Tweets will appear.');
	}


	/**
	 * Define here default custom settings that are to be made available
	 *     in the backoffice for collections, private messages and newsletters.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_custom_setting_definitions()}.
	 */
	function get_custom_setting_definitions( & $params )
	{
		return array(
			'conversation' => array(
					'label' => $this->T_('Conversations'),
					'type' => 'select',
					'options' => array(
						'all'  => $this->T_('All'),
						'none' => $this->T_('None')
					),
					'defaultvalue' => 'all',
				),
			'cards' => array(
					'label' => $this->T_('Cards'),
					'type' => 'select',
					'options' => array(
						'hidden'  => $this->T_('Hidden'),
						'visible' => $this->T_('Visible'),
					),
					'defaultvalue' => 'visible',
				),
			'width' => array(
					'label' => $this->T_( 'Width' ),
					'type' => 'integer',
					'note' => $this->T_('Leave empty for default'),
					'suffix' => ' px',
					'allow_empty' => true,
					'defaultvalue' => NULL,
				),
			'align' => array(
					'label' => $this->T_('Align'),
					'type' => 'select',
					'options' => array(
						''       => $this->T_('Auto'),
						'left'   => $this->T_('Left'),
						'right'  => $this->T_('Right'),
						'center' => $this->T_('Center'),
					),
					'defaultvalue' => 'auto',
				),
			'theme' => array(
					'label' => $this->T_('Theme'),
					'type' => 'select',
					'options' => array(
						'dark' => $this->T_('Dark'),
						'light' => $this->T_('Light'),
					),
					'defaultvalue' => 'light',
				),
			'link_color' => array(
					'label' => $this->T_('Link color'),
					'type' => 'color',
					'defaultvalue' => '#2b7bb9',
				),
		);
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

		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 * 							(Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];
		$params['check_code_block'] = false;
		$wrapper = '<span class="tweet" data-tweet-id="$2">$0</span>';

		// Wrap Twitter URLs (not within an HTML tag attribute) with marker element:
		$content = replace_content_outcode( '#<[^>]*".*https?\:\/\/twitter\.com\/(?:\w*)\/status\/(?:\d*)[^>]*">(*SKIP)(*F)|https?\:\/\/twitter\.com\/(\w*)\/status\/(\d*)#is', $wrapper, $content );

		return $content;
	}


	/**
	 * Event handler: Called when displaying an item/post's content as HTML.
	 *
	 * This is different from {@link RenderItemAsHtml()}, because it gets called
	 * on every display (while rendering gets cached).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}.
	 *   - 'Item': The {@link Item} that gets displayed (by reference).
	 *   - 'Comment': The {@link Comment} that gets displayed (by reference).
	 *   - 'Message': The {@link Message} that gets displayed (by reference).
	 *   - 'EmailCampaign': The {@link EmailCampaign} that gets displayed (by reference).
	 *   - 'Widget': The {@link Widget} that gets displayed (by reference).
	 *   - 'preview': Is this only a preview?
	 *   - 'dispmore': Does this include the "more" text (if available), which means "full post"?
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
	 * @return boolean Have we changed something?
	 */
	function DisplayItemAsHtml( & $params )
	{
		$marker = '<span class="tweet" data-tweet-id';

		if( strpos( $params['data'], $marker ) != false )
		{	// A Twitter URL marker was found:
			$current_Blog = $this->get_Blog_from_params( $params );
			$options = array(
					'conversation' => $this->get_coll_setting( 'conversation', $current_Blog ),
					'cards'        => $this->get_coll_setting( 'cards', $current_Blog ),
					'theme'        => $this->get_coll_setting( 'theme', $current_Blog ),
					'linkColor'    => $this->get_coll_setting( 'link_color', $current_Blog ),
				);

			if( ! empty( $this->get_coll_setting( 'align', $current_Blog ) ) )
			{
				$options['align'] = $this->get_coll_setting( 'align', $current_Blog );
			}

			if( ! empty( $this->get_coll_setting( 'width', $current_Blog ) ) )
			{
				$options['width'] = $this->get_coll_setting( 'width', $current_Blog );
			}

			$script = '<script>
window.twttr = ( function( d, s, id ) {
		var js, fjs = d.getElementsByTagName(s)[0],
			t = window.twttr || {};
		if ( d.getElementById( id ) ) return t;
		js = d.createElement( s );
		js.id = id;
		js.src = "https://platform.twitter.com/widgets.js";
		fjs.parentNode.insertBefore( js, fjs );

		t._e = [];
		t.ready = function( f ) {
			t._e.push(f);
		};

		return t;
	}( document, "script", "twitter-wjs" ) );

twttr.ready( function()
	{
		var tweets = jQuery( ".tweet" );
		var options = '.json_encode( $options ).'

		tweets.each( function( index, tweet ) {
				var tweet_id = tweet.dataset.tweetId;
				tweet.innerHTML = "";
				twttr.widgets.createTweet( tweet_id, tweet, options );
			} );
	} );
</script>';

			$params['data'] .= $script;
			return true;
		}

		return true;
	}
}
?>