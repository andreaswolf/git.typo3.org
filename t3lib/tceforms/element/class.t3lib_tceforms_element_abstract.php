<?php

require_once(PATH_t3lib.'interfaces/interface.t3lib_tceforms_element.php');
require_once(PATH_t3lib.'tceforms/container/class.t3lib_tceforms_container_palette.php');

abstract class t3lib_TCEforms_Element_Abstract implements t3lib_TCEforms_Element {
	/**
	 * The container containing this element (may be a sheet, a palette, ...).
	 *
	 * @var t3lib_TCEforms_Container
	 */
	protected $container;

	protected $alternativeName;

	/**
	 * The TCA config for the field
	 *
	 * @var array
	 */
	protected $fieldConfig;

	protected $hiddenFieldListArr = array();

	protected static $cachedTSconfig;

	protected static $hookObjects = array();

	protected $fieldChangeFunc = array();

	protected $table;

	protected $field;

	protected $record;

	/**
	 * Vars related to palettes
	 */

	/**
	 * The palette object
	 *
	 * @var t3lib_TCEforms_Palette
	 */
	protected $paletteObject;

	protected $parentRecordObject;

	protected $pal = NULL;

	protected $hasPalette = false;

	protected $isInPalette = false;

	/**
	 * The context this element is in (i.e., the top-level form)
	 *
	 * @var t3lib_TCEforms_Context
	 */
	protected $contextObject;

	/*
	 * Form field width compensation: Factor from NN4 form field widths to style-aware browsers
	 * (like NN6+ and MSIE, with the $CLIENT[FORMSTYLE] value set)
	 *
	 * @var float
	 */
	protected $form_rowsToStylewidth = 9.58;

	/**
	 * Form field width compensation: Compensation for large documents, doc-tab (editing)
	 *
	 * @var float
	 */
	protected $form_largeComp = 1.33;

	/**
	 * The number of chars expected per row when the height of a text area field is automatically
	 * calculated based on the number of characters found in the field content.
	 *
	 * @var integer
	 */
	protected $charsPerRow=40;

	/**
	 * The maximum abstract value for textareas
	 *
	 * @var integer
	 */
	protected $maxTextareaWidth=48;

	/**
	 * The maximum abstract value for input fields
	 *
	 * @var integer
	 */
	protected $maxInputWidth=48;

	/**
	 * Default style for the selector boxes used for multiple items in "select" and "group" types.
	 *
	 * @var string
	 */
	protected $defaultMultipleSelectorStyle = 'width:250px;';

	protected $classScheme = array();

	protected $borderStyle = array();

	protected $fieldStyle = array();

	/**
	 *
	 *
	 * @var t3lib_TCEforms_FormBuilder
	 */
	protected $injectFormBuilder;




	public function __construct($field, $fieldConfig, $alternativeName='', $extra='', $parentObject = NULL) {
		global $TYPO3_CONF_VARS;

			// Field config is the same as $PA['fieldConf'] below
		$this->fieldConfig = $fieldConfig;
		$this->field = $field;
		$this->extra = $extra;
		$this->alternativeName = $alternativeName;

		$this->parentObject = $parentObject;

		if (count(self::$hookObjects) == 0) {
				// Prepare user defined objects (if any) for hooks which extend this function:
			self::$hookObjects['getMainFields'] = array();
			if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass']))	{
				foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'] as $classRef)	{
					self::$hookObjects['getMainFields'][] = &t3lib_div::getUserObj($classRef);
				}
			}

