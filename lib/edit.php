<?

$filmsDir = 'films';
$tmplPath = 'reveal/tmpl.html';
$path = explode('/', $q);
$author = $path[0];
$filmName = $path[1];
$filmPath = $filmsDir .'/'. $author .'/'. $filmName;
$filmText = $filmPath .'/film.'.'md';
$film = file_get_contents($filmPath .'/film.'.'md');

$filmPathFull = dirname($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME']) .'/'. $filmPath;
$filmPathFull = preg_replace('/\//', DIRECTORY_SEPARATOR, $filmPathFull);

if (count($_FILES)) {
	$fileName = base_convert(time(), 10, 36) . iconv('UTF-8', 'windows-1251', $_POST['ext']);

	if (move_uploaded_file($_FILES['img']['tmp_name'], $filmPathFull .'\\'. $fileName)) {
	}
	else {
		echo 'File upload error';
	};
};

if (count($_POST)) {

	// если пришла новая версия слайдов
	if ($_POST['type'] === 'film') {
		$filmTextNew = htmlspecialchars($_POST['film']);
		$filmTextNew = iconv('UTF-8', 'windows-1251', $filmTextNew);
		file_put_contents($filmText, $filmTextNew);
		echo "Film is updated";
	}
	elseif ($_POST['type'] === 'img') {
		$result = array(
			'ok' => true,
			'name' => $fileName
		);

		echo json_encode($result);
	};

	exit();
};

$base = '../films/'. $author .'/'. $filmName .'/';

if (preg_match('/\/$/', $_SERVER['QUERY_STRING'])) {
	$base = '../'. $base;
};

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="windows-1251">
	<title><?=$filmName?></title>
	<base href="<?=$base?>">
	<link rel="stylesheet" href="../../../lib/css/edit.css">
</head>
<body>

<div id="filmContent" hidden><?=$film?></div>

<div class="Editor Editor--disabled" data-path="<?='http://'. $_SERVER['SERVER_NAME'] .'/'. $_SERVER['REQUEST_URI']?>" data-film="<?=$author .'/'. $filmName?>">

	<div class="Editor__box Editor__box--img Editor__images Editor__panel"><?

$filmData = scandir($filmPath);

foreach ($filmData as $key => $value) {
	if (preg_match('/\.(png|jpg|jpeg|gif)/', $value)) {
		echo '<img class="Editor__img" contextmenu="context-img" src="'. $value .'">';
	};
};

	?></div>
	<div class="Editor__box Editor__box--edit Editor__panel">
		<textarea class="Editor__field"></textarea>
	</div>
	<div class="Editor__box Editor__box--view">
		<div class="Editor__links">
			<a class="Editor__link Editor__link--back" href="../../../">В список</a>
			<a class="Editor__link Editor__link--edit" href="#">Править</a>
		</div>
		<iframe class="Editor__view" src="" frameborder="0"></iframe>
	</div>
</div>

<script src="../../../lib/js/contextmenu.polyfill.js"></script>
<script src="../../../lib/js/fieldAutosize.js"></script>
<script src="../../../lib/js/paste.js"></script>
<script src="../../../lib/js/edit.js"></script>

</body>
</html>
