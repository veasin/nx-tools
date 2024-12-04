<?php
namespace nx\tools;

final class routeAnnotation{
	static function Make($event):void{
		$args=$event->getArguments();
		$_i_src=array_search('--src', $args);
		$_i_sort=array_search('--sort', $args);
		$_i_file=array_search('--file', $args);
		$_b_origin=array_search('--origin', $args) || array_search('-o', $args);
		$src=getcwd()."/src";
		$sort="";
		$file="/route.php";
		$_i_src !== false && $src=$args[$_i_src + 1] ?? getcwd()."/src";
		$_i_sort !== false && $sort=$args[$_i_sort + 1] ?? null;
		$_i_file !== false && $file=$args[$_i_file + 1] ?? "/route.php";
		echo "      work path: ", $src, "\n";
		echo "     route file: ", $file, "\n";
		echo "controller sort: ", $sort ?? 'none', "\n";
		$rA=new self($src, $sort);
		$ok=$_b_origin ?$rA->buildOrigin($file) :$rA->build($file);
		echo "\n", $ok ?"done." :"fail.", "\n\n";
	}
	private string $srcPath="";
	private string $controllerNameSort='';
	private int $max=0;
	const Method=['get'=>'G', 'post'=>'P', 'put'=>'U', 'patch'=>'A', 'delete'=>'D', 'options'=>'O'];
	const MethodOrder=['*'=>1, 'options'=>2, 'get'=>3, 'post'=>4, 'patch'=>5, 'put'=>6, 'delete'=>7];
	public function __construct(string $srcPath='./src', string $controllerSort=""){
		$this->srcPath=realpath($srcPath);
		$this->controllerNameSort=$controllerSort;
	}
	private function moreLevel($Method, $Uri, $Params):array{
		$parts=explode('/', trim($Uri, "/"));
		$count=count($parts);
		$pms=array_map(fn($p) => str_replace($this->controllerNameSort, "", $p[0])."::".$p[1], $Params);
		$m=$this::Method[$Method] ?? $Method;
		$r=[$m=>$pms];
		for($i=$count - 1; $i >= 0; $i--){
			$r=[$parts[$i]=>$r];
		}
		return $r;
	}
	public function buildTree($routeFile="/route_auto.php"):bool{
		$classes=$this->getClasses($this->srcPath."/controllers");
		$refs=$this->getRefs($classes);
		$routes=$this->parseAttributes($refs);
		$r=[];
		foreach($routes as [$Method, $Uri, $Params]){
			$n=$this->moreLevel($Method, $Uri, $Params);
			$r=array_merge_recursive($r, $n);
		}
		$build=self::class;
		return file_put_contents($this->srcPath.$routeFile, "<?PHP\n//build by $build\n//*:All G:get P:post A:patch U:put D:delete O:options\n//sort:$this->controllerNameSort\nreturn ".$this->export($r).";");
	}
	public function build($routeFile="/route_auto.php"):bool{
		$classes=$this->getClasses($this->srcPath."/controllers");
		$refs=$this->getRefs($classes);
		$routes=$this->parseAttributes($refs);
		$lines=[];
		foreach($routes as [$Method, $Uri, $Params]){
			$P=$this->buildParams($Params);
			$_M=$this::Method[$Method] ?? $Method;
			$_U=str_pad("", round(($this->max + 1 - strlen("'$_M:$Uri'")) / 4) + 1, "\t");
			$lines[]="['$_M:$Uri',$_U$P]";
		}
		$build=self::class;
		return file_put_contents($this->srcPath.$routeFile, "<?PHP\n//build by $build\n//*:All G:get P:post A:patch U:put D:delete O:options\n//sort:$this->controllerNameSort\nreturn [\n\t".implode(",\n\t", $lines)."\n];");
	}
	public function buildOrigin($routeFile="/route_auto.php"):bool{
		$classes=$this->getClasses($this->srcPath."/controllers");
		$refs=$this->getRefs($classes);
		$routes=$this->parseAttributes($refs);
		$lines=[];
		foreach($routes as [$Method, $Uri, $Params]){
			$P=$this->buildOriginParams($Params);
			$_M=str_pad("'$Method'", 8, " ", STR_PAD_LEFT);
			$_U=str_pad("'$Uri',", $this->max + 3, " ");
			$lines[]="[$_M, $_U $P]";
		}
		$build=self::class;
		return file_put_contents($this->srcPath.$routeFile, "<?PHP\n//build by $build\n//sort:$this->controllerNameSort\nreturn [\n\t".implode(",\n\t", $lines)."\n];");
	}
	private function export($expression, $return=false):string{
		$export=var_export($expression, true);
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
	private function buildParams($params=[]):string{
		$str=[];
		foreach($params as $param){
			@[$cls, $act, $pms]=$param;
			$cls=str_replace($this->controllerNameSort, "", $cls);
			$p=$pms ?$this->export($param[2], true) :'';
			if(strlen($p)){
				$p=", ".trim($p);
				$str[]="['$cls', '$act'$p]";
			}else $str[]="'$cls::$act'";
			//$str[] ="['$cls', '$act'$p]";
		}
		return implode(",", $str);
	}
	private function buildOriginParams($params=[]):string{
		$str=[];
		foreach($params as $param){
			@[$cls, $act, $pms]=$param;
			$cls=str_replace($this->controllerNameSort, "", $cls);
			$p=$pms ?$this->export($param[2], true) :'';
			if(strlen($p)){
				$p=", ".trim($p);
			}
			$str[]="['$cls', '$act'$p]";
		}
		return implode(",", $str);
	}
	private function call($class, $method, $params):array{
		$str=[];
		foreach($params as $param){
			if(is_string($param)){
				$str[]=[$class, $param];
			}elseif(is_array($param)){
				$cls=null === $param[0] ?$class :$param[0];
				$act=$param[1];
				$str[]=[$cls, $act, $param[2] ?? []];
			}elseif(is_null($param)){
				$str[]=[$class, $method];
			}
		}
		return $str;
	}
	private function parseAttributes($refs):array{
		$routes=[];
		foreach($refs as $classOrMethod=>$attrs){
			@[$class, $method]=explode("::", $classOrMethod);
			$_uri='';
			$_sub_uri='';
			$_actions=[];
			foreach($attrs as [$attr, $params]){
				if(!str_starts_with($attr, "nx\annotations\\router")) continue;
				$attr=substr($attr, strlen("nx\annotations\\router") + 1);
				switch($attr){
					case "REST":
						@[$_uri, $_sub_uri, $_action]=$params;
						if(null !== $_action) $_actions=explode(',', $_action);
						break;
					case "Actions":
						$_actions=explode(',', $params[0]);
						break;
					case "Method":
						$Method=array_shift($params);
						$Uri=array_shift($params);
						$Params=$this->call($class, $method, $params);
						$routes[]=[$Method, $Uri, $Params];
						break;
					case "Any":
						$Method="*";
						$Uri=array_shift($params);
						$Params=$this->call($class, $method, $params);
						$routes[]=[$Method, $Uri, $Params];
						break;
					case "Get":
					case "Put":
					case "Patch":
					case "Post":
					case "Delete":
						$Method=strtolower($attr);
						if(array_key_exists('Uri', $params) || array_key_exists('Action', $params)){
							$Uri=$params['Uri'] ?? $params[0];
							$params=[$params['Action'] ?? $params[1] ?? null];
						}else{
							$Uri=array_shift($params);
							if(count($params) === 0) $params=[[$class, $method]];
						}
						$Params=$this->call($class, $method, $params);
						$routes[]=[$Method, $Uri, $Params];
						break;
					default:
						throw new \Error("Unknown Attribute [$attr]");
				}
			}
			if(count($_actions) && strlen($_uri)){
				foreach($_actions as $action){
					$Method=['list'=>'get', 'add'=>'post', 'get'=>'get', 'update'=>'patch', 'delete'=>'delete'][$action];
					$Uri=['list'=>$_uri, 'add'=>$_uri, 'get'=>$_uri.$_sub_uri, 'update'=>$_uri.$_sub_uri, 'delete'=>$_uri.$_sub_uri][$action];
					$Params=$this->call($class, $action, [null]);
					$routes[]=[$Method, $Uri, $Params];
				}
			}
		}
		usort($routes, function($a, $b){
			$a_len=strlen($a[1]);
			if($this->max < $a_len) $this->max=$a_len;
			//if($a[0]==='*') return $b[0]==='*'?strcmp($a[1], $b[1]):-1;
			if($a[1][$a_len - 1] === '+'){ // /user/d:uid+
				if(substr($a[1], 0, $a_len - 1) === $b[1]) return -1;
			}
			$r=strcmp($a[1], $b[1]);
			if(0 === $r){
				$_amo=$this::MethodOrder[$a[0]] ?? 999;
				$_bmo=$this::MethodOrder[$b[0]] ?? 999;
				return $_amo < $_bmo ?-1 :($_amo > $_bmo ?1 :0);
			}
			return $r;
		});
		return $routes;
	}
	private function getAttrs($r):array{
		$attributes=$r->getAttributes();
		$result=[];
		foreach($attributes as $attribute){
			$result[]=[$attribute->getName(), $attribute->getArguments()];
		}
		return $result;
	}
	private function getRefs($classes):array{
		$RefAttrs=[];
		foreach($classes as $class){
			$r=new \ReflectionClass("\\$class");
			$attributes=$r->getAttributes();
			foreach($attributes as $attribute){
				$RefAttrs["\\$class"]=$this->getAttrs($r);
			}
			foreach($r->getMethods() as $method){
				$RefMethodAttrs=$this->getAttrs($method);
				if(count($RefMethodAttrs)) $RefAttrs["\\$class::{$method->getName()}"]=$RefMethodAttrs;
			}
		}
		return $RefAttrs;
	}
	private function getClasses(string $Path):array{
		$r=[];
		if(!is_dir($Path)) return [];
		$items=scandir($Path);
		foreach($items as $item){
			$file="$Path/$item";
			if($item == '.' || $item == '..'){
				continue;
			}elseif(is_dir($file)){
				$r=[...$r, ...$this->getClasses($file)];
			}elseif(is_file($file) && is_readable($file)){
				$r=[...$r, ...$this->parseClassFile($file)];
			}
		}
		return $r;
	}
	private function parseClassFile(string $path):array{
		$tokens=token_get_all(file_get_contents($path));
		$nsTokens=[\T_STRING=>true, \T_NS_SEPARATOR=>true];
		if(\defined('T_NAME_QUALIFIED')) $nsTokens[T_NAME_QUALIFIED]=true;
		$classes=[];
		$namespace='';
		for($i=0; isset($tokens[$i]); ++$i){
			$token=$tokens[$i];
			if(!isset($token[1])) continue;
			$class='';
			switch($token[0]){
				case \T_NAMESPACE:
					$namespace='';
					while(isset($tokens[++$i][1])){
						if(isset($nsTokens[$tokens[$i][0]])) $namespace.=$tokens[$i][1];
					}
					$namespace.='\\';
					break;
				case \T_CLASS:
				case \T_INTERFACE:
				case \T_TRAIT:
					$isClassConstant=false;
					for($j=$i - 1; $j > 0; --$j){
						if(!isset($tokens[$j][1])) break;
						if(\T_DOUBLE_COLON === $tokens[$j][0]){
							$isClassConstant=true;
							break;
						}elseif(!\in_array($tokens[$j][0], [\T_WHITESPACE, \T_DOC_COMMENT, \T_COMMENT])) break;
					}
					if($isClassConstant) break;
					while(isset($tokens[++$i][1])){
						$t=$tokens[$i];
						if(\T_STRING === $t[0]) $class.=$t[1];elseif('' !== $class && \T_WHITESPACE === $t[0]) break;
					}
					$classes[]=ltrim($namespace.$class, '\\');
					break;
			}
		}
		return $classes;
	}
}