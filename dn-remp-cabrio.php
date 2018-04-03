<?php
/**
 * Plugin Name: DN REMP Cabrio
 * Plugin URI:  https://dennikn.sk/
 * Description: A/B tests for titles, featured images and lock position
 * Version:     1.0.0
 * Author:      Michal Rusina
 * Author URI:  http://michalrusina.sk/
 * License:     GPLv2
 */

if ( !defined( 'WPINC' ) ) {
	die;
}

class DN_Remp_Cabrio {
	private $plugin = 'dn_remp_cabrio';

	function __construct() {
		add_action( 'wp_footer', [ $this, 'wp_footer' ], 1 );
		add_action( 'save_post', [ $this, 'save_post' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );

		if ( !empty( get_option( 'dn_remp_cabrio_title_on' ) ) ) {
			add_action( 'edit_form_before_permalink', [ $this, 'edit_form_before_permalink' ] );
			add_action( 'the_title', [ $this, 'the_title' ], 1, 2 );
		}

		if ( !empty( get_option( 'dn_remp_cabrio_thumbnail_on' ) ) ) {
			add_action( 'init', [ $this, 'init' ] );
			add_filter( 'post_thumbnail_html', [ $this, 'post_thumbnail_html' ], 1, 5 );
		}
	}

	function wp_enqueue_scripts() {
		wp_register_script( 'dn_remp_cabrio_script', plugin_dir_url( __FILE__ ) . 'dn-remp-cabrio.js', null );
		wp_enqueue_script( 'dn_remp_cabrio_script' );
	}

	function admin_menu() {
		add_options_page(
			__( 'DN REMP Cabrio', 'remp' ),
			__( 'DN REMP Cabrio', 'remp' ),
			'manage_options',
			$this->plugin,
			[ $this, 'admin_page' ]
		);
	}

	function admin_page() {
		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( $this->plugin );
				do_settings_sections( $this->plugin );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	function admin_init() {
		$groups = $this->get_fields();

		foreach ( $groups as $group ) {
			foreach ( $group['fields'] as $field ) {
				register_setting(
					$this->plugin,
					$field['key'],
					'sanitize_text_field'
				);

				add_settings_field(
					$field['key'],
					$field['label'],
					[ $this, 'input_field' ],
					$this->plugin,
					$this->plugin . '_' . $group['key'],
					$field
				);
			}

			add_settings_section(
				$this->plugin . '_' . $group['key'],
				$group['label'],
				null,
				$this->plugin
			);
		}
	}

	function input_field( $field ) {
		switch ( $field['type'] ) {
			case 'checkbox':
				printf(
					' <input id="%1$s" name="%1$s" type="%2$s" value="1" %3$s> %4$s',
					$field['key'],
					$field['type'],
					checked( get_option( $field['key'] ), true, false ),
					isset( $field['description'] ) ? sprintf( ' <p class="description">%s</p> ', $field['description'] ) : ''
				);
				break;
			default:
				$value = get_option( $field['key'] );

				printf(
					' <input id="%1$s" name="%1$s" type="%2$s" value="%3$s"> %4$s ',
					$field['key'],
					$field['type'],
					isset( $value ) ? esc_attr( $value ) : '',
					isset( $field['description'] ) ? sprintf( ' <p class="description">%s</p> ', $field['description'] ) : ''
				);
				break;
		}
	}

	function get_fields( $flat = false, $force = false ) {
		return [
			[
				'label' => __( 'Post title A/B test', 'remp' ),
				'key' => 'title',
				'fields' => [
					[
						'label' => __( 'Enable', 'remp' ),
						'key' => 'dn_remp_cabrio_title_on',
						'type' => 'checkbox'
					]
				]
			],
			[
				'label' => __( 'Post thumbnail A/B test', 'remp' ),
				'key' => 'thumbnail',
				'fields' => [
					[
						'label' => __( 'Enable', 'remp' ),
						'key' => 'dn_remp_cabrio_thumbnail_on',
						'type' => 'checkbox'
					]
				]
			],
			[
				'label' => __( 'Lock position A/B test', 'remp' ),
				'key' => 'lock',
				'fields' => [
					[
						'label' => __( 'Enable', 'remp' ),
						'key' => 'dn_remp_cabrio_lock_on',
						'type' => 'checkbox'
					],
					[
						'label' => __( 'Article selector', 'remp' ),
						'description' => __( 'Element containing the article content, there should be only one ccurence on single pages, otherwise the first one is used.<br> Use <code>document.querySelector</code> compatibile syntax.', 'remp' ),
						'key' => 'dn_remp_cabrio_lock_article',
						'type' => 'text'
					],
					[
						'label' => __( 'Paywall gate selector', 'remp' ),
						'description' => __( 'The element wich terminates free part of article, there should be only one ccurence inside element selected by <strong>Article selector</strong>, otherwise the first one is used.<br> Use <code>document.querySelector</code> compatibile syntax.', 'remp' ),
						'key' => 'dn_remp_cabrio_lock_gate',
						'type' => 'text'
					],
					[
						'label' => __( 'Number of paragraphs to hide', 'remp' ),
						'description' => __( 'All top-level children inside element selected by <strong>Article selector</strong> are counted as paragraphs.<br/> Plugin will hide configured number of full paragraphs and will trim any content within the paragraph of paywall gate printed before the gate.', 'remp' ),
						'key' => 'dn_remp_cabrio_lock_delta',
						'type' => 'number'
					],
					[
						'label' => __( 'Minimal number of paragraphs to keep visible', 'remp' ),
						'description' => __( 'Keep this number of paragraphs visible if the article is too short for A/B testing.', 'remp' ),
						'key' => 'dn_remp_cabrio_lock_min',
						'type' => 'number'
					]
				]
			]
		];
	}

	function plugins_loaded() {
		if ( !class_exists( 'MultiPostThumbnails' ) ) {
			add_action( 'admin_init', [ $this, 'deactivate'] );
			add_action( 'admin_notices', [ $this, 'deactivate_notice'] );
			unset($_GET['activate']);
		}
	}

	function deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	function deactivate_notice() {
		printf( '<div class="error"><p>%s</p></div>',
			__( 'The plugin <strong>DN REMP Cabrio</strong> requires <a href="https://wordpress.org/plugins/multiple-post-thumbnails/" target="_blank"><strong>Multiple Post Thumbnails</strong></a> plugin to work correctly. Please install it.' )
		);
	}

	function init() {
		if ( class_exists( 'MultiPostThumbnails' ) ) {
			new MultiPostThumbnails( array(
				'label' => __( 'Alternative Featured Image', 'remp' ),
				'id' => '_' . $this->plugin . '_thumbnail2',
				'post_type' => 'post'
			) );
		}
	}

	function post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		if ( empty( get_option( 'dn_remp_cabrio_thumbnail_on' ) ) ) {
			return $html;
		}

		$thumbnail2 = '';

		if ( class_exists( 'MultiPostThumbnails' ) && ( $thumbnail2 = MultiPostThumbnails::get_post_thumbnail_id( get_post_type(), '_' . $this->plugin . '_thumbnail2', $post_id ) ) ) {
			$html2 = wp_get_attachment_image( $thumbnail2, $size );
		}

		$html = str_replace( '<img ', sprintf( '<img data-cabrioi="%s" ', $post_id ), $html );
		$html .= sprintf( '<script>cabrioSI(%1$s,"%2$s");</script>',
			$post_id,
			str_replace( '"', "'", $html2 )
		);

		return $html;
	}

	function edit_form_before_permalink( $post ) {
		if ( get_post_type( $post ) != 'post' ) {
			return;
		}

		$title2 = get_post_meta( $post->ID, '_' . $this->plugin . '_title2', true );

		printf( '<input id="%1$s" name="%1$s" style="width:100%%;margin:0;" value="%2$s" type="text" placeholder="%3$s" spellcheck="true" autocomplete="off">',
			'_' . $this->plugin . '_title2',
			empty( $title2 ) ? '' : esc_attr( esc_html( $title2 ) ),
			__( 'Alternative Title', 'remp' )
		);
	}

	function save_post( $post_id ) {
		$keys = [
			$this->plugin . '_title_on' => '_' . $this->plugin . '_title2',
			$this->plugin . '_thumbnail_on' => '_' . $this->plugin . '_thumbnail2'
		];

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || !current_user_can( 'edit_post' ) ) {
			return;
		}

		foreach ( $keys as $option => $meta ) {
			if ( empty( get_option( $option ) ) ) {
				continue;
			}

			if ( isset( $_POST[$meta] ) && !empty( $_POST[$meta] ) ) {
				update_post_meta( $post_id, $meta, $_POST[$meta] );
			} else {
				delete_post_meta( $post_id, $meta );
			}
		}
	}

