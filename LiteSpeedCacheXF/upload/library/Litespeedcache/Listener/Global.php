<?php

/* Add-on Name:       LiteSpeed Cache
 * Description:       XenForo Add-on to connect to LSCache on LiteSpeed Web Server.
 * Author:            LiteSpeed Technologies
 * Author URI:        https://www.litespeedtech.com
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl.html
 *
 * Copyright (C) 2016 LiteSpeed Technologies, Inc.
 */

class Litespeedcache_Listener_Global
{

	const COOKIE_LSCACHE_VARY_DEFAULT = '_lscache_vary' ; // fixed name, cannot change
	const COOKIE_LSCACHE_VARY_NAME = 'LSCACHE_VARY_COOKIE';
	const STATE_LOGGEDIN = 1 ;
	const STATE_STAYLOGGEDIN = 2 ;

	const CACHETAG_FORUMLIST = 'H';
	const CACHETAG_FORUM = 'F.';
	const CACHETAG_THREAD = 'T.';

	const HEADER_PURGE = 'X-LiteSpeed-Purge';
	const HEADER_CACHE_TAG = 'X-LiteSpeed-Tag';

	private static $userState = 0 ;
	private static $currentVary ;
	private static $cacheTags = array();
	private static $purgeTags = array();
	private static $isCacheable = true;

	/**
	 * @xfcp: XenForo_Model_User
	 *
	 */
	public static function extendUserModel( $class, &$extend )
	{
		if ( Litespeedcache_Listener_Global::lscache_enabled() ) {
			$extend[] = 'Litespeedcache_Extend_XenForo_Model_User';
		}
	}

	/**
	 * Detect if the server has LiteSpeed Cache enabled.
	 *
	 * @return boolean true if server has cache, else false.
	 */
	public static function lscache_enabled()
	{
		return (isset($_SERVER['X-LSCACHE']) && $_SERVER['X-LSCACHE']);
	}

	/**
	 * Set the user state to logged in and/or stay logged in.
	 *
	 * @param integer $value The bit to set user state to.
	 */
	public static function setUserState( $value )
	{
		self::$userState |= $value;
	}

	public static function setNotCacheable($reason) {
		if (XenForo_Application::debugMode()) {
			error_log('LSCache Do Not Cache because ' . $reason);
		}
		self::$isCacheable = false;
	}

	/**
	 * Add a purge tag to the request. If multiple tags need to be added,
	 * parameter should be an array.
	 *
	 * The purge tag(s) will notify the server to invalidate the cache entries
	 * associated with the tag(s).
	 *
	 * @param mixed $tag A string or an array of purge tags to add.
	 */
	public static function addPurgeTag($tag)
	{
		if (is_array($tag)) {
			self::$purgeTags = array_merge(self::$purgeTags, $tag);
		}
		else {
			self::$purgeTags[] = $tag;
		}
	}

	/**
	 * Add a cache tag to associate with the page. If multiple tags need to be
	 * added, parameter should be an array.
	 *
	 * The cache tag is for the tagging system used by the server's cache.
	 *
	 * @param mixed $tag A string or an array of cache tags to add.
	 */
	public static function addCacheTag($tag)
	{
		if (is_array($tag)) {
			self::$cacheTags = array_merge(self::$cacheTags, $tag);
		}
		else {
			self::$cacheTags[] = $tag;
		}
	}

	/**
	 * Sets the cache vary cookie. The cookie is used by the cache to
	 * distinguish logged in users from non-logged in users.
	 *
	 * @param mixed $value True/false for logged in/not. Expiration time if
	 * stay logged in.
	 */
	private static function setCacheVaryCookie( $value )
	{
		// has to call php function directly to avoid xf prefix

		$secure = XenForo_Application::$secure;
		$httpOnly = true;

		$cookieConfig = XenForo_Application::get('config')->cookie;
		$path = $cookieConfig->path;
		$domain = $cookieConfig->domain;
		$expiration = 0;
		$cookieValue = 1;

		if ( $value === false ) {
			$expiration = XenForo_Application::$time - 86400 * 365;
			$cookieValue = 0;
		}
		else if ( $value === true ) {
			$expiration = 0; // default only for current session
		}
		else {
			// stay logged in, same as xf_usr
			$expiration = XenForo_Application::$time + $value;
		}
		setcookie(self::$currentVary, $cookieValue, $expiration, $path, $domain, $secure, $httpOnly);
	}

