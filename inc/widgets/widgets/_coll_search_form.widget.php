<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
class coll_search_form_Widget extends ComponentWidget
{
	var $icon = 'search';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_search_form' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'search-form-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Search Form');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display search form');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_( 'Title to display in your skin.' ),
					'size' => 40,
					'defaultvalue' => T_('Search'),
				),
				'button' => array(
					'label' => T_('Button name'),
					'note' => T_( 'Button name to submit a search form.' ),
					'size' => 40,
					'defaultvalue' => T_('Go'),
				),
				'blog_ID' => array(
					'label' => T_('Collection ID'),
					'note' => T_('Leave empty for current collection.'),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget uses the selected items:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		$params = array_merge( array(
				'search_input_before'   => '',
				'search_input_after'    => '',
				'search_submit_before'  => '',
				'search_submit_after'   => '',
				'show_advanced_options' => false
			), $params );

		parent::init_display( $params );
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Settings;

		$this->init_display( $params );

		$blog_ID = intval( $this->disp_params['blog_ID'] );
		if( $blog_ID > 0 )
		{ // Get Blog for widget setting
			$BlogCache = & get_BlogCache();
			$widget_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
		}
		if( empty( $widget_Blog ) )
		{ // Use current blog
			global $Collection, $Blog;
			$widget_Blog = & $Blog;
		}

		// Collection search form:
		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		form_formstart( $widget_Blog->gen_blogurl(), 'search', 'SearchForm' );

		if( empty( $this->disp_params['search_class'] ) )
		{ // Class name is not defined, Use class depend on serach options
			$search_form_class = 'compact_search_form';
		}
		else
		{ // Use class from params
			$search_form_class = $this->disp_params['search_class'];
		}

		echo '<div class="'.$search_form_class.'">';

		echo $this->disp_params['search_input_before'];
		echo '<input type="text" name="s" size="25" value="'.htmlspecialchars( get_param( 's' ) ).'" class="search_field SearchField form-control" title="'.format_to_output( T_('Enter text to search for'), 'htmlattr' ).'" />';
		echo $this->disp_params['search_input_after'];

		echo $this->disp_params['search_submit_before'];
		echo '<input type="submit" name="submit" class="search_submit submit btn btn-primary" value="'.format_to_output( $this->disp_params['button'], 'htmlattr' ).'" />';
		echo $this->disp_params['search_submit_after'];

		if( $this->disp_params['show_advanced_options'] )
		{
			echo '<div style="text-align: left; margin-top: 1em;" class="row">';
			echo '<div class="col-sm-12 col-md-12 col-lg-5" style="padding-right: 5px;">';
			echo '<input type="text" name="search_author" id="search_author" value="'.htmlspecialchars( param( 'search_author', 'string' ) )
					.'" class="form-control" title="'.format_to_output( T_('Enter author to search for' ), 'htmlattr' ).'"'
					.( ! is_logged_in() ? ' placeholder="'.T_('Any author' ).'"' : '' ).' />';
			echo '</div>';

			$search_date = param( 'search_date', 'string', 'anytime' );
			echo '<div class="col-sm-12 col-md-12 col-lg-4" style="padding: 0 5px;">';
			echo '<select name="search_date" id="search_date" class="form-control">';
			echo '<option value="week_ago"'.( $search_date == 'week_ago' ? ' selected' : '' ).'>'.T_('Less than a week').'</option>';
			echo '<option value="month_ago"'.( $search_date == 'month_ago' ? ' selected' : '' ).'>'.T_('Less than a month').'</option>';
			echo '<option value="year_ago"'.( $search_date == 'year_ago' ? ' selected' : '' ).'>'.T_('Less than a year').'</option>';
			echo '<option value="anytime"'.( $search_date == 'anytime' ? ' selected' : '' ).'>'.T_('Any time').'</option>';
			echo '</select>';
			echo '</div>';


			$item_type_options = array();
			if( $Blog->get_setting( 'search_include_posts' ) )
			{
				$item_type_options['item'] = T_('Posts');
			}
			if( $Blog->get_setting( 'search_include_cmnts' ) )
			{
				$item_type_options['comment'] = T_('Comments');
			}
			if( $Blog->get_setting( 'search_include_files' ) )
			{
				$item_type_options['file'] = T_('Files');
			}
			if( $Blog->get_setting( 'search_include_cats' ) )
			{
				$item_type_options['category'] = T_('Categories');
			}
			if( $Blog->get_setting( 'search_include_tags' ) )
			{
				$item_type_options['tag'] = T_('Tags');
			}

			if( count( $item_type_options ) > 1 )
			{
				$item_type_options['all'] = T_('All');

				$search_type = param( 'search_type', 'string', 'all' );
				echo '<div class="col-sm-12 col-md-12 col-lg-3" style="padding-left: 5px;">';
				echo '<select name="search_type" id="search_type" class="form-control">';
				foreach( $item_type_options as $key => $value )
				{
					echo '<option value="'.format_to_output( $key, 'htmlattr' ).'"'.( $search_type == $key ? ' selected' : '' ).'>'.$value.'</option>';
				}
				echo '</select>';
				echo '</div>';
			}
			echo '</div>';
			if( is_logged_in() )
			{
				$selected_author_array = param( 'search_author_array', 'array' );
				$selected_author = array();
				foreach( $selected_author_array as $field => $row )
				{
					foreach( $row as $key => $value )
					{
						$selected_author[$key][$field] = $value;
					}
				}
				?>
				<script type="text/javascript">
				jQuery( document ).ready( function()
				{
					jQuery( '#search_author' ).tokenInput(
						'<?php echo get_restapi_url(); ?>users/authors',
						{
							theme: 'facebook',
							queryParam: 'q',
							propertyToSearch: 'login',
							preventDuplicates: true,
							prePopulate: <?php echo evo_json_encode( $selected_author ) ?>,
							hintText: '<?php echo TS_('Type in a username') ?>',
							noResultsText: '<?php echo TS_('No results') ?>',
							searchingText: '<?php echo TS_('Searching...') ?>',
							jsonContainer: 'users',
							tokenFormatter: function( user )
							{
								return '<li>' +
										<?php echo $Settings->get( 'username_display' ) == 'name' ? 'user.fullname' : 'user.login';?> +
										'<input type="hidden" name="search_author_array[id][]" value="' + user.id + '" />' +
										'<input type="hidden" name="search_author_array[login][]" value="' + user.login + '" />' +
									'</li>';
							},
							resultsFormatter: function( user )
							{
								var title = user.login;
								if( user.fullname != null && user.fullname !== undefined )
								{
									title += '<br />' + user.fullname;
								}
								return '<li>' +
										user.avatar +
										'<div>' +
											title +
										'</div><span></span>' +
									'</li>';
							},
							onAdd: function()
							{
								if( this.tokenInput( 'get' ).length > 0 )
								{
									jQuery( '#token-input-search_author' ).attr( 'placeholder', '' );
								}
							},
							onDelete: function()
							{
								if( this.tokenInput( 'get' ).length === 0 )
								{
									jQuery( '#token-input-search_author' ).attr( 'placeholder', '<?php echo T_('Any author' ); ?>' ).css( 'width', '100%' );
								}
							},
							<?php
							if( param_has_error( 'search_author' ) )
							{ // Mark this field as error
							?>
							onReady: function()
							{
								jQuery( '.token-input-list-facebook' ).addClass( 'token-input-list-error' );
							}
							<?php } ?>
						} );
					<?php
					if( empty( $selected_author ) )
					{
						echo 'jQuery( "#token-input-search_author" ).attr( "placeholder", "'.format_to_output( T_('Any author' ), 'htmlattr' ).'" ).css( "width", "100%" );';
					}
					?>
				} );
				</script>
				<?php
			}
		}

		echo '</div>';

		echo '<input type="hidden" name="disp" value="search" />';

		echo '</form>';

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>