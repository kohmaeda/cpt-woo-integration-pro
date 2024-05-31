<?php

namespace TinySolutions\cptwoointpro\Controllers;

// Do not allow directly accessing this file.
use TinySolutions\cptwoointpro\Helpers\Functions;
use TinySolutions\cptwoointpro\Traits\SingletonTrait;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}
/**
 * Shortcode.
 */
class ShortCodes {
	/**
	 * Singleton
	 */
	use SingletonTrait;

	/**
	 * Init Class
	 */
	private function __construct() {
		add_action( 'init', [ $this, 'cptwooint_shortcodes' ] );
	}

	/***
	 * Shortcode Init
	 *
	 * @return void
	 */
	public function cptwooint_shortcodes() {
		$shortcodes = [
			'sku',
			'attributes',
			'gallery',
			'gallery_with_variation',
			'upsell_products',
		];
		foreach ( $shortcodes as $shortcode ) :
			add_shortcode( 'cptwooint_' . $shortcode, [ $this, $shortcode . '_shortcode' ] );
		endforeach;
	}

	/***
	 * Gallery Shortcode
	 *
	 * @param array $atts attribute.
	 *
	 * @return mixed|string
	 */
	public function gallery_with_variation_shortcode( $atts ) {

		global $product;
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$gallery_ids       = $product->get_gallery_image_ids();
		$post_thumbnail_id = $product->get_image_id();
		$attachment_ids    = $post_thumbnail_id ? array_merge( [ $post_thumbnail_id ], $gallery_ids ) : $gallery_ids;

		if ( ! is_array( $attachment_ids ) || ! count( $attachment_ids ) ) {
			return;
		}

		wp_enqueue_script( 'wc-single-product' );
		wp_enqueue_script( 'cptwooint-public' );

		ob_start();
		?>
		
		<?php do_action( 'cptwooint_before_display_gallery' ); ?>
		<div class="cptwooint-product-gallery">
			<?php woocommerce_show_product_images(); ?>
		</div>
		<?php do_action( 'cptwooint_after_display_gallery' ); ?>
		<?php
		return ob_get_clean();
	}

	/***
	 * Upsale Shortcode
	 *
	 * @param array $atts array.
	 *
	 * @return mixed|string
	 */
	public function upsell_products_shortcode( $atts ) {
		global $product;
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$defaults = [
			'limit'   => - 1,
			'columns' => 4,
			'orderby' => 'rand',
			'order'   => 'desc',
		];
		$atts     = shortcode_atts( $defaults, $atts );
		ob_start();
		?>
		<div class="cptwooint-upsell-products woocommerce">
			<?php woocommerce_upsell_display( absint( $atts['limit'] ), absint( $atts['columns'] ), esc_attr( $atts['orderby'] ), esc_attr( $atts['order'] ) ); ?>
		</div>
		<?php
		return ob_get_clean();
	}


	/***
	 * Gallery Shortocde
	 *
	 * @param array $atts attribute.
	 *
	 * @return mixed|string
	 */
	public function gallery_shortcode( $atts ) {
		$defaults = [
			'thumbnail_position' => 'bottom',
			'autoheight'         => false,
			'col'                => 3,
		];
		$atts     = shortcode_atts( $defaults, $atts );

		global $product;
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$gallery_ids       = $product->get_gallery_image_ids();
		$post_thumbnail_id = $product->get_image_id();
		$attachment_ids    = $post_thumbnail_id ? array_merge( [ $post_thumbnail_id ], $gallery_ids ) : $gallery_ids;

		if ( ! is_array( $attachment_ids ) || ! count( $attachment_ids ) ) {
			return;
		}
		wp_enqueue_script( 'zoom' );

		wp_enqueue_script( 'wc-single-product' );
		wp_enqueue_script( 'cptwooint-public' );

		$isVertical = 'left' === $atts['thumbnail_position'] || 'right' === $atts['thumbnail_position'];

		ob_start();
		$isAutoheight = 'true' == $atts['autoheight'];
		?>
		<?php do_action( 'cptwooint_before_display_gallery' ); ?>
		<div class="cptwooint-product-gallery <?php echo esc_attr( $isVertical ? 'cptwooint-vertical-gallery cptwooint-vertical-' . $atts['thumbnail_position'] : 'cptwooint-horizontal-bottom' ); ?>">
			<div class="swiper cptwoo-slider-main-image woocommerce-product-gallery" data-options="
			<?php
			echo esc_attr(
				wp_json_encode(
					[
						'direction'  => 'horizontal',
						'autoHeight' => $isAutoheight,
						'thumbs'     => [
							'swiper' => '.cptwoo-thumb-image',
						],
					]
				)
			);
			?>
			">
				<div class="swiper-wrapper">
					<?php foreach ( $attachment_ids as $id ) { ?>
						<div class="swiper-slide">
							<?php echo wp_kses_post( wc_get_gallery_image_html( $id, true ) ); ?>
						</div>
					<?php } ?>
				</div>
				<div class="swiper-button-next"></div>
				<div class="swiper-button-prev"></div>
			</div>
			<?php
			$thumbnail_size = apply_filters( 'woocommerce_thumbnail_size', 'thumbnail' );
			$col            = $atts['col'] ?? 3;
			if ( is_array( $attachment_ids ) && count( $attachment_ids ) ) {
				?>
				<div class="swiper cptwoo-thumb-image" style="max-width: <?php echo $isVertical ? '200px' : '100%;'; ?>"
					 data-options="
					 <?php
						echo esc_attr(
							wp_json_encode(
								[
									'spaceBetween'         => 15,
									'slidesPerView'        => $col,
									'centeredSlides'       => true,
									'centeredSlidesBounds' => true,
									'freeMode'             => true,
									'direction'            => $isVertical ? 'vertical' : 'horizontal',
								]
							)
						);
						?>
					 "
				>
					<div class="swiper-wrapper">
						<?php foreach ( $attachment_ids as $id ) { ?>
							<div class="swiper-slide">
								<?php echo wp_get_attachment_image( $id, $thumbnail_size ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php } ?>
					</div>
					<div class="swiper-button-next"></div>
					<div class="swiper-button-prev"></div>
				</div>
				<?php
			}
			?>
		</div>
		<?php do_action( 'cptwooint_after_display_gallery' ); ?>
		<?php
		return ob_get_clean();
	}

	/***
	 * Attribute Shortcode
	 *
	 *  @param array $atts attribute.
	 *
	 * @return mixed|string
	 */
	public function attributes_shortcode( $atts ) {
		global $product;
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		ob_start();
		?>
		<div class="cptwooint-product-attributes">
			<?php
			do_action( 'cptwooint_before_display_attributes' );
			echo wp_kses_post( apply_filters( 'cptwooint_display_attributes', Functions::display_product_attributes( $product ), $product, get_the_ID() ) );
			do_action( 'cptwooint_after_display_attributes' );
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/***
	 * Sku Shortcode
	 *
	 * @param array $atts attribute.
	 *
	 * @return mixed|string
	 */
	public function sku_shortcode( $atts ) {
		global $product;
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		ob_start();
		do_action( 'cptwooint_before_display_sku' );
		?>
		<div class="cptwooint-sku">
			<span class="sku_wrapper">
				<?php esc_html_e( 'SKU:', 'cptwooint' ); ?>
				<span class="sku">
					<?php
					$sku = $product->get_sku() ?? esc_html__( 'N/A', 'cptwooint' );
					echo esc_html( $sku );
					?>
				</span>
			</span>
		</div>
		<?php
		do_action( 'cptwooint_after_display_sku' );

		return ob_get_clean();
	}
}