	/**
	 * Front Controller Post View event listener.
	 * Checks if the cache is enabled and the user is not logged in.
	 * If both are the case, set up the cache/purge headers before the response
	 * is sent out.
	 *
	 * @param XenForo_FrontController $fc
	 * @param type $output
	 */
	public static function frontControllerPostView(
			XenForo_FrontController $fc, &$output )
	{
		if ( !Litespeedcache_Listener_Global::lscache_enabled())
			return;

		$response = $fc->getResponse();
		$request = $fc->getRequest();
		$cacheable = true;
		$uri = $request->getRequestUri();
		$options = XenForo_Application::getOptions();
		$is_mobile = XenForo_Visitor::isBrowsingWith('mobile');

		if ((XenForo_Visitor::getUserId())
				|| (strpos($uri, '/admin.php') !== false)
				|| (XenForo_Helper_Cookie::getCookie('user'))
				|| (self::$isCacheable == false)) {
			$cacheable = false;
		}

		if (($request->getServer(self::COOKIE_LSCACHE_VARY_NAME))
			&& ($options->litespeedcacheXF_logincookie != self::COOKIE_LSCACHE_VARY_DEFAULT)) {
			$serverVary = $request->getServer(self::COOKIE_LSCACHE_VARY_NAME);
			if (in_array($options->litespeedcacheXF_logincookie,
				explode(',', $serverVary))) {
				self::$currentVary = $serverVary;
			}
			else {
				$cacheable = false;
				error_log('XenForo login cookie setting does not match'
					. ' server rewrite rule. Do not cache.');
			}
		}
		else {
			self::$currentVary = self::COOKIE_LSCACHE_VARY_DEFAULT;
		}

		if (($cacheable)
				&& ($options->litespeedcacheXF_separatemobile)) {
			if ($request->getServer('LSCACHE_VARY_VALUE') === 'ismobile') {
				if (!$is_mobile) {
					$cacheable = false;
				}
			}
			elseif ($is_mobile) {
				$cacheable = false;
			}
		}

		if ( $cacheable ) {
			if ( isset($_COOKIE[self::$currentVary]) ) {
				self::setCacheVaryCookie(false);
			}
			if (!empty(self::$cacheTags)) {
				$tags = array_unique(self::$cacheTags);
				$response->setHeader(self::HEADER_CACHE_TAG,
						implode(',', $tags));
			}
			if ((isset($tags))
					&& (in_array(self::CACHETAG_FORUMLIST, $tags))) {
				$maxage = $options->litespeedcacheXF_homettl;
			}
			else {
				$maxage = $options->litespeedcacheXF_publicttl;
			}
			$cache_header = 'public,max-age=' . $maxage;
			$response->setHeader('X-LiteSpeed-Cache-Control', $cache_header);
		}
		else {
			if (self::$userState & self::STATE_STAYLOGGEDIN) {
				self::setCacheVaryCookie(30 * 86400);
			}
			elseif ((self::$userState & self::STATE_LOGGEDIN)
					|| ((XenForo_Visitor::getUserId())
						&& (is_null($request->getCookie(
								self::$currentVary))))) {
				self::setCacheVaryCookie(true);
			}
			$response->setHeader('X-LiteSpeed-Cache-Control', 'no-cache');
		}

		if (!empty(self::$purgeTags)) {
			if (in_array('*', self::$purgeTags)) {
				$tags = '*';
			}
			else {
				self::$purgeTags[] = self::CACHETAG_FORUMLIST;
				$tags = implode(',', array_unique(self::$purgeTags));
			}
			$response->setHeader(self::HEADER_PURGE, $tags);
		}

		/* This header is used to handle XenForo's cookie detection.
		 * When a user attempts to log in, XenForo will check if his/her request
		 * has a cookie. If not, XenForo will return that it requires cookie support.
		 * This header makes it so that pages served from LiteSpeed Cache will include a
		 * 'Set-Cookie: lsc_active=1' header, so that when a client tries to log in,
		 * there will be a cookie set, passing the cookie detection.
		 */
		$response->setHeader('LSC-Cookie', 'lsc_active=1');
	}

