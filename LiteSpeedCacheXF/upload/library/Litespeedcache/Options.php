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

}
