<?php
//  .\vendor\bin\nx model user --dir=../../test
$src = path("src");
$options = [];
title("make src/setup.php");
if(!file_exists("$src/app.php")) die("\nno founded src/app.php\n.");
[$ns, $app, $traits] = parseClasses("$src/app.php");
if(!$app[0]) die("\nno founded app.\n");

$models = path("src/models");
$Params = $GLOBALS['_script_params'];
$arg1 = dirname($Params['-'][1]);
$args =explode("/", str_replace("\\", "/", $Params['-'][1]));
$name =array_pop($args);
$ns_path =count($args)===0?"" :'\\'. implode("\\", $args);
$path =str_replace("\\", "/", $ns_path);

$single = $name;
$multiple = plural($single);

echo "path: ", "$models$path", "\n";
echo "single: ", $single,"\n";
echo "multiple: ", $multiple, "\n";

if('yes' === choice("are u sure to make models?", ['y' => 'yes', 'n' => 'no', '' => 'yes'])){
	if(!file_exists("$models$path/")){
		mkdir("$models$path/", 0755, true);
	}
	$m = "namespace {$ns}models$ns_path;\n
use nx\helpers\model\multiple;
class $multiple extends multiple {
	protected string \$table = '$name';//db table name
	const TOMBSTONE =true;//db delete mode
	public function findByID(\$id):?$single{
		\$data = \$this->_find(['id'=>\$id]);
		if(null==\$data) return null;
		return new $single(\$data);	
	}
	public function create(\$data):$single{
		return new $single(\$data);
	}
}";
	$s = "namespace {$ns}models$ns_path;\n
use nx\helpers\model\single;
class $single extends single {
	protected string \$table = '$name';//db table name
	protected const MULTIPLE =$multiple::class;
}
";
	$ok1 =put_php_file("$models$path/$single.php", $s);
	echo "\nwrite $path/$single.php ", $ok1 ? "done." : "fail.", "\n";

	$ok2 =put_php_file("$models$path/$multiple.php", $m);
	echo "\nwrite $path/$multiple.php ", $ok2 ? "done." : "fail.", "\n";
}

function plural($word): string{
	if(str_ends_with($word, 'y') && substr($word, -2, 1) !== 'a' && substr($word, -2, 1) !== 'e' && substr($word, -2, 1) !== 'i' && substr($word, -2, 1) !== 'o'
		&& substr($word, -2, 1) !== 'u') return substr($word, 0, -1) . 'ies';
	elseif(str_ends_with($word, 's') || str_ends_with($word, 'x') || str_ends_with($word, 'z') || str_ends_with($word, 'ch') || str_ends_with($word, 'sh')) return $word . 'es';
	else return $word . 's';
}
