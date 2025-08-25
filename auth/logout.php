<?php
session_start();
session_unset();
session_destroy();
header("Location: /Counseling-system/index.php");
exit;
