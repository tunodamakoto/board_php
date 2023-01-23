<?php
session_start();

// データベースの接続情報
define('DB_HOST', 'mysql:charset=UTF8;dbname=board;host=localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

date_default_timezone_set('Asia/Tokyo');

$image_name = null;
$view_message = array();

include_once 'functions.php';

// データベースに接続
try {

    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO(DB_HOST, DB_USER, DB_PASSWORD, $option);

} catch(PDOException $e) {
    
    $error_message[] = $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>掲示板</title>
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <form method="post" class="edit">
        <ul class="error-message">
            <li>・名前を入力して下さい。</li>
            <li>・メッセージを入力して下さい。</li>
        </ul>
        <label for="name" class="edit-label">表示名</label>
        <input type="text" class="edit-input" id="name" name="name" value="">
        <label for="message" class="edit-label">メッセージ</label>
        <textarea name="message" id="message" class="edit-textarea"></textarea>
            <!-- <p>画像ファイル</p>
            <img src="img/20230122190246.jpg" alt="">
            <input type="file" name="image"> -->
        <a class="edit-cansel" href="admin.php">キャンセル</a>
        <input type="submit" name="edit_submit" class="edit-submit" value="更新">
    </form>
</body>