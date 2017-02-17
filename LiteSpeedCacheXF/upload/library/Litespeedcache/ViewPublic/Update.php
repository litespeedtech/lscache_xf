<?php

class Litespeedcache_ViewPublic_Update extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		return json_encode($this->_params);
	}
}


