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

/**
 * Not sure if this is considered a big no no or not, I am still sitting on the
 * fence about it. But basically all we are doing is checking to see if
 * the class AssetMini doesn't already exist globally and that we are not being
 * run inisde of a Laravel App (as we have the laravel service provider and
 * facade to this job).
 * 
 * If both of those conditions are met then we alias the
 * HtmlHelper class so that it makes things nice and concise.
 * 
 * So instead of doing this:
 * 
 *     use Gears\AssetMini\HtmlHelper as AssetMini;
 * 
 * You just get AssetMini automatically.
 */

if (!class_exists('\AssetMini') && !defined('LARAVEL_START'))
{
	class_alias('\Gears\AssetMini\HtmlHelper', '\AssetMini');
}