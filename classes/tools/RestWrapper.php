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
            $class = 'Pest'.($config['type'] ?? 'JSON');
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
    
    protected function invoke($name, $data = [], $headers = []) {
        $this->function = $this->functions[$name] ?? false;
        if ($this->function && $this->function['path']) {
            $method = $this->function['method'] ?? 'GET';
            $path = Zord::substitute($this->function['path'], $data);
            $unset = $this->function['unset'] ?? [];
            if (!is_array($unset)) {
                $unset = [$unset];
            }
            foreach ($unset as $key) {
                unset($data[$key]);
            }
            switch ($method) {
                case 'GET': {
                    return $this->client->get($path, $data, $headers);
                    break;
                }
                case 'HEAD': {
                    return $this->client->head($path);
                    break;
                }
                case 'POST': {
                    return $this->client->post($path, $data, $headers);
                    break;
                }
                case 'PUT': {
                    return $this->client->put($path, $data, $headers);
                    break;
                }
                case 'DELETE': {
                    return $this->client->delete($path, $headers);
                    break;
                }
            }
        }
    }
}

?>