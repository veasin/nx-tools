<?php

namespace nx\test;

use Closure;
use Exception;

class test{
	// Message constants for i18n support
	protected const array MESSAGES = [
		'not_equal' => "不同",
		'not_in_range' => "不在范围内",
		'not_empty' => "非空",
		'not_true' => "非True",
		'not_truthy' => "非真",
		'not_false' => "非False",
		'not_falsy' => "非假",
		'not_greater' => "不大于",
		'not_greater_or_equal' => "不大于等于",
		'not_less' => "不小于",
		'not_less_or_equal' => "不小于等于",
		'not_contains' => "不包含",
		'not_contains_equal' => "不包含相等值",
		'not_array' => "不是数组",
		'not_bool' => "不是布尔值",
		'not_string' => "不是字符串",
		'not_float' => "不是浮点数",
		'not_int' => "不是整数",
		'not_nan' => "不是NaN",
		'not_callable' => "不可调用",
		'not_file' => "不是文件",
		'not_iterable' => "不可迭代",
		'not_numeric' => "不是数字",
		'not_digits' => "不是纯数字",
		'not_object' => "不是对象",
		'not_resource' => "不是资源",
		'not_scalar' => "不是标量",
		'not_json' => "不是JSON",
		'not_instance' => "不是实例",
		'not_all_instances' => "不全是实例",
		'missing_property' => "缺少属性",
		'count_mismatch' => "数量不匹配",
		'not_starts_with' => "不以指定内容开头",
		'not_ends_with' => "不以指定内容结尾",
		'no_exception' => "未抛出异常",
		'not_in_list' => "不在列表中",
		'not_null' => "不是null",
		'not_uuid' => "不是UUID",
		'element_failed' => "元素未通过测试",
		'array_mismatch' => "数组不匹配",
		'object_mismatch' => "对象不匹配",
		'not_equal_values' => "不相等",
		'not_equal_canonical' => "规范不相等",
		'delta_exceeded' => "超出允许误差",
		'missing_key' => "缺少键",
		'pattern_mismatch' => "不匹配模式",
		'not_uppercase' => "不是大写",
		'not_lowercase' => "不是小写",
		'not_alpha' => "包含非字母字符",
		'not_alnum' => "包含非字母数字字符",
		'not_snake_case' => "不是蛇形命名",
		'not_kebab_case' => "不是短横线命名",
		'not_camel_case' => "不是驼峰命名",
		'not_studly_case' => "不是帕斯卡命名",
		'not_snake_case_keys' => "键名不是蛇形命名",
		'not_kebab_case_keys' => "键名不是短横线命名",
		'not_camel_case_keys' => "键名不是驼峰命名",
		'not_studly_case_keys' => "键名不是帕斯卡命名",
		'not_directory' => "不是目录",
		'not_readable_dir' => "目录不可读",
		'not_readable_file' => "文件不可读",
		'not_writable_dir' => "目录不可写",
		'not_writable_file' => "文件不可写",
		'not_infinite' => "不是无限大",
		'length_mismatch' => "长度不匹配",
		'size_mismatch' => "大小不同",
		'not_url' => "不是URL",
		// 运行时消息
		'at_location' => " [%s]",
		'expected' => "期望值",
		'received' => "实际值",
		'summary' => "测试汇总",
		'failed_count' => "%d 个失败",
		'passed_count' => "%d 个通过",
		'assertions_count' => "(%d 个测试, %d 个断言)",
		// 错误消息
		'json_error' => "值不是字符串",
		'sequence_error' => "值不可迭代",
	];
	static protected array $tests = [];
	static protected int $count = 0;
	static protected int $passed = 0;
	static protected int $assertions = 0;
	static function __shutdown(): void{
		foreach(static::$tests as $test){
			$results = $test();
			foreach($results as [$result, $message, $value, $expect, $file]){
				if(!$result){
					echo "\n", $test->label, "\t", "\033[31m" , $message, "\033[0m", "", sprintf(self::MESSAGES['at_location'], $file), "\n";
					echo "\t", self::MESSAGES['expected'], ": ", "\033[32m" , json_encode($expect, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) , "\033[0m", "\n";
					echo "\t", self::MESSAGES['received'], ": ", "\033[31m" , json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) , "\033[0m", "\n";
				}
			}
		}
		$failed = static::$assertions - static::$passed;
		$totalAssertions = static::$assertions;
		$passed = static::$passed;
		echo "\n", self::MESSAGES['summary'], ": ";
		echo $failed ? "\033[31m" . sprintf(self::MESSAGES['failed_count'], $failed) . "\033[0m" : "\033[32m" . sprintf(self::MESSAGES['passed_count'], $passed) . "\033[0m";
		echo " ", sprintf(self::MESSAGES['assertions_count'], static::$count, $totalAssertions), "\n";
	}
	static function case($label, mixed $any=null): static{
		return new static($label, $any);
	}
	protected mixed $value;
	protected array $test = [];
	protected bool $negated = false;
	protected array $beforeCallbacks = [];
	protected array $afterCallbacks = [];
	/**
	 * 添加测试前回调
	 */
	public function before(callable $callback): static {
		$this->beforeCallbacks[] = $callback;
		return $this;
	}

	/**
	 * 添加测试后回调
	 */
	public function after(callable $callback): static {
		$this->afterCallbacks[] = $callback;
		return $this;
	}
	public function __construct(protected string $label, mixed $value=null){
		$this->value = $value;
		static::$tests[] = $this;
		if(1 === count(static::$tests)){
			register_shutdown_function(static::__shutdown(...));
		}
	}
	public function __invoke(): array{
		// 执行前置回调
		foreach ($this->beforeCallbacks as $callback) {
			$result = $callback($this->value);
			if ($result !== null) {
				$this->value = $result;
			}
		}
		static::$count++;
		$valueToTest = $this->value;
		try{
			if ($valueToTest instanceof Closure || is_callable($valueToTest)) {
				$v = call_user_func($valueToTest);
			} else {
				$v = $valueToTest;
			}
			$value = [get_debug_type($v), $v];
		}catch(Exception $e){
			$value = [get_debug_type($e), $e];
		}
		$results = [];
		foreach($this->test as [$test, $message, $expect, $file]){
			static::$assertions++;
			$result = call_user_func($test, $value[1]);
			if($result) static::$passed++;
			$results[] = [$result, $message, $value[1], $expect, $file];
		}
		// 执行后置回调
		foreach ($this->afterCallbacks as $callback) {
			$callback($this->value);
		}
		return $results;
	}
	protected function backtrace(): string{
		$dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		return $dbt[2]['file'] . ':' . $dbt[2]['line'];
	}
	protected function test(callable $testFn, string $messageKey, $expect): static{
		if($this->negated){
			$testFn = function($value) use ($testFn){
				return !$testFn($value);
			};
			$this->negated = false;
		}
		$message = self::MESSAGES[$messageKey] ?? $messageKey;
		$this->test[] = [$testFn, $message, $expect, $this->backtrace()];
		return $this;
	}
	// Basic assertions
	public function toBe($value, $message = "not_equal"): static{
		return $this->test(fn($test) => $test === $value, $message, $value);
	}
	public function toBeBetween($min, $max, $message = "not_in_range"): static{
		return $this->test(fn($test) => $test >= $min && $test <= $max, $message, [$min, $max]);
	}
	public function toBeEmpty($message = "not_empty"): static{
		return $this->test(fn($test) => empty($test), $message, []);
	}
	public function toBeTrue($message = "not_true"): static{
		return $this->test(fn($test) => $test === true, $message, true);
	}
	public function toBeTruthy($message = "not_truthy"): static{
		return $this->test(fn($test) => (bool)$test, $message, true);
	}
	public function toBeFalse($message = "not_false"): static{
		return $this->test(fn($test) => $test === false, $message, false);
	}
	public function toBeFalsy($message = "not_falsy"): static{
		return $this->test(fn($test) => !$test, $message, false);
	}
	// Comparison assertions
	public function toBeGreaterThan($expected, $message = "not_greater"): static{
		return $this->test(fn($test) => $test > $expected, $message, $expected);
	}
	public function toBeGreaterThanOrEqual($expected, $message = "not_greater_or_equal"): static{
		return $this->test(fn($test) => $test >= $expected, $message, $expected);
	}
	public function toBeLessThan($expected, $message = "not_less"): static{
		return $this->test(fn($test) => $test < $expected, $message, $expected);
	}
	public function toBeLessThanOrEqual($expected, $message = "not_less_or_equal"): static{
		return $this->test(fn($test) => $test <= $expected, $message, $expected);
	}
	// Containment assertions
	public function toContain(...$needles): static{
		return $this->test(function($test) use ($needles){
			if(is_string($test)){
				foreach($needles as $needle){
					if(!str_contains($test, $needle)) return false;
				}
				return true;
			}
			if(is_iterable($test)){
				foreach($needles as $needle){
					if(!in_array($needle, [...$test], false)) return false;
				}
				return true;
			}
			return false;
		}, "not_contains", $needles);
	}
	public function toContainEqual(...$needles): static{
		return $this->test(function($test) use ($needles){
			if(is_iterable($test)){
				foreach($needles as $needle){
					$found = false;
					foreach($test as $item){
						if($item == $needle){
							$found = true;
							break;
						}
					}
					if(!$found) return false;
				}
				return true;
			}
			return false;
		}, "not_contains_equal", $needles);
	}
	// Type assertions
	public function toBeArray($message = "not_array"): static{
		return $this->test(fn($test) => is_array($test), $message, 'array');
	}
	public function toBeBool($message = "not_bool"): static{
		return $this->test(fn($test) => is_bool($test), $message, 'bool');
	}
	public function toBeString($message = "not_string"): static{
		return $this->test(fn($test) => is_string($test), $message, 'string');
	}
	public function toBeFloat($message = "not_float"): static{
		return $this->test(fn($test) => is_float($test), $message, 'float');
	}
	public function toBeInt($message = "not_int"): static{
		return $this->test(fn($test) => is_int($test), $message, 'int');
	}
	public function toBeNan($message = "not_nan"): static{
		return $this->test(fn($test) => is_nan($test), $message, NAN);
	}
	public function toBeCallable($message = "not_callable"): static{
		return $this->test(fn($test) => is_callable($test), $message, 'callable');
	}
	public function toBeFile($message = "not_file"): static{
		return $this->test(fn($test) => is_string($test) && file_exists($test) && is_file($test), $message, 'file');
	}
	public function toBeIterable($message = "not_iterable"): static{
		return $this->test(fn($test) => is_iterable($test), $message, 'iterable');
	}
	public function toBeNumeric($message = "not_numeric"): static{
		return $this->test(fn($test) => is_numeric($test), $message, 'numeric');
	}
	// Replace ctype_digit
	private static function isDigits(string $str): bool{
		return preg_match('/^[0-9]+$/', $str) === 1;
	}
	// Replace ctype_alpha
	private static function isAlpha(string $str): bool{
		return preg_match('/^[a-zA-Z]+$/', $str) === 1;
	}
	// Replace ctype_alnum
	private static function isAlphaNumeric(string $str): bool{
		return preg_match('/^[a-zA-Z0-9]+$/', $str) === 1;
	}
	public function toBeDigits($message = "not_digits"): static{
		return $this->test(fn($test) => self::isDigits((string)$test), $message, 'digits');
	}
	public function toBeObject($message = "not_object"): static{
		return $this->test(fn($test) => is_object($test), $message, 'object');
	}
	public function toBeResource($message = "not_resource"): static{
		return $this->test(fn($test) => is_resource($test), $message, 'resource');
	}
	public function toBeScalar($message = "not_scalar"): static{
		return $this->test(fn($test) => is_scalar($test), $message, 'scalar');
	}
	public function toBeJson($message = "not_json"): static{
		return $this->test(function($test){
			if(!is_string($test)) return false;
			return json_validate($test);
		}, $message, 'json');
	}
	// Object/class assertions
	public function toBeInstanceOf($class, $message = "not_instance"): static{
		return $this->test(fn($test) => $test instanceof $class, $message, $class);
	}
	// Object/array assertions
	public function toContainOnlyInstancesOf($class, $message = "not_all_instances"): static{
		return $this->test(function($test) use ($class){
			if(!is_iterable($test)) return false;
			foreach($test as $item){
				if(!$item instanceof $class) return false;
			}
			return true;
		}, $message, $class);
	}
	public function toHaveProperty(string $name, $value = null, $message = "missing_property"): static{
		return $this->test(function($test) use ($name, $value){
			$parts = explode('.', $name);
			$current = $test;
			foreach($parts as $part){
				if(is_object($current)){
					if(!property_exists($current, $part)) return false;
					$current = $current->$part;
				}
				elseif(is_array($current)){
					if(!array_key_exists($part, $current)) return false;
					$current = $current[$part];
				}
				else{
					return false;
				}
			}
			return $value === null || $current === $value;
		}, $message, $name);
	}
	public function toHaveProperties($properties, $message = "missing_property"): static{
		return $this->test(function($test) use ($properties){
			if(!is_object($test) && !is_array($test)) return false;
			foreach($properties as $key => $value){
				if(is_numeric($key)){
					if(!$this->hasProperty($test, $value)) return false;
				}
				else{
					if(!$this->hasProperty($test, $key) || $this->getProperty($test, $key) !== $value){
						return false;
					}
				}
			}
			return true;
		}, $message, $properties);
	}
	public function toHaveCount(int $count, $message = "count_mismatch"): static{
		return $this->test(function($test) use ($count){
			if(is_countable($test)) return count($test) === $count;
			if(is_iterable($test)){
				$c = 0;
				foreach($test as $_) $c++;
				return $c === $count;
			}
			return false;
		}, $message, $count);
	}
	// String assertions
	public function toStartWith(string $expected, $message = "not_starts_with"): static{
		return $this->test(fn($test) => str_starts_with($test, $expected), $message, $expected);
	}
	public function toEndWith(string $expected, $message = "not_ends_with"): static{
		return $this->test(fn($test) => str_ends_with($test, $expected), $message, $expected);
	}
	// Exception handling
	public function toThrow($exception = null, $message = null, $messageText = "no_exception"): static{
		return $this->test(function($test) use ($exception, $message){
			if($exception){
				if(!$test instanceof $exception) return false;
				if($message && $test->getMessage() !== $message) return false;
			}
			return true;
		}, $messageText, $exception);
	}
	// Modifiers
	public function not(): static{
		$this->negated = true;
		return $this;
	}
	public function and($value): static{
		return new static($this->label, $value);
	}
	/**
	 * @throws Exception
	 */
	public function json(): static{
		if(!is_string($this->value)){
			throw new Exception('Value is not a string');
		}
		$this->value = json_decode($this->value, true);
		return $this;
	}
	// Other assertions
	public function toBeIn(array $values, $message = "not_in_list"): static{
		return $this->test(fn($test) => in_array($test, $values, true), $message, $values);
	}
	public function toBeNull($message = "not_null"): static{
		return $this->test(fn($test) => $test === null, $message, null);
	}
	public function toBeUuid($message = "not_uuid"): static{
		$pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
		return $this->test(fn($test) => preg_match($pattern, $test), $message, 'UUID');
	}
	public function each(callable $callback): static{
		return $this->test(function($test) use ($callback){
			if(!is_iterable($test)) return false;
			foreach($test as $item){
				$result = $callback($item);
				if(!$result) return false;
			}
			return true;
		}, "element_failed", $callback);
	}
	public function toMatchArray($array, $message = "array_mismatch"): static{
		return $this->test(function($test) use ($array){
			if(!is_array($test)) return false;
			foreach($array as $key => $value){
				if(!array_key_exists($key, $test) || $test[$key] !== $value){
					return false;
				}
			}
			return true;
		}, $message, $array);
	}
	public function toMatchObject($object, $message = "object_mismatch"): static{
		return $this->test(function($test) use ($object){
			if(!is_object($test)) return false;
			foreach((array)$object as $key => $value){
				if(!property_exists($test, $key) || $test->$key !== $value){
					return false;
				}
			}
			return true;
		}, $message, $object);
	}
	public function toEqual($expected, $message = "not_equal_values"): static{
		return $this->test(fn($test) => $test == $expected, $message, $expected);
	}
	public function toEqualCanonicalizing($expected, $message = "not_equal_canonical"): static{
		return $this->test(function($test) use ($expected){
			if(is_array($test) && is_array($expected)){
				sort($test);
				sort($expected);
				return $test === $expected;
			}
			return $test == $expected;
		}, $message, $expected);
	}
	public function toEqualWithDelta($expected, float $delta, $message = "delta_exceeded"): static{
		return $this->test(fn($test) => abs($test - $expected) <= $delta, $message, $expected);
	}
	public function toHaveKey(string $key, $value = null, $message = "missing_key"): static{
		return $this->test(function($test) use ($key, $value){
			$parts = explode('.', $key);
			$current = $test;
			foreach($parts as $part){
				if(is_object($current)){
					if(!property_exists($current, $part)) return false;
					$current = $current->$part;
				}
				elseif(is_array($current)){
					if(!array_key_exists($part, $current)) return false;
					$current = $current[$part];
				}
				else{
					return false;
				}
			}
			return $value === null || $current === $value;
		}, $message, $key);
	}
	public function toHaveKeys(array $keys, $message = "missing_key"): static{
		return $this->test(function($test) use ($keys){
			foreach($keys as $key){
				if(!$this->hasProperty($test, $key)) return false;
			}
			return true;
		}, $message, $keys);
	}
	// String format assertions
	public function toMatch(string $expression, $message = "pattern_mismatch"): static{
		return $this->test(fn($test) => preg_match($expression, $test) === 1, $message, $expression);
	}
	public function toBeUppercase($message = "not_uppercase"): static{
		return $this->test(fn($test) => $test === strtoupper($test), $message, 'uppercase');
	}
	public function toBeLowercase($message = "not_lowercase"): static{
		return $this->test(fn($test) => $test === strtolower($test), $message, 'lowercase');
	}
	public function toBeAlpha($message = "not_alpha"): static{
		return $this->test(fn($test) => is_string($test) && self::isAlpha($test), $message, 'alpha');
	}
	public function toBeAlphaNumeric($message = "not_alnum"): static{
		return $this->test(fn($test) => is_string($test) && self::isAlphaNumeric($test), $message, 'alnum');
	}
	// Case format assertions
	public function toBeSnakeCase($message = "not_snake_case"): static{
		return $this->test(fn($test) => preg_match('/^[a-z]+(_[a-z]+)*$/', $test), $message, 'snake_case');
	}
	public function toBeKebabCase($message = "not_kebab_case"): static{
		return $this->test(fn($test) => preg_match('/^[a-z]+(-[a-z]+)*$/', $test), $message, 'kebab-case');
	}
	public function toBeCamelCase($message = "not_camel_case"): static{
		return $this->test(fn($test) => preg_match('/^[a-z]+([A-Z][a-z]*)*$/', $test), $message, 'camelCase');
	}
	public function toBeStudlyCase($message = "not_studly_case"): static{
		return $this->test(fn($test) => preg_match('/^[A-Z][a-z]+([A-Z][a-z]*)*$/', $test), $message, 'StudlyCase');
	}
	// Key format assertions
	public function toHaveSnakeCaseKeys($message = "not_snake_case_keys"): static{
		return $this->test(function($test){
			if(!is_array($test) && !is_object($test)) return false;
			foreach($test as $key => $value){
				if(!preg_match('/^[a-z]+(_[a-z]+)*$/', $key)) return false;
			}
			return true;
		}, $message, 'snake_case_keys');
	}
	public function toHaveKebabCaseKeys($message = "not_kebab_case_keys"): static{
		return $this->test(function($test){
			if(!is_array($test) && !is_object($test)) return false;
			foreach($test as $key => $value){
				if(!preg_match('/^[a-z]+(-[a-z]+)*$/', $key)) return false;
			}
			return true;
		}, $message, 'kebab-case_keys');
	}
	public function toHaveCamelCaseKeys($message = "not_camel_case_keys"): static{
		return $this->test(function($test){
			if(!is_array($test) && !is_object($test)) return false;
			foreach($test as $key => $value){
				if(!preg_match('/^[a-z]+([A-Z][a-z]*)*$/', $key)) return false;
			}
			return true;
		}, $message, 'camelCase_keys');
	}
	public function toHaveStudlyCaseKeys($message = "not_studly_case_keys"): static{
		return $this->test(function($test){
			if(!is_array($test) && !is_object($test)) return false;
			foreach($test as $key => $value){
				if(!preg_match('/^[A-Z][a-z]+([A-Z][a-z]*)*$/', $key)) return false;
			}
			return true;
		}, $message, 'StudlyCase_keys');
	}
	// Filesystem assertions
	public function toBeDirectory($message = "not_directory"): static{
		return $this->test(fn($test) => is_string($test) && is_dir($test), $message, 'directory');
	}
	public function toBeReadableDirectory($message = "not_readable_dir"): static{
		return $this->test(fn($test) => is_string($test) && is_dir($test) && is_readable($test), $message, 'readable_dir');
	}
	public function toBeReadableFile($message = "not_readable_file"): static{
		return $this->test(fn($test) => is_string($test) && is_file($test) && is_readable($test), $message, 'readable_file');
	}
	public function toBeWritableDirectory($message = "not_writable_dir"): static{
		return $this->test(fn($test) => is_string($test) && is_dir($test) && is_writable($test), $message, 'writable_dir');
	}
	public function toBeWritableFile($message = "not_writable_file"): static{
		return $this->test(fn($test) => is_string($test) && is_file($test) && is_writable($test), $message, 'writable_file');
	}
	// Other assertions
	public function toBeInfinite($message = "not_infinite"): static{
		return $this->test(fn($test) => is_infinite($test), $message, INF);
	}
	public function toHaveLength(int $number, $message = "length_mismatch"): static{
		return $this->test(function($test) use ($number){
			if(is_string($test)) return strlen($test) === $number;
			if(is_countable($test)) return count($test) === $number;
			return false;
		}, $message, $number);
	}
	public function toHaveSameSize($expected, $message = "size_mismatch"): static{
		return $this->test(function($test) use ($expected){
			if(is_countable($test) && is_countable($expected)){
				return count($test) === count($expected);
			}
			return false;
		}, $message, $expected);
	}
	public function toBeUrl($message = "not_url"): static{
		return $this->test(fn($test) => filter_var($test, FILTER_VALIDATE_URL) !== false, $message, 'url');
	}
	// Helper methods
	private function hasProperty($data, string $key): bool{
		$parts = explode('.', $key);
		$current = $data;
		foreach($parts as $part){
			if(is_object($current)){
				if(!property_exists($current, $part)) return false;
				$current = $current->$part;
			}
			elseif(is_array($current)){
				if(!array_key_exists($part, $current)) return false;
				$current = $current[$part];
			}
			else{
				return false;
			}
		}
		return true;
	}
	private function getProperty($data, string $key){
		$parts = explode('.', $key);
		$current = $data;
		foreach($parts as $part){
			if(is_object($current)){
				$current = $current->$part;
			}
			elseif(is_array($current)){
				$current = $current[$part];
			}
		}
		return $current;
	}
	// Modifiers
	public function dd(): static{
		var_dump($this->value);
		exit(1);
	}
	public function ddWhen($condition): static{
		if($condition){
			$this->dd();
		}
		return $this;
	}
	public function ddUnless($condition): static{
		if(!$condition){
			$this->dd();
		}
		return $this;
	}
	public function match($value, array $cases): static{
		if(array_key_exists($value, $cases)){
			$case = $cases[$value];
			if($case instanceof Closure){
				$case($this);
			}
			else{
				$this->toBe($case);
			}
		}
		return $this;
	}
	/**
	 * @throws Exception
	 */
	public function sequence(callable ...$callbacks): static{
		if(!is_iterable($this->value)){
			throw new Exception('Value is not iterable');
		}
		$index = 0;
		foreach($this->value as $key => $value){
			if(!isset($callbacks[$index])) break;
			$test = new static("Sequence item $index", $value);
			$callbacks[$index]($test, $key);
			$index++;
		}
		return $this;
	}
	public function when($condition, callable $callback): static{
		if($condition){
			$callback($this);
		}
		return $this;
	}
	public function unless($condition, callable $callback): static{
		if(!$condition){
			$callback($this);
		}
		return $this;
	}
}