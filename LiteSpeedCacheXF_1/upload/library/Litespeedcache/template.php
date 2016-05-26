<?php

class Litespeedcache_template
{

	public static function getPurge()
	{

		echo '<div>
		<h2 style="font-size: 20px;">Purging ...</h2>
		</div><br />' ;

		error_log('reached call') ;

		self::purge() ;

		error_log('Purged Cache') ;
		echo '<div>
			<h3>LSCache Has Been Purged</h3>
		        </div>' ;
	}

	public static function Purge( )
	{
		$fc = XenForo_Application::getFc();
		$response = $fc->getResponse() ;
		$response->setHeader('X-LiteSpeed-Purge','*') ;
	}

}
