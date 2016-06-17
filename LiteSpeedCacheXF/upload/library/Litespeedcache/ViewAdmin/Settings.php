<?php

class Litespeedcache_ViewAdmin_Settings extends XenForo_ViewAdmin_Base
{
	public function renderHtml()
	{
		foreach($this->_params['options'] as $option) {
			$this->_params[$option['option_id']] =
					XenForo_ViewAdmin_Helper_Option::renderPreparedOptionHtml(
						$this, $option, $this->_params['canEditOptionDefinition']
					);
		}

		Litespeedcache_Listener_Global::varyCmp(
			$this->_params,
			$_SERVER[Litespeedcache_Listener_Global::COOKIE_LSCACHE_VARY_NAME]
		);

		$this->_params['editLink'] =
			$this->createTemplateObject('option_list_option_editlink', array(
				'preparedOption' => $this->_params['lscacheoption_separatemobile'],
				'canEditOptionDefinition' => $this->_params['canEditOptionDefinition']
		));

	}
}


