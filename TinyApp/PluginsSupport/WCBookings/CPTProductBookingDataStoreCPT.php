<?php

namespace TinySolutions\cptwoointpro\PluginsSupport\WCBookings;

use TinySolutions\cptwooint\Traits\CptProductDataStoreReadTrait;
use WC_Product_Booking_Data_Store_CPT;
/**
 * WC Bookable Product Data Store: Stored in CPT.
 *
 * @todo When 2.6 support is dropped, implement WC_Object_Data_Store_Interface
 */
class CPTProductBookingDataStoreCPT extends WC_Product_Booking_Data_Store_CPT {
	
	/**
	 * Method to read a product from the database.
	 *
	 * @param \WC_Product
	 */
	use CptProductDataStoreReadTrait;
	
	
}
