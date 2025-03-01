<?php
/**
 * app/views/partials/header.php
 * Nagłówek strony dla aplikacji Q-ZTWS
 */
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $pageTitle ?? APP_NAME ?> | <?= APP_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Własne style CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Meta tagi -->
    <meta name="description" content="<?= APP_NAME ?> - Aplikacja quizowa w stylu Kahoot">
    <meta name="keywords" content="quiz, edukacja, interaktywne, kahoot, q-ztws">
    <meta name="author" content="ZTWS">
</head>
<body>
    <!-- Pasek nawigacji -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= APP_URL ?>/dashboard">
                <?= APP_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Menu główne dla zalogowanych użytkowników -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $pageTitle === 'dashboard' ? 'active' : '' ?>" href="<?= APP_URL ?>/dashboard">
                            <i class="fas fa-home"></i> <?= __('dashboard') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $pageTitle === 'quizzes' ? 'active' : '' ?>" href="<?= APP_URL ?>/quizzes">
                            <i class="fas fa-question-circle"></i> <?= __('my_quizzes') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/join">
                            <i class="fas fa-sign-in-alt"></i> <?= __('join_quiz') ?>
                        </a>
                    </li>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <!-- Menu administratora -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> <?= __('admin') ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/admin/dashboard">
                                    <i class="fas fa-tachometer-alt"></i> <?= __('admin_dashboard') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/admin/users">
                                    <i class="fas fa-users"></i> <?= __('user_management') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/admin/settings">
                                    <i class="fas fa-sliders-h"></i> <?= __('settings') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/admin/email-templates">
                                    <i class="fas fa-envelope"></i> <?= __('email_templates') ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <!-- Menu użytkownika -->
                <ul class="navbar-nav ms-auto">
                    <!-- Przełącznik języka -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-globe"></i> <?= $lang === 'pl' ? 'Polski' : 'English' ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                            <li>
                                <a class="dropdown-item <?= $lang === 'pl' ? 'active' : '' ?>" href="<?= APP_URL ?>/language/pl">
                                    Polski
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= $lang === 'en' ? 'active' : '' ?>" href="<?= APP_URL ?>/language/en">
                                    English
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Menu profilu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/profile">
                                    <i class="fas fa-id-card"></i> <?= __('profile') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/change-password">
                                    <i class="fas fa-key"></i> <?= __('change_password') ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/logout">
                                    <i class="fas fa-sign-out-alt"></i> <?= __('logout') ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <?php else: ?>
                <!-- Menu dla niezalogowanych -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/login">
                            <i class="fas fa-sign-in-alt"></i> <?= __('login') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/register">
                            <i class="fas fa-user-plus"></i> <?= __('register') ?>
                        </a>
                    </li>
                    <!-- Przełącznik języka dla niezalogowanych -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-globe"></i> <?= $lang === 'pl' ? 'Polski' : 'English' ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                            <li>
                                <a class="dropdown-item <?= $lang === 'pl' ? 'active' : '' ?>" href="<?= APP_URL ?>/language/pl">
                                    Polski
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= $lang === 'en' ? 'active' : '' ?>" href="<?= APP_URL ?>/language/en">
                                    English
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Komunikaty dla użytkownika -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
        <strong><i class="fas fa-check-circle"></i></strong> <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
        <strong><i class="fas fa-exclamation-circle"></i></strong> <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Główna zawartość strony -->
    <main class="py-4">
