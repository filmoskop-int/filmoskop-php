<?
if ($q !== '') {

	// подставляет фильм в шаблон
	function applyTmpl($film, $tmpl, $filmName, $filmPath, $curUrl, $ext)
	{
		if ($ext === 'md') {
			$film = explode('---\n', $film);
			$film = '<section data-markdown data-separator-vertical="\n-----\n" data-separator-notes="^Комментарий:"><script type="text/template">'. implode('</script></section><section data-markdown data-separator-vertical="\n-----\n" data-separator-notes="^Комментарий:"><script type="text/template">', $film) .'</script></section>';
		};

		$film = preg_replace('/%FILM%/', $film, $tmpl);
		$film = preg_replace('/%LIST_LINK%/', '<a class="link link--toList" href="../../../">Вернуться в список докладов</a>', $film);
		$film = preg_replace('/%TITLE%/', $filmName, $film);
		$film = preg_replace('/%STATIC_PATH%/', '../../../reveal', $film);
		$film = preg_replace('/%BASE%/', '<base href="../'. $filmPath .'/film.'. $ext. '">', $film);
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
		$film = htmlspecialchars_decode($film);

		$film = applyTmpl($film, $tmpl, $filmName, $filmPath, $curUrl, $ext);

		echo $film;
	};

	$filmsDir = 'films';
	$curUrl = 'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	$tmplPath = 'reveal/tmpl.html';
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