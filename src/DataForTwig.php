<?php
namespace waterada\ReactPHP;

class DataForTwig {
    /** @var ReactComponent */
    protected $component;

    /** @var MapForTwig */
    public $props;

    /** @var MapForTwig */
    public $state;

    /**
     * @param ReactComponent $component
     */
    public function __construct($component) {
        $this->component = $component;
        $this->props = new MapForTwig([$this, '_returnProps']);
        $this->state = new MapForTwig([$this, '_returnState']);
    }

    public function _returnProps($key) {
        $val = $this->component->props($key);
        return $val;
    }

    public function _returnState($key) {
        $val = $this->component->state($key);
        return $val;
    }

    public function __call($key, $val) {
        return call_user_func_array([$this->component, $key], $val);
    }

    public function __get($key) {
        if (method_exists($this->component, $key)) {
            return [$this->component, $key];
        }
        throw new \Exception('no key:' . $key . ' in ' . get_class($this->component));
    }

    public function __isset($key) {
        return true;
    }
}

class MapForTwig {
    /** @var callable */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct($callback) {
        $this->callback = $callback;
    }

    public function __get($key) {
        return call_user_func($this->callback, $key);
    }

    public function __isset($name) {
        return true;
    }
}