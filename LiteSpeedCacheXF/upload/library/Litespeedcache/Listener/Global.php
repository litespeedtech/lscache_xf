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

	/**
	 * @xfcp: XenForo_Model_User
	 *
	 */
	public static function extendUserModel( $class, &$extend )
	{
		if ( Litespeedcache_Listener_Global::lscache_enabled() ) {
			$extend[] = 'Litespeedcache_Extend_XenForo_Model_User' ;
		}
	}

	public static function lscache_enabled()
	{
		return (isset($_SERVER['X-LSCACHE']) && $_SERVER['X-LSCACHE']) ;
	}

	public static function setUserState( $value )
	{
		self::$userState |= $value ;
	}

	public static function addPurgeTag($tag)
	{
		if (is_array($tag)) {
			self::$purgeTags = array_merge(self::$purgeTags, $tag);
		}
		else {
			self::$purgeTags[] = $tag;
		}
	}

	public static function addCacheTag($tag)
	{
		if (is_array($tag)) {
			self::$cacheTags = array_merge(self::$cacheTags, $tag);
		}
		else {
			self::$cacheTags[] = $tag;
		}
	}

	private static function setCacheVaryCookie( $value )
	{
		// has to call php function directly to avoid xf prefix

		$secure = XenForo_Application::$secure ;
		$httpOnly = true ;

		$cookieConfig = XenForo_Application::get('config')->cookie ;
		$path = $cookieConfig->path ;
		$domain = $cookieConfig->domain ;
		$expiration = 0 ;
		$cookieValue = 1 ;

		if ( $value === false ) {
			$expiration = XenForo_Application::$time - 86400 * 365 ;
			$cookieValue = 0 ;
		}
		else if ( $value === true ) {
			$expiration = 0 ; // default only for current session
		}
		else {
			// stay logged in, same as xf_usr
			$expiration = XenForo_Application::$time + $value ;
		}
		setcookie(self::$currentVary, $cookieValue, $expiration, $path, $domain, $secure, $httpOnly) ;
	}

	public static function frontControllerPostView( XenForo_FrontController $fc, &$output )
	{
		if ( ! Litespeedcache_Listener_Global::lscache_enabled() )
			return ;

		$response = $fc->getResponse() ;
		$cacheable = true ;
		$uri = $fc->getRequest()->getRequestUri() ;


		if ( XenForo_Visitor::getUserId() || (strpos($uri, '/admin.php') !== false) || XenForo_Helper_Cookie::getCookie('user') ) {
			$cacheable = false ;
		}

		if ( isset($_SERVER[self::COOKIE_LSCACHE_VARY_NAME])) {
			self::$currentVary = $_SERVER[self::COOKIE_LSCACHE_VARY_NAME];
		}
		else {
			self::$currentVary = self::COOKIE_LSCACHE_VARY_DEFAULT;
		}

		if ( $cacheable ) {
			if ( isset($_COOKIE[self::$currentVary]) ) {
				self::setCacheVaryCookie(false) ;
			}
			$options = XenForo_Application::getOptions();
			if (!empty(self::$cacheTags)) {
				$tags = array_unique(self::$cacheTags);
				$response->setHeader(self::HEADER_CACHE_TAG,
						implode(',', $tags));
			}
			if ((isset($tags)) && (in_array(self::CACHETAG_FORUMLIST, $tags))) {
				$maxage = $options->litespeedcacheXF_homettl;
			}
			else {
				$maxage = $options->litespeedcacheXF_publicttl;
			}
			$cache_header = 'public,max-age=' . $maxage;
			$response->setHeader('X-LiteSpeed-Cache-Control', $cache_header);
		}
		else {
			if ( (self::$userState & self::STATE_STAYLOGGEDIN) == self::STATE_STAYLOGGEDIN ) {
				self::setCacheVaryCookie(30 * 86400) ;
			}
			elseif ( (self::$userState & self::STATE_LOGGEDIN) == self::STATE_LOGGEDIN ) {
				self::setCacheVaryCookie(true) ;
			}
			$response->setHeader('X-LiteSpeed-Cache-Control', 'no-cache') ;
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
		$response->setHeader('LSC-Cookie', 'lsc_active=1') ;
	}

	public static function checkForCacheTags($hookName, &$contents,
			array $hookParams, XenForo_Template_Abstract $template)
	{
		if ($hookName[0] != 'f' && $hookName[0] != 't') {
			return;
		}
		if (strcmp($hookName, 'forum_view_threads_before') == 0) {
			$forum = $hookParams['forum'];
			self::$cacheTags[] = self::CACHETAG_FORUM . $forum['node_id'];
		}
		elseif (strcmp($hookName, 'thread_view_form_before') == 0) {
			$thread = $hookParams['thread'];
			self::$cacheTags[] = self::CACHETAG_THREAD . $thread['thread_id'];
		}
		elseif (strcmp($hookName, 'forum_list_nodes') == 0) {
			self::$cacheTags[] = self::CACHETAG_FORUMLIST;
		}
	}

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
					$forum = XenForo_Model::create('XenForo_Model_Forum')
							->getForumByThreadId($threadId);
					self::$purgeTags[] = self::CACHETAG_THREAD . $threadId;
					self::$purgeTags[] = self::CACHETAG_FORUM
							. $forum['node_id'];
				}
			}
		}

		if (empty($mod_queue['post'])) {
			return;
		}
		$posts = $mod_queue['post'];
		foreach($posts as $postId => $post) {
			if (strncmp($post['action'], 'approve', 7) == 0) {
				$postModel = XenForo_Model::create('XenForo_Model_Post')
					->getPostById($postId);
				$threadId = $postModel['thread_id'];
				$forum = XenForo_Model::create('XenForo_Model_Forum')
						->getForumByThreadId($threadId);
				self::$purgeTags[] = self::CACHETAG_THREAD . $threadId;
				self::$purgeTags[] = self::CACHETAG_FORUM
						. $forum['node_id'];
			}
		}
	}

	public static function checkForPurgeTags(XenForo_Controller $controller,
			$action, $controllerName)
	{
		$prefix = 'XenForo_Controller';
		$prefixlen = strlen($prefix);
		$forumId = NULL;
		$threadId = NULL;
		if ((strncmp($controllerName, $prefix, $prefixlen) != 0)
				|| (($action[0] != 'A') && ($action[0] != 'S'))) {
			return;
		}

		$noPrefix = substr($controllerName, $prefixlen);
		switch ($noPrefix[0]) {
			case 'A':
				if ((strcmp($noPrefix, 'Admin_Forum') != 0)
					|| ((strcmp($action, 'Save') != 0)
						&& (strcmp($action, 'Delete') != 0))) {
					return;
				}
				$forumId = $controller->getInput()->filterSingle('node_id',
						XenForo_Input::UINT);
				self::$purgeTags[] = self::CACHETAG_FORUMLIST;
				if ($forumId != 0) {
					self::$purgeTags[] = self::CACHETAG_FORUM . $forumId;
				}
				return;
			case 'P':
				break;
			default:
				return;
		}

		if ((strcmp($noPrefix, 'Public_ModerationQueue') == 0)
				&& (strcmp($action, 'Save') == 0)) {
			self::checkModQueue($controller);
			return;
		}
		elseif ((strcmp($noPrefix, 'Public_Forum') == 0)
				&& (strcmp($action, 'AddThread') == 0)) {
			$forumId = $controller->getInput()->filterSingle('node_id',
					XenForo_Input::UINT);
		}
		elseif ((strcmp($noPrefix, 'Public_Thread') == 0)
				&& (strcmp($action, 'AddReply') == 0)) {
			$threadId = $controller->getInput()->filterSingle('thread_id',
					XenForo_Input::UINT);
			$forum = XenForo_Model::create('XenForo_Model_Forum')
					->getForumByThreadId($threadId);
			$forumId = $forum['node_id'];
		}
		elseif ((strcmp($noPrefix, 'Admin_Forum') == 0)
				&& ((strcmp($action, 'Save') == 0)
					|| (strcmp($action, 'Delete') == 0))) {
			$forumId = $controller->getInput()->filterSingle('node_id',
					XenForo_Input::UINT);
		}

		if (!is_null($forumId)) {
			self::$purgeTags[] = self::CACHETAG_FORUM . $forumId;
		}
		if (!is_null($threadId)) {
			self::$purgeTags[] = self::CACHETAG_THREAD . $threadId;
		}
	}
}
