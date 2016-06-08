<?php

/**
 * Route prefix handler for LiteSpeed Cache in the admin control panel.
 *
 */
class Litespeedcache_Route_PrefixAdmin_Rewrite
                implements XenForo_Route_Interface
{
        /**
         * Match the lscacherewrite prefix to the Litespeedcache Controller
         * Admin class.
         *
         * @see XenForo_Route_Interface::match()
         */
        public function match($routePath, Zend_Controller_Request_Http $request,
                        XenForo_Router $router)
        {
                return $router->getRouteMatch('Litespeedcache_ControllerAdmin_Rewrite',
                                $routePath, 'lscacherewrite');
        }
}



