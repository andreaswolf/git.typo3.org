<?php

require_once(PATH_t3lib.'tceforms/container/class.t3lib_tceforms_container_sheet.php');

class t3lib_TCEforms_FormBuilder {

	/**
	 * The context object this builder builds elements for.
	 *
	 * @var t3lib_TCEforms_Context
	 */
	protected $contextObject;

	protected $formFieldNamePrefix;

	/**
	 * The data structure the target record is based on
	 *
	 * @var t3lib_TCA_DataStructure
	 */
	protected $dataStructure;

	/**
	 * The record object this form builder belongs to
	 *
	 * @var t3lib_TCEforms_Record
	 */
	protected $recordObject;

	/**
	 * The display configuration for the current record
	 *
	 * @var t3lib_TCA_DisplayConfiguration
	 */
	protected $displayConfiguration;

	/**
	 * The stack for generating element identifiers
	 *
	 * @var array<string>
	 */
	protected $elementIdentifierStack;

	/**
	 * The number of sheets this form builder has built
	 *
	 * @var integer
	 */
	protected $sheetCounter;


	protected function __construct(t3lib_TCEforms_Record $recordObject) {
		t3lib_div::devLog('Created new formbuilder object for record ' . $recordObject->getIdentifier() . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

		$this->recordObject = $recordObject;
		$this->contextObject = $recordObject->getContextObject();
		$this->dataStructure = $recordObject->getDataStructure();
		$this->displayConfiguration = $this->dataStructure->getDisplayConfigurationForRecord($this->recordObject);
		$this->elementIdentifierStack = $this->recordObject->getElementIdentifierStack();
	}

	public static function createInstanceForRecordObject(t3lib_TCEforms_Record $recordObject) {
		return new t3lib_TCEforms_Formbuilder($recordObject);
	}

	/**
	 * @deprecated
	 */
	public function getFormFieldNamePrefix() {
		return $this->formFieldNamePrefix;
	}

	/**
	 * @deprecated
	 */
	public function setFormFieldNamePrefix($prefix) {
		$this->formFieldNamePrefix = $prefix;

		return $this;
	}

	public function setContextObject(t3lib_TCEforms_Form $contextObject) {
		$this->contextObject = $contextObject;

		return $this;
	}


	/**
	 * Takes a record object and builds the TCEforms object structure for it.
	 *
	 * @return void
	 */
	public function buildObjectStructure() {
		t3lib_div::devLog('Started building object tree for record ' . $this->recordObject->getIdentifier() . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

		$sheets = $this->displayConfiguration->getSheets();

		foreach ($sheets as $sheet) {
			$this->createSheetObjectFromDefinition($sheet);
		}
	}


	/***********************************
	 * Element handling
	 ***********************************/

	/**
	 * Returns the object representation for a database table field.
	 *
	 * @param   string   $theField  The field name
	 * @param   string   $fieldConf The field configuration
	 * @param   string   $altName   Alternative field name label to show.
	 * @return  t3lib_TCEforms_Element_Abstract
	 */
	public function createObjectFromFieldDefinition(t3lib_TCA_DataStructure_Field $fieldDefinition) {
		$fieldConf = $fieldDefinition->getConfiguration();
		$label = $fieldDefinition->getLabel();
		$fieldName = $fieldDefinition->getName();

		// Using "form_type" locally in this script
		$fieldType = $fieldConf['config']['form_type'] ? $fieldConf['config']['form_type'] : $fieldConf['config']['type'];

		$elementObject = $this->createElementObject($fieldType, $fieldName, $fieldConf);
		$elementObject->setStyle($fieldDefinition->getStyle());

		if ($fieldDefinition->hasPalette()) {
			$paletteDefinition = $fieldDefinition->getPalette();

			$paletteObject = $this->createPaletteObjectFromDefinition($paletteDefinition);

			$elementObject->setPaletteObject($paletteObject);
		}

		$elementObject->init();

		return $elementObject;
	}

	/**
	 * Creates a palette container object from a TCA datastructure definition of a palette
	 *
	 * @param t3lib_TCA_DataStructure_Palette $paletteObject
	 * @return t3lib_TCEforms_Container_Palette
	 */
	protected function createPaletteObjectFromDefinition(t3lib_TCA_DataStructure_Palette $paletteDataStructureObject) {
		$dataStructureElements = $paletteDataStructureObject->getElements();

		/* @var $paletteObject t3lib_TCEforms_Container_Palette */
		$paletteObject = $this->createPaletteContainerObject($paletteDataStructureObject);

		foreach ($dataStructureElements as $element) {
			$fieldObject = $this->createObjectFromFieldDefinition($element);

			$paletteObject->addElement($fieldObject);
		}

		return $paletteObject;
	}

	/**
	 * Factory method for form element objects. Defaults to type "unknown" if the class(file)
	 * is not found.
	 *
	 * @param  string  $type  The type of record to create - directly taken from TCA
	 * @return t3lib_TCEforms_Element_Abstract  The element object
	 */
	// TODO: refactor this as soon as the autoloader is available in core
	protected function createElementObject($type, $theField = '', $fieldConf = array()) {
		switch ($type) {
			default:
				$className = 't3lib_TCEforms_Element_'.$type;
				break;
		}

		if (!class_exists($className)) {
				// if class(file) does not exist, resolve to type "unknown"
			if (!@file_exists(PATH_t3lib.'tceforms/element/class.'.strtolower($className).'.php')) {
				return $this->createElementObject('unknown', $theField, $fieldConf);
			}
			include_once PATH_t3lib.'tceforms/element/class.'.strtolower($className).'.php';
		}

		$elementObject = t3lib_div::makeInstance($className, $theField, $fieldConf);#
		$elementObject->setContextObject($this->contextObject)
		              ->setContextRecordObject($this->recordObject->getContextRecordObject())
		              ->setRecordObject($this->recordObject)
		              ->setParentFormObject($this->recordObject->getParentFormObject())
		              ->setTable($this->recordObject->getTable())
		              ->setRecord($this->recordObject->getRecordData())
		              ->setElementIdentifierStack($this->elementIdentifierStack)
		              ->injectFormBuilder($this);

		return $elementObject;
	}


	/***********************************
	 * Sheet handling
	 ***********************************/

	protected function createSheetObjectFromDefinition(t3lib_TCA_DataStructure_Sheet $sheetDefinition) {
		$sheetObject = $this->createSheetObject($sheetDefinition);
		$this->recordObject->addSheetObject($sheetObject);

		foreach ($sheetDefinition->getElements() as $fieldObject) {
			if (is_a($fieldObject, 't3lib_TCA_DataStructure_Field')) {
				/* @var $element t3lib_TCA_DataStructure_Field */
				$elementObject = $this->createObjectFromFieldDefinition($fieldObject);
				$elementObject->init();

				$sheetObject->addChildObject($elementObject);
			} elseif (is_a($fieldObject, 't3lib_TCA_DataStructure_Palette')) {
				$paletteContainerObject = $this->createPaletteObjectFromDefinition($fieldObject);

				/* @var $paletteElementObject t3lib_TCEforms_Element_Palette */
				$paletteElementObject = $this->createElementObject('palette', $fieldObject->getLabel());
				$paletteElementObject->setPaletteObject($paletteContainerObject);
				$paletteElementObject->init();

				$sheetObject->addChildObject($paletteElementObject);
			}
		}
	}

	/**
	 * Factory method for sheet objects on forms.
	 *
	 * @param   string  $sheetIdentString  The identifier of the sheet. Must be unique for the whole form
	 *                                     (and all sub-forms!)
	 * @param   t3lib_TCA_DataStructure_Sheet  $sheetDefinition
	 * @return  t3lib_TCEforms_Sheet
	 *
	 * @TODO Check if this must be public
	 */
	public function createSheetObject(t3lib_TCA_DataStructure_Sheet $sheetDefinition) {
		++$this->sheetCounter;
		$sheetIdentString = $this->recordObject->getShortSheetIdentifier() . '-' . $this->sheetCounter;

		$sheetObject = new t3lib_TCEforms_Container_Sheet($sheetIdentString, $sheetDefinition->getLabel(),
		  $sheetDefinition->getName());
		$sheetObject->setElementIdentifierStack($this->elementIdentifierStack);

		return $sheetObject;
	}


	/***********************************
	 * Palette handling
	 ***********************************/

	public function createPaletteElement($paletteNumber, $label) {
		return $this->createElementObject('palette');
	}

	/**
	 * Enter description here ...
	 *
	 * @return t3lib_TCEforms_Container_Palette
	 */
	protected function createPaletteContainerObject(t3lib_TCA_DataStructure_Palette $paletteDataStructure) {
		$paletteObject = new t3lib_TCEforms_Container_Palette($paletteDataStructure, $paletteDataStructure->getName());
		$paletteObject->setContextObject($this->contextObject)
		              ->setRecordObject($this->recordObject)
		              ->init();

		return $paletteObject;
	}

	/**
	 * Creates objects for all main palettes (palettes existing side-by-side with normal elements,
	 * in contrast to palettes that are tied to an element). These palettes are defined in the control
	 * section of TCA, key "mainpalette". Multiple palettes are separated by commas.
	 *
	 * @return void
	 *
	 * @deprecated
	 */
	protected function resolveMainPalettes() {
		t3lib_div::devLog('Building top-level palette elements for ' . $this->recordObject->getIdentifier() . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

		$mainPalettesArray = t3lib_div::trimExplode(',', $this->dataStructure->getControlValue('mainpalette'), TRUE);

		$i = 0;
		foreach ($mainPalettesArray as $paletteNumber) {
			++$i;

			if ($this->recordObject->isPaletteCreated($paletteNumber)) {
				t3lib_div::devLog("Palette no $paletteNumber in record from table " . $this->recordObject->getTable() . ' has already been created, so it won\'t be created a second time. Please check the TCA definition for any wrong/double palette assignments.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_WARNING);

				continue;
			}
			$label = $i==1 ? $this->getLL('l_generalOptions') : $this->getLL('l_generalOptions_more');

			$paletteFieldObject = $this->createPaletteElement($paletteNumber, $label);

			$paletteFieldObject->setContextObject($this->contextObject)
			                   ->setRecordObject($this->recordObject)
			                   ->setParentFormObject($this->recordObject->getParentFormObject())
			                   ->setTable($this->recordObject->getTable())
			                   ->setRecord($this->recordObject->getRecordData())
			                   ->injectFormBuilder($this)
			                   ->init();

			$this->currentSheet->addChildObject($paletteFieldObject);
			/*$this->wrapBorder($out_array[$out_sheet],$out_pointer);
			if ($this->renderDepth)	{
				$this->renderDepth--;
			}*/
		}
	}

	/**
	 * Returns language label from locallang_core.php
	 * Labels must be prefixed with either "l_" or "m_".
	 * The prefix "l_" maps to the prefix "labels." inside locallang_core.php
	 * The prefix "m_" maps to the prefix "mess." inside locallang_core.php
	 *
	 * @param   string  The label key
	 * @return  string  The value of the label, fetched for the current backend language.
	 */
	protected function getLL($str) {
		$content = '';

		switch(substr($str, 0, 2)) {
			case 'l_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.' . substr($str,2));
			break;
			case 'm_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.' . substr($str,2));
			break;
		}
		return $content;
	}
}

?>