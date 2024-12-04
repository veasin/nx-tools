<?php
//  .\vendor\bin\nx setup --dir=../../test
$src = "{$GLOBALS['_project']}/src";
$options = [];
title("make src/setup.php");
if(!file_exists("$src/app.php")) die("\nno founded src/app.php\n.");
[$ns, $app, $traits] = parseClasses("$src/app.php");
if(!$app[0]) die("\nno founded app.\n");
echo "file: ", "$src/setup.php", "\n";
echo "app: ", $app[0], "\n";
echo "traits: ", implode(", ", $traits), "\n";
if('yes' === choice("are u sure to make setup.php?", ['y' => 'yes', 'n' => 'no', '' => 'yes'])){
	$ts = array_map(fn($t) => str_replace(["\\nx\\parts\\", "\\"], ['', "/"], $t), $traits);
	$setup = [];
	foreach($ts as $t){
		switch($t){
			case 'db/pdo':
				$setup[$t] = "
	'db/pdo' =>[
		'default' => [
			'dsn'=>'mysql:dbname=db_name;host=localhost;charset=utf8mb4',
			'username'=>'root',
			'password'=>'',
			'options'=>[],
		],
	],";
				break;
			case 'router/uri':
				$setup[$t] = "
	'router/uri' => [
		'uri' => \$_SERVER['PATH_INFO'] ?? \$_SERVER['REQUEST_URI'] ?? '',
		'method' => strtolower(\$_SERVER['REQUEST_METHOD'] ?? ''),
		'actions' => [],
		'rules' =>[],
	],";
				break;
			case 'log/ws':
				$setup[$t] = "
	'log/ws' => [
		'uri'=>'http://localhost:10010/log',
	],";
				break;
			case 'cache/redis':
				$setup[$t] = "
	'cache/redis' => [
		'default'=>[
			'host'=>'localhost',
			'port'=>'6379',
			'auth'=>'',
			'select'=>0,
		],
	],";
				break;
		}
	}
	$setup = implode("", $setup);
	$ok = put_php_file("$src/setup.php", "return [$setup\n];");
	echo "\nwrite src/setup.php ", $ok ? "done." : "fail.", "\n";
}
