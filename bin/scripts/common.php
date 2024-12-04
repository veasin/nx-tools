<?php
function title($string): void{
	echo "\n\033[46m\033[1m ", $string, " \033[0m\n";
}

function ask($prompt = "", $def = ""): string{
	echo "\n\033[33m", $prompt;
	if(!empty($def)) echo ", default=\033[34m$def\033[0m: \033[0m";
	$r = readline();
	return empty($r) ? $def : $r;
}

function choice($prompt = "", $options = ['yes', 'no'], $multiple = false){
	$_completion = [];
	$_prompt = [];
	$_promptC = [];
	$_keys = [];
	foreach($options as $_key => $o){
		if(is_numeric($_key)){
			$_completion[] = $o;
			$_prompt[] = [$o, null];
			$_promptC[$o] = null;
		}
		else{
			$_keys[$_key] = $o;
			$_completion[] = $_key;
			$_completion[] = $o;
			$_prompt[] = [$o, $_key];
			if(!isset($_promptC[$o])) $_promptC[$o] = [];
			$_promptC[$o][] = $_key === '' ? 'ENTER' : $_key;
		}
	}
	if(empty($_keys)){
		foreach($options as $_idx => $o){
			if(!isset($_keys[$o[0]])){
				$_keys[$o[0]] = $o;
				$_prompt[$_idx] = [$o, $o[0]];
				if(!isset($_promptC[$o])) $_promptC[$o] = [];
				$_promptC[$o][] = $o[0] === '' ? 'ENTER' : $o[0];
			}
		}
	}
	readline_completion_function(fn() => $_completion);
	echo "\n\033[33m", $prompt, "\033[0m\n";
	$_selected = [];
	if(is_array($multiple)){
		foreach($multiple as $item){
			$idx = array_search($item, $options);
			if($idx !== false){
				$_selected[$idx] = true;
			}
		}
	}
	if($multiple){
		echo str_repeat("\n", count($_prompt));
	}
	while(true){
		if(!$multiple){
			echo "  " . implode(', ', array_map(function($option, $keys){
					$show = null === $keys ? null : implode(',', $keys);
					return null !== $show ? "$option\033[33m[\033[34m$show\033[33m]\033[0m" : $option;
				}, array_keys($_promptC), array_values($_promptC))) . ": ";
			$r = trim(readline());
			if(isset($_keys[$r])) return $_keys[$r];
			if(in_array($r, $options)) return $r;
		}
		else{
			echo str_repeat("\033[A", count($_prompt));
			foreach($_prompt as $_idx => $item){
				echo "  ", $_selected[$_idx] ?? false ? "\033[32m>\033[0m" : " ", " ", $_selected[$_idx] ?? false ? "\033[32m" : "", $item[0], "\033[0m", $item[1] ? "\033[33m[\033[34m$item[1]\033[33m]\033[0m"
					: "", "\n";
			}
			$r = trim(readline());
			if($r === ""){
				$selectedItems = [];
				foreach($_selected as $index => $_){
					$selectedItems[] = $options[$index];
				}
				return $selectedItems;
			}
			else{
				if(isset($_keys[$r])) $idx = array_search($_keys[$r], $options);
				else $idx = array_search($r, $options);
				if($idx !== false){
					if(isset($_selected[$idx])) unset($_selected[$idx]);
					else$_selected[$idx] = true;
				}
				echo "\033[A\033[K";
			}
		}
	}
}

function updateComposer($update = []): false|int{
	$file = "{$GLOBALS['_project']}/composer.json";
	$composer = json_decode(file_get_contents($file), true);
	foreach($update as $key => $set){
		switch($key){
			case 'require':
			case 'scripts':
				foreach($set as $rule => $value){
					$composer[$key][$rule] = $value;
				}
				break;
			case 'autoload':
				if(!isset($composer[$key])) $composer[$key] = [];
				if(!isset($composer[$key]['psr-4'])) $composer[$key]['psr-4'] = [];
				foreach($set['psr-4'] as $rule => $value){
					$composer[$key]['psr-4'][$rule] = $value;
				}
				break;
			default:
				if(!isset($composer[$key])) $composer[$key] = $set;
				break;
		}
	}
	//$out =array_merge_recursive($composer, $update);
	return file_put_contents($file, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function put_php_file($file, $content): false|int{
	$php = "<?php\n$content";
	return file_put_contents($file, $php);
}

function parseClasses($file): array{
	$tokens = token_get_all(file_get_contents($file));
	//var_dump($tokens);
	//die();
	$set = [\T_STRING => true, \T_NS_SEPARATOR => true];
	if(\defined('T_NAME_QUALIFIED')) $set[T_NAME_QUALIFIED] = true;
	$classes = [];
	$traits = [];
	$trait_full_names = [];
	$trait_names = [];
	$namespace = '';
	for($i = 0; isset($tokens[$i]); ++$i){
		$token = $tokens[$i];
		if(!isset($token[1])) continue;
		$class = '';
		switch($token[0]){
			case \T_NAMESPACE:
				$namespace = '';
				while(isset($tokens[++$i][1])){
					if(isset($set[$tokens[$i][0]])) $namespace .= $tokens[$i][1];
				}
				$namespace .= '\\';
				break;
			case \T_CLASS:
			case \T_INTERFACE:
			case \T_TRAIT:
				$isClassConstant = false;
				for($j = $i - 1; $j > 0; --$j){
					if(!isset($tokens[$j][1])) break;
					if(\T_DOUBLE_COLON === $tokens[$j][0]){
						$isClassConstant = true;
						break;
					}
					elseif(!\in_array($tokens[$j][0], [\T_WHITESPACE, \T_DOC_COMMENT, \T_COMMENT])) break;
				}
				if($isClassConstant) break;
				while(isset($tokens[++$i][1])){
					$t = $tokens[$i];
					if(\T_STRING === $t[0]) $class .= $t[1];
					elseif('' !== $class && \T_WHITESPACE === $t[0]) break;
				}
				$classes[] = ltrim($namespace . $class, '\\');
				break;
			case \T_USE:
				while($tokens[++$i] !== ';'){
					$t = $tokens[$i];
					if(\T_NAME_FULLY_QUALIFIED === $t[0]) $traits[] = $t[1];
					if(\T_NAME_QUALIFIED === $t[0]) $trait_full_names[] = $t[1];
					if(\T_STRING === $t[0]) $trait_names[] = $t[1];
				}
				//$classes[]=ltrim($namespace.$class, '\\');
				break;
		}
	}
	$f = function($name, $fulls){
		foreach($fulls as $full){
			if(str_ends_with($full, "\\$name")) return "\\$full";
		}
		return null;
	};
	foreach($trait_names as $name){
		$f_name = $f($name, $trait_full_names);
		if($f_name) $traits[] = $f_name;
	}
	return [$namespace, $classes, $traits];
}

function plural($word): string{
	if(str_ends_with($word, 'y') && substr($word, -2, 1) !== 'a' && substr($word, -2, 1) !== 'e' && substr($word, -2, 1) !== 'i' && substr($word, -2, 1) !== 'o'
		&& substr($word, -2, 1) !== 'u') return substr($word, 0, -1) . 'ies';
	elseif(str_ends_with($word, 's') || str_ends_with($word, 'x') || str_ends_with($word, 'z') || str_ends_with($word, 'ch') || str_ends_with($word, 'sh')) return $word . 'es';
	else return $word . 's';
}