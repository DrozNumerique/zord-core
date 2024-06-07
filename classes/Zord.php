<?php

class Zord {

    private static $config   = [];
    private static $locales  = [];
    private static $skins    = [];
    private static $processors = [];
    private static $convmap  = [0x80, 0xffff, 0, 0xffff];
    
    private static $romans = array(
        'M'  => 1000,
        'CM' => 900,
        'D'  => 500,
        'CD' => 400,
        'C'  => 100,
        'XC' => 90,
        'L'  => 50,
        'XL' => 40,
        'X'  => 10,
        'IX' => 9,
        'V'  => 5,
        'IV' => 4,
        'I'  => 1,
    );
    
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
	    foreach (COMPONENT_FOLDERS as $folder) {
	        if (file_exists($folder.'vendor/autoload.php')) {
	            require_once($folder.'vendor/autoload.php');
	        }
	    }
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
	
	public static function getConfig($name = null, $reload = false) {
	    $context = $_SESSION[Controler::$ZORD_CONTEXT] ?? false;
	    if (isset($name)) {
	        if ($reload || !self::hasConfig($name)) {
	            self::$config[$name] = self::loadConfig($name, $context);
	        }
	        return self::$config[$name];
	    } else {
	        return self::$config;
	    }
	}
	
	public static function loadConfig($name, $context) {
	    $config = [];
	    foreach (COMPONENT_FOLDERS as $folder) {
	        $config = self::array_merge($config, self::arrayFromJSONFile($folder.'config'.DS.$name.'.json'), false, $name);
	        if ($context) {
	            $config = self::array_merge($config, self::arrayFromJSONFile($folder.'config'.DS.$name.DS.$context.'.json'), false, $name);
	        }
	    }
	    return $config;
	}
	
