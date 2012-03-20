<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2012 Daniel Lorenz <info@capsicum-ug.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib . 'class.tslib_pibase.php');

if (t3lib_extMgm::isLoaded('cap_tcpdf'))    {
	require(t3lib_extMgm::extPath('cap_tcpdf').'class.tx_cap_tcpdf.php');
}
if (t3lib_extMgm::isLoaded('cap_fpdi'))    {
	require(t3lib_extMgm::extPath('cap_fpdi').'class.tx_cap_fpdi.php');
}

class user_wt_cart_orderpdf_hooks extends tslib_pibase {

	//public $prefixId = 'tx_wtcart_pi1';
	public $scriptRelPath = 'lib/class.wt_cart_orderpdf_hooks.php';
	public $extKey = 'wt_cart_orderpdf';

	public function createPdf(&$params, &$session) {
		global $TSFE;

		$this->wtcartconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.']['settings.'];
		$this->wtcartorderconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_orderpdf.'];

		$this->pi_loadLL();

		$errorcnt = 0;
		// CHECK: Should PDF attached to mail?
		unset($session['files']);
		if (($params['subpart']=='recipient_mail') && ($this->wtcartorderconf['orderpdf.']['attach_recipient_mail'] == 1)) {
			$this->conf = $this->wtcartorderconf['orderpdf.'];
			$errorcnt += $this->renderOrderPdf($session);
		}
		if (($params['subpart']=='sender_mail') && ($this->wtcartorderconf['orderpdf.']['attach_sender_mail'] == 1)) {
			$this->conf = $this->wtcartorderconf['orderpdf.'];
			$errorcnt += $this->renderOrderPdf($session);
		}
		if (($params['subpart']=='recipient_mail') && ($this->wtcartorderconf['packinglistpdf.']['attach_recipient_mail'] == 1)) {
			$this->conf = $this->wtcartorderconf['packinglistpdf.'];
			$errorcnt += $this->renderPackinglistPdf($session);
		}
		if (($params['subpart']=='sender_mail') && ($this->wtcartorderconf['packinglistpdf.']['attach_sender_mail'] == 1)) {
			$this->conf = $this->wtcartorderconf['packinglistpdf.'];
			$errorcnt += $this->renderPackinglistPdf($session);
		}

		return $errorcnt;
	}

	private function renderOrderPdf(&$session) {
		$ordernumber = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf['ordernumber'], $this->conf['ordernumber.']);
		$filename = $this->concatFileName($ordernumber);

		$this->tmpl['all'] = $GLOBALS['TSFE']->cObj->getSubpart($GLOBALS['TSFE']->cObj->fileResource($this->conf['template']), '###WTCART_ORDERPDF###'); // Load HTML Template
		$this->tmpl['item'] = $GLOBALS['TSFE']->cObj->getSubpart($this->tmpl['all'], '###ITEM###'); // work on subpart 2

		// CHECK: Is directory for PDF available?
		if (!is_dir($this->conf['dir'])) {
			return 1;
		}
		// CHECK: Is PDF alreade created?
		if (!file_exists($this->conf['dir'].'/'.$filename)) {
			// generate the PDF
			$pdf = new FPDI();
			$pdf->AddPage();

			if ($this->conf['include_file']) {
				$pdf->setSourceFile($this->conf['include_file']);
				$tplIdx = $pdf->importPage(1);
				$pdf->useTemplate($tplIdx, 0, 0, 210);
			}

			$pdf->SetFont('Helvetica','',$this->conf['font-size']);

			$this->renderOrderAddress($pdf);
			$this->renderShippingAddress($pdf);
			$this->renderOrderNumber($pdf, 'ordernumber');
			$this->renderAdditionalTextblocks($pdf);

			$cartitems = '';

			$outerMarkerArray .= $this->renderCartHeadline($subpartArray);
			foreach ($session['products'] as $key => $product) {
				$subpartArray['###CONTENT###'] .= $this->renderCartProduct($product);
			}
			$this->renderCartSum($outerMarkerArray, $session);
			
			$html = $GLOBALS['TSFE']->cObj->substituteMarkerArrayCached($this->tmpl['all'], $outerMarkerArray, $subpartArray);

			$pdf->SetLineWidth(1);
			$pdf->writeHTMLCell($this->conf['cart-width'], 0, $this->conf['cart-position-x'], $this->conf['cart-position-y'], $html, 0, 2);

			$this->renderPaymentOption($pdf, $session['payment']);

			$pdf->Output($this->conf['dir'].'/'.$filename, 'F');
		}

