<?php
// .\vendor\bin\nx app --ns=com -o --dir=../../test
$src = "{$GLOBALS['_project']}/src";
$web = "{$GLOBALS['_project']}/web";
$options = [];
title("make web/index.php");
if(!file_exists($src . "/app.php")) die("\nno founded src/app.php\n.");
[$ns, $app, $traits] = parseClasses("$src/app.php");
if(!$app[0]) die("\nno founded app.\n");

echo "file: ", $web, '/index.php', "\n";
echo "app: ", $app[0], "\n";

if('yes' === choice("are u sure to make index.php?", ['y' => 'yes', 'n' => 'no', '' => 'yes'])){
	if(!file_exists($web)){
		mkdir($web, 0755, true);
	}
	$ok =put_php_file("$web/index.php", "const AGREE_LICENSE=true;

include __DIR__.'/../vendor/autoload.php';
\$setup=include __DIR__.'/../src/setup.php';
\$setup['router/uri']['rules']=include __DIR__.'/../src/route.php';

(new \\$app[0](\$setup))->run();
");
	echo "\nwrite web/index.php ", $ok?"done.":"fail.", "\n";
}