	/**
	 * Template Hook event listener.
	 * The template hook names are used to determine if the page loaded is
	 * a taggable one.
	 *
	 * @param type $hookName
	 * @param type $contents
	 * @param array $hookParams
	 * @param XenForo_Template_Abstract $template
	 */
	public static function checkForCacheTags($hookName, &$contents,
			array $hookParams, XenForo_Template_Abstract $template)
	{
		if ($hookName[0] != 'f' && $hookName[0] != 't') {
			return;
		}
		switch ($hookName) {
			case 'forum_view_threads_before':
				$forum = $hookParams['forum'];
				self::$cacheTags[] = self::CACHETAG_FORUM . $forum['node_id'];
				break;
			case 'thread_view_form_before':
				$thread = $hookParams['thread'];
				self::$cacheTags[] = self::CACHETAG_THREAD . $thread['thread_id'];
				break;
			case 'forum_list_nodes':
				self::$cacheTags[] = self::CACHETAG_FORUMLIST;
				break;
			default:
				break;
		}
	}

	/**
	 * Helper function for purging by post id.
	 *
	 * @param type $controller
	 * @param integer $postId Optional. If not provided, will attempt to get it
	 * from input.
	 */
	private static function purgeByPostId($controller, $postId = -1)
	{
		if ($postId == -1) {
			$postId = $controller->getInput()->filterSingle('post_id',
					XenForo_Input::UINT);
		}
		if ($postId == 0) {
			return;
		}
		$postModel = XenForo_Model::create('XenForo_Model_Post')
				->getPostById($postId);
		$threadId = $postModel['thread_id'];
		$forum = XenForo_Model::create('XenForo_Model_Forum')
				->getForumByThreadId($threadId);
		if ($threadId != 0) {
			self::$purgeTags[] = self::CACHETAG_THREAD . $threadId;
		}
		if ($forum['node_id'] != 0) {
			self::$purgeTags[] = self::CACHETAG_FORUM . $forum['node_id'];
		}
	}

	/**
	 * Helper function for purging by thread id.
	 *
	 * @param type $controller
	 * @param integer $threadId Optional. If not provided, will attempt to get
	 * it from input.
	 */
	private static function purgeByThreadId($controller, $threadId = -1)
	{
		if ($threadId == -1) {
			$threadId = $controller->getInput()->filterSingle('thread_id',
					XenForo_Input::UINT);
		}
		if ($threadId == 0) {
			return;
		}
		$forum = XenForo_Model::create('XenForo_Model_Forum')
				->getForumByThreadId($threadId);
		self::$purgeTags[] = self::CACHETAG_THREAD . $threadId;
		if ($forum['node_id'] != 0) {
			self::$purgeTags[] = self::CACHETAG_FORUM . $forum['node_id'];
		}
	}

	/**
	 * Helper function for purging by forum id.
	 *
	 * @param type $controller
	 * @param integer $forumId Optional. If not provided, will attempt to get it
	 * from input.
	 */
	private static function purgeByForumId($controller, $forumId = -1)
	{
		if ($forumId == -1) {
			$forumId = $controller->getInput()->filterSingle('node_id',
					XenForo_Input::UINT);
		}
		if ($forumId != 0) {
			self::$purgeTags[] = self::CACHETAG_FORUM . $forumId;
		}
	}

	/**
	 * Check if the moderation queue made any changes that require a purge.
	 * Specifically, if a thread or post was approved.
	 *
	 * @param XenForo_Controller $controller
	 */
	private static function checkModQueue(XenForo_Controller $controller)
	{
		$mod_queue = $controller->getInput()->filterSingle('queue',
				XenForo_Input::ARRAY_SIMPLE);
		if (empty($mod_queue)) {
			return;
		}

		if (!empty($mod_queue['thread'])) {
			$threads = $mod_queue['thread'];
			foreach($threads as $threadId => $thread) {
				if (strncmp($thread['action'], 'approve', 7) == 0) {
					self::purgeByThreadId($controller, $threadId);
				}
			}
		}

		if (empty($mod_queue['post'])) {
			return;
		}
		$posts = $mod_queue['post'];
		foreach($posts as $postId => $post) {
			if (strncmp($post['action'], 'approve', 7) == 0) {
				self::purgeByPostId($controller, $postId);
			}
		}
	}

