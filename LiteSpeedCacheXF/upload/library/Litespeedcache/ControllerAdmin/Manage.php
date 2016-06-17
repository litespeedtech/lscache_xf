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
			$response_params = array();
			$response_msg = $this->parseActions($response_params);
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('lscache/actions'),
				$response_msg,
				$response_params
			);
		}

		$viewParams = array();
		return $this->responseView('XenForo_ViewAdmin_Litespeedcache',
				'lscacheactions', $viewParams);
	}

	/**
	 * Verify that the rewrite rules are correct.
	 *
	 * @return boolean
	 */
	private function verifySetup(&$params)
	{
		$search = array();
		$loginCookie = XenForo_Application::getOptions()
			->litespeedcacheXF_logincookie;
		$cookiePrefix = XenForo_Application::get('config')->cookie->prefix;
		$serverVary = $this->getRequest()->getServer(
			Litespeedcache_Listener_Global::COOKIE_LSCACHE_VARY_NAME);
		if ($serverVary) {
			$serverVary = explode(',', $serverVary);
		}
		$styles = $this->getModelFromCache('XenForo_Model_Style')
			->getAllStylesAsFlattenedTree();
		$languages = $this->getModelFromCache('XenForo_Model_Language')
			->getAllLanguagesAsFlattenedTree();

		$count = count($styles);
		if ($count > 1) {
			$count = 0;
			foreach ($styles as $style) {
				if ($style['user_selectable']) {
					++$count;
				}
				if ($count > 1) {
					$search[] = $cookiePrefix . 'style_id';
					break;
				}
			}
		}
		if (count($languages) > 1) {
			$search[] = $cookiePrefix . 'language_id';
		}
		if ($loginCookie !=
			Litespeedcache_Listener_Global::COOKIE_LSCACHE_VARY_NAME) {
			$search[] = $loginCookie;
		}
		if ((empty($search)) || (empty(array_diff($search, $serverVary)))) {
			$params[Litespeedcache_Listener_Global::LSCACHE_VARY_TEMPLATE_NAME]
				= 1;
		}
		else {
			$params[Litespeedcache_Listener_Global::LSCACHE_VARY_TEMPLATE_NAME]
				= implode(',', $search);
		}
	}

	/**
	 * Parse for the action selected and do the action.
	 *
	 * @return mixed Redirect message on success, false on failure.
	 */
	private function parseActions(&$response_params)
	{
		$phrases = $this->getModelFromCache('XenForo_Model_Phrase');
		$purge_all = $phrases->getMasterPhraseValue('lscache_purge_all');
		$purge_home = $phrases->getMasterPhraseValue('lscache_purge_home');
		$verifysetup = $phrases->getMasterPhraseValue('lscache_verifysetup');

		switch($_REQUEST[self::ACTION_NAME]) {
			case $purge_all:
				Litespeedcache_Listener_Global::addPurgeTag('*');
				return new XenForo_Phrase('lscache_purge_success');
			case $purge_home:
				Litespeedcache_Listener_Global::addPurgeTag(
						Litespeedcache_Listener_Global::CACHETAG_FORUMLIST);
				return new XenForo_Phrase('lscache_purge_success');
			case $verifysetup:
				$this->verifySetup($response_params);
				return new XenForo_Phrase('lscache_verifysetup_success');
			default:
				break;
		}
		return false;
	}


}
