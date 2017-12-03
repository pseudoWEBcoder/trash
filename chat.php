<?php

class Chat
{
    public $db;
    private $info;
    public $user;

    public function __construct()
    {
        $this->db = new SQLite3 ('chat.sqlite');
        $this->db->query('CREATE TABLE IF NOT EXISTS  chat(id INTEGER PRIMARY KEY  AUTOINCREMENT, timestamp INT, ip TEXT,   message TEXT,  data TEXT,  userid INT)');
        $this->db->query('CREATE TABLE IF NOT EXISTS  users(id INTEGER PRIMARY KEY, timestamp INT,   name TEXT,   uid TEXT,lastview INT ,    data TEXT,  pass TEXT)');
        $this->info['chat'] = $this->IndexBy($this->asArray($this->db->query('PRAGMA  table_info(chat);')), 'name');
        $this->info['users'] = $this->IndexBy($this->asArray($this->db->query('PRAGMA  table_info(users);')), 'name');

    }

    public function createUser()
    {
        $userId = $_COOKIE['user'];
        if (!$userId)
            $userId = uniqid();
        if ($this->user = $this->getUser($userId)) {
            $this->update('users', ['(id = ' . $this->user['id'] . ')'], ['lastview' => time()]);
            return $this->user;
        }
        $new = $this->insertUser($userId);
        if ($new)
            $seted = setcookie('user', $userId, time() + 60 * 60 * 24 * 360);
        if ($seted)
            $this->user = $this->getUser($userId);
        return $this->user;


    }

    protected function getUser($uniqueid)
    {
        $sql = sprintf('SELECT * FROM users  WHERE  uid =\'%s\';', SQlite3::escapeString($uniqueid));
        return $this->db->query($sql)->fetchArray(SQLITE3_ASSOC);

    }

    protected function insertCat($text, $userid)
    {
        $vals = [
            'id' => null,
            'timestamp' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'message' => $text,
            'data' => json_encode(['_SERVER' => $_SERVER, '_REQUEST' => $_REQUEST]),
            'userid' => $userid,
        ];

        $inserted = $this->insert('chat', $vals);
        return $inserted;
    }

    protected function insertUser($uid, $name = null)
    {
        $vals = [
            'id' => null,
            'timestamp' => time(),
            'name' => $name,
            'uid' => $uid,
            'lastview' => time(),
            'data' => null,
            'pass' => null,
        ];
        $inserted = $this->insert('users', $vals);
        return $inserted;
    }

    public function getAll()
    {
        $result = $this->select('chat', '*', null, null);
        return $result;

    }

    public function getonlineUsers()
    {
        $time = time();
        $result = $this->select('users', '*', '(lastview BETWEEN ' . ($time - (60 * 60)/* за  час*/) . ' AND ' . $time . ')');
        return $result;

    }

    protected function select($table, $select, $where = null, $limit = null)
    {
        if (!isset($this->info[$table]))
            throw  new  Exception('$table is  invalid');
        $builder = ['SELECT' => $select, 'FROM' => $table, 'WHERE' => $where, 'LIMIT' => $limit];
        $sql = '';
        foreach ($builder as $index => $item) {
            if (!empty($item))
                $sql[] = $index . ' ' . $item;

        }
        $sql = implode(PHP_EOL, $sql);
        $res = $this->db->query($sql);
        $result = $this->asArray($res);
        return $result;
    }

    protected function IndexBy($array, $key)
    {
        $new = [];
        foreach ($array as $index => $item) {
            $new[$item[$key]] = $item;
        }
        return $new;
    }

    protected function asArray(SQLite3Result $result)
    {
        $res = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
            $res[] = $row;
        return $res;
    }

    public function write($text, $userid)
    {
        return $this->insertCat($text, $userid);

    }

    private function insert($table, $values)
    {
        if (!isset($this->info[$table])) {
            return false;
        }
        $columns = [];
        foreach ($values as $index => $item) {
            if (isset($this->info[$table][$index])) {
                $columns[$index]++;
                $escaped[] = $this->escape($item, $this->info[$table][$index]['type']);
            }
        }
        $sql = 'INSERT INTO   ' . $table . '(' . implode(',', array_keys($columns)) . ') VALUES ( ' . implode(',', $escaped) . ')';
        $inserted = $this->db->exec($sql);
        return $inserted;

    }


