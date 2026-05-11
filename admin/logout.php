<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

Auth::logout();
cms_redirect('/admin/login.php');

