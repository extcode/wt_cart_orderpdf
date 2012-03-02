<?php

########################################################################
# Extension Manager/Repository config file for ext "wt_cart_orderpdf".
#
# Auto generated 02-03-2012 14:38
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Order PDF Generator for wt_cart',
	'description' => 'This Extension uses fpdf and Hook the wt_cart to attach the Order PDF.',
	'category' => 'services',
	'author' => 'Daniel Lorenz',
	'author_email' => 'info@capsicum-ug.de',
	'shy' => '',
	'dependencies' => 'fpdf,wt_cart',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.0',
	'constraints' => array(
		'depends' => array(
			'fpdf' => '',
			'wt_cart' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:5:{s:9:"ChangeLog";s:4:"18f3";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"1bdc";s:19:"doc/wizard_form.dat";s:4:"f113";s:20:"doc/wizard_form.html";s:4:"ee6e";}',
);

?>