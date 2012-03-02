<?php

	class user_wt_cart_orderpdf_hooks {
		function createPdf(array $params, $Obj) {
			$ordernumber = &$params['ordernumber'];

//			t3lib_div::debug($ordernumber);
//			t3lib_div::debug($Obj);

			return 0;
		}
	}

?>