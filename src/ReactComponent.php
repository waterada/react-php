<?php
namespace waterada\ReactPHP;

abstract class ReactComponent {
    /** @var array */
    private $state = [];

    /** @var array */
    private $props = [];

    /** @var boolean */
    private $stateChanged = false;

    /** @var ReactComponent[] */
    private $children = [];

    /** @var string */
    private $html = '';

    /** @var array */
    private $address;

    public function __construct($props = []) {
        $this->props = $props;
    }

    public function construct($address) {
        $this->address = $address;
        $this->state = $this->getInitialState();
        $this->componentDidMount();
        $this->doRender();
        $this->stateChanged = false;
    }

    private function doRender() {
        $this->children = [];
        $this->html = $this->_doRender();
    }

    protected function _doRender() {
        ob_start();
        $this->render();
        return ob_get_clean();
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function state($key) {
        if (!isset($this->state[$key])) {
            //var_dump(React::$state[$this->name]);
            throw new \Exception("no key in state:" . $key);
        }
        return $this->state[$key];
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setState($key, $value) {
        if (isset($this->state[$key]) && $this->state[$key] !== $value) {
            $this->stateChanged = true;
        }
        $this->state[$key] = $value;
    }

    /**
     * @param string $key
     * @return string|array
     * @throws \Exception
     */
    public function props($key) {
        if (!isset($this->props[$key])) {
            var_dump($this->props);
            throw new \Exception("no key in props:" . $key);
        }
        return $this->props[$key];
    }

    public function __get($name) {
        list($type, $key) = explode('_', $name, 2);
        if ($type === 'props') {
            return $this->props($key);
        }
        if ($type === 'state') {
            return $this->state($key);
        }
        throw new \Exception('Unkown property:' . $name);
    }

    public function __set($name, $value) {
        list($type, $key) = explode('_', $name, 2);
        if ($type === 'state') {
            $this->setState($key, $value);
            return;
        }
        throw new \Exception('Unkown property:' . $name);
    }

    protected function getInitialState() {
        return [];
    }

    protected function componentDidMount() {
    }

    /**
     * @param ReactComponent $component
     * @return string
     */
    public function element($component) {
        //自身に子の component 追加
        $idx = count($this->children);
        $this->children[$idx] = $component;
        $html = '{{#####' . $idx . '#####}}';

        //初期化
        $component->construct(array_merge($this->address, [$idx]));

        //返す
        return $html;
    }

    /**
     * @return string
     */
    public abstract function render();

    public function onSubmitLink($handlerName) {
        return $this->onSubmit([$this, $handlerName], 'submitLink');
    }

    public function onSubmitForm($handlerName) {
        return $this->onSubmit([$this, $handlerName], 'submitForm');
    }

    public function onSubmit($handler, $method) {
        $address = implode('.', array_merge($this->address, [$handler[1]]));
        return 'ReactPHP.' . $method . '(this, \'' . htmlspecialchars($address) . '\');';
    }

//    public function fireComponentDidMount() {
//        $this->componentDidMount();
//        foreach ($this->children as $child) {
//            $child->fireComponentDidMount();
//        }
//    }

    public function fireSubmit($handlerAddress, $args = null) {
        $curAddr = array_shift($handlerAddress);
        if (ctype_digit($curAddr)) {
            return $this->children[$curAddr]->fireSubmit($handlerAddress, $args);
        } else {
            return $this->_fireSubmit([$this, $curAddr], $args);
        }
    }

    protected function _fireSubmit($onSubmit, $args = null) {
        return call_user_func_array($onSubmit, $args);
    }

    public function rerender() {
        //もしstateが変更されたら下位を再構築
        if ($this->stateChanged) {
            $this->stateChanged = false;
            $this->doRender();
        } else {
            foreach ($this->children as $child) {
                $child->rerender();
            }
        }
    }

    public function toHtml() {
        $html = $this->html;
        foreach ($this->children as $idx => $child) {
            $_html = $child->toHtml();
            $html = str_replace('{{#####' . $idx . '#####}}', $_html, $html);
        }
        return $html;
    }

    public function getAddress() {
        return $this->address;
    }
}
