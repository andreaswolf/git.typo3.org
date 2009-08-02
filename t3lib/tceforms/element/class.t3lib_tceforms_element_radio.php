<?php

require_once(PATH_t3lib.'tceforms/element/class.t3lib_tceforms_element_abstract.php');


class t3lib_TCEforms_Element_Radio extends t3lib_TCEforms_Element_Abstract {
	protected function renderField() {
		$config = $this->fieldConfig['config'];

		$disabled = '';
		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Get items for the array:
		$selItems = $this->initItemArray($this->fieldConf);
		if ($config['itemsProcFunc']) $selItems = $this->procItems($selItems,$this->fieldTSConfig['itemsProcFunc.'],$config,$table,$row,$field);

			// Traverse the items, making the form elements:
		for ($c=0;$c<count($selItems);$c++) {
			$p = $selItems[$c];
			$rID = $this->itemFormElID.'_'.$c;
			$rOnClick = implode('',$this->fieldChangeFunc);
			$rChecked = (!strcmp($p[1],$this->itemFormElValue)?' checked="checked"':'');
			$item.= '<input type="radio"'.$this->insertDefaultElementStyle('radio').' name="'.$this->itemFormElName.'" value="'.htmlspecialchars($p[1]).'" onclick="'.htmlspecialchars($rOnClick).'"'.$rChecked.$this->onFocus.$disabled.' id="'.$rID.'" />
					<label for="'.$rID.'">'.htmlspecialchars($p[0]).'</label>
					<br />';
		}

		return $item;
	}
}
