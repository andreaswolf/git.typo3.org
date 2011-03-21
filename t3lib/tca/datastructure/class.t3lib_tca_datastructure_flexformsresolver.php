<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Resolver for XML-based FlexForm data structures.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCA_DataStructure_FlexFormsResolver extends t3lib_TCA_DataStructure_Resolver implements t3lib_Singleton {
	/**
	 *
	 * @var t3lib_flexformtools
	 */
	protected $flexFormTools;

	public function __construct() {
		$this->flexFormTools = t3lib_div::makeInstance('t3lib_flexformtools');
	}

	/**
	 * Resolves a FlexForm XML data structure into a matching DataStructure object.
	 *
	 * @param t3lib_TCEforms_Element_Flex $flexElementObject
	 * @return t3lib_TCA_DataStructure The DataStructure object
	 */
	public function resolveDataStructure(t3lib_TCEforms_Element_Flex $flexElementObject) {
		$record = $flexElementObject->getRecordObject()->getRecordData();
		$table = $flexElementObject->getRecordObject()->getTable();
		$field = $flexElementObject->getFieldname();
		$fieldConfig = $flexElementObject->getFieldSetup();

		$dataStructureArray = t3lib_BEfunc::getFlexFormDS($fieldConfig['config'], $record, $table);

		if (!is_array($dataStructureArray)) {
			throw new RuntimeException('Decoding FlexForm DataStructure failed: ' . $dataStructureArray);
		}

		$TCAcolumnsArray = $this->extractColumnsFromDataStructureArray($dataStructureArray);
		$TCAcontrolArray = $this->extractControlInformationFromDataStructureArray($dataStructureArray);
		$TCAsheetsArray = $this->extractSheetInformation($dataStructureArray);

		$TCAentry = array(
			'ctrl' => $TCAcontrolArray,
			'columns' => $TCAcolumnsArray,
			'sheets' => $TCAsheetsArray,
			'meta' => $dataStructureArray['meta']
		);

		$dataStructureObject = t3lib_div::makeInstance('t3lib_TCA_FlexFormDataStructure', $TCAentry);

		return $dataStructureObject;
	}

	protected function extractColumnsFromDataStructureArray($dataStructureArray) {
		$TCAcolumns = array();
		foreach ($dataStructureArray['sheets'] as $sheet) {
			foreach ($sheet['ROOT']['el'] as $elementName => $elementConfig) {
				$TCAcolumns[$elementName] = $elementConfig['TCEforms'];
			}
		}

		return $TCAcolumns;
	}


	/**
	 * Extracts the information about the sheets this record offers and the fields they have.
	 *
	 * This function is the only one neccessary for extracting record structure information, as
	 * there is only one type with flexform records (instead of possibly many types and subtypes with
	 * TCA based records)
	 *
	 * @param array $dataStructureArray
	 * @return array
	 */
	protected function extractSheetInformation($dataStructureArray) {
		$sheets = array();
		foreach ($dataStructureArray['sheets'] as $sheetName => $sheet) {
			$currentSheet = array(
				'name' => $sheetName,
				'title' => $GLOBALS['LANG']->sL($sheet['ROOT']['TCEforms']['sheetTitle']),
				'elements' => array()
			);

			foreach (array_keys($sheet['ROOT']['el']) as $elementName) {
				$currentSheet['elements'][] = $elementName;
			}

			$sheets[] = $currentSheet;
		}

		return $sheets;
	}

	/**
	 * Extracts all available information for a TCA control array from a given Flexform XML DataStructure.
	 *
	 * @param unknown_type $dataStructureArray
	 * @return unknown_type
	 */
	protected function extractControlInformationFromDataStructureArray($dataStructureArray) {
		$TCAcontrolInformation = array();

		return $TCAcontrolInformation;
	}
}

?>
