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

class Litespeedcache_Options
{

//Validates admin settings

	/**
	 * Verify if the server and cache are enabled.
	 *
	 * @return mixed Nothing if enabled, error html if failed.
	 */
	public static function verifyLSCache()
	{
		$link = 'https://www.litespeedtech.com/solutions/other-web-application-acceleration/lscxf';
		if ( Litespeedcache_Listener_Global::lscache_enabled() ) {
			return;
		}
		return '<div class="alertText">Notice: LiteSpeed Web Server with '
		. 'LSCache module was not detected. This plugin will NOT work.</div>'
		. '<small>For more information visit our <a href="' . $link . '">'
		. 'LSCache for XenForo page</a>.</small><br /><br />';
	}

	/**
	 * Verify that the Public TTL option is a valid setting.
	 *
	 * @param type $publicttl
	 * @param XenForo_DataWriter $dw
	 * @param type $fieldname
	 * @return boolean true if verified, false otherwise.
	 */
	public static function verifyPublicTTL( $publicttl, XenForo_DataWriter $dw,
			$fieldname )
	{
		if (($publicttl == '') || (intval($publicttl) != $publicttl)
				|| ($publicttl < 60)) {
			$dw->error(new XenForo_Phrase('Public TTL must be set to an '
					. 'integer value of 60 seconds or greater.'), $fieldname);
			return false;
		}
		return true;
	}

	/**
	 * Verify that the Home TTL option is a valid setting.
	 *
	 * @param type $homettl
	 * @param XenForo_DataWriter $dw
	 * @param type $fieldname
	 * @return boolean true if verified, false otherwise.
	 */
	public static function verifyHomeTTL( $homettl, XenForo_DataWriter $dw,
			$fieldname )
	{
		if (($homettl == '') || (intval($homettl) != $homettl)
				|| ($homettl < 60)) {
			$dw->error(new XenForo_Phrase('Home TTL must be set to an integer '
					. 'value of 60 seconds or greater.'), $fieldname);
			return false;
		}
		return true;
	}

	/**
	 * Verify that the Login Cookie option is a valid setting.
	 *
	 * Will compare against setcookie list of valid characters.
	 *
	 * @param type $loginCookie
	 * @param XenForo_DataWriter $dw
	 * @param type $fieldname
	 * @return boolean true if verified, false otherwise.
	 */
	public static function verifyLoginCookie($loginCookie,
		XenForo_DataWriter $dw, $fieldname)
	{
		$invalidChars = "=,; \t\r\n\013\014";
		if (strcspn($loginCookie, $invalidChars) != strlen($loginCookie)) {
			$dw->error(new XenForo_Phrase('The login cookie contained '
					. 'invalid characters.'), $fieldname);
			return false;
		}
		Litespeedcache_Listener_Global::changedLoginCookie();
		return true;
	}


	/**
	 * Verify that the Cache Prefix option is a valid setting.
	 *
	 * Will compare against setcookie list of valid characters.
	 *
	 * @param type $prefix
	 * @param XenForo_DataWriter $dw
	 * @param type $fieldname
	 * @return boolean true if verified, false otherwise.
	 */
	public static function verifyCachePrefix($prefix,
		XenForo_DataWriter $dw, $fieldname)
	{
		if (($prefix !== '') && (!ctype_alnum($prefix))) {
			$dw->error(new XenForo_Phrase('The cache prefix contained '
					. 'invalid characters.'), $fieldname);
		}
		Litespeedcache_Listener_Global::addPurgeTag('*');
		return true;
	}

	/**
	 * Verify that the Separate Cache Entry for Mobile option is
	 * a valid setting.
	 *
	 * @param type $separateMobile
	 * @param XenForo_DataWriter $dw
	 * @param type $fieldname
	 * @return boolean
	 */
	public static function verifySeparateMobile( $separateMobile,
			XenForo_DataWriter $dw, $fieldname )
	{
		if (($separateMobile != 0) && ($separateMobile != 1)) {
			$dw->error(new XenForo_Phrase('Separate Mobile setting is an'
					. ' impossible value.'), $fieldname);
			return false;
		}
		return true;
	}


	/**
	 * Verify that the Do Not Cache URI option is a valid setting.
	 *
	 * Will verify against a small regex.
	 *
	 * @param type $input
	 * @param XenForo_DataWriter $dw
	 * @param type $fieldname
	 * @return mixed string if verified and not empty,
	 * true if verified and empty, false otherwise.
	 */
	public static function verifyDoNotCacheUri($input,
		XenForo_DataWriter $dw, $fieldname)
	{
		$trimmed = trim($input);
		if (empty($trimmed)) {
			return true;
		}
		$list = explode("\n", $input);
		if (empty($list)) {
			return true;
		}
		foreach ($list as $key=>$val) {
			if (preg_match('!^/.*\$?$!', $val) == 0) {
				$dw->error(new XenForo_Phrase('Invalid URI: ' . $val),
					$fieldname);
				return false;
			}
			$list[$key] = trim($val);
		}

		Litespeedcache_Listener_Global::addPurgeTag('*');
		return implode("\n", $list);
	}

}
