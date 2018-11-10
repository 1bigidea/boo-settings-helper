<?php

/**
 * Boo Settings API helper class
 *
 * @version 1.0
 *
 * @author RaoAbid | BooSpot
 * @link https://github.com/boospot/boo-settings-helper
 */
if ( ! class_exists( 'Boo_Settings_Helper' ) ):

	class Boo_Settings_Helper {

		public $debug = true;

		public $text_domain = 'boo-helper';

		public $plugin_basename = '';

		public $action_links = array();

		public $config_menu = array();

		public $field_types = array();

		protected $is_tabs = false;

		protected $options_id;

		protected $is_simple_options;

		/**
		 * settings sections array
		 *
		 * @var array
		 */
		protected $settings_sections = array();

		/**
		 * Settings fields array
		 *
		 * @var array
		 */
		protected $settings_fields = array();

		public function __construct( $config_array = null ) {

			if ( ! empty( $config_array ) ) {

				$this->set_properties( $config_array );

			}

//			$this->setup_hooks();


		}


		public function setup_hooks() {

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		}

		protected function get_default_config() {

			return array(
				'tabs'           => false,
				'simple_options' => false,

			);

		}

		/**
		 * Set Properties of the class
		 */
		protected function set_properties( array $config_array ) {

			// Normalise config array
			$config_array = wp_parse_args( $config_array, $this->get_default_config() );

			if ( $config_array['tabs'] ) {
				$this->is_tabs = true;
			}

			if ( isset( $config_array['options_id'] ) ) {
				$this->options_id = (string) $config_array['options_id'];
			}

			if ( isset( $config_array['simple_options'] ) ) {
				$this->is_simple_options = (bool) $config_array['simple_options'];
			}

			if ( isset( $config_array['tabs'] ) ) {
				$this->set_tabs( $config_array['tabs'] );
			}

			// Do we have menu config, if yes, call the method
			if ( isset( $config_array['menu'] ) ) {
				$this->set_menu( $config_array['menu'] );
			}

			// Do we have sections config, if yes, call the method
			if ( isset( $config_array['sections'] ) ) {
				$this->set_sections( $config_array['sections'] );
			}

			// Do we have fields config, if yes, call the method
			if ( isset( $config_array['fields'] ) ) {
				$this->set_fields( $config_array['fields'] );
			}

			if ( isset( $config_array['links'] ) ) {
				$this->set_links( $config_array['links'] );
			}


		}

		public function set_links( array $config_links ) {

			if (
				isset( $config_links['plugin_basename'] ) &&
				! empty( $config_links['plugin_basename'] )
			) {
				$this->plugin_basename = $config_links['plugin_basename'];
				$this->action_links    = isset( $config_links['action_links'] ) ? $config_links['action_links'] : true;

				$prefix = is_network_admin() ? 'network_admin_' : '';

				add_filter(
					"{$prefix}plugin_action_links_{$this->plugin_basename}",
					array( $this, 'plugin_action_links' ),
					10, // priority
					4   // parameters
				);
			}

		}

		public function get_default_settings_url() {

			$options_base_file_name = $this->config_menu['submenu'] ? $this->config_menu['parent'] : 'admin.php';

			return admin_url( "{$options_base_file_name}?page={$this->config_menu['slug']}" );

		}

		public function get_default_settings_link() {

			return array(
				'<a href="' . $this->get_default_settings_url() . '">' . __( 'Settings', $this->text_domain ) . '</a>',
			);

		}

		/**
		 * Register "settings" for plugin option page in plugins list
		 *
		 * @param array $links plugin links
		 *
		 * @return array possibly modified $links
		 */
		public function plugin_action_links( $links, $plugin_file, $plugin_data, $context ) {
			/**
			 *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
			 */

			// BOOL of settings is given true | false
			if ( is_bool( $this->action_links ) ) {

				// FALSE: If it is false, no need to go further
				if ( ! $this->action_links ) {
					return $links;
				}
				// TRUE: if Settings link is not defined, lets create one
				if ( $this->action_links ) {
					return array_merge( $this->get_default_settings_link(), $links );
				}

			} // if ( is_bool( $this->config['settings_link'] ) )


			// Admin URL of settings is given
			if ( ! is_bool( $this->action_links ) && ! is_array( $this->action_links ) ) {

				$settings_link = array(
					'<a href="' . admin_url( esc_url( $this->action_links ) ) . '">' . __( 'Settings', $this->text_domain ) . '</a>',
				);

				return array_merge( $settings_link, $links );
			}

			// Array of settings_link is given
			if ( is_array( $this->action_links ) ) {

				$settings_link_array = array();

				foreach ( $this->action_links as $link ) {

					$link_text         = isset( $link['text'] ) ? sanitize_text_field( $link['text'] ) : __( 'Settings', '' );
					$link_url_un_clean = isset( $link['url'] ) ? $link['url'] : '#';

					$link_type = isset( $link['type'] ) ? sanitize_key( $link['type'] ) : 'default';

					switch ( $link_type ) {
						case ( 'external' ):
							$link_url = esc_url_raw( $link_url_un_clean );
							break;

						case ( 'internal' ):
							$link_url = admin_url( esc_url( $link_url_un_clean ) );
							break;

						default:

							$link_url = $this->get_default_settings_url();

					}

					$settings_link_array[] = '<a href="' . $link_url . '">' . $link_text . '</a>';

				}

				return array_merge( $settings_link_array, $links );


			} // if (  $this->action_links ) )

			// if nothing is returned so far, return original $links
			return $links;

		}


		public function set_tabs( $config_tabs ) {
			$this->is_tabs = ( (bool) $config_tabs ) ? true : false;
		}

		/**
		 * Register plugin option page
		 */
		public function set_menu( $config_menu ) {

			$this->config_menu = array_merge_recursive( $this->config_menu, wp_parse_args( $config_menu, $this->get_default_config_menu() ) );

//			$this->config_menu = wp_parse_args( $config_menu, $this->get_default_config_menu() );

			$this->config_menu['slug'] = isset( $this->config_menu['slug'] ) ? $this->config_menu['slug'] : sanitize_title( $config_menu['page_title'] );
//			add_menu_page( 'test', 'test', 'manage_options', 'test', array( $this, 'display_page' ));

			// Is it a main menu or sub_menu
			if ( ! $this->config_menu['submenu'] ) {

				add_menu_page(
					$this->config_menu['page_title'],
					$this->config_menu['menu_title'],
					$this->config_menu['capability'],
					$this->config_menu['slug'], //slug
					array( $this, 'display_page' ),
					$this->config_menu['icon'],
					$this->config_menu['position']
				);


			} else {

				add_submenu_page(
					$this->config_menu['parent'],
					$this->config_menu['page_title'],
					$this->config_menu['page_title'],
					$this->config_menu['capability'],
					$this->config_menu['slug'], // slug
					array( $this, 'display_page' )
				);

			}

		}

		/**
		 * Get default config for menu
		 * @return array $default
		 */
		public function get_default_config_menu() {

			return apply_filters( 'boo_settings_filter_default_menu_array', array(
				//The name of this page
				'page_title' => __( 'Plugin Options', $this->text_domain ),
				// //The Menu Title in Wp Admin
				'menu_title' => __( 'Plugin Options', $this->text_domain ),
				// The capability needed to view the page
				'capability' => 'manage_options',
				// dashicons id or url to icon
				// https://developer.wordpress.org/resource/dashicons/
				'icon'       => '',
				// Required for submenu
				'submenu'    => false,
				// position
				'position'   => 100,
				// For sub menu, we can define parent menu slug (Defaults to Options Page)
				'parent'     => 'options-general.php',
			) );

		}


		//DEBUG
		public function write_log( $type, $log_line ) {

			$hash        = '';
			$fn          = plugin_dir_path( __FILE__ ) . '/' . $type . '-' . $hash . '.log';
			$log_in_file = file_put_contents( $fn, date( 'Y-m-d H:i:s' ) . ' - ' . $log_line . PHP_EOL, FILE_APPEND );

		}


		/*
		 * @return array configured field types
		 */
		public function get_field_types() {

			foreach ( $this->settings_fields as $sections_fields ) {
				foreach ( $sections_fields as $field ) {
					$this->field_types[] = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
				}
			}

			return array_unique( $this->field_types );
		}


		/**
		 * @return bool true if its menu options
		 */
		protected function is_menu_page_loaded() {

			$current_screen = get_current_screen();

			return substr( $current_screen->id, - strlen( $this->config_menu['slug'] ) ) === $this->config_menu['slug'];

		}

		/**
		 * Enqueue scripts and styles
		 */
		function admin_enqueue_scripts() {

			// Conditionally Load scripts and styles for field types configured

			// Load scripts for only plugin menu page
			if ( ! $this->is_menu_page_loaded() ) {
				return null;
			}

			// Load Color Picker if required
			if ( in_array( 'color', $this->get_field_types() ) ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
			}


			wp_enqueue_media();

			wp_enqueue_script( 'jquery' );

		}

		/**
		 * Set settings sections
		 *
		 * @param array $sections setting sections array
		 */
		function set_sections( array $sections ) {

			$this->settings_sections = array_merge_recursive( $this->settings_sections, $sections );

			return $this;
		}

		/**
		 * Add a single section
		 *
		 * @param array $section
		 */
		public function add_section( $section ) {
			$this->settings_sections[] = $section;

			return $this;
		}

		/**
		 * Set settings fields
		 *
		 * @param array $fields settings fields array
		 */
		public function set_fields( $fields ) {
			$this->settings_fields = array_merge_recursive( $this->settings_fields, $fields );
			$this->setup_hooks();

			return $this;
		}


		function add_field( $section, $field ) {
			$defaults = array(
				'name'  => '',
				'label' => '',
				'desc'  => '',
				'type'  => 'text'
			);

			$arg = wp_parse_args( $field, $defaults );

			$this->settings_fields[ $section ][] = $arg;

			return $this;
		}

		/**
		 * Initialize and registers the settings sections and fileds to WordPress
		 *
		 * Usually this should be called at `admin_init` hook.
		 *
		 * This function gets the initiated settings sections and fields. Then
		 * registers them to WordPress and ready for use.
		 */
		function admin_init() {
			//register settings sections
			foreach ( $this->settings_sections as $section ) {
				if ( false == get_option( $section['id'] ) ) {
					add_option( $section['id'] );
				}

				if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
					$section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
					$callback        = function () use ( $section ) {
						echo str_replace( '"', '\"', $section['desc'] );
					};
				} else if ( isset( $section['callback'] ) ) {
					$callback = $section['callback'];
				} else {
					$callback = null;
				}

				add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
			}

			//register settings fields
			foreach ( $this->settings_fields as $section => $field ) {
				foreach ( $field as $option ) {

					$name     = $option['name'];
					$type     = isset( $option['type'] ) ? $option['type'] : 'text';
					$label    = isset( $option['label'] ) ? $option['label'] : '';
					$callback = isset( $option['callback'] ) ? $option['callback'] : array(
						$this,
						'callback_' . $type
					);

					$args = array(
						'id'                => $name,
						'class'             => isset( $option['class'] ) ? $option['class'] : $name,
						'label_for'         => "{$section}[{$name}]",
						'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
						'name'              => $label,
						'section'           => $section,
						'size'              => isset( $option['size'] ) ? $option['size'] : null,
						'options'           => isset( $option['options'] ) ? $option['options'] : '',
						'std'               => isset( $option['default'] ) ? $option['default'] : '',
						'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
						'type'              => $type,
						'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
						'min'               => isset( $option['min'] ) ? $option['min'] : '',
						'max'               => isset( $option['max'] ) ? $option['max'] : '',
						'step'              => isset( $option['step'] ) ? $option['step'] : '',
						'options_id'        => ! empty( $this->options_id ) ? $this->options_id : str_replace( '-', '_', $this->config_menu['slug'] ),
					);
//
//					// Update Property
//					$this->field_types[] = $args['type'];

					add_settings_field( "{$section}[{$name}]", $label, $callback, $section, $section, $args );
				}
			}

			// creates our settings in the options table
			foreach ( $this->settings_sections as $section ) {
				register_setting( $this->options_id, $this->options_id, array( $this, 'sanitize_options' ) );
			}

//			$this->var_dump( $this->settings_fields); die();

		}

		/**
		 * Get field description for display
		 *
		 * @param array $args settings field args
		 */
		public function get_field_description( $args ) {
			if ( ! empty( $args['desc'] ) ) {
				$desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
			} else {
				$desc = '';
			}

			return $desc;
		}

