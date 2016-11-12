<?php
spl_autoload_register(function ($className) {
	static $classMap = [
		'PhpPivot\\Pivot' => 'Pivot.php',
	];
	if (isset($classMap[$className])) {
		require __DIR__ . '/PhpPivot/' . $classMap[$className];
	}
}); 

