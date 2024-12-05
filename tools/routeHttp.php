<?php
namespace nx\tools;

final class routeHttp{

	private string $path ="";
	private string $controllerSort ="";
	public function __construct($path="./src/route.php"){
		$this->path =realpath($path);
		if(!is_readable($this->path)) throw new \Error("Can't Read $this->path");
	}
	public function build():bool{
		//if(file_exists("route.http")) throw new \Error("route.http exits!");
		$routes =$this->parseRoute($this->path);
		
		$build =self::class;
		$lines =["# build by $build\n\n", "> {% \n\tclient.global.set('host', 'http://localhost:8080'); \n%}"];
		foreach($routes as $route){
			//$Method =array_shift($route);
			$route1 =array_shift($route);
			[$Method, $Uri] =explode(":", $route1, 2);
			if('*' ===$Method) continue;
			$lines[] ="### $Uri";
			foreach($route as $param){
				[$cls, $act] =explode("::", $param, 2);
				$cls =$this->controllerSort.$cls;

				$r =new \ReflectionMethod($cls, $act);

//				$n =$this->parseMethod($r);
				$lines[] ="# $this->controllerSort$cls->$act()";
				$docC =$r->getDocComment();
				if($docC){
					$docCLines =explode("\n", $docC);
					foreach($docCLines as $line){
						if(trim($line) ==='/**') continue;
						if(trim($line) ==='*/') continue;
						$line =preg_replace("/^\s*\*/", "", $line);
						$lines[]="# $line";
					}
					$lines[]="#";
				}
			}
			$lines[] =strtoupper(['G'=>'get', 'P'=>'post', 'U'=>'put', 'A'=>'patch', 'D'=>'delete', 'O'=>'options'][$Method] ?? $Method)." {{host}}$Uri";
			$lines[] ="\n";
		}
		return file_put_contents("route.http", implode("\n", $lines));
	}
	private function parseMethod(\ReflectionMethod $method){
		$lines = [];
		$n = 0;
		if (($handle = fopen($method->getFileName(), 'r'))!== false) {
			while (($line = fgets($handle))!== false) {
				$n++;
				if ($n >= $method->getStartLine() && $n <= $method->getEndLine())  $lines[] = $line;
				if ($n > $method->getEndLine())  break;
			}
			fclose($handle);
		}
		$tokens =token_get_all("<?PHP\n".implode('', $lines));

		var_dump($tokens);
	}
	private function parseRoute($file){
		$_route_file =explode("\n", file_get_contents($file));
		$this->controllerSort =substr($_route_file[3], 7);

		$routes =include $file;
		return array_filter($routes, function($r){
			if($r[0] ==="*" || $r[1] ==="*") return false;
			if($r[1][strlen($r[1])-1] ==='+') return false;
			return $r;
		});
	}
	static function Make($event):void{
		$args=$event->getArguments();
		$path =getcwd()."/src/route.php";
		$_i_path=array_search('--path', $args);
		$_i_path !== false && $path=$args[$_i_path + 1] ?? getcwd()."/src/route.php";

		echo "route file: ", $path, "\n\n";

		$rH=new self($path);
		$ok=$rH->build();
		echo "\n", $ok ?"done." :"fail.", "\n\n";
	}
}