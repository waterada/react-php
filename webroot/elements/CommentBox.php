<?
namespace elements;
require '../../vendor/autoload.php';
use waterada\ReactPHP\ReactPHP;
use waterada\ReactPHP\ReactComponent;
use waterada\ReactPHP\File;

/**
 * @var ReactComponent|object $this
 * @var string $url
 * @var string $data
 */

$this->loadCommentsFromFile = function() use($url, &$data) {
    $comments = File::load($url);
    $data = $comments;
};

$this->handleCommentSubmit = function($comment) use($url, &$data) {
    $comments = $data;
    $comments[] = $comment;
    $data = $comments;
    File::save($url, $comments);
};

$this->getInitialState = function() use(&$data) {
    $data = [];
    return ['data' => []];
};

$this->componentDidMount = function() {
    $this->loadCommentsFromFile();
};

?>
<div class="commentBox">
    <h1>Comments</h1>
    <?= $this->include('CommentList', [
        'data' => $data,
    ]) ?>
    <?= $this->include('CommentForm', [
        'onCommentSubmit' => function ($comment) {
            $this->handleCommentSubmit($comment);
        },
    ]) ?>
</div>
