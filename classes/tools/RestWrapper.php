<?php

class RestWrapper {
    
    protected $client    = null;
    protected $functions = null;
    
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
        }
    }
    
    protected function invoke($name, $data = [], $headers = []) {
        $config = $this->functions[$name] ?? false;
        if ($config && $config['path']) {
            $method = $config['method'] ?? 'GET';
            switch ($method) {
                case 'GET': {
                    return $this->client->get($config['path'], $data, $headers);
                    break;
                }
                case 'HEAD': {
                    return $this->client->head($config['path']);
                    break;
                }
                case 'POST': {
                    return $this->client->post($config['path'], $data, $headers);
                    break;
                }
                case 'PUT': {
                    return $this->client->put($config['path'], $data, $headers);
                    break;
                }
                case 'DELETA': {
                    return $this->client->delete($config['path'], $headers);
                    break;
                }
            }
        }
    }
}

?>