	/**
	 * Test the controllerResponse class to verify that login was successful.
	 *
	 * @param type $resp
	 * @return boolean True if login was successful, false otherwise.
	 */
	private static function isLoginSuccess($resp)
	{
		$postTemplate = 'login_post_redirect';
		return ((($resp instanceof XenForo_ControllerResponse_View)
					&& ($resp->templateName == $postTemplate))
				|| ($resp instanceof XenForo_ControllerResponse_Redirect));
	}

	/**
	 * Controller Post Dispatch Event Listener.
	 * This will listen for the login controller + action and
	 * any purge controller + action.
	 * If login is successful, it will set the vary cookie so that the logged
	 * in user will be able to see uncached pages.
	 * If any action involving changing a forum or thread takes place, purge
	 * the thread/forum/forum list.
	 *
	 * @param XenForo_Controller $controller
	 * @param type $controllerResponse
	 * @param type $controllerName
	 * @param type $action
	 */
	public static function checkPostDispatch(XenForo_Controller $controller,
			$controllerResponse, $controllerName, $action)
	{
		$prefix = 'XenForo_Controller';
		$prefixlen = strlen($prefix);
		$actionStart = array(
			'A', // AddThread, AddReply, Approve
			'D', // Delete
			'L', // Login
			'S', // Save, SaveInline
		);
		if ((strncmp($controllerName, $prefix, $prefixlen) != 0)
				|| (!in_array($action[0], $actionStart))) {
			return;
		}

		$noPrefix = substr($controllerName, $prefixlen);
		switch ($noPrefix[0]) {
			case 'A':
				if (($noPrefix != 'Admin_Forum')
					|| (($action != 'Save') && ($action != 'Delete'))) {
					return;
				}
				self::purgeByForumId($controller);
				self::$purgeTags[] = self::CACHETAG_FORUMLIST;
				return;
			case 'P':
				if (strncmp($noPrefix, 'Public_', 7)) {
					return;
				}
				$noPrefix = substr($noPrefix, 7);
				break;
			default:
				return;
		}

		switch ($noPrefix) {
			case 'Login':
				if (($action != 'Login')
						|| (!self::isLoginSuccess($controllerResponse))) {
					break;
				}
				self::setUserState(self::STATE_LOGGEDIN);
				if ($controller->getInput()->filterSingle('remember',
						XenForo_Input::UINT)) {
					self::setUserState(self::STATE_STAYLOGGEDIN);
				}
				break;
			case 'Thread':
				if (($action != 'AddReply')
					|| (!($controllerResponse instanceof XenForo_ControllerResponse_View))) {
					break;
				}
				// If it is a thread add reply, need to check the replies.
				// If any are not moderated, purge the cache.
				foreach ($controllerResponse->params['posts'] as $post) {
					if (!$post['isModerated']) {
						self::purgeByThreadId($controller);
						break;
					}
				}
				break;
			case 'Forum':
				if ($action == 'AddThread') {
					// TODO: Check if added thread is moderated?
					// Currently no easy fix.
					self::purgeByForumId($controller);
				}
				break;
			case 'Post':
				// If edit post or delete post, purge cache.
				if (($action == 'SaveInline') || ($action == 'Delete')) {
					self::purgeByPostId($controller);
				}
				break;
			case 'ModerationQueue':
				// If saving something in moderation queue, check it.
				if ($action == 'Save') {
					self::checkModQueue($controller);
				}
				break;
			case 'InlineMod_Post':
				if ($action == 'Approve') {
					$posts = $controller->getInput()->filterSingle('posts',
						XenForo_Input::ARRAY_SIMPLE);
					foreach ($posts as $postId) {
						self::purgeByPostId($controller, $postId);
					}
				}
				break;
			case 'InlineMod_Thread':
				if ($action == 'Approve') {
					$threads = $controller->getInput()->filterSingle('threads',
						XenForo_Input::ARRAY_SIMPLE);
					foreach ($threads as $threadId) {
						self::purgeByThreadId($controller, $threadId);
					}
				}
				break;
			case 'Misc':
				if (($action == 'Style') || ($action == 'Language')) {
					self::setNotCacheable('logged out user changing style or language.');
				}
				break;
			case 'Register':
				if (($action == 'Facebook') || ($action == 'Google') || ($action == 'Twitter')) {
					self::setNotCacheable('External login (e.g. Facebook).');
				}
				break;
			default:
				break;
		}
	}
}


