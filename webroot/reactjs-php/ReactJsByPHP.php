<?php
namespace ReactJsByPHP;
require '../lib/ReactPHP.php';

function marked($str, $opt) {
    return \ReactPHP\marked($str, $opt);
}

class React {
    public static function findDOMNode($name) {
        return new DomNode(isset($_POST[$name]) ? $_POST[$name] : '');
    }

    /**
     * @param string $xml
     * @param        $var
     * @return string
     */
    public static function render($xml, &$var) {
        $top = new TopComponent(['xml' => $xml]);

        //サブミットを処理
        $httpMethod = strtoupper($_SERVER["REQUEST_METHOD"]);
        $top->fireSubmit($httpMethod);

        //ステータスが変わったら、そこから下位を再描画
        $top->rerender();

        //DOM描画
        $var = $top->toHtml();
    }

    public static function escape($obj) {
        if (is_array($obj) && isset($obj[0]) && isset($obj[1]) && count($obj) == 2 && $obj[0] instanceof ReactComponent) {
            return self::escape(['__CALLBACK__' => $obj[1]]);
        } else {
            if ($obj instanceof ReactValue) {
                $obj = $obj->toString();
            }
            return htmlspecialchars(json_encode($obj));
        }
    }
}

abstract class ReactComponent extends \ReactPHP\ReactComponent {
    protected function _doRender() {
        $xml = $this->render();
        $node = simplexml_load_string($xml);
        return $this->__parse($node);
    }

    private function __parse(\SimpleXMLElement $node) {
        $name = $node->getName();
        $class = 'comments\\' . $name;
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
                    $this->onSubmit("POST", $val);
                    continue;
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

    protected function escape($obj) {
        return React::escape($obj);
    }

    private function decodeAttr($val) {
        $_val = json_decode($val, true);
        if ($_val === null) { //文字列で、引用符がない場合にjson_decodeが失敗するので救う
            $_val = json_decode(json_encode((string) $val), true);
        }
        if (is_array($_val) && isset($_val['__CALLBACK__'])) {
            $_val = [$this, $_val['__CALLBACK__']];
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

    protected function _fireSubmit($onSubmit) {
        call_user_func($onSubmit, new Event());
    }
}

class TopComponent extends ReactComponent {
    public function render() {
        return $this->props('xml');
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
        return call_user_func($this->value, $args);
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
            $data[] = $comment;
            file_put_contents($url, json_encode($data));
        }
        call_user_func($config['success'], $data);
    }
}

class Event {
    public function preventDefault() {}
}

class DomNode {
    public $value;
    public function __construct($value) {
        $this->value = $value;
    }
}