	function the_title( $title, $post_id = 0 ) {
		$post = get_post( $post_id );

		$title2 = wptexturize( get_post_meta( $post->ID, '_' . $this->plugin . '_title2', true ) ); // skaredy hotfix

		if ( !is_admin() && !is_feed() && ( is_singular( [ 'page', 'post' ] ) || is_home() || is_front_page() || is_archive() || is_search() || is_404() ) ) {
			$title = sprintf( '<span data-cabriot="%1$s">%2$s</span><script>cabrioST(%1$s,"%3$s");</script>',
				$post_id,
				$title,
				esc_js( esc_html( $title2 ) )
			);
		}

		return $title;
	}

	function wp_footer() {
		if ( empty( get_option( 'dn_remp_cabrio_lock_on' ) ) || !intval( get_option( 'dn_remp_cabrio_lock_delta' ) ) || !is_single() ) {
			return;
		}

		printf( '<script type="text/javascript">window.cabrioSL(%s,"%s","%s",%d,%d);</script>',
			get_the_ID(),
			esc_js( esc_html( get_option( 'dn_remp_cabrio_lock_article' ) ) ),
			esc_js( esc_html( get_option( 'dn_remp_cabrio_lock_gate' ) ) ),
			get_option( 'dn_remp_cabrio_lock_delta' ),
			get_option( 'dn_remp_cabrio_lock_min' )
		);
	}
}

new DN_Remp_Cabrio();
