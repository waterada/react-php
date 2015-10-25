<?php
namespace waterada\ReactJsByPHP;
use \waterada\ReactPHP\ReactPHP;
require_once 'DataForTwig.php';

class React {
    public static $NAMESPACE;

    /** @var ReactComponent */
    public static $top;

    public static function findDOMNode($name) {
        return new DomNode(isset($_POST[$name]) ? $_POST[$name] : '');
    }

    /**
     * @param string $namespace
     * @param string $xml
     * @param        $var
     * @return string
     */
    public static function render($namespace, $xml, &$var) {
        self::$NAMESPACE = $namespace;
        self::$top = new TopComponent(['xml' => $xml]);
        $var = ReactPHP::element(self::$top);
    }

//    public static function escape($obj) {
//        if (is_array($obj) && isset($obj[0]) && isset($obj[1]) && count($obj) == 2 && $obj[0] instanceof ReactComponent) {
//            return self::escape(['__CALLBACK__' => $obj[1]]);
//        } else {
//            if ($obj instanceof ReactValue) {
//                $obj = $obj->toString();
//            }
//            return htmlspecialchars(json_encode($obj));
//        }
//    }

    /**
     * @param \waterada\ReactPHP\ReactComponent $component
     * @param $method
     * @return string
     */
    public static function addCallback($component, $method) {
        $address = implode('.', array_merge($component->getAddress(), [$method]));
        return $address;
    }

    public static function callCallback($address, $args) {
        $address = explode('.', $address);
        return self::$top->fireSubmit($address, $args);
    }
}

abstract class ReactComponent extends \waterada\ReactPHP\ReactComponent {
    /**
     * @param string     $str
     * @param null|array $args
     * @return string
     */
    public function twig($str, $args = null) {
        /** @noinspection PhpDeprecationInspection */
        /** @noinspection PhpInternalEntityUsedInspection */
        $twig = new \Twig_Environment(new \Twig_Loader_String());
        if (!isset($str)) {
            $args = ['this' => new DataForTwig($this)];
        }
        return $twig->render($str, $args);
    }

    protected function _doRender() {
        $twig = $this->render();
        $xml = $this->twig($twig);
        $node = simplexml_load_string($xml);
        return $this->__parse($node);
    }

    private function __parse(\SimpleXMLElement $node) {
        $name = $node->getName();
        $class = React::$NAMESPACE . '\\' . $name;
        if (class_exists($class)) {
            $props = [];
            foreach ($node->attributes() as $key => $val) {
                $val = $this->decodeAttr((string) $val);
                $props[$key] = $val;
            }
            $child = trim((string) $node);
            if (!empty($child)) {
                $child = $this->decodeAttr($child);
                $props['children'] = [$child];
            }
            /** @var ReactComponent $component */
            $component = new $class($props);
            return $this->element($component);
        } else {
            $innerHTML = null;
            $html = '<' . $name;
            foreach ($node->attributes() as $key => $val) {
                $val = $this->decodeAttr($val);
                if ($key === 'className') {
                    $key = 'class';
                }
                if ($key === 'ref') {
                    $key = 'name';
                }
                if ($key === 'onSubmit') {
                    $method = preg_replace('/^.*\./', '', $val);
                    $val = $this->onSubmit([$this, $method], 'submitForm');
                    $key = 'onsubmit';
                }
                if ($key === 'dangerouslySetInnerHTML') {
                    $innerHTML = $val['__html'];
                    continue;
                }
                $html .= " " . $key . '="' . htmlspecialchars($val) . '"';
            }
            $html .= '>';
            if (isset($innerHTML)) {
                $html .= $innerHTML;
            } else {
                $children = [];
                foreach ($node->children() as $child) {
                    $children[] = $this->__parse($child);
                }
                $child = trim((string) $node);
                if (empty($children) && !empty($child)) {
                    $child = $this->decodeAttr($child);
                    $children[] = htmlspecialchars($child);
                }
                $html .= implode('', $children);
            }
            $html .= '</' . $name . '>';
            return $html;
        }
    }

//    protected function escape($obj) {
//        return React::escape($obj);
//    }

    private function decodeAttr($val) {
        $_val = json_decode($val, true);
        if ($_val === null) { //文字列で、引用符がない場合にjson_decodeが失敗するので救う
            $_val = (string) $val;
        }
        return $_val;
    }

    /**
     * @param string $key
     * @return ReactValue
     * @throws \Exception
     */
    public function props($key) {
        $val = parent::props($key);
        return new ReactValue($val);
    }

    /**
     * @param string $key
     * @return string
     */
    public function refs($key) {
        return $key;
    }

    protected function _fireSubmit($onSubmit,  $args = null) {
        return call_user_func_array($onSubmit, $args);
    }
}

class TopComponent extends ReactComponent {
    public function render() {
        return $this->props('xml')->toString();
    }
}

class ReactValue {
    private $value = null;
    public function __construct($value) {
        $this->value = $value;
    }

    /**
     * @param callable $callback
     * @return array
     */
    public function map($callback) {
        $array = $this->value;
        if (!empty($array)) {
            $array = array_map($callback, $array, range(0, count($array) - 1));
        }
        return new ReactValue($array);
    }

    /**
     * @param mixed $args
     * @return mixed
     */
    public function call($args = null) {
        return React::callCallback($this->value, func_get_args());
    }

    /**
     * @return string
     */
    public function toString() {
        if (is_array($this->value)) {
            return implode('', $this->value);
        }
        return $this->value;
    }

    public function getValue() {
        return $this->value;
    }

    public function __toString() {
        return $this->toString();
    }
}

class Console {
    public static function error($msg, $status, $err) {}
}

class JQuery {
    public static function ajax($config) {
        $url = $config['url'];
        $data = json_decode(file_get_contents($url), true);
        if (isset($config['type']) && $config['type'] === 'POST') {
            $comment = $config['data'];
            if (!empty($comment)) {
                $data[] = $comment;
                file_put_contents($url, json_encode($data));
            }
        }
        call_user_func($config['success'], $data);
    }
}

class DomNode {
    public $value;
    public function __construct($value) {
        $this->value = $value;
    }
}

class DataForTwig extends \waterada\ReactPHP\DataForTwig {
    public function _returnProps($key) {
        /** @var ReactValue $val */
        $val = $this->component->props($key);
        $val = $val->getValue();
        $val = new ValueForTwig($val);
        return $val;
    }

    public function _returnState($key) {
        $val = $this->component->state($key);
        $val = new ValueForTwig($val);
        return $val;
    }

    public function __call($key, $val) {
        $res = call_user_func_array([$this->component, $key], $val);
        $res = json_encode($res);
        return $res;
    }

    public function __get($key) {
        if (method_exists($this->component, $key)) {
            $code = React::addCallback($this->component, $key);
            return json_encode($code);
        }
        throw new \Exception('no key:' . $key . ' in ' . get_class($this->component));
    }
}

class ValueForTwig extends \ArrayObject {
    /** @var ReactValue */
    private $value;
    public function __construct($input = null, $flags = 0, $iterator_class = "ArrayIterator") {
        $this->value = $input;
        if (is_array($input)) {
            parent::__construct($input, $flags, $iterator_class);
        } else {
            parent::__construct([]);
        }
    }

    public function __toString() {
        return json_encode($this->value);
    }
}