    private function update($table, $where, $values)
    {
        if (!isset($this->info[$table]))
            return false;
        foreach ($values as $index => $item) {
            if (isset($this->info[$table][$index])) {
                $columns[$index]++;
                $clean[] = $index . ' =' . $this->escape($item, $this->info[$table][$index]['type']);
            }
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(',', $clean) . ' WHERE ' . implode(' AND ', $where);
        $updated = $this->db->exec($sql);
        return $updated;
    }

    protected function escape($val, $type)
    {/*array (
  0 => 'INTEGER',
  1 => 'INT',
  2 => 'TEXT',
)*/
        if (is_null($val))
            return 'NULL';
        switch ($type) {

            case 'INT':
            case  'INTEGER':
                return (int)$val;
                break;
            default:
                return "'" . Sqlite3::escapeString($val) . "'";
        }

    }

    public function updateUser($user, $password, $id)
    {
        $username = "'" . SQlite3::escapeString($user) . "'";

        $exists = $this->select('users', '*', '(name=' . ($username) . ')');
        if ($exists)
            return false;
        return $this->update('users', ['(id = ' . $this->user['id'] . ')'], ['name' => $user, 'pass' => $password]);
    }
}

class View
{
    public $title;
    private $chat;

    /**
     * View constructor.
     * @param $title
     */
    public function __construct($title, $chat)
    {
        $this->title = $title;
        $this->chat = $chat;
    }

    public function renderUsers($users)
    {
        $rows = '';
        foreach ($users as $index => $item) {
            $rows .= '<tr data-id="' . $item['id'] . '">
		                <td>' . ($index + 1) . '</td>
		                <td>' . ($item['name'] ? $item['name'] : '<em>(not set)</em>') . '</td>
		            </tr>';
        }
        return '            <div class="col-sm-4">
                  <div class="card text-white bg-white">
		    <div class="card-heading top-bar  bg-primary">
                    <div class="col-md-8 col-xs-8">
                        <h3 class="card-title"><span class="glyphicon glyphicon-book"></span> Online</h3>
                    </div>
                </div>
		    <table class="table table-striped table-hover">
		        <tbody>
		           ' . $rows . '
		        </tbody>
		    </table>
		</div>
                 </div>
                 
                 
                 
                 ';

    }

    public function render()
    {
        $items = $this->chat->getAll();
        $str = '<div class="container">
	<div class="row">';
        $str .= $this->renderUsers($this->chat->getonlineUsers());
        $str .= ' <div class="col-sm-8">
                  <div class="chatbody">
                  <div class="card card-primary">
                <div class="card-heading  bg-primary top-bar">
                    <div class="col-md-8 col-xs-8">
                        <h3 class="card-title"><span class="glyphicon glyphicon-comment"></span> Chat - Miguel</h3>
                    </div>
                </div>
                <div class="card-body msg_container_base">';
        foreach ($items as $index => $item) {
            //if(isset($item['child']))
            $str .= $this->renderItem($item);
        }
        $str .= $this->renderForm();
        $str .= '	</div>

                 </div>
             </div>';
        return $this->fullRender(['body' => $str]);

    }

