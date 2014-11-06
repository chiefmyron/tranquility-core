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
        
        <?php if (count($this->scope) > 0): ?>
        <p>
            Do you give <b>ApplicationName</b> permission to perform the following actions:
        </p>    
             
        <li>
        <?php foreach ($this->scope as $scopeItem): ?>
            <ul><?php echo $scopeItem; ?></ul>
        <?php endforeach; ?>
        </li>
        <?php endif; ?>
        
        <p>
            <form method="post">
                <input type="submit" name="authorized" value="yes">
                <input type="submit" name="authorized" value="no">
            </form>
        </p>
      </div>

    </div><!-- /.container -->
  </body>
</html>