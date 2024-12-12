<?php
namespace nx\annotations\router;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class REST extends Any{
	protected string $Uri ='';
	protected string $Sub ='';
	protected array $Actions=[];
	static array $Methods =['list'=>'get', 'add'=>'post', 'get'=>'get', 'update'=>'patch', 'delete'=>'delete'];
	/**
	 * @param string $Uri     父类对应的地址 list(GET),add(POST)
	 * @param string $Sub     子类对应的后半段地址 get,update(PATCH),delete
	 * @param string $Actions 可选动作 list,add,get,update,delete
	 */
	public function __construct(string $Uri, string $Sub="", string $Actions=""){
		$this->Sub = $Sub;
		$this->Method ="REST";
		$this->Multiple=true;
		parent::__construct($Uri, ...explode(",", $Actions));
	}
	public function route($class, $method): array{
		$routes =[];
		if(count($this->Actions) && strlen($this->Uri)){
			foreach($this->Actions as $action){
				$Method=self::$Methods[$action];
				$Uri=['list'=>$this->Uri, 'add'=>$this->Uri, 'get'=>$this->Uri.$this->Sub, 'update'=>$this->Uri.$this->Sub, 'delete'=>$this->Uri.$this->Sub][$action];
				$Params=$this->call($class, $action, [null]);
				$routes[]=[$Method, $Uri, $Params];
			}
		}
		return $routes;
	}
	public function actionsMap(): \Generator{
		if(count($this->Actions) && strlen($this->Uri)){
			foreach($this->Actions as $action){
				$Method=self::$Methods[$action];
				$Uri=['list'=>$this->Uri, 'add'=>$this->Uri, 'get'=>$this->Uri.$this->Sub, 'update'=>$this->Uri.$this->Sub, 'delete'=>$this->Uri.$this->Sub][$action];
				yield $action=>[$Method, $Uri];
			}
		}
	}
}