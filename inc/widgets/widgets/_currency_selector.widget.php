<?php
/**
 * This file implements the currency_selector Widget class.
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
class currency_selector_Widget extends ComponentWidget
{
	var $icon = 'money';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'currency_selector' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'currency-selector-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Currency Selector');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Currency Selector') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display the currency selector form');
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
		$currency_options = array( NULL => T_('Default') ) + $CurrencyCache->get_option_array();

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Title'),
					'size' => 40,
					'note' => T_('This is the title to display'),
					'defaultvalue' => '',
				),
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
		$this->init_display( $params );

		$currency = get_currency();

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];
		global $Session;

		$CurrencyCache = & get_CurrencyCache();
		$update_button = '<button type="submit" id="button_update_currency" name="actionArray[set_currency]" class="btn btn-primary" style="margin: 0;">'.T_('Update').'</button>';

		$Form = new Form( get_htsrv_url().'anon_async.php?action=set_currency', 'currency_selector', 'post', 'blockspan' );
		$Form->begin_form();
		$Form->select_input_object( 'currency', $currency->ID, $CurrencyCache, T_('Currency'), array( 'field_suffix' => $update_button ) );
		$Form->hidden( 'redirect_to', regenerate_url() );
		$Form->end_form();

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>