(function () {

	// стили для меню
	var style = '<style>\
	.context-menu-list {\
		margin:0; \
		padding:0;\
		z-index: 1;\
		\
		min-width: 120px;\
		max-width: 250px;\
		display: inline-block;\
		position: absolute;\
		list-style-type: none;\
		\
		border: 1px solid #DDD;\
		background: #EEE;\
		\
		-webkit-box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);\
		   -moz-box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);\
				box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);\
		\
		font-family: inherit;\
		font-size: 12px;\
	}\
	\
	.context-menu-item {\
		padding: 2px 2px 2px 24px;\
		background-color: #EEE;\
		position: relative;\
		-webkit-user-select: none;\
		   -moz-user-select: none;\
			-ms-user-select: none;\
				user-select: none;\
		cursor: pointer;\
	}\
	\
	.context-menu-item:hover {\
		cursor: pointer;\
		background-color: Highlight;\
		color: HighlightText;\
	}\
	\
	.context-menu-item.icon { height: 18px; background-repeat: no-repeat; background-position: 4px 2px; }\
	</style>';

	document.body.insertAdjacentHTML('afterBegin', style);

	// показываем по нажатию правой кнопкой
	document.addEventListener('contextmenu', function (e) {
		if (!e.target.closest('[contextmenu]')) { return; };

		e.preventDefault();

		var elem = e.target.closest('[contextmenu]');
		var menuId = elem.getAttribute('contextmenu');
		var menu = document.querySelector('[data-context-id='+ menuId +']');

		if (!menu) {
			menu = generateMenu(menuId);
		};

		menu.style.top = e.clientY +'px';
		menu.style.left = e.clientX +'px';

		menu.style.display = '';

		emit(elem, 'contextmenu-is-open', {
			target: elem
		});
	});

	// прячем по любому нажатию
	document.addEventListener('mouseup', function (e) {
		[].forEach.call(document.querySelectorAll('.context-menu-list'), function (menu) {
			menu.style.display = 'none';
			emit(document, 'contextmenu-is-close');
		});
	});

	// создаёт меню
	function generateMenu (menuId) {
		var menuTrue = document.querySelector('#'+ menuId);

		// если нет сооветствующего меню
		if (!menuTrue) { return; };

		// если есть, создаём своё
		var items = menuTrue.querySelectorAll('menuitem');
		var menu = document.createElement('ul');

		menu.classList.add('context-menu-list', 'context-menu-root');
		menu.setAttribute('data-context-id', menuId);

		// наполняем его
		[].forEach.call(items, function (item) {
			var menuItem = document.createElement('li');

			menuItem.classList.add('context-menu-item');
			menuItem.onclick = item.onclick;
			menuItem.innerHTML = item.getAttribute('label');

			// добавляем пиктограмму
			if (item.getAttribute('icon')) {
				menuItem.classList.add('icon');
				menuItem.style.backgroundImage = 'url('+ item.getAttribute('icon') +')';
			};

			menu.appendChild(menuItem);
		});

		// скрываем скриптом,
		// чтобы было удобно показывать потом
		menu.style.display = 'none';
		document.body.appendChild(menu);

		return menu;
	};

	// генерация событий
	function emit (elem, name, data) {
		if (window.CustomEvent) {
			var event = new CustomEvent(name, {
				detail: data,
				bubbles: true
			});
		}
		else {
			var event = document.createEvent('CustomEvent');

			event.initCustomEvent(name, true, true, data);
		};

		elem.dispatchEvent(event);
	};
})();
