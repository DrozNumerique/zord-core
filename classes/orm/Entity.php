<?php

abstract class Entity
{
    protected $database;
    protected $mapping;
    protected $parameters;
    protected $type;
    protected $table;
    protected $fields;
    protected $keys;
    protected $joins;
    protected $elements;
    protected $json;
    
    protected $engine = null;
    protected $select = false;
    
    protected function configure() {
        $this->database = 'default';
        $this->mapping  = 'orm';
    }
    
    public function __construct($parameters = []) {
        $this->configure();
        $this->parameters = $parameters;
        $this->type = get_class($this);
        $database = $this->resolve('database');
        if (isset($database)) {
            $this->database = $database;
        }
        $this->table = $this->resolve('table');
        $this->fields = $this->resolve('fields');
        $this->keys = $this->resolve('key');
        $this->joins = $this->resolve('join');
        $this->elements = $this->resolve('elements');
        $this->json = $this->resolve('json');
        if (!is_array($this->keys)) {
            $this->keys = [$this->keys];
        }
        if ($this->table && $this->fields) {
            foreach(Zord::value('connection', ['database',$this->database]) as $key => $value) {
                ORM::configure($key, $value, $this->database);
            }
            $keys = array();
            $objects = array();
            foreach(array_keys(Zord::getConfig($this->mapping)) as $key) {
                $table = $this->resolve('table', $key);
                if ($table) {
                    $_key = $this->resolve('key', $key);
                    if ($_key) {
                        $keys[$table] = $_key;
                    }
                    $_json = $this->resolve('json', $key);
                    if ($_json) {
                        $objects[$table] = $_json;
                    }
                }
            }
            
            if (count($keys) > 0) {
                ORM::configure('id_column_overrides', $keys, $this->database);
            }
            if (count($objects) > 0) {
                ORM::configure('fields_as_objects', $objects, $this->database);
            }
            ORM::configure('return_result_sets', true, $this->database);
            $this->engine = ORM::for_table($this->table, $this->database)->table_alias($this->type);
        }
    }
    
    private function resolve($property, $type = null) {
        $type = $type ?? $this->type;
        $result = Zord::value($this->mapping, [$type,$property]);
        return isset($result) ? Zord::substitute($result, $this->parameters) : null;
    }
    
    private function engine($insert = false) {
        if (!$insert && !$this->select) {
            foreach ($this->fields as $field) {
                $full = $this->type.'.'.$field;
                $get = Zord::value($this->mapping, [$this->type,'expr',$field,'get']);
                if ($get) {
                    $this->engine->select_expr($get.'('.$full.') as '.$field);
                } else {
                    $this->engine->select($full, $field);
                }
            }
            $this->select = true;
        }
        return $this->engine;
    }

    private function query($criteria) {
        $join = false;
        if (isset($criteria['join']) && isset($this->joins)) {
            if (!is_array($criteria['join'])) {
                $criteria['join'] = [$criteria['join']];
            }
            $this->engine->distinct();
            foreach ($criteria['join'] as $type) {
                $join = true;
                $this->engine()->join(Zord::value($this->mapping, [$type,'table']), [$this->type.'.'.$this->joins[$type][0], '=', $type.'.'.$this->joins[$type][1]], $type);
            }
        }
        if (isset($criteria['where']) && is_array($criteria['where'])) {
            foreach ($criteria['where'] as $key => $value) {
                if ($key == 'any') {
                    $this->engine()->where_any_is($value);
                } else if ($key == 'raw') {
                    if (isset($criteria['where']['parameters'])) {
                        $parameters = $criteria['where']['parameters'];
                        if (is_array($parameters)) {
                            $this->engine()->where_raw($value, $parameters);
                        } else if (is_scalar($parameters)) {
                            $this->engine()->where_raw($value, [$parameters]);
                        }
                    } else {
                        $this->engine()->where_raw($value);
                    }
                } else if ($key !== 'parameters') {
                    $get = Zord::value($this->mapping, [$this->type,'expr',$key,'get']);
                    if ($get) {
                        $key = $get.'('.$key.'.)';
                    }
                    if ($join) {
                        $key = $this->type.'.'.$key;
                    }
                    if (is_array($value)) {
                        foreach ($value as $op => $comp) {
                            if ($op == '=') {
                                $this->engine()->where_equal($key, $comp);
                            } else if ($op == '!=') {
                                $this->engine()->where_not_equal($key, $comp);
                            } else if ($op == '>') {
                                $this->engine()->where_gt($key, $comp);
                            } else if ($op == '<') {
                                $this->engine()->where_lt($key, $comp);
                            } else if ($op == '>=') {
                                $this->engine()->where_gte($key, $comp);
                            } else if ($op == '<=') {
                                $this->engine()->where_lte($key, $comp);
                            } else if ($op == 'like') {
                                $this->engine()->where_like($key, $comp);
                            } else if ($op == '!like') {
                                $this->engine()->where_not_like($key, $comp);
                            } else if ($op == 'in' && is_array($comp)) {
                                $this->engine()->where_in($key, $comp);
                            } else if ($op == '!in' && is_array($comp)) {
                                $this->engine()->where_not_in($key, $comp);
                            }
                        }
                    } else {
                        if ($value == '__NULL__' || !isset($value)) {
                            $this->engine()->where_null($key);
                        } else if ($value == '__NOT_NULL__') {
                            $this->engine()->where_not_null($key);
                        } else {
                            $this->engine()->where($key, $value);
                        }
                    }
                }
            }
        }
        if (isset($criteria['limit'])) {
            $this->engine()->limit($criteria['limit']);
        }
        if (isset($criteria['offset'])) {
            $this->engine()->offset($criteria['offset']);
        }
        if (isset($criteria['order'])) {
            if (Zord::is_associative($criteria['order'])) {
                $criteria['order'] = [$criteria['order']];
            }
            foreach ($criteria['order'] as $order) {
                if (isset($order['asc'])) {
                    $this->engine()->order_by_asc($order['asc']);
                } else if (isset($order['desc'])) {
                    $this->engine()->order_by_desc($order['desc']);
                }
            }
        }
    }
    
