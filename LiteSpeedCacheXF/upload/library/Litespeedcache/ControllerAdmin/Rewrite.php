<?php

class Litespeedcache_ControllerAdmin_Rewrite extends XenForo_ControllerAdmin_Abstract
{

	public function actionIndex()
	{

		$viewParams = array();

		return $this->responseView('Litespeedcache_ViewAdmin_Rewrite',
				'lscacherewrite', $viewParams);
	}
}


