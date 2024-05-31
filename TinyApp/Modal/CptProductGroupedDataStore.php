<?php

namespace TinySolutions\cptwoointpro\Modal;

use TinySolutions\cptwooint\Traits\CptProductDataStoreReadTrait;
use WC_Product;
use WC_Product_Grouped_Data_Store_CPT;

/**
 * Grouped Product Data Store
 */
class CptProductGroupedDataStore extends WC_Product_Grouped_Data_Store_CPT {
	/**
	 * Method to read a product from the database.
	 *
	 * @param WC_Product
	 */
	use CptProductDataStoreReadTrait;
}
