<?
namespace waterada\reactjsphp;
require '../../vendor/autoload.php';
require 'ReactJsByPHP.php';
use waterada\ReactPHP\ReactPHP;
use waterada\ReactJsByPHP\React;
use waterada\ReactJsByPHP\ReactComponent;
use waterada\ReactJsByPHP\Console;
use waterada\ReactJsByPHP\JQuery;
use waterada\ReactPHP\Event;
function marked($str, $opt) { return ReactPHP::marked($str, $opt); }

//var Comment = React.createClass({
class Comment extends ReactComponent {
    public function rawMarkup() {
        $rawMarkup = marked($this->props('children')->toString(), ['sanitize' => true]);
        return ['__html' => $rawMarkup];
    }

    public function render() {
        return $this->twig('
            <div className="comment">
                <h2 className="commentAuthor">
                    {{this.props.author}}
                </h2>
                <span dangerouslySetInnerHTML="{{this.rawMarkup()}}" />
            </div>
        ');
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
        return $this->twig('
            <div className="commentBox">
                <h1>Comments</h1>
                <CommentList data="{{this.state.data}}" />
                <CommentForm onCommentSubmit="{{this.handleCommentSubmit}}" />
            </div>
        ');
    }
}

class CommentList extends ReactComponent {
    public function render() {
        $commentNodes =  $this->props('data')->map(function($comment, $index) {
            return $this->twig(
                // `key` is a React-specific concept and is not mandatory for the
                // purpose of this tutorial. if you're curious, see more here:
                // http://facebook.github.io/react/docs/multiple-components.html#dynamic-children
                '
                <Comment author="{{comment.author}}" key="{{index}}">
                    {{comment.text}}
                </Comment>
                ', compact('comment', 'index')
            );
        });
        return $this->twig('
            <div className="commentList">
                {{commentNodes}}
            </div>
        ', compact('commentNodes'));
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
        return $this->twig('
            <form className="commentForm" onSubmit="{{this.handleSubmit}}">
                <input type="text" placeholder="Your name" ref="author" />
                <input type="text" placeholder="Say something..." ref="text" />
                <input type="submit" value="Post" />
            </form>
        ');
    }
}

React::render(
    __NAMESPACE__,
    '<CommentBox url="../file/comments" />',
    $content
);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>PHPReact Tutorial</title>
    <script type="text/javascript" src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
</head>
<body>
<div id="content"><?= $content ?></div>
</body>
</html>