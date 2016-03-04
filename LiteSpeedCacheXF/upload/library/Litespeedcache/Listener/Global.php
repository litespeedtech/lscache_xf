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

	const COOKIE_LSCACHE_VARY = '_lscache_vary' ; // fixed name, cannot change
	const STATE_LOGGEDIN = 1 ;
	const STATE_STAYLOGGEDIN = 2 ;

	private static $_userState = 0 ;

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
		self::$_userState |= $value ;
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
		setcookie(self::COOKIE_LSCACHE_VARY, $cookieValue, $expiration, $path, $domain, $secure, $httpOnly) ;
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

		if ( $cacheable ) {
			if ( isset($_COOKIE[self::COOKIE_LSCACHE_VARY]) ) {
				self::setCacheVaryCookie(false) ;
			}
			$maxage = XenForo_Application::getOptions()->litespeedcacheXF_publicttl ;
			$cache_header = 'public,max-age=' . $maxage ;
			$response->setHeader('X-LiteSpeed-Cache-Control', $cache_header) ;
		}
		else {
			if ( (self::$_userState & self::STATE_STAYLOGGEDIN) == self::STATE_STAYLOGGEDIN ) {
				self::setCacheVaryCookie(30 * 86400) ;
			}
			elseif ( (self::$_userState & self::STATE_LOGGEDIN) == self::STATE_LOGGEDIN ) {
				self::setCacheVaryCookie(true) ;
			}
			$response->setHeader('X-LiteSpeed-Cache-Control', 'no-cache') ;
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

}
