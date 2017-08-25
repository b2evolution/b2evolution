<?php
/**
 * This file implements the facebook like plugin
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory - Attila Simo
 *
 * @version $Id: _facebook.plugin.php 8856 2015-05-02 01:25:05Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Facebook Plugin
 *
 * This plugin displays
 */
class facebook_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	var $name;
	var $code = 'evo_facebook';
	var $priority = 20;
	var $version = '6.9.3';
	var $author = 'The b2evo Group';
	var $group = 'widget';
	var $subgroup = 'other';

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_( 'Facebook Widget' );
		$this->short_desc = T_('This widget displays Facebook Like and Share buttons.');
		$this->long_desc = $this->short_desc.' '.T_('Also shows how many users liked and shared the current page.');
	}


	/**
	 * Get definitions for widget editable params
	 *
	 * @see Plugin::get_widget_param_definitions()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		global $preview;
		$r = array_merge( array(
			'show_buttons' => array(
					'label' => T_('Buttons to show'),
					'type' => 'radio',
					'options' => array(
						array( 'like', /*TRANS: Facebook Like*/ T_('Like') ),
						array( 'share', /*TRANS: Facebook Share*/ T_('Share') ),
						array( 'both', T_('Both') )
					),
					'defaultvalue' => 'both',
					'note' => ''
				),
			'layout' => array(
				'label' => T_('Layout'),
				'type' => 'select',
				'options' => array(
						'standard' => T_('standard'),
						'box_count' => T_('box with count'),
						'button_count' => T_('button with count'),
						'button' => T_('button')
					),
				'defaultvalue' => 'standard',
				'note' => ''
				),
			'action' => array(
				'label' => T_('Action type'),
				'type' => 'radio',
				'options' => array(
					array( 'like', T_('Like') ),
					array( 'recommend', T_('Recommend') )
					),
				'defaultvalue' => 'like',
				'note' => '',
				),
			'button_size' => array(
				'label' => T_('Button size'),
				'type' => 'radio',
				'options' => array(
					array( 'small', T_('small') ),
					array( 'large', T_('large') ),
					),
				'defaultvalue' => 'small',
				'note' => ''
				),
			'show_friends' => array(
				'label' => T_('Show friend\'s faces'),
				'type' => 'checkbox',
				'defaultvalue' => true,
				'note' => T_('Show profile photos when 2 or more friends like this'),
				),
			) );

		return $r;
	}


	/**
	 * Event handler: Called at the beginning of the "Edit wdiget" form on back-office.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'ComponentWidget': the Widget which gets edited (by reference)
	 * @return boolean did we display something?
	 */
	function WidgetBeginSettingsForm( & $params )
	{
		?>
		<script type="text/javascript">
		jQuery( document ).ready( function() {
			var showButtons = jQuery( 'input[name$=show_buttons]' ),
					layoutSelect = jQuery( 'select[name$=layout]' ),
					actionType = jQuery( 'input[name$=action]' ),
					showFriends = jQuery( 'input[name$=show_friends]' );

			if( jQuery( 'input[name$=show_buttons]:checked' ).val() == 'share' )
			{
				if( layoutSelect.val() == 'standard' )
				{
					layoutSelect.val( 'box_count' );
				}
				jQuery( 'option:first', layoutSelect ).attr( 'disabled', true );
				actionType.closest( 'div.form-group' ).hide();
				showFriends.closest( 'div.form-group' ).hide();
			}

			showButtons.on( 'click', function() {
				switch( this.value )
				{
					case 'like':
					case 'both':
						jQuery( 'option:first', layoutSelect ).removeAttr( 'disabled' );
						actionType.closest( 'div.form-group' ).show();
						showFriends.closest( 'div.form-group' ).show();
						break;

					case 'share':
						if( layoutSelect.val() == 'standard' )
						{
							layoutSelect.val( 'box_count' );
						}
						jQuery( 'option:first', layoutSelect ).attr( 'disabled', true );
						actionType.closest( 'div.form-group' ).hide();
						showFriends.closest( 'div.form-group' ).hide();
						break;
				}
			} );
		} );
		</script>
		<?php
		return true; // Do nothing by default.
	}


	/**
	 * Event handler: SkinTag (widget)
	 *
	 * @param array Associative array of parameters.
	 * @return boolean did we display?
	 */
	function SkinTag( & $params )
	{
		/**
		 * Default params:
		 */
		$params = array_merge( array(
				// This is what will enclose the block in the skin:
				'block_start'       => '<div class="bSideItem">',
				'block_end'         => "</div>\n",
				// This is what will enclose the body:
				'block_body_start'  => '',
				'block_body_end'    => '',
			), $params );

		global $baseurlroot;

		$current_url = url_absolute( regenerate_url( '', '', '', '&' ), $baseurlroot );
		$show_buttons = $this->get_widget_setting( 'show_buttons', $params );
		$layout = $this->get_widget_setting( 'layout', $params );
		$action_type = $this->get_widget_setting( 'action', $params );
		$button_size = $this->get_widget_setting( 'button_size', $params );
		$show_friends = $this->get_widget_setting( 'show_friends', $params );
		$code = '';

		switch( $show_buttons )
		{
			case 'like':
			case 'both':
				$include_share = $show_buttons == 'both' ? 1 : 0;
				$button_sizes = array(
					'standard' => array(
						'like' => array(
							'small' => array(
								array(
									array( 450, 35 ),
									array( 450, 35 )
									),
								array(
									array( 450, 80 ),
									array( 450, 80 )
									)
							),
							'large' => array(
								array(
									array( 450, 35 ),
									array( 450, 35 )
									),
								array(
									array( 450, 80 ),
									array( 450, 80 )
								)
							)
						),
						'recommend' => array(
							'small' => array(
								array(
									array( 450, 35 ),
									array( 450, 35 )
									),
								array(
									array( 450, 80 ),
									array( 450, 80 )
									)
							),
							'large' => array(
								array(
									array( 450, 35 ),
									array( 450, 35 )
									),
								array(
									array( 450, 80 ),
									array( 450, 80 )
								)
							)
						)
					),
					'box_count' => array(
						'like' => array(
							'small' => array(
								array(
									array( 51, 65 ),
									array( 51, 65 )
									),
								array(
									array( 51, 65 ),
									array( 51, 65 )
									)
							),
							'large' => array(
								array(
									array( 63, 90 ),
									array( 63, 90 )
									),
								array(
									array( 63, 90 ),
									array( 63, 90 )
								)
							)
						),
						'recommend' => array(
							'small' => array(
								array(
									array( 95, 65 ),
									array( 95, 65 )
									),
								array(
									array( 95, 65 ),
									array( 95, 65 )
									)
							),
							'large' => array(
								array(
									array( 115, 90 ),
									array( 115, 90 )
									),
								array(
									array( 115, 90 ),
									array( 115, 90 )
								)
							)
						)
					),
					'button_count' => array(
						'like' => array(
							'small' => array(
								array(
									array( 79, 21 ),
									array( 124, 46 )
									),
								array(
									array( 79, 21 ),
									array( 124, 46 )
									)
							),
							'large' => array(
								array(
									array( 95, 21 ),
									array( 154, 46 )
									),
								array(
									array( 95, 21 ),
									array( 154, 46 )
								)
							)
						),
						'recommend' => array(
							'small' => array(
								array(
									array( 123, 21 ),
									array( 168, 46 )
									),
								array(
									array( 123, 21 ),
									array( 168, 46 )
									)
							),
							'large' => array(
								array(
									array( 147, 28 ),
									array( 206, 46 )
									),
								array(
									array( 147, 28 ),
									array( 206, 46 )
								)
							)
						)
					),
					'button' => array(
						'like' => array(
							'small' => array(
								array(
									array( 51, 65 ),
									array( 96, 65 )
									),
								array(
									array( 51, 65 ),
									array( 96, 65 )
									)
							),
							'large' => array(
								array(
									array( 63, 65 ),
									array( 122, 65 )
									),
								array(
									array( 63, 65 ),
									array( 122, 65 )
								)
							)
						),
						'recommend' => array(
							'small' => array(
								array(
									array( 95, 65 ),
									array( 140, 65 )
									),
								array(
									array( 95, 65 ),
									array( 140, 65 )
									)
							),
							'large' => array(
								array(
									array( 115, 65 ),
									array( 174, 65 )
									),
								array(
									array( 115, 65 ),
									array( 174, 65 )
								)
							)
						)
					)
				);

				$width = $button_sizes[$layout][$action_type][$button_size][$show_friends][$include_share][0];
				$height = $button_sizes[$layout][$action_type][$button_size][$show_friends][$include_share][1];
				$code = '<iframe src="https://www.facebook.com/plugins/like.php?href='.urlencode( $current_url )
						.'&amp;width='.$width.'&amp;layout='.$layout.'&amp;action='.$action_type.'&amp;size='.$button_size
						.'&amp;show_faces='.$show_friends.'&amp;share='.$include_share.'&amp;height='.$height.'"
						width="'.$width.'" height="'.$height.'" style="border:none;overflow:hidden"
						scrolling="no" frameborder="0" allowTransparency="true"></iframe>';

				break;

			case 'share':
				if( $layout == 'standard' ) $layout = 'box_count';
				$button_sizes = array(
					'box_count' => array(
						'small' => array( 59, 40 ),
						'large' => array( 73, 58 )
					),
					'button_count' => array(
						'small' => array( 88, 20 ),
						'large' => array( 106, 28 )
					),
					'button' => array(
						'small' => array( 59, 20 ),
						'large' => array( 73, 28 )
					)
				);

				$width = $button_sizes[$layout][$button_size][0];
				$height = $button_sizes[$layout][$button_size][1];
				$code = '<iframe src="https://www.facebook.com/plugins/share_button.php?href='.urlencode( $current_url )
						.'&amp;layout='.$layout.'&amp;size='.$button_size.'&amp;width='.$width.'&amp;height='.$height.'"
						width="'.$width.'" height="'.$height.'" style="border:none;overflow:hidden"
						scrolling="no" frameborder="0" allowTransparency="true"></iframe>';
				break;
		}

		echo $params['block_start'];
		echo $params['block_body_start'];

		echo $code;

		echo $params['block_body_end'];
		echo $params['block_end'];

		return true;
	}
}

?>