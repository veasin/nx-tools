<?php
// .\vendor\bin\nx app --ns=com -o --dir=../../test
$src = "{$GLOBALS['_project']}/src";
$Params = $GLOBALS['_script_params'];
$options = [];
title("make src/app.php");
if(file_exists($src . "/app.php")){
	$r = choice("file: src/app.php already exists, overwrite?", ['y' => 'yes', 'n' => 'no', '' => 'no']);
	//$r=choice("file: src/app.php already exists, overwrite?");
	if('no' === $r) die("\nexit .");
	$options['overwrite'] = true;
}
$options['namespace'] = $Params['ns'] ?? ask("app's namespace", 'com');
$options['use'] = choice("select the trait you want to use? multiple.", [
	"log-ws",
	"log-file",
	"log-cli",
	"runtime",
	"path",
	"control-ca",
	"control-main",
	"router-uri",
	//'router-annotation',
	"db-pdo",
	"filter-from",
	"output-http",
	"output-rest",
	"output-cli",
	"cache-redis",
	"cache-hash",
	"model",
	"model-cache",
	"controller-model",
	"network-context",
], ["log-ws", "runtime", "control-ca", "router-uri", "db-pdo", "filter-from", "output-rest", "controller-model"]);

$requires =[
	'log-ws'=>"veasin/nx-log-ws:>=0.0.2",
	'log-file'=>"veasin/nx-log:>=0.0.4",
	'log-cli'=>"veasin/nx-log-cli:>=0.0.3",
	//'router-annotation'=>"veasin/nx-router-annotation:>=0.0.5",
	'db-pdo'=>"veasin/nx-db-pdo:>=0.0.9",
	'filter-from'=>"veasin/nx-filter-from:>=0.0.5",
	'cache-redis'=>"veasin/nx-cache-redis:>=0.0.10",
	'cache-hash'=>"veasin/nx-cache-redis:>=0.0.9",
	'model'=>"veasin/nx-model:>=0.0.10",
	'controller-model'=>"veasin/nx-controller-model:>=0.0.7",
	'network-context'=>"veasin/nx-network-context:>=0.0.6",
];
$uses =[
	"log-ws",
	"log-file",
	"log-cli",
	"runtime",
	"path",
	"control-ca",
	"control-main",
	"router-uri",
	"db-pdo",
	"filter-from",
	"output-http",
	"output-rest",
	"output-cli",
	"cache-redis",
	"cache-hash",
];
echo "file: ", $src, '/app.php', "\n";
echo "namespace: ", $options['namespace'], "\n";
echo "use: ", implode(",", $options['use']), "\n";
if('yes' === choice("are u sure to make app.php?", ['y' => 'yes', 'n' => 'no', '' => 'yes'])){
	if(count($options['use'])){
		$use =[];
		foreach($options['use'] as $u){
			if(in_array($u, $uses)) $use[] =str_replace("-", "\\", "\\nx-parts-$u");
		}
		$use = "\tuse " . implode(", ", $use) . ";";
	}
	else $use = "";
	if(in_array("path", $options['use'])){
		$path = "\n\tpublic string \$path=__DIR__;";
	}
	else $path = "";
	if(!file_exists($src)){
		mkdir($src, 0755, true);
	}
	$ok =put_php_file("$src/app.php", "namespace {$options['namespace']};\nclass app extends \\nx\\app{\n$use$path\n}");
	echo "\nwrite src/app.php ", $ok?"done.":"fail.", "\n";

	$require =["veasin/nx"=>">=1.3.1"];
	foreach($options['use'] as $use){
		if(isset($requires[$use])) {
			[$package, $version] = explode(':', $requires[$use]);
			$require[$package]=$version;
		}
	}
	updateComposer([
		//'name'=>$options['namespace'],
		//'description'=>"",
		//'author'=>"",
		//'keywords'=>[],
		'type'=>'project',
		'require' => $require,
		'require-dev'=>[
			'veasin/nx-tools'=>'>=0.0.11',
		],
		'autoload' => [
			'psr-4' => [
				"{$options['namespace']}\\" => "src/",
			]
		],
		'config'=>[
			'process-timeout'=>0,
		],
		'scripts' => [
			'dev'=>"@php -S localhost:8080 web/index.php",
			'annotate'=> 'nx annotate',
			'uml'=> 'nx uml',
		]
	]);

	if('yes'===choice('do you want to make src/setup.php?', ['y' => 'yes', 'n' => 'no', '' => 'yes'])){
		include __DIR__."/setup.php";
	}
	if('yes'===choice('do you want to make web/index.php?', ['y' => 'yes', 'n' => 'no', '' => 'yes'])){
		include __DIR__."/index.php";
	}

}
