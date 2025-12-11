<?php
// index.php
require 'db_config.php';

// --- A. 人数修正処理 (POSTリクエスト時のみ実行) ---
// センサの誤差で人数がズレた場合、手動で修正するためのロジック
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_count'])) {
    $new_count = (int)$_POST['reset_count'];
    // 0未満にならないように調整
    if ($new_count < 0) $new_count = 0;
    
    $stmt = $pdo->prepare("UPDATE room_status SET current_count = :cnt WHERE id = 1");
    $stmt->execute([':cnt' => $new_count]);
    
    // 処理後にリダイレクトして再読み込み（二重送信防止）
    header("Location: index.php");
    exit;
}

// --- B. データ取得処理 ---

// 1. 環境情報の取得 (最新の1件)
// env_logs テーブル: temperature(FLOAT), humidity(FLOAT)
$stmt_env = $pdo->query("SELECT temperature, humidity, created_at FROM env_logs ORDER BY id DESC LIMIT 1");
$env = $stmt_env->fetch();

// 2. 現在の人数の取得
// room_status テーブル: current_count(INT)
$stmt_ppl = $pdo->query("SELECT current_count FROM room_status WHERE id = 1");
$status = $stmt_ppl->fetch();
$current_people = isset($status['current_count']) ? (int)$status['current_count'] : 0;

// 3. 本の人気ランキング (TOP 5)
// books テーブル: title, pickup_count(INT)
$stmt_books = $pdo->query("SELECT title, pickup_count FROM books ORDER BY pickup_count DESC LIMIT 5");
$ranking = $stmt_books->fetchAll();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室スマート管理システム</title>
    <style>
        /* シンプルで見やすいCSSデザイン */
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #f0f2f5; color: #333; margin: 0; padding: 20px; }
        h1 { text-align: center; color: #2c3e50; margin-bottom: 30px; }
        
        /* カードレイアウト */
        .container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 20px; width: 300px; text-align: center; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        
        .card h2 { font-size: 1.2em; color: #7f8c8d; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; margin-top: 0; }
        .value { font-size: 3em; font-weight: bold; color: #2c3e50; margin: 10px 0; }
        .unit { font-size: 0.4em; color: #95a5a6; }
        
        /* ステータス別の色変化 */
        .status-good { color: #27ae60; font-weight: bold; }
        .status-warn { color: #e67e22; font-weight: bold; }
        .status-alert { color: #c0392b; font-weight: bold; }

        /* ランキングリスト */
        ul.ranking { list-style: none; padding: 0; text-align: left; }
        ul.ranking li { padding: 8px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        ul.ranking li:last-child { border-bottom: none; }
        .rank-num { font-weight: bold; color: #3498db; margin-right: 10px; }

        /* メンテナンス用エリア */
        .admin-area { margin-top: 50px; text-align: center; padding: 20px; border-top: 1px dashed #ccc; font-size: 0.9em; color: #777; }
        .admin-area input[type="number"] { width: 50px; padding: 5px; }
        .admin-area button { padding: 5px 10px; cursor: pointer; background: #95a5a6; color: white; border: none; border-radius: 4px; }
    </style>
</head>
<body>

    <h1>📚 図書室 リアルタイム情報</h1>

    <div class="container">
        
        <div class="card">
            <h2>🌡️ 環境モニター</h2>
            <?php if ($env): ?>
                <div>
                    <div class="value"><?= round($env['temperature'], 1) ?><span class="unit">℃</span></div>
                    <div class="value"><?= round($env['humidity'], 1) ?><span class="unit">%</span></div>
                </div>
                <?php
                    $temp = $env['temperature'];
                    if ($temp >= 18 && $temp <= 28) {
                        echo '<p class="status-good">快適な温度です ◎</p>';
                    } elseif ($temp < 18) {
                        echo '<p class="status-alert">少し寒いです 🥶</p>';
                    } else {
                        echo '<p class="status-alert">少し暑いです 🥵</p>';
                    }
                ?>
                <p><small>更新: <?= date('H:i', strtotime($env['created_at'])) ?></small></p>
            <?php else: ?>
                <p>データ収集中...</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>👥 現在の利用者数</h2>
            <div class="value"><?= $current_people ?><span class="unit">人</span></div>
            
            <?php if ($current_people <= 5): ?>
                <p class="status-good">空いています ◎</p>
            <?php elseif ($current_people <= 20): ?>
                <p class="status-warn">やや混雑しています △</p>
            <?php else: ?>
                <p class="status-alert">混雑しています ✕</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>📖 人気の本ランキング</h2>
            <?php if (count($ranking) > 0): ?>
                <ul class="ranking">
                    <?php foreach ($ranking as $idx => $book): ?>
                        <li>
                            <span>
                                <span class="rank-num"><?= $idx + 1 ?>.</span>
                                <?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span><?= $book['pickup_count'] ?>回</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>データ収集中...</p>
            <?php endif; ?>
        </div>

    </div>

    <div class="admin-area">
        <p>管理者用メニュー: 人数カウントの手動修正</p>
        <form method="POST" action="index.php">
            現在の人数を 
            <input type="number" name="reset_count" value="<?= $current_people ?>" min="0"> 
            人に
            <button type="submit" onclick="return confirm('人数を強制変更しますか？')">修正する</button>
        </form>
        <p><small>※センサの入退室判定ミスで人数がズレた場合に使用してください。</small></p>
    </div>

    <script>
        setTimeout(function(){
            window.location.reload();
        }, 30000);
    </script>

</body>
</html>