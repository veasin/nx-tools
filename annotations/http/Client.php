<?php

namespace nx\annotations\http;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Client{
	protected string $Method;
	protected string $Uri = '';
	protected array $Query = [];
	protected array $Headers = [];
	protected array $Body = [];
	protected array $Throw = [];
	protected array $Return = [];
	protected string $Note = '';
	protected string $Group = '';
	protected array $Response = [];
	protected array $Test=[];
	protected string $Auth="";
	/**
	 * @param string $Method   请求方法
	 * @param string $Uri      请求地址
	 * @param array  $Query    请求 query
	 * @param array  $Headers  请求header
	 * @param string $Auth
	 * @param array  $Body     请求body
	 * @param array  $Throw    抛出异常的状态码
	 * @param array  $Return   200时返回的结构
	 * @param string $Note     备注
	 * @param string $Group    默认为 __CLASS__
	 * @param array  $Response 响应结果的处理 如header中的token等
	 * @param array  $Test
	 */
	public function __construct(string $Method,
		string $Uri = '',
		array $Query = [],
		array $Headers = [],
		string $Auth="",
		array $Body = [],
		array $Throw = [],
		array $Return = [],
		string $Note = '',
		string $Group = "",
		array $Response = [],
		array $Test = [],
	){
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
		$this->Test = $Test;
		$this->Auth = $Auth;
	}
	public function __toString(): string{
		$_method =strtoupper($this->Method);
		if(count($this->Query)){
			$_query = [];
			$_http_query = [];
			foreach($this->Query as $name => $query){
				$_query[] = "# Query $name: ".$this->explainQuery($query);
				if($query['test']) $_http_query[] = "$name={$query['test']}";
			}
			$_query_string = "\n" . implode(' ', $_query);
			$_query_http =count($_http_query)? "?".implode("&", $_http_query):"";
		} else {
			$_query_string ="";
			$_query_http ="";
		}
		if(!empty($this->Auth)){
			$this->Headers['Authorization'] = $this->Auth;
		}
		if(count($this->Headers)){
			$_headers= [];
			foreach($this->Headers as $name => $h){
				$_headers[] = "$name: $h";
			}
			$_headers = "\n" . implode("\n", $_headers);
		} else $_headers ="";
		if(count($this->Return)){
			$_return= ['# Return:'];
			foreach($this->Return as $name => $h){
				$_return[] = "#   $name: ".(is_string($h)?$h:($h['type'] ?? "").' '.($h['name'] ?? ''));
			}
			$_return = "\n" . implode("\n", $_return);
		} else $_return ="";
		if(count($this->Response)){
			$_set =["\n> {%"];
			foreach($this->Response['body']??[] as $body=>$var){
				$_set[] ="\tclient.global.set('$var', response.body['$body']);";
			}
			foreach($this->Response['header']??[] as $body=>$var){
				$_set[] ="\tclient.global.set('$var', response.headers.valueOf('$body'));";
			}
			$_set[] ="%}\n";
			$_set = implode("\n", $_set);
		} else $_set ="";

		return "### $this->Note
# Group $this->Group$_query_string$_return
$_method {{host}}$this->Uri$_query_http$_headers
$_set
";
	}
	protected function explainQuery($query): string{
		$r =[];
		foreach($query as $key=>$set){
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
					$r[] =json_encode($query);
			}
		}
		return implode(", ", $r);
	}
	public function id($class, $method): string{
		if(empty($this->Group)){
			$cls = explode("\\", $class);
			$this->Group = end($cls);
		}
		return "$this->Group>$this->Method:$this->Uri";
	}
}