<base href="http://localhost/inventory-system/">
<?php
session_start();
session_destroy();
header("Location: login.php");
exit;
