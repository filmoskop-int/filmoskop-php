<?php

// �������� ������ �� ������
if (isset($_GET['q'])) {
	$q = $_GET['q'];
};

// ���������� ������ �������
if (!isset($q) || !$q || count(explode('/', $q)) < 2) {
	include 'lib/list.php';
}

// ���������� �����
elseif (count(explode('/', $q)) === 2) {
	include 'lib/edit.php';
};

?>
