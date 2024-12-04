<?php
namespace nx\annotations\router;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class REST{
	/**
	 * @param string $Uri 父类对应的地址 list(GET),add(POST)
	 * @param string $Sub 子类对应的后半段地址 get,update(PATCH),delete
	 * @param string $Actions 可选动作 list,add,get,update,delete
	 */
	public function __construct($Uri, $Sub="", $Actions=""){

	}
}