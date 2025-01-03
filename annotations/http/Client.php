<?php

namespace nx\annotations\http;

use Attribute;
use nx\annotations\router\Any;
use nx\annotations\router\Method;
use nx\annotations\router\REST;
use nx\parts\log;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Client{
	protected string $Method;
	protected string $Uri = '';
	protected array $Query = [];
	protected array $Headers = [];
	protected array $Body = [];
	protected array $Throw = [];
	protected array $Return = [];
	protected string $Note = '';
	protected string $Name = '';
	protected string $Group = '';
	protected array $Response = [];
	protected array $Test=[];
	protected string $Auth="";
	protected array $Var=[];
	protected static array $Vars =[
		'hostname'=>'localhost',
		'port'=>'8080',
		'protocol'=>'http',
		'host'=>'{{protocol}}://{{hostname}}:{{port}}',
	];
	protected array $ControllerSet=[];
	protected ?Any $Route=null;
	protected array $ClassMethod=[];
	/**
	 * @param string $Method   请求方法
	 * @param string $Uri      请求地址
	 * @param array  $Route
	 * @param array  $Query    请求 query
	 * @param array  $Headers  请求header
	 * @param string $Auth
	 * @param array  $Body     请求body
	 * @param array  $Throw    抛出异常的状态码
	 * @param array  $Return   200时返回的结构
	 * @param string $Note     备注
	 * @param string $Name     名称
	 * @param string $Group    默认为 __CLASS__
	 * @param array  $Response 响应结果的处理 如header中的token等
	 * @param array  $Var      设置变量
	 * @param array  $Test
	 */
	public function __construct(string $Method="?",
		string $Uri = '',
		array $Route=[],
		array $Query = [],
		array $Headers = [],
		string $Auth="",
		array $Body = [],
		array $Throw = [],
		array $Return = [],
		string $Note = '',
		string $Name = '',
		string $Group = "",
		array $Response = [],
		array $Var =[],
		array $Test = [],
	){
		if(is_array($Route) && count($Route)){
			if('REST' ===strtoupper($Route[0])){
				array_shift($Route);
				$this->Route =new REST(...$Route);
			} else $this->Route =new Method(...$Route);
		}
		$this->Method = $Method;
		$this->Uri = $Uri;
		$this->Query = $Query;
		$this->Headers = $Headers;
		$this->Body = $Body;
		$this->Throw = $Throw;
		$this->Return = $Return;
		$this->Note = $Note;
		$this->Group = $Group;
		$this->Response = $Response;
		$this->Var =$Var;
		$this->Test = $Test;
		$this->Auth = $Auth;
		$this->Name = $Name;
		if(count($this->Var)){
			foreach($this->Var as $name => $h){
				self::$Vars[$name] =$h;
			}
		}
	}
	protected function _makeQuery($Query): array{
		if(count($Query)){
			$_query = [];
			$_http_query = [];
			foreach($Query as $name => $query){
				$_query[] = "# Query $name: ".$this->explainQuery($query);
				if($query['test']) $_http_query[] = "$name={$query['test']}";
			}
			$_query_string = "\n" . implode(' ', $_query);
			$_query_http =count($_http_query)? "?".implode("&", $_http_query):"";
		} else {
			$_query_string ="";
			$_query_http ="";
		}
		return [$_query_string, $_query_http];
	}
	protected function _makeHeaders($Headers, $Auth=""): string{
		if(!empty($Auth)){
			$Headers['Authorization'] = $Auth;
		}
		if(count($Headers)){
			$_headers= [];
			foreach($Headers as $name => $h){
				$_headers[] = "$name: $h";
			}
			$_headers = "\n" . implode("\n", $_headers);
		} else $_headers ="";
		return $_headers;
	}
	protected function _makeReturn($Return): string{
		if(count($Return)){
			$_return= ['# Return:'];
			foreach($Return as $name => $h){
				$_return[] = "#   $name: ".(is_string($h)?$h:($h['type'] ?? "").' '.($h['name'] ?? ''));
			}
			$_return = "\n" . implode("\n", $_return);
		} else $_return ="";
		return $_return;
	}
	protected function _makeResponse($Response): string{
		if(count($Response)){
			$_set =["\n> {%"];
			foreach($Response['body']??[] as $body=>$var){
				$_set[] ="\tclient.global.set('$var', response.body['$body']);";
			}
			foreach($Response['header']??[] as $body=>$var){
				$_set[] ="\tclient.global.set('$var', response.headers.valueOf('$body'));";
			}
			$_set[] ="%}\n";
			$_set = implode("\n", $_set);
		} else $_set ="";
		return $_set;
	}
	protected function _makeArgs($Args): array{
		if(count($Args)){
			$_r = [];
			$_query = [];
			foreach($Args as $from => $arg){
				$_r[] = "# $from: ";
				$max =0;
				foreach($arg as $name => $set){
					if($max<strlen($name)) $max = strlen($name);
				}
				foreach($arg as $name => $set){
					$_r[] = "#   $name: ".str_repeat(" ", $max -strlen($name)).implode(', ', $set);
				}
			}
			$_string = "\n" . implode("\n", $_r);
			$_query =count($_query)? "?".implode("&", $_query):"";
		} else {
			$_string ="";
			$_query ="";
		}
		return [$_string, $_query];
	}
	protected function _makeRequestVar($Vars): string{
		$n =count($Vars);
		if($n){
			$_set =[];
			foreach($Vars as $name=>$value){
				if(null===$value || ''===$value) continue;
				$_set[] =(1==$n?' ':"\t")."request.variables.set('$name', '$value')".(1===$n?" ":";");
			}
			if(count($_set)) $_set = "\n< {%\n".implode(1===$n ?"":"\n", $_set)."\n%}";
			else $_set="";
		} else $_set ="";
		return $_set;
	}
	public function __toString(): string{
		$_method =strtoupper($this->Method);
		[$_query_string, $_query_http] = $this->_makeQuery($this->Query);
		$_headers = $this->_makeHeaders($this->Headers, $this->Auth);
		$_return = $this->_makeReturn($this->Return);
		$_set = $this->_makeResponse($this->Response);
		if(!empty(trim($this->Name))){
			$_name ="\n# @name = $this->Name";
		} else $_name ="";
		$_body="\n";
		$_argString=$_r_var="";
		$_uri =$this->Uri;
		if($this->Route){
			if(!$this->Route->isMultiple()){
				$action = $this->ClassMethod[1] ?? null;
				[$_method, $_uri] = $this->Route->route($this->ClassMethod[0]??null, $this->ClassMethod[1] ?? null);
				$_uri =preg_replace_callback('#([d|w]?):(\w*)#', fn($matches)=>'{{'.('' !== $matches[2] ?$matches[2] :'').'}}', $_uri);
				$_method = strtoupper($_method);
				if('*' ===$_method) return "";
				foreach($this->Route->actionsMap() as $action){
					if(in_array($action, ['list', 'add', 'get', 'update', 'delete'])){
						[$__list_name,
							$__act_name,
							$_return,
							$_argString,
							$_query_http,
							$_body,
							$_headers,
							$_r_var] = $this->getModelMethod($action, $_return, $_headers);
					}
				}
			} else {//REST
				//var_dump($this->ControllerSet);
				$r =[];
				foreach($this->Route->actionsMap() as $action => $route){
					$_method = strtoupper($route[0]);
					$_uri =preg_replace_callback('#([d|w]?):(\w*)#', fn($matches)=>'{{'.('' !== $matches[2] ?$matches[2] :'').'}}', $route[1]);
					[$__list_name,
						$__act_name,
						$_return,
						$_argString,
						$_query_http,
						$_body,
						$_headers,
						$_r_var] = $this->getModelMethod($action, $_return, $_headers);
					$r[]= "### $__act_name$this->Note$__list_name$_name\n# Group $this->Group$_argString$_return$_r_var\n$_method {{host}}$_uri$_query_http$_headers\n$_body$_set\n";
				}
				return implode("\n", $r);
			}
		}
		if(empty($_uri)) return "";
		return "### $this->Note$_name\n# Group $this->Group$_query_string$_argString$_return$_r_var\n$_method {{host}}$_uri$_query_http$_headers\n$_body$_set\n";
	}
	public static function outVar():string{
		$_var =[];
		foreach(self::$Vars as $name => $value){
			$_var[] = "@$name=$value";
		}
		return "\n" . implode("\n", $_var)."\n";
	}
	protected function explainQuery($sets): string{
		$r =[];
		foreach($sets as $key=>$set){
			if(is_numeric($key)) {
				$key =$set;
				$set=null;
			}
			switch($key){
				case 'null':
					$r[] ="不传".['throw'=>'报错', 'remove'=>'移除'][$set];
					break;
				case 'empty':
					$r[] ="为空".['throw'=>'报错', 'remove'=>'移除'][$set];
					break;
				case "test":
					//$r[] ="测试数据为 $set";
					break;
				default:
					$r[] =json_encode($sets);
			}
		}
		return implode(", ", $r);
	}
	protected function _explainInput($Args): array{
		$ne =['null'=>'不传', 'empty'=>'为空'];
		$rr =[];
		$uu =[];
		foreach($Args as $name=>$sets){
			$from ="unknown";
			$r =[];
			$u =null;
			foreach($sets as $key=>$set){
				if(is_numeric($key)) {
					$key =$set;
					$set=null;
				}
				$_def =[''=>'空字符串', null=>'NULL'];
				switch($key){
					case 'null':
					case 'empty':
						$r[] =$ne[$key].(['throw'=>'报错', 'remove'=>'移除'][$set] ?? "为 ".($_def[$set] ?? $set));
						if('throw'===$set) $u='';
						else if('remove'!==$set) $u=$set;
						break;
					case "digit":
						$_r=[];
						foreach($set as $rule => $_set){
							$_r[] ="$rule$_set";
						}
						$r[] ="数字检测 ".implode(",", $_r);
						break;
					case "test":
						$r[] ="测试数据为 $set";
						$u =$set;
						break;
					case "int":
						//						$r[] ="整型";
						break;
					case "str":
						//						$r[] ="字符串";
						break;
					case "error":
						if(is_string($set)) $r[] ="错误提示: $set";
						elseif (is_numeric($set)) $r[] ="错误码: $set";
						break;
					case "body":
					case "query":
					case "uri":
						$from =$key;
						break;
					default:
						$r[] =json_encode($sets);
				}
			}
			if(!array_key_exists($from, $rr)) $rr[$from]=[];
			$rr[$from][$name]=$r;
			if(null !==$u){
				if(!array_key_exists($from, $uu)) $uu[$from] = [];
				$uu[$from][$name] = $u;
			}
		}
		return [$rr, $uu];
	}
	public function id($class, $method): string{
		$this->ClassMethod=[$class, $method];
		if(empty($this->Group)){
			$cls = explode("\\controllers\\", $class);
			$this->Group = end($cls);
		}
		$method =$this->Method;
		$uri =$this->Uri;
		if($this->Route) [$method, $uri] = $this->Route->id();
		return "$this->Group>$method:$uri";
	}
	public function route():?Any{
		return $this->Route;
	}
	public function updateControllerSet($set): void{
		$this->ControllerSet =$set;
	}/**
 * @param mixed $action
 * @param string $_return
 * @param string $_headers
 * @return array
 */
	public function getModelMethod(mixed $action, string $_return, string $_headers): array{
		$__list_name = "";
		$__act_name = "";
		switch($action){
			case 'list':
				$_args = [...$this->ControllerSet['list'], ...$this->ControllerSet['options']];
				$__list_name = " 列表";
				break;
			case 'add':
				$_args = [...$this->ControllerSet['create']];
				$_return = "";
				$__list_name = " 创建";
				//$__act_name ="创建 ";
				break;
			case 'get':
				$_args = [...$this->ControllerSet['single']];
				$__list_name = " 获取";
				//$__act_name ="获取 ";
				break;
			case 'update':
				$_args = [...$this->ControllerSet['single'], ...$this->ControllerSet['update']];
				$_return = "";
				$__list_name = " 更新";
				//$__act_name ="更新 ";
				break;
			case 'delete':
				$_args = [...$this->ControllerSet['single']];
				$_return = "";
				$__list_name = " 删除";
				//$__act_name ="删除 ";
				break;
			default:
				$_args = [];
				$_return = "";
				break;
		}
		[$_argsset, $_updates] = $this->_explainInput($_args);
		[$_argString] = $this->_makeArgs($_argsset);
		$_query_http = count($_updates['query'] ?? []) ? "?" . http_build_query($_updates['query'] ?? []) : "";
		$__headers = [];
		if(count($_updates['body'] ?? [])){
			$__headers['Content-Type'] = 'application/x-www-form-urlencoded';
			$_body = "\n" . http_build_query($_updates['body']) . "\n";
		}
		else $_body = ($_argsset['body'] ?? null) ? "\n\n" : "";
		if(count($__headers)) $_headers = $this->_makeHeaders([...$this->Headers, ...$__headers], $this->Auth);
		if(count($_updates['uri'] ?? [])){
			$_r_var = $this->_makeRequestVar($_updates['uri'] ?? []);
		}
		else $_r_var = "";
		return [$__list_name, $__act_name, $_return, $_argString, $_query_http, $_body, $_headers, $_r_var];
	}
}