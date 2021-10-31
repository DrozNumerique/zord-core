<?php

trait NginxAdmin  {
    
    protected function resetContext($context) {
        if (defined('NGINX_VHOST_INCLUDE')) {
            $include = $this->serverName($context);
            $content = file_get_contents(NGINX_VHOST_INCLUDE);
            if (file_put_contents(NGINX_VHOST_INCLUDE, $include)) {
                $output = [];
                $status = 0;
                exec("sudo service nginx reload", $output, $status);
                if ($status !== 0) {
                    file_put_contents(NGINX_VHOST_INCLUDE, $content);
                    return "Can't reload nginx config\n\nStatus : $status\nOutput : ".implode("\n", $output);
                }
            } else {
                return "Can't write ".NGINX_VHOST_INCLUDE;
            }
        }
        return parent::resetContext($context);
    }
    
    protected function serverName($context) {
        $directive = "server_name\n";
        $hosts = [];
        foreach ($context as $entry) {
            foreach ($entry['url'] ?? [] as $url) {
                $host = $url['host'];
                if (!in_array($host, $hosts)) {
                    $hosts[] = $host;
                    $directive .= "\t$host\n";
                }
            }
        }
        if (empty($hosts)) {
            return "No context host defined";
        }
        if (defined('NGINX_VHOST_SERVER_NAME') && !empty(NGINX_VHOST_SERVER_NAME)) {
            $directive .= "\t".NGINX_VHOST_SERVER_NAME."\n";
        }
        $directive .= ";";
        return $directive;
    }
}

?>