<?php
namespace nx\annotations\router;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Method{
	public function __construct($Method, $Uri, ...$ControllerActionParams){

	}
}