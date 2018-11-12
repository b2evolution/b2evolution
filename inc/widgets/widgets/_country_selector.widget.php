<?php
/**
 * This file implements the country_selector Widget class.
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
class country_selector_Widget extends ComponentWidget
{
	var $icon = 'compass';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'country_selector' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'country-selector-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Country Selector');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Country Selector') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display the country selector form');
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
				'selector_label' => array(
					'label' => T_('Label'),
					'size' => 40,
					'defaultvalue' => T_('Country'),
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
		$this->init_display( $params );

		$country = get_country();

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];
		global $Session;

		$CountryCache = & get_CountryCache();
		$update_button = '<button type="submit" id="button_update_country" name="actionArray[set_country]" class="btn btn-primary" style="margin: 0;">'.T_('Update').'</button>';

		$Form = new Form( get_htsrv_url().'anon_async.php?action=set_country', 'country_selector' );
		$Form->begin_form();
		$Form->select_input_object( 'country', $country->ID, $CountryCache, $this->disp_params['selector_label'], array( 'field_suffix' => $update_button ) );
		$Form->hidden( 'redirect_to', regenerate_url() );
		$Form->end_form();

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>