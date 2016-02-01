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

	/**
	 * @xfcp: XenForo_Model_User
	 *
	 */
	public static function extendUserModel( $class, &$extend )
	{
		if (Litespeedcache_Listener_Global::lscache_enabled()) {
			$extend[] = 'Litespeedcache_Extend_XenForo_Model_User' ;
		}
	}

	public static function lscache_enabled()
	{
        return (isset($_SERVER['X-LSCACHE']) && $_SERVER['X-LSCACHE']);
	}

	public static function frontControllerPostView( XenForo_FrontController $fc, &$output )
	{
		if (!Litespeedcache_Listener_Global::lscache_enabled())
			return;

		$response = $fc->getResponse() ;
		$cacheable = true ;
		$uri = $fc->getRequest()->getRequestUri();
		if (strpos($uri, '/admin.php') !== FALSE) {
			$cacheable = false ;
		}
		else if ( XenForo_Visitor::getUserId() ) {
			$cacheable = false ;
		}

		if ( $cacheable ) {
			$maxage = XenForo_Application::getOptions()->litespeedcacheXF_publicttl ;
			$cache_header = 'public,max-age=' . $maxage ;
			$response->setHeader('X-LiteSpeed-Cache-Control', $cache_header) ;
		}
		else {
			$response->setHeader('X-LiteSpeed-Cache-Control', 'no-cache') ;
			//error_log('Cache set to NO');
		}
	}

}
