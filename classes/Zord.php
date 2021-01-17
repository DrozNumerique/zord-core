<?php

class Zord {

    private static $config   = [];
    private static $locales  = [];
    private static $skins    = [];
    private static $processors = [];
    private static $convmap  = [0x80, 0xffff, 0, 0xffff];
    
	private $classMap = array();
	
	public static function start() {
	    define('LOGS_FOLDER', self::liveFolder('logs'));
	    define('BUILD_FOLDER', self::liveFolder('build'));
	    foreach (array_reverse(COMPONENT_FOLDERS) as $folder) {
	        $constants = self::arrayFromJSONFile($folder.'config'.DS.'constant.json');
	        foreach($constants as $name => $value) {
	            if (!defined($name)) {
	                define($name, $value);
	            }
	        }
	    }
	    if (defined('DEFAULT_TIMEZONE')) {
	        date_default_timezone_set(DEFAULT_TIMEZONE);
	    }
	    if (isset($_SERVER['SERVER_ADDR'])) {
	        session_start();
	    }
	    spl_autoload_register([new self(), 'autoload']);
	}
	
	public function autoload($className, $rebuild = true) {
	    $classesFile = self::liveFolder('config').'classes.json';
	    if (count($this->classMap) === 0) {
	        $this->classMap = self::arrayFromJSONFile($classesFile);
	    }
	    if (isset($this->classMap[$className])) {
	        $classFile = $this->classMap[$className];
	        if (file_exists($classFile) && !self::needsUpdate($classesFile, $classFile)) {
	            require_once ($classFile);
	            return true;
	        }
	    }
	    if ($rebuild) {
	        $dir = new AppendIterator();
	        foreach(COMPONENT_FOLDERS as $folder) {
	            $path = $folder.'classes';
	            if (file_exists($path)) {
	                $dir->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)));
	            }
	        }
	        $files = new class($dir) extends FilterIterator {
	            public function accept(){
	                return pathinfo($this->current(), PATHINFO_EXTENSION) === 'php' && is_readable($this->current());
	            }
	        };
	        $this->classMap = array();
	        foreach ($files as $file) {
	            $found = false;
	            foreach(array_filter(token_get_all(file_get_contents($file, false)), 'is_array') as $token){
	                if($token[0] === T_INTERFACE || $token[0] === T_CLASS || $token[0] === T_TRAIT) {
	                    $found = true;
	                    continue;
	                }
	                if ($found && $token[0] === T_STRING){
	                    $this->classMap[$token[1]] = $file->getRealPath();
	                    $found = false;
	                }
	            }
	        }
	        file_put_contents($classesFile, self::json_encode($this->classMap));
	        return $this->autoload($className, false);
	    }
	    return false;
	}
	
	public static function getClassName($class) {
	    $extend = self::value('extend', $class);
	    $class = $extend ?? $class;
	    return !class_exists($class) || (new ReflectionClass($class))->isAbstract() ? null : $class;
	}
	
	public static function getInstance($class, ...$parameters) {
	    $class = self::getClassName($class);
	    return isset($class) ? new $class(...$parameters) : null;
	}
	
	public static function saveConfig($name, $config) {
	    if (is_array($config)) {
	        file_put_contents(self::liveFolder('config').$name.'.json', self::json_encode($config));
	        unset(self::$config[$name]);
	    }
	}
	
	public static function updateConfig($name, $update) {
	    $config = self::arrayFromJSONFile(self::liveFolder('config').$name.'.json') ?? [];
        $update($config);
	    self::saveConfig($name, $config);
	}
	
	public static function hasConfig($name) {
	    return isset(self::$config[$name]) && is_array(self::$config[$name]);
	}
	
	public static function getConfig($name = null) {
	    if (isset($name)) {
	        if (!self::hasConfig($name)) {
	            self::$config[$name] = [];
	            foreach (COMPONENT_FOLDERS as $folder) {
	                self::$config[$name] = self::array_merge(self::$config[$name], self::arrayFromJSONFile($folder.'config'.DS.$name.'.json'), false, $name);
	            }
	        }
	        return self::$config[$name];
	    } else {
	        return self::$config;
	    }
	}
	
	public static function value($name, $key, $def = null) {
	    $value = null;
	    if (is_scalar($key)) {
	        $value = self::value($name, [$key]);
	    } else if (is_array($key)) {
	        $value = self::getConfig($name);
	        foreach($key as $id) {
	            if (is_array($value) && isset($value[$id])) {
	                $value = $value[$id];
	            } else {
	                $value = null;
	                break;
	            }
	        }
	    }
	    return isset($value) ? $value : $def;
	}
	
	public static function getLocale($target, $lang = DEFAULT_LANG) {
	    if (!isset(self::$locales[$target][$lang])) {
	        $locale = array();
	        foreach (COMPONENT_FOLDERS as $folder) {
	            foreach (['', DEFAULT_LANG.DS, $lang.DS] as $variant) {
	                $locale = self::array_merge($locale, self::arrayFromJSONFile($folder.'locales'.DS.$variant.$target.'.json'), true);
	            }
	        }
	        self::$locales[$target][$lang] = json_decode(json_encode($locale));
	    }
	    return self::$locales[$target][$lang];
	}
	
	public static function getSkin($context = 'default') {
	    if (!isset(self::$skins[$context])) {
	        $skin = [];
	        foreach (COMPONENT_FOLDERS as $folder) {
	            $skin = self::array_merge($skin, self::arrayFromJSONFile($folder.'config'.DS.'skin.json'));
	            $skin = self::array_merge($skin, self::arrayFromJSONFile($folder.'skins'.DS.$context.DS.'skin.json'));
	        }
	        self::$skins[$context] = json_decode(self::json_encode($skin));
	    }
	    return self::$skins[$context];
	}
	
	public static function getProcessor($name) {
	    if (!isset(self::$processors[$name])) {
	        $document = new DOMDocument();
	        $found = false;
	        foreach(array_reverse(COMPONENT_FOLDERS) as $folder) {
	            $xsl = $folder.'xml'.DS.$name.'.xsl';
	            if (file_exists($xsl)) {
	                $document->load($xsl);
	                $found = true;
	                break;
	            }
	        }
	        if ($found) {
	            $processor = new XSLTProcessor();
	            $processor->registerPHPFunctions();
	            $processor->importStyleSheet($document);
	            self::$processors[$name] = $processor;
	        }
	    }
	    if (isset(self::$processors[$name])) {
	        return self::$processors[$name];
	    } else {
	        return null;
	    }
	}
	
	public static function makeFolders($folders, $path = null) {
	    if ($folders && is_array($folders)) {
	        foreach ($folders as $folder => $subs) {
	            if ($path) {
	                $folder = $path.DS.$folder;
	            }
	            if (!file_exists($folder)) {
	                mkdir($folder);
        	    }
        	    self::makeFolders($subs, $folder);
	        }
	    }
	}
	
	public static function copyRecursive($source, $target) {
	    $dir = opendir($source);
	    if (!file_exists($target)) {
	       mkdir($target);
	    }
	    while (false !== ($file = readdir($dir))) {
	        if (($file != '.') && ($file != '..')) {
	            if (is_dir($source.DS.$file)) {
	                self::copyRecursive($source.DS.$file, $target.DS.$file);
	            } else {
	                copy($source.DS.$file, $target.DS.$file);
	            }
	        }
	    }
	    closedir($dir);
	}
	
	public static function deleteRecursive($path) {
	    if (file_exists($path)) {
	        if (is_dir($path)) {
	            foreach(scandir($path) as $item ) {
	                if ($item != '.' && $item != '..') {
	                    self::deleteRecursive($path.DS.$item );
	                }
	            }
	            rmdir($path);
	        } else if(is_file($path)) {
	            unlink($path);
	        }
	    }
	}
	
	public static function listRecursive($path, $base = null) {
	    $list = [];
	    if ($base == null) $base = $path;
	    if (file_exists($path) && is_dir($path)) {
	        foreach(scandir($path) as $item ) {
	            if ($item != '.' && $item != '..') {
	                $list[substr($path.'/'.$item, strlen($base.'/'))] = $path.'/'.$item;
	                if (is_dir($path.'/'.$item)) {
	                   $list = array_merge($list, self::listRecursive($path.'/'.$item, $base));
	                }
	            }
	        }
	    }
	    return $list;
	}
	
	public static function addRecursive($zip, $path, $sub = null, $excludes = ['php']) {
	    $entries = [];
	    foreach (self::listRecursive($path) as $relative => $absolute) {
   	        if (!empty($sub)) $relative = $sub.'/'.$relative;
   	        if (is_dir($absolute)) {
   	            $zip->addEmptyDir($relative);
   	        } else {
   	            $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
   	            if (!in_array($extension, $excludes) && !empty(self::value('content', $extension))) {
   	                $zip->addFile($absolute, $relative);
   	                $entries[] = $relative;
   	            }
   	        }
	    }
	    return $entries;
	}
	
	public static function resetFolder($path, $create = true) {
	    self::deleteRecursive($path);
	    if ($create) {
	       mkdir($path, 0777, true);
	    }
	}
	
	public static function timestamp($format = LOG_DATE_FORMAT, $timezone = DEFAULT_TIMEZONE) {
	    return DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->setTimezone(new DateTimeZone($timezone))->format($format);
	}
	
	public static function log($msg, $fileName = 'system') {    
	    $logFile = LOGS_FOLDER.$fileName.'.log';
	    $fileSize = 0;
	    $content = '';
	    if (file_exists($logFile)) {
	        $fileSize = filesize($logFile);
	        $content = file_get_contents($logFile);
	    }
	    if ($fileSize > MAX_LOG_SIZE) {
	        $countFiles = count(glob($logFile . "*")) - 1;
	        $compressFile = $logFile.'.'.$countFiles.'.gz';
	        while (file_exists($compressFile)) {
	            $compressFile = $logFile.'.'.($countFiles ++).'.gz';
	        }
	        file_put_contents("compress.zlib://$compressFile", $content);
	        unlink($logFile);
	        $content = '';
	    }
	    if (!is_string($msg)) {
	        $msg = var_export($msg, true);
	    }
	    $content .= self::timestamp().' ';
	    $backtrace = debug_backtrace(false);
	    if (isset($backtrace[0]['file']) && isset($backtrace[0]['line'])) {
	        $content .= $backtrace[0]['file'].':'.$backtrace[0]['line'].' ';
	    }
	    $content .= $msg.PHP_EOL;
	    file_put_contents($logFile, $content);
	}
	
	public static function defineLang() {
	    $lang = null;
	    if (isset($_SESSION['__ZORD__']['___LANG___'])) {
	        $lang = $_SESSION['__ZORD__']['___LANG___'];
	    }
	    if (isset($_REQUEST['lang'])) {
	        $lang = $_REQUEST['lang'];
	    }
	    if (!isset($lang)) {
	        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
       	        $lang_parse = [];
       	        preg_match_all(
       	            '/([a-z]{1,8}(-[a-z]{1,8})*)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
       	            $_SERVER["HTTP_ACCEPT_LANGUAGE"],
       	            $lang_parse
       	        );
       	        $langs = $lang_parse[1];
       	        $ranks = $lang_parse[4];	        
       	        $lang2pref = array();
       	        for ($i = 0 ; $i < count($langs) ; $i++) {
       	            $expLang = explode("-", $langs[$i]);
       	            if (count($expLang) > 1) {
       	                $lang2pref[$expLang[0].'-'.strtoupper($expLang[1])] = (float) (!empty($ranks[$i]) ? $ranks[$i] : 1);
       	            }
       	        }	        
       	        $cmpLangs = function ($a, $b) use ($lang2pref) {
      	            if ($lang2pref[$a] > $lang2pref[$b]) {
       	                return -1;
       	            } else if ($lang2pref[$a] < $lang2pref[$b]) {
       	                return 1;
       	            } else if (strlen($a) > strlen($b)) {
       	                return -1;
       	            } else if (strlen($a) < strlen($b)) {
       	                return 1;
      	            } else {
       	                return 0;
       	            }
       	        };	        
       	        uksort($lang2pref, $cmpLangs);
       	        reset($lang2pref);
       	        $lang = key($lang2pref);
       	        $zordLangs = array_keys(self::getConfig('lang'));
       	        if (!in_array($lang, $zordLangs)) {
       	            $lang = DEFAULT_LANG;
       	        }
    	    } else {
    	        $lang = DEFAULT_LANG;
    	    }
	    }
	    $_SESSION['__ZORD__']['___LANG___'] = $lang;
        return $lang;
	}
	
	public static function is_associative($array) {
	    return !empty($array) && array_keys($array) !== range(0, count($array) - 1);
	}
	
	public static function array_merge($first, $second, $reset = false, $base = null) {
	    if (is_array($first) && is_array($second)) {
    	    foreach ($second as $key => $value) {
    	        if (is_array($value) && self::is_associative($value)) {
    	            if (isset($value['__RESET__'])) {
    	                $reset = true;
    	                $value = $value['__RESET__'];
    	            } else {
    	                if (!isset($first[$key])) {
    	                    $first[$key] = [];
    	                }
    	                $value = self::array_merge($first[$key], $value, $reset, isset($base) ? $base.'.'.$key : null);
    	            }
    	        }
    	        if (!self::is_associative($second)) {
    	            $first[] = $value;
    	        } else {
    	            if ($value === '__UNSET__') {
    	                unset($first[$key]);
    	            } else {
    	                if (!$reset && isset($first[$key]) && self::matches($first[$key], $value)) {
    	                    foreach ($value as $entry) {
    	                        $first[$key][] = $entry;
    	                    }
    	                } else {
    	                    $first[$key] = $value;
    	                }
    	            }
    	        }
    	    }
	    }
	    if ($first) {
	        return $first;
	    } else if ($second) {
	        return $second;
	    } else {
	        return [];
	    }
	}
	
	public static function matches($first, $second) {
	    return is_array($first)  && !self::is_associative($first) &&
	           is_array($second) && !self::is_associative($second);
	}
	
	public static function getContextURL($name, $index = 0, $target = '', $lang = null, $session = null) {
	    $urls = self::value('context', [$name,'url']);
	    $url = null;
	    if (is_array($urls) && $index < count($urls)) {
	        $host = $urls[$index]['host'];
	        $path = $urls[$index]['path'];
	        $scheme = isset($urls[$index]['secure']) && $urls[$index]['secure'] ? 'https' : 'http';
	        $url = $scheme.'://'.$host.($path != '/' ? $path : '').$target;
	        if ($lang) {
	            $url = $url.(!strpos($url, '?') ? '?' : '&').'lang='.$lang;
	        }
	        if ($session) {
	            $url = $url.(!strpos($url, '?') ? '?' : '&').User::$ZORD_SESSION.'='.$session;
	        }
	    }
	    return $url;
	}
	
	public static function objectToArray($object) {
	    if (!is_object($object) && !is_array($object)) {
	        return $object;
	    }
	    if (is_object($object)) {
	        $object = get_object_vars($object);
	    }
	    return array_map(array(get_class(), __FUNCTION__), $object);
	}
	
	public static function arrayFromJSONFile($path) {
	    if (file_exists($path)) {
	       return self::objectToArray(json_decode(file_get_contents($path)));
	    } else {
	        return [];
	    }
	}
	
	public static function json_encode($val, $pretty = true) {
	    if (is_array($val)) {
    	    array_walk_recursive(
    	        $val,
    	        function (&$item, $key) {
    	            if (is_string($item)) {
    	                $item = mb_encode_numericentity($item, self::$convmap, 'UTF-8');
    	            }
    	        }
    	    );
	    } else {
	        $val = mb_encode_numericentity($val, self::$convmap, 'UTF-8');
	    }
	    $val = mb_decode_numericentity(json_encode($val, $pretty ? JSON_PRETTY_PRINT : null), self::$convmap, 'UTF-8');
	    return preg_replace_callback(
	        '#:"(\d+)"#s',
	        function($matches) {
	            if (substr($matches[1], 0, 1) == "0") {
	                return ':"'.$matches[1].'"';
	            } else {
	                return ':'.$matches[1];
	            }
	        },
	        $val
	    );
	}
	
	public static function substitute($raw, $values, $base = '') {
	    if (is_array($values)) {
    	    foreach($values as $key => $value) {
    	        if (is_array($value)) {
    	            $raw = self::substitute($raw, $value, $base.$key.'.');
    	        } else if (is_scalar($value)) {
    	            $raw = str_replace('${'.$base.$key.'}', $value, $raw);
    	        }
    	    }
	    } else if (is_object($values)) {
	        foreach(get_object_vars($values) as $key => $value) {
	            if (is_object($value)) {
	                $raw = self::substitute($raw, $value, $base.$key.'.');
	            } else if (is_scalar($value)) {
	                $raw = str_replace('$['.$base.$key.']', $value, $raw);
	            }
	        }
	    }
	    return $raw;
	}
	
	public static function resolve($raw, $models, $locale) {
	    return self::substitute(self::substitute($raw, $models), $locale);
	}
	
	public static function execute($strategy, $command, $params = []) {
	    $command = self::substitute($command, $params);
	    $result = null;
	    switch ($strategy) {
	        case 'proc_open': {
	            $desc  = $params['desc']  ?? [["pipe", "r"],["pipe", "w"],["pipe", "w"]];
	            $pipes = $params['pipes'] ?? [];
	            $cwd   = $params['cwd']   ?? null;
	            $env   = $params['env']   ?? null;
	            $opt   = $params['opt']   ?? null;
	            $proc = proc_open($command, $desc, $pipes, $cwd, $env, $opt);
	            if (is_resource($proc)) {
	                $output = stream_get_contents($pipes[1]);
	                //$errors = stream_get_contents($pipes[2]);
	                foreach ($pipes as $pipe) {
	                    fclose($pipe);
	                }
	                if (proc_close($proc) == 0) {
	                    $result = trim($output);
	                }
	            }
	            break;
	        }
	        case 'popen': {
	            $proc = popen($command, $params['mode'] ?? 'w');
	            if (is_resource($proc)) {
	                $result = stream_get_contents($proc);
	                pclose($proc);
	            }
	            break;
	        }
	        case 'exec': {
	            $output = [];
	            $exitCode = 0;
	            exec($command, $output, $exitCode);
	            if ($exitCode == 0) {
	                $result = implode(PHP_EOL, $output);
	            }
	            break;
	        }
	    }
	    return isset($result) ? $result : false;
	}
	
	public static function str_pad(
	    $input,
	    $pad_length,
	    $pad_string = " ",
	    $pad_style = STR_PAD_RIGHT,
	    $encoding = "UTF-8")
	{
	    return str_pad(
	        $input,
	        strlen($input) - mb_strlen($input, $encoding) + $pad_length,
	        $pad_string,
	        $pad_style);
	}
	
	public static function chunkIP($ip) {
	    $include = TRUE;
	    if (substr($ip, 0, 1) === '~') {
	        $ip = substr($ip, 1);
	        $include = FALSE;
	    }
	    $cidr = explode('/', $ip);
	    $ip = $cidr[0];
	    $mask = count($cidr) > 1 ? (int) $cidr[1] : (strpos($ip, ':') !== false ? 128 : 32);
	    return array(
	        'ip' => $ip,
	        'mask' => $mask,
	        'include' => $include
	    );
	}
	
	public static function explodeIP($block) {
	    $list = array();
	    $values = array();
	    $ranges = explode('.', $block);
	    $level = 0;
	    foreach ($ranges as $range) {
	        $values[$level] = array();
	        $segments = explode('|', $range);
	        foreach ($segments as $segment) {
	            if ($segment == '*') {
	                $min = $level == 3 ? 1 : 0;
	                $max = $level == 3 ? 254 : 255;
	            } else {
	                $limits = explode('-', $segment);
	                $min = $limits[0];
	                $max = count($limits) == 1 ? $limits[0] : $limits[1];
	            }
	            for ($value = $min ; $value <= $max ; $value++) {
	                $values[$level][] = $value;
	            }
	        }
	        $level++;
	    }
	    foreach ($values[0] as $A) {
	        foreach ($values[1] as $B) {
	            foreach ($values[2] as $C) {
	                foreach ($values[3] as $D) {
	                    $list[] = $A.'.'.$B.'.'.$C.'.'.$D;
	                }
	            }
	        }
	    }
	    return $list;
	}
	
	public static function needsUpdate($targets, $sources) {
	    if (is_string($targets)) {
	        $targets = [$targets];
	    }
	    if (is_string($sources)) {
	        $sources = [$sources];
	    }
	    foreach ($targets as $target) {
	        if (!file_exists($target)) {
    	        return true;
    	    }
    	    foreach ($sources as $source) {
    	        if (file_exists($source) && (filemtime($source) > filemtime($target))) {
           	        return true;
       	        }
       	    }
	    }
	    return false;
	}
	
	public static function template($name, $context = null, $lang = null) {
	    if (is_string($name)) {
    	    $variants = [];
    	    if (is_string($context) && is_string($lang)) {
    	        $variants[] = DS.$context.DS.$lang;
    	    }
    	    if (is_string($context)) {
    	        $variants[] = DS.$context;
    	    }
    	    if (is_string($lang)) {
    	        $variants[] = DS.$lang;
    	    }
    	    $variants[] = '';
    	    $name = str_replace('/', DS, $name);
    	    foreach(array_reverse(COMPONENT_FOLDERS) as $folder) {
    	        foreach($variants as $variant) {
    	            $template = $folder.'templates'.$name.$variant.'.php';
    	            if (file_exists($template)) {
    	                return $template;
    	            }
    	        }
    	    }
	    }
	    return null;
	}
	
	public static function getLocaleValue($key, $locale, $lang = DEFAULT_LANG, $altKeys = null, $alt = null) {
	    if (isset($altKeys) && is_array($altKeys) && in_array($key, $altKeys) && isset($alt) && isset($alt->$key)) {
	        return $alt->$key;
	    }
	    $value = $key;
	    if (isset($key) && isset($locale)) {
	        if (isset($locale[$key])) {
	           $value = $locale[$key];
	        }
	        if (is_array($value)) {
	            if (isset($value[$lang])) {
	                $value = $value[$lang];
	            } else if (isset($value[DEFAULT_LANG])) {
	                $value = $value[DEFAULT_LANG];
	            }
	        }
	    }
	    return $value;
	}
	
	public static function getLocaleValues($locale, $lang = DEFAULT_LANG, $altKeys = null, $alt = null) {
	    $values = [];
	    if (isset($locale) && is_array($locale)) {
    	    foreach (array_keys($locale) as $key) {
    	        $values[$key] = self::getLocaleValue($key, $locale, $lang);
    	    }
	    }
	    if (isset($altKeys) && is_array($altKeys) && isset($alt)) {
    	    foreach ($altKeys as $key) {
    	        $values[$key] = self::getLocaleValue($key, $locale, $lang, $altKeys, $alt);
    	    }
    	}
	    return $values;
	}
	
	public static function arrayToJS($array, $num = 2, $root = true) {
	    $indent = str_repeat("\t", $num);
	    $sequential = $array === [] || !self::is_associative($array);
	    $result = ($sequential ? '[' : '{')."\n";
	    $index = 0;
	    foreach ($array as $key => $value) {
	        $result .= $indent."\t".($sequential ? '' : "'".$key."':");
	        if (is_string($value)) {
	            $result .= "'";
	        }
	        if (is_array($value)) {
	            $result .= self::arrayToJS($value, $num + 1);
	        } else if (is_string($value)) {
	            $result .= str_replace("'", "\\'", $value);
	        } else if (is_numeric($value)) {
	            $result .= $value;
	        } else if (is_bool($value)) {
	            $result .= ($value) ? 'true' : 'false';
	        } else if (!isset($value)) {
	            $result .= 'undefined';
	        }
	        if (is_string($value)) {
	            $result .= "'";
	        }
	        $result .=  (($index < count($array) - 1) ? ',' : '')."\n";
	        $index++;
	    }
	    $result .= $indent.($sequential ? ']' : '}');
	    return $result;
	}
	
	public static function changeNodeName($node, $name, $attributes = []) {
	    $changed = $node->ownerDocument->createElement($name);
	    if ($node->attributes->length) {
	        foreach ($node->attributes as $attribute) {
	            $changed->setAttribute($attribute->nodeName, $attribute->nodeValue);
	        }
	    }
	    foreach($attributes as $name => $value) {
	        $changed->setAttribute($name, $value);
	    }
	    while ($node->firstChild) {
	        $changed->appendChild($node->firstChild);
	    }
	    $node->parentNode->replaceChild($changed, $node);
	    return $changed;
	}
	
	public static function parameter($parameters, $name, $default = null) {
	    if (isset($parameters[$name])) {
	        return $parameters[$name];
	    } else if (defined($name)) {
	        return constant($name);
	    } else if (isset($default)) {
	        return $default;
	    } else {
	        return null;
	    }
	}
	
	public static function portalTitle($context, $lang) {
	    $title = self::value('context', [$context,'title']);
	    if (is_string($title)) {
	        return $title;
	    } else if (is_array($title)) {
	        if (isset($title[$lang])) {
	            return $title[$lang];
	        } else if (isset($title[DEFAULT_LANG])) {
	            return $title[DEFAULT_LANG];
	        } else {
	            return DEFAULT_TITLE;
	        }
	    }
	}
	
	public static function getComponentPath($path) {
	    foreach (array_reverse(COMPONENT_FOLDERS) as $folder) {
	        if (file_exists($folder.$path)) {
	            return $folder.$path;
	        }
	    }
	    return null;
	}
	
	public static function liveFolder($path) {
	    $folders = COMPONENT_FOLDERS;
	    $folder = end($folders).$path.(substr($path, -1) !== DS ? DS : '');
	    if (!file_exists($folder)) {
	        mkdir($folder, 0755, true);
	    }
	    return $folder;
	}
	
	public static function utf8($string) {
	    $encoding = mb_detect_encoding($string, mb_detect_order(), true);
	    if ($encoding !== 'UTF-8') {
	        return iconv($encoding, 'UTF-8//IGNORE', $string);
	    } else {
	        return $string;
	    }
	}
	
	public static function collapse($string, $separator = true) {
	    $string = str_replace(
	        ['Š','Œ', 'Ž','š','œ' ,'ž','Ÿ','¥','µ','À','Á','Â','Ã','Ä','Å','Æ' ,'Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','ß','à','á','â','ã','ä','å','æ' ,'ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ÿ','Ὅ','μ','Ł','ą'],
	        ['S','OE','Z','s','oe','z','Y','Y','u','A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','s','a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','d','n','o','o','o','o','o','o','u','u','u','u','y','y','O','m','L','a'],
	        self::utf8($string)
	    );
	    $string = trim(strtolower($string));
	    return $separator ? str_replace([' ',"\u{00a0}",'"',"'",',',';','.','-','(',')','[',']',':','/','!','?','¿','+','=','*','#','«','»','„','“','”','‚','‘','’','<','>','‹','›','`','…'], '', $string) : $string;
	}
	
	public static function sort(&$array, $values = true) {
	    if ($values) {
    	    uasort($array, function($first, $second) {
    	        return self::collapse($first) <=> self::collapse($second);
    	    });
	    } else {
	        uksort($array, function($first, $second) {
	            return self::collapse($first) <=> self::collapse($second);
	        });
	    }
	}
	
	public static function html($text, $config = []) {
	    $br = $config['br'] ?? "\n";
	    $escape = $config['escape'] ?? true;
	    return implode('<br>', explode($br, $escape ? htmlspecialchars($text) : $text));
	}
	
	public static function md2html($content, $breaks = false) {
	    return Parsedown::instance()->setBreaksEnabled($breaks)->text($content);
	}
	
	public static function trunc($string, $maxlength) {
	    return mb_substr($string, 0, $maxlength).(mb_strlen($string) > $maxlength ? "…" : '');
	}
	
	public static function trim($string) {
	    return isset($string) ? trim($string) : null;
	}
	
	public static function sendMail($mail) {
	    $category  = $mail['category']  ?? null;
	    $textonly  = $mail['textonly']  ?? false;
	    $template  = $mail['template']  ?? '/mail';
	    $principal = $mail['principal'] ?? null;
	    $models    = $mail['models']    ?? [];
	    $controler = $mail['controler'] ?? null;
	    $locale    = $mail['locale']    ?? null;
	    $post      = $mail['post']      ?? null;
	    $text      = $mail['text']      ?? null;
	    $models['mail'] = $mail;
	    if (isset($models['context'])) {
	        $context = Zord::value('context', $models['context']);
	        $config = $context['url'][0];
	        $host = $config['host'];
	        $scheme = ($config['secure'] ?? false) ? 'https' : 'http';
	        $baseURL = $scheme.'://'.$host.($config['path'] == '/' ? '' : $config['path']);
	        $models = array_merge($models, [
	            'context'  => $models['context'],
	            'host'     => $host,
	            'scheme'   => $scheme,
	            'indexURL' => 0,
	            'baseURL'  => $baseURL,
	            'skin'     => Zord::getSkin($models['context'])
	        ]);
	    }
	    $html      = $textonly === true ? null : (new View($template, $models, $controler, $locale))->render();
	    if (is_callable($post)) {
	        $html = isset($html) ? call_user_func($post, $html, $models, $controler, $locale) : call_user_func($post, $models, $controler, $locale);
	    }
	    if (is_callable($text)) {
	        $text = isset($html) ? call_user_func($text, $html, $models, $controler, $locale) : call_user_func($text, $models, $controler, $locale);
	    }
	    $text = $text ?? (isset($html) ? self::text($html) : '');
	    $body = $html ?? $text;
	    $mailer = new PHPMailer();
	    $mailer->IsHTML(isset($html));
	    $mailer->CharSet = 'UTF-8';
	    //$mail->Encoding = 'base64';
	    $mailer->SetFrom(WEBMASTER_MAIL_ADDRESS, WEBMASTER_MAIL_NAME);
	    if (isset($principal)) {
	        $mail['recipients']['to'][$principal['email']] = $principal['name'];
	    }
	    foreach ($mail['recipients'] as $kind => $recipients) {
	        foreach ($recipients as $email => $name) {
	            switch ($kind) {
	                case 'to': {
	                    $mailer->AddAddress($email, $name);
	                    break;
	                }
	                case 'cc': {
	                    $mailer->AddCC($email, $name);
	                    break;
	                }
	                case 'bcc': {
	                    $mailer->AddBCC($email, $name);
	                    break;
	                }
	            }
	        }
	    }
	    if (isset($mail['reply'])) {
	        $mailer->AddReplyTo($mail['reply']['email'], $mail['reply']['name'] ?? '');
	    }
	    $mailer->Subject = $mail['subject'];
	    $mailer->Body = $body;
	    if (isset($html)) {
	        $mailer->AltBody = $text;
	    }
	    if (MAIL_TRACE && isset($category)) {
	        $base = self::liveFolder(MAIL_FOLDER).$category.DS.self::timestamp('Y.m.d.H.i.s.u').DS;
	        mkdir($base, 0777, true);
	        file_put_contents($base.'body.'.(isset($html) ? 'html' : 'txt'), $body);
	        file_put_contents($base.'subject.txt', $mail['subject']);
	        file_put_contents($base.'recipients.json', self::json_encode($mail['recipients']));
	    }
	    return $mailer->Send() === false ? $mailer->ErrorInfo : true;
	}
	
	public static function mark($content, $begin = VIEW_MARK_BEGIN, $end = VIEW_MARK_END) {
	    return $begin.$content.$end;
	}
	
	public static function text($html) {
	    $begin = false;
	    $body = '';
	    foreach(explode("\n", html_entity_decode($html)) as $line) {
	        $line = trim($line);
	        if ($line == self::mark(MAIL_TEXT_END)) {
	            $begin = false;
	        }
	        if ($begin) {
	            $body .= $line."\n";
	        }
	        if ($line == self::mark(MAIL_TEXT_BEGIN)) {
	            $begin = true;
	        }
	    }
	    $text = '';
	    foreach(explode("\n", strip_tags($body)) as $line) {
	        $line = trim($line);
	        if (!empty($line)) {
	            $text .= $line."\n";
	        }
	    }
	    return $text;
	}
	
    public static function urlencode($path) {
        $path = explode('/', $path);
        array_walk($path, function(&$element) {
            if (!empty($element)) {
                $element = urlencode($element);
            }
        });
        $path = implode('/', $path);
        return $path;
    }
    
    public static function realPath($path) {
        return realpath(str_replace('~', $_SERVER['HOME'], $path));
    }
    
    public static function price($amount, $lang = DEFAULT_LANG, $currency = DEFAULT_CURRENCY) {
        $locale = str_replace('-', '_', $lang);
        $format = numfmt_create($locale, NumberFormatter::CURRENCY);
        return numfmt_format_currency($format, $amount, $currency);
    }
    
    public static function date($date, $lang = DEFAULT_LANG, $format = null) {
        $locale = str_replace('-', '_', $lang);
        $date = date_create($date);
        return datefmt_format_object($date, $format, $locale);
    }
    
    public static function country($code, $lang = DEFAULT_LANG) {
        $locale = self::getLocale('country', $lang);
        return isset($locale->$code) ? $locale->$code : null;
    }
    
    public static function encrypt($data, $keyfile) {
        $crypted = null;
        if (openssl_public_encrypt($data, $crypted, openssl_pkey_get_public(file_get_contents($keyfile)))) {
            return $crypted;
        }
        return false;
    }
    
    public static function decrypt($data, $keyfile) {
        $decrypted = null;
        if (openssl_private_decrypt($data, $decrypted, openssl_pkey_get_private(file_get_contents($keyfile)))) {
            return $decrypted;
        }
        return false;
    }
    
    public static function token($keyfile, $user, $key = null) {
        $token = uniqid($user, true);
        $crypted = self::encrypt($token, $keyfile);
        if ($crypted !== false) {
            (new UserHasTokenEntity())->create([
                'user'  => $user,
                'key'   => $key,
                'token' => $token,
                'start' => date('Y-m-d H:i:s')
            ]);
            return base64_encode($crypted);
        }
        return null;
    }
    
    public static function array($var) {
        if (!isset($var) || is_null($var)) {
            $var = [];
        }
        if (is_scalar($var)) {
            $var = [$var];
        }
        return $var;
    }
    
    public static function union($first, $second) {
        $first  = self::array($first);
        $second = self::array($second);
        foreach ($second as $element) {
            if (!in_array($element, $first)) {
                $first[] = $element;
            }
        }
        return $first;
    }
    
    public static function cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null) {
        if (version_compare(phpversion(), '7.3.0', 'ge')) {
            setcookie($name, $value, [
                'expires'  => $expires,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
                'SameSite' => 'Lax'
            ]);
        } else {
            setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
        }
    }
    
    public static function handleError($parameters) {
        set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
            if (0 === error_reporting()) {
                return false;
            }
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
        try {
            if (isset($parameters['try']) && is_callable($parameters['try'])) {
                return call_user_func($parameters['try']);
            }
        } catch (ErrorException $exception) {
            if (isset($parameters['catch']) && is_callable($parameters['catch'])) {
                return call_user_func($parameters['catch'], $exception);
            }
        } finally {
            if (isset($parameters['finally']) && is_callable($parameters['finally'])) {
                call_user_func($parameters['finally']);
            }
            restore_error_handler();
        }
    }
    
    public static function content($name, $lang, $content = null) {
        $folder = Zord::liveFolder('contents'.DS.$name.DS.$lang);
        if (isset($content)) {
            $date = date('YmdHis');
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
            return file_put_contents($folder.$date.'.md', $content) !== false ? Zord::date($date, $lang) : null;
        }
        $contents = glob($folder.'*.md');
        if (!empty($contents)) {
            $content = max($contents);
        } else {
            foreach(array_reverse(COMPONENT_FOLDERS) as $folder) {
                $content = $folder.'contents'.DS.$lang.DS.$name.'.md';
                if (!file_exists($content)) {
                    $content = $folder.'contents'.DS.$name.'.md';
                }
                if (!file_exists($content)) {
                    $content = $folder.'contents'.DS.DEFAULT_LANG.DS.$name.'.md';
                }
                if (!file_exists($content)) {
                    $content = null;
                }
            }
        }
        return $content;
    }
}