		// CHECK: Was PDF not created, send E-Mail and exit with error.
		if (!file_exists($this->conf['dir'].'/'.$filename)) {
			// send E-Mail
			$erroremailaddress = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf['erroremailaddress'], $this->conf['erroremailaddress.']);
			if ($erroremailaddress) {
				t3lib_div::plainMailEncoded($erroremailaddress,"wt_cart_orderpdf - PDF was not created", "wt_cart_orderpdf cannot create the PDF for order. Order was aborted.",$headers='',$enc='',$charset='',$dontEncodeHeader=false);
			}
			return $abortonerror;
		}
		$session['files'][$filename] = $this->conf['dir'].'/'.$filename;
	}

	private function renderPackinglistPdf(&$session) {
		$packinglistnumber = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf['packinglistnumber'], $this->conf['packinglistnumber.']);
		$filename = $this->concatFileName($packinglistnumber);

		$this->tmpl['all'] = $GLOBALS['TSFE']->cObj->getSubpart($GLOBALS['TSFE']->cObj->fileResource($this->conf['template']), '###WTCART_PACKINGLISTPDF###'); // Load HTML Template
		$this->tmpl['item'] = $GLOBALS['TSFE']->cObj->getSubpart($this->tmpl['all'], '###ITEM###'); // work on subpart 2

		// CHECK: Is directory for PDF available?
		if (!is_dir($this->conf['dir'])) {
			return 1;
		}

		// CHECK: Is PDF alreade created?
		if (!file_exists($this->conf['dir'].'/'.$filename)) {
			// generate the PDF
			$pdf = new FPDI();
			$pdf->AddPage();

			if ($this->conf['include_file']) {
				$pdf->setSourceFile($this->conf['include_file']);
				$tplIdx = $pdf->importPage(1);
				$pdf->useTemplate($tplIdx, 0, 0, 210);
			}

			$pdf->SetFont('Helvetica','',$this->conf['font-size']);

			#$pdf = $this->renderOrderAddress($pdf, $this->conf);
			$this->renderShippingAddress($pdf, true);
			$this->renderOrderNumber($pdf, 'packinglistnumber');
			$this->renderAdditionalTextblocks($pdf);

			$pdf->SetY($this->conf['cart-position-y']);

			$cartitems = '';

			$outerMarkerArray .= $this->renderCartHeadline($subpartArray);
			foreach ($session['products'] as $key => $product) {
				$subpartArray['###CONTENT###'] .= $this->renderCartProduct($product);
			}
			$html = $GLOBALS['TSFE']->cObj->substituteMarkerArrayCached($this->tmpl['all'], $this->outerMarkerArray, $subpartArray);
			
			$pdf->writeHTMLCell($this->conf['cart-width'], 0, $this->conf['cart-position-x'], $this->conf['cart-position-y'], $html, 0, 2);

			$pdf->Output($this->conf['dir'].'/'.$filename, 'F');


		}

		// CHECK: Was PDF not created, send E-Mail and exit with error.
		if (!file_exists($this->conf['dir'].'/'.$filename)) {
			// send E-Mail
			$erroremailaddress = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf['erroremailaddress'], $this->conf['erroremailaddress.']);
			if ($erroremailaddress) {
				t3lib_div::plainMailEncoded($erroremailaddress,"wt_cart_orderpdf - PDF was not created", "wt_cart_orderpdf cannot create the PDF for packinglist. Order was aborted.",$headers='',$enc='',$charset='',$dontEncodeHeader=false);
			}
			return $abortonerror;
		}
		$session['files'][$filename] = $this->conf['dir'].'/'.$filename;
	}

	private function renderOrderAddress(&$pdf) {
		$orderaddress = "";

		$orderaddress = "";
		for ($line = 1; $line <= 5; $line++) {
			if ($orderaddressrow = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf['orderaddress.'][$line], $this->conf['orderaddress.'][$line.'.']))
			{
				$orderaddress .= $orderaddressrow . "\n";
			}
		}
		
		if (!$orderaddress == "") {
			if ($orderaddresstitle = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf['orderaddress.']['0'], $this->conf['orderaddress.']['0.']))
			{
				$pdf->SetXY($this->conf['orderaddress-position-x'], intval($this->conf['orderaddress-position-y'])-4);
				$pdf->SetFontSize(intval($this->conf['font-size'])-2);
				$pdf->MultiCell(70, 4, $orderaddresstitle);
				$pdf->SetFontSize($this->conf['font-size']);
			}
		
			$pdf->SetXY($this->conf['orderaddress-position-x'], $this->conf['orderaddress-position-y']);

			$pdf->MultiCell(70, 4, $orderaddress);
		}
	}

	private function renderShippingAddress(&$pdf, $fallback=false) {
		$shippingaddress = "";

		for ($line=1; $line<=5; $line++) {
			if ($shippingaddressrow = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf['shippingaddress.'][$line], $this->conf['shippingaddress.'][$line.'.']))
			{
				$shippingaddress .= $shippingaddressrow . "\n";
			}
			
		}

		if (!$shippingaddress == "") {
			if ($shippingaddresstitle = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf['shippingaddress.']['0'], $this->conf['shippingaddress.']['0.']))
			{
				$pdf->SetXY($this->conf['shippingaddress-position-x'], intval($this->conf['shippingaddress-position-y'])-4);
				$pdf->SetFontSize(intval($this->conf['font-size'])-2);
				$pdf->MultiCell(70, 4, $shippingaddresstitle);
				$pdf->SetFontSize($this->conf['font-size']);
			}

			$pdf->SetXY($this->conf['shippingaddress-position-x'], $this->conf['shippingaddress-position-y']);

			$pdf->MultiCell(70, 4, $shippingaddress);
		} elseif ($fallback) {
			$this->renderOrderAddress($pdf);
		}
	}

	private function renderCartHeadline(&$subpartArray) {

		foreach ((array) $this->wtcartconf['powermailCart.']['fields.'] as $key => $value)
		{ 
			if (!stristr($key, '.'))
			{ 
				$subpartArray['###WTCART_LL_' . strtoupper($key) . '###'] = $this->pi_getLL('wtcartorderpdf_ll_' . $key);
			}
		}

	}

	private function renderCartProduct($product) {
		$local_cObj = $GLOBALS['TSFE']->cObj; // cObject
		$product['price_total'] = $product['price'] * $product['qty']; // price total
		$local_cObj->start($product, $this->wtcartconf['db.']['table']); // enable .field in typoscript

		foreach ((array) $this->wtcartconf['powermailCart.']['fields.'] as $key => $value)
		{ 
			if (!stristr($key, '.'))
			{ 
				$productOut[$key] = $local_cObj->cObjGetSingle($this->wtcartconf['powermailCart.']['fields.'][$key], $this->wtcartconf['powermailCart.']['fields.'][$key . '.']); // write to marker
				$productOut[$key] = str_replace('&euro;', '€', $productOut[$key]);
				$productOut[$key] = str_replace('&nbsp;', ' ', $productOut[$key]);
				
				$this->markerArray['###' . strtoupper($key) . '###'] = $productOut[$key]; // write to marker
			}
		}

		return $GLOBALS['TSFE']->cObj->substituteMarkerArrayCached($this->tmpl['item'], $this->markerArray); // add inner html to variable
	}

	private function renderCartSum(&$subpartArray, $session) {
		global $TSFE;

		$outerArr = array(
			'service_cost_net' => $session['service_cost_net'], 
			'service_cost_gross' => $session['service_cost_gross'],
			'cart_gross' => $session['cart_gross'],
			'cart_gross_no_service' => $session['cart_gross_no_service'],
			'cart_net' => $session['cart_net'],
			'cart_net_no_service' => $session['cart_net_no_service'],
			'cart_tax_reduced' => $session['cart_tax_reduced'],
			'cart_tax_normal' => $session['cart_tax_normal']
		);

		$local_cObj = $GLOBALS['TSFE']->cObj;
		$local_cObj->start($outerArr, $this->wtcartconf['db.']['table']);

		foreach ((array) $this->wtcartconf['powermailCart.']['overall.'] as $key => $value)
		{ // one loop for every param of the current product
			if (!stristr($key, '.'))
			{ // no .
				$out = $local_cObj->cObjGetSingle($this->wtcartconf['powermailCart.']['overall.'][$key], $this->wtcartconf['powermailCart.']['overall.'][$key . '.']); // write to marker
				$out = str_replace('&euro;', '€', $out);
				$out = str_replace('&nbsp;', ' ', $out);

				$subpartArray['###' . strtoupper($key) . '###'] = $out;
			}
		}

		$subpartArray['###WTCART_LL_CART_NET###'] = $this->pi_getLL('wtcartorderpdf_ll_cart_net');
		$subpartArray['###WTCART_LL_SERVICE_COST###'] = $this->pi_getLL('wtcartorderpdf_ll_service_cost');
		$subpartArray['###WTCART_LL_TAX###'] = $this->pi_getLL('wtcartorderpdf_ll_tax');
		$subpartArray['###WTCART_LL_GROSS_TOTAL###'] = $this->pi_getLL('wtcartorderpdf_ll_gross_total');
		$subpartArray['###WTCART_LL_SHIPPING###'] = $this->pi_getLL('wtcartorderpdf_ll_shipping');
		$subpartArray['###WTCART_LL_PAYMENT###'] = $this->pi_getLL('wtcartorderpdf_ll_payment');
		$subpartArray['###WTCART_LL_SPECIAL###'] = $this->pi_getLL('wtcartorderpdf_ll_special');
	
		$subpartArray['###SHIPPING_OPTION###'] = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.']['shipping.']['options.'][$session['shipping'].'.']['title'];
		$subpartArray['###PAYMENT_OPTION###'] = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.']['payment.']['options.'][$session['payment'].'.']['title'];
		
		$subpartArray['###SPECIAL_OPTION###'] = '';
		if (isset($session['special'])) {
			foreach ($session['special'] as $special_id) {
				$subpartArray['###SPECIAL_OPTION###'] .= $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.']['special.']['options.'][$special_id.'.']['title'];
			}
		}
	}

	private function renderOrderNumber(&$pdf, $type='ordernumber') {
		$number = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->wtcartconf['powermailCart.']['overall.'][$type], $this->wtcartconf['powermailCart.']['overall.'][$type . '.']);
		
		$pdf->SetXY($this->conf[$type . '-position-x'], $this->conf[$type . '-position-y']);

		$pdf->Cell('150', '6', $number);
	}

	private function renderAdditionalTextblocks(&$pdf) {
		foreach ($this->conf['additionaltextblocks.'] as $key => $value) {
			$html = $GLOBALS['TSFE']->cObj->cObjGetSingle($value['content'], $value['content.']);

			$pdf->writeHTMLCell($value['width'], $value['height'], $value['position-x'], $value['position-y'], $html, 0, 2, 0, true, $value['align'] ? $value['align'] : 'L', true);
		}
	}

	private function renderPaymentOption(&$pdf, $payment_id) {
		$payment_option = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.']['payment.']['options.'][$payment_id . '.'];
		
		if ($payment_option['note']) {
			$pdf->SetY($pdf->GetY()+20);
			$pdf->SetX($this->conf['cart-position-x']);
			$pdf->Cell('150', '5', $payment_option['title'], 0, 1);
			$pdf->SetX($this->conf['cart-position-x']);
			$pdf->Cell('150', '5', $payment_option['note'], 0, 1);
		}
	} 

	private function concatFileName($filename) {
		$date = date("Ymd");

		$pathfilename = $date.'-'.$filename.'.pdf';

		return $pathfilename;
	}

}

?>