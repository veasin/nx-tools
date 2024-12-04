<?php
namespace nx\annotations\router;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS| Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Patch{
	public function __construct($Uri, $Action=null, $Params=[]){

	}
}