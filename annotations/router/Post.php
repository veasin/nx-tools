<?php
namespace nx\annotations\router;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS| Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Post extends Any{
	protected string $Method='post';
}