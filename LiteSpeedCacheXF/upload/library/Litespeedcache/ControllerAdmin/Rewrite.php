<?php

class Litespeedcache_ControllerAdmin_Rewrite extends XenForo_ControllerAdmin_Abstract
{

	/**
	 * actionIndex connects the ControllerAdmin to the admin-template for
	 * the rewrite rule overlay.
	 *
	 * This page will use the 'lscacherewrite' admin template.
	 */
	public function actionIndex()
	{
		$viewParams = array();
		return $this->responseView('Litespeedcache_ViewAdmin_Rewrite',
				'lscacherewrite', $viewParams);
	}
}


