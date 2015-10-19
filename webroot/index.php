<?
namespace comments;
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

    public function render() {
        ?>
        <div class="comment">
            <h2 class="commentAuthor">
                <?= h($this->props('author')) ?>
            </h2>
            <span><?= $this->rawMarkup($this->props('text')) /* 無害化不要 */ ?></span>
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
        <form class="commentForm" <?= $this->onSubmitForm('handleSubmit') ?>>
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