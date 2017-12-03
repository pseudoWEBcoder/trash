<?php

class  Singleton
{
    public static $instances = [];

    /**
     * Singleton constructor.
     */
    private function __construct()
    {
    }

    public static function getInstance()
    {
        $class = get_called_class();
        return isset(self::$instances[$class]) ? self::$instances[$class] : self::$instances[$class] = new  $class;
    }
}
class $db   extends Singleton{
    
}
class View extends Singleton
{
    private $body;

    private $title;


    public function render()
    {

        $str = $this->renderHeader();
        $str .= $this->renderContent();
        $str .= $this->renderFooter();
        return $str;
    }

    public function addContent($content)
    {
        $this->body .= $content;

    }

    private function renderContent()
    {
      return  $str = '<body class="container">' . $this->body;
    }

    private function renderFooter()
    {
        $str = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js"
        integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
</body>
</html>';
        return $str;
    }

    private function renderHeader()
    {
        $str = '<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>' . $this->title . '</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css"
          integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
</head>';
        return $str;
    }
}

$view = View::getInstance();
$view->addContent('<h1>привет</h1>');
echo  $view->render();
?>