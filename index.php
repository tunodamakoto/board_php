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

if(!empty($_POST['submit'])) {

    // 空白除去
	$name = preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['name']);
	$message = preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['message']);

    // 名前チェック
    if(empty($name)) {
        $error_message[] = '名前を入力して下さい。';
    } else {
        // セッションに保存
        $_SESSION['name'] = $name;
    }

    // メッセージチェック
    if(empty($message)) {
        $error_message[] = 'メッセージを入力して下さい。';
    } else {
		// 文字数を確認
		if( 100 < mb_strlen($message, 'UTF-8') ) {
			$error_message[] = 'ひと言メッセージは100文字以内で入力してください。';
		}
	}

    // 書き込み
    if(empty($error_message)) {
        // 書き込み日時
        $post_date = date("Y-m-d H:i:s");

        // 画像をアップロード
        if(isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])){
            $image_name = uploadImage($_FILES['image']);
        }

        // トランザクション開始
        $pdo->beginTransaction();

        try{

            // SQL作成
            $stmt = $pdo->prepare("INSERT INTO post (name, message, image_name, post_date) VALUES (:name, :message, :image_name, :post_date)");

            // 値をセット
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':image_name', $image_name, PDO::PARAM_STR);
            $stmt->bindParam(':post_date', $post_date, PDO::PARAM_STR);

            // SQLクエリの実行
            $res = $stmt->execute();

            // コミット
            $res = $pdo->commit();

        } catch(Exception $e) {

            $pdo->rollBack();
        }

        if($res){
            $_SESSION['success_message'] = 'メッセージを書き込みました。';
        } else {
            $error_message[] = '書き込みに失敗しました。';
        }

        // プリペアードステートメントを削除
        $stmt = null;

        header('Location: ./');
		exit;
    }
}

if(!empty($pdo)) {

    // メッセージデータを取得
    $sql = "SELECT id, name, message, image_name, post_date FROM post ORDER BY post_date DESC";
    $view_message = $pdo->query($sql);
}

// データベースの接続を閉じる
$pdo = null;

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
    <div class="wrapper">
        <h1>掲示板</h1>
        <form action="" method="post" enctype="multipart/form-data">
            <div>
                <label for="name">名前</label>
                <input type="text" id="name" name="name" value="<?php if( !empty($_SESSION['name']) ){ echo htmlspecialchars( $_SESSION['name'], ENT_QUOTES, 'UTF-8'); } ?>">
            </div>
            <div>
                <label for="message">メッセージ</label>
                <textarea name="message" id="message"></textarea>
            </div>
            <input type="file" name="image">
            <input type="submit" name="submit" value="書き込む" class="submit">
        </form>
        <hr>
        <?php if(empty($_POST['submit']) && !empty($_SESSION['success_message'])): ?>
            <ul class="success-message">
                <li><?php echo $_SESSION['success_message']; ?></li>
                <?php unset($_SESSION['success_message']); ?>
            </ul>
        <?php endif; ?>
        <?php if(isset($error_message)): ?>
            <ul class="error-message">
                <?php foreach($error_message as $value): ?>
                    <li>・<?php echo $value; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <section>
            <?php if(isset($view_message)): ?>
                <?php foreach($view_message as $value): ?>
                <article>
                    <div class="info">
                        <h2><?php echo htmlspecialchars( $value['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <time><?php echo date('Y年m月d日 H:i', strtotime($value['post_date'])); ?></time>
                        <div>
                            <a href="edit.php?message_id=<?php echo $value['id']; ?>">編集</a>
                            <a href="delete.php?message_id=<?php echo $value['id']; ?>">削除</a>
                        </div>
                    </div>
                    <p><?php echo htmlspecialchars( $value['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if(!empty($value['image_name'])): ?>
                        <img src="<?php echo buildImagePath($value['image_name']); ?>" alt="">
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>