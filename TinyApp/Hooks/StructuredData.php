<?php
/**
 * Structured data's handler and generator using JSON-LD format.
 *
 * @package WooCommerce\Classes
 * @since   3.0.0
 * @version 3.0.0
 */
namespace TinySolutions\cptwoointpro\Hooks;

use TinySolutions\cptwooint\Helpers\Fns;
use TinySolutions\cptwoointpro\Traits\SingletonTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Structured data class.
 */
class StructuredData {

	/**
	 * Singleton
	 */
	use SingletonTrait;

	/**
	 * Stores the structured data.
	 *
	 * @var array $_data Array of structured data.
	 */
	private $_data = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Output structured data.
		add_action( 'wp_footer', [ $this, 'output_structured_data' ], 10 );
	}

	/**
	 * Sets data.
	 *
	 * @param  array $data  Structured data.
	 * @param  bool  $reset Unset data (default: false).
	 * @return bool
	 */
	public function set_data( $data, $reset = false ) {
		if ( ! isset( $data['@type'] ) || ! preg_match( '|^[a-zA-Z]{1,20}$|', $data['@type'] ) ) {
			return false;
		}

		if ( $reset && isset( $this->_data ) ) {
			unset( $this->_data );
		}

		$this->_data[] = $data;

		return true;
	}

	/**
	 * Gets data.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->_data;
	}

	/**
	 * Structures and returns data.
	 *
	 * List of types available by default for specific request:
	 *
	 * 'product',
	 * 'review',
	 * 'breadcrumblist',
	 * 'website',
	 * 'order',
	 *
	 * @param  array $types Structured data types.
	 * @return array
	 */
	public function get_structured_data( $types ) {
		$data = [];
		// Put together the values of same type of structured data.
		foreach ( $this->get_data() as $value ) {
			$data[ strtolower( $value['@type'] ) ][] = $value;
		}

		// Wrap the multiple values of each type inside a graph... Then add context to each type.
		foreach ( $data as $type => $value ) {
			$data[ $type ] = count( $value ) > 1 ? [ '@graph' => $value ] : $value[0];
			$data[ $type ] = apply_filters( 'woocommerce_structured_data_context', [ '@context' => 'https://schema.org/' ], $data, $type, $value ) + $data[ $type ];
		}

		// If requested types, pick them up... Finally change the associative array to an indexed one.
		$data = $types ? array_values( array_intersect_key( $data, array_flip( $types ) ) ) : array_values( $data );

		if ( ! empty( $data ) ) {
			if ( 1 < count( $data ) ) {
				$data = apply_filters( 'woocommerce_structured_data_context', [ '@context' => 'https://schema.org/' ], $data, '', '' ) + [ '@graph' => $data ];
			} else {
				$data = $data[0];
			}
		}

		return $data;
	}
	/**
	 * Get data types for pages.
	 *
	 * @return array
	 */
	protected function get_data_type_for_page() {
		if ( ! is_singular() ) {
			return [];
		}
		$supported = Fns::is_supported( get_post_type() );
		$types     = [];
		if ( $supported ) {
			$types[] = 'product';
			$types[] = 'review';
		}

		return array_filter( apply_filters( 'woocommerce_structured_data_type_for_page', $types ) );
	}
	/**
	 * Sanitizes, encodes and outputs structured data.
	 *
	 * Hooked into `wp_footer` action hook.
	 * Hooked into `woocommerce_email_order_details` action hook.
	 */
	public function output_structured_data() {
		$supported = Fns::is_schema_enabled( get_post_type() );
		if ( ! $supported ) {
			return;
		}
		$this->generate_website_data();
		$this->generate_product_data();
		$types = $this->get_data_type_for_page();
		$data  = $this->get_structured_data( $types );
		if ( $data ) {
			echo '<script type="application/ld+json">' . wc_esc_json( wp_json_encode( $data ), true ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	
	/**
	 * Generates Product structured data.
	 *
	 * Hooked into `woocommerce_single_product_summary` action hook.
	 *
	 * @param \WC_Product $product Product data (default: null).
	 */
	public function generate_product_data() {
		if ( ! is_singular() ) {
			return [];
		}
		$supported = Fns::is_supported( get_post_type() );
		if ( ! $supported ) {
			return [];
		}
		
		global $product;
		
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$shop_name = get_bloginfo( 'name' );
		$shop_url  = home_url();
		$currency  = get_woocommerce_currency();
		$permalink = get_permalink( $product->get_id() );
		$image     = wp_get_attachment_url( $product->get_image_id() );

		$markup = [
			'@type'       => 'Product',
			'@id'         => $permalink . '#product', // Append '#product' to differentiate between this @id and the @id generated for the Breadcrumblist.
			'name'        => wp_kses_post( $product->get_name() ),
			'url'         => $permalink,
			'description' => wp_strip_all_tags( do_shortcode( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ) ),
		];

		if ( $image ) {
			$markup['image'] = $image;
		}

		// Declare SKU or fallback to ID.
		if ( $product->get_sku() ) {
			$markup['sku'] = $product->get_sku();
		} else {
			$markup['sku'] = $product->get_id();
		}

		if ( '' !== $product->get_price() ) {
			// Assume prices will be valid until the end of next year, unless on sale and there is an end date.
			$price_valid_until = gmdate( 'Y-12-31', time() + YEAR_IN_SECONDS );

			if ( $product->is_type( 'variable' ) ) {
				$lowest  = $product->get_variation_price( 'min', false );
				$highest = $product->get_variation_price( 'max', false );

				if ( $lowest === $highest ) {
					$markup_offer = [
						'@type'              => 'Offer',
						'price'              => wc_format_decimal( $lowest, wc_get_price_decimals() ),
						'priceValidUntil'    => $price_valid_until,
						'priceSpecification' => [
							'price'                 => wc_format_decimal( $lowest, wc_get_price_decimals() ),
							'priceCurrency'         => $currency,
							'valueAddedTaxIncluded' => wc_prices_include_tax() ? 'true' : 'false',
						],
					];
				} else {
					$markup_offer = [
						'@type'      => 'AggregateOffer',
						'lowPrice'   => wc_format_decimal( $lowest, wc_get_price_decimals() ),
						'highPrice'  => wc_format_decimal( $highest, wc_get_price_decimals() ),
						'offerCount' => count( $product->get_children() ),
					];
				}
			} elseif ( $product->is_type( 'grouped' ) ) {
				if ( $product->is_on_sale() && $product->get_date_on_sale_to() ) {
					$price_valid_until = gmdate( 'Y-m-d', $product->get_date_on_sale_to()->getTimestamp() );
				}

				$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
				$children         = array_filter( array_map( 'wc_get_product', $product->get_children() ), 'wc_products_array_filter_visible_grouped' );
				$price_function   = 'incl' === $tax_display_mode ? 'wc_get_price_including_tax' : 'wc_get_price_excluding_tax';

				foreach ( $children as $child ) {
					if ( '' !== $child->get_price() ) {
						$child_prices[] = $price_function( $child );
					}
				}
				if ( empty( $child_prices ) ) {
					$min_price = 0;
				} else {
					$min_price = min( $child_prices );
				}

				$markup_offer = [
					'@type'              => 'Offer',
					'price'              => wc_format_decimal( $min_price, wc_get_price_decimals() ),
					'priceValidUntil'    => $price_valid_until,
					'priceSpecification' => [
						'price'                 => wc_format_decimal( $min_price, wc_get_price_decimals() ),
						'priceCurrency'         => $currency,
						'valueAddedTaxIncluded' => wc_prices_include_tax() ? 'true' : 'false',
					],
				];
			} else {
				if ( $product->is_on_sale() && $product->get_date_on_sale_to() ) {
					$price_valid_until = gmdate( 'Y-m-d', $product->get_date_on_sale_to()->getTimestamp() );
				}
				$markup_offer = [
					'@type'              => 'Offer',
					'price'              => wc_format_decimal( $product->get_price(), wc_get_price_decimals() ),
					'priceValidUntil'    => $price_valid_until,
					'priceSpecification' => [
						'price'                 => wc_format_decimal( $product->get_price(), wc_get_price_decimals() ),
						'priceCurrency'         => $currency,
						'valueAddedTaxIncluded' => wc_prices_include_tax() ? 'true' : 'false',
					],
				];
			}

			if ( $product->is_in_stock() ) {
				$stock_status_schema = ( 'onbackorder' === $product->get_stock_status() ) ? 'BackOrder' : 'InStock';
			} else {
				$stock_status_schema = 'OutOfStock';
			}

			$markup_offer += [
				'priceCurrency' => $currency,
				'availability'  => 'http://schema.org/' . $stock_status_schema,
				'url'           => $permalink,
				'seller'        => [
					'@type' => 'Organization',
					'name'  => $shop_name,
					'url'   => $shop_url,
				],
			];

			$markup['offers'] = [ apply_filters( 'woocommerce_structured_data_product_offer', $markup_offer, $product ) ];
		}

		if ( $product->get_rating_count() && wc_review_ratings_enabled() ) {
			$markup['aggregateRating'] = [
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'reviewCount' => $product->get_review_count(),
			];

			// Markup 5 most recent rating/review.
			$comments = get_comments(
				[
					'number'      => 5,
					'post_id'     => $product->get_id(),
					'status'      => 'approve',
					'post_status' => 'publish',
					'post_type'   => 'product',
					'parent'      => 0,
					'meta_query'  => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						[
							'key'     => 'rating',
							'type'    => 'NUMERIC',
							'compare' => '>',
							'value'   => 0,
						],
					],
				]
			);

			if ( $comments ) {
				$markup['review'] = [];
				foreach ( $comments as $comment ) {
					$markup['review'][] = [
						'@type'         => 'Review',
						'reviewRating'  => [
							'@type'       => 'Rating',
							'bestRating'  => '5',
							'ratingValue' => get_comment_meta( $comment->comment_ID, 'rating', true ),
							'worstRating' => '1',
						],
						'author'        => [
							'@type' => 'Person',
							'name'  => get_comment_author( $comment ),
						],
						'reviewBody'    => get_comment_text( $comment ),
						'datePublished' => get_comment_date( 'c', $comment ),
					];
				}
			}
		}

		// Check we have required data.
		if ( empty( $markup['aggregateRating'] ) && empty( $markup['offers'] ) && empty( $markup['review'] ) ) {
			return;
		}

		$this->set_data( apply_filters( 'woocommerce_structured_data_product', $markup, $product ) );
	}


	/**
	 * Generates WebSite structured data.
	 *
	 * Hooked into `woocommerce_before_main_content` action hook.
	 */
	public function generate_website_data() {
		$markup                    = [];
		$markup['@type']           = 'WebSite';
		$markup['name']            = get_bloginfo( 'name' );
		$markup['url']             = home_url();
		$markup['potentialAction'] = [
			'@type'       => 'SearchAction',
			'target'      => home_url( '?s={search_term_string}&post_type=product' ),
			'query-input' => 'required name=search_term_string',
		];

		$this->set_data( apply_filters( 'woocommerce_structured_data_website', $markup ) );
	}
}
