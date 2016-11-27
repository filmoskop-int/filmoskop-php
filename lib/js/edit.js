var film = (function () {
	var ui = (function () {
		return {
			editor: document.querySelector('.Editor'),
			edit: document.querySelector('.Editor__field'),
			view: document.querySelector('.Editor__view'),
			img: document.querySelector('.Editor__images'),
			content: filmContent,
			toggle: document.querySelector('.Editor__link--edit')
		};
	})();

	var net = {
		path: ui.editor.getAttribute('data-path'),
		get: {
			film: function () {
				return fetch('../../../reveal/tmpl.html');
			}
		},
		post: {

			// отправка разметки доклада
			film: function (film) {
				var data = new FormData();

				data.append('type', 'film');
				data.append('film', film);

				return fetch(net.path, {
					method: 'POST',
					body: data
				});
			},

			// отправка изображений
			image: function (img, ext) {
				var data = new FormData();

				data.append('type', 'img');
				data.append('img', img);
				data.append('ext', ext);

				return fetch(net.path, {
					method: 'POST',
					body: data
				});
			}
		}
	};

	var film = {
		info: ui.editor.getAttribute('data-film'),
		tmpl: '',
		content: '',
		caret: 0,
		paste: {
			img: {
				bg: '\n<!-- .slide: data-background-transition="slide" data-background-image="%FILENAME%" data-background-size="contain" -->\n',
				inline: '![](%FILENAME%)'
			}
		},

		// начало работы
		start: function () {
			net.get.film().then(film.onTmpl);
			[].forEach.call(Paste.init(ui.edit), pasteInit);

			ui.toggle.addEventListener('click', function (e) {
				e.preventDefault();

				if (ui.editor.classList.contains('Editor--disabled')) {
					ui.editor.classList.remove('Editor--disabled');
				}
				else {
					ui.editor.classList.add('Editor--disabled');
				};
			});
		},

		// действия по получению шаблона
		onTmpl: function (response) {
			response.text().then(function (tmpl) {
				film.tmpl = tmpl;

				film.content = htmlspecialchars_decode(ui.content.innerHTML);
				ui.edit.value = film.content;

				film.update(true);

				ui.edit.addEventListener('input', debounce(film.update, 1000));
				ui.edit.addEventListener('keydown', debounce(film.sync, 400));
				ui.edit.addEventListener('click', film.sync);
			});
		},

		// обновление отображения
		syncTimeout: null,
		update: function (onlyShow) {
			var view = ui.edit.value;

			if (
				typeof onlyShow !== 'boolean'
			) {
				onlyShow = false;
			};

			if (
				!onlyShow
				&& film.content === view
			) { return; };

			film.content = view;

			view = view.split('---\n');
			view = '<section data-markdown data-separator-vertical="\n-----\n" data-separator-notes="^Комментарий:"><script type="text/template">'+ view.join('</sc'+'ript></section><section data-markdown data-separator-vertical="\n-----\n" data-separator-notes="^Комментарий:"><scr'+ 'ipt type="text/template">') +'</scr'+'ipt></section>';

			view = film.tmpl.replace(/%FILM%/, view);
			view = view.replace(/%LIST_LINK%/, '');
			view = view.replace(/%STATIC_PATH%/g, '../../../reveal');
			view = view.replace(/%BASE%/, '');
			ui.view.srcdoc = view;

			if (onlyShow) {
				setCaretPosition(ui.edit, 0);
			};

			clearTimeout(film.syncTimeout);
			film.syncTimeout = setTimeout(function () {

				// синхронизируем слайды
				film.sync();

				if (onlyShow) { return; };

				// отправляем новую разметку слайдов
				net.post.film(film.content).then(function (response) {
					if (response.ok) {
						response.text().then(function (text) {
							console.log(text);
						});
					};
				});
			}, 500);

			fieldAutosize.process();
		},

		// синхронизация слайдов
		sync: function () {
			var view = ui.edit.value;
			var slideCounter = 0;
			var caretPos = getCaretPosition(ui.edit);

			film.caret = caretPos;

			for (var i = 0; i < view.length; i++) {
				if (i > caretPos) {
					break;
				}
				else if (view[i] === '-') {
					if (
						view[i + 1] === '-'
						&& view[i + 2] === '-'
					) {
						slideCounter++;
						i = i + 2;
					};
				};
			};

			if (ui.view.contentWindow.Reveal) {
				ui.view.contentWindow.Reveal.navigateTo(slideCounter)
			};
		}
	};

	// добавление обработчика вставки изображения
	function pasteInit (edit) {
		var context = '\
			<menu type="context" id="context-img">\
				<menuitem id="context-img-bg" class="context-img-bg" label="Сделать фоном"></menuitem>\
				<menuitem id="context-img-text" class="context-img-text" label="Добавить в текст"></menuitem>\
			</menu>\
		';

		ui.img.insertAdjacentHTML('afterBegin', context);

		// обработчик события вставки изображения
		edit.addEventListener('pasteImage', function (e) {
			var image = document.createElement('img');
			var file = e.detail.blob;
			var src = URL.createObjectURL(file);

			image.className = 'Editor__img';
			image.setAttribute('contextmenu', "context-img")

			// изображение передаётся в виде blob
			image.src = src;
			ui.img.appendChild(image);

			// отправка изображения
			net.post.image(
				file,
				'.png'
			).then(function (response) {
				if (response.ok) {
					response.json().then(function (data) {
						image.src = data.name;
					});
				};
				URL.revokeObjectURL(src);
			});
		});

		// вставка изображений
		var contextTarget;

		document.addEventListener('contextmenu-is-open', function (e) {
			contextTarget = e.detail.target;
		});

		document.addEventListener('click', function (e) {
			if (e.target.closest('.context-menu-item')) {
				var elem = e.target.closest('.context-menu-item');

				if (elem.textContent.match(/фоном/)) {
					edit.value = pasteAtCaret(edit.value, film.paste.img.bg.replace('%FILENAME%', contextTarget.getAttribute('src') || 'kek'), film.caret);
				}
				else {
					edit.value = pasteAtCaret(edit.value, film.paste.img.inline.replace('%FILENAME%', contextTarget.getAttribute('src') || 'kek'), film.caret);
				};

				film.update();
			}
			else if (e.target.closest('.Editor__img')) {
				edit.value = pasteAtCaret(edit.value, film.paste.img.inline.replace('%FILENAME%', e.target.getAttribute('src') || 'kek'), film.caret);
				
				film.update();
			};
		});
	};

	// получения положения каретки
	function getCaretPosition (field) {

		// Initialize
		var result = 0;

		// IE Support
		if (document.selection) {

			// Set focus on the element
			field.focus();

			// To get cursor position, get empty selection range
			var sel = document.selection.createRange();

			// Move selection start to 0 position
			sel.moveStart('character', -field.value.length);

			// The caret position is selection length
			result = sel.text.length;
		}

		// Firefox support
		else if (field.selectionStart || field.selectionStart == '0')
			result = field.selectionStart;

		// Return results
		return result;
	};

	// выставляем положение каретки
	function setCaretPosition (field, pos) {
		if (field.createTextRange) {
			var range = field.createTextRange();

			range.move('character', pos);
			range.select();
		}
		else {
			if (field.selectionStart) {
				field.focus();
				field.setSelectionRange(pos, pos);
			}
			else
				field.focus();
		};
	};

	// нормализация вызовов функции
	function debounce (func, wait, immediate) {
		var timeout;

		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;

			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	};

	// вставка в нужном месте
	function pasteAtCaret (a, b, position) {
		var result = [
			a.slice(0, position),
			b,
			a.slice(position)
		].join('');

		return result;
	};

	// кодирование строки
	function utf8_to_b64(str) {
		return window.btoa(unescape(encodeURIComponent(str)));
	};

	// http://locutus.io/php/strings/htmlspecialchars_decode/
	function htmlspecialchars_decode (string, quoteStyle) {

		// eslint-disable-line camelcase
		//       discuss at: http://locutus.io/php/htmlspecialchars_decode/
		//      original by: Mirek Slugen
		//      improved by: Kevin van Zonneveld (http://kvz.io)
		//      bugfixed by: Mateusz "loonquawl" Zalega
		//      bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
		//      bugfixed by: Brett Zamir (http://brett-zamir.me)
		//      bugfixed by: Brett Zamir (http://brett-zamir.me)
		//         input by: ReverseSyntax
		//         input by: Slawomir Kaniecki
		//         input by: Scott Cariss
		//         input by: Francois
		//         input by: Ratheous
		//         input by: Mailfaker (http://www.weedem.fr/)
		//       revised by: Kevin van Zonneveld (http://kvz.io)
		// reimplemented by: Brett Zamir (http://brett-zamir.me)
		//        example 1: htmlspecialchars_decode("<p>this -&gt; &quot;</p>", 'ENT_NOQUOTES')
		//        returns 1: '<p>this -> &quot;</p>'
		//        example 2: htmlspecialchars_decode("&amp;quot;")
		//        returns 2: '&quot;'

		var optTemp = 0;
		var i = 0;
		var noquotes = false;
		var OPTS = {
			'ENT_NOQUOTES': 0,
			'ENT_HTML_QUOTE_SINGLE': 1,
			'ENT_HTML_QUOTE_DOUBLE': 2,
			'ENT_COMPAT': 2,
			'ENT_QUOTES': 3,
			'ENT_IGNORE': 4
		};

		if (typeof quoteStyle === 'undefined') {
			quoteStyle = 2;
		};

		string = string.toString()
			.replace(/&lt;/g, '<')
			.replace(/&gt;/g, '>');

		if (quoteStyle === 0) {
			noquotes = true;
		};
		if (typeof quoteStyle !== 'number') {

			// Allow for a single string or an array of string flags
			quoteStyle = [].concat(quoteStyle);
			for (i = 0; i < quoteStyle.length; i++) {

				// Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
				if (OPTS[quoteStyle[i]] === 0) {
					noquotes = true;
				}
				else if (OPTS[quoteStyle[i]]) {
					optTemp = optTemp | OPTS[quoteStyle[i]];
				};
			};
			quoteStyle = optTemp;
		}
		if (quoteStyle & OPTS.ENT_HTML_QUOTE_SINGLE) {

			// PHP doesn't currently escape if more than one 0, but it should:
			string = string.replace(/&#0*39;/g, "'");

			// This would also be useful here, but not a part of PHP:
			// string = string.replace(/&apos;|&#x0*27;/g, "'");
		}
		if (!noquotes) {
			string = string.replace(/&quot;/g, '"');
		};

		// Put this in last place to avoid escape being double-decoded
		string = string.replace(/&amp;/g, '&');

		return string;
	};

	return film;
})().start();
