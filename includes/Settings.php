<?php

namespace WPTM;

class Settings {

	/**
	 * @var
	 */
	protected static $_instance;

	public static function get_instance() {
		if ( ! self::$_instance instanceof Settings ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'cmb2_admin_init', [ $this, 'register_options' ] );
		add_action( 'cmb2_admin_init', [ $this, 'clean_db' ] );
		add_filter( 'list_terms_exclusions', [ $this, 'exclude_terms' ], 10, 2 );
		add_action( 'admin_footer', [ $this, 'admin_scripts' ] );
	}

	/**
	 * Register settings page and fields
	 */
	public function register_options() {
		$primary = [
			'id'           => 'wptm_settings',
			'menu_title'   => __( 'WP Term Manager', 'wptm' ),
			'object_types' => [ 'options-page' ],
			'option_key'   => 'wptm_settings',
			'parent_slug'  => 'tools.php'
		];

		$primary_options = new_cmb2_box( $primary );

		$primary_options->add_field(
			[
				'name'          => __( 'Exclude Terms', 'wptm' ),
				'desc'          => __( 'Choose which terms to hide from being displayed.', 'wptm' ),
				'type'          => 'select',
				'id'            => 'exclude',
				'render_row_cb' => [ $this, 'exclude_field' ]
			]
		);

		$primary_options->add_field(
			[
				'name' => __( 'Remove Plugin', 'wptm' ),
				'desc' => __( 'Remove all plugin data from database on uninstall.', 'wptm' ),
				'type' => 'checkbox',
				'id'   => 'clean_db'
			]
		);
	}

	/**
	 * Display field in settings
	 *
	 * @param $field_args
	 * @param $field
	 */
	public function exclude_field( $field_args, $field ) {
		$id          = $field_args['id'];
		$label       = $field_args['name'];
		$name        = $field_args['_name'];
		$description = $field_args['description'];
		$value       = $field->escaped_value();
		$options     = $this->exclude_options();
		?>
        <div class="cmb-row cmb-type-select">
            <div class="cmb-th">
                <label for="<?php esc_attr( $id ) ?>"><?php esc_attr__( $label, 'wptm' ) ?></label>
                <p class="description">
					<?php esc_html_e( 'Hold Ctrl to select individual terms.', 'wptm' ); ?><br/>
					<?php esc_html_e( 'Hold Shift to select multiple terms.', 'wptm' ); ?><br/>
					<?php esc_html_e( 'Press Ctrl + A to select all terms.', 'wptm' ); ?>
                </p>
            </div>
            <div class="cmb-td">
                <select id="<?php echo $id; ?>" class="cmb2_select" name="<?php echo $name; ?>[]"
                        value="<?php echo $value; ?>" multiple>
					<?php foreach ( $options as $key => $option ) {
						if ( ! is_numeric( $key ) ) { ?>
                            <option class="disabled" disabled><?php echo $option ?></option>
						<?php } else { ?>
                            <option value="<?php echo $key ?>" <?php if ( is_array( $value ) && in_array( $key, $value ) ) {
								echo 'selected';
							} ?>><?php echo $option ?></option>
						<?php }
					} ?>
                </select>
                <p class="description"><?php echo $description; ?></p>
            </div>
        </div>
		<?php
	}

	/**
	 * Return array of terms for settings
	 *
	 * @return array
	 */
	public function exclude_options() {
		$taxonomies = get_taxonomies();
		$args       = [
			'taxonomy'   => $taxonomies,
			'hide_empty' => false,
			'orderby'    => 'term_group'
		];

		$terms = get_terms( $args );

		$term_ids = [];
		$tax      = [];

		foreach ( $terms as $term ) {
			if ( ! in_array( $term->taxonomy, $tax ) ) {
				$term_ids[ $term->taxonomy ] = $term->taxonomy;
				$tax[]                       = $term->taxonomy;
			}
			$term_ids[ $term->term_id ] = $term->name;
		}

		return $term_ids;

	}

	/**
	 * Exclude terms
	 *
	 * @param $exclusions
	 * @param $args
	 *
	 * @return string|void
	 */
	public function exclude_terms( $exclusions, $args ) {
		
		if ( ! is_admin() ) {
			return;
		}

		$screen = get_current_screen();

		if ( 'tools_page_wptm_settings' === $screen->id ) {
			return;
		}

		$settings = get_option( 'wptm_settings' );
		$terms    = $settings['exclude'];

		if ( is_array( $terms ) ) {
			$terms = implode( ',', array_map( 'intval', $terms ) );
		}

		if ( ! empty( $terms ) ) {
			$exclusions = 't.term_id NOT IN (' . $terms . ')';
		}

		return $exclusions;
	}

	public function clean_db() {
		$settings = get_option( 'wptm_settings' );
		$option   = $settings['clean_db'];

		if ( 'on' !== $option ) {
			return;
		}

		delete_option( 'wptm_settings' );
	}

	/**
	 * Styles for settings and more importantly for removing
	 * categories in the Gutenberg Editor
	 */
	public function admin_scripts() {
		$settings = get_option( 'wptm_settings' );
		$terms    = $settings['exclude'];
		$screen   = get_current_screen();
		?>
        <style type="text/css">

            <?php if ( 'tools_page_wptm_settings' == $screen->id ) { ?>

            #exclude {
                height: 200px;
            }

            #exclude option.disabled {
                font-weight: bold;
                text-transform: capitalize;
            }

            <?php } if ( $screen->is_block_editor ) { foreach( $terms as $term ) { ?>

            label[for="editor-post-taxonomies-hierarchical-term-<?php echo $term ?>"],
            #editor-post-taxonomies-hierarchical-term-<?php echo $term ?> {
                display: none;
            }

            <?php } } ?>
        </style>
	<?php }

}