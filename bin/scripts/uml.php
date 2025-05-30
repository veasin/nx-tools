<?php
// .\vendor\bin\nx uml --ns=com -o --dir=../../test
use function PHPSTORM_META\type;

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

$sort ="{$ns}models";
$sortName =className($sort);

$classes =getClasses(path('src/models'));
$refs =getRefs($classes);

//命名空间层级和类名相同会报错
$names =array_keys($refs);
$fix_names =[];
foreach($names as $name){
	$find =$name."\\";
	foreach($names as $_name){
		if(str_contains($_name,$find)){
			$fix_names[$name] =true;
			break;
		}
	}
}
//var_dump($fix_names);

const M ="nx\\helpers\model\\multiple";
const S ="nx\\helpers\model\\single";

if($refs[M]){
	$mutipleConstants =$refs[M]->getConstants();
} else $mutipleConstants =[];

$multiple =[];
$single =[];
foreach($refs as $cls => $ref){
	$consts =$ref->getConstants();
	if($consts['MULTIPLE'] ?? false){
		$multiple[$cls] =$consts['MULTIPLE'];
		$single[$consts['MULTIPLE']] =$cls;
	}
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

function getRefs($classes):array{
	$refs=[];
	foreach($classes as $class){
		$r=new ReflectionClass("\\$class");
		$refs[$r->getName()] =$r;
		while($p = $r->getParentClass()){
			$refs[$p->getName()]=$p;
			$r =$p;
		}
	}
	return $refs;
}

function className(string $name, bool $withFix =true):string{
	global $fix_names;
	if($withFix && ($fix_names[$name] ?? false)) $name.='_';//命名空间层级和类名相同会报错
	$name = str_replace("\\", "/", $name);
	//$name =str_replace($sortName, $sort, $name);
	return $name;
}

function value($value):string{
	switch(gettype($value)){
		case 'NULL':
			return 'NULL';
		case "boolean":
			return $value ? 'TRUE' : 'FALSE';
		case 'array':
			return json_encode($value);
		case 'string':
			return "'".$value."'";
		default:
			return $value;
	}
}

function color(&$idx){
	return ['red', 'blue', 'green', 'cyan', 'magenta', 'yellow', 'lightyellow'][$idx++]??'black';
}

function buildClass(ReflectionClass $cls):string{
	global $mutipleConstants, $refs;
	$after =[];//类后输出的内容
	$class =$cls->getName();
	$className =className($class);
	$parent = $cls->getParentClass();

	$c_idx =0;

	if($parent){
		switch($parent->getName()){
			case M:
				$extends ="<< (M, beige) >>";
				break;
			case S:
				$extends ="<< (S, lightblue) >>";
				break;
			default:
				$extend =className($parent->getName());
				$extends ="extends $extend ";
		}
	} else $extends="";

	//$string =["class $className $extends {"];

	$_const =[];
	$consts =$cls->getConstants();
	if($consts['MULTIPLE'] ?? false){
		$m =className($consts['MULTIPLE']);
		$after[] ="\"$m\" o-down- \"$className\"\n";
	}
	//var_dump($consts);
	foreach($consts as $const =>$value){
		//var_dump($value, $mutipleConstants[$const]??null);
		if($class!==M && $class!==S){
			if($value ===($mutipleConstants[$const]??null)) continue;
			if($const ==='MULTIPLE') {
				//$v =value($value);
				//$string[] ="'  {field} $const = $v";
				continue;
			}
		}
		$v =value($value);
		$_const[] ="  {field} $const = $v";
	}
	$_property =[];
	foreach($cls->getProperties() as $p){
		$line ="  ";
		if($p->isStatic()) $line .="{static} ";
		if($p->isPublic()) $line .="+ ";
		if($p->isProtected()) $line .="# ";
		if($p->isPrivate()) $line .="- ";
		$name =$p->getName();
		$line .=$name;
		if($p->hasDefaultValue()){
			$line .= " = ";
			$line .=value($p->getDefaultValue());
		}
		//$line .=" : ";
		$type =$p->getType();
		if(!$type->isBuiltin()){
			//var_dump($type->getName());
			$typeClass =$refs[$type->getName()]->getName();
			if($typeClass===$class){
				$type =" : SELF";
			} else {
				$type =className($refs[$type->getName()]->getName(), false);
				$type = " : ".$type;
				//$after[] ="\"$className::$name\" *.left.> \"$type\"\n";
				//$type ="";
			}
		} else $type='';// $type =$type->getName();
		$line .=$type;
		$_property[] =$line;

		//var_dump($property);
	}

	$file =$cls->getFileName();
	$_method =[];
	//$methods =$cls->getMethods();
	//$methods =array_filter($methods, fn($m)=>$m->getFileName()===$file);
	//var_dump($class, count($methods));
	//if(count($methods)>0) $string[] ='--';
	foreach($cls->getMethods() as $m){
		if($m->getFileName()!==$file) continue;
		$line ="  ";
		if($m->isStatic()) $line .="{static} ";
		if($m->isAbstract()) $line .="{abstract} ";
		if($m->isPublic()) $line .="+ ";
		if($m->isProtected()) $line .="# ";
		if($m->isPrivate()) $line .="- ";
		$name =$m->getName();
		$line .="$name(";

		$mp =array_map(function($p)use($refs, $class, $m){
			$s = $p->getName();
			if($p->hasType()){
				$s.=":".buildType($p->getType(), $refs, $class);
			}
			if($p->isOptional()){
				if($p->isVariadic()){
					$s ='...'.$s;
				} else{
					$s.="=".json_encode($p->getDefaultValue());
				}
			}

			return $s;
		}, $m->getParameters());

		$line .=implode(",", $mp);

		//foreach($m->getParameters() as $param){
		//	$line .="$".$param->getName();
		//}

		$line .=")";
		//if($m->hasDefaultValue()){
		//	$line .= " = ";
		//	$line .=value($m->getDefaultValue());
		//}
		//$line .=" : ";
		$type =$m->getReturnType();
		if($type){
			if($type instanceof \ReflectionNamedType){
				$type =buildType($type, $refs, $class);
				if(str_contains($type, '/')){
					$color =color($c_idx);
					$after[] ="\"$className::$name()\" *-[#$color,dotted]-> \"$type\"\n";
					$type ="";
				} else $type =" : ".$type;
			} else {
				$ts =[];
				foreach($type->getTypes() as $t){
					$ts[] =buildType($t, $refs, $class);
				}
				$type =implode("|", $ts);
				$type =" : ".$type;
			}
		} else $type='';
		$line .=$type;
		$_method[] =$line;
	}

	if(count($_const)){
		if(count($_property)) $_const[] ='..';
	}
	if(count($_property) || count($_const)) $_property[] ='__';

	//$string[] ="}\n";
	return join("\n", array_merge(["class $className $extends {"], $_const, $_property, $_method, ["}\n"])). join("", $after);
}

function buildType(\ReflectionType $type, $refs, $class): string{
	global $multiple, $single;

	if(!$type->isBuiltin()){
		//var_dump($type->getName());
		$typeClass = ($refs[$type->getName()]??null)?->getName();
		if($typeClass === $class){
			$type = " : SELF";
		}
		else{
			if($typeClass){
				$cls =$refs[$type->getName()]->getName();
				if(($single[$class] ?? null) ===$cls){//父类的方法返回的类的名字，和缓存中的M-S一样
					$type ="(S)";
				} else if(($multiple[$class]??null) ===$cls){
					$type ="(M)";
				} else{
					$type = className($refs[$type->getName()]->getName());
				}
				//$after[] ="\"$className::$name()\" *.left.> \"$type\"\n";
				//$type ="";
			} else $type =className($type->getName());
		}
	} else $type = $type->getName();
	return $type;
}

function buildUML($refs, string $filename='models.puml'){
	echo "find ", count($refs), ' models', "\n";
	$cls =[];
	foreach($refs as $ref){
		$cls[]=buildClass($ref);
	}
	$now =date("Y-m-d H:i:s");
	$string ="@startuml
'build by nx-tools

set namespaceSeparator /
!pragma useIntermediatePackages false

left header
Models UML
$now
endheader

".  implode("\n", $cls).""."\n\n@enduml";
	//echo $string;
	$ok =file_put_contents(path($filename), $string);
	echo "\nwrite $filename ", $ok?"done.":"fail.", "\n\n";
}


buildUML($refs);