<?php

use nx\test\test;

include_once '../vendor/autoload.php';
error_reporting(E_ALL);


## 基础断言测试用例

### 1. toBe 断言


// 成功情况
test::case('toBe - 成功', 42)
    ->toBe(42);

// 失败情况
test::case('toBe - 失败', 42)
    ->toBe(43);


### 2. toBeBetween 断言


// 成功情况
test::case('toBeBetween - 成功', 5)
    ->toBeBetween(1, 10);

// 边界情况
test::case('toBeBetween - 边界成功', 10)
    ->toBeBetween(1, 10);

// 失败情况
test::case('toBeBetween - 失败', 15)
    ->toBeBetween(1, 10);


### 3. toBeEmpty 断言


// 成功情况
test::case('toBeEmpty - 成功', [])
    ->toBeEmpty();

// 失败情况
test::case('toBeEmpty - 失败', [1, 2, 3])
    ->toBeEmpty();


## 类型断言测试用例

### 4. toBeArray 断言


// 成功情况
test::case('toBeArray - 成功', [1, 2, 3])
    ->toBeArray();

// 失败情况
test::case('toBeArray - 失败', 'not an array')
    ->toBeArray();


### 5. toBeString 断言


// 成功情况
test::case('toBeString - 成功', 'hello')
    ->toBeString();

// 失败情况
test::case('toBeString - 失败', 123)
    ->toBeString();


## 包含断言测试用例

### 6. toContain 断言


// 成功情况 - 数组
test::case('toContain - 数组成功', ['a', 'b', 'c'])
    ->toContain('b');

// 成功情况 - 字符串
test::case('toContain - 字符串成功', 'hello world')
    ->toContain('world');

// 失败情况
test::case('toContain - 失败', ['a', 'b', 'c'])
    ->toContain('d');


### 7. toContainEqual 断言


// 成功情况
test::case('toContainEqual - 成功', [1, '2', ['three']])
    ->toContainEqual('2');

// 失败情况
test::case('toContainEqual - 失败', [1, '2', ['three']])
    ->toContainEqual(2); // 严格不相等


## 异常断言测试用例

### 8. toThrow 断言


// 成功情况
test::case('toThrow - 成功', function() {
    throw new Exception('error');
})->toThrow(Exception::class);

// 带消息检查
test::case('toThrow - 带消息检查成功', function() {
    throw new Exception('specific error');
})->toThrow(Exception::class, 'specific error');

// 失败情况 - 没抛出异常
test::case('toThrow - 无异常失败', function() {
    return 'no error';
})->toThrow(Exception::class);

// 失败情况 - 异常类型不匹配
test::case('toThrow - 类型不匹配失败', function() {
    throw new RuntimeException('error');
})->toThrow(InvalidArgumentException::class);


## 修饰符测试用例

### 9. not 修饰符


// 成功情况
test::case('not - 成功', 42)
    ->not()->toBe(43);

// 失败情况
test::case('not - 失败', 42)
    ->not()->toBe(42);


### 10. and 修饰符


// 链式断言
test::case('and - 链式断言', 42)
    ->toBeInt()
    ->and(3.14)
    ->toBeFloat();


## 对象/数组断言测试用例

### 11. toHaveProperty 断言


$obj = new class {
    public $name = 'test';
    public $nested = ['key' => 'value'];
};

// 成功情况
test::case('toHaveProperty - 成功', $obj)
    ->toHaveProperty('name');

// 嵌套属性
test::case('toHaveProperty - 嵌套属性成功', $obj)
    ->toHaveProperty('nested.key');

// 带值检查
test::case('toHaveProperty - 带值检查成功', $obj)
    ->toHaveProperty('name', 'test');

// 失败情况
test::case('toHaveProperty - 失败', $obj)
    ->toHaveProperty('nonexistent');


### 12. toHaveCount 断言


// 成功情况
test::case('toHaveCount - 成功', [1, 2, 3])
    ->toHaveCount(3);

// 失败情况
test::case('toHaveCount - 失败', [1, 2, 3])
    ->toHaveCount(5);


## JSON 相关断言测试用例

### 13. json 修饰符 + toHaveProperty


// 成功情况
test::case('json - 成功', '{"name":"test","age":30}')
    ->json()
    ->toHaveProperty('name', 'test');

// 失败情况 - 无效JSON
test::case('json - 无效JSON失败', 'invalid json')
    ->json();


## 字符串格式断言测试用例

### 14. toBeUppercase 断言


// 成功情况
test::case('toBeUppercase - 成功', 'HELLO')
    ->toBeUppercase();

// 失败情况
test::case('toBeUppercase - 失败', 'Hello')
    ->toBeUppercase();


### 15. toBeCamelCase 断言


// 成功情况
test::case('toBeCamelCase - 成功', 'camelCaseString')
    ->toBeCamelCase();

// 失败情况
test::case('toBeCamelCase - 失败', 'not_camel_case')
    ->toBeCamelCase();


## 文件系统断言测试用例

### 16. toBeFile 断言

test::case('文件测试 - 改进方案')
	->before(function($value) {
		// 创建临时文件并返回路径
		$tempFile = tempnam(sys_get_temp_dir(), 'test');
		file_put_contents($tempFile, 'test content');
		return $tempFile; // 返回值会替换当前测试值
	})
	->after(function($value) {
		// 清理临时文件
		if ($value && file_exists($value)) {
			unlink($value);
		}
	})
	->toBeFile()
	->toBeReadableFile();

// 文件不存在测试用例
test::case('文件测试 - 失败', '/nonexistent/path/to/file')
	->toBeFile();  // 这将失败，符合预期


// 运行所有测试用例
function runAllAssertionTests() {
    // 基础断言
    test::case('toBe 测试', 42)
        ->toBe(42)
        ->not()->toBe(43);

    // 类型断言
    test::case('类型断言测试', 'string')
        ->toBeString()
        ->not()->toBeInt();

    // 包含断言
    test::case('包含断言测试', ['a', 'b', 'c'])
        ->toContain('b')
        ->not()->toContain('d');

    // 异常断言
    test::case('异常断言测试', function() {
        throw new RuntimeException('error');
    })->toThrow(RuntimeException::class);

    // 对象断言
    $obj = new stdClass();
    $obj->prop = 'value';
    test::case('对象断言测试', $obj)
        ->toHaveProperty('prop', 'value');

    // JSON断言
    test::case('JSON断言测试', '{"key":"value"}')
        ->json()
        ->toHaveProperty('key', 'value');

    // 字符串格式
    test::case('字符串格式测试', 'camelCase')
        ->toBeCamelCase();
}

runAllAssertionTests();

test::case('值转换测试', 'hello')
	->before(function($value) {
		// 转换值为大写
		return strtoupper($value);
	})
	->toBe('HELLO');