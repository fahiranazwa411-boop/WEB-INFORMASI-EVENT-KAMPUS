<?php
session_start();
session_unset();
session_destroy();

header("Location:../../view/auth/login/Login.html?sukses=logout_success");
exit;
