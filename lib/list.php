<?

// подставляет алиасы
function aliaser($text)
{
	$aliases = array(
		'svasilev' => 'Геннадич'
	);

	if (isset($aliases[$text])) {
		return $aliases[$text];
	}
	else {
		return $text;
	};
};

// получает список фильмов, упорядоченный по дате изменения
function getFilms($dir) {
	$ignored = array('.', '..', '.svn', '.htaccess');

	$files = array();    
	foreach (scandir($dir) as $file) {
		if (in_array($file, $ignored)) continue;
		$files[$file] = filemtime($dir . '/' . $file);
	};

	arsort($files);
	$files = array_keys($files);

	return ($files) ? $files : false;
};

$tmplFilm = <<<EOL
# Основной заголовок

---

### Дополнительный заголовок
EOL;

$filmsDir = 'films';
$curUrl = 'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

if (count($_POST)) {
	if ($_POST['type'] === 'addFolder') {
		$name = htmlspecialchars($_POST['name']);
		$name = iconv('UTF-8', 'windows-1251', $name);

		if (!file_exists($filmsDir .'/'. $name)) {
			mkdir($filmsDir .'/'. $name, 0777, true);
		};
	}
	elseif ($_POST['type'] === 'addFilm') {
		$name = htmlspecialchars($_POST['name']);
		$author = htmlspecialchars($_POST['author']);
		$name = iconv('UTF-8', 'windows-1251', $name);
		$author = iconv('UTF-8', 'windows-1251', $author);
		
		if (!file_exists($filmsDir .'/'. $author .'/'. $name)) {
			mkdir($filmsDir .'/'. $author .'/'. $name, 0777, true);
			file_put_contents($filmsDir .'/'. $author .'/'. $name .'/film.md', $tmplFilm);
		};
	};

	exit();
};

$authors = scandir($filmsDir);

?>
<!doctype html>
<head>
	<meta charset="windows-1251">
	<title>Фильмоскоп</title>
	<style>
		body {
			font-family: "Segoe UI", sans-serif;
		}
		.User + .User {
			margin-top: 3px;
		}
		.User__name {
			cursor: default;
			margin: 1em 0 .3em;
		}
		.User:first-child .User__name {
			margin-top: 0;
		}
		.User__name a {
			text-decoration: none;
		}
		.User__name a:hover {
			text-decoration: underline;
		}
		.Add--folder {
			display: inline-block;
			font-weight: bold;
			margin-top: 2em;
			text-decoration: none;
			border-bottom: 1px dotted;
		}
	</style>
</head>
<?

foreach ($authors as $key => $author) {
	if ($author === '.' || $author === '..') {
		continue;
	};

	$films = getFilms($filmsDir .'/'. $author);

	echo '<div class="User" id="'. $author .'">'
		. '<h2 class="User__name">'
			. '<a href="#'. $author .'">#</a>'
			. ' '
			. aliaser($author)
			. ' '
			. '<a class="User__add" href="#'. $author .'">+</a>'
		. '</h2>';


	if ($films) {
		foreach ($films as $key => $film) {
			if ($film === '.' || $film === '..') {
				continue;
			};

			echo '<div class="User__film">'
					. '<a href="'. $curUrl . $author .'/'. $film .'">'
						. $film
					. '</a>'
				. '</div>';
		};
	};

	echo '</div>';
};
?>

<a class="Add Add--folder" href="#">Добавить папку</a>

<script>
document.querySelector('.User__add').addEventListener('click', function (e) {
	e.preventDefault();

	var name = prompt('Введите название нового фильма', '');

	if (!name) { return; };

	var data = new FormData();

	data.append('type', 'addFilm');
	data.append('name', name);
	data.append('author', this.closest('.User').id);

	fetch('', {
		method: 'POST',
		body: data
	}).then(function (response) {
		if (response.ok) {
			response.text().then(function (data) {
				window.location.reload(true);
			});
		};
	});
});

document.querySelector('.Add--folder').addEventListener('click', function (e) {
	e.preventDefault();

	var name = prompt('Введите название новой папки', '');

	if (!name) { return; };

	var data = new FormData();

	data.append('type', 'addFolder');
	data.append('name', name);

	fetch('', {
		method: 'POST',
		body: data
	}).then(function (response) {
		if (response.ok) {
			response.text().then(function (data) {
				window.location.reload(true);
			});
		};
	});
});
</script>
