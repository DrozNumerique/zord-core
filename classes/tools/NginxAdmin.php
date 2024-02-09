<?php

trait NginxAdmin  {
    
    protected function applyContext($context) {
        if (defined('NGINX_VHOST_INCLUDE')) {
            $include = $this->buildInclude($context);
            if (is_string($include)) {
                return "Can't build include\n\n$include";
            }
            $current = file_get_contents(NGINX_VHOST_INCLUDE);
            if (file_put_contents(NGINX_VHOST_INCLUDE, implode("\n", $include))) {
                $output = [];
                $status = 0;
                exec("sudo service nginx reload", $output, $status);
                if ($status !== 0) {
                    file_put_contents(NGINX_VHOST_INCLUDE, $current);
                    return "Can't reload nginx config\n\nStatus : $status\nOutput : ".implode("\n", $output);
                }
            } else {
                return "Can't write ".NGINX_VHOST_INCLUDE;
            }
        }
    }
    
    protected function buildInclude($context) {
        $hosts = [];
        foreach ($context as $entry) {
            foreach ($entry['url'] ?? [] as $url) {
                $host = $url['host'];
                if (!in_array($host, $hosts)) {
                    $hosts[] = $host;
                }
            }
        }
        if (empty($hosts)) {
            return "No context host defined";
        }
        if (defined('NGINX_VHOST_SERVER_NAME') && !empty(NGINX_VHOST_SERVER_NAME)) {
            $hosts[] = NGINX_VHOST_SERVER_NAME;
        }
        $lines = ["server_name"];
        foreach ($hosts as $host) {
            $lines[]= "\t$host";
        }
        $lines[] = ";";
        return $lines;
    }
}

?>