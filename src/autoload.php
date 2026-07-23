<?php

spl_autoload_register(static function ($class) {
	$config = json_decode(file_get_contents(__DIR__ . '/../config.json'), true);
	$namespace = $config['namespace'] ?? '';
	$base_dir = __DIR__ . '/';
	$class = str_replace($namespace . '\\', '', $class);
	$file = $base_dir . str_replace('\\', '/', $class) . '.php';
	if (file_exists($file)) {
		require $file;
	}
});
