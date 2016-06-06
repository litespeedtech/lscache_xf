<?php

/**
 * This class controls the LiteSpeed Cache Management tab in the XenForo
 * Admin Control Panel.
 *
 */
class Litespeedcache_ControllerAdmin_Manage extends XenForo_ControllerAdmin_Abstract
{
	/**
	 * When creating new admin actions, use this as the input's name.
	 *
	 * The value should be unique and a master phrase.
	 * Master phrases are added in Appearance->phrases.
	 */
	const ACTION_NAME = 'lscache_action_submit';

	/**
	 * actionIndex connects the ControllerAdmin to the admin-template for
	 * the lscachemanagement index page.
	 *
	 * This page will use the 'lscachemanageinfo' admin template.
	 */
	public function actionIndex()
	{
		return $this->responseReroute(__CLASS__, 'Actions');
	}

	/**
	 * actionActions connects the ControllerAdmin to the admin-template for
	 * the lscachemanagement Actions page.
	 *
	 * This page will use the 'lscacheactions' admin template.
	 *
	 * If action name is set, this function will also parse the action selected.
	 */
	public function actionActions()
	{
		if ((!empty($_REQUEST)) && (isset($_REQUEST[self::ACTION_NAME]))) {
			$response_msg = $this->parseActions();
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('lscache/actions'),
				$response_msg
			);
		}

		$viewParams = array();
		return $this->responseView('XenForo_ViewAdmin_Litespeedcache',
				'lscacheactions', $viewParams);
	}

	/**
	 * Parse for the action selected and do the action.
	 *
	 * @return mixed Redirect message on success, false on failure.
	 */
	private function parseActions()
	{
		$phrases = $this->getModelFromCache('XenForo_Model_Phrase');
		$purge_all = $phrases->getMasterPhraseValue('lscache_purge_all');
		$purge_home = $phrases->getMasterPhraseValue('lscache_purge_home');

		switch($_REQUEST[self::ACTION_NAME]) {
			case $purge_all:
				Litespeedcache_Listener_Global::addPurgeTag('*');
				return new XenForo_Phrase('lscache_purge_success');
			case $purge_home:
				Litespeedcache_Listener_Global::addPurgeTag(
						Litespeedcache_Listener_Global::CACHETAG_FORUMLIST);
				return new XenForo_Phrase('lscache_purge_success');
			default:
				break;
		}
		return false;
	}


}
