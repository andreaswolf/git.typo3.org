<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE')	{
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
		'name' => 'tx_impexp_clickmenu',
		'path' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_impexp_clickmenu.php'
	);


	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']['impexp']['tx_impexp_task'] = array(
		'title'       => 'LLL:EXT:impexp/locallang_csh.xml:.alttitle',
		'description' => 'LLL:EXT:impexp/locallang_csh.xml:.description',
		'icon'		  => 'EXT:impexp/export.gif'
	);

	t3lib_extMgm::addLLrefForTCAdescr('xMOD_tx_impexp','EXT:impexp/locallang_csh.xml');

	// CSH labels for TYPO3 4.5 and greater.  These labels override the ones set above, while still falling back to the original labels if no translation is available.
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:impexp/locallang_csh.xml'][] = 'EXT:impexp/locallang_csh_45.xml';


}
?>