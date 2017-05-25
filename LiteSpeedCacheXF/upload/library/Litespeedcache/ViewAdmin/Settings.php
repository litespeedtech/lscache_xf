<?php

/**
 * NOTE WHEN CREATING A NEW OPTION:
 *
 * There are a minimum of 3 steps that need to happen when creating a new option.
 * 1. In the XenForo admin, go to home->options. From there, go to the LiteSpeed Cache option group
 * and create a new option. Use the other options as guidelines.
 * 2. In Litespeedcache_ControllerAdmin_Settings, add the new option id to
 * the array of options.
 * 3*. In the admin template lscachesettings, add a xen:raw for the new option.
 *
 * If the new option is a check box:
 * 3*. Instead of a xen:raw, copy the other checkbox unit and add that.
 * 4. In Litespeedcache_ControllerAdmin_Settings, need to add a viewparam similar to:
			'lscacheoption_separatemobile' => $optionModel->prepareOption(
				$optionModel->getOptionById('litespeedcacheXF_separatemobile')),
 * 5. In Litespeedcache_ViewAdmin_Settings, add an editlink for the checkbox.
 *
 * Somewhat optional step 6:
 * Add a new option validator here (the function has to exist before pointing
 * to it in the option creator in step 1.)
 */

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
			$this->_renderer->getRequest()->getServer(
				Litespeedcache_Listener_Global::COOKIE_LSCACHE_VARY_NAME,
				array())
		);

		foreach ($this->_params as $key => $value) {
			if (strpos($key, 'lscacheoption_') === false) {
				continue;
			}
			$this->_params[$key.'_editLink'] =
				$this->createTemplateObject('option_list_option_editlink', array(
					'preparedOption' => $this->_params[$key],
					'canEditOptionDefinition' => $this->_params['canEditOptionDefinition']
			));
		}

	}
}


