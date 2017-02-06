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

class Litespeedcache_Extend_XenForo_Model_User
extends XFCP_Litespeedcache_Extend_XenForo_Model_User
{

	public function validateAuthentication( $nameOrEmail, $password, &$error = '' )
	{
		$parentReturn = parent::validateAuthentication($nameOrEmail, $password, $error) ;
		if ( $parentReturn ) {
			Litespeedcache_Listener_Global::setUserState(Litespeedcache_Listener_Global::STATE_LOGGEDIN) ;
		}
		return $parentReturn ;
	}

	/**
	 * Sets the user remember cookie for the specified user ID.
	 *
	 * @param integer $userId
	 * @param array|false|null $auth User's auth record (retrieved if null)
	 *
	 * @return boolean
	 */
	public function setUserRememberCookie($userId, $auth = null)
	{
		$parentReturn = parent::setUserRememberCookie($userId, $auth);
		if ($parentReturn) {
			// use same length as parent
			Litespeedcache_Listener_Global::setUserState(Litespeedcache_Listener_Global::STATE_STAYLOGGEDIN) ;
		}
		return $parentReturn ;
	}

}
