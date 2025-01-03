<?php
namespace nx\annotations\router;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS| Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Any{
	protected string $Method ='*';
	protected string $Uri ="";
	protected array $Actions =[];
	protected bool $Multiple = false;
	/**
	 * @param string       $Uri        地址
	 * @param string|array ...$Actions 控制器方法
	 * - class =>[null, action, params=[]]
	 * - method =>[null, null, params=[]]
	 */
	public function __construct(string $Uri, ...$Actions){
		$this->Uri =$Uri;
		$this->Actions =$Actions;
	}
	public function route($class, $method): array{
		return [$this->Method, $this->Uri, $this->call($class, $method, empty($this->Actions) ?[null] :$this->Actions)];
	}
	public function isMultiple(): bool{
		return $this->Multiple;
	}
	protected function call($class, $method, $params=[]):array{
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
	public function id():array{
		return [$this->Method, $this->Uri];
	}
	public function actionsMap(): \Generator{
		if(count($this->Actions) && strlen($this->Uri)){
			foreach($this->Actions as $action){
				yield $action;
			}
		}
	}
}