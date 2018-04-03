(function(d, n, w, p, r) {
	w.cabrioWC = function(n, v, d) {
		var e, t = new Date();

		if (d) {
			t.setTime(t.getTime() + d * 86400000);
			e = '; expires=' + t.toGMTString();
		} else {
			e = '';
		}

		document.cookie = n + '=' + v + e + '; path=/';
	};

	w.cabrioRC = function(n) {
		n = n + '=';
		var a = document.cookie.split(';');

		for (var i = 0; i < a.length; i++) {
			var c = a[i];
			while (c.charAt(0) === ' ') {
				c = c.substring(1, c.length);
			}

			if (c.indexOf(n) === 0) {
				return c.substring(n.length, c.length);
			}
		}

		return null;
	};

	w.cabrioSI = function(postId, alternativeImage) {
		w[p]['i']['variants'][postId] = (alternativeImage && w[p]['i']['selected'] === 'B') ? 'B' : 'A';

		if (w[p]['i']['selected'] === 'A') {
			return;
		}

		var d = 'data-' + p + 'i',
			e = document.querySelector('[' + d + '="' + postId + '"]');

		if (e && alternativeImage) {
			e.outerHTML = alternativeImage;
		}
	};

	w.cabrioST = function(postId, alternativeTitle) {
		w[p]['t']['variants'][postId] = (alternativeTitle && w[p]['t']['selected'] === 'B') ? 'B' : 'A';

		if (w[p]['t']['selected'] === 'A') {
			return;
		}

		var d = 'data-' + p + 't',
			e = document.querySelector('[' + d + '="' + postId + '"]');

		if (e && alternativeTitle) {
			e.innerHTML = alternativeTitle;
			e.removeAttribute(d);
		}
	};

	w.cabrioSL = function(postId, articleSelector, gateSelector, delta, min) {
		w[p]['l']['variants'][postId] = (w[p]['l']['selected'] === 'B') ? 'B' : 'A';
		if (w[p]['l']['selected'] === 'A') {
			return;
		}

		// initialize DOM elements
		var article = document.querySelector(articleSelector);
		if (!article) {
			return;
		}
		var paywallGate = document.querySelector(gateSelector);
		if (!paywallGate) {
			return;
		}

		if (delta < 1) {
			return;
		}

		// find paywall gate's paragraph
		var paywallNodeIndex = null;
		var paywallGatePosition = null;

		for (var i=0; i<article.children.length; i++) {
			var idx = article.children[i].outerHTML.indexOf(paywallGate.outerHTML);

			if (idx !== -1) {
				paywallNodeIndex = i;
				paywallGatePosition = idx;
				break;
			}
		}

		if (paywallNodeIndex === null || paywallGatePosition === null) {
			return;
		}

		// remove the content within the paywall's paragraph
		article.children[paywallNodeIndex].innerHTML = article.children[paywallNodeIndex].innerHTML.substring(paywallGatePosition);

		// remove the paragraphs preceding paragraph with paywall gate
		for (i = 1; i <= delta; i++ ) {
			article.children[paywallNodeIndex-i].remove();
			if (paywallNodeIndex-i === min) {
				// keep at least one paragraph of content
				break;
			}
		}
	};

	if (window.Element && !Element.prototype.remove) {
		Element.prototype.remove =
			function() {
				if (this.parentNode) {
					this.parentNode.removeChild(this);
				}
			};
	}

	var a = ['i', 't', 'l'];

	if (!w[p]) {
		w[p] = {};
	}

	for (var i = 0; i < a.length; i++) {
		var b = a[i];
		var v = cabrioRC(p + b);

		if (!v) {
			v = Math.random() > r ? 'A' : 'B';
			cabrioWC(p + b, v, 365);
		}

		if (!w[p][b]) {
			w[p][b] = {
				'default': 'A',
				'selected': v,
				'variants': {}
			};
		}
	}
}(document, 't', window, 'cabrio', 0.5)); //dnwp
