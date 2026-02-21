<?php
?>
<!doctype html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title><?php if(isset($title)){echo $title;} else echo "Biblioteca Scrum"?></title>

        <?php if(!isset($path)) $path = "./";?>
        <link rel="stylesheet" href="<?= $path ?>public/css/style_global.css">
        <?php if (isset($page_css)): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($page_css); ?>">
        <?php endif; ?>

        <link rel="icon" href="<?= $path ?>public/assets/icon.png">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Young+Serif&display=swap" rel="stylesheet">
    </head>
    <body>
    <div class="contents">