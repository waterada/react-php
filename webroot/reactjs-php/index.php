<?
namespace comments;
require 'ReactJsByPHP.php';
use ReactJsByPHP\React;
use ReactJsByPHP\ReactComponent;
use ReactJsByPHP\Console;
use ReactJsByPHP\JQuery;
use ReactJsByPHP\Event;

//var Comment = React.createClass({
class Comment extends ReactComponent {
    public function rawMarkup() {
        $rawMarkup = \ReactJsByPHP\marked($this->props('children')->toString(), ['sanitize' => true]);
        return ['__html' => $rawMarkup];
    }

    public function render() {
        return <<< EOS
            <div className="comment">
                <h2 className="commentAuthor">
                    {$this->escape($this->props('author'))}
                </h2>
                <span dangerouslySetInnerHTML="{$this->escape($this->rawMarkup())}" />
            </div>
EOS;
    }
}

//var CommentBox = React.createClass({
class CommentBox extends ReactComponent {
    private function loadCommentsFromServer() {
        JQuery::ajax([
            'url' => $this->props('url'),
            'dataType' => 'json',
            'cache' => false,
            'success' => function($data) {
                $this->setState('data', $data);
            }, //bind(this)
            'error' => function($xhr, $status, $err) {
                Console::error($this->props('url'), $status, $err);
            }, //bind(this)
        ]);
    }

    public function handleCommentSubmit($comment) {
        $comments = $this->state('data');
        $newComments = array_merge($comments, [$comment]);
        $this->setState('data', $newComments);
        JQuery::ajax([
            'url' => $this->props('url'),
            'dataType' => 'json',
            'type' => 'POST',
            'data' => $comment,
            'success' => function($data) {
                $this->setState('data', $data);
            }, //bind(this)
            'error' => function($xhr, $status, $err) {
                Console::error($this->props('url'), $status, $err);
            }, //bind(this)
        ]);
    }

    public function getInitialState() {
        return ['data' => []];
    }

    public function componentDidMount() {
        $this->loadCommentsFromServer();
        //setInterval(this.loadCommentsFromServer, this.props.pollInterval);
    }

    public function render() {
        return <<< EOS
            <div className="commentBox">
                <h1>Comments</h1>
                <CommentList data="{$this->escape($this->state('data'))}" />
                <CommentForm onCommentSubmit="{$this->escape([$this, 'handleCommentSubmit'])}" />
            </div>
EOS;
    }
}

class CommentList extends ReactComponent {
    public function render() {
        $commentNodes = $this->props('data')->map(function($comment, $index) {
            return (
                // `key` is a React-specific concept and is not mandatory for the
                // purpose of this tutorial. if you're curious, see more here:
                // http://facebook.github.io/react/docs/multiple-components.html#dynamic-children
            <<< EOS
                    <Comment author="{$this->escape($comment['author'])}" key="{$this->escape($index)}">
                        {$this->escape($comment['text'])}
                    </Comment>
EOS
            );
        });
        return <<< EOS
            <div className="commentList">
                {$commentNodes}
            </div>
EOS;
    }
}

class CommentForm extends ReactComponent {
    public function handleSubmit(Event $e) {
        $e->preventDefault();
        $author = trim(React::findDOMNode($this->refs('author'))->value);
        $text = trim(React::findDOMNode($this->refs('text'))->value);
        if (!$text || !$author) {
            return;
        }
        $this->props('onCommentSubmit')->call(['author' => $author, 'text' => $text]);
        React::findDOMNode($this->refs('author'))->value = '';
        React::findDOMNode($this->refs('text'))->value = '';
    }

    public function render() {
        return <<< EOS
            <form className="commentForm" onSubmit="{$this->escape([$this, 'handleSubmit'])}" method="post">
                <input type="text" placeholder="Your name" ref="author" />
                <input type="text" placeholder="Say something..." ref="text" />
                <input type="submit" value="Post" />
            </form>
EOS;
    }
}

React::render(
    '<CommentBox url="../file/comments" />',
    $content
);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>PHPReact Tutorial</title>
</head>
<body>
<div id="content"><?= $content ?></div>
</body>
</html>