<?php
/**
 * This file implements the item_price Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author erhsatingin: Erwin Rommel Satingin.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class item_price_Widget extends ComponentWidget
{
	var $icon = 'money';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_price' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-price-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Price');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Price') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display the price of the item.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $Blog;

		$CurrencyCache = & get_currencyCache();
		$currency_options = $CurrencyCache->get_option_array();

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Title'),
					'size' => 40,
					'note' => T_('This is the title to display'),
					'defaultvalue' => '',
				),
				'currency_ID' => array(
					'label' => T_('Currency'),
					'type' => 'select',
					'options' => $currency_options,
					'defaultvalue' => locale_currency( '#', 'ID' ),
				),
				'display_original_price' => array(
					'label' => T_('Display original price'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
					'note' => T_('If user is awarded a reduced price, the original price will be displayed with strikethrough style.'),
				)
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget displays dynamic data:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item, $disp;

		if( empty( $Item ) )
		{ // Don't display this widget when there is no Item object:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item.' );
			return false;
		}

		$this->init_display( $params );

		$this->disp_params = array_merge( array(
				'widget_item_price_before_default' => '<div class="evo_price__default" style="text-decoration: line-through;">',
				'widget_item_price_after_default' => '</div>',
				'widget_item_price_before_best' => '<div class="evo_price__best">',
				'widget_item_price_after_best' => '</div>',
			), $this->disp_params );

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		$default_pricing = $Item->get_default_pricing( $this->disp_params['currency_ID'] );
		$best_pricing = $Item->get_current_best_pricing( $this->disp_params['currency_ID'] );
		$CurrencyCache = & get_currencyCache();
		$currency = $CurrencyCache->get_by_ID( $this->disp_params['currency_ID'], false, false );

		if( $default_pricing
				&& !( $best_pricing && ( $best_pricing['iprc_price'] == $default_pricing['iprc_price'] ) )
				&& $this->disp_params['display_original_price'] )
		{
			echo $this->disp_params['widget_item_price_before_default'];
			echo $currency->get( 'code' ).'&nbsp;'.number_format( $default_pricing['iprc_price'], 2 );
			echo $this->disp_params['widget_item_price_after_default'];
		}

		if( $best_pricing )
		{
			echo $this->disp_params['widget_item_price_before_best'];
			echo $currency->get( 'code' ).'&nbsp;'.number_format( $best_pricing['iprc_price'], 2 );
			echo $this->disp_params['widget_item_price_after_best'];
		}

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>