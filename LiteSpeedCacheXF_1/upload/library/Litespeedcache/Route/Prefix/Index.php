<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Litespeedcache_Route_Prefix_Index implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
				{
		return $router->getRouteMatch('Litespeedcache_ControllerAdmin_Index', $routePath, 'litespeedcache-purge');
	}
}