<?php
// .\vendor\bin\nx app --ns=com -o --dir=../../test
$src = "{$GLOBALS['_project']}/src";
$web = "{$GLOBALS['_project']}/web";
$vendor = "{$GLOBALS['_project']}/vendor";
$options = [];
title("parse annotate");
if(!file_exists($src . "/app.php")) die("\nno founded src/app.php\n.");
[$ns, $app, $traits] = parseClasses("$src/app.php");
if(!$app[0]) die("\nno founded app.\n");

$composer =include "$vendor/autoload.php";
$composer->addPsr4("$ns\\", $src);

$sort ="\\{$ns}controllers\\";

//$ref =new ReflectionClass("\\$app[0]");
//var_dump($ref->getTraits());


$classes =getClasses(path('src/controllers'));
$refs=getRefs($classes);
[$routes, $https]=parseAttributes($refs);

function parseAttributes($refs):array{
	$https =[];

	$routes=[];
	foreach($refs as $classOrMethod=>$attrs){
		@[$class, $method]=explode("::", $classOrMethod);
		foreach($attrs as [$attr, $params, $anno]){
			if(str_starts_with($attr, "nx\annotations\\router")){
				$attr = substr($attr, strlen("nx\annotations\\router") + 1);
				switch($attr){
					case "REST":
						$routes =[...$routes, ...$anno->newInstance()->route($class, $method)];
						break;
					case "Method":
					case "Any":
					case "Get":
					case "Put":
					case "Patch":
					case "Post":
					case "Delete":
						$routes[] =$anno->newInstance()->route($class, $method);
						break;
					default:
						throw new \Error("Unknown Attribute [$attr]");
				}
			} else if(str_starts_with($attr, "nx\annotations\http")){
				$attr = substr($attr, strlen("nx\annotations\http") + 1);
				switch($attr){
					case 'Client':
						$h =$anno->newInstance();
						$https[$h->id($class, $method)]=$h;
						break;
					default:
						throw new \Error("Unknown Attribute [$attr]");
				}
			}
		}
	}
	usort($routes, function($a, $b){
		$MethodOrder = ['*' => 1, 'options' => 2, 'get' => 3, 'post' => 4, 'patch' => 5, 'put' => 6, 'delete' => 7];
		$a_len=strlen($a[1]);
		//if($a[0]==='*') return $b[0]==='*'?strcmp($a[1], $b[1]):-1;
		if($a[1][$a_len - 1] === '+'){ // /user/d:uid+
			if(substr($a[1], 0, $a_len - 1) === $b[1]) return -1;
		}
		$r=strcmp($a[1], $b[1]);
		if(0 === $r){
			$_amo=$MethodOrder[$a[0]] ?? 999;
			$_bmo=$MethodOrder[$b[0]] ?? 999;
			return $_amo < $_bmo ?-1 :($_amo > $_bmo ?1 :0);
		}
		return $r;
	});
	return [$routes, $https];
}

function getClasses(string $Path):array{
	$r=[];
	if(!is_dir($Path)) return [];
	$items=scandir($Path);
	foreach($items as $item){
		$file="$Path/$item";
		if($item == '.' || $item == '..'){
			continue;
		}elseif(is_dir($file)){
			$r=[...$r, ...getClasses($file)];
		}elseif(is_file($file) && is_readable($file)){
			$r=[...$r, ...parseClasses($file)[1]];
		}
	}
	return $r;
}

function getAttrs($r):array{
	$attributes=$r->getAttributes();
	$result=[];
	foreach($attributes as $attribute){
		$result[]=[$attribute->getName(), $attribute->getArguments(), $attribute];
	}
	return $result;
}
function getRefs($classes):array{
	$RefAttrs=[];
	foreach($classes as $class){
		$r=new \ReflectionClass("\\$class");
		$attributes=$r->getAttributes();
		foreach($attributes as $attribute){
			$RefAttrs["\\$class"]=getAttrs($r);
		}
		foreach($r->getMethods() as $method){
			$RefMethodAttrs=getAttrs($method);
			if(count($RefMethodAttrs)) $RefAttrs["\\$class::{$method->getName()}"]=$RefMethodAttrs;
		}
	}
	return $RefAttrs;
}
function buildParams($params=[]):string{
	global $sort;
	$str=[];
	foreach($params as $param){
		@[$cls, $act, $pms]=$param;
		$cls=str_replace($sort, "", $cls);
		$p=$pms ?export($param[2], true) :'';
		if(strlen($p)){
			$p=", ".trim($p);
			$str[]="['$cls', '$act'$p]";
		}else $str[]="'$cls::$act'";
		//$str[] ="['$cls', '$act'$p]";
	}
	return implode(",", $str);
}
function export($expression, $return=true):string{
	$export=var_export($expression, $return);
	$patterns=[
		"/array \(/"=>'[',
		"/^([ ]*)\)(,?)$/m"=>'$1]$2',
		"/=>[ ]?\n[ ]+\[/"=>'=>[',
		"/([ ]*)(\'[^\']+\') => ([\[\'])/"=>'$1$2=>$3',
		"/,]/"=>']',
		"/\[  /"=>'[',
		"/ => /"=>'=>',
		"/\d=>/"=>'',
		"/\n/"=>'',
		"/\s/"=>'',
		"/\,\]/"=>']',
	];
	return preg_replace(array_keys($patterns), array_values($patterns), $export);
}

echo "controller sort: ", $sort ?? 'none', "\n\n";
echo "find ", count($routes), ' routes', "\n";
echo "find ", count($https), ' https', "\n";

if(0===count($routes) && 0===count($https)) die('exit.');

//if('yes' === choice("are u sure to write file?", ['y' => 'yes', 'n' => 'no', '' => 'yes'])){
if(count($routes)){
	$temp =[];
	$max =0;
	foreach($routes as [$Method, $Uri, $Params]){
		$P = buildParams($Params);
		$MethodSet = ['get' => 'G', 'post' => 'P', 'put' => 'U', 'patch' => 'A', 'delete' => 'D', 'options' => 'O'];
		$_M = $MethodSet[$Method] ?? $Method;
		$temp[] =[$_M, $Uri, $P];
		if($max<strlen($Uri)) $max = strlen($Uri);
	}
	$lines=[];
	foreach($temp as [$_M, $Uri, $P]){
		$_U = str_pad("", round(($max + 1 - strlen("'$_M:$Uri'")) / 4) + 1, "\t");
		$lines[] = "['$_M:$Uri',$_U$P]";
	}
	$ok = put_php_file(path("src/route.php"), "//build by nx-tools\n//*:All G:get P:post A:patch U:put D:delete O:options\n//sort:$sort\nreturn [\n\t" . implode(",\n\t", $lines) . "\n];");
	echo "\nwrite src/route.php ", $ok?"done.":"fail.", "\n";
}
if(count($https)){
	$http = implode("", $https);
	$ok =file_put_contents(path("route.http"),
		"# build by nx-tools
@host =http://localhost:8080

\n$http"
	);
	echo "\nwrite route.http ", $ok?"done.":"fail.", "\n\n";
}
//}
