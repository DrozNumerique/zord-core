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
            $this->functions = $config['functions'] ?? [];
            $this->config    = $config;
            $this->client    = new $class($config['baseURL'], $options);
            if ($config['silent'] ?? false) {
                $this->client->throw_exceptions = false;
                $this->client->throwJsonExceptions = false;
            }
        }
    }
    
    protected function invoke($name, $data = null, $headers = []) {
        $this->function = $this->functions[$name] ?? false;
        if ($this->function && $this->function['path']) {
            $method = $this->function['method'] ?? ($data['METHOD'] ?? 'GET');
            $in     = $this->function['in']     ?? ($data['IN']     ?? '');
            $out    = $this->function['out']    ?? ($data['OUT']    ?? '');
            $path   = $this->function['path']   ?? ($data['PATH']   ?? '');
            if (is_array($data)) {
                $path = Zord::substitute($path, $data);
                $unset = $this->function['unset'] ?? [];
                if (!is_array($unset)) {
                    $unset = [$unset];
                }
                $unset = array_merge($unset, ['METHOD','IN','OUT','PATH']);
                foreach ($unset as $key) {
                    unset($data[$key]);
                }
            }
            if ($in === 'JSON') {
                if ($this->config['type'] ?? '' !== 'JSON') {
                    $data = json_encode($data);
                }
                $hasContentType = false;
                foreach ($headers as $header) {
                    if (strtolower(substr($header, 0, 12)) === 'content-type') {
                        $hasContentType = true;
                        break;
                    }
                }
                if (!$hasContentType) {
                    $headers[] = 'Content-Type: application/json';
                }
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
            if (!empty($out)) {
                $class = 'Pest'.$out;
                $result = (new $class(null))->processBody($result);
            }
            return $result;
        }
    }
}

?>