    public function fullRender()
    {
        $args = func_get_args();
        extract($args[0]);
        $str = '<p><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>' . $title . '</title>
  <style>
  .chatperson{
  display: block;
  border-bottom: 1px solid #eee;
  width: 100%;
  display: flex;
  align-items: center;
  white-space: nowrap;
  overflow: hidden;
  margin-bottom: 15px;
  padding: 4px;
}
.chatperson:hover{
  text-decoration: none;
  border-bottom: 1px solid orange;
}
.namechat {
    display: inline-block;
    vertical-align: middle;
}
.chatperson .chatimg img{
  width: 40px;
  height: 40px;
  background-image: url(\'http://i.imgur.com/JqEuJ6t.png\');
}
.chatperson .pname{
  font-size: 18px;
  padding-left: 5px;
}
.chatperson .lastmsg{
  font-size: 12px;
  padding-left: 5px;
  color: #ccc;
}

body{
    height:400px;
    position: fixed;
    bottom: 0;
}
.col-md-2, .col-md-10{
    padding:0;
}
.card{
    margin-bottom: 0px;
}
.chat-window{
    bottom:0;
    position:fixed;
    float:right;
    margin-left:10px;
}
.chat-window > div > .card{
    border-radius: 5px 5px 0 0;
}
.icon_minim{
    padding:2px 10px;
}
.msg_container_base{
  background: #e5e5e5;
  margin: 0;
  padding: 0 10px 10px;
  max-height:300px;
  overflow-x:hidden;
}
.top-bar {
  background: #666;
  color: white;
  padding: 10px;
  position: relative;
  overflow: hidden;
}
.msg_receive{
    padding-left:0;
    margin-left:0;
}
.msg_sent{
    padding-bottom:20px !important;
    margin-right:0;
}
.messages {
  background: white;
  padding: 10px;
  border-radius: 2px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  max-width:100%;
}
.messages > p {
    font-size: 13px;
    margin: 0 0 0.2rem 0;
  }
.messages > time {
    font-size: 11px;
    color: #ccc;
}
.msg_container {
    padding: 10px;
    overflow: hidden;
    display: flex;
}
img {
    display: block;
    width: 100%;
}
.avatar {
    position: relative;
}
.base_receive > .avatar:after {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 0;
    height: 0;
    border: 5px solid #FFF;
    border-left-color: rgba(0, 0, 0, 0);
    border-bottom-color: rgba(0, 0, 0, 0);
}

.base_sent {
  justify-content: flex-end;
  align-items: flex-end;
}
.base_sent > .avatar:after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 0;
    border: 5px solid white;
    border-right-color: transparent;
    border-top-color: transparent;
    box-shadow: 1px 1px 2px rgba(black, 0.2); // not quite perfect but close
}

.msg_sent > time{
    float: right;
}



.msg_container_base::-webkit-scrollbar-track
{
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
    background-color: #F5F5F5;
}

.msg_container_base::-webkit-scrollbar
{
    width: 12px;
    background-color: #F5F5F5;
}

.msg_container_base::-webkit-scrollbar-thumb
{
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,.3);
    background-color: #555;
}

.btn-group.dropup{
    position:fixed;
    left:0px;
    bottom:0;
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css"
          integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
          <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.1.3/css/jasny-bootstrap.min.css">



</head>
<body class="container">
' . $body . ';
$(".fileinput").fileinput();
$(document).on("mouseenter",".avatar",  function(){
    var  that =  $(this),  oldhtml =  that[0].outerHTML;
    that.html(placeholder);
    $(document).on("click",  function(event){
        if($(this).is(".avaror"))
        return  false;
        that[0].oldHTML =  oldhtml;
        $(event).off(this)
    })
})

}</script>
<script src = "https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js"></script>
<script src = "https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js"
integrity = "sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin = "anonymous"></script>
<script src = "//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.1.3/js/jasny-bootstrap.min.js"></script>
</body>
</html>';
        return $str;

    }

    public function renderForm($reply = null)
    {
        return '             <div class = "card-footer">
<form action = "" method = "POST">
<div class = "input-group">' .
            (isset($this->chat->user['name']) ? ('<span class = "input-group-addon" >' . $this->chat->user['name'] . '</span>') : '<input id = "btn-input" type = "text" name = "user"class = "form-control input-sm chat_input" placeholder = "nikname" />
<input id = "btn-input" type = "password" name = "password"class = "form-control input-sm chat_input" placeholder = "пароль (запомнить)" title = "введите пароль  и   потом   сможете авторизоваться (без  проверок и почт, просто введи  сюда пароль)" />') .
            '<input id = "btn-input" type = "text" name = "text"class = "form-control input-sm chat_input" placeholder = "Write your message here..." />
<span class = "input-group-btn">
<input type = "submit" name = "write" class = "btn btn-primary btn-sm" id = "btn-chat"/><i class = "fa fa-send fa-1x" aria-hidden = "true"></i></input>
</span>
</div>
</form>
</div>';

    }

    public function renderItem($item)
    {//https://bootsnipp.com/snippets/5MrA7
        $avatar = ' <div class = "col-md-2 col-xs-2 avatar">
<img src = "http://www.bitrebels.com/wp-content/uploads/2011/02/Original-Facebook-Geek-Profile-Avatar-1.jpg" class = " img-responsive ">
</div>';
        if ($this->chat->user->id == $item['userid']) {
            return ' <div class = "row msg_container ' . ('base_sent') . '">' .
                $avatar .
                '<div class = "col-md-10 col-xs-10">
<div class = "messages msg_sent">
<p>' . $item['message'] . '</p>
<time datetime = "2009-11-13T20:00">' . date('d.m.Y H:i:s', $item['timestamp']) . '</time>
</div>
</div>

</div>';
        } else {
            return ' <div class = "row msg_container ' . ('base_receive') . '">
<div class = "col-md-10 col-xs-10">
<div class = "messages msg_sent">
<p>' . $item['message'] . '</p>
<time datetime = "2009-11-13T20:00">' . date('d.m.Y H:i:s', $item['timestamp']) . '</time>
</div>
</div>
' . $avatar . '

</div>';
        }

    }
}

session_start();
$chat = new Chat();
$user = $chat->createUser();
$view = new View('чат', $chat);

if (isset($_REQUEST['user'])) {
    $chat->updateUser($_REQUEST['user'], $_REQUEST['password'], $user['id']);
}
if (isset($_REQUEST['write']) && !empty($_REQUEST['write'])) {
    $chat->write($_REQUEST['text'], $user['id']);
}
echo $view->render();
?>
