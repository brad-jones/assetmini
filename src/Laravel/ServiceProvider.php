<?php
////////////////////////////////////////////////////////////////////////////////
//            _____                        __     _____   __        __          
//           /  _  \   ______ ______ _____/  |_  /     \ |__| ____ |__|         
//          /  /_\  \ /  ___//  ___// __ \   __\/  \ /  \|  |/    \|  |         
//         /    |    \\___ \ \___ \\  ___/|  | /    Y    \  |   |  \  |         
//         \____|__  /____  >____  >\___  >__| \____|__  /__|___|  /__|         
//                 \/     \/     \/     \/             \/        \/             
// -----------------------------------------------------------------------------
//          Designed and Developed by Brad Jones <brad @="bjc.id.au" />         
// -----------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

namespace Gears\AssetMini\Laravel;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
	protected $defer = false;
	
	public function register()
	{
		$this->app->singleton('asset', function($app)
		{
			\Gears\AssetMini\HtmlHelper::$debug = $app['config']->get('*::app.debug');
			return new \Gears\AssetMini\HtmlHelper();
		});
	}
}