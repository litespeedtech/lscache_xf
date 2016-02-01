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

class Litespeedcache_Extend_XenForo_Model_User extends
XFCP_Litespeedcache_Extend_XenForo_Model_User
{

	public function validateAuthentication( $nameOrEmail, $password, &$error = '' )
	{
		$parentReturn = parent::validateAuthentication($nameOrEmail, $password, $error) ;
		if ( $parentReturn ) {
			// has to call php function directly to avoid xf prefix

			$secure = XenForo_Application::$secure;
			$httpOnly = true;

			$cookieConfig = XenForo_Application::get('config')->cookie;
			$path = $cookieConfig->path;
			$domain = $cookieConfig->domain;
			$name = '_lscache_vary';

			setcookie($name, 1, 0, $path, $domain, $secure, $httpOnly);
		}
		return $parentReturn ;
	}

}
