<?php
namespace waterada\ReactPHP;

class ReactPHP {
    public static function getRequest($name) {
        return (isset($_REQUEST[$name]) ? $_REQUEST[$name] : '');
    }

    /**
     * @param ReactElement $component
     * @return string
     */
    public static function element($component) {
        //まずは画面に表示されていた初期状態を再現する
        $component->construct([]);

        //サブミットを処理
        $handlerAddress = ReactPHP::getRequest('handlerAddress');
        if (!empty($handlerAddress)) {
            $handlerAddress = explode('.', $handlerAddress);
            $component->fireSubmit($handlerAddress, [new Event()]);

            //ステータスが変わったら、そこから下位を再描画
            $component->rerender();
        }

        //DOM描画
        $html = $component->toHtml() . self::writeBasicJs();
        return $html;
    }

    private static function writeBasicJs() {
        return <<< 'EOS'
<script type="text/javascript">
var ReactPHP = {};
ReactPHP.submitLink = function(node, handlerAddress) {
    var $form = $('#ReactPHPForm');
    $form.find('[name="handlerAddress"]').val(handlerAddress);
    $form.submit();
};
ReactPHP.submitForm = function(node, handlerAddress) {
    var $form = $(node);
    $('<input type="hidden" name="handlerAddress" value="' + handlerAddress + '" />').appendTo($form);
    $form.attr('method', 'post');
}
</script>
<form method="post" id="ReactPHPForm">
    <input type="hidden" name="handlerAddress" value="" />
</form>
EOS;
    }

    public static function marked($str, $opt) {
        if (!empty($opt['sanitize'])) {
            $str = htmlspecialchars($str);
        }
        $str = preg_replace('/\*(.*?)\*/', '<span style="font-weight:bold;">$1</span>', $str);
        return $str;
    }
}

class Event {
    public function preventDefault() {}
}
