<?php
///////////////////////////
// 便利な関数
//////////////////////////

/**
* 画像のファイル名から画像のURLを生成
*
* @param array $file
* @return string 画像のファイル名
*/
function buildImagePath(string $name = null)
{
    return 'img/' . htmlspecialchars($name);
}


/**
* 画像をアップロード
*
* @param array $file
* @return string 画像のファイル名
*/
function uploadImage(array $file)
{
    // 画像ファイル名から拡張子を取得（例：.png）
    $image_extention = strchr($file['name'], '.');

    // 画像のファイル名を作成（YmdHis：2021-01-01 00:00:00 ならば 20210101000000）
    $image_name = date('YmdHis') . $image_extention;

    // 保存先のディレクトリ
    $directory = 'img/';

    // 画像のパスを作成
    $image_path = $directory . $image_name;

    // 画像を設置
    move_uploaded_file($file['tmp_name'], $image_path);

    // 画像ファイルが正しい場合->ファイル名をreturn
    if(exif_imagetype($image_path)) {
        return $image_name;
    }

    // 画像ファイル以外の場合
    echo '選択されたファイルが画像ではないため処理を停止しました。';
    exit;
}