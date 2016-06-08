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
	}
}


