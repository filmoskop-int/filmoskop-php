<?php

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

// подставляет фильм в шаблон
function applyTmpl($film, $tmpl, $filmName, $filmPath, $curUrl, $ext)
{
	if ($ext === 'md') {
		$film = explode('---\n', $film);
		$film = '<section data-markdown data-separator-vertical="\n-----\n" data-separator-notes="^Комментарий:"><script type="text/template">'. implode('</script></section><section data-markdown data-separator-vertical="\n-----\n" data-separator-notes="^Комментарий:"><script type="text/template">', $film) .'</script></section>';
	};

	$film = preg_replace('/%FILM%/', $film, $tmpl);
	$film = preg_replace('/%LIST_LINK%/', '../../../', $film);
	$film = preg_replace('/%TITLE%/', $filmName, $film);
	$film = preg_replace('/%STATIC_PATH%/', '../../../reveal', $film);
	$film = preg_replace('/%BASE%/', '../'. $filmPath .'/film.'. $ext, $film);
	$film = preg_replace('/href="#/', 'href="'. $curUrl .'#', $film);
	$film = preg_replace('/\]\(#/', ']('. $curUrl .'#', $film);
	$film = preg_replace('/href="\?/', 'href="'. $curUrl .'?', $film);

	return $film;
};

// показывает фильм
function showFilm($filmName, $filmPath, $curUrl, $ext, $tmplPath)
{
	$tmpl = file_get_contents($tmplPath);
	$film = file_get_contents($filmPath .'/film.'. $ext);

	$film = applyTmpl($film, $tmpl, $filmName, $filmPath, $curUrl, $ext);

	echo $film;
};

// получает список фильмов, упорядоченный по дате изменения

// СДЕЛАТЬ
// упорядочивать фильмы по дате модификации
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

// получаем запрос из адреса
if (isset($_GET['q'])) {
	$q = $_GET['q'];
};

$filmsDir = 'films';
$tmplPath = 'reveal/tmpl.html';
$authors = scandir($filmsDir);
$curUrl = 'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// отображаем список фильмов
if (!isset($q) || !$q || count(explode('/', $q)) !== 2) {

	echo '<!doctype html>'
		. '<head>'
		. '<meta charset="windows-1251">'
		. '<title>Фильмоскоп</title>'
		. '<style>'
		. 'body {'
		. 'font-family: "Segoe UI", sans-serif;'
		. '}'
		. '.User__name {'
		. 'margin: 1em 0 .3em;'
		. '}'
		. '.User:first-child .User__name {'
		. 'margin-top: 0;'
		. '}'
		. '</style>'
		. '</head>';
	
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
			. '</h2>';


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

		echo '</div>';
	};

}

// отображаем фильм
else {

	$path = explode('/', $q);
	$author = $path[0];
	$filmName = $path[1];
	$filmPath = $filmsDir .'/'. $author .'/'. $filmName;

	if (file_exists($filmPath)) {

		$filmFiles = scandir($filmPath);

		$ext = false;

		foreach ($filmFiles as $key => $value) {
			if (preg_match('/\.html$/', $value)) {
				$ext = 'html';
			}
			else if (preg_match('/\.md$/', $value)) {
				$ext = 'md';
			};
		};

		if ($ext) {
			showFilm($filmName, $filmPath, $curUrl, $ext, $tmplPath);
		};
	};

};

?>
