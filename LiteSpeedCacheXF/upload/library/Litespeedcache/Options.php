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

	public static function verifyTTL( $publicttl, XenForo_DataWriter $dw, $fieldname )
	{
		if ( $publicttl == '' || intval($publicttl) != $publicttl || $publicttl < 60 ) {
			$dw->error(new XenForo_Phrase('Public TTL must be set to a numeric value of  60 seconds or greater.'), $fieldname) ;
			return false ;
		}
		return true ;
	}

}
