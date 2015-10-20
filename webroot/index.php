<?
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

    public function handleCommentSubmit($comment) {
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
        return '
        <div class="commentBox">
            <h1>Comments</h1>
            {{ "CommentList" | element({data: this.state.data}) }}
            {{ "CommentForm" | element({onCommentSubmit: this.handleCommentSubmit}) }}
        </div>
        ';
    }
}

class CommentList extends ReactComponent {
    public function render() {
        return '
        <div class="commentList">
            {% for comment in this.props.data %}
                {{ "Comment" | element({author: comment.author, key: loop.index0, text: comment.text}) }}
            {% endfor %}
        </div>
        ';
    }
}

class Comment extends ReactComponent {
    public function rawMarkup($text) {
        $rawMarkup = ReactPHP::marked($text, ['sanitize' => true]);
        return $rawMarkup;
    }

    public function render() {
        return '
        <div class="comment">
            <h2 class="commentAuthor">
                {{this.props.author}}
            </h2>
            <span>{{this.rawMarkup(this.props.text)|raw}}</span>
        </div>
        ';
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
        return '
        <form class="commentForm" onsubmit="{{this.handleSubmit|onsubmit}}">
            <input type="text" placeholder="Your name" name="author"/>
            <input type="text" placeholder="Say something..." name="text"/>
            <input type="submit" value="Post"/>
        </form>
        ';
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