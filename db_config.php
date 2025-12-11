<?php
// db_config.php

// AWS EC2 (DB Server) のプライベートIPアドレス
$host = '172.31.29.159'; 
$dbname = 'IoT_Edge';
$username = 'dbuser' // MySQLのユーザー名
$password = 's9Luser#'SQLのパスワード

try {
    // MySQL接続設定
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    // エラー時に例外を投げる設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // デフォルトのフェッチモードを連想配列に設定
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // 接続失敗時はエラーを表示して終了
    header('Content-Type: text/plain; charset=UTF-8');
    exit("データベース接続失敗: " . $e->getMessage());
}
?>