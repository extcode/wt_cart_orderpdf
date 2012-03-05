<?php
	require_once(PATH_tslib . 'class.tslib_pibase.php');

	define('EURO',chr(128));


	if (t3lib_extMgm::isLoaded('fpdf'))    {
		require(t3lib_extMgm::extPath('fpdf').'class.tx_fpdf.php');
	}

	class user_wt_cart_orderpdf_hooks {
		public function createPdf(&$params, &$session) {
			$this->wtcartconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.']['settings.'];
			$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_orderpdf.'];

			// CHECK: Should PDF attached to mail?
			unset($session['files']);
			if (($params['subpart']=='recipient_mail') && ($this->conf['orderpdf.']['attach_recipient_mail'] == 1)) {
				$this->renderOrderPdf($session);
			}
			if (($params['subpart']=='sender_mail') && ($this->conf['orderpdf.']['attach_sender_mail'] == 1)) {
				$this->renderOrderPdf($session);
			}
			if (($params['subpart']=='recipient_mail') && ($this->conf['packinglistpdf.']['attach_recipient_mail'] == 1)) {
				$this->renderPackinglistPdf($session);
			}
			if (($params['subpart']=='sender_mail') && ($this->conf['packinglistpdf.']['attach_sender_mail'] == 1)) {
				$this->renderPackinglistPdf($session);
			}

			return 0;
		}

		private function renderOrderPdf(&$session) {
			$ordernumber = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf['orderpdf.']['ordernumber'], $this->conf['orderpdf.']['ordernumber.']);
			$filename = $this->concatFileName($ordernumber);

			// CHECK: Is directory for PDF available?
			if (!is_dir($this->conf['orderpdf.']['dir'])) {
				return 1;
			}
			// CHECK: Is PDF alreade created?
			if (!file_exists($this->conf['orderpdf.']['dir'].'/'.$filename)) {
				// generate the PDF
				$pdf = new FPDI();
				$pdf->AddPage();

				if ($this->conf['orderpdf.']['include_file']) {
					$pdf->setSourceFile('typo3conf/ext/wt_cart_orderpdf/'.$this->conf['orderpdf.']['include_file']);
					$tplIdx = $pdf->importPage(1);
					$pdf->useTemplate($tplIdx, 0, 0, 210);
				}

				$pdf->SetFont('Arial','',8);

				$pdf = $this->renderOrderAddress($pdf, $this->conf['orderpdf.']);
				$pdf = $this->renderShippingAddress($pdf, $this->conf['orderpdf.']);

				$pdf = $this->renderOrderNumber($pdf, $this->conf['orderpdf.'], 'ordernumber');

				$pdf->SetY($this->conf['orderpdf.']['cart-position-y']);

				$pdf = $this->renderCartHeadline($pdf, $this->conf['orderpdf.']);

				foreach ($session['products'] as $key => $product) {
					$pdf = $this->renderCartProduct($pdf, $product, $this->conf['orderpdf.']);
				}

				$pdf = $this->renderPaymentOption($pdf, $session['payment'], $this->conf['orderpdf.']);

				$pdf->Output($this->conf['orderpdf.']['dir'].'/'.$filename, 'F');
			}

			$session['files'][$filename] = $this->conf['orderpdf.']['dir'].'/'.$filename;
		}

		private function renderPackinglistPdf(&$session) {
			$packinglistnumber = $GLOBALS['TSFE']->cObj->cObjGetSingle($this->conf['packinglistpdf.']['packinglistnumber'], $this->conf['packinglistpdf.']['packinglistnumber.']);
			$filename = $this->concatFileName($packinglistnumber);

			// CHECK: Is directory for PDF available?
			if (!is_dir($this->conf['packinglistpdf.']['dir'])) {
				return 1;
			}


			// CHECK: Is PDF alreade created?
			if (!file_exists($this->conf['packinglistpdf.']['dir'].'/'.$filename)) {
				// generate the PDF
				$pdf = new FPDI();
				$pdf->AddPage();

				if ($this->conf['packinglistpdf.']['include_file']) {
					$pdf->setSourceFile('typo3conf/ext/wt_cart_orderpdf/'.$this->conf['packinglistpdf.']['include_file']);
					$tplIdx = $pdf->importPage(1);
					$pdf->useTemplate($tplIdx, 0, 0, 210);
				}

				$pdf->SetFont('Arial','',8);

				#$pdf = $this->renderOrderAddress($pdf, $this->conf['packinglistpdf.']);
				$pdf = $this->renderShippingAddress($pdf, $this->conf['packinglistpdf.']);

				$pdf = $this->renderOrderNumber($pdf, $this->conf['packinglistpdf.'], 'packinglistnumber');

				$pdf->SetY($this->conf['packinglistpdf.']['cart-position-y']);

				$pdf = $this->renderCartHeadline($pdf, $this->conf['packinglistpdf.']);

				foreach ($session['products'] as $key => $product) {
					$pdf = $this->renderCartProduct($pdf, $product, $this->conf['packinglistpdf.']);
				}

				$pdf = $this->renderPaymentOption($pdf, $session['payment'], $this->conf['packinglistpdf.']);

				$pdf->Output($this->conf['packinglistpdf.']['dir'].'/'.$filename, 'F');
			}

			$session['files'][$filename] = $this->conf['packinglistpdf.']['dir'].'/'.$filename;
		}

		private function renderOrderAddress($pdf, $conf) {
			$orderaddress = "";
			if ($orderaddress = utf8_decode($GLOBALS['TSFE']->cObj->cObjGetSingle($conf['orderaddress.']['0'], $conf['orderaddress.']['0.']))) {
				$pdf->SetXY($conf['orderaddress-position-x'], intval($conf['orderaddress-position-y'])-4);
				$pdf->SetFontSize(6);
				$pdf->MultiCell(70, 4, $orderaddress);
				$pdf->SetFontSize(8);
			}

			$orderaddress = "";
			for ($line = 1; $line <= 5; $line++) {
				$orderaddress .= utf8_decode($GLOBALS['TSFE']->cObj->cObjGetSingle($conf['orderaddress.'][$line], $conf['orderaddress.'][$line.'.']));
				$orderaddress .= "\n";
			}
			
			if (!$orderaddress == "") {
				$pdf->SetXY($conf['orderaddress-position-x'], $conf['orderaddress-position-y']);

				$pdf->MultiCell(70, 4, $orderaddress);
			}

			return $pdf;
		}

		private function renderShippingAddress($pdf, $conf) {
			$shippingaddress = "";
			if ($shippingaddress = utf8_decode($GLOBALS['TSFE']->cObj->cObjGetSingle($conf['shippingaddress.']['0'], $conf['shippingaddress.']['0.']))) {
				$pdf->SetXY($conf['shippingaddress-position-x'], intval($conf['shippingaddress-position-y'])-4);
				$pdf->SetFontSize(6);
				$pdf->MultiCell(70, 4, $shippingaddress);
				$pdf->SetFontSize(8);
			}

			$shippingaddress = "";
			for ($line=1; $line<=5; $line++) {
				$shippingaddress .= utf8_decode($GLOBALS['TSFE']->cObj->cObjGetSingle($conf['shippingaddress.'][$line], $conf['shippingaddress.'][$line.'.']));
				$shippingaddress .= "\n";
			}

			if (!$shippingaddress == "") {
				$pdf->SetXY($conf['shippingaddress-position-x'], $conf['shippingaddress-position-y']);

				$pdf->MultiCell(70, 4, $shippingaddress);
			}

			return $pdf;
		}

		private function renderCartHeadline($pdf, $conf) {
			$pdf->SetX($conf['cart-position-x']);

			$pdf->SetFillColor(230,230,230);

			$pdf->Cell(10,6,'#',1,0,'L',1);
			$pdf->Cell(90,6,'Bezeichnung',1,0,'L',1);
			$pdf->Cell(25,6,'Preis',1,0,'R',1);
			$pdf->Cell(35,6,'Gesamtpreis',1,1,'R',1);

			$pdf->SetFillColor(0,0,0);

			return $pdf;
		}

		private function renderCartProduct($pdf, $product, $conf) {
			$pdf->SetX($conf['cart-position-x']);

			$local_cObj = $GLOBALS['TSFE']->cObj; // cObject
			$product['price_total'] = $product['price'] * $product['qty']; // price total
			$local_cObj->start($product, $this->wtcartconf['db.']['table']); // enable .field in typoscript
			foreach ((array) $this->wtcartconf['powermailCart.']['fields.'] as $key => $value)
			{ // one loop for every param of the current product
				if (!stristr($key, '.'))
				{ // no .
					$productOut[$key] = $local_cObj->cObjGetSingle($this->wtcartconf['powermailCart.']['fields.'][$key], $this->wtcartconf['powermailCart.']['fields.'][$key . '.']); // write to marker
					$productOut[$key] = str_replace('&euro;', chr(128), $productOut[$key]);
					$productOut[$key] = str_replace('&nbsp;', ' ', $productOut[$key]);
				
				}
			}
			
			$pdf->Cell(10,6,$product['qty'],1);
			$pdf->Cell(90,6,iconv('UTF-8', 'ISO-8859-1', $product['title']),1);
			$pdf->Cell(25,6,htmlspecialchars_decode($productOut['price']),1,0,'R');
			$pdf->Cell(35,6,$productOut['price_total'],1,1,'R');

			return $pdf;
		}

		private function renderOrderNumber($pdf, $conf, $type='ordernumber') {
			$number = utf8_decode($GLOBALS['TSFE']->cObj->cObjGetSingle($this->wtcartconf['powermailCart.']['overall.'][$type], $this->wtcartconf['powermailCart.']['overall.'][$type . '.']));
			
			$pdf->SetXY($conf[$type . '-position-x'], $conf[$type . '-position-y']);

			$pdf->SetFont('Arial','B',10);
			$pdf->Cell('150', '6', $number);
			$pdf->SetFont('Arial','',8);

			return $pdf;	
		}

		private function renderPaymentOption($pdf, $payment_id, $conf) {
			$payment_option = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.']['payment.']['options.'][$payment_id . '.'];
			
			$pdf->SetY($pdf->GetY()+20);
			$pdf->SetX($conf['cart-position-x']);
			$pdf->Cell('150', '5', utf8_decode($payment_option['title']), 0, 1);
			$pdf->SetX($conf['cart-position-x']);
			$pdf->Cell('150', '5', utf8_decode($payment_option['note']), 0, 1);

			return $pdf;
		} 

		private function concatFileName($filename) {
			$date = date("Ymd");

			$pathfilename = $date.'-'.$filename.'.pdf';

			return $pathfilename;
		}

	}

?>