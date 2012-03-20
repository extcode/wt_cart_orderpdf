<?php

########################################################################
# Extension Manager/Repository config file for ext "wt_cart_orderpdf".
#
# Auto generated 20-03-2012 13:25
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
	'dependencies' => 'wt_cart',
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
	'version' => '0.0.5',
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
	'_md5_values_when_last_written' => 'a:13:{s:9:"ChangeLog";s:4:"18f3";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"78bf";s:14:"ext_tables.php";s:4:"7843";s:19:"doc/wizard_form.dat";s:4:"f113";s:20:"doc/wizard_form.html";s:4:"ee6e";s:17:"files/include.pdf";s:4:"74a5";s:26:"files/static/constants.txt";s:4:"1bd6";s:22:"files/static/setup.txt";s:4:"22a1";s:31:"files/templates/cart_table.html";s:4:"066a";s:36:"lib/class.wt_cart_orderpdf_hooks.php";s:4:"c841";s:17:"lib/locallang.xml";s:4:"98ca";}',
	'suggests' => array(
	),
);

?>