<?php
namespace nx\annotations\router;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS| Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Get extends Any{
	protected string $Method ='get';
}