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
		$fieldPrefix = 'options';
		$viewParams = array(
			'lscacheoption_separatemobile' => $optionModel->prepareOption(
				$optionModel->getOptionById('litespeedcacheXF_separatemobile')),
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'options' => $optionModel->prepareOptions(
					$optionModel->getOptionsByIds(array('litespeedcacheXF_homettl',
					'litespeedcacheXF_publicttl', 'litespeedcacheXF_logincookie',
					'litespeedcacheXF_separatemobile', 'litespeedcacheXF_nocacheuri'))),
			'canEditOptionDefinition' => $optionModel->canEditOptionAndGroupDefinitions()
		);

		return $this->responseView('Litespeedcache_ViewAdmin_Settings',
				'lscachesettings', $viewParams);
	}
}


