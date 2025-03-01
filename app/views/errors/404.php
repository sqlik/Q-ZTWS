<!DOCTYPE html>
<html lang="<?= $lang ?? 'pl' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?= APP_NAME ?? 'Q-ZTWS' ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?? '' ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?? '' ?>/assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background-color: white;
        }
        .error-code {
            font-size: 96px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 24px;
            margin-bottom: 30px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <div class="error-message"><?= __('page_not_found') ?? 'Strona nie została znaleziona' ?></div>
        <p><?= __('page_not_found_message') ?? 'Przepraszamy, ale strona, której szukasz, nie istnieje lub została przeniesiona.' ?></p>
        <a href="<?= APP_URL ?? '/' ?>" class="btn btn-primary mt-4">
            <i class="fas fa-home"></i> <?= __('back_to_home') ?? 'Powrót do strony głównej' ?>
        </a>
    </div>
</body>
</html>