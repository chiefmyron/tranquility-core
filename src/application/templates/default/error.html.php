<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tranquility API</title>

    <!-- Bootstrap -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/tranquility.css" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
<body>

    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">Tranquility API</a>
        </div>
      </div>
    </div>

    <div class="container">

      <div>
        <h1><?php echo $this->heading; ?> / <small><?php echo $this->subHeading; ?></small></h1>
        <p class="lead">
            <?php echo $this->message; ?>
        </p>
        <?php if ($this->displayDetailedErrors): ?>
            
        <h4>Request details</h4>
        <pre>
URI:  <?php echo $this->request->getUri()."\n"; ?>
Verb: <?php echo $this->request->getMethod()."\n"; ?>

Query string (GET) parameters:
<?php echo generateParameterText($this->request->query->all()); ?>

Request body (POST) parameters:
<?php echo generateParameterText($this->request->request->all()); ?>

Custom parameters:
<?php echo generateParameterText($this->request->attributes->all()); ?>
        </pre>
        
            <?php if (isset($this->exception)): ?>
        <h4>Exception details</h4>
        <pre><?php echo '['.get_class($this->exception).'] '.$this->exception->getMessage(); ?></pre>

        <h4>Stack trace</h4>
        <?php echo generateStackTraceTable($this->exception); ?>
            <?php endif; // Has exception assigned?>
        <?php endif; // Display detailed errors?>
      </div>

    </div><!-- /.container -->
  </body>
</html>

<?php 
function generateStackTraceTable($ex) {
    $trace = $ex->getTrace();
    $count = 0;
    $html = '';
    
    foreach ($trace as $item) {
        if (isset($item['file']) && strpos($item['file'], 'error.php') === false) {
            $html .= "<tr> \n";
            $html .= "  <td>".$count."</td> \n";
            if (isset($item['class'])) {
                $html .= "  <td>".$item['class']."->".$item['function']."()</td> \n";
            } else {
                $html .= "  <td>".$item['function']."()</td> \n";
            }
            $html .= "  <td>".$item['file']."</td> \n";
            $html .= "  <td align='right'>".$item['line']."</td> \n";
            $html .= "</tr>\n";
            $count++;
        }
    }
    
    if ($html !== '') {
        $table  = "<table class='table table-striped'> \n";
        $table .= "  <tr> \n";
        $table .= "    <th>#</th> \n";
        $table .= "    <th>Function</th> \n";
        $table .= "    <th>Location</th> \n";
        $table .= "    <th>Line</th> \n";
        $table .= "  </tr> \n";
        $table .= $html;
        $table .= "</table> \n";
        return $table;
    }
    
    return '';
}

function generateParameterText($params) {
    $output = '';
    foreach ($params as $key => $value) {
        $output .= "    [".$key."] => ".$value."\n";
    }
    
    if ($output == '') {
        $output = "    No parameters\n";
    }
    return $output;
}