    private function deep($type, &$entity) {
        if (isset($this->elements)) {
            foreach ($this->elements as $element => $fields) {
                $attribute = $fields[0];
                $field = $fields[1];
                $entities = (new $element())->retrieve([
                    'many'   => true,
                    'where' => [$field => $this->key($type, $entity)]
                ], true);
                $entity->$attribute = $entities;
            }
        }
    }
    
    private function key($type, $entry) {
        $keys = Zord::value($this->mapping, [$type,'key']);
        if (isset($keys)) {
            if (is_array($keys)) {
                $result = [];
                foreach ($keys as $key) {
                    $result[$key] = $entry->$key;
                }
                return $result;
            } else {
                return $entry->$keys;
            }
        }
        return null;
    }
    
    public function create(array $data) {
        $entity = $this->engine(true)->create();
        $this->set($entity, $data);
        $this->save($entity, $data);
        return $entity;
    }
    
    public function retrieve($criteria = null, $deep = false) {
        if ($this->is_many($criteria)) {
            if ($criteria !== null) {
                $this->query($criteria);
            }
            $results = $this->engine()->find_many();
            if ($deep && isset($this->elements)) {
                foreach ($results as $result) {
                    $this->deep($this->type, $result);
                }
            }
            return $results;
        } else {
            if (is_scalar($criteria) || $this->is_key($criteria)) {
                $criteria = ['key' => $criteria];
            } else if (isset($criteria) && !isset($criteria['key'])) {
                $this->query($criteria);
            }
            $result = isset($criteria['key']) ? $this->engine()->find_one($criteria['key']) : $this->engine()->find_one();
            if ($result && $deep && isset($this->elements)) {
                $this->deep($this->type, $result);
            }
            return $result;
        }
    }
    
    public function update($criteria, array $data) {
        $entity = $this->retrieve($criteria);
        if ($entity) {
            if ($this->is_many($criteria)) {
                foreach ($entity as $entry) {
                    $this->set($entry, $data);
                }
            } else {
                $this->set($entity, $data);
            }
            $this->save($entity, $data);
        }
        return $entity;
    }

    public function delete($criteria = null, $deep = false) {
        $many = $this->is_many($criteria);
        if ($criteria == null && !$deep) {
            return $this->engine()->delete_many();
        } else if (is_scalar($criteria) || $this->is_key($criteria)) {
            $criteria = ['key' => $criteria];
        }
        if (isset($criteria['key']) && !$many) {
            $where = [];
            if (is_scalar($criteria['key'])) {
                $where[$this->keys[0]] = $criteria['key'];
            } else {
                foreach($this->keys as $key) {
                    $where[$key] = $criteria['key'][$key];
                }
            }
            $criteria = ['many' => true, 'where' => $where];
            $many = true;
        }
        if (isset($criteria)) {
            $this->query($criteria);
        }
        if ($deep && isset($this->elements)) {
            $deleted = [];
            if ($many) {
                $entries = $this->engine()->find_many();
                foreach ($entries as $entry) {
                    $deleted[] = $this->key($this->type, $entry);
                }
            } else {
                $entry = $this->engine()->find_one();
                if ($entry) {
                    $deleted[] = $this->key($this->type, $entry);
                }
            }
            $result = $this->delete($criteria, false);
            foreach ($deleted as $key) {
                foreach ($this->elements as $element => $fields) {
                    $result &= (new $element())->delete([
                        'many'  => true,
                        'where' => [$fields[1] => $key]
                    ], true);
                }
            }
            return $result;
        } else {
            $result = $many ? $this->engine()->delete_many() : $this->engine()->delete();
        }
        return $result;
    }
    
    public function get($entity, $property) {
        if (in_array($property, $this->json)) {
            return json_decode($entity->$property);
        } else {
            return $entity->$property;
        }
    }
    
    private function set($entity, $data) {
        foreach ($this->fields as $field) {
            if (array_key_exists($field, $data)) {
                $set = Zord::value($this->mapping, [$this->type,'expr',$field,'set']);
                if ($set) {
                    $entity->set_expr($field, $set.'("'.$data[$field].'")');
                } else if (is_array($data[$field])) {
                    $entity->set($field, Zord::json_encode($data[$field]));
                } else {
                    $entity->set($field, $data[$field]);
                }
            }
        }
    }
    
    private function save($entity, $data) {
        return $this->beforeSave($entity, $data)->save();
    }
    
    private function is_many($criteria) {
        return !isset($criteria) || (isset($criteria['many']) && $criteria['many'] === true);
    }
    
    private function is_key($criteria) {
        if (!isset($criteria)) {
            return false;
        }
        $is_key = true;
        $keys = array_keys($criteria);
        foreach ($keys as $key) {
            if (!in_array($key, $this->keys)) {
                $is_key = false;
                break;
            }
        }
        foreach ($this->keys as $key) {
            if (!in_array($key, $keys)) {
                $is_key = false;
                break;
            }
        }
        return $is_key;
    }
    
    protected function beforeSave($entity, $data) {
        return $entity;
    }
}
