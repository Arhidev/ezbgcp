<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Laravel Vue SPA - API</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Nunito';
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      background-color: azure;
    }

    article {
      padding: 2rem;
      text-align: center;
      background-color: white;
      max-width: 48rem;
      margin: 0 auto;
      border: 1px solid #eee;
      border-top: none;
    }
  </style>
</head>

<body>
  <article>
    <h1>EZ-Bus - API</h1>
    <p>To use the admin panel of EZ-Bus, use:
      <?php
      //get the base address without last /
      $base_url = rtrim($_SERVER['SERVER_NAME'], '/');
      //get https or http
      $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';

      echo "<a href='$protocol://$base_url'>$protocol://$base_url</a>";

      ?>
    </p>
  </article>
</body>

</html>
