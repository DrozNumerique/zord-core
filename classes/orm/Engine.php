<?php

class Engine extends ORM {
        
    private function _field_is_object($key) {
        $_fields_as_objects = self::$_config[$this->_connection_name]['fields_as_objects'][$this->_table_name] ?? [];
        if (!is_array($_fields_as_objects)) {
            $_fields_as_objects = [$_fields_as_objects];
        }
        return in_array($key, $_fields_as_objects);
    }
        
    protected function _create_instance_from_row($row) {
        foreach ($row as $key => $value) {
            if ($this->_field_is_object($key)) {
                $row[$key] = json_decode($value);
            }
        }
        return parent::_create_instance_from_row($row);
    }
    
    protected function _set_orm_property($key, $value = null, $expr = false) {
        if ($this->_field_is_object($key)) {
            $value = Zord::json_encode($value);
        }
        return parent::_set_orm_property($key, $value, $expr);
    }
    
}

?>