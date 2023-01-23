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

if(!empty($_GET['message_id']) && empty($_POST['message_id'])) {

	// SQL作成
	$stmt = $pdo->prepare("SELECT * FROM post WHERE id = :id");

	// 値をセット
	$stmt->bindValue( ':id', $_GET['message_id'], PDO::PARAM_INT);

	// SQLクエリの実行
	$stmt->execute();

	// 表示するデータを取得
	$message_data = $stmt->fetch();

    $_SESSION['image_name'] = $message_data['image_name'];

	// 投稿データが取得できないときは管理ページに戻る
	if( empty($message_data) ) {
		header("Location: ./index.php");
		exit;
	}

} elseif(!empty($_POST['message_id'])) {

    $image_name = $_SESSION['image_name'];

    if(isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])){

        if(file_exists(buildImagePath($_SESSION['image_name']))) {
            unlink(buildImagePath($_SESSION['image_name']));
        }

        $image_name = uploadImage($_FILES['image']);
    }

    // 空白除去
	$name = preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['name']);
	$message = preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['message']);

    // 表示名の入力チェック
	if(empty($name)) {
		$error_message[] = '表示名を入力してください。';
	}

	// メッセージの入力チェック
	if(empty($message)) {
		$error_message[] = 'メッセージを入力してください。';
	} else {
		if( 100 < mb_strlen($message, 'UTF-8') ) {
			$error_message[] = 'ひと言メッセージは100文字以内で入力してください。';
		}
	}

    if(empty($error_message)){
     
        // トランザクション開始
        $pdo->beginTransaction();

        try {
            // SQL作成
            $stmt = $pdo->prepare("UPDATE post SET name = :name, message = :message, image_name = :image_name WHERE id = :id");

            // 値をセット
            $stmt->bindParam( ':name', $name, PDO::PARAM_STR);
            $stmt->bindParam( ':message', $message, PDO::PARAM_STR);
            $stmt->bindParam( ':image_name', $image_name, PDO::PARAM_STR);
            $stmt->bindValue( ':id', $_POST['message_id'], PDO::PARAM_INT);

            // SQLクエリの実行
            $stmt->execute();

            // コミット
            $res = $pdo->commit();

        } catch(Exception $e) {

            // エラーが発生した時はロールバック
            $pdo->rollBack();
        }

        // 削除に成功したら一覧に戻る
        if($res) {
            header("Location: ./index.php");
            exit;
        }
    }
}

$stmt = null;
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
    <form method="post" class="edit" enctype="multipart/form-data">
        <?php if(isset($error_message)): ?>
            <ul class="error-message">
                <?php foreach($error_message as $value): ?>
                    <li>・<?php echo $value; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <label for="name" class="edit-label">表示名</label>
        <input type="text" class="edit-input" id="name" name="name" value="<?php if(!empty($message_data['name'])){echo $message_data['name'];}elseif( !empty($name) ){ echo htmlspecialchars( $name, ENT_QUOTES, 'UTF-8'); } ?>">
        <label for="message" class="edit-label">メッセージ</label>
        <textarea name="message" id="message" class="edit-textarea"><?php if(!empty($message_data['message'])){echo $message_data['message'];}elseif( !empty($message) ){ echo htmlspecialchars( $message, ENT_QUOTES, 'UTF-8'); } ?></textarea>
        <?php if(isset($message_data['image_name'])): ?>
            <p>画像ファイル</p>
            <img src="<?php echo buildImagePath($message_data['image_name']); ?>" alt="">
        <?php elseif(!empty($image_name)): ?>
            <p>画像ファイル</p>
            <img src="<?php echo buildImagePath($image_name); ?>" alt="">
        <?php endif; ?>
        <input type="file" name="image">
        <a class="edit-cansel" href="index.php">キャンセル</a>
        <input type="submit" name="edit_submit" class="edit-submit" value="更新">
	    <input type="hidden" name="message_id" value="<?php if(!empty($message_data['id'])){ echo $message_data['id']; } elseif( !empty($_POST['message_id']) ){ echo htmlspecialchars( $_POST['message_id'], ENT_QUOTES, 'UTF-8'); } ?>">
    </form>
</body>