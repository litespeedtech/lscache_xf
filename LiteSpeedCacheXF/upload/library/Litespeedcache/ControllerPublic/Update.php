<?php

class Litespeedcache_ControllerPublic_Update extends XenForo_ControllerPublic_Abstract
{

	/**
	 * actionIndex connects the ControllerAdmin to the admin-template for
	 * the rewrite rule overlay.
	 *
	 * This page will use the 'lscacherewrite' admin template.
	 */
	public function actionIndex()
	{
		$req = $this->getRequest();
		$data = array();
		$this->_routeMatch->setResponseType('json');

		if (($req->isPost()) && ($req->isXmlHttpRequest())) {
			$tid = $this->_input->filterSingle('tid', XenForo_Input::UINT);
			if (!is_null($tid)) {
				$threadModel = $this->getModelFromCache('XenForo_Model_Thread');
				$threadModel->logThreadView($tid);
				$data['tid'] = $tid;
			}
		}
		else {
			Litespeedcache_Listener_Global::setNotCacheable(
				'Invalid request type - Not post or not ajax.');
		}

		return $this->responseView('Litespeedcache_ViewPublic_Update', '', $data);
	}
}


