<?php
/**
 * This file implements the Financial Contribution plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Financial Contribution Plugin
 *
 * This plugin displays
 */
class financial_contribution_plugin extends Plugin
{
	var $name;
	var $code = 'fin_contrib';
	var $priority = 50;
	var $version = '7.0.0';
	var $author = 'The b2evo Group';
	var $group = 'widget';
	var $subgroup = 'other';
	var $widget_icon = 'money';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_('Financial Contribution Widget');
		$this->short_desc = T_('This skin tag displays a form for financial contribution.');
		$this->long_desc = T_('This skin tag displays a form for financial contribution.');
	}


	/**
	 * Table to store financial contributions per Item per User
	 *
	 * @return array
	 */
	function GetDbLayout()
	{
		global $DB;

		return array(
				'CREATE TABLE '.$this->get_sql_table( 'contributions' ).' (
					fnct_item_ID   INT(10) UNSIGNED NOT NULL,
					fnct_user_ID   INT(10) UNSIGNED NOT NULL,
					fnct_amount    DOUBLE UNSIGNED NOT NULL,
					fnct_timestamp TIMESTAMP NOT NULL DEFAULT "2000-01-01 00:00:00",
					PRIMARY KEY( fnct_item_ID, fnct_user_ID )
				) ENGINE = innodb DEFAULT CHARSET = '.$DB->connection_charset,
			);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$UserSettings}.
	 * @return
	 */
	function get_coll_setting_definitions( & $params )
	{
		$CurrencyCache = & get_CurrencyCache();
		$CurrencyCache->load_all();
		$currencies = $CurrencyCache->get_option_array();
		foreach( $currencies as $c => $currency )
		{
			unset( $currencies[ $c ] );
			$currencies[ $currency ] = $currency;
		}

		$r = array_merge( array(
			'currency' => array(
				'label' => T_('Currency'),
				'defaultvalue' => 'USD',
				'type' => 'select',
				'options' => $currencies,
			),
		), parent::get_coll_setting_definitions( $params ) );

		return $r;
	}


	/**
	 * Get keys for block/widget caching
	 *
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @param integer Widget ID
	 * @return array of keys this widget depends on
	 */
	function get_widget_cache_keys( $widget_ID = 0 )
	{
		global $Collection, $Blog, $Item, $current_User;

		return array(
				'wi_ID'        => $widget_ID, // Have the widget settings changed ?
				'set_coll_ID'  => isset( $Blog ) ? $Blog->ID : NULL, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => isset( $Blog ) ? $Blog->ID : NULL, // Has the content of the displayed blog changed ?
				'item_ID'      => isset( $Item ) ? $Item->ID : NULL, // Has the Item page changed?
				'user_ID'      => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
			);
	}


	/**
	 * Get definitions for widget specific editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		return array(
			'title' => array(
				'label' => T_('Title'),
				'size' => 60,
				'defaultvalue' => T_('Contribute to this project'),
			),
			'total_text' => array(
				'label' => T_('Total text'),
				'size' => 60,
				'defaultvalue' => T_('Total contributions so far'),
			),
			'current_contributors_text' => array(
				'label' => T_('Current contributors list text param'),
				'size' => 100,
				'defaultvalue' => T_('Members who have already pledged a contribution'),
			),
			'contribute_text' => array(
				'label' => T_('Contribute text'),
				'size' => 100,
				'defaultvalue' => T_('If you wish to financially support this project, enter the amount you are willing to contribute here'),
			),
			'contribute_button_text' => array(
				'label' => T_('Contribute button text'),
				'size' => 60,
				'defaultvalue' => T_('Pledge to contribute'),
			),
			'modify_button_text' => array(
				'label' => T_('Modify button text'),
				'size' => 60,
				'defaultvalue' => T_('Modify'),
			),
			'contribution_message' => array(
				'label' => T_('Contribution Message'),
				'size' => 100,
				'defaultvalue' => T_('Your contribution has been recorded. Thank you!'),
			),
			'deletion_message' => array(
				'label' => T_('Deletion Message'),
				'size' => 100,
				'defaultvalue' => T_('Your pledge has been removed.'),
			),
			'thank_you_text' => array(
				'label' => T_('Thank you text'),
				'size' => 100,
				'defaultvalue' => T_('You have pledged to contribute'),
			),
		);
	}


	/**
	 * Event handler: SkinTag
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display?
	 */
	function SkinTag( & $params )
	{
		global $Blog, $Item, $current_User;

		$this->init_widget_params( $params, array(
				'block_start'       => '<div class="evo_widget $wi_class$ panel panel-default">',
				'block_end'         => '</div>',
				'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
				'block_title_end'   => '</h4></div>',
				'block_body_start'  => '<div class="panel-body">',
				'block_body_end'    => '</div>',
				'text_row_start'    => '<p>',
				'text_title_start'  => '<b>',
				'text_title_end'    => ': </b>',
				'text_value_start'  => '',
				'text_value_end'    => '',
				'text_row_end'      => '</p>',
				'no_members_text'   => T_('No members yet.'),
			) );

		if( empty( $Blog ) )
		{	// Don't display this widget when no current Collection:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because there is no Collection.' );
			return false;
		}

		if( empty( $Item ) )
		{	// Don't display this widget when no current Item:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because there is no Item.' );
			return false;
		}

		if( ! is_logged_in() )
		{	// Don't display this widget when current User is not logged in:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because you are not logged in.' );
			return false;
		}

		$CurrencyCache = & get_CurrencyCache();
		if( ! ( $Currency = $CurrencyCache->get_by_name( $this->get_coll_setting( 'currency', $Blog ), false, false ) ) )
		{	// Don't display this widget with unknown currency:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because currency is not found, please check collection settings of this plugin.' );
			return false;
		}

		// Get contributed users:
		$contributed_users = $this->get_users_by_item_ID( $Item->ID );

		// Check if current user already contributed for the Item:
		if( isset( $contributed_users[ $current_User->ID ] ) )
		{
			$is_contributed_current_user = true;
			$contributed_current_user_amount = $contributed_users[ $current_User->ID ];
		}
		else
		{
			$is_contributed_current_user = false;
			$contributed_current_user_amount = '';
		}

		// Calculate contribution data:
		$total_amount = 0;
		if( ! empty( $contributed_users ) )
		{	// Display only if at least one user contributed this Item:
			$UserCache = & get_UserCache();
			$UserCache->load_list( array_keys( $contributed_users ) );
			$current_contributors_text = array();
			foreach( $contributed_users as $cont_user_ID => $cont_amount )
			{
				if( $cont_User = & $UserCache->get_by_ID( $cont_user_ID, false, false ) )
				{
					$current_contributors_text[] = $cont_User->get_identity_link().' ('.$Currency->get( 'shortcut' ).number_format( $cont_amount, 0, '', '\'' ).')';
					$total_amount += $cont_amount;
				}
			}
			$current_contributors_text = implode( ', ', $current_contributors_text );
		}

		echo $this->widget_params['block_start'];

		$widget_title = $this->get_widget_setting( 'title' );
		if( ! empty( $widget_title ) )
		{	// We want to display a title for the widget block:
			echo $this->widget_params['block_title_start'];
			echo $widget_title;
			echo $this->widget_params['block_title_end'];
		}

		echo $this->widget_params['block_body_start'];

		$Form = new Form( $this->get_htsrv_url( 'contribute', array(), '&' ), '', 'post' );

		$Form->begin_form();
		$Form->hidden( 'wi_ID', $this->widget_params['wi_ID'] );
		$Form->hidden( 'item_ID', $Item->ID );
		$Form->add_crumb( 'contribute' );

		// Total text:
		$this->display_widget_text( 'total_text', $Currency->get( 'shortcut' ).number_format( $total_amount, 0, '', '\'' ) );

		if( ! empty( $current_contributors_text ) )
		{	// Current contributors list:
			$this->display_widget_text( 'current_contributors_text', $current_contributors_text );
		}

		// Contribute text (amount input + submit button):
		$Form->output = false;
		$Form->switch_layout( 'none' );
		$contribute_input = $Form->text_input( 'contribute_amount', $contributed_current_user_amount, 5, '', '', array( 'maxlength' => 16, 'style' => 'width:auto;display:inline' ) );
		$contribute_button = $Form->button( array( 'submit', 'action_name',
			$this->get_widget_setting( $is_contributed_current_user ? 'modify_button_text' : 'contribute_button_text' ),
			$is_contributed_current_user ? 'btn-default' : 'btn-primary' ) );
		$Form->switch_layout( NULL );
		$Form->output = true;
		$this->display_widget_text( ( $is_contributed_current_user ? 'thank_you_text' : 'contribute_text' ),
			'<span class="nowrap">'.$contribute_input.' '.$this->get_coll_setting( 'currency', $Blog ).'</span> '.$contribute_button );

		$Form->end_form();

		echo $this->widget_params['block_body_end'];

		echo $this->widget_params['block_end'];

		return true;
	}


	/**
	 * Display text field for the widget form
	 *
	 * @param string Widget setting name
	 * @param string Content
	 */
	function display_widget_text( $setting_name, $content )
	{
		echo $this->widget_params['text_row_start']
			.$this->widget_params['text_title_start']
				.$this->get_widget_setting( $setting_name )
			.$this->widget_params['text_title_end']
			.$this->widget_params['text_value_start']
				.$content
			.$this->widget_params['text_value_end']
		.$this->widget_params['text_row_end'];
	}


	/**
	 * Return the list of Htsrv (HTTP-Services) provided by the plugin.
	 *
	 * This implements the plugin interface for the list of methods that are valid to
	 * get called through htsrv/call_plugin.php.
	 *
	 * @return array
	 */
	function GetHtsrvMethods()
	{
		return array( 'contribute' );
	}

	/**
	 * Plugin action to store a a pledge of contribution
	 *
	 * @param array Params
	 */
	function htsrv_contribute( $params )
	{
		global $Messages, $Session, $current_User, $DB, $localtimenow;

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'contribute' );

		if( ! is_logged_in() )
		{	// User must be logged in:
			// (don't translate because for normal work it must not happens):
			$Messages->add( 'You must be logged in to pledge a contribution.', 'error' );
			header_redirect();
			// Exit here.
		}

		$widget_ID = param( 'wi_ID', 'integer', true );
		$item_ID = param( 'item_ID', 'integer', true );
		$contribute_amount = str_replace( ',', '.', param( 'contribute_amount', 'string' ) );

		if( $contribute_amount === '' ||
		    ! is_decimal( $contribute_amount ) ||
		    $contribute_amount < 0 )
		{	// Allow only decimal numbers for amount::
			$Messages->add( T_('Please enter an amount you are willing to contribute.'), 'error' );
			header_redirect();
			// Exit here.
		}

		$WidgetCache = & get_WidgetCache();
		if( ! ( $Widget = & $WidgetCache->get_by_ID( $widget_ID, false, false ) ) )
		{	// Widget is not found by requested ID:
			// (don't translate because for normal work it must not happens):
			$Messages->add( 'Widget #'.$widget_ID.' is not found.', 'error' );
			header_redirect();
			// Exit here.
		}

		$ItemCache = & get_ItemCache();
		if( ! ( $Item = & $ItemCache->get_by_ID( $item_ID, false, false ) ) )
		{	// Item is not found by requested ID:
			// (don't translate because for normal work it must not happens):
			$Messages->add( 'Item #'.$item_ID.' is not found.', 'error' );
			header_redirect();
			// Exit here.
		}

		// Format amount:
		$contribute_amount = floor( $contribute_amount );

		if( $contribute_amount > 0 )
		{	// Insert/Update contribution:
			$DB->query( 'REPLACE INTO '.$this->get_sql_table( 'contributions' ).'
				       ( fnct_item_ID, fnct_user_ID, fnct_amount, fnct_timestamp )
				VALUES ( '.$DB->quote( $item_ID ).', '.$DB->quote( $current_User->ID ).', '.$DB->quote( $contribute_amount ).', '.$DB->quote( date2mysql( $localtimenow ) ).' )' );
			// Inform user about success contribution:
			$Messages->add( $Widget->get_param( 'contribution_message' ), 'success' );
		}
		else
		{	// Delete contribution:
			$DB->query( 'DELETE FROM '.$this->get_sql_table( 'contributions' ).'
				WHERE fnct_item_ID = '.$DB->quote( $item_ID ).'
				  AND fnct_user_ID = '.$DB->quote( $current_User->ID ) );
			// Inform user after delete contribution:
			$Messages->add( $Widget->get_param( 'deletion_message' ), 'warning' );
		}

		// The cached content of the widget must be invalidated:
		BlockCache::invalidate_key( 'wi_ID', $widget_ID );

		// Redirect back to previous page:
		header_redirect();
	}


	/**
	 * Get contributed users data for requested Item
	 *
	 * @param integer Item ID
	 * @return array Key - User ID, Value - Amount
	 */
	function get_users_by_item_ID( $item_ID )
	{
		global $DB;

		if( empty( $item_ID ) )
		{	// Item ID must be defined:
			return array();
		}

		$SQL = new SQL( 'Get contributed users data for Item #'.$item_ID );
		$SQL->SELECT( 'fnct_user_ID, fnct_amount' );
		$SQL->FROM( $this->get_sql_table( 'contributions' ) );
		$SQL->WHERE( 'fnct_item_ID = '.$DB->quote( $item_ID ) );
		$SQL->ORDER_BY( 'fnct_amount DESC, fnct_timestamp ASC' );

		return $DB->get_assoc( $SQL );
	}
}
?>