<?php
/**
 * This file implements the Table class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/ui/_uiwidget.class.php', 'Widget' );

/**
 * Class Table
 *
 * @package evocore
 */
class Table extends Widget
{
	/**
	 * Total number of pages
	 */
	var $total_pages = 1;

	/**
	 * Number of cols.
	 */
	var $nb_cols;

	/**
	 * Number of lines already displayed
	 */
	var $displayed_lines_count;

	/**
	 * Number of cols already displayed (in current line)
	 */
	var $displayed_cols_count;

	/**
	 * @var array
	 */
	var $fadeout_array;

	var $fadeout_count = 0;

	/**
	 * @var boolean
	 */
	var $is_fadeout_line;

	var $no_results_text;


	/**
	 * URL param names
	 */
	var $param_prefix;


	/**
	 * Parameters for the filter area:
	 */
	var $filter_area;

	/**
	 * Preset button that is currently active.
	 */
	var $current_filter_preset = NULL;

	/**
	 * Constructor
	 *
	 * @param string template name to get from $AdminUI
	 * @param string prefix to differentiate page/order/filter params
	 */
	function __construct( $ui_template = NULL, $param_prefix = '' )
	{
		parent::__construct( $ui_template );

		$this->param_prefix = $param_prefix;

		$this->no_results_text = T_('No results').'.';
	}


	/**
	 * Initialize things in order to be ready for displaying.
	 *
	 * Lazy fills $this->params
	 *
	 * @param array ***please document***
	 * @param array Fadeout settings array( 'key column' => array of values ) or 'session'
	 */
	function display_init( $display_params = NULL, $fadeout = NULL )
	{
		global $AdminUI, $Session, $Debuglog;

		if( empty( $this->params ) && isset( $AdminUI ) )
		{ // Use default params from Admin Skin:
			$this->params = $AdminUI->get_template( 'Results' );
		}

		// Make sure we have display parameters:
		if( !is_null($display_params) )
		{ // Use passed params:
			//$this->params = & $display_params;
			if( !empty( $this->params ) )
			{
				$this->params = array_merge( $this->params, $display_params );
			}
			else
			{
				$this->params = & $display_params;
			}
		}

		if( empty( $this->params ) )
		{
			$this->params = array();
		}
		// Initialize default params
		$this->params = array_merge( array(
				// If button at top right: (default values for v5 skins)
				'filter_button_before' => '',
				'filter_button_after'  => '',
				'filter_button_class'  => 'filter',
				// If buttom at bottom (only happens in v7+)
				'bottom_filter_button_before' => '<div class="form-group">',
				'bottom_filter_button_after'  => '</div>',
				'bottom_filter_button_class'  => 'evo_btn_apply_filters btn-sm btn-info',
			), $this->params );

		if( $fadeout == 'session' )
		{	// Get fadeout_array from session:
			if( ($this->fadeout_array = $Session->get('fadeout_array')) && is_array( $this->fadeout_array ) )
			{
				$Debuglog->add( 'UIwidget: Got fadeout_array from session data.', 'results' );
				$Session->delete( 'fadeout_array' );
			}
			else
			{
				$this->fadeout_array = NULL;
			}
		}
		else
		{
			$this->fadeout_array = $fadeout;
		}
	}


