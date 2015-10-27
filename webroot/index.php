<?
namespace reactphp;
require '../vendor/autoload.php';
use waterada\ReactPHP\ReactPHP;
use waterada\ReactPHP\ReactElement;
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
class CommentBox extends ReactElement {
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

    protected function getInitialState() {
        $this->state_data = [];
        return ['data' => []];
    }

    protected function componentDidMount() {
        $this->loadCommentsFromFile();
    }

    public function render() {
        ?>
        <div class="commentBox">
            <h1>Comments</h1>
            <?= $this->element(new CommentList([
                'data' => $this->state_data,
            ])) ?>
            <?= $this->element(new CommentForm([
                'onCommentSubmit' => function ($comment) {
                    $this->handleCommentSubmit($comment);
                },
            ])) ?>
        </div>
        <?
    }
}

/**
 * @property array $props_data
 */
class CommentList extends ReactElement {
    public function render() {
        ?>
        <div class="commentList">
            <? foreach ($this->props_data as $index => $comment): ?>
                <?= $this->element(new Comment([
                    'author' => $comment['author'],
                    'key'    => $index,
                    'text'   => $comment['text'],
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
 */
class Comment extends ReactElement {
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
        </div>
        <?
    }
}

/**
 * @property callable $props_onCommentSubmit
 */
class CommentForm extends ReactElement {
    public function handleSubmit() {
        $author = trim(ReactPHP::getRequest('author'));
        $text = trim(ReactPHP::getRequest('text'));
        if (!$text || !$author) {
            return;
        }
        call_user_func($this->props_onCommentSubmit, ['author' => $author, 'text' => $text]);
    }

    public function render() {
        ?>
        <form class="commentForm" onsubmit="<?= $this->onSubmit([$this, 'handleSubmit'], 'submitForm') ?>">
            <input type="text" placeholder="Your name" name="author"/>
            <input type="text" placeholder="Say something..." name="text"/>
            <input type="submit" value="Post"/>
        </form>
        <?
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