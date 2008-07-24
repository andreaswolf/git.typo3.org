<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_TextElement extends t3lib_TCEforms_AbstractElement {
	protected $item;

	public function renderField() {
			// Init config:
		$config = $this->fieldConf['config'];

		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			return $this->TCEformsObject->getSingleField_typeNone_render($config, $this->itemFormElValue);
		}

			// Setting columns number:
		$cols = t3lib_div::intInRange($config['cols'] ? $config['cols'] : 30, 5, $this->TCEformsObject->maxTextareaWidth);

			// Setting number of rows:
		$origRows = $rows = t3lib_div::intInRange($config['rows'] ? $config['rows'] : 5, 1, 20);
		if (strlen($this->itemFormElValue) > $this->TCEformsObject->charsPerRow*2)	{
			$cols = $this->TCEformsObject->maxTextareaWidth;
			$rows = t3lib_div::intInRange(round(strlen($this->itemFormElValue)/$this->TCEformsObject->charsPerRow), count(explode(chr(10),$this->itemFormElValue)), 20);
			if ($rows<$origRows)	$rows = $origRows;
		}

			// Init RTE vars:
		$RTEwasLoaded = 0;				// Set true, if the RTE is loaded; If not a normal textarea is shown.
		$RTEwouldHaveBeenLoaded = 0;	// Set true, if the RTE would have been loaded if it wasn't for the disable-RTE flag in the bottom of the page...

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. Traditionally, this is where RTE configuration has been found.
		$specConf = $this->TCEformsObject->getSpecConfFromString($PA['extra'], $this->fieldConf['defaultExtras']);

			// Setting up the altItem form field, which is a hidden field containing the value
		$altItem = '<input type="hidden" name="'.htmlspecialchars($PA['itemFormElName']).'" value="'.htmlspecialchars($this->itemFormElValue).'" />';

			// If RTE is generally enabled (TYPO3_CONF_VARS and user settings)
		if ($this->TCEformsObject->RTEenabled) {
			$p = t3lib_BEfunc::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
			if (isset($specConf['richtext']) && (!$p['flag'] || !$row[$p['flag']]))	{	// If the field is configured for RTE and if any flag-field is not set to disable it.
				t3lib_BEfunc::fixVersioningPid($table,$row);
				list($tscPID,$thePidValue) = $this->TCEformsObject->getTSCpid($table,$row['uid'],$row['pid']);

					// If the pid-value is not negative (that is, a pid could NOT be fetched)
				if ($thePidValue >= 0)	{
					$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($tscPID));
					$RTEtypeVal = t3lib_BEfunc::getTCAtypeValue($table,$row);
					$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$table,$field,$RTEtypeVal);

					if (!$thisConfig['disabled'])	{
						if (!$this->TCEformsObject->disableRTE)	{
							$this->TCEformsObject->RTEcounter++;

								// Find alternative relative path for RTE images/links:
							$eFile = t3lib_parsehtml_proc::evalWriteFile($specConf['static_write'], $row);
							$RTErelPath = is_array($eFile) ? dirname($eFile['relEditFile']) : '';

								// Get RTE object, draw form and set flag:
							$RTEobj = &t3lib_BEfunc::RTEgetObj();
							$item = $RTEobj->drawRTE($this->TCEformsObject,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue);

								// Wizard:
							$item = $this->TCEformsObject->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf,1);

							$RTEwasLoaded = 1;
						} else {
							$RTEwouldHaveBeenLoaded = 1;
							$this->TCEformsObject->commentMessages[] = $PA['itemFormElName'].': RTE is disabled by the on-page RTE-flag (probably you can enable it by the check-box in the bottom of this page!)';
						}
					} else $this->TCEformsObject->commentMessages[] = $PA['itemFormElName'].': RTE is disabled by the Page TSconfig, "RTE"-key (eg. by RTE.default.disabled=0 or such)';
				} else $this->TCEformsObject->commentMessages[] = $PA['itemFormElName'].': PID value could NOT be fetched. Rare error, normally with new records.';
			} else {
				if (!isset($specConf['richtext']))	$this->TCEformsObject->commentMessages[] = $PA['itemFormElName'].': RTE was not configured for this field in TCA-types';
				if (!(!$p['flag'] || !$row[$p['flag']]))	 $this->TCEformsObject->commentMessages[] = $PA['itemFormElName'].': Field-flag ('.$PA['flag'].') has been set to disable RTE!';
			}
		}

			// Display ordinary field if RTE was not loaded.
		if (!$RTEwasLoaded) {
			if ($specConf['rte_only'])	{	// Show message, if no RTE (field can only be edited with RTE!)
				$item = '<p><em>'.htmlspecialchars($this->TCEformsObject->getLL('l_noRTEfound')).'</em></p>';
			} else {
				if ($specConf['nowrap'])	{
					$wrap = 'off';
				} else {
					$wrap = ($config['wrap'] ? $config['wrap'] : 'virtual');
				}

				$classes = array();
				if ($specConf['fixed-font'])	{ $classes[] = 'fixed-font'; }
				if ($specConf['enable-tab'])	{ $classes[] = 'enable-tab'; }

				$formWidthText = $this->TCEformsObject->formWidthText($cols,$wrap);

					// Extract class attributes from $formWidthText (otherwise it would be added twice to the output)
				$res = array();
				if (preg_match('/ class="(.+?)"/',$formWidthText,$res))	{
					$formWidthText = str_replace(' class="'.$res[1].'"','',$formWidthText);
					$classes = array_merge($classes, explode(' ',$res[1]));
				}

				if (count($classes))	{
					$class = ' class="'.implode(' ',$classes).'"';
				} else $class='';

				$evalList = t3lib_div::trimExplode(',',$config['eval'],1);
				foreach ($evalList as $func) {
					switch ($func) {
						case 'required':
							$this->TCEformsObject->registerRequiredProperty('field', $table.'_'.$row['uid'].'_'.$field, $PA['itemFormElName']);
							break;
						default:
							if (substr($func, 0, 3) == 'tx_')	{
								// Pair hook to the one in t3lib_TCEmain::checkValue_input_Eval() and t3lib_TCEmain::checkValue_text_Eval()
								$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func].':&'.$func);
								if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue'))	{
									$_params = array(
										'value' => $this->itemFormElValue
									);
									$this->itemFormElValue = $evalObj->deevaluateFieldValue($_params);
								}
							}
							break;
					}
				}

				$iOnChange = implode('',$this->fieldChangeFunc);
				$item.= '
							<textarea name="'.$this->itemFormElName.'"'.$formWidthText.$class.' rows="'.$rows.'" wrap="'.$wrap.'" onchange="'.htmlspecialchars($iOnChange).'"'.$PA['onFocus'].'>'.
							t3lib_div::formatForTextarea($this->itemFormElValue).
							'</textarea>';
				$item = $this->TCEformsObject->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf,$RTEwouldHaveBeenLoaded);
			}
		}

		return $item;
	}
}