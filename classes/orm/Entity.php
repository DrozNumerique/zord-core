<?php

abstract class Entity
{
    protected $database;
    protected $mapping;
    protected $type;
    protected $table;
    protected $fields;
    protected $keys;
    protected $joins;
    
    protected $engine = null;
    protected $select = false;
    
    protected function configure() {
        $this->database = 'default';
        $this->mapping  = 'orm';
    }
    
    public function __construct() {
        $this->configure();
        $this->type = get_class($this);
        $database = Zord::value($this->mapping, [$this->type,'database']);
        if (isset($database)) {
            $this->database = $database;
        }
        $this->table = Zord::value($this->mapping, [$this->type,'table']);
        $this->fields = Zord::value($this->mapping, [$this->type,'fields']);
        $this->keys = Zord::value($this->mapping, [$this->type,'key']);
        $this->joins = Zord::value($this->mapping, [$this->type,'join']);
        if (!is_array($this->keys)) {
            $this->keys = [$this->keys];
        }
        if ($this->table && $this->fields) {
            foreach(Zord::value('connection', ['database',$this->database]) as $key => $value) {
                ORM::configure($key, $value, $this->database);
            }
            $keys = array();
            foreach(array_keys(Zord::getConfig($this->mapping)) as $key) {
                $table = Zord::value($this->mapping, [$key,'table']);
                if ($table) {
                    $key = Zord::value($this->mapping, [$key,'key']);
                    if ($key) {
                        $keys[$table] = $key;
                    }
                }
            }
            if (count($keys) > 0) {
                ORM::configure('id_column_overrides', $keys, $this->database);
            }
            ORM::configure('return_result_sets', true, $this->database);
            $this->engine = ORM::for_table($this->table, $this->database)->table_alias($this->type);
        }
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
        if (isset($criteria['join']) && isset($this->joins)) {
            if (!is_array($criteria['join'])) {
                $criteria['join'] = [$criteria['join']];
            }
            $this->engine->distinct();
            foreach ($criteria['join'] as $type) {
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
                            $this->engine()->where__not_null($key);
                        } else {
                            $this->engine()->where($key, $value);
                        }
                    }
                }
            }
        }
        if (isset($criteria['order'])) {
            if (isset($criteria['order']['asc'])) {
                $this->engine()->order_by_asc($criteria['order']['asc']);
            } else if (isset($criteria['order']['desc'])) {
                $this->engine()->order_by_desc($criteria['order']['desc']);
            }
        }
    }
    
    private function deep($many) {
        if (isset($this->joins)) {
            foreach (array_keys($this->joins) as $type) {
                $fields = Zord::value($this->mapping, [$type,'fields']);
                foreach ($fields as $field) {
                    $full = $type.'.'.$field;
                    $alias = $type.'_'.$field;
                    $get = Zord::value($this->mapping, [$type,'expr',$field,'get']);
                    if ($get) {
                        $this->engine()->select_expr($get.'('.$full.') as '.$alias);
                    } else {
                        $this->engine()->select($full, $alias);
                    }
                }
            }
        }
        $entities = $this->engine()->find_many();
        $results = [];
        foreach ($entities as $entity) {
            $id = $this->id($this->type, $entity);
            $results[$id][$this->type] = $entity;
            if (isset($this->joins)) {
                foreach (array_keys($this->joins) as $type) {
                    $_id = $this->id($type, $entity, true);
                    $results[$id][$type][$_id] = $entity;
                }
            }
        }
        return ($many || count($results) == 1) ? $results : false;
    }
    
    private function id($type, $entity, $deep = false) {
        $id = '';
        $keys = Zord::value($this->mapping, [$type,'key']);
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        foreach ($keys as $key) {
            $alias = $deep ? $type.'_'.$key : $key;
            $id .= $entity->$alias;
        }
        return $id;
    }
    
    public function create(array $data) {
        $entity = $this->engine(true)->create();
        $this->set($entity, $data);
        $this->save($entity, $data);
        return $entity;
    }
    
    public function retrieve($criteria = null) {
        $many = isset($criteria['many']) && $criteria['many'] === true;
        $deep = isset($criteria['deep']) && $criteria['deep'] === true;
        if ($criteria == null) {
            return $this->engine()->find_many();
        } else if (is_scalar($criteria)) {
            $criteria = ['key' => $criteria];
        } else if (is_array($criteria)) {
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
            if ($is_key) {
                $criteria = ['key' => $criteria];
            }
        }
        if (isset($criteria['key']) && !$many) {
            if (!$deep) {
                return $this->engine()->find_one($criteria['key']);
            } else {
                return $this->deep(false);
            }
        }
        $this->query($criteria);
        if (!$deep) {
            return $many ? $this->engine()->find_many() : $this->engine()->find_one();
        } else {
            return $this->deep($many);
        }
    }
    
    public function update($criteria, array $data) {
        $many = isset($criteria['many']) && $criteria['many'] === true;
        $entity = $this->retrieve($criteria);
        if ($entity) {
            if (!$many) {
                $this->set($entity, $data);
            } else {
                foreach ($entity as $entry) {
                    $this->set($entry, $data);
                }
            }
            $this->save($entity, $data);
        }
        return $entity;
    }

    public function delete($criteria = null) {
        $many = isset($criteria['many']) && $criteria['many'] === true;
        if ($criteria == null) {
            return $this->engine()->delete_many();
        } else if (is_scalar($criteria)) {
            $criteria = ['key' => $criteria];
        }
        if (isset($criteria['key']) && !$many) {
            $criteria = [
                'where' => [Zord::value($this->mapping, [$this->type,'key']) => $criteria['key']]
            ];
            $many = true;
        }
        $this->query($criteria);
        return $many ? $this->engine()->delete_many() : $this->engine()->delete();
    }
    
    private function set($entity, $data) {
        foreach ($this->fields as $field) {
            if (array_key_exists($field, $data)) {
                $set = Zord::value($this->mapping, [$this->type,'expr',$field,'set']);
                if ($set) {
                    $entity->set_expr($field, $set.'("'.$data[$field].'")');
                } else if (is_array($data[$field])) {
                    $entity->set($field, Zord::json_encode($data[$field], false));
                } else {
                    $entity->set($field, $data[$field]);
                }
            }
        }
    }
    
    private function save($entity, $data) {
        $this->beforeSave($entity, $data);
        $entity->save();
    }
    
    protected function beforeSave($entity, $data) {}
}