	/**
	 * Display options area (e-g: Filters area; was also designed for makign a 'columns' options area)
	 *
	 * @param string name of the option ( ma_colselect, tsk_filter....)
	 * @param string area name ( colselect_area, filter_area )
	 * @param string option title
	 * @param string submit button title
	 * @param string default fold state when is empty in the session; possible values: 'collapsed', 'expanded'
	 *
	 */
	function display_option_area( $option_name, $area_name, $option_title, $submit_title, $default_fold_state = 'expanded' )
	{
		global $debug, $Session;

		// Do we already have a form?
		$create_new_form = ! isset( $this->Form );

		echo $this->replace_vars( $this->params['filters_start'] );

		$fold_state = $Session->get( $option_name );

		if( empty( $fold_state ) )
		{
			$fold_state = $default_fold_state;
		}

		//__________________________________  Toogle link _______________________________________

		if( $fold_state == 'collapsed' )
		{
			echo '<a class="filters_title" href="'.regenerate_url( 'action,target', 'action=expand_filter&target='.$option_name ).'"
								onclick="return toggle_filter_area(\''.$option_name.'\');" >'
						.get_icon( 'filters_show', 'imgtag', array( 'id' => 'clickimg_'.$option_name ) );
		}
		else
		{
			echo '<a class="filters_title" href="'.regenerate_url( 'action,target', 'action=collapse_filter&target='.$option_name ).'"
								onclick="return toggle_filter_area(\''.$option_name.'\');" >'
						.get_icon( 'filters_hide', 'imgtag', array( 'id' => 'clickimg_'.$option_name ) );
		}
		echo $option_title.'</a>:';

		//____________________________________ Filters preset ____________________________________

		if( !empty( $this->{$area_name}['presets'] ) )
		{ // We have preset filters
			$r = array();
			// Loop on all preset filters:
			foreach( $this->{$area_name}['presets'] as $key => $preset )
			{
				// Display preset filter link:
				$preset_text = ( $preset[1] == '#nolink#' ? $preset[0] : '<a href="'.$preset[1].'" class="label label-default">'.$preset[0].'</a>' );
				$r[] = ( isset( $preset[2] ) ? '<span '.$preset[2].'>'.$preset_text.'</span>' : $preset_text );
			}

			echo ' '.implode( ' ', $r );
		}

		//_________________________________________________________________________________________

		if( $debug > 1 )
		{
			echo ' <span class="notes">('.$option_name.':'.$fold_state.')</span>';
			echo ' <span id="asyncResponse"></span>';
		}

		// Begining of the div:
		echo '<div id="clickdiv_'.$option_name.'"';
		if( $fold_state == 'collapsed' )
		{
			echo ' style="display:none;"';
		}
		echo '>';

		//_____________________________ Form and callback _________________________________________

		if( !empty($this->{$area_name}['callback']) )
		{	// We want to display Filter Form fields:

			if( $create_new_form )
			{	// We do not already have a form surrounding the whole results list:

				if( !empty( $this->{$area_name}['url_ignore'] ) )
				{
					$ignore = $this->{$area_name}['url_ignore'];
				}
				else
				{
					$ignore = $this->page_param;
				}

				$this->Form = new Form( regenerate_url( $ignore, '', '', '&' ), $this->param_prefix.'form_search', 'get', 'blockspan' );

				$this->Form->begin_form( '' );
			}

			if( !isset( $this->filter_area['apply_filters_button'] ) || $this->filter_area['apply_filters_button'] == 'topright' )
			{ // Display a filter button only when it is not hidden by param:  (Hidden example: BackOffice > Contents > Posts)
				echo $this->params['filter_button_before'];
				$submit_name = empty( $this->{$area_name}['submit'] ) ? 'colselect_submit' : $this->{$area_name}['submit'];
				$this->Form->button_input( array(
							'tag'   => 'button',
							'name'  => $submit_name,
							'value' => get_icon( 'filter' ).' '.$submit_title,
							'class' => $this->params['filter_button_class']
					) );
				echo $this->params['filter_button_after'];
			}

			if( ! empty( $this->force_checkboxes_to_inline ) )
			{ // Set this to TRUE in order to display all checkboxes before labels
				$this->Form->force_checkboxes_to_inline = true;
			}

			$func = $this->{$area_name}['callback'];
			$filter_fields = $func( $this->Form );

			if( ! empty( $filter_fields ) && is_array( $filter_fields ) )
			{	// Display filters which use JavaScript plugin QueryBuilder:
				$this->display_filter_fields( $this->Form, $filter_fields );
			}

			if( isset( $this->filter_area['apply_filters_button'] ) && $this->filter_area['apply_filters_button'] == 'bottom' )
			{ // Display a filter button only when it is not hidden by param:  (Hidden example: BackOffice > Contents > Posts)
				echo $this->params['bottom_filter_button_before'];
				$submit_name = empty( $this->{$area_name}['submit'] ) ? 'colselect_submit' : $this->{$area_name}['submit'];
				$this->Form->button_input( array(
						'tag'   => 'button',
						'name'  => $submit_name,
						'value' => get_icon( 'filter' ).' '.$submit_title,
						'class' => $this->params['bottom_filter_button_class']
					) );
				echo $this->params['bottom_filter_button_after'];
			}

			if( $create_new_form )
			{ // We do not already have a form surrounding the whole result list:
				$this->Form->end_form( '' );
				unset( $this->Form );	// forget about this temporary form
			}
		}

		echo '</div>';

		echo $this->params['filters_end'];
	}


	/**
	 * Register a filter preset
	 */
	function register_filter_preset( $preset_codename, $preset_label, $preset_url )
	{
		// Append a preset
		$this->filter_area['presets'][$preset_codename] = array( 
				$preset_label,
				// Append param to indicate current preset:
				url_add_param( $preset_url, $this->param_prefix.'filter_preset='.$preset_codename ) );
	}


