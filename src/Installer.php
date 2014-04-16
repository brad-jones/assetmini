<?php namespace Gears\AssetMini;

use Composer\Script\Event;

class Installer
{
	public static function post_install_cmd(Event $event)
	{
		echo '...INSTALLING...';
	}
}