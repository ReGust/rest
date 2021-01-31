<?php

require_once 'db.php';

session_start();

    $length = 2;

    $instance = new db('root','');

        try {
            $connection = $instance->connect();
        } catch (PDOException $exception) {
            print_r('Problem with connecting to the Database: '.PHP_EOL);
            print_r($exception->getMessage());
        }


    if (isset($_POST['submit'])) {
        $_SESSION['pages'] = null;
        $needle = $_POST['search'];
        $_SESSION['start'] = 0;
        $_SESSION['results'] = $instance->getData($needle);
        $count = count($_SESSION['results']);
        $data = displayJson();

        $_SESSION['pages'] = $count / 100;
    }
    if (isset($_POST['pagn'])) {
        $page = $_POST['pagn'];
        $data = displayJson($page);

    }

    function displayJson($page = 1)
    {

        $i = 1;
        $j = 0;
        foreach ($_SESSION['results'] as $result) {
            if ($j < 100) {
                $j++;
            } else {
                ++$i;
                $j = 1;
            }
            $pages[$i][] = $result;
        }
        return $pages[$page];
    }

?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <title>Document</title>
</head>
<body>

<div class="row">
    <div class="col-sm-4 offset-sm-4">
        <div class="card" style="margin-top: 25%;">
            <div class="card-title" style="margin: auto;">
                <form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
                    <input type="text" name="search" id="search">
                    <input type="submit" name="submit" value="Search">
                </form>
            </div>
            <div class="card-body">
                <?php if (isset($data)) {?>
                    <?php foreach ($data as $result):?>
                        <h5><?php print_r(json_encode($result))?></h5>
                    <?php endforeach;?>
                <?php } ?>
            </div>
            <div class="card-footer">
                <?php if (isset($_SESSION['pages'])) {?>
                    <form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
                        <?php for($j = 0; $j < $_SESSION['pages']; $j++):?>
                            <input type="submit" name="pagn" value=<?php print_r($j+1);?>>
                        <?php endfor;?>
                    </form>
                <?php };?>
            </div>
        </div>
    </div>
</div>

</body>
</html>


