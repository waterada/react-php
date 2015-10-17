<?
namespace comments2;
require 'lib/ReactPHP.php';
use ReactPHP\ReactPHP;
use ReactPHP\ReactComponent;
use ReactPHP\File;
function h($str) { return htmlspecialchars($str); } //デファオルトでの無害化は諦める

class CommentBox extends ReactComponent {
    private function loadCommentsFromFile($url) {
        $comments = File::load($url);
        $this->setState('data', $comments);
    }

    private function handleCommentSubmit($url, $comment) {
        $comments = $this->state('data');
        $comments[] = $comment;
        $this->setState('data', $comments);
        File::save($url, $comments);
    }

    protected function getInitialState() {
        return ['data' => []];
    }

    protected function componentDidMount() {
        $this->loadCommentsFromFile($this->props('url'));
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
                    $this->handleCommentSubmit($this->props('url'), $comment);
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
        $rawMarkup = htmlspecialchars($text);
        $rawMarkup = str_replace('react', '<b>react</b>', $rawMarkup);
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
    public function handleSubmit($onCommentSubmit) {
        $author = trim(ReactPHP::getRequest('author'));
        $text = trim(ReactPHP::getRequest('text'));
        if (!$text || !$author) {
            return;
        }
        call_user_func($onCommentSubmit, ['author' => $author, 'text' => $text]);
    }

    public function render() {
        $this->onSubmit('POST', function () {
            $this->handleSubmit($this->props('onCommentSubmit'));
        });
        ?>
        <form class="commentForm" method="post">
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
</head>
<body>
<div id="content">
    <?= ReactPHP::element(new CommentBox([
        'url' => 'file/comments',
    ])) ?>
</div>
</body>
</html>