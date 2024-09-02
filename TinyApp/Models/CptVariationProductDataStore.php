<?php

namespace TinySolutions\cptwoointpro\Models;

use TinySolutions\cptwooint\Helpers\Fns;
use WC_Product_Variation_Data_Store_CPT;

/**
 * Variation Product Data Store
 */
class CptVariationProductDataStore extends WC_Product_Variation_Data_Store_CPT {

	/**
	 * Reads a product from the database and sets its data to the class.
	 *
	 * @param \WC_Product_Variation $product Product object.
	 *
	 * @throws \WC_Data_Exception If WC_Product::set_tax_status() is called with an invalid tax status (via read_product_data), or when passing an invalid ID.
	 * @since 3.0.0
	 */
	public function read( &$product ) {
		$product->set_defaults();

		if ( ! $product->get_id() ) {
			return;
		}

		$post_object = get_post( $product->get_id() );

		if ( ! $post_object ) {
			return;
		}

		if ( 'product_variation' !== $post_object->post_type ) {
			throw new \WC_Data_Exception( 'variation_invalid_id', esc_html__( 'Invalid product type: passed ID does not correspond to a product variation.', 'woocommerce' ) );
		}

		$product->set_props(
			[
				'name'              => $post_object->post_title,
				'slug'              => $post_object->post_name,
				'date_created'      => $this->string_to_timestamp( $post_object->post_date_gmt ),
				'date_modified'     => $this->string_to_timestamp( $post_object->post_modified_gmt ),
				'status'            => $post_object->post_status,
				'menu_order'        => $post_object->menu_order,
				'reviews_allowed'   => 'open' === $post_object->comment_status,
				'parent_id'         => $post_object->post_parent,
				'attribute_summary' => $post_object->post_excerpt,
			]
		);

		$parent_id = $product->get_parent_id( 'edit' );
		// The post parent is not a valid variable product so we should prevent this.
		if ( $parent_id && 'product' !== get_post_type( $parent_id ) && ! Fns::is_supported( get_post_type( $parent_id ) ) ) {
			$product->set_parent_id( 0 );
		}

		// ! Fns::is_supported( get_post_type( $product->get_id() ) )
		$this->read_downloads( $product );
		$this->read_product_data( $product );
		$this->read_extra_data( $product );
		$product->set_attributes( wc_get_product_variation_attributes( $product->get_id() ) );

		$updates = [];
		/**
		 * If a variation title is not in sync with the parent e.g. saved prior to 3.0, or if the parent title has changed, detect here and update.
		 */
		$new_title = $this->generate_product_title( $product );

		if ( $post_object->post_title !== $new_title ) {
			$product->set_name( $new_title );
			$updates = array_merge( $updates, [ 'post_title' => $new_title ] );
		}

		/**
		 * If the attribute summary is not in sync, update here. Used when searching for variations by attribute values.
		 * This is meant to also cover the case when global attribute name or value is updated, then the attribute summary is updated
		 * for respective products when they're read.
		 */
		$new_attribute_summary = $this->generate_attribute_summary( $product );

		if ( $new_attribute_summary !== $post_object->post_excerpt ) {
			$product->set_attribute_summary( $new_attribute_summary );
			$updates = array_merge( $updates, [ 'post_excerpt' => $new_attribute_summary ] );
		}

		if ( ! empty( $updates ) ) {
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $updates, [ 'ID' => $product->get_id() ] );
			clean_post_cache( $product->get_id() );
		}

