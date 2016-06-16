<?php

class Litespeedcache_ViewAdmin_Settings extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		$serverVary =
			$_SERVER[Litespeedcache_Listener_Global::COOKIE_LSCACHE_VARY_NAME];
		$optionVary =
			XenForo_Application::getOptions()->litespeedcacheXF_logincookie;
		foreach($this->_params['options'] as $option) {
			$this->_params[$option['option_id']] =
					XenForo_ViewAdmin_Helper_Option::renderPreparedOptionHtml(
						$this, $option, $this->_params['canEditOptionDefinition']
					);
		}

		if (((isset($serverVary))
				&& (!in_array($optionVary, explode(',', $serverVary))))
			|| ((!isset($serverVary))
				&& ($optionVary != Litespeedcache_Listener_Global::COOKIE_LSCACHE_VARY_DEFAULT))) {
			$this->_params['showMessage']
				= Litespeedcache_Listener_Global::buildVaryString($optionVary);
		}

		$this->_params['editLink'] =
			$this->createTemplateObject('option_list_option_editlink', array(
				'preparedOption' => $this->_params['lscacheoption_separatemobile'],
				'canEditOptionDefinition' => $this->_params['canEditOptionDefinition']
		));

	}
}


