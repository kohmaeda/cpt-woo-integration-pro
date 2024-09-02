<?php
/**
 * Main ActionHooks class.
 *
 * @package TinySolutions\cptwoointpro
 */

namespace TinySolutions\cptwoointpro\Hooks;

use TinySolutions\cptwooint\Helpers\Fns;
use TinySolutions\cptwoointpro\Helpers\Functions;
use TinySolutions\cptwoointpro\Traits\SingletonTrait;
use TinySolutions\cptwooint\Controllers\Admin\AdminMenu;
use WC_Meta_Box_Product_Images;

defined( 'ABSPATH' ) || exit();

/**
 * Main ActionHooks class.
 */
class ActionHooks {

	/**
	 * Singleton
	 */
	use SingletonTrait;

	/**
	 * Gallery Meta
	 *
	 * @var string
	 */
	private $show_gallery_meta = false;

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	private function __construct() {
		add_action( 'cptwooint/add/more/submenu', [ $this, 'register_sub_menu' ], 15, 2 );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 30 );
		add_action( 'pre_get_posts', [ $this, 'supported_post_type_in_shop' ], 15 );
		add_action( 'save_post', [ $this, 'save_custom_product_meta_box' ], 11, 2 );
	}

	/**
	 * Add snippets CPT to category archive page
	 */
	public function supported_post_type_in_shop( $query ) {
		if ( ! is_admin() && $query->is_main_query() && ( is_shop() || is_product_taxonomy() ) ) {
			$supported = Functions::get_supported_post_type_in_shop();
			if ( empty( $supported ) ) {
				return;
			}

			$post_types       = $query->get( 'post_type' );
			$query_post_types = [];

			if ( ! is_array( $post_types ) ) {
				$query_post_types[] = $post_types;
			} else {
				$query_post_types = $post_types;
			}
			$query_post_types = array_merge( $query_post_types, $supported );
			$ignored          = Functions::get_hide_post_ids_for_shop_page();
			if ( ! empty( $ignored ) ) {
				$existing_ignored = $query->get( 'post__not_in', [] );
				$ignored          = array_merge( (array) $existing_ignored, $ignored );
				$query->set( 'post__not_in', $ignored );
			}
			$query->set( 'post_type', $query_post_types );
		}
	}

	/**
	 * Register submenu
	 *
	 * @param string $menu_slug Menu Slug.
	 * @param string $capability Capability.
	 * @return void
	 * @throws \Freemius_Exception  Freemius_Exception.
	 */
	public function register_sub_menu( $menu_slug, $capability ) {
		if ( ! cwip_fs()->is_paying() ) {
			$title = '<span class="cptwooint-submenu" style="color: #6BBE66;"> <span class="dashicons-icons" style="transform: rotateX(180deg) rotate(180deg);font-size: 18px;"></span> ' . esc_html__( 'Get license', 'cptwooint-media-tools' ) . '</span>';
			add_submenu_page(
				$menu_slug,
				$title,
				$title,
				$capability,
				'cptwooint-pricing-pro',
				[ AdminMenu::instance(), 'pro_pages' ]
			);
		}
	}
	/**
	 * Add WC Meta boxes.
	 */
	public function add_meta_boxes() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$post_type = $screen->post_type;

		if ( ! Fns::is_supported( $post_type ) ) {
			return;
		}
		$this->show_gallery_meta = Fns::is_add_cpt_meta( $post_type, 'show_gallery_meta' );
		if ( $this->show_gallery_meta ) {
			add_meta_box(
				'woocommerce-product-images',
				__( 'Product gallery', 'woocommerce' ),
				[
					$this,
					'add_wc_product_gallery_image',
				],
				$post_type,
				'side',
				'low'
			);
		}
		$supported = Functions::get_supported_post_type_in_shop();
		if ( is_array( $supported ) && in_array( $post_type, $supported, true ) && cptwooint()->has_pro() ) {
			add_meta_box( 'woocommerce-product-extra-options', __( 'Integration Settings', 'woocommerce' ), [ $this, 'woocommerce_product_custom_fields' ], $post_type, 'side', 'high' );
		}
	}

	/**
	 * Product Meta field
	 *
	 * @param object $post post object.
	 *
	 * @return void
	 */
	public function add_wc_product_gallery_image( $post ) {
		// Instantiate your custom class.
		?>
		<div class="cptwooint-product-metabox cptwooint-gallery-image <?php echo esc_attr( cptwooint()->has_pro() ? 'permitted' : 'cptwoo-pro-disable always-show-pro-label' ); ?>">
			<?php WC_Meta_Box_Product_Images::output( $post ); ?>
			<?php
			if ( ! cptwooint()->has_pro() ) {
				Fns::pro_message_button();
			}
			?>
		</div>
		<?php
	}

	/**
	 * @return void
	 */
	public function woocommerce_product_custom_fields() {
		global $woocommerce, $post;
		echo '<div class="cptwooint-product-metabox "' . esc_attr( cptwooint()->has_pro() ? 'permitted' : 'cptwoo-pro-disable always-show-pro-label' ) . '>';
		$supported = Functions::get_hide_post_ids_for_shop_page();
		$value     = '';
		if ( ! empty( $supported ) && in_array( $post->ID, $supported, true ) ) {
			$value = 'yes';
		}
        
		woocommerce_wp_checkbox(
			[
				'id'       => '_hide_post_in_the_shop_page',
				'value'    => $value,
				'label'    => __( 'Hide This Post from Shop Page :', 'cptwooint' ),
				'desc_tip' => 'true',
			]
		);

		echo '</div>';
	}


	/**
	 * @param $post_id
	 * @return mixed|void
	 */
	public function save_custom_product_meta_box( $post_id, $post ) {
		// Check if our nonce is set.
		if ( ! Fns::is_supported( $post->post_type ) ) {
			return $post_id;
		}
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce, 'update-post_' . $post_id ) ) {
			return $post_id;
		}

		// Check the nonce.
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		$the_settings = get_option( 'cptwooint_settings', [] );
		// Sanitize user input.
		$new_meta_value = sanitize_text_field( wp_unslash( $_POST['_hide_post_in_the_shop_page'] ?? '' ) );
		$prev_posts     = $the_settings['cpt_hide_post_for_shop_page'][ $post->post_type ] ?? [];
		$prev_posts     = array_filter(
			$prev_posts,
			function ( $value ) {
				return is_numeric( $value );
			}
		);
		if ( 'yes' === $new_meta_value ) {
			$prev_posts[] = $post_id;
		} else {
			$prev_posts = array_filter(
				$prev_posts,
				function ( $value ) use ( $post_id ) {
					return $value !== $post_id;
				}
			);
		}
		$the_settings['cpt_hide_post_for_shop_page'][ $post->post_type ] = array_unique( $prev_posts );

		// Update the meta field in the database.
		update_option( 'cptwooint_settings', $the_settings );
	}
}
