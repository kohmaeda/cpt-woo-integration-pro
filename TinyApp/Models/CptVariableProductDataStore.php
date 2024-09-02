<?php

namespace TinySolutions\cptwoointpro\Models;

use TinySolutions\cptwooint\Traits\CptProductDataStoreReadTrait;
use WC_Product;
use WC_Product_Variable_Data_Store_CPT;
/**
 * Variable Product Data Store
 */
class CptVariableProductDataStore extends WC_Product_Variable_Data_Store_CPT {
	/**
	 * Method to read a product from the database.
	 *
	 * @param WC_Product
	 */
	use CptProductDataStoreReadTrait;

}
