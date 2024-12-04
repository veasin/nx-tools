<?php
namespace nx\annotations\router;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Any{
	public function __construct($Uri, ...$ControllerActionParams){

	}
}