			self::$hookObjects['getSingleFields'] = array();
			if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass']))	{
				foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'] as $classRef)	{
					self::$hookObjects['getSingleFields'][] = &t3lib_div::getUserObj($classRef);
				}
			}
		}

		$this->resetStyles();
	}

	public function init() {
		// code mainly copied/moved from t3lib_tceforms::getSingleField

		// TODO: rename this to formFieldNamePrefix/formFieldFileNamePrefix
		$this->prependFormFieldNames = $this->parentRecordObject->getFormFieldNamePrefix();//'data';//$this->TCEformsObject->prependFormFieldNames;
		$this->prependFormFieldNames_file = $this->parentRecordObject->getFormFieldNamePrefix();//$this->TCEformsObject->prependFormFieldNames_file;
		$this->formFieldIdPrefix = $this->parentRecordObject->getFormFieldIdPrefix();;

			// Init variables:
		$this->itemFormElName = $this->prependFormFieldNames.'['.$this->field.']'; // Form field name
		$this->itemFormElName_file = $this->prependFormFieldNames_file.'['.$this->field.']'; // Form field name, in case of file uploads
		$this->itemFormElValue = $this->record[$this->field]; // The value to show in the form field.
		$this->itemFormElID = $this->formFieldIdPrefix . '_' . $this->field;

			// Hook: getSingleField_preProcess
		foreach (self::$hookObjects['getSingleFields'] as $hookObj)	{
			if (method_exists($hookObj,'getSingleField_preProcess'))	{
				$hookObj->getSingleField_preProcess($this->table, $this->field, $this->record, $this->alternativeName, $this->palette, $this->extra, $this->pal, $this);
			}
		}

		$this->defaultLanguageValue = $this->parentRecordObject->getDefaultLanguageValue($this->field);


		// TODO: move this to AbstractForm
		//$skipThisField = $this->inline->skipField($table, $this->field, $this->record, $this->fieldConfig['config']);
	}

	public function injectFormBuilder(t3lib_TCEforms_FormBuilder $formBuilder) {
		$this->formBuilder = $formBuilder;

		return $this;
	}

	public function initializePalette($paletteNumber) {
		$paletteClassName = t3lib_div::makeInstanceClassName('t3lib_TCEforms_Container_Palette');

		$this->paletteObject = new $paletteClassName($paletteNumber);
		$this->paletteObject->setContainingObject($this)
		                    ->setContextObject($this->contextObject)
		                    ->setRecordObject($this->parentRecordObject)
		                    ->injectFormBuilder($this->formBuilder);

		$this->hasPalette = TRUE;
	}

	public function setRecord($record) {
		$this->record = $record;

		return $this;
	}

	public function setTable($table) {
		$this->table = $table;

		return $this;
	}

	public function setParentRecordObject($parentRecordObject) {
		$this->parentRecordObject = $parentRecordObject;

		return $this;
	}

	public function setContextObject(t3lib_TCEforms_Context $contextObject) {
		$this->contextObject = $contextObject;

		$this->backPath = $contextObject->getBackpath();

		return $this;
	}

	public function setItemFormElementName($itemFormElementName) {
		$this->itemFormElName = $itemFormElementName;
		$this->itemFormElName_file = $itemFormElementName;

		return $this;
	}

	public function setItemFormElementID($itemFormElementID) {
		$this->itemFormElID = $itemFormElementID;

		return $this;
	}

	public function setItemFormElementValue($formElementValue) {
		$this->itemFormElValue = $formElementValue;

		return $this;
	}

	public function setIsInPalette($inPalette) {
		$this->isInPalette = $inPalette;

		return $this;
	}


	// NOTE: these methods were extracted from the monster condition in render()

	protected function isPassThroughField() {
		return $this->fieldConfig['config']['form_type'] == 'passthrough';
	}

	protected function hasFieldConfig() {
		return is_array($this->fieldConfig);
	}

	protected function isRteEnabled() {
		return $this->RTEenabled;
	}

	protected function isOnlyShownIfRteIsEnabled() {
		return $this->fieldConfig['config']['showIfRTE'];
	}

	protected function hasDisplayCondition() {
		return $this->fieldConfig['displayCond'];
	}

	protected function isExcludeField() {
		return $this->fieldConfig['exclude'];
	}

	/*
	 * TODO:refactor:
	 *
	 *  * conditions to element object creator (AbstractForm)
	 *  * most of the code to init method
	 *  * new abstract methods: renderField(), renderFieldReadonly()
	 *  *
	 */
	public function render() {
		global $BE_USER, $TCA;

		// Now, check if this field is configured and editable (according to excludefields + other configuration)
		if (	$this->hasFieldConfig()
				&& !$skipThisField // TODO: check this before calling render(), i.e. in the record object -- AW
				&& (!$this->isExcludeField() || $BE_USER->check('non_exclude_fields',$this->table.':'.$this->field))
				&& !$this->isPassThroughField()
				&& ($this->isRteEnabled() || !$this->isOnlyShownIfRteIsEnabled())
				&& (!$this->hasDisplayCondition() || $this->isDisplayCondition($this->fieldConfig['displayCond'], $this->record))
				&& (!$TCA[$this->table]['ctrl']['languageField'] || $this->fieldConfig['l10n_display'] || strcmp($this->fieldConfig['l10n_mode'],'exclude') || $this->record[$TCA[$this->table]['ctrl']['languageField']]<=0)
				&& (!$TCA[$this->table]['ctrl']['languageField'] || !$this->localizationMode || $this->localizationMode===$this->fieldConfig['l10n_cat'])
			) {

				// Fetching the TSconfig for the current table/field. This includes the $this->record which means that
			$fieldTSConfig = $this->getTSconfig();

				// If the field is NOT disabled from TSconfig (which it could have been) then render it
			if (!$fieldTSConfig['disabled']) {
					// Override fieldConf by fieldTSconfig:
				$this->overrideFieldConf($fieldTSConfig);

					// set field to read-only if configured for translated records to show default language content as readonly
				if ($this->fieldConfig['l10n_display'] && t3lib_div::inList($this->fieldConfig['l10n_display'], 'defaultAsReadonly') && $this->record[$TCA[$this->table]['ctrl']['languageField']]) {
					$this->fieldConfig['config']['readOnly'] =  true;
					$this->itemFormElValue = $this->defaultLanguageData[$this->table.':'.$this->record['uid']][$this->field];
				}

					// Create a JavaScript code line which will ask the user to save/update the form due to changing the element. This is used for eg. "type" fields and others configured with "requestUpdate"
				if (
				    ($TCA[$this->table]['ctrl']['type'] && !strcmp($this->field,$TCA[$this->table]['ctrl']['type'])) ||
				    ($TCA[$this->table]['ctrl']['requestUpdate'] && t3lib_div::inList($TCA[$this->table]['ctrl']['requestUpdate'],$this->field))
				    ) {

					if($GLOBALS['BE_USER']->jsConfirmation(1))	{
						$alertMsgOnChange = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
					} else {
						$alertMsgOnChange = 'if (TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
					}
				} else {
					$alertMsgOnChange = '';
				}

					// Render as a hidden field?
				// TODO: use "hidden" flag instead (don't even create this object, so check hidden in the form)
				if (in_array($this->field,$this->hiddenFieldListArr))	{
					// TODO: use context object here
					$this->TCEformsObject->addHiddenFieldHTMLCode($this->itemFormElName, '<input type="hidden" name="'.$this->itemFormElName.'" value="'.htmlspecialchars($this->itemFormElValue).'" />');
				} else { // Render as a normal field:

						// If the field is NOT a palette field, then we might create an icon which links to a palette for the field, if one exists.
					// TODO: move palette rendering to own method
					if ($this->hasPalette) {
						$paletteFields = $this->paletteObject->render();

						//$paletteFields = $this->TCEformsObject->loadPaletteElements($this->table, $this->record, $this->pal);
						if ($this->paletteObject->isCollapsed() && count($paletteFields))	{
							list($thePalIcon,$palJSfunc) = $this->paletteObject->wrapOpenPalette('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/options.gif','width="18" height="16"').' border="0" title="'.htmlspecialchars($this->getLL('l_moreOptions')).'" alt="" />', TRUE);
						} else {
							$thePalIcon = '';
							$palJSfunc = '';
						}
					}
						// onFocus attribute to add to the field:
					$onFocus = ($palJSfunc && !$BE_USER->uc['dontShowPalettesOnFocusInAB']) ? ' onfocus="'.htmlspecialchars($palJSfunc).'"' : '';

						// Find item
					$item='';
					// TODO: move to init()
					$this->label = ($this->alternativeName ? $this->alternativeName : $this->fieldConfig['label']);
					$this->label = ($fieldTSconfig['label'] ? $fieldTSconfig['label'] : $this->label);
					$this->label = ($fieldTSconfig['label.'][$GLOBALS['LANG']->lang] ? $fieldTSconfig['label.'][$GLOBALS['LANG']->lang] : $this->label);
					$this->label = $this->sL($this->label);
						// JavaScript code for event handlers:
					$this->fieldChangeFunc=array();
					$this->fieldChangeFunc['TBE_EDITOR_fieldChanged'] = "TBE_EDITOR.fieldChanged('".$this->table."','".$this->record['uid']."','".$this->field."','".$this->itemFormElName."');";
					$this->fieldChangeFunc['alert']=$alertMsgOnChange;
						// if this is the child of an inline type and it is the field creating the label
					// disabled while IRRE is not supported -- andreaswolf, 23.07.2008
					// TODO: move this to IRRE
					/*if ($this->inline->isInlineChildAndLabelField($this->table, $this->field)) {
						$this->fieldChangeFunc['inline'] = "inline.handleChangedField('".$this->itemFormElName."','".$this->inline->inlineNames['object']."[$this->table][".$this->record['uid']."]');";
					}*/

					$item = $this->renderField();

						// Add language + diff
					if ($this->fieldConfig['l10n_display'] && (t3lib_div::inList($this->fieldConfig['l10n_display'], 'hideDiff') || t3lib_div::inList($this->fieldConfig['l10n_display'], 'defaultAsReadonly'))) {
						$renderLanguageDiff = false;
					} else {
						$renderLanguageDiff = true;
					}

					if ($renderLanguageDiff) {
						$item = $this->renderDefaultLanguageContent($item);
						$item = $this->renderDefaultLanguageDiff($item);
					}

					// TODO: check what this means and if it works
						// If the record has been saved and the "linkTitleToSelf" is set, we make the field name into a link, which will load ONLY this field in alt_doc.php
					$this->label = t3lib_div::deHSCentities(htmlspecialchars($this->label));
					if (t3lib_div::testInt($this->record['uid']) && $fieldTSconfig['linkTitleToSelf'] && !t3lib_div::_GP('columnsOnly'))	{
						$lTTS_url = $this->TCEformsObject->backPath.'alt_doc.php?edit['.$this->table.']['.$this->record['uid'].']=edit&columnsOnly='.$this->field.'&returnUrl='.rawurlencode($this->thisReturnUrl());
						$this->label = '<a href="'.htmlspecialchars($lTTS_url).'">'.$this->label.'</a>';
					}

						// Create output value:
					// TODO: refactor this
					// - new methods in Element_Abstract:
					//      getLabel
					//      renderHelpIcon
					//      renderHelpText
					//      renderPaletteIcon
					//      renderPaletteContents
					// putting it all together will happen in container

					if ($this->fieldConfig['config']['form_type']=='user' && $this->fieldConfig['config']['noTableWrapping'])	{
						$out = $item;
					} elseif ($this->isInPalette) {
						$out=array(
							'NAME'=>$this->label,
							'ID'=>$this->record['uid'],
							'FIELD'=>$this->field,
							'TABLE'=>$this->table,
							'ITEM'=>$item,
							'HELP_ICON' => $this->helpTextIcon(TRUE)
						);
					} else {
						$out=array(
							'NAME'=>$this->label,
							'ITEM'=>$item,
							'TABLE'=>$this->table,
							'ID'=>$this->record['uid'],
							'HELP_ICON'=>$this->helpTextIcon(),
							'HELP_TEXT'=>$this->helpText(),
							'PAL_LINK_ICON'=>$thePalIcon,
							'FIELD'=>$this->field
						);
						$out = $this->intoTemplate($out);
					}
				}
			} else $this->commentMessages[]=$this->prependFormFieldNames.'['.$this->table.']['.$this->record['uid'].']['.$this->field.']: Disabled by TSconfig';
		}

			// Hook: getSingleField_postProcess
		foreach (self::$hookObjects['getSingleFields'] as $hookObj)	{
			if (method_exists($hookObj,'getSingleField_postProcess'))	{
				$hookObj->getSingleField_postProcess($this->table, $this->field, $this->record, $this->alternativeName, $this->palette, $this->extra, $this->pal, $this);
			}
		}

		if ($this->hasPalette) {
			$out .= $paletteFields;
		}

		return $out;
	}

	public function getHelpText() {
		return $this->helpText();
	}

	public function getHelpTextIcon() {
		return $this->helpTextIcon();
	}

	public function getLabel() {
		return $this->label;
	}

	//abstract public function render();

	public function setTCEformsObject(t3lib_TCEforms $TCEformsObject) {
		$this->TCEformsObject = $TCEformsObject;

		return $this;
	}

	// TODO: remove this once the transition to the new TCEforms object structure is done
	public function set_TCEformsObject(t3lib_TCEforms_AbstractForm $_TCEformsObject) {
		$this->_TCEformsObject = $_TCEformsObject;

		return $this;
	}

	public function setContainingTab(t3lib_TCEforms_Tab $tabObject) {
		$this->setContainer($tabObject);

		return $this;
	}

	// TODO: rename to setParentContainer
	public function setContainer(t3lib_TCEforms_Container $container) {
		$this->container = $container;

		return $this;
	}

	/**
	 * Returns TSconfig for table/row
	 *
	 * Multiple requests to this function will return cached content so there is no performance
	 * loss in calling this many times since the information is looked up only once.
	 *
	 * Uses central caching functionality in AbstractForm
	 *
	 * @see t3lib_TCEForms_AbstractForm::getTSconfig()
	 */
	protected function getTSconfig($useField = TRUE) {
		return t3lib_TCEForms_AbstractForm::getTSconfig($this->table, $this->record, ($useField ? $this->field : ''));
	}

	public function getFieldname() {
		return $this->field;
	}

	/**
	 * Returns the HTML code for disabling a form field if the current field should be displayed
	 * disabled
	 *
	 */
	protected function getDisabledCode() {
		// TODO: replace by real get method
		if($this->contextObject->isReadOnly() || $this->fieldConfig['config']['readOnly'])  {
			$disabled = ' disabled="disabled"';
		} else {
			$disabled = '';
		}

		return $disabled;
	}

	/**
	 * Overrides the TCA field configuration by TSconfig settings.
	 *
	 * Example TSconfig: TCEform.<table>.<field>.config.appearance.useSortable = 1
	 * This overrides the setting in $TCA[<table>]['columns'][<field>]['config']['appearance']['useSortable'].
	 *
	 * @param	array		$TSconfig: TSconfig
	 * @return	array		Changed TCA field configuration
	 */
	protected function overrideFieldConf($TSconfig) {
		$fieldConfig = $this->fieldConfig['config'];

		if (is_array($TSconfig)) {
			$TSconfig = t3lib_div::removeDotsFromTS($TSconfig);
			$type = $fieldConfig['type'];
			if (is_array($TSconfig['config']) && is_array($this->allowOverrideMatrix[$type])) {
					// Check if the keys in TSconfig['config'] are allowed to override TCA field config:
				foreach (array_keys($TSconfig['config']) as $key) {
					if (!in_array($key, $this->allowOverrideMatrix[$type], true)) {
						unset($TSconfig['config'][$key]);
					}
				}
					// Override TCA field config by remaining TSconfig['config']:
				if (count($TSconfig['config'])) {
					$fieldConfig = t3lib_div::array_merge_recursive_overrule($fieldConfig, $TSconfig['config']);
				}
			}
		}

		$this->fieldConfig['config'] = $fieldConfig;
	}

	/**
	 * Returns true, if the evaluation of the required-field code is OK.
	 *
	 * @param	string		The required-field code
	 * @param	array		The record to evaluate
	 * @param	string		FlexForm value key, eg. vDEF
	 * @return	boolean
	 */
	protected function isDisplayCondition($displayCond,$row,$ffValueKey='')	{
		$output = FALSE;

		$parts = explode(':',$displayCond);
		switch((string)$parts[0]) { // Type of condition:
			case 'FIELD':
				$theFieldValue = $ffValueKey ? $row[$parts[1]][$ffValueKey] : $row[$parts[1]];

				switch((string)$parts[2]) {
					case 'REQ':
						if (strtolower($parts[3])=='true')	{
							$output = $theFieldValue ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
							$output = !$theFieldValue ? TRUE : FALSE;
						}
					break;
					case '>':
						$output = $theFieldValue > $parts[3];
					break;
					case '<':
						$output = $theFieldValue < $parts[3];
					break;
					case '>=':
						$output = $theFieldValue >= $parts[3];
					break;
					case '<=':
						$output = $theFieldValue <= $parts[3];
					break;
					case '-':
					case '!-':
						$cmpParts = explode('-',$parts[3]);
						$output = $theFieldValue >= $cmpParts[0] && $theFieldValue <= $cmpParts[1];
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
					case 'IN':
					case '!IN':
						$output = t3lib_div::inList($parts[3],$theFieldValue);
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
					case '=':
					case '!=':
						$output = t3lib_div::inList($parts[3],$theFieldValue);
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
				}
			break;
			case 'EXT':
				switch((string)$parts[2]) {
					case 'LOADED':
						if (strtolower($parts[3])=='true') {
							$output = t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
							$output = !t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'REC':
				switch((string)$parts[1])	{
					case 'NEW':
						if (strtolower($parts[2])=='true') {
							$output = !(intval($row['uid']) > 0) ? TRUE : FALSE;
						} elseif (strtolower($parts[2])=='false') {
							$output = (intval($row['uid']) > 0) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'HIDE_L10N_SIBLINGS':
				if ($ffValueKey==='vDEF') {
					$output = TRUE;
				} elseif ($parts[1]==='except_admin' && $GLOBALS['BE_USER']->isAdmin()) {
					$output = TRUE;
				}
			break;
			case 'HIDE_FOR_NON_ADMINS':
				$output = $GLOBALS['BE_USER']->isAdmin() ? TRUE : FALSE;
			break;
			case 'VERSION':
				switch((string)$parts[1]) {
					case 'IS':
						if (strtolower($parts[2])=='true') {
							$output = intval($row['pid'])==-1 ? TRUE : FALSE;
						} elseif (strtolower($parts[2])=='false') {
							$output = !(intval($row['pid'])==-1) ? TRUE : FALSE;
						}
					break;
				}
			break;
		}

		return $output;
	}

	/************************************
	 *
	 * Template functions
	 *
	 ************************************/
	// TODO: add accessors for style scheme so the container can access them for rendering

	/**
	 * This inserts the content of $inArr into the field-template
	 *
	 * @param	array		Array with key/value pairs to insert in the template.
	 * @param	string		Alternative template to use instead of the default.
	 * @return	string
	 */
	// TODO: will be removed after refactoring render()
	function intoTemplate($inArr,$altTemplate='')	{
		$parentTemplate = t3lib_parsehtml::getSubpart($this->contextObject->getTemplateContent(), '###FIELD_WRAP###');
		$template = $this->rplColorScheme($altTemplate?$altTemplate:$parentTemplate);

		foreach ($inArr as $key => $value) {
			$markerArray['###FIELD_'.$key.'###'] = $value;
		}

		$content = t3lib_parsehtml::substituteMarkerArray($template, $markerArray);

		return $content;
	}

	/**
	 * Replaces colorscheme markers in the template string
	 *
	 * @param	string		Template string with markers to be substituted.
	 * @return	string
	 */
	function rplColorScheme($inTemplate)	{
			// Colors:
		$markerArray = array(
			'###BGCOLOR###' => $this->colorScheme[0] ? ' bgcolor="' . $this->colorScheme[0].'"' : '',
			'###BGCOLOR_HEAD###' => $this->colorScheme[1] ? ' bgcolor="' . $this->colorScheme[1].'"':'',
			'###FONTCOLOR_HEAD###' => $this->colorScheme[3],
			'###CLASSATTR_1###' => $this->classScheme[0] ? ' class="' . $this->classScheme[0] . '"' : '',
			'###CLASSATTR_2###' => $this->classScheme[1] ? ' class="' . $this->classScheme[1] . '"' : '',
			'###CLASSATTR_4###' => $this->classScheme[3] ? ' class="' . $this->classScheme[3] . '"' : ''
		);

		$inTemplate = t3lib_parsehtml::substituteMarkerArray($inTemplate, $markerArray);

		return $inTemplate;
	}

	/**
	 * Setting the current color scheme ($this->colorScheme) based on $this->defColorScheme plus input string.
	 *
	 * @param   string  A color scheme string.
	 * @return  void
	 */
	public function setColorScheme($scheme)	{
		$this->colorScheme = $this->defColorScheme;
		$this->classScheme = $this->defClassScheme;

		$parts = t3lib_div::trimExplode(',',$scheme);
		foreach($parts as $key => $col)	{
				// Split for color|class:
			list($color,$class) = t3lib_div::trimExplode('|',$col);

				// Handle color values:
			if ($color)	$this->colorScheme[$key] = $color;
			if ($color=='-') {
				$this->colorScheme[$key] = '';
			}

				// Handle class values:
			if ($class)	$this->classScheme[$key] = $class;
			if ($class=='-') {
				$this->classScheme[$key] = '';
			}
		}

		return $this;
	}

	public function setBorderStyle($style) {
		$this->borderStyle = $style;

		return $this;
	}

	public function getBorderStyle() {
		return $this->borderStyle;
	}

	public function setFieldStyle($style) {
		$this->fieldStyle = $style;

		return $this;
	}

	public function getFieldStyle() {
		return $this->fieldStyle;
	}

	/**
	 * Reset element styles to default values.
	 *
	 * @return void
	 */
	protected function resetStyles() {
		$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][0]);
		$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
		$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
	}


	/************************************************************
	 *
	 * Display of localized content etc.
	 *
	 ************************************************************/

	public function setDefaultLanguageValue($defaultValue) {
		$this->defaultLanguageValue = $defaultValue;
	}

	/**
	 * Creates language-overlay for a field value
	 * This means the requested field value will be overridden with the data from the default language.
	 * Can be used to render read only fields for example.
	 *
	 * @param	string		Table name of the record being edited
	 * @param	string		Field name represented by $item
	 * @param	array		Record array of the record being edited in current language
	 * @param	array		Content of $PA['fieldConf']
	 * @return	string		Unprocessed field value merged with default language data if needed
	 */
	protected function getLanguageOverlayRawValue() {
		global $TCA;

		if ($this->fieldConf['l10n_mode']=='exclude'
		  || ($this->fieldConf['l10n_mode']=='mergeIfNotBlank'
		  && strcmp(trim($this->defaultLanguageValue),''))) {

			$value = $this->defaultLanguageValue;
		}


		return $value;
	}

	/**
	 * Renders the display of default language record content around current field.
	 * Will render content if any is found in the internal array, $this->defaultLanguageData, depending on registerDefaultLanguageData() being called prior to this.
	 *
	 * @param	string		HTML of the form field. This is what we add the content to.
	 * @return	string		Item string returned again, possibly with the original value added to.
	 * @see getSingleField(), registerDefaultLanguageData()
	 */
	// TODO: check where $this->previewFieldValue() gets its data from
	protected function renderDefaultLanguageContent($item) {
		if ($this->defaultLanguageValue != '') {
			$dLVal = t3lib_BEfunc::getProcessedValue($this->table, $this->field, $this->defaultLanguageValue, 0, 1);

				// Don't show content if it's for IRRE child records:
			if ($this->fieldConfig['type'] != 'inline') {
				if (strcmp($dLVal, '')) {
					$item.='<div class="typo3-TCEforms-originalLanguageValue">'.$this->parentRecordObject->getLanguageIcon(0).$this->previewFieldValue($dLVal).'&nbsp;</div>';
				}

				$prLang = $this->parentRecordObject->getAdditionalPreviewLanguages();
				foreach ($prLang as $prL) {
					$dlVal = t3lib_BEfunc::getProcessedValue($this->table,$this->field,$this->additionalPreviewLanguageValue[$prL['uid']][$this->field],0,1);

					if (strcmp($dlVal, '')) {
						$item .= '<div class="typo3-TCEforms-originalLanguageValue">'.$this->parentRecordObject->getLanguageIcon('v'.$prL['ISOcode']).$this->previewFieldValue($dlVal).'&nbsp;</div>';
					}
				}
			}
		}

		return $item;
	}

	/**
	 * Renders the diff-view of default language record content compared with what the record was originally translated from.
	 * Will render content if any is found in the internal array, $this->defaultLanguageData, depending on registerDefaultLanguageData() being called prior to this.
	 *
	 * @param	string		Table name of the record being edited
	 * @param	string		Field name represented by $item
	 * @param	array		Record array of the record being edited
	 * @param	string		HTML of the form field. This is what we add the content to.
	 * @return	string		Item string returned again, possibly with the original value added to.
	 * @see getSingleField(), registerDefaultLanguageData()
	 */
	protected function renderDefaultLanguageDiff($item) {
		if (is_array($this->defaultLanguageData_diff[$this->table.':'.$this->record['uid']])) {

				// Initialize:
			$dLVal = array(
				'old' => $this->defaultLanguageData_diff[$this->table.':'.$this->record['uid']],
				'new' => $this->defaultLanguageData[$this->table.':'.$this->record['uid']],
			);

			if (isset($dLVal['old'][$this->field])) { // There must be diff-data:
			 	if (strcmp($dLVal['old'][$this->field],$dLVal['new'][$this->field])) {

						// Create diff-result:
					$t3lib_diff_Obj = t3lib_div::makeInstance('t3lib_diff');
					$diffres = $t3lib_diff_Obj->makeDiffDisplay(
						t3lib_BEfunc::getProcessedValue($this->table,$this->field,$dLVal['old'][$this->field],0,1),
						t3lib_BEfunc::getProcessedValue($this->table,$this->field,$dLVal['new'][$this->field],0,1)
					);

					$item .= '<div class="typo3-TCEforms-diffBox">'.
						'<div class="typo3-TCEforms-diffBox-header">'.htmlspecialchars($this->getLL('l_changeInOrig')).':</div>'.
						$diffres.
					'</div>';
				}
			}
		}

		return $item;
	}

	/**
	 * Rendering preview output of a field value which is not shown as a form field but just outputted.
	 *
	 * @param	string		The value to output
	 * @param	array		Configuration for field.
	 * @return 	string		HTML formatted output
	 */
	// TODO: move to renderField in type group
	protected function previewFieldValue($value) {
		if ($this->fieldConfig['type'] === 'group' && $this->fieldConfig['internal_type'] === 'file') {
			$show_thumbs = TRUE;
			$table = 'tt_content';

				// Making the array of file items:
			$itemArray = t3lib_div::trimExplode(',', $value, 1);

				// Showing thumbnails:
			$thumbsnail = '';
			if ($show_thumbs) {
				$imgs = array();
				foreach($itemArray as $imgRead) {
					$imgP = explode('|',$imgRead);
					$imgPath = rawurldecode($imgP[0]);

					$rowCopy = array();
					$rowCopy[$field] = $imgPath;

						// Icon + clickmenu:
					$absFilePath = t3lib_div::getFileAbsFileName($this->fieldConfig['uploadfolder'].'/'.$imgPath);

					$fI = pathinfo($imgPath);
					$fileIcon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));
					$fileIcon = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/fileicons/'.$fileIcon,'width="18" height="16"').' class="absmiddle" title="'.htmlspecialchars($fI['basename'].($absFilePath && @is_file($absFilePath) ? ' ('.t3lib_div::formatSize(filesize($absFilePath)).'bytes)' : ' - FILE NOT FOUND!')).'" alt="" />';

					$imgs[] = '<span class="nobr">'.t3lib_BEfunc::thumbCode($rowCopy,$table,$field,$this->backPath,'thumbs.php',$config['config']['uploadfolder'],0,' align="middle"').
								($absFilePath ? $this->getClickMenu($fileIcon, $absFilePath) : $fileIcon).
								$imgPath.
								'</span>';
				}
				$thumbsnail = implode('<br />',$imgs);
			}

			return $thumbsnail;
		} else {
			return nl2br(htmlspecialchars($value));
		}
	}

	/**
	 * Returns help-text ICON if configured for.
	 *
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	boolean		Force the return of the help-text icon.
	 * @return	string		HTML, <a>-tag with
	 */
	protected function helpTextIcon($force = 0) {
		if ($this->contextObject->isHelpGloballyShown()
		  && $GLOBALS['TCA_DESCR'][$this->table]['columns'][$this->field]
		  && (
		    ($this->contextObject->getEditFieldHelpMode() =='icon'
		      && !$this->contextObject->doLoadTableDescription($this->table)
		    )
		    || $force)
		  ) {
			$aOnClick = 'vHWin=window.open(\''.$this->backPath.'view_help.php?tfID='.($this->table.'.'.$this->field).'\',\'viewFieldHelp\',\'height=400,width=600,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';

			if ($this->contextObject->getEditFieldHelpMode() == 'icon') {
				$text = t3lib_befunc::helpText($this->table, $this->field, $this->backPath, '');
				$text = '<span class="typo3-csh-inline">'.$GLOBALS['LANG']->hscAndCharConv($text, false).'</span>';
			}

			return '<a class="typo3-csh-link" href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/helpbubble.gif','width="14" height="14"').' hspace="2" border="0" class="absmiddle"'.($GLOBALS['CLIENT']['FORMSTYLE']?' style="cursor:help;"':'').' alt="" />'.$text.
					'</a>';
		} else {
				// Detects fields with no CSH and outputs dummy line to insert into CSH locallang file:
			return '<span class="nbsp">&nbsp;</span>';
		}
	}

	/**
	 * Returns help text DESCRIPTION, if configured for.
	 *
	 * @param	string		The table name
	 * @param	string		The field name
	 * @return	string
	 */
	protected function helpText() {
		if ($this->contextObject->isHelpGloballyShown()
		  && $GLOBALS['TCA_DESCR'][$this->table]['columns'][$this->field]
		  && ($this->contextObject->getEditFieldHelpMode() == 'text'
		    || $this->contextObject->doLoadTableDescription($this->table))
		  ) {
			$fDat = $GLOBALS['TCA_DESCR'][$this->table]['columns'][$this->field];

			return '<table border="0" cellpadding="2" cellspacing="0" width="90%"><tr><td valign="top" width="14">'.
					$this->helpTextIcon(
						$this->table,
						$this->field,
						$fDat['details']||$fDat['syntax']||$fDat['image_descr']||$fDat['image']||$fDat['seeAlso']
					).
					'</td><td valign="top"><span class="typo3-TCEforms-helpText">'.
					$GLOBALS['LANG']->hscAndCharConv(strip_tags($fDat['description']),1).
					'</span></td></tr></table>';
		}
	}

	/**
	 * Returns the "special" configuration of an "extra" string (non-parsed)
	 *
	 * @param   string  The "Part 4" of the fields configuration in "types" "showitem" lists.
	 * @param   string  The ['defaultExtras'] value from field configuration
	 * @return  array   An array with the special options in.
	 * @see getSpecConfForField(), t3lib_BEfunc::getSpecConfParts()
	 */
	protected function getSpecConfFromString($extraString, $defaultExtras) {
		return t3lib_BEfunc::getSpecConfParts($extraString, $defaultExtras);
	}

	/**
	 * Creates style attribute content for option tags in a selector box, primarily setting it up to
	 * show the icon of an element as background image (works in mozilla)
	 *
	 * @param	string		Icon string for option item
	 * @return	string		Style attribute content, if any
	 */
	protected function optionTagStyle($iconString)	{
		if ($iconString) {
			list($selIconFile, $selIconInfo) = $this->getIcon($iconString);

			$padLeft = $selIconInfo[0] + 4;

			if($padLeft >= 18 && $padLeft <= 24) {
				$padLeft = 22; // In order to get the same padding for all option tags even if icon sizes differ a little, set it to 22 if it was between 18 and 24 pixels
			}

			$padTop = t3lib_div::intInRange(($selIconInfo[1]-12)/2, 0);
			$styleAttr = 'background-image: url(' . $selIconFile . '); background-repeat: no-repeat; height: ' .
			  t3lib_div::intInRange(($selIconInfo[1] + 2) - $padTop, 0) . 'px; padding-top: ' . $padTop .
			  'px; padding-left: ' . $padLeft . 'px;';
			return $styleAttr;
		}
	}

	/**
	 * Get icon (for example for selector boxes)
	 *
	 * @param	string		Icon reference
	 * @return	array		Array with two values; the icon file reference (relative to PATH_typo3 minus backPath), the icon file information array (getimagesize())
	 */
	protected function getIcon($icon)	{
		if (substr($icon, 0, 4) == 'EXT:') {
			$file = t3lib_div::getFileAbsFileName($icon);
			if ($file) {
				$file = substr($file,strlen(PATH_site));
				$selIconFile = $this->backPath.'../'.$file;
				$selIconInfo = @getimagesize(PATH_site.$file);
			}
		} elseif (substr($icon,0,3)=='../')	{
			$selIconFile = $this->backPath.t3lib_div::resolveBackPath($icon);
			$selIconInfo = @getimagesize(PATH_site.t3lib_div::resolveBackPath(substr($icon,3)));
		} elseif (substr($icon,0,4)=='ext/' || substr($icon,0,7)=='sysext/') {
			$selIconFile = $this->backPath.$icon;
			$selIconInfo = @getimagesize(PATH_typo3.$icon);
		} else {
			$selIconFile = t3lib_iconWorks::skinImg($this->backPath,'gfx/'.$icon,'',1);
			$selIconInfo = @getimagesize(PATH_typo3.$selIconFile);
		}
		return array($selIconFile,$selIconInfo);
	}


	/************************************************************
	 *
	 * Item-array manipulation functions (check/select/radio)
	 *
	 ************************************************************/

	// TODO: check if moving these functions to a common baseclass of elements check, select and radio
	// makes sense
	/**
	 * Initialize item array (for checkbox, selectorbox, radio buttons)
	 * Will resolve the label value.
	 *
	 * @param	array		The "columns" array for the field (from TCA)
	 * @return	array		An array of arrays with three elements; label, value, icon
	 */
	protected function initItemArray($fieldValue)	{
		$items = array();
		if (is_array($fieldValue['config']['items']))	{
			reset ($fieldValue['config']['items']);
			while (list($itemName,$itemValue) = each($fieldValue['config']['items']))	{
				$items[] = array($this->sL($itemValue[0]), $itemValue[1], $itemValue[2]);
			}
		}
		return $items;
	}

	/**
	 * Merges items into an item-array
	 *
	 * @param	array		The existing item array
	 * @param	array		An array of items to add. NOTICE: The keys are mapped to values, and the values and mapped to be labels. No possibility of adding an icon.
	 * @return	array		The updated $item array
	 */
	protected function addItems($items,$iArray)	{
		global $TCA;
		if (is_array($iArray))	{
			reset($iArray);
			while(list($value,$label)=each($iArray))	{
				$items[]=array($this->sl($label),$value);
			}
		}
		return $items;
	}

	/**
	 * Perform user processing of the items arrays of checkboxes, selectorboxes and radio buttons.
	 *
	 * @param	array		The array of items (label,value,icon)
	 * @param	array		The "itemsProcFunc." from fieldTSconfig of the field.
	 * @param	array		The config array for the field.
	 * @param	string		Table name
	 * @param	array		Record row
	 * @param	string		Field name
	 * @return	array		The modified $items array
	 */
	protected function procItems($items,$iArray,$config,$table,$row,$field)	{
		global $TCA;

		$params=array();
		$params['items'] = &$items;
		$params['config'] = $config;
		$params['TSconfig'] = $iArray;
		$params['table'] = $table;
		$params['row'] = $row;
		$params['field'] = $field;

		t3lib_div::callUserFunction($config['itemsProcFunc'],$params,$this);
		return $items;
	}


	/********************************************
	 *
	 * Localization functions
	 *
	 ********************************************/

	/**
	 * Fetches language label for key
	 *
	 * @param   string  Language label reference, eg. 'LLL:EXT:lang/locallang_core.php:labels.blablabla'
	 * @return  string  The value of the label, fetched for the current backend language.
	 */
	// TODO: refactor the method name
	protected function sL($str) {
		return $GLOBALS['LANG']->sL($str);
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
	/************************************************************
	 *
	 * Form element helper functions
	 *
	 ************************************************************/

	/**
	 * Prints the selector box form-field for the db/file/select elements (multiple)
	 *
	 * @param	string		Form element name
	 * @param	string		Mode "db", "file" (internal_type for the "group" type) OR blank (then for the "select" type)
	 * @param	string		Commalist of "allowed"
	 * @param	array		The array of items. For "select" and "group"/"file" this is just a set of value. For "db" its an array of arrays with table/uid pairs.
	 * @param	string		Alternative selector box.
	 * @param	array		An array of additional parameters, eg: "size", "info", "headers" (array with "selector" and "items"), "noBrowser", "thumbnails"
	 * @param	string		On focus attribute string
	 * @param	string		$table: (optional) Table name processing for
	 * @param	string		$field: (optional) Field of table name processing for
	 * @param	string		$uid:	(optional) uid of table record processing for
	 * @return	string		The form fields for the selection.
	 */
	function dbFileIcons($fName,$mode,$allowed,$itemArray,$selector='',$params=array(),$onFocus='',$table='',$field='',$uid='')	{


		$disabled = '';
		if($this->contextObject->isReadOnly() || $params['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Sets a flag which means some JavaScript is included on the page to support this element.
		$this->printNeededJS['dbFileIcons']=1;

			// INIT
		$uidList=array();
		$opt=array();
		$itemArrayC=0;

			// Creating <option> elements:
		if (is_array($itemArray))	{
			$itemArrayC=count($itemArray);
			reset($itemArray);
			switch($mode)	{
				case 'db':
					while(list(,$pp)=each($itemArray))	{
						$pRec = t3lib_BEfunc::getRecordWSOL($pp['table'],$pp['id']);
						if (is_array($pRec))	{
							$pTitle = t3lib_BEfunc::getRecordTitle($pp['table'], $pRec, FALSE, TRUE);
							$pUid = $pp['table'].'_'.$pp['id'];
							$uidList[]=$pUid;
							$opt[]='<option value="'.htmlspecialchars($pUid).'">'.htmlspecialchars($pTitle).'</option>';
						}
					}
				break;
				case 'file':
				case 'folder':
					while(list(,$pp)=each($itemArray))	{
						$pParts = explode('|',$pp);
						$uidList[]=$pUid=$pTitle = $pParts[0];
						$opt[]='<option value="'.htmlspecialchars(rawurldecode($pParts[0])).'">'.htmlspecialchars(rawurldecode($pParts[0])).'</option>';
					}
				break;
				default:
					while(list(,$pp)=each($itemArray))	{
						$pParts = explode('|',$pp, 2);
						$uidList[]=$pUid=$pParts[0];
						$pTitle = $pParts[1];
						$opt[]='<option value="'.htmlspecialchars(rawurldecode($pUid)).'">'.htmlspecialchars(rawurldecode($pTitle)).'</option>';
					}
				break;
			}
		}

			// Create selector box of the options
		$sSize = $params['autoSizeMax'] ? t3lib_div::intInRange($itemArrayC+1,t3lib_div::intInRange($params['size'],1),$params['autoSizeMax']) : $params['size'];
		if (!$selector)	{
			$selector = '<select ' . ($params['noList'] ? 'style="display: none"' : 'size="'.$sSize.'"'.$this->insertDefaultElementStyle('group')).' multiple="multiple" name="'.$fName.'_list" '.$onFocus.$params['style'].$disabled.'>'.implode('',$opt).'</select>';
		}


		$icons = array(
			'L' => array(),
			'R' => array(),
		);
		if (!$params['readOnly'] && !$params['noList']) {
			if (!$params['noBrowser'])	{
					// check against inline uniqueness
				// TODO: re-implement for IRRE
				/*$inlineParent = $this->inline->getStructureLevel(-1);
				if(is_array($inlineParent) && $inlineParent['uid']) {
					if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
						$objectPrefix = $this->inline->inlineNames['object'].'['.$table.']';
						$aOnClickInline = $objectPrefix.'|inline.checkUniqueElement|inline.setUniqueElement';
						$rOnClickInline = 'inline.revertUnique(\''.$objectPrefix.'\',null,\''.$uid.'\');';
					}
				}*/
				$aOnClick='setFormValueOpenBrowser(\''.$mode.'\',\''.($fName.'|||'.$allowed.'|'.$aOnClickInline).'\'); return false;';
				$icons['R'][]='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/insert3.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_browse_'.($mode=='file'?'file':'db'))).' />'.
						'</a>';
			}
			if (!$params['dontShowMoveIcons'])	{
				if ($sSize>=5)	{
					$icons['L'][]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Top\'); return false;">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/group_totop.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_move_to_top')).' />'.
							'</a>';
				}
				$icons['L'][]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Up\'); return false;">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/up.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_move_up')).' />'.
						'</a>';
				$icons['L'][]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Down\'); return false;">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/down.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_move_down')).' />'.
						'</a>';
				if ($sSize>=5)	{
					$icons['L'][]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Bottom\'); return false;">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/group_tobottom.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_move_to_bottom')).' />'.
							'</a>';
				}
			}

			$clipElements = $this->getClipboardElements($allowed,$mode);
			if (count($clipElements))	{
				$aOnClick = '';
	#			$counter = 0;
				foreach($clipElements as $elValue)	{
					if ($mode=='file')	{
						$itemTitle = 'unescape(\''.rawurlencode(basename($elValue)).'\')';
					} else {	// 'db' mode assumed
						list($itemTable,$itemUid) = explode('|', $elValue);
						$itemTitle = $GLOBALS['LANG']->JScharCode(t3lib_BEfunc::getRecordTitle($itemTable, t3lib_BEfunc::getRecordWSOL($itemTable,$itemUid)));
						$elValue = $itemTable.'_'.$itemUid;
					}
					$aOnClick.= 'setFormValueFromBrowseWin(\''.$fName.'\',unescape(\''.rawurlencode(str_replace('%20',' ',$elValue)).'\'),'.$itemTitle.');';

	#				$counter++;
	#				if ($params['maxitems'] && $counter >= $params['maxitems'])	{	break;	}	// Makes sure that no more than the max items are inserted... for convenience.
				}
				$aOnClick.= 'return false;';
				$icons['R'][]='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/insert5.png','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib(sprintf($this->getLL('l_clipInsert_'.($mode=='file'?'file':'db')),count($clipElements))).' />'.
						'</a>';
			}
			$rOnClick = $rOnClickInline.'setFormValueManipulate(\''.$fName.'\',\'Remove\'); return false';
			$icons['L'][]='<a href="#" onclick="'.htmlspecialchars($rOnClick).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/group_clear.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_remove_selected')).' />'.
					'</a>';
		}

		$str='<table border="0" cellpadding="0" cellspacing="0" width="1">
			'.($params['headers']?'
				<tr>
					<td>'.$this->wrapLabels($params['headers']['selector']).'</td>
					<td></td>
					<td></td>
					<td>'.($params['thumbnails'] ? $this->wrapLabels($params['headers']['items']) : '').'</td>
				</tr>':'').
			'
			<tr>
				<td valign="top">'.
					$selector.
					($params['noList'] ? '' : '<br />'.$this->wrapLabels($params['info'])) .
				'</td>
				<td valign="top" class="icons">'.
					implode('<br />',$icons['L']).'</td>
				<td valign="top" class="icons">'.
					implode('<br />',$icons['R']).'</td>
				<td valign="top" class="thumbnails">'.
					$this->wrapLabels($params['thumbnails']).
				'</td>
			</tr>
		</table>';

			// Creating the hidden field which contains the actual value as a comma list.
		$str.='<input type="hidden" name="'.$fName.'" value="'.htmlspecialchars(implode(',',$uidList)).'" />';

		return $str;
	}

	/**
	 * Returns array of elements from clipboard to insert into GROUP element box.
	 *
	 * @param	string		Allowed elements, Eg "pages,tt_content", "gif,jpg,jpeg,png"
	 * @param	string		Mode of relations: "db" or "file"
	 * @return	array		Array of elements in values (keys are insignificant), if none found, empty array.
	 */
	function getClipboardElements($allowed,$mode)	{

		$output = array();

		if (is_object($this->clipObj))	{
			switch($mode)	{
				case 'file':
					$elFromTable = $this->clipObj->elFromTable('_FILE');
					$allowedExts = t3lib_div::trimExplode(',', $allowed, 1);

					if ($allowedExts)	{	// If there are a set of allowed extensions, filter the content:
						foreach($elFromTable as $elValue)	{
							$pI = pathinfo($elValue);
							$ext = strtolower($pI['extension']);
							if (in_array($ext, $allowedExts))	{
								$output[] = $elValue;
							}
						}
					} else {	// If all is allowed, insert all: (This does NOT respect any disallowed extensions, but those will be filtered away by the backend TCEmain)
						$output = $elFromTable;
					}
				break;
				case 'db':
					$allowedTables = t3lib_div::trimExplode(',', $allowed, 1);
					if (!strcmp(trim($allowedTables[0]),'*'))	{	// All tables allowed for relation:
						$output = $this->clipObj->elFromTable('');
					} else {	// Only some tables, filter them:
						foreach($allowedTables as $tablename)	{
							$elFromTable = $this->clipObj->elFromTable($tablename);
							$output = array_merge($output,$elFromTable);
						}
					}
					$output = array_keys($output);
				break;
			}
		}

		return $output;
	}


	/**
	 * Rendering wizards for form fields.
	 *
	 * @param	array		Array with the real item in the first value, and an alternative item in the second value.
	 * @param	array		The "wizard" key from the config array for the field (from TCA)
	 * @param	string		Table name
	 * @param	array		The record array
	 * @param	string		The field name
	 * @param	array		Additional configuration array. (passed by reference!)
	 * @param	string		The field name
	 * @param	array		Special configuration if available.
	 * @param	boolean		Whether the RTE could have been loaded.
	 * @return	string		The new item value.
	 */
	// TODO: refactor this!
	function renderWizards($itemKinds, $wizConf, $itemName, $specConf, $RTE=0) {

			// Init:
		$this->fieldChangeFunc = $this->fieldChangeFunc;
		$item = $itemKinds[0];
		$outArr = array();
		$colorBoxLinks = array();
		$fName = '['.$this->table.']['.$this->record['uid'].']['.$this->field.']';
		$md5ID = 'ID'.t3lib_div::shortmd5($itemName);
		$listFlag = '_list';

			// Manipulate the field name (to be the true form field name) and remove a suffix-value if the item is a selector box with renderMode "singlebox":
		if ($this->fieldConfig['config']['form_type']=='select')	{
			if ($this->fieldConfig['config']['maxitems']<=1)	{	// Single select situation:
				$listFlag = '';
			} elseif ($this->fieldConfig['config']['renderMode']=='singlebox')	{
				$itemName.='[]';
				$listFlag = '';
			}
		}

			// traverse wizards:
		if (is_array($wizConf) && !$this->disableWizards) {
			foreach($wizConf as $wid => $wConf)	{
				if (substr($wid,0,1)!='_'
						&& (!$wConf['enableByTypeConfig'] || @in_array($wid,$specConf['wizards']['parameters']))
						&& ($RTE || !$wConf['RTEonly'])
					)	{

						// Title / icon:
					$iTitle = htmlspecialchars($this->sL($wConf['title']));
					if ($wConf['icon'])	{
						$iDat = $this->getIcon($wConf['icon']);
						$icon = '<img src="'.$iDat[0].'" '.$iDat[1][3].' border="0"'.t3lib_BEfunc::titleAltAttrib($iTitle).' />';
					} else {
						$icon = $iTitle;
					}

					switch((string)$wConf['type']) {
						case 'userFunc':
						case 'script':
						case 'popup':
						case 'colorbox':
							if (!$wConf['notNewRecords'] || t3lib_div::testInt($this->record['uid'])) {

									// Setting &P array contents:
								$params = array();
								$params['params'] = $wConf['params'];
								$params['exampleImg'] = $wConf['exampleImg'];
								$params['table'] = $this->table;
								$params['uid'] = $this->record['uid'];
								$params['pid'] = $this->record['pid'];
								$params['field'] = $this->field;
								$params['md5ID'] = $md5ID;
								$params['returnUrl'] = $this->contextObject->thisReturnUrl();

									// Resolving script filename and setting URL.
								if (!strcmp(substr($wConf['script'],0,4), 'EXT:')) {
									$wScript = t3lib_div::getFileAbsFileName($wConf['script']);
									if ($wScript) {
										$wScript = '../' . substr($wScript, strlen(PATH_site));
									} else break;
								} else {
									$wScript = $wConf['script'];
								}
								$url = $this->backPath.$wScript.(strstr($wScript,'?') ? '' : '?');

									// If there is no script and the type is "colorbox", break right away:
								if ((string)$wConf['type']=='colorbox' && !$wConf['script'])	{ break; }

									// If "script" type, create the links around the icon:
								if ((string)$wConf['type']=='script')	{
									$aUrl = $url.t3lib_div::implodeArrayForUrl('',array('P'=>$params));
									$outArr[]='<a href="'.htmlspecialchars($aUrl).'" onclick="'.$this->contextObject->blur().'return !TBE_EDITOR.isFormChanged();">'.
										$icon.
										'</a>';
								} else {

										// ... else types "popup", "colorbox" and "userFunc" will need additional parameters:
									$params['formName'] = $this->formName;
									$params['itemName'] = $itemName;
									$params['fieldChangeFunc'] = $this->fieldChangeFunc;

									switch((string)$wConf['type']) {
										case 'popup':
										case 'colorbox':
												// Current form value is passed as P[currentValue]!
											$addJS = $wConf['popup_onlyOpenIfSelected']?'if (!TBE_EDITOR.curSelected(\''.$itemName.$listFlag.'\')){alert('.$GLOBALS['LANG']->JScharCode($this->getLL('m_noSelItemForEdit')).'); return false;}':'';
											$curSelectedValues='+\'&P[currentSelectedValues]=\'+TBE_EDITOR.curSelected(\''.$itemName.$listFlag.'\')';
											$aOnClick=	$this->contextObject->blur().
														$addJS.
														'vHWin=window.open(\''.$url.t3lib_div::implodeArrayForUrl('',array('P'=>$params)).'\'+\'&P[currentValue]=\'+TBE_EDITOR.rawurlencode('.$this->contextObject->elName($itemName).'.value,200)'.$curSelectedValues.',\'popUp'.$md5ID.'\',\''.$wConf['JSopenParams'].'\');'.
														'vHWin.focus();return false;';
												// Setting "colorBoxLinks" - user LATER to wrap around the color box as well:
											$colorBoxLinks = Array('<a href="#" onclick="'.htmlspecialchars($aOnClick).'">','</a>');
											if ((string)$wConf['type']=='popup')	{
												$outArr[] = $colorBoxLinks[0].$icon.$colorBoxLinks[1];
											}
										break;
										case 'userFunc':
											$params['item'] = &$item;	// Reference set!
											$params['icon'] = $icon;
											$params['iTitle'] = $iTitle;
											$params['wConf'] = $wConf;
											$params['row'] = $this->record;
											$outArr[] = t3lib_div::callUserFunction($wConf['userFunc'],$params,$this);
										break;
									}
								}

									// Hide the real form element?
								if (is_array($wConf['hideParent']) || $wConf['hideParent']) {
									$item = $itemKinds[1];	// Setting the item to a hidden-field.
									if (is_array($wConf['hideParent']))	{
										$item.= $this->getSingleField_typeNone_render($wConf['hideParent'], $this->itemFormElValue);
									}
								}
							}
						break;
						case 'select':
							$this->fieldValue = array('config' => $wConf);
							$TSconfig = $this->setTSconfig($this->table, $this->record);
							$TSconfig[$this->field] = $TSconfig[$this->field]['wizards.'][$wid.'.'];
							$selItems = $this->addSelectOptionsToItemArray($this->initItemArray($this->fieldValue), $this->fieldValue, $TSconfig, $this->field);

							$opt = array();
							$opt[] = '<option>'.$iTitle.'</option>';
							foreach($selItems as $p)	{
								$opt[] = '<option value="'.htmlspecialchars($p[1]).'">'.htmlspecialchars($p[0]).'</option>';
							}
							if ($wConf['mode']=='append') {
								$assignValue = $this->elName($itemName).'.value=\'\'+this.options[this.selectedIndex].value+'.$this->elName($itemName).'.value';
							} elseif ($wConf['mode']=='prepend') {
								$assignValue = $this->elName($itemName).'.value+=\'\'+this.options[this.selectedIndex].value';
							} else {
								$assignValue = $this->elName($itemName).'.value=this.options[this.selectedIndex].value';
							}
							$sOnChange = $assignValue.';this.selectedIndex=0;'.implode('',$this->fieldChangeFunc);
							$outArr[] = '<select name="_WIZARD'.$fName.'" onchange="'.htmlspecialchars($sOnChange).'">'.implode('',$opt).'</select>';
						break;
					}

						// Color wizard colorbox:
					if ((string)$wConf['type'] == 'colorbox') {
						$dim = t3lib_div::intExplode('x', $wConf['dim']);
						$dX = t3lib_div::intInRange($dim[0], 1, 200, 20);
						$dY = t3lib_div::intInRange($dim[1], 1, 200, 20);
						$color = $this->itemFormElValue ? ' bgcolor="'.htmlspecialchars($this->itemFormElValue).'"' : '';
						$outArr[] = '<table border="0" cellpadding="0" cellspacing="0" id="'.$md5ID.'"'.$color.' style="'.htmlspecialchars($wConf['tableStyle']).'">
									<tr>
										<td>'.
											$colorBoxLinks[0].
											'<img src="clear.gif" width="'.$dX.'" height="'.$dY.'"'.t3lib_BEfunc::titleAltAttrib(trim($iTitle.' '.$this->itemFormElValue)).' border="0" />'.
											$colorBoxLinks[1].
											'</td>
									</tr>
								</table>';
					}
				}
			}

				// For each rendered wizard, put them together around the item.
			if (count($outArr)) {
				if ($wizConf['_HIDDENFIELD'])	$item = $itemKinds[1];

				$outStr = '';
				$vAlign = $wizConf['_VALIGN'] ? ' valign="'.$wizConf['_VALIGN'].'"' : '';
				if (count($outArr)>1 || $wizConf['_PADDING'])	{
					$dist = intval($wizConf['_DISTANCE']);
					if ($wizConf['_VERTICAL'])	{
						$dist = $dist ? '<tr><td><img src="clear.gif" width="1" height="'.$dist.'" alt="" /></td></tr>' : '';
						$outStr = '<tr><td>'.implode('</td></tr>'.$dist.'<tr><td>',$outArr).'</td></tr>';
					} else {
						$dist = $dist ? '<td><img src="clear.gif" height="1" width="'.$dist.'" alt="" /></td>' : '';
						$outStr = '<tr><td'.$vAlign.'>'.implode('</td>'.$dist.'<td'.$vAlign.'>',$outArr).'</td></tr>';
					}
					$outStr = '<table border="0" cellpadding="'.intval($wizConf['_PADDING']).'" cellspacing="0">'.$outStr.'</table>';
				} else {
					$outStr = implode('',$outArr);
				}

				if (!strcmp($wizConf['_POSITION'],'left')) {
					$outStr = '<tr><td'.$vAlign.'>'.$outStr.'</td><td'.$vAlign.'>'.$item.'</td></tr>';
				} elseif (!strcmp($wizConf['_POSITION'],'top')) {
					$outStr = '<tr><td>'.$outStr.'</td></tr><tr><td>'.$item.'</td></tr>';
				} elseif (!strcmp($wizConf['_POSITION'],'bottom')) {
					$outStr = '<tr><td>'.$item.'</td></tr><tr><td>'.$outStr.'</td></tr>';
				} else {
					$outStr = '<tr><td'.$vAlign.'>'.$item.'</td><td'.$vAlign.'>'.$outStr.'</td></tr>';
				}

				$item = '<table border="0" cellpadding="0" cellspacing="0">'.$outStr.'</table>';
			}
		}
		return $item;
	}


	// TODO: check if these functions should be moved to a common base class of elements input and text
	/**
	 * Returns parameters to set the width for a <input>/<textarea>-element
	 *
	 * @param	integer		The abstract size value (1-48)
	 * @param	boolean		If this is for a text area.
	 * @return	string		Either a "style" attribute string or "cols"/"size" attribute string.
	 */
	function formWidth($size=48,$textarea=0) {

			// Input or text-field attribute (size or cols)
		if ($this->docLarge) {
			$size = round($size*$this->form_largeComp);
		}
		$wAttrib = $textarea ? 'cols' : 'size';
		if (!$GLOBALS['CLIENT']['FORMSTYLE']) {	// If not setting the width by style-attribute
			$retVal = ' ' . $wAttrib . '="' . $size . '"';
		} else {
				// Setting width by style-attribute. 'cols' MUST be avoided with NN6+
			$pixels = ceil($size * $this->form_rowsToStylewidth);
			$theStyle = 'width:' . $pixels . 'px;' . $this->defStyle . $this->formElStyle($textarea ? 'text' : 'input');
			$retVal = ' style="'.htmlspecialchars($theStyle).'"';

			$class = $this->formElClass($textarea?'text':'input');
			if ($class) {
				$retVal.= ' class="'.htmlspecialchars($class).'"';
			}
		}
		return $retVal;
	}

	/**
	 * Returns parameters to set width for a textarea field
	 *
	 * @param	integer		The abstract width (1-48)
	 * @param	string		Empty or "off" (text wrapping in the field or not)
	 * @return	string		The "cols" attribute string (or style from formWidth())
	 * @see formWidth()
	 */
	function formWidthText($size=48, $wrap='') {
		$wTags = $this->formWidth($size, 1);
			// Netscape 6+ seems to have this ODD problem where there WILL ALWAYS be wrapping with the cols-attribute set and NEVER without the col-attribute...
		if (strtolower(trim($wrap)) != 'off' && $GLOBALS['CLIENT']['BROWSER']=='net' && $GLOBALS['CLIENT']['VERSION']>=5)	{
			$wTags.= ' cols="'.$size.'"';
		}
		return $wTags;
	}

	/**
	 * Wrapping labels
	 * Currently not implemented - just returns input value.
	 *
	 * @param	string		Input string.
	 * @return	string		Output string.
	 */
	function wrapLabels($str)	{
		return $str;
	}

	/**
	 * Wraps the icon of a relation item (database record or file) in a link opening the context menu for the item.
	 * Icons will be wrapped only if $this->enableClickMenu is set. This must be done only if a global SOBE object exists and if the necessary JavaScript for displaying the context menus has been added to the page properties.
	 *
	 * @param	string		The icon HTML to wrap
	 * @param	string		Table name (eg. "pages" or "tt_content") OR the absolute path to the file
	 * @param	integer		The uid of the record OR if file, just blank value.
	 * @return	string		HTML
	 */
	function getClickMenu($str,$table,$uid='')	{
		if ($this->enableClickMenu)	{
			$onClick = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($str,$table,$uid,1,'','+copy,info,edit,view', TRUE);
			return '<a href="#" onclick="'.htmlspecialchars($onClick).'">'.$str.'</a>';
		}
	}

	/**
	 * Return default "style" / "class" attribute line.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 */
	// copied from t3lib_tceforms::insertDefStyle
	public function insertDefaultElementStyle($type)	{
		$out = '';

		// TODO: replace $this->defStyle by access to defaultStyle in contextObject/formObject
		$style = trim($this->defStyle.$this->formElStyle($type));
		$out.= $style?' style="'.htmlspecialchars($style).'"':'';

		$class = $this->formElClass($type);
		$out.= $class?' class="'.htmlspecialchars($class).'"':'';

		return $out;
	}

	/**
	 * Get style CSS values for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 * @see formElStyleClassValue()
	 */
	protected function formElStyle($type)	{
		return $this->formElStyleClassValue($type);
	}

	/**
	 * Get class attribute value for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 * @see formElStyleClassValue()
	 */
	public function formElClass($type)	{
		return $this->formElStyleClassValue($type, TRUE);
	}

	/**
	 * Get style CSS values for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @param	boolean		If set, will return value only if prefixed with CLASS, otherwise must not be prefixed "CLASS"
	 * @return	string		CSS attributes
	 */
	protected function formElStyleClassValue($type, $class=FALSE)	{
			// Get value according to field:
		if (isset($this->fieldStyle[$type]))	{
			$style = trim($this->fieldStyle[$type]);
		} else {
			$style = trim($this->fieldStyle['all']);
		}

			// Check class prefixed:
		if (substr($style,0,6)=='CLASS:')	{
			$out = $class ? trim(substr($style,6)) : '';
		} else {
			$out = !$class ? $style : '';
		}

		return $out;
	}
}

?>