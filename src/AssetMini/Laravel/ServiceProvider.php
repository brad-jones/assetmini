<?php namespace Gears\AssetMini\Laravel;
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

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
	protected $defer = false;
	
	public function register()
	{
		$this->app->singleton('asset', function($app)
		{
			\Gears\AssetMini::setDebug($app['config']->get('*::app.debug'));
			return new \Gears\AssetMini();
		});
	}
}