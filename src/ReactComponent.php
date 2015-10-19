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

    /** @var callable[] */
    private $onSubmit = [];

    public function __construct($props = []) {
        $this->props = $props;
    }

    public function construct() {
        $this->state = $this->getInitialState();
        $this->doRender();
        $this->stateChanged = false;
    }

    private function doRender() {
        $this->children = [];
        $this->onSubmit = [];
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

        //アドレスが確定したのでセット
        $component->setAddress($this->address . "." . $idx);

        //初期化
        $component->construct();

        //返す
        return $html;
    }

    private $address;
    public function setAddress($address) {
        $this->address = $address;
    }

    public function getAddress() {
        return $this->address;
    }

    /**
     * @return string
     */
    public abstract function render();

    public function onSubmitA($handlerName) {
        $address = $this->_onSubmit($handlerName);
        return ' href="javascript:ReactPHP.submitLink(this, \'' . htmlspecialchars($address) . '\');"';
    }

    public function onSubmitForm($handlerName) {
        $address = $this->_onSubmit($handlerName);
        return ' onsubmit="ReactPHP.submitForm(this, \'' . htmlspecialchars($address) . '\');"';
    }

    private function _onSubmit($handlerName) {
        $address = $this->address . "." . $handlerName;
        $this->onSubmit[$address] = [$this, $handlerName];
        return $address;
    }

    public function fireComponentDidMount() {
        $this->componentDidMount();
        foreach ($this->children as $child) {
            $child->fireComponentDidMount();
        }
    }

    public function fireSubmit($httpMethod, $handlerAddress) {
        if ($handlerAddress && isset($this->onSubmit[$handlerAddress])) {
            $this->_fireSubmit($this->onSubmit[$handlerAddress], $httpMethod);
        }
        foreach ($this->children as $child) {
            $child->fireSubmit($httpMethod, $handlerAddress);
        }
    }

    protected function _fireSubmit($onSubmit, $httpMethod) {
        call_user_func($onSubmit, $httpMethod);
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
}
