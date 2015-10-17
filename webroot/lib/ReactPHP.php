<?php
namespace ReactPHP;

class ReactPHP {
    public static function getRequest($name) {
        return (isset($_REQUEST[$name]) ? $_REQUEST[$name] : '');
    }

    /**
     * @param ReactComponent $component
     * @return string
     */
    public static function element($component) {
        //サブミットを処理
        $httpMethod = strtoupper($_SERVER["REQUEST_METHOD"]);
        $component->fireSubmit($httpMethod);

        //ステータスが変わったら、そこから下位を再描画
        $component->rerender();

        //DOM描画
        $html = $component->toHtml();
        return $html;
    }
}

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

    public function __construct($props) {
        $this->props = $props;
        $this->state = $this->getInitialState();
        $this->componentDidMount();

        $this->doRender();

        $this->stateChanged = false;
    }

    private function doRender() {
        $this->children = [];
        $this->onSubmit = [];

        ob_start();
        $this->render();
        $this->html = ob_get_clean();
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

        //返す
        return $html;
    }

    /**
     * @return string
     */
    public abstract function render();

    /**
     * @param string   $httpMethod
     * @param callable $callback
     */
    public function onSubmit($httpMethod, $callback) {
        $this->onSubmit[$httpMethod][] = $callback;
    }

    public function fireSubmit($httpMethod) {
        if (isset($this->onSubmit[$httpMethod])) {
            foreach ($this->onSubmit[$httpMethod] as $onSubmit) {
                call_user_func($onSubmit);
            }
        }
        foreach ($this->children as $child) {
            $child->fireSubmit($httpMethod);
        }
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

class File {
    public static function load($path) {
        return json_decode(file_get_contents($path), true);
    }

    public static function save($path, $contents) {
        file_put_contents($path, json_encode($contents));
    }
}