//		public function is_sections() {
////			return true;
////		}
////
////		public function is_sections_in_name() {
////			return ( $this->is_sections() && $this->is_simple_options ) ? true : false;
////		}

		public function get_field_name( $options_id, $section, $field_id ) {

			$section_part = ! $this->is_simple_options ? "[" . $section . "]" : '';

			return "$options_id" . $section_part . "[" . $field_id . "]";

		}

		/**
		 * Displays a text field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_text( $args ) {
			$name        = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'text';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%7$s" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $name );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a url field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_url( $args ) {
			$this->callback_text( $args );
		}

		/**
		 * Displays a number field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_number( $args ) {
			$name        = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'number';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
			$min         = ( $args['min'] == '' ) ? '' : ' min="' . $args['min'] . '"';
			$max         = ( $args['max'] == '' ) ? '' : ' max="' . $args['max'] . '"';
			$step        = ( $args['step'] == '' ) ? '' : ' step="' . $args['step'] . '"';

			$html = sprintf( '<input type="%1$s" class="%2$s-number" id="%3$s[%4$s]" name="%10$s" value="%5$s"%6$s%7$s%8$s%9$s/>', $type, $size, $args['section'], $args['id'], $value, $placeholder, $min, $max, $step, $name );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a checkbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_checkbox( $args ) {
			$name  = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

			$html = '<fieldset>';
			$html .= sprintf( '<label for="wpuf-%1$s[%2$s]">', $args['section'], $args['id'] );
			$html .= sprintf( '<input type="hidden" name="%1$s" value="off" />', $name, $args['id'] );
			$html .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s]" name="%4$s" value="on" %3$s />', $args['section'], $args['id'], checked( $value, 'on', false ), $name );
			$html .= sprintf( '%1$s</label>', $args['desc'] );
			$html .= '</fieldset>';

			echo $html;
		}

		/**
		 * Displays a multicheckbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_multicheck( $args ) {
			$name  = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$html  = '<fieldset>';
			$html  .= sprintf( '<input type="hidden" name="%3$s" value="" />', $args['section'], $args['id'], $name );
			foreach ( $args['options'] as $key => $label ) {
				$checked = isset( $value[ $key ] ) ? $value[ $key ] : '0';
				$html    .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
				$html    .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%5$s[%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ), $name );
				$html    .= sprintf( '%1$s</label><br>', $label );
			}

			$html .= $this->get_field_description( $args );
			$html .= '</fieldset>';

			echo $html;
		}

		/**
		 * Displays a radio button for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_radio( $args ) {
			$name  = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$html  = '<fieldset>';

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
				$html .= sprintf( '<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%5$s" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ), $name );
				$html .= sprintf( '%1$s</label><br>', $label );
			}

			$html .= $this->get_field_description( $args );
			$html .= '</fieldset>';

			echo $html;
		}

		/**
		 * Displays a selectbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_select( $args ) {
			$name  = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$html  = sprintf( '<select class="%1$s" name="%4$s" id="%2$s[%3$s]">', $size, $args['section'], $args['id'], $name );

			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
			}

			$html .= sprintf( '</select>' );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a textarea for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_textarea( $args ) {
			$name        = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value       = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%6$s"%4$s>%5$s</textarea>', $size, $args['section'], $args['id'], $placeholder, $value, $name );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays the html for a settings field
		 *
		 * @param array $args settings field args
		 *
		 * @return string
		 */
		function callback_html( $args ) {
			echo $this->get_field_description( $args );
		}

		/**
		 * Displays a rich text textarea for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_wysiwyg( $args ) {
			$name  = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : '500px';

			echo '<div style="max-width: ' . $size . ';">';

			$editor_settings = array(
				'teeny'         => true,
				'textarea_name' => $name,
				'textarea_rows' => 10
			);

			if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
				$editor_settings = array_merge( $editor_settings, $args['options'] );
			}

			wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

			echo '</div>';

			echo $this->get_field_description( $args );
		}

		/**
		 * Displays a file upload field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_file( $args ) {
			$name  = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$id    = $args['section'] . '[' . $args['id'] . ']';
			$label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File' );

			$html = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%5$s" value="%4$s"/>', $size, $args['section'], $args['id'], $value, $name );
			$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a password field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_password( $args ) {
			$name  = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%5$s" value="%4$s"/>', $size, $args['section'], $args['id'], $value, $name );
			$html .= $this->get_field_description( $args );

			echo $html;
		}

		/**
		 * Displays a color picker field for a settings field
		 *
		 * @param array $args settings field args
		 */
		function callback_color( $args ) {
			$name  = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html = sprintf( '<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s[%3$s]" name="%6$s" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std'], $name );
			$html .= $this->get_field_description( $args );

			echo $html;
		}


		/**
		 * Displays a select box for creating the pages select box
		 *
		 * @param array $args settings field args
		 */
		function callback_pages( $args ) {
			$name          = $this->get_field_name( $args['options_id'], $args['section'], $args['id'] );
			$dropdown_args = array(
				'selected' => esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) ),
				'name'     => $args['section'] . '[' . $args['id'] . ']',
				'id'       => $args['section'] . '[' . $args['id'] . ']',
				'echo'     => 0
			);
			$html          = wp_dropdown_pages( $dropdown_args );
			echo $html;
		}

		/**
		 * Sanitize callback for Settings API
		 *
		 * @return mixed
		 */
		function sanitize_options( $posted_data ) {

			$saved_tab_key     = array_shift( array_keys( $posted_data ) );
			$db_options        = get_option( $this->options_id );
			$preserved_options = array();
			$clean_data        = array();

			// preserve other tabs options if its not simple_options
			if ( ! $this->is_simple_options ) {
				foreach ( $db_options as $tab_key => $tab_options ) {

					if ( ! in_array( $tab_key, $saved_tab_key ) ) {
						$preserved_options[ $tab_key ] = $tab_options;
					}
				}

			}

			$this->write_log( 'sanitize_options', var_export( $_POST, true ) . PHP_EOL );
			$this->write_log( 'sanitize_options', var_export( $posted_data, true ) . PHP_EOL );

			if ( ! $posted_data ) {
				return $preserved_options;
			}

			if(!$this->is_simple_options){
				$posted_data = array_shift( $posted_data );
            }

			foreach ( $posted_data as $option_slug => $option_value ) {
				$sanitize_callback = $this->get_sanitize_callback( $option_slug );

				// If callback is set, call it
				if ( $sanitize_callback ) {
					$clean_data[$option_slug] = call_user_func( $sanitize_callback, $option_value );
					continue;
				} else {
					$clean_data[$option_slug] = $option_value;
                }
			}



			if( ! $this->is_simple_options){
			    $preserved_options[$saved_tab_key] = $clean_data;
				$clean_data = $preserved_options;
            }

			$this->write_log( 'sanitize_options', var_export( $clean_data, true ) . PHP_EOL );

			return $clean_data;

		}

		/**
		 * Get sanitization callback for given option slug
		 *
		 * @param string $slug option slug
		 *
		 * @return mixed string or bool false
		 */
		function get_sanitize_callback( $slug = '' ) {
			if ( empty( $slug ) ) {
				return false;
			}

			// Iterate over registered fields and see if we can find proper callback
			foreach ( $this->settings_fields as $section => $options ) {
				foreach ( $options as $option ) {
					if ( $option['name'] != $slug ) {
						continue;
					}

					// Return the callback name
					return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
				}
			}

			return false;
		}

		/**
		 * Get the value of a settings field
		 *
		 * @param string $option settings field name
		 * @param string $section the section name this field belongs to
		 * @param string $default default text if it's not found
		 *
		 * @return string
		 */
		function get_option( $option, $section, $default = '' ) {

			$db_options = get_option( $this->options_id );

			if ( $this->is_simple_options ) {
				return ( isset( $db_options[ $option ] ) ) ? $db_options[ $option ] : $default;
			}

			if ( isset( $db_options[ $section ][ $option ] ) ) {
				return $db_options[ $section ][ $option ];
			}

			return $default;
		}


		function display_page() {
			echo '<div class="wrap">';

			if ( $this->debug ) {
				echo "<b>TYPES of fields</b>";
				$this->var_dump_pretty( $this->get_field_types() );

				echo "<b>Options Array</b>";
				$this->var_dump_pretty( get_option( $this->options_id ) );
			}


//			if ( $this->is_tabs ) {
//				$this->show_navigation();
//			}
//			$this->show_navigation();
			$this->show_forms();

			echo '</div>';

		}


		/**
		 * Show navigations as tab
		 *
		 * Shows all the settings section labels as tab
		 */
		function show_navigation() {
			$settings_page = $this->get_default_settings_url();

			$count = count( $this->settings_sections );

			// don't show the navigation if only one section exists
			if ( $count === 1 ) {
				return;
			}

			$active_tab = ( isset( $_GET['tab'] ) ) ? sanitize_key( $_GET['tab'] ) : $this->settings_sections[0]['id'];

			$html = '<h2 class="nav-tab-wrapper">';

			foreach ( $this->settings_sections as $tab ) {
				$active_class = ( $tab['id'] == $active_tab ) ? 'nav-tab-active' : '';
				$html         .= sprintf( '<a href="%3$s&tab=%1$s" class="nav-tab %4$s" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'], $settings_page, $active_class );
			}

			$html .= '</h2>';

			echo $html;
		}


		public function var_dump_pretty( $var ) {
			echo "<pre>";
			var_dump( $var );
			echo "</pre>";
		}

		function tabbed_sections() {

			$active_tab = ( isset( $_GET['tab'] ) ) ? sanitize_key( $_GET['tab'] ) : $this->settings_sections[0]['id'];

			foreach ( $this->settings_sections as $section ) {

				// Dont out put fields if its not the right section/tab
				if ( $active_tab != $section['id'] ) {
					continue;
				}

				?>
                <div class="metabox-holder">

					<?php
					if ( $this->is_tabs ) {
						$this->show_navigation();
					}
					?>
                    <form method="post" action="options.php">
						<?php
						do_action( 'wsa_form_top_' . $section['id'], $section );
						settings_fields( $this->options_id );
						do_settings_sections( $section['id'] );
						do_action( 'wsa_form_bottom_' . $section['id'], $section );
						if ( isset( $this->settings_fields[ $section['id'] ] ) ):
							?>
                            <div style="padding-left: 10px">
								<?php submit_button(); ?>
                            </div>
						<?php endif; ?>
                    </form>
                </div>


			<?php } // end foreach
		}

		function tabless_sections() {
			?>

            <div class="metabox-holder">
                <form method="post" action="options.php">
					<?php foreach ( $this->settings_sections as $section ) : ?>
                        <div id="<?php echo $section['id']; ?>">
							<?php
							//                            $this->var_dump_pretty( get_option( $section['id']));

							//							do_action( 'wsa_form_top_' . $section['id'], $section );
							settings_fields( $section['id'] );
							do_settings_sections( $section['id'] );
							//							do_action( 'wsa_form_bottom_' . $section['id'], $section );
							?>
                        </div>
					<?php endforeach; ?>
                    <div style="padding-left: 10px">
						<?php submit_button(); ?>
                    </div>
                </form>
            </div>

			<?php


		}


		/**
		 * Show the section settings forms
		 *
		 * This function displays every sections in a different form
		 */
		function show_forms() {

//			( $this->is_tabs ) ? $this->tabbed_sections() : $this->tabless_sections();

			$active_tab = ( isset( $_GET['tab'] ) ) ? sanitize_key( $_GET['tab'] ) : $this->settings_sections[0]['id'];

			?>
            <div class="metabox-holder">
				<?php
				if ( $this->is_tabs ) {
					$this->show_navigation();
				}
				?>
                <form method="post" action="options.php">
					<?php
					settings_fields( $this->options_id );

					foreach ( $this->settings_sections as $section ) :

						// Dont out put fields if its not the right section/tab
						if ( $active_tab != $section['id'] ) {
							continue;
						}

						do_settings_sections( $section['id'] );


					endforeach; // end foreach
					?>
                    <div style="padding-left: 10px">
						<?php submit_button(); ?>
                    </div>

                </form>
            </div>
			<?php

			$this->script_general();

		}


		/**
		 * Tabbable JavaScript codes & Initiate Color Picker
		 *
		 * This code uses localstorage for displaying active tabs
		 */
		function script_general() {
			?>
            <script>
                jQuery(document).ready(function ($) {
                    //Initiate Color Picker
                    if ($('.wp-color-picker-field').length > 0) {
                        $('.wp-color-picker-field').wpColorPicker();
                    }


                    // For Files Upload
                    $('.wpsa-browse').on('click', function (event) {
                        event.preventDefault();

                        var self = $(this);

                        // Create the media frame.
                        var file_frame = wp.media.frames.file_frame = wp.media({
                            title: self.data('uploader_title'),
                            button: {
                                text: self.data('uploader_button_text'),
                            },
                            multiple: false
                        });

                        file_frame.on('select', function () {
                            attachment = file_frame.state().get('selection').first().toJSON();
                            self.prev('.wpsa-url').val(attachment.url).change();
                        });

                        // Finally, open the modal
                        file_frame.open();
                    });

                    $(function () {
                        var changed = false;

                        $('input, textarea, select, checkbox').change(function () {
                            changed = true;
                        });

                        $('.nav-tab-wrapper a').click(function () {
                            if (changed) {
                                window.onbeforeunload = function () {
                                    return "Changes you made may not be saved."
                                };
                            } else {
                                window.onbeforeunload = '';
                            }
                        });

                        $('.submit :input').click(function () {
                            window.onbeforeunload = '';
                        });
                    });

                });
            </script>
			<?php
//			$this->_style_fix();
		}

	}

endif;