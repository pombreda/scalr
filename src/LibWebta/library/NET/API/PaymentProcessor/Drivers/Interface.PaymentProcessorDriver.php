<?
	/**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */		

	/**
     * @name       IPaymentProcessorDriver
     * @category   LibWebta
     * @package    NET_API
     * @subpackage PaymentProcessor
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	interface IPaymentProcessorDriver
	{
		/**
		 * Return name of payment method
		 * @return string
		 */
		public function GetName();
		
		/**
		 * Return true if current application use this payment
		 *
		 * @return bool
		 */
		public function IPNApplicable();
		
		/**
		 * Proccess payment
		 *
		 * @return bool
		 */
		public function ProccessPayment($expected_amount = false, $expected_currency = false);
		
		/**
		 * Send request to payment server
		 * @param float $amount
		 * @param integer $invoiceid
		 * @param string $description
		 * @param array $extra
		 * 
		 * @return bool
		 */
		public function ProceedToPayment($amount, $invoiceid, $description, $extra = array());
		
		
		/**
		 * Validate additional payment data
		 *
		 * @param ar $data
		 */
		public function ValidatePaymentData($data);
		
		/**
		 * Return OrderID
		 *
		 */
		public function GetOrderID();
	}
?>