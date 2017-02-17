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
			'lscacheoption_purgelikes' => $optionModel->prepareOption(
				$optionModel->getOptionById('litespeedcacheXF_purgelikes')),
			'lscacheoption_updatethreadviews' => $optionModel->prepareOption(
				$optionModel->getOptionById('litespeedcacheXF_updatethreadviews')),
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'options' => $optionModel->prepareOptions(
					$optionModel->getOptionsByIds(
						array(
							'litespeedcacheXF_homettl',
							'litespeedcacheXF_publicttl',
							'litespeedcacheXF_logincookie',
							'litespeedcacheXF_cacheprefix',
							'litespeedcacheXF_separatemobile',
							'litespeedcacheXF_nocacheuri',
							'litespeedcacheXF_purgelikes',
							'litespeedcacheXF_updatethreadviews',
						))),
			'canEditOptionDefinition' => $optionModel->canEditOptionAndGroupDefinitions()
		);

		return $this->responseView('Litespeedcache_ViewAdmin_Settings',
				'lscachesettings', $viewParams);
	}
}


