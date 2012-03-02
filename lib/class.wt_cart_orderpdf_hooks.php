<?php
	if (t3lib_extMgm::isLoaded('fpdf'))    {
		require(t3lib_extMgm::extPath('fpdf').'class.tx_fpdf.php');
	}

	class user_wt_cart_orderpdf_hooks {
		function createPdf(array $params, $session) {
			$wtcartconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.']['settings.']['overall.'];
			$orderpdfconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_orderpdf.']['settings.'];
			$ordernumber = $GLOBALS['TSFE']->cObj->cObjGetSingle($orderpdfconf['ordernumber'], $orderpdfconf['ordernumber.']);
			#t3lib_div::debug($ordernumber);
			#t3lib_div::debug($conf);
			#t3lib_div::debug($orderpdfconf);
			#t3lib_div::debug($session);

			if (!is_dir($orderpdfconf['dir'])) {
				return 1;
			}

			$pdf = new FPDF();
			$pdf->AddPage();
			$pdf->SetFont('Arial','B',16);
			$pdf->Cell(40,10,'Hello World!');

			$pathfilename = $this->concatFileName($orderpdfconf['dir'], $ordernumber);

			$pdf->Output($pathfilename, 'F');

			return 0;
		}


		private 

		function concatFileName($path, $filename) {
			$date = date("Ymd");

			$pathfilename = $path.'/'.$date.'-'.$filename.'.pdf';

			return $pathfilename;
		}

	}

?>