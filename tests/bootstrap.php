<?php
declare(strict_types=1);

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer install`';
	exit(1);
}

\Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

// temporary directory
define('TempDir', __DIR__ . '/tmp/' . getmypid() . '-' . uniqid());
@mkdir(TempDir, recursive: true);

register_shutdown_function(function (): void{
	@\Tester\Helpers::purge(TempDir);
	@rmdir(TempDir);

	$tempRoot = dirname(TempDir);
	if(is_dir($tempRoot) && count(@scandir($tempRoot) ?: []) === 2){
		@rmdir($tempRoot);
	}
});
