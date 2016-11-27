<?php

// получаем запрос из адреса
if (isset($_GET['q'])) {
	$q = $_GET['q'];
};

// отображаем список фильмов
if (!isset($q) || !$q || count(explode('/', $q)) < 2) {
	include 'lib/list.php';
}

// отображаем фильм
elseif (count(explode('/', $q)) === 2) {
	include 'lib/edit.php';
};

?>