		// Set object_read true once all data is read.
		$product->set_object_read( true );
	}

	/**
	 * Create a new product.
	 *
	 * @param \WC_Product_Variation $product Product object.
	 *
	 * @since 3.0.0
	 */
	public function create( &$product ) {
		if ( ! $product->get_date_created() ) {
			$product->set_date_created( time() );
		}

		$new_title = $this->generate_product_title( $product );

		if ( $product->get_name( 'edit' ) !== $new_title ) {
			$product->set_name( $new_title );
		}

		$attribute_summary = $this->generate_attribute_summary( $product );
		$product->set_attribute_summary( $attribute_summary );

		$parent_id = $product->get_parent_id( 'edit' );
		// The post parent is not a valid variable product so we should prevent this.
		if ( $parent_id && 'product' !== get_post_type( $parent_id ) && ! Fns::is_supported( get_post_type( $parent_id ) ) ) {
			$product->set_parent_id( 0 );
		}

		$id = wp_insert_post(
			apply_filters(
				'woocommerce_new_product_variation_data',
				[
					'post_type'      => 'product_variation',
					'post_status'    => $product->get_status() ? $product->get_status() : 'publish',
					'post_author'    => get_current_user_id(),
					'post_title'     => $product->get_name( 'edit' ),
					'post_excerpt'   => $product->get_attribute_summary( 'edit' ),
					'post_content'   => '',
					'post_parent'    => $product->get_parent_id(),
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'menu_order'     => $product->get_menu_order(),
					'post_date'      => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getTimestamp() ),
					'post_name'      => $product->get_slug( 'edit' ),
				]
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$product->set_id( $id );

			$this->update_post_meta( $product, true );
			$this->update_terms( $product, true );
			$this->update_visibility( $product, true );
			$this->update_attributes( $product, true );
			$this->handle_updated_props( $product );

			$product->save_meta_data();
			$product->apply_changes();

			$this->update_version_and_type( $product );
			$this->update_guid( $product );

			$this->clear_caches( $product );

			do_action( 'woocommerce_new_product_variation', $id, $product );
		}
	}

	/**
	 * Updates an existing product.
	 *
	 * @param \WC_Product_Variation $product Product object.
	 *
	 * @since 3.0.0
	 */
	public function update( &$product ) {
		$product->save_meta_data();

		if ( ! $product->get_date_created() ) {
			$product->set_date_created( time() );
		}

		$new_title = $this->generate_product_title( $product );

		if ( $product->get_name( 'edit' ) !== $new_title ) {
			$product->set_name( $new_title );
		}

		$parent_id = $product->get_parent_id( 'edit' );
		// The post parent is not a valid variable product so we should prevent this.
		if ( $parent_id && 'product' !== get_post_type( $parent_id ) && ! Fns::is_supported( get_post_type( $parent_id ) ) ) {
			$product->set_parent_id( 0 );
		}

		$changes = $product->get_changes();

		if ( array_intersect( [ 'attributes' ], array_keys( $changes ) ) ) {
			$product->set_attribute_summary( $this->generate_attribute_summary( $product ) );
		}

		// Only update the post when the post data changes.
		if ( array_intersect(
			[
				'name',
				'parent_id',
				'status',
				'menu_order',
				'date_created',
				'date_modified',
				'attributes',
			],
			array_keys( $changes )
		) ) {
			$post_data = [
				'post_title'        => $product->get_name( 'edit' ),
				'post_excerpt'      => $product->get_attribute_summary( 'edit' ),
				'post_parent'       => $product->get_parent_id( 'edit' ),
				'comment_status'    => 'closed',
				'post_status'       => $product->get_status( 'edit' ) ? $product->get_status( 'edit' ) : 'publish',
				'menu_order'        => $product->get_menu_order( 'edit' ),
				'post_date'         => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $product->get_date_created( 'edit' )->getTimestamp() ),
				'post_modified'     => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $product->get_date_modified( 'edit' )->getOffsetTimestamp() ) : current_time( 'mysql' ),
				'post_modified_gmt' => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $product->get_date_modified( 'edit' )->getTimestamp() ) : current_time( 'mysql', 1 ),
				'post_type'         => 'product_variation',
				'post_name'         => $product->get_slug( 'edit' ),
			];

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, [ 'ID' => $product->get_id() ] );
				clean_post_cache( $product->get_id() );
			} else {
				wp_update_post( array_merge( [ 'ID' => $product->get_id() ], $post_data ) );
			}
			$product->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.

		} else { // Only update post modified time to record this save event.
			$GLOBALS['wpdb']->update(
				$GLOBALS['wpdb']->posts,
				[
					'post_modified'     => current_time( 'mysql' ),
					'post_modified_gmt' => current_time( 'mysql', 1 ),
				],
				[
					'ID' => $product->get_id(),
				]
			);
			clean_post_cache( $product->get_id() );
		}

		$this->update_post_meta( $product );
		$this->update_terms( $product );
		$this->update_visibility( $product, true );
		$this->update_attributes( $product );
		$this->handle_updated_props( $product );

		$product->apply_changes();

		$this->update_version_and_type( $product );

		$this->clear_caches( $product );

		do_action( 'woocommerce_update_product_variation', $product->get_id(), $product );
	}
}
