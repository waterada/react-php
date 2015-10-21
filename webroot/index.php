<?
namespace reactphp;
require '../vendor/autoload.php';
use waterada\ReactPHP\ReactPHP;
use waterada\ReactPHP\ReactComponent;
use waterada\ReactPHP\File;
function h($str) { return htmlspecialchars($str); } //デファオルトでの無害化は諦める

//  CommentBox
//      CommentList
//          Comment
//      CommentForm

class CommentBox extends ReactComponent {
    private function loadCommentsFromFile() {
        $url = $this->props('url');
        $comments = File::load($url);
        $this->setState('data', $comments);
    }

    private function handleCommentSubmit($comment) {
        $url = $this->props('url');
        $comments = $this->state('data');
        $comments[] = $comment;
        $this->setState('data', $comments);
        File::save($url, $comments);
    }

    private function handleDeleteSubmit($deleteKey) {
        $url = $this->props('url');
        $comments = $this->state('data');
        array_splice($comments, $deleteKey, 1);
        $this->setState('data', $comments);
        File::save($url, $comments);
    }

    protected function getInitialState() {
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
                'data' => $this->state('data'),
                'onDeleteSubmit' => function ($deleteKey) {
                    $this->handleDeleteSubmit($deleteKey);
                },
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

class CommentList extends ReactComponent {
    public function render() {
        ?>
        <div class="commentList">
            <? foreach ($this->props('data') as $index => $comment): ?>
                <?= $this->element(new Comment([
                    'author' => $comment['author'],
                    'key'    => $index,
                    'text'   => $comment['text'],
                    'onDeleteSubmit' => $this->props('onDeleteSubmit'),
                ])) ?>
            <? endforeach; ?>
        </div>
        <?
    }
}

class Comment extends ReactComponent {
    private function rawMarkup($text) {
        $rawMarkup = ReactPHP::marked($text, ['sanitize' => true]);
        return $rawMarkup;
    }

    public function handleDeleteSubmit() {
        call_user_func($this->props('onDeleteSubmit'), $this->props('key'));
    }

    public function render() {
        ?>
        <div class="comment">
            <h2 class="commentAuthor">
                <?= h($this->props('author')) ?>
            </h2>
            <span><?= $this->rawMarkup($this->props('text')) /* 無害化不要 */ ?></span>
            <a <?= $this->onSubmitA('handleDeleteSubmit') ?>>x</a>
        </div>
        <?
    }
}

class CommentForm extends ReactComponent {
    public function handleSubmit() {
        $author = trim(ReactPHP::getRequest('author'));
        $text = trim(ReactPHP::getRequest('text'));
        if (!$text || !$author) {
            return;
        }
        call_user_func($this->props('onCommentSubmit'), ['author' => $author, 'text' => $text]);
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
</head>
<body>
<div id="content">
    <?= ReactPHP::element(new CommentBox([
        'url' => 'file/comments',
    ])) ?>
</div>
</body>
</html>
<!--
//    $(document).on('click', '.delete-link', function() { //Commentの仕事
//        var deleteKey = $(this).attr('data-key'); //Commentの仕事
//        $('#deleteKey').val(deleteKey); //formの仕事
//        $('form').submit(); //formの仕事
//    });
-->