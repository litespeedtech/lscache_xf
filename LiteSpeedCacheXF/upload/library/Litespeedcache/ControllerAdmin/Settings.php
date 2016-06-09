<?php

class Litespeedcache_ControllerAdmin_Settings extends XenForo_ControllerAdmin_Abstract
{
	/**
	 * actionIndex connects the ControllerAdmin to the admin-template for
	 * the LiteSpeed Cache settings page.
	 *
	 * This page will use the 'lscachesettings' admin template.
	 */
	public function actionIndex()
	{
		$optionModel = $this->getModelFromCache('XenForo_Model_Option');
		$viewParams = array(
			'options' => $optionModel->prepareOptions(
					$optionModel->getOptionsByIds(array('litespeedcacheXF_homettl',
					'litespeedcacheXF_publicttl', 'litespeedcacheXF_separatemobile'))),
			'canEditOptionDefinition' => $optionModel->canEditOptionAndGroupDefinitions()
		);

		return $this->responseView('Litespeedcache_ViewAdmin_Settings',
				'lscachesettings', $viewParams);
	}
}


