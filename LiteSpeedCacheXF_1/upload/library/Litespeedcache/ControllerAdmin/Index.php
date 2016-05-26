<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Litespeedcache_ControllerAdmin_Index extends XenForo_ControllerAdmin_Abstract
{
public function actionIndex()
		{
			return $this->responseView('Litespeedcache_ViewAdmin_Index', 'litespeedcache_view');
		}
}