	public static function value($name, $key, $def = null, $context = null) {
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
	
	public static function getLocale($target, $lang = DEFAULT_LANG, $array = false) {
	    $context = $_SESSION[Controler::$ZORD_CONTEXT] ?? false;
	    if (!isset(self::$locales[$target][$lang])) {
	        self::$locales[$target][$lang] = json_decode(json_encode(self::loadLocale($target, $lang, $context)));
	    }
	    $locale = self::$locales[$target][$lang];
	    if (is_object($locale)) {
	       $locale->__TARGET__ = $target;
	    }
	    return ($array && is_object($locale)) ? self::objectToArray($locale) : $locale;
	}
	
	public static function loadLocale($target, $lang, $context) {
	    $locale = array();
	    foreach (COMPONENT_FOLDERS as $folder) {
	        $variants = DEFAULT_LANG !== $lang ? ['', DEFAULT_LANG.DS, $lang.DS] : ['', $lang.DS];
	        foreach ($variants as $variant) {
	            $locale = self::array_merge($locale, self::arrayFromJSONFile($folder.'locales'.DS.$variant.$target.'.json'), true);
	            if ($context) {
	                $locale = self::array_merge($locale, self::arrayFromJSONFile($folder.'locales'.DS.$variant.$target.DS.$context.'.json'), true);
	            }
	        }
	    }
	    return $locale;
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
    	        if ($value === '__UNSET__') {
    	            unset($first[$key]);
    	        } else {
    	            if (is_array($value) && self::is_associative($value)) {
    	                if (isset($value['__RESET__'])) {
    	                    $reset = true;
        	                $value = $value['__RESET__'];
        	            } else if (isset($value['__CONST__']) && defined($value['__CONST__'])) {
        	                $value = constant($value['__CONST__']);
        	            } else if (isset($value['__SIBLING__']) && is_string($value['__SIBLING__']) && isset($first[$value['__SIBLING__']])) {
        	                $value = $first[$value['__SIBLING__']];
        	            } else {
        	                if (!isset($first[$key])) {
        	                    $first[$key] = [];
        	                }
        	                $value = self::array_merge($first[$key], $value, false, isset($base) ? $base.'.'.$key : null);
        	            }
        	        }
        	        if (!self::is_associative($second)) {
        	            $first[] = $value;
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
	    if (isset($first)) {
	        return $first;
	    } else if (isset($second)) {
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
	    $options = JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES;
	    $options = $pretty ? $options|JSON_PRETTY_PRINT : $options;
	    $val = mb_decode_numericentity(json_encode($val, $options), self::$convmap, 'UTF-8');
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
    	    if (!is_array($raw) && $base === '') {
    	        $raw = preg_replace('/\$\{(.*)\}/', '', $raw);
    	    }
	    } else if (is_object($values)) {
	        foreach(get_object_vars($values) as $key => $value) {
	            if (is_object($value)) {
	                $raw = self::substitute($raw, $value, $base.$key.'.');
	            } else if (is_scalar($value)) {
	                $raw = str_replace('$['.$base.$key.']', $value, $raw);
	            }
	        }
	        if (!is_array($raw) && $base === '') {
	            $raw = preg_replace('/\$\[(.*)\]/', '', $raw);
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
	        case 'async':
	        case 'popen': {
	            $proc = popen($command, $strategy === 'async' ? 'w' : ($params['mode'] ?? 'r'));
	            if (is_resource($proc)) {
	                $result = $strategy === 'async' ? null : stream_get_contents($proc);
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
	
	public static function template($name, $device = null, $context = null, $lang = null) {
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
    	            foreach ([DS.$device, ''] as $_device) {
        	            $template = $folder.'templates'.$_device.$name.$variant.'.php';
        	            if (file_exists($template)) {
        	                return $template;
        	            }
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
	
	public static function arrayToJS($array, $assoc = true, $num = 2, $root = true) {
	    if (empty($array)) {
	        return $assoc ? '{}' : '[]';
	    }
	    $indent = str_repeat("\t", $num);
	    $sequential = !self::is_associative($array);
	    $result = ($sequential ? '[' : '{')."\n";
	    $index = 0;
	    foreach ($array as $key => $value) {
	        $result .= $indent."\t".($sequential ? '' : "'".$key."':");
	        if (is_string($value)) {
	            $result .= "'";
	        }
	        if (is_array($value)) {
	            $result .= self::arrayToJS($value, $assoc, $num + 1);
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
	
	public static function liveFolder($path, $create = true) {
	    $folders = COMPONENT_FOLDERS;
	    $folder = end($folders).$path.(substr($path, -1) !== DS ? DS : '');
	    if (!file_exists($folder) && $create) {
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
	
	public static function sort(&$array, $values = true, $value = null) {
	    if (!isset($value)) {
	        $value = function($comparable) {
	            return self::collapse($comparable);
	        };
	    }
	    $compare = function($first, $second) use ($value) {
	        return $value($first) <=> $value($second);
	    };
	    if ($values) {
	        if (self::is_associative($array)) {
	            uasort($array, $compare);
	        } else {
	            usort($array, $compare);
	        }
	    } else {
	        uksort($array, $compare);
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
	
	public static function sanitize($serialized) {
	    return preg_replace_callback(
	        '!s:(\d+):"(.*?)";!',
	        function($match) {
	            return 's:'.strlen($match[2]).':"'.$match[2].'";';
	        },
	        $serialized
	    );
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
	        $context = self::value('context', $models['context']);
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
	            'skin'     => self::getSkin($models['context'])
	        ]);
	    }
	    $html = $textonly === true ? null : (new View($template, $models, $controler, $locale))->render();
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
        $date  = date('Y-m-d H:i:s');
        $crypted = self::encrypt($token.$date, $keyfile);
        if ($crypted !== false) {
            (new UserHasTokenEntity())->create([
                'user'  => $user,
                'key'   => $key,
                'token' => $token,
                'start' => $date
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
    
    public static function array_map_recursive($callback, $array) {
        if (is_array($callback)) {
            foreach ($callback as $function){
                $array = array_map_recursive($function, $array);
            }
            return $array;
        }
        $function = function ($item) use (&$function, &$callback) {
            return is_array($item) ? array_map($function, $item) : call_user_func($callback, $item);
        };
        return array_map($function, $array);
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
    
    public static function cookie($name, $value = '', $expires = 0, $path = '/', $domain = '', $secure = false, $httponly = false) {
        if (version_compare(phpversion(), '7.3.0', 'ge')) {
            setcookie($name, $value, [
                'expires'  => $expires,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => 'Lax'
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
        $folder = self::liveFolder('contents'.DS.$name.DS.$lang, false);
        if (isset($content)) {
            $date = date('YmdHis');
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
            return file_put_contents($folder.$date.'.md', $content) !== false ? self::date($date, $lang) : null;
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
        if (!isset($content) && $lang !== DEFAULT_LANG) {
            return self::content($name, DEFAULT_LANG);
        }
        return $content;
    }
    
    public static function firstElementChild($node) {
        $element = null;
        if (isset($node) && $node->childNodes->length > 0) {
            foreach ($node->childNodes as $child) {
                if ($child->nodeType == XML_ELEMENT_NODE) {
                    $element = $child;
                    break;
                }
            }
        }
        return $element;
    }
    
    public static function lastElementChild($node) {
        $element = null;
        if (isset($node) && $node->childNodes->length > 0) {
            $children = [];
            foreach ($node->childNodes as $child) {
                if ($child->nodeType == XML_ELEMENT_NODE) {
                    $children[] = $child;
                }
            }
            if (!empty($children)) {
                $element = $children[count($children) - 1];
            }
        }
        return $element;
    }
    
    public static function nextElementSibling($node) {
        $element = null;
        if (isset($node)) {
            $element = $node->nextSibling;
            if (isset($element) && $element->nodeType !== XML_ELEMENT_NODE) {
                $element = self::nextElementSibling($element);
            }
        }
        return $element;
    }
    
    public static function previousElementSibling($node) {
        $element = null;
        if (isset($node)) {
            $element = $node->previousSibling;
            if (isset($element) && $element->nodeType !== XML_ELEMENT_NODE) {
                $element = self::previousElementSibling($element);
            }
        }
        return $element;
    }
    
    public static function listModels($config, $models) {
        if (!is_array($config)) {
            $config = [$config];
        }
        $_models = ['id' => $config[0]];
        foreach ($config as $entry) {
            $_models = self::array_merge($_models, self::value('portal', ['list',$entry]) ?? []);
        }
        return self::array_merge($models, $_models);
    }
    
    public static function classList($classes, $complete = true) {
        return isset($classes) ? ($complete ? ' ' : '').implode(' ', is_array($classes) ? $classes : [$classes]) : '';
    }
    
    public static function entryValue($entry, $field, $options) {
        return isset($entry) ? (is_object($entry) ? $entry->$field : $entry[$field]) : ($options['default'] ?? '');
    }
    
    public static function clientCacheQuery() {
        return defined("CLIENT_CACHE_VERSION") ? '?clientCacheVersion='.CLIENT_CACHE_VERSION : '';
    }
    
    public static function contextList($lang, $withURLOnly = true, $property = 'title') {
        $list = [];
        foreach (self::getConfig('context') as $context => $config) {
            if (!$withURLOnly || (isset($config['url']) && !empty($config['url']))) {
                $title = $context;
                if (isset($config[$property][$lang])) {
                    $title = $config[$property][$lang];
                } else if (isset($config[$property][DEFAULT_LANG])) {
                    $title = $config[$property][DEFAULT_LANG];
                } else if (isset($config[$property]) && is_string($config[$property])) {
                    $title = $config[$property];
                }
                $list[$context] = $title;
            }
        }
        uksort($list, function($first, $second) {
            return (self::value('context', [$first,'position']) ?? 0) <=> (self::value('context', [$second,'position']) ?? 0);
        });
        return $list;
    }
    
    public static function IP($list) {
        $IPList = [];
        $unfolded = [];
        $folded = [];
        for ($index = 0 ; $index < count($list) ; $index++) {
            if (empty($list[$index]) || strpos($list[$index], ':') > 0) {
                continue;
            }
            $n = explode('.', $list[$index], 4);
            $d = explode('-', $n[3], 2);
            $c = explode('-', $n[2], 2);
            $mask = 32;
            if (count($d) == 2) {
                if (in_array($d[0], ['0', '1']) && in_array($d[1], ['254', '255'])) {
                    $n[3] = '0';
                    $mask = 24;
                } else {
                    if ($d[0] == 0) {
                        $n[3] = '1-'.$d[1];
                    }
                    if ($d[1] == 255) {
                        $n[3] = $d[0].'-254';
                    }
                }
                if (count($c) == 2 && $c[0] == '0' && $c[1] == '255') {
                    $n[2] = '0';
                    $mask = 16;
                }
            }
            if (count($d) == 2 && $d[0] == $d[1]) {
                $n[3] = $d[0];
            }
            if (count($c) == 2 && $c[0] == $c[1]) {
                $n[2] = $c[0];
            }
            $IPList[] = implode('.',$n).'/'.$mask;
        }
        foreach ($IPList as $IP) {
            $chunk = self::chunkIP($IP);
            $n = explode('.', $chunk['ip'], 4);
            $class = null;
            $pattern = null;
            switch ($chunk['mask']) {
                case '32': {
                    $class = 'D';
                    $pattern = $n[0].'.'.$n[1].'.'.$n[2];
                    if (strpos($n[2],'-')) {
                        $pattern .= '.'.$n[3];
                    }
                    break;
                }
                case '24': {
                    $class = 'C';
                    $pattern = $n[0].'.'.$n[1];
                    break;
                }
                case '16': {
                    $class = 'B';
                    $pattern = $n[0];
                    break;
                }
            }
            if ($class && $pattern) {
                foreach (self::explodeIP($chunk['ip']) as $ip) {
                    $unfolded[$class][$pattern][] = $ip;
                }
            }
        }
        if (count($unfolded) > 0) {
            foreach ($unfolded as $class => $list) {
                foreach ($list as $pattern => $entries) {
                    sort($entries, SORT_NATURAL);
                    $n = explode('.', $pattern);
                    if (count($n) == 4) {
                        $folded[] = $pattern.'/32';
                    } else {
                        $block = '';
                        $prefix = $n[0].'.'.(($class == 'C' || $class == 'D') ? $n[1].'.' : '').($class == 'D' ? $n[2].'.' : '');
                        $suffix = ($class == 'B' ? '.0.0' : ($class == 'C' ? '.0' : ''));
                        $first = true;
                        $start = false;
                        $from = ($class == 'D' ? 1   : 0);
                        $to   = ($class == 'D' ? 254 : 255);
                        for ($i = $from; $i <= $to; $i++) {
                            $current = $prefix.$i.$suffix;
                            $next = ($i == $to ? null : $prefix.($i + 1).$suffix);
                            if (self::keep($current, $class, $entries)) {
                                if ($first) {
                                    $block .= $i;
                                    $first = false;
                                    $start = self::keep($next, $class, $entries);
                                } else {
                                    if (!$start) {
                                        $block .= '|'.$i;
                                        $start = (isset($next) && self::keep($next, $class, $entries));
                                    } else if (!isset($next) || !self::keep($next, $class, $entries)) {
                                        $block .= '-'.$i;
                                        $start = false;
                                    }
                                }
                            }
                        }
                        $suffix .= ($class == 'B' ? '/16' : ($class == 'C' ? '/24' : '/32'));
                        $block = $prefix.$block.$suffix;
                        if (count($entries) > ($class == 'D' ? 127 : 128)) {
                            $folded[] = $prefix.($class == 'B' ? '0.0.0/8' : ($class == 'C' ? '0.0/16' : '0/24'));
                            if (!$first) {
                                $folded[] = '~'.$block;
                            }
                        } else {
                            $folded[] = $block;
                        }
                    }
                }
            }
        }
        return $folded;
    }
    
    private static function keep($ip, $class, $entries) {
        if (count($entries) <= ($class == 'D' ? 127 : 128)) {
            return in_array($ip, $entries);
        } else {
            return !in_array($ip, $entries);
        }
    }
    
    public static function roman2number($roman) {
        $number = 0;
        $roman = strtoupper($roman);
        foreach (self::$romans as $key => $value) {
            while (strpos($roman, $key) === 0) {
                $number += $value;
                $roman = substr($roman, strlen($key));
            }
        }
        return $number;
    }
    
    public static function number2roman($number, $upper = true) {
        $roman = '';
        foreach (self::$romans as $key => $value) {
            $repeat = intval($number / $value);
            $roman .= str_repeat($key, $repeat);
            $number = $number % $value;
        }
        return $upper ? $roman : strtolower($roman);
    }
    
    public static function messages($message) {
        $messages = [];
        if (!empty($message)) {
            foreach (explode('|', $message) as $_message) {
                $__message = explode('=', $_message);
                foreach (explode('§', $__message[1]) as $___message) {
                    $messages[$__message[0]][] = $___message;
                }
            }
        }
        return $messages;
    }
    
    public static function uuid() {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = random_bytes(16);
        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
}
