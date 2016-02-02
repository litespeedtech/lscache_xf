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

	const cookieName = '_lscache_vary' ; // fixed name, cannot change

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

	public static function setCacheVaryCookie( $value )
	{
		// has to call php function directly to avoid xf prefix

		$secure = XenForo_Application::$secure ;
		$httpOnly = true ;

		$cookieConfig = XenForo_Application::get('config')->cookie ;
		$path = $cookieConfig->path ;
		$domain = $cookieConfig->domain ;
		$expiration = 0 ;

		if ( $value === false ) {
			$expiration = XenForo_Application::$time - 86400 * 365 ;
		}

		setcookie(self::cookieName, $value, $expiration, $path, $domain, $secure, $httpOnly) ;
	}

	public static function frontControllerPostView( XenForo_FrontController $fc, &$output )
	{
		if ( ! Litespeedcache_Listener_Global::lscache_enabled() )
			return ;

		$response = $fc->getResponse() ;
		$cacheable = true ;
		$uri = $fc->getRequest()->getRequestUri() ;

		if ( XenForo_Helper_Cookie::getCookie('user') ) {
			self::setCacheVaryCookie(true) ;
			$cacheable = false ;
		}
		if ( strpos($uri, '/admin.php') !== false ) {
			$cacheable = false ;
		}
		else if ( XenForo_Visitor::getUserId() ) {
			$cacheable = false ;
		}

		if ( $cacheable ) {
			if ( isset($_COOKIE[self::cookieName]) ) {
				self::setCacheVaryCookie(false) ;
			}
			$maxage = XenForo_Application::getOptions()->litespeedcacheXF_publicttl ;
			$cache_header = 'public,max-age=' . $maxage ;
			$response->setHeader('X-LiteSpeed-Cache-Control', $cache_header) ;
		}
		else {
			$response->setHeader('X-LiteSpeed-Cache-Control', 'no-cache') ;
		}
	}

}
