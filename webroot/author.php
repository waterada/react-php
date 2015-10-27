<?
namespace author;
require '../vendor/autoload.php';
use waterada\ReactPHP\ReactPHP;
use waterada\ReactPHP\ReactComponent;
use waterada\ReactPHP\File;
function h($str) { return htmlspecialchars($str); } //デファオルトでの無害化は諦める

//  CommentBox
//      CommentList
//          Comment
//      CommentForm

/**
 * @property string $props_url
 * @property array  $state_data
 */
class CommentBox extends ReactComponent {
    private function loadCommentsFromFile() {
        $comments = File::load($this->props_url);
        $this->state_data = $comments;
    }

    private function handleCommentSubmit($comment) {
        $comments = $this->state_data;
        $comments[] = $comment;
        $this->state_data = $comments;
        File::save($this->props_url, $comments);
    }

    public function onDelete($key) {
        $comments = $this->state_data;
        array_splice($comments, $key, 1);
        $this->state_data = $comments;
        File::save($this->props_url, $comments);
    }

    protected function getInitialState() {
        $this->state_data = [];
        return ['data' => []];
    }

    protected function componentDidMount() {
        $this->loadCommentsFromFile();
    }

    public function getLastComment() {
        $comments = $this->state_data;
        return array_pop($comments);
    }

    public function render() {
        ?>
        <div class="commentBox">
            <h1>Comments</h1>
            <?= $this->element(new CommentList([
                'data' => $this->state_data,
                'onDelete' => [$this, 'onDelete'],
            ])) ?>
            <?= $this->element(new CommentForm([
                'onCommentSubmit' => function ($comment) {
                    $this->handleCommentSubmit($comment);
                },
                'getLastComment' => [$this, 'getLastComment'],
            ])) ?>
        </div>
        <?
    }
}

/**
 * @property array $props_data
 * @property callable $props_onDelete
 */
class CommentList extends ReactComponent {
    public function render() {
        ?>
        <div class="commentList">
            <? foreach ($this->props_data as $index => $comment): ?>
                <?= $this->element(new Comment([
                    'author' => $comment['author'],
                    'key'    => $index,
                    'text'   => $comment['text'],
                    'onDelete' => $this->props_onDelete,
                ])) ?>
            <? endforeach; ?>
        </div>
        <?
    }
}

/**
 * @property string $props_author
 * @property string $props_text
 * @property string $props_key
 * @property callable $props_onDelete
 */
class Comment extends ReactComponent {
    private function rawMarkup($text) {
        $rawMarkup = ReactPHP::marked($text, ['sanitize' => true]);
        return $rawMarkup;
    }

    public function render() {
        ?>
        <div class="comment">
            <h2 class="commentAuthor">
                <?= h($this->props_author) ?>
            </h2>
            <span><?= $this->rawMarkup($this->props_text) /* 無害化不要 */ ?></span>
            <?= $this->element(new CommentDelete([
                'key' => $this->props_key,
                'onDelete' => $this->props_onDelete,
            ])) ?>
        </div>
        <?
    }
}

/**
 * @property callable $props_onCommentSubmit
 * @property callable $props_getLastComment
 */
class CommentForm extends ReactComponent {
    public function handleSubmit() {
        $author = trim(ReactPHP::getRequest('author'));
        $text = trim(ReactPHP::getRequest('text'));
        if (!$text || !$author) {
            return;
        }
        call_user_func($this->props_onCommentSubmit, ['author' => $author, 'text' => $text]);
    }

    public function getLastAuthor() {
        $comment = call_user_func($this->props_getLastComment);
        if (empty($comment)) {
            return "";
        } else {
            return $comment['author'];
        }
    }

    public function render() {
        ?>
        <form class="commentForm" onsubmit="<?= $this->onSubmit([$this, 'handleSubmit'], 'submitForm') ?>">
            <input type="text" placeholder="Your name" name="author" value="<?= $this->getLastAuthor() ?>" />
            <input type="text" placeholder="Say something..." name="text"/>
            <input type="submit" value="Post"/>
        </form>
        <?
    }
}

/**
 * @property boolean  $state_opened
 * @property integer  $props_key
 * @property callable $props_onDelete
 */
class CommentDelete extends ReactComponent {
    protected function getInitialState() {
        $this->state_opened = false;
        return ['opened' => false];
    }

    public function doDelete() {
        $this->state_opened = false;
        call_user_func($this->props_onDelete, $this->props_key);
    }

    public function doCancel() {
        $this->state_opened = false;
    }

    public function doOpen() {
        $this->state_opened = true;
    }

    public function render() {
        if ($this->state_opened) {
            ?>
            <span style="font-size: 12px;">[
                <a href="javascript:<?= $this->onSubmitLink('doDelete') ?>">本当に削除する</a>
                &nbsp;
                <a href="javascript:<?= $this->onSubmitLink('doCancel') ?>">キャンセル</a>
            ]</span>
            <?
        } else {
            ?>
            <span style="font-size: 12px;">
                [<a href="javascript:<?= $this->onSubmitLink('doOpen') ?>">削除</a>]
            </span>
            <?
        }
    }
}


?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>ReactPHP Tutorial</title>
    <!--suppress JSUnresolvedLibraryURL -->
    <script type="text/javascript" src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
    <link rel="stylesheet" type="text/css" href="/basic.css" />
</head>
<body>
<div id="content">
    <?= ReactPHP::element(new CommentBox([
        'url' => 'file/comments',
    ])) ?>
</div>
</body>
</html>