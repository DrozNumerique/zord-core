<?php

class RestWrapper {
    
    protected $client    = null;
    protected $config    = null;
    protected $functions = null;
    protected $function  = null;
    
    public function __construct() {
        $config = Zord::value('rest', get_class($this));
        if (isset($config['baseURL'])) {
            $options = [];
            $class = 'Pest'.($config['type'] ?? '');
            if (isset($config['options'])) {
                foreach ($config['options'] as $key => $value) {
                    if (defined($key)) {
                        $key = constant($key);
                    }
                    if (defined($value)) {
                        $value = constant($value);
                    }
                    $options[$key] = $value;
                }
            }
            $this->client    = new $class($config['baseURL'], $options);
            $this->functions = $config['functions'] ?? [];
            $this->config    = $config;
        }
    }
    
    protected function invoke($name, $data = null, $headers = []) {
        $this->function = $this->functions[$name] ?? false;
        if ($this->function && $this->function['path']) {
            $method = $this->function['method'] ?? 'GET';
            $path = $this->function['path'];
            if (is_array($data)) {
                $path = Zord::substitute($path, $data);
                $unset = $this->function['unset'] ?? [];
                if (!is_array($unset)) {
                    $unset = [$unset];
                }
                foreach ($unset as $key) {
                    unset($data[$key]);
                }
            }
            if (($this->function['in'] ?? '') === 'JSON') {
                $data = json_encode($data);
                $headers[] = 'Content-Type: application/json';
            }
            $result = null;
            switch ($method) {
                case 'GET': {
                    $result =  $this->client->get($path, $data, $headers);
                    break;
                }
                case 'HEAD': {
                    $result =  $this->client->head($path);
                    break;
                }
                case 'POST': {
                    $result =  $this->client->post($path, $data, $headers);
                    break;
                }
                case 'PUT': {
                    $result =  $this->client->put($path, $data, $headers);
                    break;
                }
                case 'DELETE': {
                    $result =  $this->client->delete($path, $headers);
                    break;
                }
            }
            if (!empty($this->function['out'] ?? '')) {
                $class = 'Pest'.$this->function['out'];
                $result = (new $class(null))->processBody($result);
            }
            return $result;
        }
    }
}

?>