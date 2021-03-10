<?php

$result = ['errors' => [], 'success' => []];

if (file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/dbconn.php')) {
    $file_contents = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/dbconn.php');
    preg_match('/\$DBHost\s*=\s*[\'"]([^\'"]+)[\'"]/', $file_contents, $DBHost);
    preg_match('/\$DBLogin\s*=\s*[\'"]([^\'"]+)[\'"]/', $file_contents, $DBLogin);
    preg_match('/\$DBPassword\s*=\s*[\'"]([^\'"]*)[\'"]/', $file_contents, $DBPassword);
    preg_match('/\$DBName\s*=\s*[\'"]([^\'"]+)[\'"]/', $file_contents, $DBName);
    $host = $DBHost[1];
    $login = $DBLogin[1];
    $passwd = $DBPassword[1];
    $base = $DBName[1];
} else {
    $login = $passwd = $base = $host = '';
}

if ($_POST['db_login'] && $_POST['db_passwd'] && $_POST['db_base']) {
    $login = $_POST['db_login'];
    $passwd = $_POST['db_passwd'];
    $base = $_POST['db_base'];
    $host = $_POST['db_host'] ?: '127.0.0.1';

    try {
        $dbh = new PDO("mysql:dbname={$base};host={$host}", $login, $passwd);
    } catch (PDOException $e) {
        $result['errors'][] = 'Подключение не удалось: ' . $e->getMessage();
    }

    if (!$result['errors']) {
        $sth = $dbh->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = :dbname;");
        $sth->execute(array(':dbname' => $base));
        $tables = $sth->fetchAll();

        foreach ($tables as $table) {
            $sth = $dbh->prepare("DROP TABLE IF EXISTS `{$table['table_name']}`;");
            if ($sth->execute()) {
                $result['success'][] = "Удалена таблица: {$table['table_name']}";
            } else {
                $result['errors'][] = "Не удалось удалить таблицу {$table['table_name']}";
            }
        }
    }
}

header('Content-Type: text/html; charset=utf-8');

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удаление всех таблиц из БД</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
</head>
<body style="background-color:#f5f5f5">
    <div class="container">
        <div class="row pt-5 mb-3 justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Удаление всех таблиц из базы данных</h5>
                        <form action="" method="POST" onsubmit="return prompt('Вы действительно хотите удалить все таблицы? Введите `Да`') === 'Да';">
                            <div class="mb-3 row">
                                <label for="db_host" class="col-sm-2 col-form-label">Host</label>
                                <div class="col-sm-10">
                                    <input name="db_host" type="text" class="form-control" id="db_host" value="<?=$host ?>" placeholder="127.0.0.1">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="db_base" class="col-sm-2 col-form-label">Database</label>
                                <div class="col-sm-10">
                                    <input name="db_base" type="text" class="form-control" id="db_base" value="<?=$base ?>" required="required">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="db_login" class="col-sm-2 col-form-label">Login</label>
                                <div class="col-sm-10">
                                    <input name="db_login" type="text" class="form-control" id="db_login" value="<?=$login ?>" required="required">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="db_passwd" class="col-sm-2 col-form-label">Password</label>
                                <div class="col-sm-10">
                                    <input name="db_passwd" type="password" class="form-control" id="db_passwd" value="<?=$passwd ?>" required="required">
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-danger">
                                    <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512" width="15" class="align-bottom"><path fill="#fff" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg>
                                    &nbsp;Удалить все таблицы
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($result['success']): ?>
        <div class="row mb-3 justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="alert alert-success" role="alert">
                    <ul class="mb-0">
                    <?php foreach($result['success'] as $row): ?>
                        <li><?=$row ?></li>
                    <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif ?>

        <?php if ($result['errors']): ?>
        <div class="row mb-3 justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                    <?php foreach($result['errors'] as $row): ?>
                        <li><?=$row ?></li>
                    <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif ?>
    </div>
</body>
</html>