	/**
	 * Display filter fields
	 *
	 * @param array Filters
	 */
	function display_filter_fields( & $Form, $filter_fields )
	{
		echo '<div id="evo_results_filters"></div>';
		$Form->hidden( 'filter_query', '' );

		$js_filters = array();
		foreach( $filter_fields as $field_ID => $params )
		{
			if( $field_ID == '#default' )
			{	// Skip a reserved field for default filters:
				continue;
			}

			$js_filter = array( 'id:\''.$field_ID.'\'' );

			if( isset( $params['type'] ) )
			{	// Set default params depending on field type:
				switch( $params['type'] )
				{
					case 'date':
						$params['operators'] = '=,!=,>,>=,<,<=,between,not_between';
						$params['plugin'] = 'datepicker';
						$params['plugin_config'] = array(
							'dateFormat'  => jquery_datepicker_datefmt(),
							'monthNames'  => jquery_datepicker_month_names(),
							'dayNamesMin' => jquery_datepicker_day_names(),
							'firstDay'    => locale_startofweek(),
						);
						if( ! isset( $params['validation'] ) )
						{
							$params['validation'] = array();
						}
						$params['validation']['format'] = strtoupper( jquery_datepicker_datefmt() );
						break;
				}
			}

			if( ! isset( $params['operators'] ) )
			{	// Use default operator if it is not defined:
				$params['operators'] = '=,!=';
			}

			foreach( $params as $param_name => $param_value )
			{
				switch( $param_name )
				{
					case 'operators':
						// Convert operators to proper format:
						if( ! empty( $param_value ) )
						{
							$operators = explode( ',', $param_value );
							foreach( $operators as $o => $operator )
							{	// Replace aliases with corrent name which is used in jQuery QueryBuilder plugin:
								switch( $operator )
								{
									case '=':
										$operators[ $o ] = 'equal';
										break;
									case '!=':
									case '<>':
										$operators[ $o ] = 'not_equal';
										break;
									case '<':
										$operators[ $o ] = 'less';
										break;
									case '<=':
										$operators[ $o ] = 'less_or_equal';
										break;
									case '>':
										$operators[ $o ] = 'greater';
										break;
									case '>=':
										$operators[ $o ] = 'greater_or_equal';
										break;
								}
							}
							$param_value = '[\''.implode( '\',\'', $operators ).'\']';
						}
						break;

					case 'values':
						$param_values = array();
						foreach( $param_value as $sub_param_name => $sub_param_value )
						{
							$param_values[] = '{\''.format_to_js( $sub_param_name ).'\':\''.format_to_js( $sub_param_value ).'\'}';
						}
						$param_value = '['.implode( ',', $param_values ).']';
						break;

					case 'valueGetter':
					case 'valueSetter':
						// Don't convert these params to string because they are functions:
						break;

					case 'input':
						if( strpos( $param_value, 'function' ) === 0 )
						{	// Don't convert this param to string if it is a function:
							break;
						}

					default:
						if( is_array( $param_value ) )
						{	// Array param:
							$param_values = array();
							foreach( $param_value as $sub_param_name => $sub_param_value )
							{
								if( $sub_param_value == 'true' || $sub_param_value == 'false' ||
								    strpos( $sub_param_value, '[' ) === 0 )
								{	// This is a not string value:
									$sub_param_value = $sub_param_value;
								}
								else
								{	// This is a string value:
									$sub_param_value = '\''.format_to_js( $sub_param_value ).'\'';
								}
								$param_values[] = $sub_param_name.':'.$sub_param_value;
							}
							$param_value = '{'.implode( ',', $param_values ).'}';
						}
						else
						{	// String param:
							$param_value = '\''.format_to_js( $param_value ).'\'';
						}
				}

				$js_filter[] = $param_name.':'.$param_value;
			}
			$js_filters[] = '{'.implode( ',', $js_filter ).'}';
		}

		// Get filter values from request:
		$filter_query = param_condition( 'filter_query', '', false, array_keys( $filter_fields ) );
		if( empty( $filter_query ) || $filter_query === 'null' )
		{	// Set filter values if no request yet:
			$filter_query = array(
				'rules'     => array(),
				'valid'     => true,
			);
			if( isset( $filter_fields['#default'] ) )
			{	// Set filters from default config:
				foreach( $filter_fields['#default'] as $def_filter_id => $def_filter_data )
				{
					$filter_query['rules'][] = array(
						'id'       => $def_filter_id,
						'operator' => is_array( $def_filter_data ) && isset( $def_filter_data[0] ) ? $def_filter_data[0] : $def_filter_data,
						'value'    => is_array( $def_filter_data ) && isset( $def_filter_data[1] ) ? $def_filter_data[1] : '',
					);
				}
			}
			$filter_query = json_encode( $filter_query );
		}
?>
<script>
jQuery( document ).ready( function()
{
	jQuery( '#evo_results_filters' ).queryBuilder(
	{
		allow_empty: true,
		display_empty_filter: true,
		plugins: ['bt-tooltip-errors'],
		icons: {
			add_group: 'fa fa-plus-circle',
			add_rule: 'fa fa-plus',
			remove_group: 'fa fa-minus-circle',
			remove_rule: 'fa fa-minus-circle',
			error: 'fa fa-warning',
		},
		operators: [
			'equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal', 'between', 'not_between', 'contains', 'not_contains',
			{ type: 'blank', nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'user_tagged', nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'user_not_tagged', nb_inputs: 1, multiple: false, apply_to: ['string'] }
		],
		lang: {
			add_rule: '<?php echo TS_('Add line'); ?>',
			delete_rule: '<?php echo TS_('Remove line'); ?>',
			add_group: '<?php echo TS_('Add group'); ?>',
			delete_rule: '<?php echo TS_('Remove line'); ?>',
			delete_group: '<?php echo TS_('Remove group'); ?>',
			conditions: {
			   AND: 'Match ALL of',
    			OR: 'Match ANY of',
    		},
    		operators: {
				equal: '=',
				not_equal: '&#8800;',
				less: '<',
				less_or_equal: '&#8804;',
				greater: '>',
				greater_or_equal: '&#8805;',
				between: '<?php echo TS_('between'); ?>',
				not_between: '<?php echo TS_('not between'); ?>',
				contains: '<?php echo TS_('contains'); ?>',
				not_contains: '<?php echo TS_('doesn\'t contain'); ?>',
				blank: ' ',
				user_tagged: '<?php echo TS_('user is tagged with all of'); ?>',
				user_not_tagged: '<?php echo TS_('user is not tagged with any of'); ?>',
			}
		},
		templates: {
			group: '\
<dl id="{{= it.group_id }}" class="rules-group-container"> \
  <dt class="rules-group-header"> \
    <div class="btn-group pull-right group-actions"> \
      {{? it.level>1 }} \
        <button type="button" class="btn btn-xs btn-default" data-delete="group"> \
          <i class="{{= it.icons.remove_group }}"></i> {{= it.translate("delete_group") }} \
        </button> \
      {{?}} \
    </div> \
    <div class="btn-group group-conditions"> \
      {{~ it.conditions: condition }} \
        <label class="btn btn-xs btn-default"> \
          <input type="radio" name="{{= it.group_id }}_cond" value="{{= condition }}"> {{= it.translate("conditions", condition) }} \
        </label> \
      {{~}} \
    </div> \
    {{? it.settings.display_errors }} \
      <div class="error-container"><i class="{{= it.icons.error }}"></i></div> \
    {{?}} \
  </dt> \
  <dd class=rules-group-body> \
    <ul class=rules-list>\
    <li class="rule-container">\
      <button type="button" class="btn btn-xs btn-default" data-add="rule"> \
        <i class="{{= it.icons.add_rule }}"></i> {{= it.translate("add_rule") }} \
      </button> \
      {{? it.settings.allow_groups===-1 || it.settings.allow_groups>=it.level }} \
        <button type="button" class="btn btn-xs btn-default" data-add="group"> \
          <i class="{{= it.icons.add_group }}"></i> {{= it.translate("add_group") }} \
        </button> \
      {{?}} \
    </li> \
    </ul> \
  </dd> \
</dl>',
			rule: '\
<li id="{{= it.rule_id }}" class="rule-container"> \
  <div class="rule-header"> \
    <div class="btn-group pull-right rule-actions"> \
      <button type="button" class="btn btn-xs btn-default" data-delete="rule"> \
        <i class="{{= it.icons.remove_rule }}"></i> {{= it.translate("delete_rule") }} \
      </button> \
    </div> \
  </div> \
  {{? it.settings.display_errors }} \
    <div class="error-container"><i class="{{= it.icons.error }}"></i></div> \
  {{?}} \
  <div class="rule-filter-container"></div> \
  <div class="rule-operator-container"></div> \
  <div class="rule-value-container"></div> \
</li>',
		},	
		filters: [<?php echo implode( ',', $js_filters ); ?>],
		rules: <?php echo param_format_condition( $filter_query, 'js', array_keys( $filter_fields ) ); ?>,
	} );

	// Prepare form before submitting:
	jQuery( '#evo_results_filters' ).closest( 'form' ).on( 'submit', function()
	{
		// Convert filter fields to JSON format:
		var result = jQuery( '#evo_results_filters' ).queryBuilder( 'getRules' );
		if( result === null )
		{	// Stop submitting on wrong SQL:
			return false;
		}
		else
		{	// Set query rules to hidden field before submitting:
			jQuery( 'input[name=filter_query]' ).val( JSON.stringify( result ) );
		}
	} );

	// Fix space of blank hidden operator:
	evo_fix_query_builder_blank_operator();
	jQuery( '#evo_results_filters' ).on( 'afterUpdateRuleFilter.queryBuilder.filter', function()
	{
		evo_fix_query_builder_blank_operator();
	} );
	function evo_fix_query_builder_blank_operator()
	{
		jQuery( '.rule-container .rule-operator-container' ).each( function()
		{
			if( jQuery( this ).find( 'option' ).length == jQuery( this ).find( 'option[value=blank]' ).length )
			{	// Hide container if rule uses only single blank operator:
				jQuery( this ).hide();
			}
			else
			{	// Show container with other operators:
				jQuery( this ).show();
			}
		} );
	}
} );
</script>
<?php
	}


	/**
	 * Display the column selection
	 */
	function display_colselect()
	{
		if( empty( $this->colselect_area ) )
		{	// We don't want to display a col selection section:
			return;
		}

		$option_name = $this->param_prefix.'colselect';

		$this->display_option_area( $option_name, 'colselect_area', T_('Columns'), T_('Apply'), 'collapsed');
	}


	/**
	 * Display the filtering form
	 */
	function display_filters()
	{
		global $debug, $Session;

		if( empty( $this->filter_area ) )
		{	// We don't want to display a filters section:
			return;
		}

		if( empty( $this->param_prefix ) )
		{	// Deny to use a list without prefix
			debug_die( 'You must define a $param_prefix before you can use filters.' );
		}

		$option_name = $this->param_prefix.'filters';
		$preset_name = $this->param_prefix.'filter_preset';
		$option_title = T_('Filters');
		$submit_title = !empty( $this->filter_area['submit_title'] ) ? $this->filter_area['submit_title'] : T_('Apply filters');

		// Do we already have a form?
		$create_new_form = ! isset( $this->Form );

		echo $this->replace_vars( $this->params['filters_start'] );

		$this->current_filter_preset = param( $preset_name, 'string', NULL );
		if( $this->current_filter_preset !== NULL )
		{	// Store new preset in Session:
			$Session->set( $preset_name, $this->current_filter_preset );
			$Session->dbsave();
		}
		if( $this->current_filter_preset === NULL )
		{	// Try to get preset from Session:
			$this->current_filter_preset = $Session->get( $preset_name );
		}
		if( $this->current_filter_preset === NULL )
		{	// Use 'all' preset filter by default:
			$this->current_filter_preset = 'all';
		}

		$fold_state = ( $this->current_filter_preset == 'custom' ? 'expanded' : 'collapsed' );

		if( empty( $fold_state ) )
		{
			$fold_state = 'collapsed';
		}

		//____________________________________ Filter presets ____________________________________

		echo '<span class="btn-group">';

		// Display all presers;
		if( ! empty( $this->filter_area['presets'] ) )
		{	// Display preset filters:
			
			foreach( $this->filter_area['presets'] as $key => $preset )
			{
				// Link for preset filter:
				echo '<a href="'.$preset[1].'" class="btn btn-xs btn-info'.( $this->current_filter_preset == $key ? ' active' : '' ).'">'.$preset[0].'</a>';
			}
		}

		// "Custom preset" with JS toggle to reveal form:
// fp>yb: TODO: I don't think this can work without Javascript (and it doesn't need to) -- remove all unnecessary code
		echo '<a href="'.regenerate_url( 'action,target', 'action='.( $fold_state == 'collapsed' ? 'expand_filter' : 'collapse_filter' ).'&target='.$option_name ).'"'
			.' onclick="return toggle_filter_area(\''.$option_name.'\')" class="btn btn-xs btn-info'.( $this->current_filter_preset == 'custom' ? ' active' : '' ).'">'
				.get_icon( ( $fold_state == 'collapsed' ? 'filters_show' : 'filters_hide' ), 'imgtag', array( 'id' => 'clickimg_'.$option_name ) )
				.' '.T_('Custom filters')
			.'</a>';

		echo '</span>'; // End of <span class="btn-group">

		if( ! empty( $this->filter_area['presets_after'] ) )
		{	// Display additional info after presets:
			echo $this->filter_area['presets_after'];
		}

		//_________________________________________________________________________________________

		if( $debug > 1 )
		{
			echo ' <span class="notes">('.$option_name.':'.$fold_state.')</span>';
			echo ' <span id="asyncResponse"></span>';
		}

		// Begining of the div:
		echo '<div id="clickdiv_'.$option_name.'"';
		if( $fold_state == 'collapsed' )
		{
			echo ' style="display:none;"';
		}
		echo '>';

		//_____________________________ Form and callback _________________________________________

		if( !empty($this->filter_area['callback']) )
		{	// We want to display Filter Form fields:

			if( $create_new_form )
			{	// We do not already have a form surrounding the whole results list:

				if( !empty( $this->filter_area['url_ignore'] ) )
				{
					$ignore = $this->filter_area['url_ignore'];
				}
				else
				{
					$ignore = $this->page_param;
				}

				$this->Form = new Form( regenerate_url( $ignore, '', '', '&' ), $this->param_prefix.'form_search', 'get', 'blockspan' );

				$this->Form->begin_form( '' );
			}

			if( !isset( $this->filter_area['apply_filters_button'] ) || $this->filter_area['apply_filters_button'] == 'topright' )
			{ // Display a filter button only when it is not hidden by param:  (Hidden example: BackOffice > Contents > Posts)
				echo $this->params['filter_button_before'];
				$submit_name = empty( $this->filter_area['submit'] ) ? 'colselect_submit' : $this->filter_area['submit'];
				$this->Form->button_input( array(
							'tag'   => 'button',
							'name'  => $submit_name,
							'value' => get_icon( 'filter' ).' '.$submit_title,
							'class' => $this->params['filter_button_class']
					) );
				echo $this->params['filter_button_after'];
			}

			if( ! empty( $this->force_checkboxes_to_inline ) )
			{ // Set this to TRUE in order to display all checkboxes before labels
				$this->Form->force_checkboxes_to_inline = true;
			}

			$func = $this->filter_area['callback'];
			$filter_fields = $func( $this->Form );

			if( ! empty( $filter_fields ) && is_array( $filter_fields ) )
			{	// Display filters which use JavaScript plugin QueryBuilder:
				$this->display_filter_fields( $this->Form, $filter_fields );
			}

			if( isset( $this->filter_area['apply_filters_button'] ) && $this->filter_area['apply_filters_button'] == 'bottom' )
			{ // Display a filter button only when it is not hidden by param:  (Hidden example: BackOffice > Contents > Posts)
				echo $this->params['bottom_filter_button_before'];
				$submit_name = empty( $this->filter_area['submit'] ) ? 'colselect_submit' : $this->filter_area['submit'];
				$this->Form->button_input( array(
						'tag'   => 'button',
						'name'  => $submit_name,
						'value' => get_icon( 'filter' ).' '.$submit_title,
						'class' => $this->params['bottom_filter_button_class']
					) );
				echo $this->params['bottom_filter_button_after'];
			}

			// Use reserved preset name for filtering by submitted form:
			$this->Form->hidden( $this->param_prefix.'filter_preset', 'custom' );

			if( $create_new_form )
			{ // We do not already have a form surrounding the whole result list:
				$this->Form->end_form( '' );
				unset( $this->Form );	// forget about this temporary form
			}
		}

		echo '</div>';

		echo $this->params['filters_end'];
	}


	/**
	 * Display list/table start.
	 *
	 * Typically outputs UL or TABLE tags.
	 */
	function display_list_start()
	{
		if( $this->total_pages == 0 )
		{ // There are no results! Nothing to display!
			if( isset( $this->filter_area['is_filtered'] ) && ! $this->filter_area['is_filtered'] )
			{	// If this table list is filtered and no results then we should collapse the filter area because nothing to filter:
				echo '<script type="text/javascript">toggle_filter_area( "'.$this->param_prefix.'filters", "collapse" )</script>';
			}
			echo $this->replace_vars( $this->params['no_results_start'] );
		}
		else
		{ // We have rows to display:
			if( ! empty( $this->list_mass_actions ) )
			{	// Start form for list with mass actions:
				$this->Form = new Form();
				$this->Form->begin_form();
				if( ! empty( $this->list_form_hiddens ) )
				{
					foreach( $this->list_form_hiddens as $list_form_hidden_key => $list_form_hidden_value )
					{
						if( $list_form_hidden_key == 'crumb' )
						{	// Special hidden for crumb:
							$this->Form->add_crumb( $list_form_hidden_value );
						}
						else
						{	// Normal hidden field:
							$this->Form->hidden( $list_form_hidden_key, $list_form_hidden_value );
						}
					}
				}
			}

			$list_class = empty( $this->params['list_class'] ) ? '' : ' '.$this->params['list_class'];
			$list_attrs = empty( $this->params['list_attrib'] ) ? '' : ' '.$this->params['list_attrib'];
			echo str_replace( array( ' $list_class$', ' $list_attrib$' ), array( $list_class, $list_attrs ), $this->params['list_start'] );
		}
	}


	/**
	 * Display list/table end.
	 *
	 * Typically outputs </ul> or </table>
	 */
	function display_list_end()
	{
		if( $this->total_pages == 0 )
		{ // There are no results! Nothing to display!
			echo $this->replace_vars( $this->params['no_results_end'] );
		}
		else
		{	// We have rows to display:
			echo $this->params['list_end'];

			if( ! empty( $this->list_mass_actions ) && isset( $this->Form ) )
			{	// Start form for list with mass actions:
				$this->Form->end_form();
			}
		}
	}


	/**
	 * Display list/table head.
	 *
	 * This includes list head/title and filters.
	 * EXPERIMENTAL: also dispays <tfoot>
	 */
	function display_head()
	{
		if( is_ajax_content() )
		{	// Don't display this content on AJAX request
			return;
		}

		// DISPLAY TITLE:
		if( isset($this->title) )
		{ // A title has been defined for this result set:
			echo $this->replace_vars( $this->params['head_title'] );
		}

		// DISPLAY FILTERS:
		$this->display_filters();

		// DISPLAY COL SELECTION
		$this->display_colselect();


		// Experimental:
		/*echo $this->params['tfoot_start'];
		echo $this->params['tfoot_end'];*/
	}



	/**
	 * Display column headers
	 */
	function display_col_headers()
	{
		echo $this->params['head_start'];

		if( isset( $this->cols ) )
		{

			if( !isset($this->nb_cols) )
			{	// Needed for sort strings:
				$this->nb_cols = count($this->cols);
			}


			$th_group_activated = false;

			// Loop on all columns to see if we have th_group columns:
			foreach( $this->cols as $col )
			{
				if( isset( $col['th_group'] )	)
				{	// We have a th_group column, so break:
					$th_group_activated = true;
					break;
				}
			}

			$current_th_group_colspan = 1;
			$current_th_colspan = 1;
			$current_th_group_title = NULL;
			$current_th_title = NULL;
			$header_cells = array();

			// Loop on all columns to get an array of header cells description
			// Each header cell will have a colspan and rowspan value
			// The line 0 is reserved for th_group
			// The line 1 is reserved for th
			foreach( $this->cols as $key=>$col )
			{
				//_______________________________ TH GROUP __________________________________

				if( isset( $col['th_group'] ) )
				{	// The column has a th_group
					if( is_null( $current_th_group_title ) || $col['th_group'] != $current_th_group_title )
					{	// It's the begining of a th_group colspan (line0):

						//Initialize current th_group colspan to 1 (line0):
						$current_th_group_colspan = 1;

						// Set colspan and rowspan colum for line0 to 1:
						$header_cells[0][$key]['colspan'] = 1;
						$header_cells[0][$key]['rowspan'] = 1;
					}
					else
					{	// The column is part of a th group colspan
						// Update the first th group colspan cell
						$header_cells[0][$key-$current_th_group_colspan]['colspan']++;

						// Set the colspan column to 0 to not display it
						$header_cells[0][$key]['colspan'] = 0;
						$header_cells[0][$key]['rowspan'] = 0;

						//Update current th_group colspan to 1 (line0):
						$current_th_group_colspan++;
					}

					// Update current th group title:
					$current_th_group_title = 	$col['th_group'];
				}

				//___________________________________ TH ___________________________________

				if( is_null( $current_th_title ) || $col['th'] != $current_th_title )
				{	// It's the begining of a th colspan (line1)

					//Initialize current th colspan to 1 (line1):
					$current_th_colspan = 1;

					// Update current th title:
					$current_th_title = $col['th'];

					if( $th_group_activated  && !isset( $col['th_group'] ) )
					{ // We have to lines and the column has no th_group, so it will be a "rowspan2"

						// Set the cell colspan and rowspan values for the line0:
						$header_cells[0][$key]['colspan'] = 1;
						$header_cells[0][$key]['rowspan'] = 2;

						// Set the cell colspan and rowspan values for the line1, to do not display it:
						$header_cells[1][$key]['colspan'] = 0;
						$header_cells[1][$key]['rowspan'] = 0;
					}
					else
					{	// The cell has no rowspan
						$header_cells[1][$key]['colspan'] = 1;
						$header_cells[1][$key]['rowspan'] = 1;
					}
				}
				else
				{	// The column is part of a th colspan
					if( $th_group_activated && !isset( $col['th_group'] ) )
					{	// We have to lines and the column has no th_group, the colspan is "a rowspan 2"

						// Update the first th cell colspan in line0
						$header_cells[0][$key-$current_th_colspan]['colspan']++;

						// Set the cell colspan to 0 in line0 to not display it:
						$header_cells[0][$key]['colspan'] = 0;
						$header_cells[0][$key]['rowspan'] = 0;
					}
					else
					{ // Update the first th colspan cell in line1
						$header_cells[1][$key-$current_th_colspan]['colspan']++;
					}

					// Set the cell colspan to 0 in line1 to do not display it:
					$header_cells[1][$key]['colspan'] = 0;
					$header_cells[1][$key]['rowspan'] = 0;

					$current_th_colspan++;
				}
			}

			// ________________________________________________________________________________

			if( !$th_group_activated )
			{	// We have only the "th" line to display
				$start = 1;
			}
			else
			{	// We have the "th_group" and the "th" lines to display
				$start = 0;
			}

			//__________________________________________________________________________________

			// Loop on all headers lines:
			for( $i = $start; $i <2 ; $i++ )
			{
				echo $this->params['line_start_head'];
				// Loop on all headers lines cells to display them:
				foreach( $header_cells[$i] as $key=>$cell )
				{
					if( $cell['colspan'] )
					{	// We have to dispaly cell:
						if( $i == 0 && $cell['rowspan'] != 2 )
						{	// The cell is a th_group
							$th_title = $this->cols[$key]['th_group'];
							$col_order = isset( $this->cols[$key]['order_group'] );
						}
						else
						{	// The cell is a th
							$th_title = $this->cols[$key]['th'];
							$col_order = isset( $this->cols[$key]['order'] )
							|| isset( $this->cols[$key]['order_objects_callback'] )
							|| isset( $this->cols[$key]['order_rows_callback'] );
						}


						if( isset( $this->cols[$key]['th_class'] ) )
						{	// We have a class for the th column
							$class = $this->cols[$key]['th_class'];
						}
						else
						{	// We have no class for the th column
							$class = '';
						}

						if( $key == 0 && isset($this->params['colhead_start_first']) )
						{ // Display first column start:
							$output = $this->params['colhead_start_first'];

							// Add the total column class in the grp col start first param class:
							$output = str_replace( '$class$', $class, $output );
						}
						elseif( ( $key + $cell['colspan'] ) == (count( $this->cols) ) && isset($this->params['colhead_start_last']) )
						{ // Last column can get special formatting:
							$output = $this->params['colhead_start_last'];

							// Add the total column class in the grp col start end param class:
							$output = str_replace( '$class$', $class, $output );
						}
						else
						{ // Display regular colmun start:
							$output = $this->params['colhead_start'];

							// Replace the "class_attrib" in the grp col start param by the td column class
							$output = str_replace( '$class_attrib$', 'class="'.$class.'"', $output );
						}

						// Replace column header title attribute
						if( isset( $this->cols[$key]['th_title'] ) )
						{ // Column header title is set
							$output = str_replace( '$title_attrib$', ' title="'.$this->cols[$key]['th_title'].'"', $output );
						}
						else
						{ // Column header title is not set, replace with empty string
							$output = str_replace( '$title_attrib$', '', $output );
						}

						// Set colspan and rowspan values for the cell:
						$output = preg_replace( '#(<)([^>]*)>$#', '$1$2 colspan="'.$cell['colspan'].'" rowspan="'.$cell['rowspan'].'">' , $output );

						echo $output;

						if( $col_order )
						{ // The column can be ordered:
							$col_sort_values = $this->get_col_sort_values( $key );


							// Determine CLASS SUFFIX depending on wether the current column is currently sorted or not:
							if( !empty($col_sort_values['current_order']) )
							{ // We are currently sorting on the current column:
								$class_suffix = '_current';
							}
							else
							{	// We are not sorting on the current column:
								$class_suffix = '_sort_link';
							}

							// Display title depending on sort type/mode:
							if( $this->params['sort_type'] == 'single' )
							{ // single column sort type:

								// Title with toggle:
								echo '<a href="'.$col_sort_values['order_toggle'].'"'
											.' title="'.T_('Change Order').'"'
											.' class="single'.$class_suffix.'"'
											.'>'.$th_title.'</a>';

								// Icon for ascending sort:
								echo '<a href="'.$col_sort_values['order_asc'].'"'
											.' title="'.T_('Ascending order').'"'
											.'>'.$this->params['sort_asc_'.($col_sort_values['current_order'] == 'ASC' ? 'on' : 'off')].'</a>';

								// Icon for descending sort:
								echo '<a href="'.$col_sort_values['order_desc'].'"'
											.' title="'.T_('Descending order').'"'
											.'>'.$this->params['sort_desc_'.($col_sort_values['current_order'] == 'DESC' ? 'on' : 'off')].'</a>';

							}
							else
							{ // basic sort type (toggle single column):

								if( $col_sort_values['current_order'] == 'ASC' )
								{ // the sorting is ascending and made on the current column
									$sort_icon = $this->params['basic_sort_asc'];
								}
								elseif( $col_sort_values['current_order'] == 'DESC' )
								{ // the sorting is descending and made on the current column
									$sort_icon = $this->params['basic_sort_desc'];
								}
								else
								{ // the sorting is not made on the current column
									$sort_icon = $this->params['basic_sort_off'];
								}

								// Toggle Icon + Title
								// Set link title only if the column header title was not set
								$link_title = isset( $this->cols[$key]['th_title'] ) ? '' : ' title="'.T_('Change Order').'"';
								echo '<a href="'.$col_sort_values['order_toggle'].'"'
											.$link_title
											.' class="basic'.$class_suffix.'"'
											.'>'.$sort_icon.' '.$th_title.'</a>';

							}

						}
						elseif( $th_title )
						{ // the column can't be ordered, but we still have a header defined:
							echo '<span>'.$th_title.'</span>';
						}
						// </td>
						echo $this->params['colhead_end'];
					}
				}
				// </tr>
				echo $this->params['line_end'];
			}
		} // this->cols not set

		echo $this->params['head_end'];
	}


	/**
	 *
	 */
	function display_body_start()
	{
		echo $this->params['body_start'];

		$this->displayed_lines_count = 0;

	}


	/**
	 *
	 */
	function display_body_end()
	{
		echo $this->params['body_end'];
	}


	/**
	 *
	 */
	function display_line_start( $is_last = false, $is_fadeout_line = false )
	{
		if( $this->displayed_lines_count % 2 )
		{ // Odd line:
			if( $is_last )
				$start_tag = $this->params['line_start_odd_last'];
			else
				$start_tag = $this->params['line_start_odd'];
		}
		else
		{ // Even line:
			if( $is_last )
				$start_tag = $this->params['line_start_last'];
			else
				$start_tag = $this->params['line_start'];
		}

		if( $is_fadeout_line )
		{	// Add css class for this row to highlight:
			$start_tag = update_html_tag_attribs( $start_tag, array( 'class' => 'evo_highlight' ) );
		}

		echo $start_tag;

		$this->displayed_cols_count = 0;
	}


	/**
	 *
	 */
	function display_line_end()
	{
		echo $this->params['line_end'];

		$this->displayed_lines_count ++;
	}


	/**
	 * Start a column (data).
	 *
	 * @param array Additional attributes for the <td> tag (attr_name => attr_value).
	 * @param object|NULL Current row data (The var $row is requested inside of $this->parse_col_content() )
	 */
	function display_col_start( $extra_attr = array(), $row = NULL )
	{
		// Get colum definitions for current column:
		$col = $this->cols[$this->displayed_cols_count];

		if( isset( $col['td_class'] ) )
		{	// We have a class for the total column
			$class = $col['td_class'];
		}
		else
		{	// We have no class for the total column
			$class = '';
		}

		if( ($this->displayed_cols_count == 0) && isset($this->params['col_start_first']) )
		{ // Display first column column start:
			$output = $this->params['col_start_first'];
			// Add the total column class in the col start first param class:
			$output = str_replace( '$class$', $class, $output );
		}
		elseif( ( $this->displayed_cols_count == count($this->cols)-1) && isset($this->params['col_start_last']) )
		{ // Last column can get special formatting:
			$output = $this->params['col_start_last'];
			// Add the total column class in the col start end param class:
			$output = str_replace( '$class$', $class, $output );
		}
		else
		{ // Display regular colmun start:
			$output = $this->params['col_start'];
			// Replace the "class_attrib" in the total col start param by the td column class
			$output = str_replace( '$class_attrib$', 'class="'.$class.'"', $output );
		}

		if( isset( $col['td_colspan'] ) )
		{	// Initialize colspan attribute:
			if( method_exists( $this, 'parse_col_content' ) )
			{
				$colspan = $this->parse_col_content( $col['td_colspan'] );
				$colspan = eval( "return '$colspan';" );
			}
			else
			{
				$colspan = $col['td_colspan'];
			}
			if( $colspan < 0 )
			{	// We want to substract columns from the total count:
				$colspan = $this->nb_cols + $colspan;
			}
			elseif( $colspan == 0 )
			{	// Use a count of columns:
				$colspan = $this->nb_cols;
			}
			$colspan = intval( $colspan );
			if( $colspan === 1 )
			{	// Don't create attribute "colspan" with value "1":
				$output = str_replace( '$colspan_attrib$', '', $output );
			}
			else
			{
				$output = str_replace( '$colspan_attrib$', 'colspan="'.$colspan.'"', $output );
				// Store current colspan value in order to skip next columns:
				$this->current_colspan = $colspan;
			}
		}
		else
		{	// Remove non-HTML attrib:
			$output = str_replace( '$colspan_attrib$', '', $output );
		}

		// Custom attributes:
		// Tblue> TODO: Make this more elegant (e. g.: replace "$extra_attr$" with the attributes string).
		if( $extra_attr )
		{
			if ( ! isset ($extra_attr['format_to_output']))
			{
				$output = substr( $output, 0, -1 ).get_field_attribs_as_string( $extra_attr ).'>';
			}
			else
			{
				$format_to_output = $extra_attr['format_to_output'];
				unset($extra_attr['format_to_output']);
				$output = substr( $output, 0, -1 ).get_field_attribs_as_string( $extra_attr, $format_to_output ).'>';


			}

		}
		// Check variables in column declaration:
		$output = $this->parse_class_content( $output );
		echo $output;
	}


  /**
	 *
	 */
	function display_col_end()
	{
		echo $this->params['col_end'];

		$this->displayed_cols_count ++;
	}


	/**
	 * Widget callback for template vars.
	 *
	 * This allows to replace template vars, see {@link Widget::replace_callback()}.
	 *
	 * @return string
	 */
	function replace_callback( $matches )
	{
		// echo '['.$matches[1].']';
		switch( $matches[1] )
		{
			case 'reset_filters_button':
				// Resetting the filters is the same as applying preset 'all' (should be defined for all)
				if( !isset($this->filter_area['presets']['all'] ) )
				{	// Preset "all" not defined, we don't know how to reset.
					return '';
				}
				if( empty($this->current_filter_preset) || $this->current_filter_preset == 'all' )
				{ // No filters applied:
					return '';
				}
				return '<a href="'.$this->filter_area['presets']['all'][1].'" class="btn btn-sm btn-warning">'.get_icon('reset_filters').T_('Remove filters').'</a>';

			case 'nb_cols' :
				// Number of columns in result:
				if( !isset($this->nb_cols) )
				{
					$this->nb_cols = count($this->cols);
				}
				return $this->nb_cols;

			default :
				return parent::replace_callback( $matches );
		}
	}

	/**
	 * Handle variable subtitutions for class column contents.
	 *
	 * This is one of the key functions to look at when you want to use the Results class.
	 * - #var#
	 */
	function parse_class_content( $content )
	{
		// Make variable substitution for RAWS:
		while (preg_match('!\# (\w+) \#!ix', $content, $matchesarray))
		{ // Replace all matches to the content of the current row's cell. That means that several variables can be inserted to the class.
			if (! empty($this->rows[$this->current_idx]->{$matchesarray[1]}))
			{
				$content = str_replace($matchesarray[0],$this->rows[$this->current_idx]->{$matchesarray[1]} , $content);
			}
			else
			{
				$content = str_replace($matchesarray[0], 'NULL' , $content);
			}
		}

		while (preg_match('#% (.+?) %#ix', $content, $matchesarray))
		{
			 eval('$result = '.$matchesarray[1].';');
			 $content = str_replace($matchesarray[0],$result, $content);
		}

		return $content;
	}


	/**
	 * Init results params from skin template params. It's used when Results table is filled from ajax result.
	 *
	 * @param string the template param which can have values( 'admin', 'front' )
	 * @param string the name of the skin
	 */
	function init_params_by_skin( $skin_type, $skin_name )
	{
		switch( $skin_type )
		{
			case 'admin': // admin skin type
				global $adminskins_path;
				require_once $adminskins_path.$skin_name.'/_adminUI.class.php';
				$this->params = AdminUI::get_template( 'Results' );
				break;

			case 'front': // front office skin type
				global $skins_path;
				require_once $skins_path.$skin_name.'/_skin.class.php';
				$this->params = Skin::get_template( 'Results' );
				break;

			default:
				debug_die( 'Invalid results template param!' );
		}
	}

}

?>