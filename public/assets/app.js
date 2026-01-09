(self["webpackChunk"] = self["webpackChunk"] || []).push([["app"],{

/***/ "./assets/app.js"
/*!***********************!*\
  !*** ./assets/app.js ***!
  \***********************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _scss_app_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./scss/app.scss */ "./assets/scss/app.scss");
/* harmony import */ var _bootstrap_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./bootstrap.js */ "./assets/bootstrap.js");
/* harmony import */ var _js_global_search_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./js/global-search.js */ "./assets/js/global-search.js");
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)


// start the Stimulus application


// Import Global Search


/***/ },

/***/ "./assets/bootstrap.js"
/*!*****************************!*\
  !*** ./assets/bootstrap.js ***!
  \*****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   app: () => (/* binding */ app)
/* harmony export */ });
/* harmony import */ var _symfony_stimulus_bridge__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @symfony/stimulus-bridge */ "./node_modules/@symfony/stimulus-bridge/dist/index.js");


// Registers Stimulus controllers from controllers.json and in the controllers/ directory
var app = (0,_symfony_stimulus_bridge__WEBPACK_IMPORTED_MODULE_0__.startStimulusApp)(__webpack_require__("./assets/controllers sync recursive ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \\.[jt]sx?$"));
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

/***/ },

/***/ "./assets/controllers sync recursive ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \\.[jt]sx?$"
/*!****************************************************************************************************************!*\
  !*** ./assets/controllers/ sync ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \.[jt]sx?$ ***!
  \****************************************************************************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var map = {
	"./csrf_protection_controller.js": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/csrf_protection_controller.js",
	"./hello_controller.js": "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/hello_controller.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./assets/controllers sync recursive ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js! \\.[jt]sx?$";

/***/ },

/***/ "./assets/js/global-search.js"
/*!************************************!*\
  !*** ./assets/js/global-search.js ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/**
 * Recherche globale avec raccourci clavier Ctrl+K (ou Cmd+K sur Mac)
 */
var GlobalSearch = /*#__PURE__*/function () {
  function GlobalSearch() {
    _classCallCheck(this, GlobalSearch);
    this.modal = null;
    this.searchInput = null;
    this.resultsContainer = null;
    this.debounceTimer = null;
    this.currentQuery = '';
    this.init();
  }
  return _createClass(GlobalSearch, [{
    key: "init",
    value: function init() {
      this.createModal();
      this.bindKeyboardShortcut();
      this.bindEvents();
    }
  }, {
    key: "createModal",
    value: function createModal() {
      var modalHtml = "\n            <div class=\"modal fade\" id=\"globalSearchModal\" tabindex=\"-1\" aria-hidden=\"true\">\n                <div class=\"modal-dialog modal-dialog-centered modal-lg\">\n                    <div class=\"modal-content\">\n                        <div class=\"modal-header border-0 pb-0\">\n                            <div class=\"w-100\">\n                                <div class=\"input-group\">\n                                    <span class=\"input-group-text bg-transparent border-0\">\n                                        <i class=\"bx bx-search-alt\"></i>\n                                    </span>\n                                    <input \n                                        type=\"text\" \n                                        class=\"form-control border-0 shadow-none\" \n                                        id=\"globalSearchInput\"\n                                        placeholder=\"Rechercher un projet, client, contributeur, devis...\"\n                                        autofocus\n                                    >\n                                    <span class=\"input-group-text bg-transparent border-0 text-muted\">\n                                        <small>Ctrl+K</small>\n                                    </span>\n                                </div>\n                            </div>\n                            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\" aria-label=\"Close\"></button>\n                        </div>\n                        <div class=\"modal-body\">\n                            <div id=\"globalSearchResults\">\n                                <div class=\"text-center text-muted py-5\">\n                                    <i class=\"bx bx-search-alt font-size-24 mb-2\"></i>\n                                    <p class=\"mb-0\">Tapez au moins 2 caract\xE8res pour rechercher</p>\n                                </div>\n                            </div>\n                            <div id=\"globalSearchLoading\" class=\"text-center py-5 d-none\">\n                                <div class=\"spinner-border text-primary\" role=\"status\">\n                                    <span class=\"visually-hidden\">Chargement...</span>\n                                </div>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n            </div>\n        ";
      document.body.insertAdjacentHTML('beforeend', modalHtml);
      this.modal = new bootstrap.Modal(document.getElementById('globalSearchModal'));
      this.searchInput = document.getElementById('globalSearchInput');
      this.resultsContainer = document.getElementById('globalSearchResults');
      this.loadingIndicator = document.getElementById('globalSearchLoading');
    }
  }, {
    key: "bindKeyboardShortcut",
    value: function bindKeyboardShortcut() {
      var _this = this;
      document.addEventListener('keydown', function (e) {
        // Ctrl+K (Windows/Linux) ou Cmd+K (Mac)
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
          e.preventDefault();
          _this.openModal();
        }

        // ESC pour fermer
        if (e.key === 'Escape' && document.getElementById('globalSearchModal').classList.contains('show')) {
          _this.closeModal();
        }
      });
    }
  }, {
    key: "bindEvents",
    value: function bindEvents() {
      var _this2 = this;
      // Recherche en temps réel
      this.searchInput.addEventListener('input', function (e) {
        var query = e.target.value.trim();
        if (query.length < 2) {
          _this2.showEmptyState();
          return;
        }

        // Debounce de 300ms
        clearTimeout(_this2.debounceTimer);
        _this2.debounceTimer = setTimeout(function () {
          _this2.performSearch(query);
        }, 300);
      });

      // Navigation au clavier dans les résultats (TODO: implémenter si besoin)
      this.searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
          e.preventDefault();
          // TODO: Navigation avec flèches
        }
      });
    }
  }, {
    key: "openModal",
    value: function openModal() {
      var _this3 = this;
      this.modal.show();
      setTimeout(function () {
        _this3.searchInput.focus();
        _this3.searchInput.select();
      }, 100);
    }
  }, {
    key: "closeModal",
    value: function closeModal() {
      this.modal.hide();
      this.searchInput.value = '';
      this.showEmptyState();
    }
  }, {
    key: "showEmptyState",
    value: function showEmptyState() {
      this.resultsContainer.innerHTML = "\n            <div class=\"text-center text-muted py-5\">\n                <i class=\"bx bx-search-alt font-size-24 mb-2\"></i>\n                <p class=\"mb-0\">Tapez au moins 2 caract\xE8res pour rechercher</p>\n            </div>\n        ";
    }
  }, {
    key: "showLoading",
    value: function showLoading() {
      this.resultsContainer.classList.add('d-none');
      this.loadingIndicator.classList.remove('d-none');
    }
  }, {
    key: "hideLoading",
    value: function hideLoading() {
      this.resultsContainer.classList.remove('d-none');
      this.loadingIndicator.classList.add('d-none');
    }
  }, {
    key: "performSearch",
    value: function () {
      var _performSearch = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(query) {
        var response, results, _t;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.p = _context.n) {
            case 0:
              this.currentQuery = query;
              this.showLoading();
              _context.p = 1;
              _context.n = 2;
              return fetch("/api/search?q=".concat(encodeURIComponent(query)));
            case 2:
              response = _context.v;
              _context.n = 3;
              return response.json();
            case 3:
              results = _context.v;
              if (!(query !== this.currentQuery)) {
                _context.n = 4;
                break;
              }
              return _context.a(2);
            case 4:
              this.displayResults(results);
              _context.n = 6;
              break;
            case 5:
              _context.p = 5;
              _t = _context.v;
              console.error('Erreur de recherche:', _t);
              this.resultsContainer.innerHTML = "\n                <div class=\"alert alert-danger\" role=\"alert\">\n                    <i class=\"bx bx-error-circle me-2\"></i>\n                    Une erreur est survenue lors de la recherche\n                </div>\n            ";
            case 6:
              _context.p = 6;
              this.hideLoading();
              return _context.f(6);
            case 7:
              return _context.a(2);
          }
        }, _callee, this, [[1, 5, 6, 7]]);
      }));
      function performSearch(_x) {
        return _performSearch.apply(this, arguments);
      }
      return performSearch;
    }()
  }, {
    key: "displayResults",
    value: function displayResults(results) {
      var _this4 = this;
      if (Object.keys(results).length === 0) {
        this.resultsContainer.innerHTML = "\n                <div class=\"text-center text-muted py-5\">\n                    <i class=\"bx bx-search-alt-2 font-size-24 mb-2\"></i>\n                    <p class=\"mb-0\">Aucun r\xE9sultat trouv\xE9</p>\n                </div>\n            ";
        return;
      }
      var typeLabels = {
        projects: {
          label: 'Projets',
          icon: 'bx-briefcase-alt-2',
          color: 'primary'
        },
        clients: {
          label: 'Clients',
          icon: 'bx-building-house',
          color: 'success'
        },
        contributors: {
          label: 'Contributeurs',
          icon: 'bx-user',
          color: 'info'
        },
        orders: {
          label: 'Devis',
          icon: 'bx-file',
          color: 'warning'
        }
      };
      var html = '';
      var _loop = function _loop() {
        var _Object$entries$_i = _slicedToArray(_Object$entries[_i], 2),
          type = _Object$entries$_i[0],
          items = _Object$entries$_i[1];
        var config = typeLabels[type] || {
          label: type,
          icon: 'bx-circle',
          color: 'secondary'
        };
        html += "\n                <div class=\"result-category mb-4\">\n                    <h6 class=\"text-muted text-uppercase font-size-12 mb-3\">\n                        <i class=\"bx ".concat(config.icon, " me-2\"></i>\n                        ").concat(config.label, "\n                        <span class=\"badge badge-soft-").concat(config.color, " ms-2\">").concat(items.length, "</span>\n                    </h6>\n                    <div class=\"list-group list-group-flush\">\n            ");
        items.forEach(function (item) {
          html += "\n                    <a href=\"".concat(item.url, "\" class=\"list-group-item list-group-item-action border-0 rounded mb-1\">\n                        <div class=\"d-flex align-items-center\">\n                            <div class=\"flex-shrink-0 me-3\">\n                                <div class=\"avatar-xs\">\n                                    <span class=\"avatar-title rounded-circle bg-light text-").concat(config.color, "\">\n                                        <i class=\"bx ").concat(config.icon, "\"></i>\n                                    </span>\n                                </div>\n                            </div>\n                            <div class=\"flex-grow-1\">\n                                <h6 class=\"mb-0\">").concat(_this4.escapeHtml(item.name), "</h6>\n                            </div>\n                            <div class=\"flex-shrink-0\">\n                                <i class=\"bx bx-right-arrow-alt font-size-18 text-muted\"></i>\n                            </div>\n                        </div>\n                    </a>\n                ");
        });
        html += "\n                    </div>\n                </div>\n            ";
      };
      for (var _i = 0, _Object$entries = Object.entries(results); _i < _Object$entries.length; _i++) {
        _loop();
      }
      this.resultsContainer.innerHTML = html;
    }
  }, {
    key: "escapeHtml",
    value: function escapeHtml(text) {
      var div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  }]);
}(); // Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', function () {
  new GlobalSearch();
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (GlobalSearch);

/***/ },

/***/ "./assets/scss/app.scss"
/*!******************************!*\
  !*** ./assets/scss/app.scss ***!
  \******************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./node_modules/@hotwired/stimulus/dist/stimulus.js"
/*!**********************************************************!*\
  !*** ./node_modules/@hotwired/stimulus/dist/stimulus.js ***!
  \**********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Application: () => (/* binding */ Application),
/* harmony export */   AttributeObserver: () => (/* binding */ AttributeObserver),
/* harmony export */   Context: () => (/* binding */ Context),
/* harmony export */   Controller: () => (/* binding */ Controller),
/* harmony export */   ElementObserver: () => (/* binding */ ElementObserver),
/* harmony export */   IndexedMultimap: () => (/* binding */ IndexedMultimap),
/* harmony export */   Multimap: () => (/* binding */ Multimap),
/* harmony export */   SelectorObserver: () => (/* binding */ SelectorObserver),
/* harmony export */   StringMapObserver: () => (/* binding */ StringMapObserver),
/* harmony export */   TokenListObserver: () => (/* binding */ TokenListObserver),
/* harmony export */   ValueListObserver: () => (/* binding */ ValueListObserver),
/* harmony export */   add: () => (/* binding */ add),
/* harmony export */   defaultSchema: () => (/* binding */ defaultSchema),
/* harmony export */   del: () => (/* binding */ del),
/* harmony export */   fetch: () => (/* binding */ fetch),
/* harmony export */   prune: () => (/* binding */ prune)
/* harmony export */ });
/*
Stimulus 3.2.1
Copyright © 2023 Basecamp, LLC
 */
class EventListener {
    constructor(eventTarget, eventName, eventOptions) {
        this.eventTarget = eventTarget;
        this.eventName = eventName;
        this.eventOptions = eventOptions;
        this.unorderedBindings = new Set();
    }
    connect() {
        this.eventTarget.addEventListener(this.eventName, this, this.eventOptions);
    }
    disconnect() {
        this.eventTarget.removeEventListener(this.eventName, this, this.eventOptions);
    }
    bindingConnected(binding) {
        this.unorderedBindings.add(binding);
    }
    bindingDisconnected(binding) {
        this.unorderedBindings.delete(binding);
    }
    handleEvent(event) {
        const extendedEvent = extendEvent(event);
        for (const binding of this.bindings) {
            if (extendedEvent.immediatePropagationStopped) {
                break;
            }
            else {
                binding.handleEvent(extendedEvent);
            }
        }
    }
    hasBindings() {
        return this.unorderedBindings.size > 0;
    }
    get bindings() {
        return Array.from(this.unorderedBindings).sort((left, right) => {
            const leftIndex = left.index, rightIndex = right.index;
            return leftIndex < rightIndex ? -1 : leftIndex > rightIndex ? 1 : 0;
        });
    }
}
function extendEvent(event) {
    if ("immediatePropagationStopped" in event) {
        return event;
    }
    else {
        const { stopImmediatePropagation } = event;
        return Object.assign(event, {
            immediatePropagationStopped: false,
            stopImmediatePropagation() {
                this.immediatePropagationStopped = true;
                stopImmediatePropagation.call(this);
            },
        });
    }
}

class Dispatcher {
    constructor(application) {
        this.application = application;
        this.eventListenerMaps = new Map();
        this.started = false;
    }
    start() {
        if (!this.started) {
            this.started = true;
            this.eventListeners.forEach((eventListener) => eventListener.connect());
        }
    }
    stop() {
        if (this.started) {
            this.started = false;
            this.eventListeners.forEach((eventListener) => eventListener.disconnect());
        }
    }
    get eventListeners() {
        return Array.from(this.eventListenerMaps.values()).reduce((listeners, map) => listeners.concat(Array.from(map.values())), []);
    }
    bindingConnected(binding) {
        this.fetchEventListenerForBinding(binding).bindingConnected(binding);
    }
    bindingDisconnected(binding, clearEventListeners = false) {
        this.fetchEventListenerForBinding(binding).bindingDisconnected(binding);
        if (clearEventListeners)
            this.clearEventListenersForBinding(binding);
    }
    handleError(error, message, detail = {}) {
        this.application.handleError(error, `Error ${message}`, detail);
    }
    clearEventListenersForBinding(binding) {
        const eventListener = this.fetchEventListenerForBinding(binding);
        if (!eventListener.hasBindings()) {
            eventListener.disconnect();
            this.removeMappedEventListenerFor(binding);
        }
    }
    removeMappedEventListenerFor(binding) {
        const { eventTarget, eventName, eventOptions } = binding;
        const eventListenerMap = this.fetchEventListenerMapForEventTarget(eventTarget);
        const cacheKey = this.cacheKey(eventName, eventOptions);
        eventListenerMap.delete(cacheKey);
        if (eventListenerMap.size == 0)
            this.eventListenerMaps.delete(eventTarget);
    }
    fetchEventListenerForBinding(binding) {
        const { eventTarget, eventName, eventOptions } = binding;
        return this.fetchEventListener(eventTarget, eventName, eventOptions);
    }
    fetchEventListener(eventTarget, eventName, eventOptions) {
        const eventListenerMap = this.fetchEventListenerMapForEventTarget(eventTarget);
        const cacheKey = this.cacheKey(eventName, eventOptions);
        let eventListener = eventListenerMap.get(cacheKey);
        if (!eventListener) {
            eventListener = this.createEventListener(eventTarget, eventName, eventOptions);
            eventListenerMap.set(cacheKey, eventListener);
        }
        return eventListener;
    }
    createEventListener(eventTarget, eventName, eventOptions) {
        const eventListener = new EventListener(eventTarget, eventName, eventOptions);
        if (this.started) {
            eventListener.connect();
        }
        return eventListener;
    }
    fetchEventListenerMapForEventTarget(eventTarget) {
        let eventListenerMap = this.eventListenerMaps.get(eventTarget);
        if (!eventListenerMap) {
            eventListenerMap = new Map();
            this.eventListenerMaps.set(eventTarget, eventListenerMap);
        }
        return eventListenerMap;
    }
    cacheKey(eventName, eventOptions) {
        const parts = [eventName];
        Object.keys(eventOptions)
            .sort()
            .forEach((key) => {
            parts.push(`${eventOptions[key] ? "" : "!"}${key}`);
        });
        return parts.join(":");
    }
}

const defaultActionDescriptorFilters = {
    stop({ event, value }) {
        if (value)
            event.stopPropagation();
        return true;
    },
    prevent({ event, value }) {
        if (value)
            event.preventDefault();
        return true;
    },
    self({ event, value, element }) {
        if (value) {
            return element === event.target;
        }
        else {
            return true;
        }
    },
};
const descriptorPattern = /^(?:(?:([^.]+?)\+)?(.+?)(?:\.(.+?))?(?:@(window|document))?->)?(.+?)(?:#([^:]+?))(?::(.+))?$/;
function parseActionDescriptorString(descriptorString) {
    const source = descriptorString.trim();
    const matches = source.match(descriptorPattern) || [];
    let eventName = matches[2];
    let keyFilter = matches[3];
    if (keyFilter && !["keydown", "keyup", "keypress"].includes(eventName)) {
        eventName += `.${keyFilter}`;
        keyFilter = "";
    }
    return {
        eventTarget: parseEventTarget(matches[4]),
        eventName,
        eventOptions: matches[7] ? parseEventOptions(matches[7]) : {},
        identifier: matches[5],
        methodName: matches[6],
        keyFilter: matches[1] || keyFilter,
    };
}
function parseEventTarget(eventTargetName) {
    if (eventTargetName == "window") {
        return window;
    }
    else if (eventTargetName == "document") {
        return document;
    }
}
function parseEventOptions(eventOptions) {
    return eventOptions
        .split(":")
        .reduce((options, token) => Object.assign(options, { [token.replace(/^!/, "")]: !/^!/.test(token) }), {});
}
function stringifyEventTarget(eventTarget) {
    if (eventTarget == window) {
        return "window";
    }
    else if (eventTarget == document) {
        return "document";
    }
}

function camelize(value) {
    return value.replace(/(?:[_-])([a-z0-9])/g, (_, char) => char.toUpperCase());
}
function namespaceCamelize(value) {
    return camelize(value.replace(/--/g, "-").replace(/__/g, "_"));
}
function capitalize(value) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}
function dasherize(value) {
    return value.replace(/([A-Z])/g, (_, char) => `-${char.toLowerCase()}`);
}
function tokenize(value) {
    return value.match(/[^\s]+/g) || [];
}

function isSomething(object) {
    return object !== null && object !== undefined;
}
function hasProperty(object, property) {
    return Object.prototype.hasOwnProperty.call(object, property);
}

const allModifiers = ["meta", "ctrl", "alt", "shift"];
class Action {
    constructor(element, index, descriptor, schema) {
        this.element = element;
        this.index = index;
        this.eventTarget = descriptor.eventTarget || element;
        this.eventName = descriptor.eventName || getDefaultEventNameForElement(element) || error("missing event name");
        this.eventOptions = descriptor.eventOptions || {};
        this.identifier = descriptor.identifier || error("missing identifier");
        this.methodName = descriptor.methodName || error("missing method name");
        this.keyFilter = descriptor.keyFilter || "";
        this.schema = schema;
    }
    static forToken(token, schema) {
        return new this(token.element, token.index, parseActionDescriptorString(token.content), schema);
    }
    toString() {
        const eventFilter = this.keyFilter ? `.${this.keyFilter}` : "";
        const eventTarget = this.eventTargetName ? `@${this.eventTargetName}` : "";
        return `${this.eventName}${eventFilter}${eventTarget}->${this.identifier}#${this.methodName}`;
    }
    shouldIgnoreKeyboardEvent(event) {
        if (!this.keyFilter) {
            return false;
        }
        const filters = this.keyFilter.split("+");
        if (this.keyFilterDissatisfied(event, filters)) {
            return true;
        }
        const standardFilter = filters.filter((key) => !allModifiers.includes(key))[0];
        if (!standardFilter) {
            return false;
        }
        if (!hasProperty(this.keyMappings, standardFilter)) {
            error(`contains unknown key filter: ${this.keyFilter}`);
        }
        return this.keyMappings[standardFilter].toLowerCase() !== event.key.toLowerCase();
    }
    shouldIgnoreMouseEvent(event) {
        if (!this.keyFilter) {
            return false;
        }
        const filters = [this.keyFilter];
        if (this.keyFilterDissatisfied(event, filters)) {
            return true;
        }
        return false;
    }
    get params() {
        const params = {};
        const pattern = new RegExp(`^data-${this.identifier}-(.+)-param$`, "i");
        for (const { name, value } of Array.from(this.element.attributes)) {
            const match = name.match(pattern);
            const key = match && match[1];
            if (key) {
                params[camelize(key)] = typecast(value);
            }
        }
        return params;
    }
    get eventTargetName() {
        return stringifyEventTarget(this.eventTarget);
    }
    get keyMappings() {
        return this.schema.keyMappings;
    }
    keyFilterDissatisfied(event, filters) {
        const [meta, ctrl, alt, shift] = allModifiers.map((modifier) => filters.includes(modifier));
        return event.metaKey !== meta || event.ctrlKey !== ctrl || event.altKey !== alt || event.shiftKey !== shift;
    }
}
const defaultEventNames = {
    a: () => "click",
    button: () => "click",
    form: () => "submit",
    details: () => "toggle",
    input: (e) => (e.getAttribute("type") == "submit" ? "click" : "input"),
    select: () => "change",
    textarea: () => "input",
};
function getDefaultEventNameForElement(element) {
    const tagName = element.tagName.toLowerCase();
    if (tagName in defaultEventNames) {
        return defaultEventNames[tagName](element);
    }
}
function error(message) {
    throw new Error(message);
}
function typecast(value) {
    try {
        return JSON.parse(value);
    }
    catch (o_O) {
        return value;
    }
}

class Binding {
    constructor(context, action) {
        this.context = context;
        this.action = action;
    }
    get index() {
        return this.action.index;
    }
    get eventTarget() {
        return this.action.eventTarget;
    }
    get eventOptions() {
        return this.action.eventOptions;
    }
    get identifier() {
        return this.context.identifier;
    }
    handleEvent(event) {
        const actionEvent = this.prepareActionEvent(event);
        if (this.willBeInvokedByEvent(event) && this.applyEventModifiers(actionEvent)) {
            this.invokeWithEvent(actionEvent);
        }
    }
    get eventName() {
        return this.action.eventName;
    }
    get method() {
        const method = this.controller[this.methodName];
        if (typeof method == "function") {
            return method;
        }
        throw new Error(`Action "${this.action}" references undefined method "${this.methodName}"`);
    }
    applyEventModifiers(event) {
        const { element } = this.action;
        const { actionDescriptorFilters } = this.context.application;
        const { controller } = this.context;
        let passes = true;
        for (const [name, value] of Object.entries(this.eventOptions)) {
            if (name in actionDescriptorFilters) {
                const filter = actionDescriptorFilters[name];
                passes = passes && filter({ name, value, event, element, controller });
            }
            else {
                continue;
            }
        }
        return passes;
    }
    prepareActionEvent(event) {
        return Object.assign(event, { params: this.action.params });
    }
    invokeWithEvent(event) {
        const { target, currentTarget } = event;
        try {
            this.method.call(this.controller, event);
            this.context.logDebugActivity(this.methodName, { event, target, currentTarget, action: this.methodName });
        }
        catch (error) {
            const { identifier, controller, element, index } = this;
            const detail = { identifier, controller, element, index, event };
            this.context.handleError(error, `invoking action "${this.action}"`, detail);
        }
    }
    willBeInvokedByEvent(event) {
        const eventTarget = event.target;
        if (event instanceof KeyboardEvent && this.action.shouldIgnoreKeyboardEvent(event)) {
            return false;
        }
        if (event instanceof MouseEvent && this.action.shouldIgnoreMouseEvent(event)) {
            return false;
        }
        if (this.element === eventTarget) {
            return true;
        }
        else if (eventTarget instanceof Element && this.element.contains(eventTarget)) {
            return this.scope.containsElement(eventTarget);
        }
        else {
            return this.scope.containsElement(this.action.element);
        }
    }
    get controller() {
        return this.context.controller;
    }
    get methodName() {
        return this.action.methodName;
    }
    get element() {
        return this.scope.element;
    }
    get scope() {
        return this.context.scope;
    }
}

class ElementObserver {
    constructor(element, delegate) {
        this.mutationObserverInit = { attributes: true, childList: true, subtree: true };
        this.element = element;
        this.started = false;
        this.delegate = delegate;
        this.elements = new Set();
        this.mutationObserver = new MutationObserver((mutations) => this.processMutations(mutations));
    }
    start() {
        if (!this.started) {
            this.started = true;
            this.mutationObserver.observe(this.element, this.mutationObserverInit);
            this.refresh();
        }
    }
    pause(callback) {
        if (this.started) {
            this.mutationObserver.disconnect();
            this.started = false;
        }
        callback();
        if (!this.started) {
            this.mutationObserver.observe(this.element, this.mutationObserverInit);
            this.started = true;
        }
    }
    stop() {
        if (this.started) {
            this.mutationObserver.takeRecords();
            this.mutationObserver.disconnect();
            this.started = false;
        }
    }
    refresh() {
        if (this.started) {
            const matches = new Set(this.matchElementsInTree());
            for (const element of Array.from(this.elements)) {
                if (!matches.has(element)) {
                    this.removeElement(element);
                }
            }
            for (const element of Array.from(matches)) {
                this.addElement(element);
            }
        }
    }
    processMutations(mutations) {
        if (this.started) {
            for (const mutation of mutations) {
                this.processMutation(mutation);
            }
        }
    }
    processMutation(mutation) {
        if (mutation.type == "attributes") {
            this.processAttributeChange(mutation.target, mutation.attributeName);
        }
        else if (mutation.type == "childList") {
            this.processRemovedNodes(mutation.removedNodes);
            this.processAddedNodes(mutation.addedNodes);
        }
    }
    processAttributeChange(element, attributeName) {
        if (this.elements.has(element)) {
            if (this.delegate.elementAttributeChanged && this.matchElement(element)) {
                this.delegate.elementAttributeChanged(element, attributeName);
            }
            else {
                this.removeElement(element);
            }
        }
        else if (this.matchElement(element)) {
            this.addElement(element);
        }
    }
    processRemovedNodes(nodes) {
        for (const node of Array.from(nodes)) {
            const element = this.elementFromNode(node);
            if (element) {
                this.processTree(element, this.removeElement);
            }
        }
    }
    processAddedNodes(nodes) {
        for (const node of Array.from(nodes)) {
            const element = this.elementFromNode(node);
            if (element && this.elementIsActive(element)) {
                this.processTree(element, this.addElement);
            }
        }
    }
    matchElement(element) {
        return this.delegate.matchElement(element);
    }
    matchElementsInTree(tree = this.element) {
        return this.delegate.matchElementsInTree(tree);
    }
    processTree(tree, processor) {
        for (const element of this.matchElementsInTree(tree)) {
            processor.call(this, element);
        }
    }
    elementFromNode(node) {
        if (node.nodeType == Node.ELEMENT_NODE) {
            return node;
        }
    }
    elementIsActive(element) {
        if (element.isConnected != this.element.isConnected) {
            return false;
        }
        else {
            return this.element.contains(element);
        }
    }
    addElement(element) {
        if (!this.elements.has(element)) {
            if (this.elementIsActive(element)) {
                this.elements.add(element);
                if (this.delegate.elementMatched) {
                    this.delegate.elementMatched(element);
                }
            }
        }
    }
    removeElement(element) {
        if (this.elements.has(element)) {
            this.elements.delete(element);
            if (this.delegate.elementUnmatched) {
                this.delegate.elementUnmatched(element);
            }
        }
    }
}

class AttributeObserver {
    constructor(element, attributeName, delegate) {
        this.attributeName = attributeName;
        this.delegate = delegate;
        this.elementObserver = new ElementObserver(element, this);
    }
    get element() {
        return this.elementObserver.element;
    }
    get selector() {
        return `[${this.attributeName}]`;
    }
    start() {
        this.elementObserver.start();
    }
    pause(callback) {
        this.elementObserver.pause(callback);
    }
    stop() {
        this.elementObserver.stop();
    }
    refresh() {
        this.elementObserver.refresh();
    }
    get started() {
        return this.elementObserver.started;
    }
    matchElement(element) {
        return element.hasAttribute(this.attributeName);
    }
    matchElementsInTree(tree) {
        const match = this.matchElement(tree) ? [tree] : [];
        const matches = Array.from(tree.querySelectorAll(this.selector));
        return match.concat(matches);
    }
    elementMatched(element) {
        if (this.delegate.elementMatchedAttribute) {
            this.delegate.elementMatchedAttribute(element, this.attributeName);
        }
    }
    elementUnmatched(element) {
        if (this.delegate.elementUnmatchedAttribute) {
            this.delegate.elementUnmatchedAttribute(element, this.attributeName);
        }
    }
    elementAttributeChanged(element, attributeName) {
        if (this.delegate.elementAttributeValueChanged && this.attributeName == attributeName) {
            this.delegate.elementAttributeValueChanged(element, attributeName);
        }
    }
}

function add(map, key, value) {
    fetch(map, key).add(value);
}
function del(map, key, value) {
    fetch(map, key).delete(value);
    prune(map, key);
}
function fetch(map, key) {
    let values = map.get(key);
    if (!values) {
        values = new Set();
        map.set(key, values);
    }
    return values;
}
function prune(map, key) {
    const values = map.get(key);
    if (values != null && values.size == 0) {
        map.delete(key);
    }
}

class Multimap {
    constructor() {
        this.valuesByKey = new Map();
    }
    get keys() {
        return Array.from(this.valuesByKey.keys());
    }
    get values() {
        const sets = Array.from(this.valuesByKey.values());
        return sets.reduce((values, set) => values.concat(Array.from(set)), []);
    }
    get size() {
        const sets = Array.from(this.valuesByKey.values());
        return sets.reduce((size, set) => size + set.size, 0);
    }
    add(key, value) {
        add(this.valuesByKey, key, value);
    }
    delete(key, value) {
        del(this.valuesByKey, key, value);
    }
    has(key, value) {
        const values = this.valuesByKey.get(key);
        return values != null && values.has(value);
    }
    hasKey(key) {
        return this.valuesByKey.has(key);
    }
    hasValue(value) {
        const sets = Array.from(this.valuesByKey.values());
        return sets.some((set) => set.has(value));
    }
    getValuesForKey(key) {
        const values = this.valuesByKey.get(key);
        return values ? Array.from(values) : [];
    }
    getKeysForValue(value) {
        return Array.from(this.valuesByKey)
            .filter(([_key, values]) => values.has(value))
            .map(([key, _values]) => key);
    }
}

class IndexedMultimap extends Multimap {
    constructor() {
        super();
        this.keysByValue = new Map();
    }
    get values() {
        return Array.from(this.keysByValue.keys());
    }
    add(key, value) {
        super.add(key, value);
        add(this.keysByValue, value, key);
    }
    delete(key, value) {
        super.delete(key, value);
        del(this.keysByValue, value, key);
    }
    hasValue(value) {
        return this.keysByValue.has(value);
    }
    getKeysForValue(value) {
        const set = this.keysByValue.get(value);
        return set ? Array.from(set) : [];
    }
}

class SelectorObserver {
    constructor(element, selector, delegate, details) {
        this._selector = selector;
        this.details = details;
        this.elementObserver = new ElementObserver(element, this);
        this.delegate = delegate;
        this.matchesByElement = new Multimap();
    }
    get started() {
        return this.elementObserver.started;
    }
    get selector() {
        return this._selector;
    }
    set selector(selector) {
        this._selector = selector;
        this.refresh();
    }
    start() {
        this.elementObserver.start();
    }
    pause(callback) {
        this.elementObserver.pause(callback);
    }
    stop() {
        this.elementObserver.stop();
    }
    refresh() {
        this.elementObserver.refresh();
    }
    get element() {
        return this.elementObserver.element;
    }
    matchElement(element) {
        const { selector } = this;
        if (selector) {
            const matches = element.matches(selector);
            if (this.delegate.selectorMatchElement) {
                return matches && this.delegate.selectorMatchElement(element, this.details);
            }
            return matches;
        }
        else {
            return false;
        }
    }
    matchElementsInTree(tree) {
        const { selector } = this;
        if (selector) {
            const match = this.matchElement(tree) ? [tree] : [];
            const matches = Array.from(tree.querySelectorAll(selector)).filter((match) => this.matchElement(match));
            return match.concat(matches);
        }
        else {
            return [];
        }
    }
    elementMatched(element) {
        const { selector } = this;
        if (selector) {
            this.selectorMatched(element, selector);
        }
    }
    elementUnmatched(element) {
        const selectors = this.matchesByElement.getKeysForValue(element);
        for (const selector of selectors) {
            this.selectorUnmatched(element, selector);
        }
    }
    elementAttributeChanged(element, _attributeName) {
        const { selector } = this;
        if (selector) {
            const matches = this.matchElement(element);
            const matchedBefore = this.matchesByElement.has(selector, element);
            if (matches && !matchedBefore) {
                this.selectorMatched(element, selector);
            }
            else if (!matches && matchedBefore) {
                this.selectorUnmatched(element, selector);
            }
        }
    }
    selectorMatched(element, selector) {
        this.delegate.selectorMatched(element, selector, this.details);
        this.matchesByElement.add(selector, element);
    }
    selectorUnmatched(element, selector) {
        this.delegate.selectorUnmatched(element, selector, this.details);
        this.matchesByElement.delete(selector, element);
    }
}

class StringMapObserver {
    constructor(element, delegate) {
        this.element = element;
        this.delegate = delegate;
        this.started = false;
        this.stringMap = new Map();
        this.mutationObserver = new MutationObserver((mutations) => this.processMutations(mutations));
    }
    start() {
        if (!this.started) {
            this.started = true;
            this.mutationObserver.observe(this.element, { attributes: true, attributeOldValue: true });
            this.refresh();
        }
    }
    stop() {
        if (this.started) {
            this.mutationObserver.takeRecords();
            this.mutationObserver.disconnect();
            this.started = false;
        }
    }
    refresh() {
        if (this.started) {
            for (const attributeName of this.knownAttributeNames) {
                this.refreshAttribute(attributeName, null);
            }
        }
    }
    processMutations(mutations) {
        if (this.started) {
            for (const mutation of mutations) {
                this.processMutation(mutation);
            }
        }
    }
    processMutation(mutation) {
        const attributeName = mutation.attributeName;
        if (attributeName) {
            this.refreshAttribute(attributeName, mutation.oldValue);
        }
    }
    refreshAttribute(attributeName, oldValue) {
        const key = this.delegate.getStringMapKeyForAttribute(attributeName);
        if (key != null) {
            if (!this.stringMap.has(attributeName)) {
                this.stringMapKeyAdded(key, attributeName);
            }
            const value = this.element.getAttribute(attributeName);
            if (this.stringMap.get(attributeName) != value) {
                this.stringMapValueChanged(value, key, oldValue);
            }
            if (value == null) {
                const oldValue = this.stringMap.get(attributeName);
                this.stringMap.delete(attributeName);
                if (oldValue)
                    this.stringMapKeyRemoved(key, attributeName, oldValue);
            }
            else {
                this.stringMap.set(attributeName, value);
            }
        }
    }
    stringMapKeyAdded(key, attributeName) {
        if (this.delegate.stringMapKeyAdded) {
            this.delegate.stringMapKeyAdded(key, attributeName);
        }
    }
    stringMapValueChanged(value, key, oldValue) {
        if (this.delegate.stringMapValueChanged) {
            this.delegate.stringMapValueChanged(value, key, oldValue);
        }
    }
    stringMapKeyRemoved(key, attributeName, oldValue) {
        if (this.delegate.stringMapKeyRemoved) {
            this.delegate.stringMapKeyRemoved(key, attributeName, oldValue);
        }
    }
    get knownAttributeNames() {
        return Array.from(new Set(this.currentAttributeNames.concat(this.recordedAttributeNames)));
    }
    get currentAttributeNames() {
        return Array.from(this.element.attributes).map((attribute) => attribute.name);
    }
    get recordedAttributeNames() {
        return Array.from(this.stringMap.keys());
    }
}

class TokenListObserver {
    constructor(element, attributeName, delegate) {
        this.attributeObserver = new AttributeObserver(element, attributeName, this);
        this.delegate = delegate;
        this.tokensByElement = new Multimap();
    }
    get started() {
        return this.attributeObserver.started;
    }
    start() {
        this.attributeObserver.start();
    }
    pause(callback) {
        this.attributeObserver.pause(callback);
    }
    stop() {
        this.attributeObserver.stop();
    }
    refresh() {
        this.attributeObserver.refresh();
    }
    get element() {
        return this.attributeObserver.element;
    }
    get attributeName() {
        return this.attributeObserver.attributeName;
    }
    elementMatchedAttribute(element) {
        this.tokensMatched(this.readTokensForElement(element));
    }
    elementAttributeValueChanged(element) {
        const [unmatchedTokens, matchedTokens] = this.refreshTokensForElement(element);
        this.tokensUnmatched(unmatchedTokens);
        this.tokensMatched(matchedTokens);
    }
    elementUnmatchedAttribute(element) {
        this.tokensUnmatched(this.tokensByElement.getValuesForKey(element));
    }
    tokensMatched(tokens) {
        tokens.forEach((token) => this.tokenMatched(token));
    }
    tokensUnmatched(tokens) {
        tokens.forEach((token) => this.tokenUnmatched(token));
    }
    tokenMatched(token) {
        this.delegate.tokenMatched(token);
        this.tokensByElement.add(token.element, token);
    }
    tokenUnmatched(token) {
        this.delegate.tokenUnmatched(token);
        this.tokensByElement.delete(token.element, token);
    }
    refreshTokensForElement(element) {
        const previousTokens = this.tokensByElement.getValuesForKey(element);
        const currentTokens = this.readTokensForElement(element);
        const firstDifferingIndex = zip(previousTokens, currentTokens).findIndex(([previousToken, currentToken]) => !tokensAreEqual(previousToken, currentToken));
        if (firstDifferingIndex == -1) {
            return [[], []];
        }
        else {
            return [previousTokens.slice(firstDifferingIndex), currentTokens.slice(firstDifferingIndex)];
        }
    }
    readTokensForElement(element) {
        const attributeName = this.attributeName;
        const tokenString = element.getAttribute(attributeName) || "";
        return parseTokenString(tokenString, element, attributeName);
    }
}
function parseTokenString(tokenString, element, attributeName) {
    return tokenString
        .trim()
        .split(/\s+/)
        .filter((content) => content.length)
        .map((content, index) => ({ element, attributeName, content, index }));
}
function zip(left, right) {
    const length = Math.max(left.length, right.length);
    return Array.from({ length }, (_, index) => [left[index], right[index]]);
}
function tokensAreEqual(left, right) {
    return left && right && left.index == right.index && left.content == right.content;
}

class ValueListObserver {
    constructor(element, attributeName, delegate) {
        this.tokenListObserver = new TokenListObserver(element, attributeName, this);
        this.delegate = delegate;
        this.parseResultsByToken = new WeakMap();
        this.valuesByTokenByElement = new WeakMap();
    }
    get started() {
        return this.tokenListObserver.started;
    }
    start() {
        this.tokenListObserver.start();
    }
    stop() {
        this.tokenListObserver.stop();
    }
    refresh() {
        this.tokenListObserver.refresh();
    }
    get element() {
        return this.tokenListObserver.element;
    }
    get attributeName() {
        return this.tokenListObserver.attributeName;
    }
    tokenMatched(token) {
        const { element } = token;
        const { value } = this.fetchParseResultForToken(token);
        if (value) {
            this.fetchValuesByTokenForElement(element).set(token, value);
            this.delegate.elementMatchedValue(element, value);
        }
    }
    tokenUnmatched(token) {
        const { element } = token;
        const { value } = this.fetchParseResultForToken(token);
        if (value) {
            this.fetchValuesByTokenForElement(element).delete(token);
            this.delegate.elementUnmatchedValue(element, value);
        }
    }
    fetchParseResultForToken(token) {
        let parseResult = this.parseResultsByToken.get(token);
        if (!parseResult) {
            parseResult = this.parseToken(token);
            this.parseResultsByToken.set(token, parseResult);
        }
        return parseResult;
    }
    fetchValuesByTokenForElement(element) {
        let valuesByToken = this.valuesByTokenByElement.get(element);
        if (!valuesByToken) {
            valuesByToken = new Map();
            this.valuesByTokenByElement.set(element, valuesByToken);
        }
        return valuesByToken;
    }
    parseToken(token) {
        try {
            const value = this.delegate.parseValueForToken(token);
            return { value };
        }
        catch (error) {
            return { error };
        }
    }
}

class BindingObserver {
    constructor(context, delegate) {
        this.context = context;
        this.delegate = delegate;
        this.bindingsByAction = new Map();
    }
    start() {
        if (!this.valueListObserver) {
            this.valueListObserver = new ValueListObserver(this.element, this.actionAttribute, this);
            this.valueListObserver.start();
        }
    }
    stop() {
        if (this.valueListObserver) {
            this.valueListObserver.stop();
            delete this.valueListObserver;
            this.disconnectAllActions();
        }
    }
    get element() {
        return this.context.element;
    }
    get identifier() {
        return this.context.identifier;
    }
    get actionAttribute() {
        return this.schema.actionAttribute;
    }
    get schema() {
        return this.context.schema;
    }
    get bindings() {
        return Array.from(this.bindingsByAction.values());
    }
    connectAction(action) {
        const binding = new Binding(this.context, action);
        this.bindingsByAction.set(action, binding);
        this.delegate.bindingConnected(binding);
    }
    disconnectAction(action) {
        const binding = this.bindingsByAction.get(action);
        if (binding) {
            this.bindingsByAction.delete(action);
            this.delegate.bindingDisconnected(binding);
        }
    }
    disconnectAllActions() {
        this.bindings.forEach((binding) => this.delegate.bindingDisconnected(binding, true));
        this.bindingsByAction.clear();
    }
    parseValueForToken(token) {
        const action = Action.forToken(token, this.schema);
        if (action.identifier == this.identifier) {
            return action;
        }
    }
    elementMatchedValue(element, action) {
        this.connectAction(action);
    }
    elementUnmatchedValue(element, action) {
        this.disconnectAction(action);
    }
}

class ValueObserver {
    constructor(context, receiver) {
        this.context = context;
        this.receiver = receiver;
        this.stringMapObserver = new StringMapObserver(this.element, this);
        this.valueDescriptorMap = this.controller.valueDescriptorMap;
    }
    start() {
        this.stringMapObserver.start();
        this.invokeChangedCallbacksForDefaultValues();
    }
    stop() {
        this.stringMapObserver.stop();
    }
    get element() {
        return this.context.element;
    }
    get controller() {
        return this.context.controller;
    }
    getStringMapKeyForAttribute(attributeName) {
        if (attributeName in this.valueDescriptorMap) {
            return this.valueDescriptorMap[attributeName].name;
        }
    }
    stringMapKeyAdded(key, attributeName) {
        const descriptor = this.valueDescriptorMap[attributeName];
        if (!this.hasValue(key)) {
            this.invokeChangedCallback(key, descriptor.writer(this.receiver[key]), descriptor.writer(descriptor.defaultValue));
        }
    }
    stringMapValueChanged(value, name, oldValue) {
        const descriptor = this.valueDescriptorNameMap[name];
        if (value === null)
            return;
        if (oldValue === null) {
            oldValue = descriptor.writer(descriptor.defaultValue);
        }
        this.invokeChangedCallback(name, value, oldValue);
    }
    stringMapKeyRemoved(key, attributeName, oldValue) {
        const descriptor = this.valueDescriptorNameMap[key];
        if (this.hasValue(key)) {
            this.invokeChangedCallback(key, descriptor.writer(this.receiver[key]), oldValue);
        }
        else {
            this.invokeChangedCallback(key, descriptor.writer(descriptor.defaultValue), oldValue);
        }
    }
    invokeChangedCallbacksForDefaultValues() {
        for (const { key, name, defaultValue, writer } of this.valueDescriptors) {
            if (defaultValue != undefined && !this.controller.data.has(key)) {
                this.invokeChangedCallback(name, writer(defaultValue), undefined);
            }
        }
    }
    invokeChangedCallback(name, rawValue, rawOldValue) {
        const changedMethodName = `${name}Changed`;
        const changedMethod = this.receiver[changedMethodName];
        if (typeof changedMethod == "function") {
            const descriptor = this.valueDescriptorNameMap[name];
            try {
                const value = descriptor.reader(rawValue);
                let oldValue = rawOldValue;
                if (rawOldValue) {
                    oldValue = descriptor.reader(rawOldValue);
                }
                changedMethod.call(this.receiver, value, oldValue);
            }
            catch (error) {
                if (error instanceof TypeError) {
                    error.message = `Stimulus Value "${this.context.identifier}.${descriptor.name}" - ${error.message}`;
                }
                throw error;
            }
        }
    }
    get valueDescriptors() {
        const { valueDescriptorMap } = this;
        return Object.keys(valueDescriptorMap).map((key) => valueDescriptorMap[key]);
    }
    get valueDescriptorNameMap() {
        const descriptors = {};
        Object.keys(this.valueDescriptorMap).forEach((key) => {
            const descriptor = this.valueDescriptorMap[key];
            descriptors[descriptor.name] = descriptor;
        });
        return descriptors;
    }
    hasValue(attributeName) {
        const descriptor = this.valueDescriptorNameMap[attributeName];
        const hasMethodName = `has${capitalize(descriptor.name)}`;
        return this.receiver[hasMethodName];
    }
}

class TargetObserver {
    constructor(context, delegate) {
        this.context = context;
        this.delegate = delegate;
        this.targetsByName = new Multimap();
    }
    start() {
        if (!this.tokenListObserver) {
            this.tokenListObserver = new TokenListObserver(this.element, this.attributeName, this);
            this.tokenListObserver.start();
        }
    }
    stop() {
        if (this.tokenListObserver) {
            this.disconnectAllTargets();
            this.tokenListObserver.stop();
            delete this.tokenListObserver;
        }
    }
    tokenMatched({ element, content: name }) {
        if (this.scope.containsElement(element)) {
            this.connectTarget(element, name);
        }
    }
    tokenUnmatched({ element, content: name }) {
        this.disconnectTarget(element, name);
    }
    connectTarget(element, name) {
        var _a;
        if (!this.targetsByName.has(name, element)) {
            this.targetsByName.add(name, element);
            (_a = this.tokenListObserver) === null || _a === void 0 ? void 0 : _a.pause(() => this.delegate.targetConnected(element, name));
        }
    }
    disconnectTarget(element, name) {
        var _a;
        if (this.targetsByName.has(name, element)) {
            this.targetsByName.delete(name, element);
            (_a = this.tokenListObserver) === null || _a === void 0 ? void 0 : _a.pause(() => this.delegate.targetDisconnected(element, name));
        }
    }
    disconnectAllTargets() {
        for (const name of this.targetsByName.keys) {
            for (const element of this.targetsByName.getValuesForKey(name)) {
                this.disconnectTarget(element, name);
            }
        }
    }
    get attributeName() {
        return `data-${this.context.identifier}-target`;
    }
    get element() {
        return this.context.element;
    }
    get scope() {
        return this.context.scope;
    }
}

function readInheritableStaticArrayValues(constructor, propertyName) {
    const ancestors = getAncestorsForConstructor(constructor);
    return Array.from(ancestors.reduce((values, constructor) => {
        getOwnStaticArrayValues(constructor, propertyName).forEach((name) => values.add(name));
        return values;
    }, new Set()));
}
function readInheritableStaticObjectPairs(constructor, propertyName) {
    const ancestors = getAncestorsForConstructor(constructor);
    return ancestors.reduce((pairs, constructor) => {
        pairs.push(...getOwnStaticObjectPairs(constructor, propertyName));
        return pairs;
    }, []);
}
function getAncestorsForConstructor(constructor) {
    const ancestors = [];
    while (constructor) {
        ancestors.push(constructor);
        constructor = Object.getPrototypeOf(constructor);
    }
    return ancestors.reverse();
}
function getOwnStaticArrayValues(constructor, propertyName) {
    const definition = constructor[propertyName];
    return Array.isArray(definition) ? definition : [];
}
function getOwnStaticObjectPairs(constructor, propertyName) {
    const definition = constructor[propertyName];
    return definition ? Object.keys(definition).map((key) => [key, definition[key]]) : [];
}

class OutletObserver {
    constructor(context, delegate) {
        this.started = false;
        this.context = context;
        this.delegate = delegate;
        this.outletsByName = new Multimap();
        this.outletElementsByName = new Multimap();
        this.selectorObserverMap = new Map();
        this.attributeObserverMap = new Map();
    }
    start() {
        if (!this.started) {
            this.outletDefinitions.forEach((outletName) => {
                this.setupSelectorObserverForOutlet(outletName);
                this.setupAttributeObserverForOutlet(outletName);
            });
            this.started = true;
            this.dependentContexts.forEach((context) => context.refresh());
        }
    }
    refresh() {
        this.selectorObserverMap.forEach((observer) => observer.refresh());
        this.attributeObserverMap.forEach((observer) => observer.refresh());
    }
    stop() {
        if (this.started) {
            this.started = false;
            this.disconnectAllOutlets();
            this.stopSelectorObservers();
            this.stopAttributeObservers();
        }
    }
    stopSelectorObservers() {
        if (this.selectorObserverMap.size > 0) {
            this.selectorObserverMap.forEach((observer) => observer.stop());
            this.selectorObserverMap.clear();
        }
    }
    stopAttributeObservers() {
        if (this.attributeObserverMap.size > 0) {
            this.attributeObserverMap.forEach((observer) => observer.stop());
            this.attributeObserverMap.clear();
        }
    }
    selectorMatched(element, _selector, { outletName }) {
        const outlet = this.getOutlet(element, outletName);
        if (outlet) {
            this.connectOutlet(outlet, element, outletName);
        }
    }
    selectorUnmatched(element, _selector, { outletName }) {
        const outlet = this.getOutletFromMap(element, outletName);
        if (outlet) {
            this.disconnectOutlet(outlet, element, outletName);
        }
    }
    selectorMatchElement(element, { outletName }) {
        const selector = this.selector(outletName);
        const hasOutlet = this.hasOutlet(element, outletName);
        const hasOutletController = element.matches(`[${this.schema.controllerAttribute}~=${outletName}]`);
        if (selector) {
            return hasOutlet && hasOutletController && element.matches(selector);
        }
        else {
            return false;
        }
    }
    elementMatchedAttribute(_element, attributeName) {
        const outletName = this.getOutletNameFromOutletAttributeName(attributeName);
        if (outletName) {
            this.updateSelectorObserverForOutlet(outletName);
        }
    }
    elementAttributeValueChanged(_element, attributeName) {
        const outletName = this.getOutletNameFromOutletAttributeName(attributeName);
        if (outletName) {
            this.updateSelectorObserverForOutlet(outletName);
        }
    }
    elementUnmatchedAttribute(_element, attributeName) {
        const outletName = this.getOutletNameFromOutletAttributeName(attributeName);
        if (outletName) {
            this.updateSelectorObserverForOutlet(outletName);
        }
    }
    connectOutlet(outlet, element, outletName) {
        var _a;
        if (!this.outletElementsByName.has(outletName, element)) {
            this.outletsByName.add(outletName, outlet);
            this.outletElementsByName.add(outletName, element);
            (_a = this.selectorObserverMap.get(outletName)) === null || _a === void 0 ? void 0 : _a.pause(() => this.delegate.outletConnected(outlet, element, outletName));
        }
    }
    disconnectOutlet(outlet, element, outletName) {
        var _a;
        if (this.outletElementsByName.has(outletName, element)) {
            this.outletsByName.delete(outletName, outlet);
            this.outletElementsByName.delete(outletName, element);
            (_a = this.selectorObserverMap
                .get(outletName)) === null || _a === void 0 ? void 0 : _a.pause(() => this.delegate.outletDisconnected(outlet, element, outletName));
        }
    }
    disconnectAllOutlets() {
        for (const outletName of this.outletElementsByName.keys) {
            for (const element of this.outletElementsByName.getValuesForKey(outletName)) {
                for (const outlet of this.outletsByName.getValuesForKey(outletName)) {
                    this.disconnectOutlet(outlet, element, outletName);
                }
            }
        }
    }
    updateSelectorObserverForOutlet(outletName) {
        const observer = this.selectorObserverMap.get(outletName);
        if (observer) {
            observer.selector = this.selector(outletName);
        }
    }
    setupSelectorObserverForOutlet(outletName) {
        const selector = this.selector(outletName);
        const selectorObserver = new SelectorObserver(document.body, selector, this, { outletName });
        this.selectorObserverMap.set(outletName, selectorObserver);
        selectorObserver.start();
    }
    setupAttributeObserverForOutlet(outletName) {
        const attributeName = this.attributeNameForOutletName(outletName);
        const attributeObserver = new AttributeObserver(this.scope.element, attributeName, this);
        this.attributeObserverMap.set(outletName, attributeObserver);
        attributeObserver.start();
    }
    selector(outletName) {
        return this.scope.outlets.getSelectorForOutletName(outletName);
    }
    attributeNameForOutletName(outletName) {
        return this.scope.schema.outletAttributeForScope(this.identifier, outletName);
    }
    getOutletNameFromOutletAttributeName(attributeName) {
        return this.outletDefinitions.find((outletName) => this.attributeNameForOutletName(outletName) === attributeName);
    }
    get outletDependencies() {
        const dependencies = new Multimap();
        this.router.modules.forEach((module) => {
            const constructor = module.definition.controllerConstructor;
            const outlets = readInheritableStaticArrayValues(constructor, "outlets");
            outlets.forEach((outlet) => dependencies.add(outlet, module.identifier));
        });
        return dependencies;
    }
    get outletDefinitions() {
        return this.outletDependencies.getKeysForValue(this.identifier);
    }
    get dependentControllerIdentifiers() {
        return this.outletDependencies.getValuesForKey(this.identifier);
    }
    get dependentContexts() {
        const identifiers = this.dependentControllerIdentifiers;
        return this.router.contexts.filter((context) => identifiers.includes(context.identifier));
    }
    hasOutlet(element, outletName) {
        return !!this.getOutlet(element, outletName) || !!this.getOutletFromMap(element, outletName);
    }
    getOutlet(element, outletName) {
        return this.application.getControllerForElementAndIdentifier(element, outletName);
    }
    getOutletFromMap(element, outletName) {
        return this.outletsByName.getValuesForKey(outletName).find((outlet) => outlet.element === element);
    }
    get scope() {
        return this.context.scope;
    }
    get schema() {
        return this.context.schema;
    }
    get identifier() {
        return this.context.identifier;
    }
    get application() {
        return this.context.application;
    }
    get router() {
        return this.application.router;
    }
}

class Context {
    constructor(module, scope) {
        this.logDebugActivity = (functionName, detail = {}) => {
            const { identifier, controller, element } = this;
            detail = Object.assign({ identifier, controller, element }, detail);
            this.application.logDebugActivity(this.identifier, functionName, detail);
        };
        this.module = module;
        this.scope = scope;
        this.controller = new module.controllerConstructor(this);
        this.bindingObserver = new BindingObserver(this, this.dispatcher);
        this.valueObserver = new ValueObserver(this, this.controller);
        this.targetObserver = new TargetObserver(this, this);
        this.outletObserver = new OutletObserver(this, this);
        try {
            this.controller.initialize();
            this.logDebugActivity("initialize");
        }
        catch (error) {
            this.handleError(error, "initializing controller");
        }
    }
    connect() {
        this.bindingObserver.start();
        this.valueObserver.start();
        this.targetObserver.start();
        this.outletObserver.start();
        try {
            this.controller.connect();
            this.logDebugActivity("connect");
        }
        catch (error) {
            this.handleError(error, "connecting controller");
        }
    }
    refresh() {
        this.outletObserver.refresh();
    }
    disconnect() {
        try {
            this.controller.disconnect();
            this.logDebugActivity("disconnect");
        }
        catch (error) {
            this.handleError(error, "disconnecting controller");
        }
        this.outletObserver.stop();
        this.targetObserver.stop();
        this.valueObserver.stop();
        this.bindingObserver.stop();
    }
    get application() {
        return this.module.application;
    }
    get identifier() {
        return this.module.identifier;
    }
    get schema() {
        return this.application.schema;
    }
    get dispatcher() {
        return this.application.dispatcher;
    }
    get element() {
        return this.scope.element;
    }
    get parentElement() {
        return this.element.parentElement;
    }
    handleError(error, message, detail = {}) {
        const { identifier, controller, element } = this;
        detail = Object.assign({ identifier, controller, element }, detail);
        this.application.handleError(error, `Error ${message}`, detail);
    }
    targetConnected(element, name) {
        this.invokeControllerMethod(`${name}TargetConnected`, element);
    }
    targetDisconnected(element, name) {
        this.invokeControllerMethod(`${name}TargetDisconnected`, element);
    }
    outletConnected(outlet, element, name) {
        this.invokeControllerMethod(`${namespaceCamelize(name)}OutletConnected`, outlet, element);
    }
    outletDisconnected(outlet, element, name) {
        this.invokeControllerMethod(`${namespaceCamelize(name)}OutletDisconnected`, outlet, element);
    }
    invokeControllerMethod(methodName, ...args) {
        const controller = this.controller;
        if (typeof controller[methodName] == "function") {
            controller[methodName](...args);
        }
    }
}

function bless(constructor) {
    return shadow(constructor, getBlessedProperties(constructor));
}
function shadow(constructor, properties) {
    const shadowConstructor = extend(constructor);
    const shadowProperties = getShadowProperties(constructor.prototype, properties);
    Object.defineProperties(shadowConstructor.prototype, shadowProperties);
    return shadowConstructor;
}
function getBlessedProperties(constructor) {
    const blessings = readInheritableStaticArrayValues(constructor, "blessings");
    return blessings.reduce((blessedProperties, blessing) => {
        const properties = blessing(constructor);
        for (const key in properties) {
            const descriptor = blessedProperties[key] || {};
            blessedProperties[key] = Object.assign(descriptor, properties[key]);
        }
        return blessedProperties;
    }, {});
}
function getShadowProperties(prototype, properties) {
    return getOwnKeys(properties).reduce((shadowProperties, key) => {
        const descriptor = getShadowedDescriptor(prototype, properties, key);
        if (descriptor) {
            Object.assign(shadowProperties, { [key]: descriptor });
        }
        return shadowProperties;
    }, {});
}
function getShadowedDescriptor(prototype, properties, key) {
    const shadowingDescriptor = Object.getOwnPropertyDescriptor(prototype, key);
    const shadowedByValue = shadowingDescriptor && "value" in shadowingDescriptor;
    if (!shadowedByValue) {
        const descriptor = Object.getOwnPropertyDescriptor(properties, key).value;
        if (shadowingDescriptor) {
            descriptor.get = shadowingDescriptor.get || descriptor.get;
            descriptor.set = shadowingDescriptor.set || descriptor.set;
        }
        return descriptor;
    }
}
const getOwnKeys = (() => {
    if (typeof Object.getOwnPropertySymbols == "function") {
        return (object) => [...Object.getOwnPropertyNames(object), ...Object.getOwnPropertySymbols(object)];
    }
    else {
        return Object.getOwnPropertyNames;
    }
})();
const extend = (() => {
    function extendWithReflect(constructor) {
        function extended() {
            return Reflect.construct(constructor, arguments, new.target);
        }
        extended.prototype = Object.create(constructor.prototype, {
            constructor: { value: extended },
        });
        Reflect.setPrototypeOf(extended, constructor);
        return extended;
    }
    function testReflectExtension() {
        const a = function () {
            this.a.call(this);
        };
        const b = extendWithReflect(a);
        b.prototype.a = function () { };
        return new b();
    }
    try {
        testReflectExtension();
        return extendWithReflect;
    }
    catch (error) {
        return (constructor) => class extended extends constructor {
        };
    }
})();

function blessDefinition(definition) {
    return {
        identifier: definition.identifier,
        controllerConstructor: bless(definition.controllerConstructor),
    };
}

class Module {
    constructor(application, definition) {
        this.application = application;
        this.definition = blessDefinition(definition);
        this.contextsByScope = new WeakMap();
        this.connectedContexts = new Set();
    }
    get identifier() {
        return this.definition.identifier;
    }
    get controllerConstructor() {
        return this.definition.controllerConstructor;
    }
    get contexts() {
        return Array.from(this.connectedContexts);
    }
    connectContextForScope(scope) {
        const context = this.fetchContextForScope(scope);
        this.connectedContexts.add(context);
        context.connect();
    }
    disconnectContextForScope(scope) {
        const context = this.contextsByScope.get(scope);
        if (context) {
            this.connectedContexts.delete(context);
            context.disconnect();
        }
    }
    fetchContextForScope(scope) {
        let context = this.contextsByScope.get(scope);
        if (!context) {
            context = new Context(this, scope);
            this.contextsByScope.set(scope, context);
        }
        return context;
    }
}

class ClassMap {
    constructor(scope) {
        this.scope = scope;
    }
    has(name) {
        return this.data.has(this.getDataKey(name));
    }
    get(name) {
        return this.getAll(name)[0];
    }
    getAll(name) {
        const tokenString = this.data.get(this.getDataKey(name)) || "";
        return tokenize(tokenString);
    }
    getAttributeName(name) {
        return this.data.getAttributeNameForKey(this.getDataKey(name));
    }
    getDataKey(name) {
        return `${name}-class`;
    }
    get data() {
        return this.scope.data;
    }
}

class DataMap {
    constructor(scope) {
        this.scope = scope;
    }
    get element() {
        return this.scope.element;
    }
    get identifier() {
        return this.scope.identifier;
    }
    get(key) {
        const name = this.getAttributeNameForKey(key);
        return this.element.getAttribute(name);
    }
    set(key, value) {
        const name = this.getAttributeNameForKey(key);
        this.element.setAttribute(name, value);
        return this.get(key);
    }
    has(key) {
        const name = this.getAttributeNameForKey(key);
        return this.element.hasAttribute(name);
    }
    delete(key) {
        if (this.has(key)) {
            const name = this.getAttributeNameForKey(key);
            this.element.removeAttribute(name);
            return true;
        }
        else {
            return false;
        }
    }
    getAttributeNameForKey(key) {
        return `data-${this.identifier}-${dasherize(key)}`;
    }
}

class Guide {
    constructor(logger) {
        this.warnedKeysByObject = new WeakMap();
        this.logger = logger;
    }
    warn(object, key, message) {
        let warnedKeys = this.warnedKeysByObject.get(object);
        if (!warnedKeys) {
            warnedKeys = new Set();
            this.warnedKeysByObject.set(object, warnedKeys);
        }
        if (!warnedKeys.has(key)) {
            warnedKeys.add(key);
            this.logger.warn(message, object);
        }
    }
}

function attributeValueContainsToken(attributeName, token) {
    return `[${attributeName}~="${token}"]`;
}

class TargetSet {
    constructor(scope) {
        this.scope = scope;
    }
    get element() {
        return this.scope.element;
    }
    get identifier() {
        return this.scope.identifier;
    }
    get schema() {
        return this.scope.schema;
    }
    has(targetName) {
        return this.find(targetName) != null;
    }
    find(...targetNames) {
        return targetNames.reduce((target, targetName) => target || this.findTarget(targetName) || this.findLegacyTarget(targetName), undefined);
    }
    findAll(...targetNames) {
        return targetNames.reduce((targets, targetName) => [
            ...targets,
            ...this.findAllTargets(targetName),
            ...this.findAllLegacyTargets(targetName),
        ], []);
    }
    findTarget(targetName) {
        const selector = this.getSelectorForTargetName(targetName);
        return this.scope.findElement(selector);
    }
    findAllTargets(targetName) {
        const selector = this.getSelectorForTargetName(targetName);
        return this.scope.findAllElements(selector);
    }
    getSelectorForTargetName(targetName) {
        const attributeName = this.schema.targetAttributeForScope(this.identifier);
        return attributeValueContainsToken(attributeName, targetName);
    }
    findLegacyTarget(targetName) {
        const selector = this.getLegacySelectorForTargetName(targetName);
        return this.deprecate(this.scope.findElement(selector), targetName);
    }
    findAllLegacyTargets(targetName) {
        const selector = this.getLegacySelectorForTargetName(targetName);
        return this.scope.findAllElements(selector).map((element) => this.deprecate(element, targetName));
    }
    getLegacySelectorForTargetName(targetName) {
        const targetDescriptor = `${this.identifier}.${targetName}`;
        return attributeValueContainsToken(this.schema.targetAttribute, targetDescriptor);
    }
    deprecate(element, targetName) {
        if (element) {
            const { identifier } = this;
            const attributeName = this.schema.targetAttribute;
            const revisedAttributeName = this.schema.targetAttributeForScope(identifier);
            this.guide.warn(element, `target:${targetName}`, `Please replace ${attributeName}="${identifier}.${targetName}" with ${revisedAttributeName}="${targetName}". ` +
                `The ${attributeName} attribute is deprecated and will be removed in a future version of Stimulus.`);
        }
        return element;
    }
    get guide() {
        return this.scope.guide;
    }
}

class OutletSet {
    constructor(scope, controllerElement) {
        this.scope = scope;
        this.controllerElement = controllerElement;
    }
    get element() {
        return this.scope.element;
    }
    get identifier() {
        return this.scope.identifier;
    }
    get schema() {
        return this.scope.schema;
    }
    has(outletName) {
        return this.find(outletName) != null;
    }
    find(...outletNames) {
        return outletNames.reduce((outlet, outletName) => outlet || this.findOutlet(outletName), undefined);
    }
    findAll(...outletNames) {
        return outletNames.reduce((outlets, outletName) => [...outlets, ...this.findAllOutlets(outletName)], []);
    }
    getSelectorForOutletName(outletName) {
        const attributeName = this.schema.outletAttributeForScope(this.identifier, outletName);
        return this.controllerElement.getAttribute(attributeName);
    }
    findOutlet(outletName) {
        const selector = this.getSelectorForOutletName(outletName);
        if (selector)
            return this.findElement(selector, outletName);
    }
    findAllOutlets(outletName) {
        const selector = this.getSelectorForOutletName(outletName);
        return selector ? this.findAllElements(selector, outletName) : [];
    }
    findElement(selector, outletName) {
        const elements = this.scope.queryElements(selector);
        return elements.filter((element) => this.matchesElement(element, selector, outletName))[0];
    }
    findAllElements(selector, outletName) {
        const elements = this.scope.queryElements(selector);
        return elements.filter((element) => this.matchesElement(element, selector, outletName));
    }
    matchesElement(element, selector, outletName) {
        const controllerAttribute = element.getAttribute(this.scope.schema.controllerAttribute) || "";
        return element.matches(selector) && controllerAttribute.split(" ").includes(outletName);
    }
}

class Scope {
    constructor(schema, element, identifier, logger) {
        this.targets = new TargetSet(this);
        this.classes = new ClassMap(this);
        this.data = new DataMap(this);
        this.containsElement = (element) => {
            return element.closest(this.controllerSelector) === this.element;
        };
        this.schema = schema;
        this.element = element;
        this.identifier = identifier;
        this.guide = new Guide(logger);
        this.outlets = new OutletSet(this.documentScope, element);
    }
    findElement(selector) {
        return this.element.matches(selector) ? this.element : this.queryElements(selector).find(this.containsElement);
    }
    findAllElements(selector) {
        return [
            ...(this.element.matches(selector) ? [this.element] : []),
            ...this.queryElements(selector).filter(this.containsElement),
        ];
    }
    queryElements(selector) {
        return Array.from(this.element.querySelectorAll(selector));
    }
    get controllerSelector() {
        return attributeValueContainsToken(this.schema.controllerAttribute, this.identifier);
    }
    get isDocumentScope() {
        return this.element === document.documentElement;
    }
    get documentScope() {
        return this.isDocumentScope
            ? this
            : new Scope(this.schema, document.documentElement, this.identifier, this.guide.logger);
    }
}

class ScopeObserver {
    constructor(element, schema, delegate) {
        this.element = element;
        this.schema = schema;
        this.delegate = delegate;
        this.valueListObserver = new ValueListObserver(this.element, this.controllerAttribute, this);
        this.scopesByIdentifierByElement = new WeakMap();
        this.scopeReferenceCounts = new WeakMap();
    }
    start() {
        this.valueListObserver.start();
    }
    stop() {
        this.valueListObserver.stop();
    }
    get controllerAttribute() {
        return this.schema.controllerAttribute;
    }
    parseValueForToken(token) {
        const { element, content: identifier } = token;
        return this.parseValueForElementAndIdentifier(element, identifier);
    }
    parseValueForElementAndIdentifier(element, identifier) {
        const scopesByIdentifier = this.fetchScopesByIdentifierForElement(element);
        let scope = scopesByIdentifier.get(identifier);
        if (!scope) {
            scope = this.delegate.createScopeForElementAndIdentifier(element, identifier);
            scopesByIdentifier.set(identifier, scope);
        }
        return scope;
    }
    elementMatchedValue(element, value) {
        const referenceCount = (this.scopeReferenceCounts.get(value) || 0) + 1;
        this.scopeReferenceCounts.set(value, referenceCount);
        if (referenceCount == 1) {
            this.delegate.scopeConnected(value);
        }
    }
    elementUnmatchedValue(element, value) {
        const referenceCount = this.scopeReferenceCounts.get(value);
        if (referenceCount) {
            this.scopeReferenceCounts.set(value, referenceCount - 1);
            if (referenceCount == 1) {
                this.delegate.scopeDisconnected(value);
            }
        }
    }
    fetchScopesByIdentifierForElement(element) {
        let scopesByIdentifier = this.scopesByIdentifierByElement.get(element);
        if (!scopesByIdentifier) {
            scopesByIdentifier = new Map();
            this.scopesByIdentifierByElement.set(element, scopesByIdentifier);
        }
        return scopesByIdentifier;
    }
}

class Router {
    constructor(application) {
        this.application = application;
        this.scopeObserver = new ScopeObserver(this.element, this.schema, this);
        this.scopesByIdentifier = new Multimap();
        this.modulesByIdentifier = new Map();
    }
    get element() {
        return this.application.element;
    }
    get schema() {
        return this.application.schema;
    }
    get logger() {
        return this.application.logger;
    }
    get controllerAttribute() {
        return this.schema.controllerAttribute;
    }
    get modules() {
        return Array.from(this.modulesByIdentifier.values());
    }
    get contexts() {
        return this.modules.reduce((contexts, module) => contexts.concat(module.contexts), []);
    }
    start() {
        this.scopeObserver.start();
    }
    stop() {
        this.scopeObserver.stop();
    }
    loadDefinition(definition) {
        this.unloadIdentifier(definition.identifier);
        const module = new Module(this.application, definition);
        this.connectModule(module);
        const afterLoad = definition.controllerConstructor.afterLoad;
        if (afterLoad) {
            afterLoad.call(definition.controllerConstructor, definition.identifier, this.application);
        }
    }
    unloadIdentifier(identifier) {
        const module = this.modulesByIdentifier.get(identifier);
        if (module) {
            this.disconnectModule(module);
        }
    }
    getContextForElementAndIdentifier(element, identifier) {
        const module = this.modulesByIdentifier.get(identifier);
        if (module) {
            return module.contexts.find((context) => context.element == element);
        }
    }
    proposeToConnectScopeForElementAndIdentifier(element, identifier) {
        const scope = this.scopeObserver.parseValueForElementAndIdentifier(element, identifier);
        if (scope) {
            this.scopeObserver.elementMatchedValue(scope.element, scope);
        }
        else {
            console.error(`Couldn't find or create scope for identifier: "${identifier}" and element:`, element);
        }
    }
    handleError(error, message, detail) {
        this.application.handleError(error, message, detail);
    }
    createScopeForElementAndIdentifier(element, identifier) {
        return new Scope(this.schema, element, identifier, this.logger);
    }
    scopeConnected(scope) {
        this.scopesByIdentifier.add(scope.identifier, scope);
        const module = this.modulesByIdentifier.get(scope.identifier);
        if (module) {
            module.connectContextForScope(scope);
        }
    }
    scopeDisconnected(scope) {
        this.scopesByIdentifier.delete(scope.identifier, scope);
        const module = this.modulesByIdentifier.get(scope.identifier);
        if (module) {
            module.disconnectContextForScope(scope);
        }
    }
    connectModule(module) {
        this.modulesByIdentifier.set(module.identifier, module);
        const scopes = this.scopesByIdentifier.getValuesForKey(module.identifier);
        scopes.forEach((scope) => module.connectContextForScope(scope));
    }
    disconnectModule(module) {
        this.modulesByIdentifier.delete(module.identifier);
        const scopes = this.scopesByIdentifier.getValuesForKey(module.identifier);
        scopes.forEach((scope) => module.disconnectContextForScope(scope));
    }
}

const defaultSchema = {
    controllerAttribute: "data-controller",
    actionAttribute: "data-action",
    targetAttribute: "data-target",
    targetAttributeForScope: (identifier) => `data-${identifier}-target`,
    outletAttributeForScope: (identifier, outlet) => `data-${identifier}-${outlet}-outlet`,
    keyMappings: Object.assign(Object.assign({ enter: "Enter", tab: "Tab", esc: "Escape", space: " ", up: "ArrowUp", down: "ArrowDown", left: "ArrowLeft", right: "ArrowRight", home: "Home", end: "End", page_up: "PageUp", page_down: "PageDown" }, objectFromEntries("abcdefghijklmnopqrstuvwxyz".split("").map((c) => [c, c]))), objectFromEntries("0123456789".split("").map((n) => [n, n]))),
};
function objectFromEntries(array) {
    return array.reduce((memo, [k, v]) => (Object.assign(Object.assign({}, memo), { [k]: v })), {});
}

class Application {
    constructor(element = document.documentElement, schema = defaultSchema) {
        this.logger = console;
        this.debug = false;
        this.logDebugActivity = (identifier, functionName, detail = {}) => {
            if (this.debug) {
                this.logFormattedMessage(identifier, functionName, detail);
            }
        };
        this.element = element;
        this.schema = schema;
        this.dispatcher = new Dispatcher(this);
        this.router = new Router(this);
        this.actionDescriptorFilters = Object.assign({}, defaultActionDescriptorFilters);
    }
    static start(element, schema) {
        const application = new this(element, schema);
        application.start();
        return application;
    }
    async start() {
        await domReady();
        this.logDebugActivity("application", "starting");
        this.dispatcher.start();
        this.router.start();
        this.logDebugActivity("application", "start");
    }
    stop() {
        this.logDebugActivity("application", "stopping");
        this.dispatcher.stop();
        this.router.stop();
        this.logDebugActivity("application", "stop");
    }
    register(identifier, controllerConstructor) {
        this.load({ identifier, controllerConstructor });
    }
    registerActionOption(name, filter) {
        this.actionDescriptorFilters[name] = filter;
    }
    load(head, ...rest) {
        const definitions = Array.isArray(head) ? head : [head, ...rest];
        definitions.forEach((definition) => {
            if (definition.controllerConstructor.shouldLoad) {
                this.router.loadDefinition(definition);
            }
        });
    }
    unload(head, ...rest) {
        const identifiers = Array.isArray(head) ? head : [head, ...rest];
        identifiers.forEach((identifier) => this.router.unloadIdentifier(identifier));
    }
    get controllers() {
        return this.router.contexts.map((context) => context.controller);
    }
    getControllerForElementAndIdentifier(element, identifier) {
        const context = this.router.getContextForElementAndIdentifier(element, identifier);
        return context ? context.controller : null;
    }
    handleError(error, message, detail) {
        var _a;
        this.logger.error(`%s\n\n%o\n\n%o`, message, error, detail);
        (_a = window.onerror) === null || _a === void 0 ? void 0 : _a.call(window, message, "", 0, 0, error);
    }
    logFormattedMessage(identifier, functionName, detail = {}) {
        detail = Object.assign({ application: this }, detail);
        this.logger.groupCollapsed(`${identifier} #${functionName}`);
        this.logger.log("details:", Object.assign({}, detail));
        this.logger.groupEnd();
    }
}
function domReady() {
    return new Promise((resolve) => {
        if (document.readyState == "loading") {
            document.addEventListener("DOMContentLoaded", () => resolve());
        }
        else {
            resolve();
        }
    });
}

function ClassPropertiesBlessing(constructor) {
    const classes = readInheritableStaticArrayValues(constructor, "classes");
    return classes.reduce((properties, classDefinition) => {
        return Object.assign(properties, propertiesForClassDefinition(classDefinition));
    }, {});
}
function propertiesForClassDefinition(key) {
    return {
        [`${key}Class`]: {
            get() {
                const { classes } = this;
                if (classes.has(key)) {
                    return classes.get(key);
                }
                else {
                    const attribute = classes.getAttributeName(key);
                    throw new Error(`Missing attribute "${attribute}"`);
                }
            },
        },
        [`${key}Classes`]: {
            get() {
                return this.classes.getAll(key);
            },
        },
        [`has${capitalize(key)}Class`]: {
            get() {
                return this.classes.has(key);
            },
        },
    };
}

function OutletPropertiesBlessing(constructor) {
    const outlets = readInheritableStaticArrayValues(constructor, "outlets");
    return outlets.reduce((properties, outletDefinition) => {
        return Object.assign(properties, propertiesForOutletDefinition(outletDefinition));
    }, {});
}
function getOutletController(controller, element, identifier) {
    return controller.application.getControllerForElementAndIdentifier(element, identifier);
}
function getControllerAndEnsureConnectedScope(controller, element, outletName) {
    let outletController = getOutletController(controller, element, outletName);
    if (outletController)
        return outletController;
    controller.application.router.proposeToConnectScopeForElementAndIdentifier(element, outletName);
    outletController = getOutletController(controller, element, outletName);
    if (outletController)
        return outletController;
}
function propertiesForOutletDefinition(name) {
    const camelizedName = namespaceCamelize(name);
    return {
        [`${camelizedName}Outlet`]: {
            get() {
                const outletElement = this.outlets.find(name);
                const selector = this.outlets.getSelectorForOutletName(name);
                if (outletElement) {
                    const outletController = getControllerAndEnsureConnectedScope(this, outletElement, name);
                    if (outletController)
                        return outletController;
                    throw new Error(`The provided outlet element is missing an outlet controller "${name}" instance for host controller "${this.identifier}"`);
                }
                throw new Error(`Missing outlet element "${name}" for host controller "${this.identifier}". Stimulus couldn't find a matching outlet element using selector "${selector}".`);
            },
        },
        [`${camelizedName}Outlets`]: {
            get() {
                const outlets = this.outlets.findAll(name);
                if (outlets.length > 0) {
                    return outlets
                        .map((outletElement) => {
                        const outletController = getControllerAndEnsureConnectedScope(this, outletElement, name);
                        if (outletController)
                            return outletController;
                        console.warn(`The provided outlet element is missing an outlet controller "${name}" instance for host controller "${this.identifier}"`, outletElement);
                    })
                        .filter((controller) => controller);
                }
                return [];
            },
        },
        [`${camelizedName}OutletElement`]: {
            get() {
                const outletElement = this.outlets.find(name);
                const selector = this.outlets.getSelectorForOutletName(name);
                if (outletElement) {
                    return outletElement;
                }
                else {
                    throw new Error(`Missing outlet element "${name}" for host controller "${this.identifier}". Stimulus couldn't find a matching outlet element using selector "${selector}".`);
                }
            },
        },
        [`${camelizedName}OutletElements`]: {
            get() {
                return this.outlets.findAll(name);
            },
        },
        [`has${capitalize(camelizedName)}Outlet`]: {
            get() {
                return this.outlets.has(name);
            },
        },
    };
}

function TargetPropertiesBlessing(constructor) {
    const targets = readInheritableStaticArrayValues(constructor, "targets");
    return targets.reduce((properties, targetDefinition) => {
        return Object.assign(properties, propertiesForTargetDefinition(targetDefinition));
    }, {});
}
function propertiesForTargetDefinition(name) {
    return {
        [`${name}Target`]: {
            get() {
                const target = this.targets.find(name);
                if (target) {
                    return target;
                }
                else {
                    throw new Error(`Missing target element "${name}" for "${this.identifier}" controller`);
                }
            },
        },
        [`${name}Targets`]: {
            get() {
                return this.targets.findAll(name);
            },
        },
        [`has${capitalize(name)}Target`]: {
            get() {
                return this.targets.has(name);
            },
        },
    };
}

function ValuePropertiesBlessing(constructor) {
    const valueDefinitionPairs = readInheritableStaticObjectPairs(constructor, "values");
    const propertyDescriptorMap = {
        valueDescriptorMap: {
            get() {
                return valueDefinitionPairs.reduce((result, valueDefinitionPair) => {
                    const valueDescriptor = parseValueDefinitionPair(valueDefinitionPair, this.identifier);
                    const attributeName = this.data.getAttributeNameForKey(valueDescriptor.key);
                    return Object.assign(result, { [attributeName]: valueDescriptor });
                }, {});
            },
        },
    };
    return valueDefinitionPairs.reduce((properties, valueDefinitionPair) => {
        return Object.assign(properties, propertiesForValueDefinitionPair(valueDefinitionPair));
    }, propertyDescriptorMap);
}
function propertiesForValueDefinitionPair(valueDefinitionPair, controller) {
    const definition = parseValueDefinitionPair(valueDefinitionPair, controller);
    const { key, name, reader: read, writer: write } = definition;
    return {
        [name]: {
            get() {
                const value = this.data.get(key);
                if (value !== null) {
                    return read(value);
                }
                else {
                    return definition.defaultValue;
                }
            },
            set(value) {
                if (value === undefined) {
                    this.data.delete(key);
                }
                else {
                    this.data.set(key, write(value));
                }
            },
        },
        [`has${capitalize(name)}`]: {
            get() {
                return this.data.has(key) || definition.hasCustomDefaultValue;
            },
        },
    };
}
function parseValueDefinitionPair([token, typeDefinition], controller) {
    return valueDescriptorForTokenAndTypeDefinition({
        controller,
        token,
        typeDefinition,
    });
}
function parseValueTypeConstant(constant) {
    switch (constant) {
        case Array:
            return "array";
        case Boolean:
            return "boolean";
        case Number:
            return "number";
        case Object:
            return "object";
        case String:
            return "string";
    }
}
function parseValueTypeDefault(defaultValue) {
    switch (typeof defaultValue) {
        case "boolean":
            return "boolean";
        case "number":
            return "number";
        case "string":
            return "string";
    }
    if (Array.isArray(defaultValue))
        return "array";
    if (Object.prototype.toString.call(defaultValue) === "[object Object]")
        return "object";
}
function parseValueTypeObject(payload) {
    const { controller, token, typeObject } = payload;
    const hasType = isSomething(typeObject.type);
    const hasDefault = isSomething(typeObject.default);
    const fullObject = hasType && hasDefault;
    const onlyType = hasType && !hasDefault;
    const onlyDefault = !hasType && hasDefault;
    const typeFromObject = parseValueTypeConstant(typeObject.type);
    const typeFromDefaultValue = parseValueTypeDefault(payload.typeObject.default);
    if (onlyType)
        return typeFromObject;
    if (onlyDefault)
        return typeFromDefaultValue;
    if (typeFromObject !== typeFromDefaultValue) {
        const propertyPath = controller ? `${controller}.${token}` : token;
        throw new Error(`The specified default value for the Stimulus Value "${propertyPath}" must match the defined type "${typeFromObject}". The provided default value of "${typeObject.default}" is of type "${typeFromDefaultValue}".`);
    }
    if (fullObject)
        return typeFromObject;
}
function parseValueTypeDefinition(payload) {
    const { controller, token, typeDefinition } = payload;
    const typeObject = { controller, token, typeObject: typeDefinition };
    const typeFromObject = parseValueTypeObject(typeObject);
    const typeFromDefaultValue = parseValueTypeDefault(typeDefinition);
    const typeFromConstant = parseValueTypeConstant(typeDefinition);
    const type = typeFromObject || typeFromDefaultValue || typeFromConstant;
    if (type)
        return type;
    const propertyPath = controller ? `${controller}.${typeDefinition}` : token;
    throw new Error(`Unknown value type "${propertyPath}" for "${token}" value`);
}
function defaultValueForDefinition(typeDefinition) {
    const constant = parseValueTypeConstant(typeDefinition);
    if (constant)
        return defaultValuesByType[constant];
    const hasDefault = hasProperty(typeDefinition, "default");
    const hasType = hasProperty(typeDefinition, "type");
    const typeObject = typeDefinition;
    if (hasDefault)
        return typeObject.default;
    if (hasType) {
        const { type } = typeObject;
        const constantFromType = parseValueTypeConstant(type);
        if (constantFromType)
            return defaultValuesByType[constantFromType];
    }
    return typeDefinition;
}
function valueDescriptorForTokenAndTypeDefinition(payload) {
    const { token, typeDefinition } = payload;
    const key = `${dasherize(token)}-value`;
    const type = parseValueTypeDefinition(payload);
    return {
        type,
        key,
        name: camelize(key),
        get defaultValue() {
            return defaultValueForDefinition(typeDefinition);
        },
        get hasCustomDefaultValue() {
            return parseValueTypeDefault(typeDefinition) !== undefined;
        },
        reader: readers[type],
        writer: writers[type] || writers.default,
    };
}
const defaultValuesByType = {
    get array() {
        return [];
    },
    boolean: false,
    number: 0,
    get object() {
        return {};
    },
    string: "",
};
const readers = {
    array(value) {
        const array = JSON.parse(value);
        if (!Array.isArray(array)) {
            throw new TypeError(`expected value of type "array" but instead got value "${value}" of type "${parseValueTypeDefault(array)}"`);
        }
        return array;
    },
    boolean(value) {
        return !(value == "0" || String(value).toLowerCase() == "false");
    },
    number(value) {
        return Number(value.replace(/_/g, ""));
    },
    object(value) {
        const object = JSON.parse(value);
        if (object === null || typeof object != "object" || Array.isArray(object)) {
            throw new TypeError(`expected value of type "object" but instead got value "${value}" of type "${parseValueTypeDefault(object)}"`);
        }
        return object;
    },
    string(value) {
        return value;
    },
};
const writers = {
    default: writeString,
    array: writeJSON,
    object: writeJSON,
};
function writeJSON(value) {
    return JSON.stringify(value);
}
function writeString(value) {
    return `${value}`;
}

class Controller {
    constructor(context) {
        this.context = context;
    }
    static get shouldLoad() {
        return true;
    }
    static afterLoad(_identifier, _application) {
        return;
    }
    get application() {
        return this.context.application;
    }
    get scope() {
        return this.context.scope;
    }
    get element() {
        return this.scope.element;
    }
    get identifier() {
        return this.scope.identifier;
    }
    get targets() {
        return this.scope.targets;
    }
    get outlets() {
        return this.scope.outlets;
    }
    get classes() {
        return this.scope.classes;
    }
    get data() {
        return this.scope.data;
    }
    initialize() {
    }
    connect() {
    }
    disconnect() {
    }
    dispatch(eventName, { target = this.element, detail = {}, prefix = this.identifier, bubbles = true, cancelable = true, } = {}) {
        const type = prefix ? `${prefix}:${eventName}` : eventName;
        const event = new CustomEvent(type, { detail, bubbles, cancelable });
        target.dispatchEvent(event);
        return event;
    }
}
Controller.blessings = [
    ClassPropertiesBlessing,
    TargetPropertiesBlessing,
    ValuePropertiesBlessing,
    OutletPropertiesBlessing,
];
Controller.targets = [];
Controller.outlets = [];
Controller.values = {};




/***/ },

/***/ "./node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js"
/*!***************************************************************!*\
  !*** ./node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FetchEnctype: () => (/* binding */ FetchEnctype),
/* harmony export */   FetchMethod: () => (/* binding */ FetchMethod),
/* harmony export */   FetchRequest: () => (/* binding */ FetchRequest),
/* harmony export */   FetchResponse: () => (/* binding */ FetchResponse),
/* harmony export */   FrameElement: () => (/* binding */ FrameElement),
/* harmony export */   FrameLoadingStyle: () => (/* binding */ FrameLoadingStyle),
/* harmony export */   FrameRenderer: () => (/* binding */ FrameRenderer),
/* harmony export */   PageRenderer: () => (/* binding */ PageRenderer),
/* harmony export */   PageSnapshot: () => (/* binding */ PageSnapshot),
/* harmony export */   StreamActions: () => (/* binding */ StreamActions),
/* harmony export */   StreamElement: () => (/* binding */ StreamElement),
/* harmony export */   StreamSourceElement: () => (/* binding */ StreamSourceElement),
/* harmony export */   cache: () => (/* binding */ cache),
/* harmony export */   clearCache: () => (/* binding */ clearCache),
/* harmony export */   config: () => (/* binding */ config),
/* harmony export */   connectStreamSource: () => (/* binding */ connectStreamSource),
/* harmony export */   disconnectStreamSource: () => (/* binding */ disconnectStreamSource),
/* harmony export */   fetch: () => (/* binding */ fetchWithTurboHeaders),
/* harmony export */   fetchEnctypeFromString: () => (/* binding */ fetchEnctypeFromString),
/* harmony export */   fetchMethodFromString: () => (/* binding */ fetchMethodFromString),
/* harmony export */   isSafe: () => (/* binding */ isSafe),
/* harmony export */   morphBodyElements: () => (/* binding */ morphBodyElements),
/* harmony export */   morphChildren: () => (/* binding */ morphChildren),
/* harmony export */   morphElements: () => (/* binding */ morphElements),
/* harmony export */   morphTurboFrameElements: () => (/* binding */ morphTurboFrameElements),
/* harmony export */   navigator: () => (/* binding */ navigator$1),
/* harmony export */   registerAdapter: () => (/* binding */ registerAdapter),
/* harmony export */   renderStreamMessage: () => (/* binding */ renderStreamMessage),
/* harmony export */   session: () => (/* binding */ session),
/* harmony export */   setConfirmMethod: () => (/* binding */ setConfirmMethod),
/* harmony export */   setFormMode: () => (/* binding */ setFormMode),
/* harmony export */   setProgressBarDelay: () => (/* binding */ setProgressBarDelay),
/* harmony export */   start: () => (/* binding */ start),
/* harmony export */   visit: () => (/* binding */ visit)
/* harmony export */ });
/*!
Turbo 8.0.19
Copyright © 2025 37signals LLC
 */
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2019 Javan Makhmali
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

(function (prototype) {
  if (typeof prototype.requestSubmit == "function") return

  prototype.requestSubmit = function (submitter) {
    if (submitter) {
      validateSubmitter(submitter, this);
      submitter.click();
    } else {
      submitter = document.createElement("input");
      submitter.type = "submit";
      submitter.hidden = true;
      this.appendChild(submitter);
      submitter.click();
      this.removeChild(submitter);
    }
  };

  function validateSubmitter(submitter, form) {
    submitter instanceof HTMLElement || raise(TypeError, "parameter 1 is not of type 'HTMLElement'");
    submitter.type == "submit" || raise(TypeError, "The specified element is not a submit button");
    submitter.form == form ||
      raise(DOMException, "The specified element is not owned by this form element", "NotFoundError");
  }

  function raise(errorConstructor, message, name) {
    throw new errorConstructor("Failed to execute 'requestSubmit' on 'HTMLFormElement': " + message + ".", name)
  }
})(HTMLFormElement.prototype);

const submittersByForm = new WeakMap();

function findSubmitterFromClickTarget(target) {
  const element = target instanceof Element ? target : target instanceof Node ? target.parentElement : null;
  const candidate = element ? element.closest("input, button") : null;
  return candidate?.type == "submit" ? candidate : null
}

function clickCaptured(event) {
  const submitter = findSubmitterFromClickTarget(event.target);

  if (submitter && submitter.form) {
    submittersByForm.set(submitter.form, submitter);
  }
}

(function () {
  if ("submitter" in Event.prototype) return

  let prototype = window.Event.prototype;
  // Certain versions of Safari 15 have a bug where they won't
  // populate the submitter. This hurts TurboDrive's enable/disable detection.
  // See https://bugs.webkit.org/show_bug.cgi?id=229660
  if ("SubmitEvent" in window) {
    const prototypeOfSubmitEvent = window.SubmitEvent.prototype;

    if (/Apple Computer/.test(navigator.vendor) && !("submitter" in prototypeOfSubmitEvent)) {
      prototype = prototypeOfSubmitEvent;
    } else {
      return // polyfill not needed
    }
  }

  addEventListener("click", clickCaptured, true);

  Object.defineProperty(prototype, "submitter", {
    get() {
      if (this.type == "submit" && this.target instanceof HTMLFormElement) {
        return submittersByForm.get(this.target)
      }
    }
  });
})();

const FrameLoadingStyle = {
  eager: "eager",
  lazy: "lazy"
};

/**
 * Contains a fragment of HTML which is updated based on navigation within
 * it (e.g. via links or form submissions).
 *
 * @customElement turbo-frame
 * @example
 *   <turbo-frame id="messages">
 *     <a href="/messages/expanded">
 *       Show all expanded messages in this frame.
 *     </a>
 *
 *     <form action="/messages">
 *       Show response from this form within this frame.
 *     </form>
 *   </turbo-frame>
 */
class FrameElement extends HTMLElement {
  static delegateConstructor = undefined

  loaded = Promise.resolve()

  static get observedAttributes() {
    return ["disabled", "loading", "src"]
  }

  constructor() {
    super();
    this.delegate = new FrameElement.delegateConstructor(this);
  }

  connectedCallback() {
    this.delegate.connect();
  }

  disconnectedCallback() {
    this.delegate.disconnect();
  }

  reload() {
    return this.delegate.sourceURLReloaded()
  }

  attributeChangedCallback(name) {
    if (name == "loading") {
      this.delegate.loadingStyleChanged();
    } else if (name == "src") {
      this.delegate.sourceURLChanged();
    } else if (name == "disabled") {
      this.delegate.disabledChanged();
    }
  }

  /**
   * Gets the URL to lazily load source HTML from
   */
  get src() {
    return this.getAttribute("src")
  }

  /**
   * Sets the URL to lazily load source HTML from
   */
  set src(value) {
    if (value) {
      this.setAttribute("src", value);
    } else {
      this.removeAttribute("src");
    }
  }

  /**
   * Gets the refresh mode for the frame.
   */
  get refresh() {
    return this.getAttribute("refresh")
  }

  /**
   * Sets the refresh mode for the frame.
   */
  set refresh(value) {
    if (value) {
      this.setAttribute("refresh", value);
    } else {
      this.removeAttribute("refresh");
    }
  }

  get shouldReloadWithMorph() {
    return this.src && this.refresh === "morph"
  }

  /**
   * Determines if the element is loading
   */
  get loading() {
    return frameLoadingStyleFromString(this.getAttribute("loading") || "")
  }

  /**
   * Sets the value of if the element is loading
   */
  set loading(value) {
    if (value) {
      this.setAttribute("loading", value);
    } else {
      this.removeAttribute("loading");
    }
  }

  /**
   * Gets the disabled state of the frame.
   *
   * If disabled, no requests will be intercepted by the frame.
   */
  get disabled() {
    return this.hasAttribute("disabled")
  }

  /**
   * Sets the disabled state of the frame.
   *
   * If disabled, no requests will be intercepted by the frame.
   */
  set disabled(value) {
    if (value) {
      this.setAttribute("disabled", "");
    } else {
      this.removeAttribute("disabled");
    }
  }

  /**
   * Gets the autoscroll state of the frame.
   *
   * If true, the frame will be scrolled into view automatically on update.
   */
  get autoscroll() {
    return this.hasAttribute("autoscroll")
  }

  /**
   * Sets the autoscroll state of the frame.
   *
   * If true, the frame will be scrolled into view automatically on update.
   */
  set autoscroll(value) {
    if (value) {
      this.setAttribute("autoscroll", "");
    } else {
      this.removeAttribute("autoscroll");
    }
  }

  /**
   * Determines if the element has finished loading
   */
  get complete() {
    return !this.delegate.isLoading
  }

  /**
   * Gets the active state of the frame.
   *
   * If inactive, source changes will not be observed.
   */
  get isActive() {
    return this.ownerDocument === document && !this.isPreview
  }

  /**
   * Sets the active state of the frame.
   *
   * If inactive, source changes will not be observed.
   */
  get isPreview() {
    return this.ownerDocument?.documentElement?.hasAttribute("data-turbo-preview")
  }
}

function frameLoadingStyleFromString(style) {
  switch (style.toLowerCase()) {
    case "lazy":
      return FrameLoadingStyle.lazy
    default:
      return FrameLoadingStyle.eager
  }
}

const drive = {
  enabled: true,
  progressBarDelay: 500,
  unvisitableExtensions: new Set(
    [
      ".7z", ".aac", ".apk", ".avi", ".bmp", ".bz2", ".css", ".csv", ".deb", ".dmg", ".doc",
      ".docx", ".exe", ".gif", ".gz", ".heic", ".heif", ".ico", ".iso", ".jpeg", ".jpg",
      ".js", ".json", ".m4a", ".mkv", ".mov", ".mp3", ".mp4", ".mpeg", ".mpg", ".msi",
      ".ogg", ".ogv", ".pdf", ".pkg", ".png", ".ppt", ".pptx", ".rar", ".rtf",
      ".svg", ".tar", ".tif", ".tiff", ".txt", ".wav", ".webm", ".webp", ".wma", ".wmv",
      ".xls", ".xlsx", ".xml", ".zip"
    ]
  )
};

function activateScriptElement(element) {
  if (element.getAttribute("data-turbo-eval") == "false") {
    return element
  } else {
    const createdScriptElement = document.createElement("script");
    const cspNonce = getCspNonce();
    if (cspNonce) {
      createdScriptElement.nonce = cspNonce;
    }
    createdScriptElement.textContent = element.textContent;
    createdScriptElement.async = false;
    copyElementAttributes(createdScriptElement, element);
    return createdScriptElement
  }
}

function copyElementAttributes(destinationElement, sourceElement) {
  for (const { name, value } of sourceElement.attributes) {
    destinationElement.setAttribute(name, value);
  }
}

function createDocumentFragment(html) {
  const template = document.createElement("template");
  template.innerHTML = html;
  return template.content
}

function dispatch(eventName, { target, cancelable, detail } = {}) {
  const event = new CustomEvent(eventName, {
    cancelable,
    bubbles: true,
    composed: true,
    detail
  });

  if (target && target.isConnected) {
    target.dispatchEvent(event);
  } else {
    document.documentElement.dispatchEvent(event);
  }

  return event
}

function cancelEvent(event) {
  event.preventDefault();
  event.stopImmediatePropagation();
}

function nextRepaint() {
  if (document.visibilityState === "hidden") {
    return nextEventLoopTick()
  } else {
    return nextAnimationFrame()
  }
}

function nextAnimationFrame() {
  return new Promise((resolve) => requestAnimationFrame(() => resolve()))
}

function nextEventLoopTick() {
  return new Promise((resolve) => setTimeout(() => resolve(), 0))
}

function nextMicrotask() {
  return Promise.resolve()
}

function parseHTMLDocument(html = "") {
  return new DOMParser().parseFromString(html, "text/html")
}

function unindent(strings, ...values) {
  const lines = interpolate(strings, values).replace(/^\n/, "").split("\n");
  const match = lines[0].match(/^\s+/);
  const indent = match ? match[0].length : 0;
  return lines.map((line) => line.slice(indent)).join("\n")
}

function interpolate(strings, values) {
  return strings.reduce((result, string, i) => {
    const value = values[i] == undefined ? "" : values[i];
    return result + string + value
  }, "")
}

function uuid() {
  return Array.from({ length: 36 })
    .map((_, i) => {
      if (i == 8 || i == 13 || i == 18 || i == 23) {
        return "-"
      } else if (i == 14) {
        return "4"
      } else if (i == 19) {
        return (Math.floor(Math.random() * 4) + 8).toString(16)
      } else {
        return Math.floor(Math.random() * 15).toString(16)
      }
    })
    .join("")
}

function getAttribute(attributeName, ...elements) {
  for (const value of elements.map((element) => element?.getAttribute(attributeName))) {
    if (typeof value == "string") return value
  }

  return null
}

function hasAttribute(attributeName, ...elements) {
  return elements.some((element) => element && element.hasAttribute(attributeName))
}

function markAsBusy(...elements) {
  for (const element of elements) {
    if (element.localName == "turbo-frame") {
      element.setAttribute("busy", "");
    }
    element.setAttribute("aria-busy", "true");
  }
}

function clearBusyState(...elements) {
  for (const element of elements) {
    if (element.localName == "turbo-frame") {
      element.removeAttribute("busy");
    }

    element.removeAttribute("aria-busy");
  }
}

function waitForLoad(element, timeoutInMilliseconds = 2000) {
  return new Promise((resolve) => {
    const onComplete = () => {
      element.removeEventListener("error", onComplete);
      element.removeEventListener("load", onComplete);
      resolve();
    };

    element.addEventListener("load", onComplete, { once: true });
    element.addEventListener("error", onComplete, { once: true });
    setTimeout(resolve, timeoutInMilliseconds);
  })
}

function getHistoryMethodForAction(action) {
  switch (action) {
    case "replace":
      return history.replaceState
    case "advance":
    case "restore":
      return history.pushState
  }
}

function isAction(action) {
  return action == "advance" || action == "replace" || action == "restore"
}

function getVisitAction(...elements) {
  const action = getAttribute("data-turbo-action", ...elements);

  return isAction(action) ? action : null
}

function getMetaElement(name) {
  return document.querySelector(`meta[name="${name}"]`)
}

function getMetaContent(name) {
  const element = getMetaElement(name);
  return element && element.content
}

function getCspNonce() {
  const element = getMetaElement("csp-nonce");

  if (element) {
    const { nonce, content } = element;
    return nonce == "" ? content : nonce
  }
}

function setMetaContent(name, content) {
  let element = getMetaElement(name);

  if (!element) {
    element = document.createElement("meta");
    element.setAttribute("name", name);

    document.head.appendChild(element);
  }

  element.setAttribute("content", content);

  return element
}

function findClosestRecursively(element, selector) {
  if (element instanceof Element) {
    return (
      element.closest(selector) || findClosestRecursively(element.assignedSlot || element.getRootNode()?.host, selector)
    )
  }
}

function elementIsFocusable(element) {
  const inertDisabledOrHidden = "[inert], :disabled, [hidden], details:not([open]), dialog:not([open])";

  return !!element && element.closest(inertDisabledOrHidden) == null && typeof element.focus == "function"
}

function queryAutofocusableElement(elementOrDocumentFragment) {
  return Array.from(elementOrDocumentFragment.querySelectorAll("[autofocus]")).find(elementIsFocusable)
}

async function around(callback, reader) {
  const before = reader();

  callback();

  await nextAnimationFrame();

  const after = reader();

  return [before, after]
}

function doesNotTargetIFrame(name) {
  if (name === "_blank") {
    return false
  } else if (name) {
    for (const element of document.getElementsByName(name)) {
      if (element instanceof HTMLIFrameElement) return false
    }

    return true
  } else {
    return true
  }
}

function findLinkFromClickTarget(target) {
  const link = findClosestRecursively(target, "a[href], a[xlink\\:href]");

  if (!link) return null
  if (link.hasAttribute("download")) return null
  if (link.hasAttribute("target") && link.target !== "_self") return null

  return link
}

function getLocationForLink(link) {
  return expandURL(link.getAttribute("href") || "")
}

function debounce(fn, delay) {
  let timeoutId = null;

  return (...args) => {
    const callback = () => fn.apply(this, args);
    clearTimeout(timeoutId);
    timeoutId = setTimeout(callback, delay);
  }
}

const submitter = {
  "aria-disabled": {
    beforeSubmit: submitter => {
      submitter.setAttribute("aria-disabled", "true");
      submitter.addEventListener("click", cancelEvent);
    },

    afterSubmit: submitter => {
      submitter.removeAttribute("aria-disabled");
      submitter.removeEventListener("click", cancelEvent);
    }
  },

  "disabled": {
    beforeSubmit: submitter => submitter.disabled = true,
    afterSubmit: submitter => submitter.disabled = false
  }
};

class Config {
  #submitter = null

  constructor(config) {
    Object.assign(this, config);
  }

  get submitter() {
    return this.#submitter
  }

  set submitter(value) {
    this.#submitter = submitter[value] || value;
  }
}

const forms = new Config({
  mode: "on",
  submitter: "disabled"
});

const config = {
  drive,
  forms
};

function expandURL(locatable) {
  return new URL(locatable.toString(), document.baseURI)
}

function getAnchor(url) {
  let anchorMatch;
  if (url.hash) {
    return url.hash.slice(1)
    // eslint-disable-next-line no-cond-assign
  } else if ((anchorMatch = url.href.match(/#(.*)$/))) {
    return anchorMatch[1]
  }
}

function getAction$1(form, submitter) {
  const action = submitter?.getAttribute("formaction") || form.getAttribute("action") || form.action;

  return expandURL(action)
}

function getExtension(url) {
  return (getLastPathComponent(url).match(/\.[^.]*$/) || [])[0] || ""
}

function isPrefixedBy(baseURL, url) {
  const prefix = addTrailingSlash(url.origin + url.pathname);
  return addTrailingSlash(baseURL.href) === prefix || baseURL.href.startsWith(prefix)
}

function locationIsVisitable(location, rootLocation) {
  return isPrefixedBy(location, rootLocation) && !config.drive.unvisitableExtensions.has(getExtension(location))
}

function getRequestURL(url) {
  const anchor = getAnchor(url);
  return anchor != null ? url.href.slice(0, -(anchor.length + 1)) : url.href
}

function toCacheKey(url) {
  return getRequestURL(url)
}

function urlsAreEqual(left, right) {
  return expandURL(left).href == expandURL(right).href
}

function getPathComponents(url) {
  return url.pathname.split("/").slice(1)
}

function getLastPathComponent(url) {
  return getPathComponents(url).slice(-1)[0]
}

function addTrailingSlash(value) {
  return value.endsWith("/") ? value : value + "/"
}

class FetchResponse {
  constructor(response) {
    this.response = response;
  }

  get succeeded() {
    return this.response.ok
  }

  get failed() {
    return !this.succeeded
  }

  get clientError() {
    return this.statusCode >= 400 && this.statusCode <= 499
  }

  get serverError() {
    return this.statusCode >= 500 && this.statusCode <= 599
  }

  get redirected() {
    return this.response.redirected
  }

  get location() {
    return expandURL(this.response.url)
  }

  get isHTML() {
    return this.contentType && this.contentType.match(/^(?:text\/([^\s;,]+\b)?html|application\/xhtml\+xml)\b/)
  }

  get statusCode() {
    return this.response.status
  }

  get contentType() {
    return this.header("Content-Type")
  }

  get responseText() {
    return this.response.clone().text()
  }

  get responseHTML() {
    if (this.isHTML) {
      return this.response.clone().text()
    } else {
      return Promise.resolve(undefined)
    }
  }

  header(name) {
    return this.response.headers.get(name)
  }
}

class LimitedSet extends Set {
  constructor(maxSize) {
    super();
    this.maxSize = maxSize;
  }

  add(value) {
    if (this.size >= this.maxSize) {
      const iterator = this.values();
      const oldestValue = iterator.next().value;
      this.delete(oldestValue);
    }
    super.add(value);
  }
}

const recentRequests = new LimitedSet(20);

function fetchWithTurboHeaders(url, options = {}) {
  const modifiedHeaders = new Headers(options.headers || {});
  const requestUID = uuid();
  recentRequests.add(requestUID);
  modifiedHeaders.append("X-Turbo-Request-Id", requestUID);

  return window.fetch(url, {
    ...options,
    headers: modifiedHeaders
  })
}

function fetchMethodFromString(method) {
  switch (method.toLowerCase()) {
    case "get":
      return FetchMethod.get
    case "post":
      return FetchMethod.post
    case "put":
      return FetchMethod.put
    case "patch":
      return FetchMethod.patch
    case "delete":
      return FetchMethod.delete
  }
}

const FetchMethod = {
  get: "get",
  post: "post",
  put: "put",
  patch: "patch",
  delete: "delete"
};

function fetchEnctypeFromString(encoding) {
  switch (encoding.toLowerCase()) {
    case FetchEnctype.multipart:
      return FetchEnctype.multipart
    case FetchEnctype.plain:
      return FetchEnctype.plain
    default:
      return FetchEnctype.urlEncoded
  }
}

const FetchEnctype = {
  urlEncoded: "application/x-www-form-urlencoded",
  multipart: "multipart/form-data",
  plain: "text/plain"
};

class FetchRequest {
  abortController = new AbortController()
  #resolveRequestPromise = (_value) => {}

  constructor(delegate, method, location, requestBody = new URLSearchParams(), target = null, enctype = FetchEnctype.urlEncoded) {
    const [url, body] = buildResourceAndBody(expandURL(location), method, requestBody, enctype);

    this.delegate = delegate;
    this.url = url;
    this.target = target;
    this.fetchOptions = {
      credentials: "same-origin",
      redirect: "follow",
      method: method.toUpperCase(),
      headers: { ...this.defaultHeaders },
      body: body,
      signal: this.abortSignal,
      referrer: this.delegate.referrer?.href
    };
    this.enctype = enctype;
  }

  get method() {
    return this.fetchOptions.method
  }

  set method(value) {
    const fetchBody = this.isSafe ? this.url.searchParams : this.fetchOptions.body || new FormData();
    const fetchMethod = fetchMethodFromString(value) || FetchMethod.get;

    this.url.search = "";

    const [url, body] = buildResourceAndBody(this.url, fetchMethod, fetchBody, this.enctype);

    this.url = url;
    this.fetchOptions.body = body;
    this.fetchOptions.method = fetchMethod.toUpperCase();
  }

  get headers() {
    return this.fetchOptions.headers
  }

  set headers(value) {
    this.fetchOptions.headers = value;
  }

  get body() {
    if (this.isSafe) {
      return this.url.searchParams
    } else {
      return this.fetchOptions.body
    }
  }

  set body(value) {
    this.fetchOptions.body = value;
  }

  get location() {
    return this.url
  }

  get params() {
    return this.url.searchParams
  }

  get entries() {
    return this.body ? Array.from(this.body.entries()) : []
  }

  cancel() {
    this.abortController.abort();
  }

  async perform() {
    const { fetchOptions } = this;
    this.delegate.prepareRequest(this);
    const event = await this.#allowRequestToBeIntercepted(fetchOptions);
    try {
      this.delegate.requestStarted(this);

      if (event.detail.fetchRequest) {
        this.response = event.detail.fetchRequest.response;
      } else {
        this.response = fetchWithTurboHeaders(this.url.href, fetchOptions);
      }

      const response = await this.response;
      return await this.receive(response)
    } catch (error) {
      if (error.name !== "AbortError") {
        if (this.#willDelegateErrorHandling(error)) {
          this.delegate.requestErrored(this, error);
        }
        throw error
      }
    } finally {
      this.delegate.requestFinished(this);
    }
  }

  async receive(response) {
    const fetchResponse = new FetchResponse(response);
    const event = dispatch("turbo:before-fetch-response", {
      cancelable: true,
      detail: { fetchResponse },
      target: this.target
    });
    if (event.defaultPrevented) {
      this.delegate.requestPreventedHandlingResponse(this, fetchResponse);
    } else if (fetchResponse.succeeded) {
      this.delegate.requestSucceededWithResponse(this, fetchResponse);
    } else {
      this.delegate.requestFailedWithResponse(this, fetchResponse);
    }
    return fetchResponse
  }

  get defaultHeaders() {
    return {
      Accept: "text/html, application/xhtml+xml"
    }
  }

  get isSafe() {
    return isSafe(this.method)
  }

  get abortSignal() {
    return this.abortController.signal
  }

  acceptResponseType(mimeType) {
    this.headers["Accept"] = [mimeType, this.headers["Accept"]].join(", ");
  }

  async #allowRequestToBeIntercepted(fetchOptions) {
    const requestInterception = new Promise((resolve) => (this.#resolveRequestPromise = resolve));
    const event = dispatch("turbo:before-fetch-request", {
      cancelable: true,
      detail: {
        fetchOptions,
        url: this.url,
        resume: this.#resolveRequestPromise
      },
      target: this.target
    });
    this.url = event.detail.url;
    if (event.defaultPrevented) await requestInterception;

    return event
  }

  #willDelegateErrorHandling(error) {
    const event = dispatch("turbo:fetch-request-error", {
      target: this.target,
      cancelable: true,
      detail: { request: this, error: error }
    });

    return !event.defaultPrevented
  }
}

function isSafe(fetchMethod) {
  return fetchMethodFromString(fetchMethod) == FetchMethod.get
}

function buildResourceAndBody(resource, method, requestBody, enctype) {
  const searchParams =
    Array.from(requestBody).length > 0 ? new URLSearchParams(entriesExcludingFiles(requestBody)) : resource.searchParams;

  if (isSafe(method)) {
    return [mergeIntoURLSearchParams(resource, searchParams), null]
  } else if (enctype == FetchEnctype.urlEncoded) {
    return [resource, searchParams]
  } else {
    return [resource, requestBody]
  }
}

function entriesExcludingFiles(requestBody) {
  const entries = [];

  for (const [name, value] of requestBody) {
    if (value instanceof File) continue
    else entries.push([name, value]);
  }

  return entries
}

function mergeIntoURLSearchParams(url, requestBody) {
  const searchParams = new URLSearchParams(entriesExcludingFiles(requestBody));

  url.search = searchParams.toString();

  return url
}

class AppearanceObserver {
  started = false

  constructor(delegate, element) {
    this.delegate = delegate;
    this.element = element;
    this.intersectionObserver = new IntersectionObserver(this.intersect);
  }

  start() {
    if (!this.started) {
      this.started = true;
      this.intersectionObserver.observe(this.element);
    }
  }

  stop() {
    if (this.started) {
      this.started = false;
      this.intersectionObserver.unobserve(this.element);
    }
  }

  intersect = (entries) => {
    const lastEntry = entries.slice(-1)[0];
    if (lastEntry?.isIntersecting) {
      this.delegate.elementAppearedInViewport(this.element);
    }
  }
}

class StreamMessage {
  static contentType = "text/vnd.turbo-stream.html"

  static wrap(message) {
    if (typeof message == "string") {
      return new this(createDocumentFragment(message))
    } else {
      return message
    }
  }

  constructor(fragment) {
    this.fragment = importStreamElements(fragment);
  }
}

function importStreamElements(fragment) {
  for (const element of fragment.querySelectorAll("turbo-stream")) {
    const streamElement = document.importNode(element, true);

    for (const inertScriptElement of streamElement.templateElement.content.querySelectorAll("script")) {
      inertScriptElement.replaceWith(activateScriptElement(inertScriptElement));
    }

    element.replaceWith(streamElement);
  }

  return fragment
}

const PREFETCH_DELAY = 100;

class PrefetchCache {
  #prefetchTimeout = null
  #prefetched = null

  get(url) {
    if (this.#prefetched && this.#prefetched.url === url && this.#prefetched.expire > Date.now()) {
      return this.#prefetched.request
    }
  }

  setLater(url, request, ttl) {
    this.clear();

    this.#prefetchTimeout = setTimeout(() => {
      request.perform();
      this.set(url, request, ttl);
      this.#prefetchTimeout = null;
    }, PREFETCH_DELAY);
  }

  set(url, request, ttl) {
    this.#prefetched = { url, request, expire: new Date(new Date().getTime() + ttl) };
  }

  clear() {
    if (this.#prefetchTimeout) clearTimeout(this.#prefetchTimeout);
    this.#prefetched = null;
  }
}

const cacheTtl = 10 * 1000;
const prefetchCache = new PrefetchCache();

const FormSubmissionState = {
  initialized: "initialized",
  requesting: "requesting",
  waiting: "waiting",
  receiving: "receiving",
  stopping: "stopping",
  stopped: "stopped"
};

class FormSubmission {
  state = FormSubmissionState.initialized

  static confirmMethod(message) {
    return Promise.resolve(confirm(message))
  }

  constructor(delegate, formElement, submitter, mustRedirect = false) {
    const method = getMethod(formElement, submitter);
    const action = getAction(getFormAction(formElement, submitter), method);
    const body = buildFormData(formElement, submitter);
    const enctype = getEnctype(formElement, submitter);

    this.delegate = delegate;
    this.formElement = formElement;
    this.submitter = submitter;
    this.fetchRequest = new FetchRequest(this, method, action, body, formElement, enctype);
    this.mustRedirect = mustRedirect;
  }

  get method() {
    return this.fetchRequest.method
  }

  set method(value) {
    this.fetchRequest.method = value;
  }

  get action() {
    return this.fetchRequest.url.toString()
  }

  set action(value) {
    this.fetchRequest.url = expandURL(value);
  }

  get body() {
    return this.fetchRequest.body
  }

  get enctype() {
    return this.fetchRequest.enctype
  }

  get isSafe() {
    return this.fetchRequest.isSafe
  }

  get location() {
    return this.fetchRequest.url
  }

  // The submission process

  async start() {
    const { initialized, requesting } = FormSubmissionState;
    const confirmationMessage = getAttribute("data-turbo-confirm", this.submitter, this.formElement);

    if (typeof confirmationMessage === "string") {
      const confirmMethod = typeof config.forms.confirm === "function" ?
        config.forms.confirm :
        FormSubmission.confirmMethod;

      const answer = await confirmMethod(confirmationMessage, this.formElement, this.submitter);
      if (!answer) {
        return
      }
    }

    if (this.state == initialized) {
      this.state = requesting;
      return this.fetchRequest.perform()
    }
  }

  stop() {
    const { stopping, stopped } = FormSubmissionState;
    if (this.state != stopping && this.state != stopped) {
      this.state = stopping;
      this.fetchRequest.cancel();
      return true
    }
  }

  // Fetch request delegate

  prepareRequest(request) {
    if (!request.isSafe) {
      const token = getCookieValue(getMetaContent("csrf-param")) || getMetaContent("csrf-token");
      if (token) {
        request.headers["X-CSRF-Token"] = token;
      }
    }

    if (this.requestAcceptsTurboStreamResponse(request)) {
      request.acceptResponseType(StreamMessage.contentType);
    }
  }

  requestStarted(_request) {
    this.state = FormSubmissionState.waiting;
    if (this.submitter) config.forms.submitter.beforeSubmit(this.submitter);
    this.setSubmitsWith();
    markAsBusy(this.formElement);
    dispatch("turbo:submit-start", {
      target: this.formElement,
      detail: { formSubmission: this }
    });
    this.delegate.formSubmissionStarted(this);
  }

  requestPreventedHandlingResponse(request, response) {
    prefetchCache.clear();

    this.result = { success: response.succeeded, fetchResponse: response };
  }

  requestSucceededWithResponse(request, response) {
    if (response.clientError || response.serverError) {
      this.delegate.formSubmissionFailedWithResponse(this, response);
      return
    }

    prefetchCache.clear();

    if (this.requestMustRedirect(request) && responseSucceededWithoutRedirect(response)) {
      const error = new Error("Form responses must redirect to another location");
      this.delegate.formSubmissionErrored(this, error);
    } else {
      this.state = FormSubmissionState.receiving;
      this.result = { success: true, fetchResponse: response };
      this.delegate.formSubmissionSucceededWithResponse(this, response);
    }
  }

  requestFailedWithResponse(request, response) {
    this.result = { success: false, fetchResponse: response };
    this.delegate.formSubmissionFailedWithResponse(this, response);
  }

  requestErrored(request, error) {
    this.result = { success: false, error };
    this.delegate.formSubmissionErrored(this, error);
  }

  requestFinished(_request) {
    this.state = FormSubmissionState.stopped;
    if (this.submitter) config.forms.submitter.afterSubmit(this.submitter);
    this.resetSubmitterText();
    clearBusyState(this.formElement);
    dispatch("turbo:submit-end", {
      target: this.formElement,
      detail: { formSubmission: this, ...this.result }
    });
    this.delegate.formSubmissionFinished(this);
  }

  // Private

  setSubmitsWith() {
    if (!this.submitter || !this.submitsWith) return

    if (this.submitter.matches("button")) {
      this.originalSubmitText = this.submitter.innerHTML;
      this.submitter.innerHTML = this.submitsWith;
    } else if (this.submitter.matches("input")) {
      const input = this.submitter;
      this.originalSubmitText = input.value;
      input.value = this.submitsWith;
    }
  }

  resetSubmitterText() {
    if (!this.submitter || !this.originalSubmitText) return

    if (this.submitter.matches("button")) {
      this.submitter.innerHTML = this.originalSubmitText;
    } else if (this.submitter.matches("input")) {
      const input = this.submitter;
      input.value = this.originalSubmitText;
    }
  }

  requestMustRedirect(request) {
    return !request.isSafe && this.mustRedirect
  }

  requestAcceptsTurboStreamResponse(request) {
    return !request.isSafe || hasAttribute("data-turbo-stream", this.submitter, this.formElement)
  }

  get submitsWith() {
    return this.submitter?.getAttribute("data-turbo-submits-with")
  }
}

function buildFormData(formElement, submitter) {
  const formData = new FormData(formElement);
  const name = submitter?.getAttribute("name");
  const value = submitter?.getAttribute("value");

  if (name) {
    formData.append(name, value || "");
  }

  return formData
}

function getCookieValue(cookieName) {
  if (cookieName != null) {
    const cookies = document.cookie ? document.cookie.split("; ") : [];
    const cookie = cookies.find((cookie) => cookie.startsWith(cookieName));
    if (cookie) {
      const value = cookie.split("=").slice(1).join("=");
      return value ? decodeURIComponent(value) : undefined
    }
  }
}

function responseSucceededWithoutRedirect(response) {
  return response.statusCode == 200 && !response.redirected
}

function getFormAction(formElement, submitter) {
  const formElementAction = typeof formElement.action === "string" ? formElement.action : null;

  if (submitter?.hasAttribute("formaction")) {
    return submitter.getAttribute("formaction") || ""
  } else {
    return formElement.getAttribute("action") || formElementAction || ""
  }
}

function getAction(formAction, fetchMethod) {
  const action = expandURL(formAction);

  if (isSafe(fetchMethod)) {
    action.search = "";
  }

  return action
}

function getMethod(formElement, submitter) {
  const method = submitter?.getAttribute("formmethod") || formElement.getAttribute("method") || "";
  return fetchMethodFromString(method.toLowerCase()) || FetchMethod.get
}

function getEnctype(formElement, submitter) {
  return fetchEnctypeFromString(submitter?.getAttribute("formenctype") || formElement.enctype)
}

class Snapshot {
  constructor(element) {
    this.element = element;
  }

  get activeElement() {
    return this.element.ownerDocument.activeElement
  }

  get children() {
    return [...this.element.children]
  }

  hasAnchor(anchor) {
    return this.getElementForAnchor(anchor) != null
  }

  getElementForAnchor(anchor) {
    return anchor ? this.element.querySelector(`[id='${anchor}'], a[name='${anchor}']`) : null
  }

  get isConnected() {
    return this.element.isConnected
  }

  get firstAutofocusableElement() {
    return queryAutofocusableElement(this.element)
  }

  get permanentElements() {
    return queryPermanentElementsAll(this.element)
  }

  getPermanentElementById(id) {
    return getPermanentElementById(this.element, id)
  }

  getPermanentElementMapForSnapshot(snapshot) {
    const permanentElementMap = {};

    for (const currentPermanentElement of this.permanentElements) {
      const { id } = currentPermanentElement;
      const newPermanentElement = snapshot.getPermanentElementById(id);
      if (newPermanentElement) {
        permanentElementMap[id] = [currentPermanentElement, newPermanentElement];
      }
    }

    return permanentElementMap
  }
}

function getPermanentElementById(node, id) {
  return node.querySelector(`#${id}[data-turbo-permanent]`)
}

function queryPermanentElementsAll(node) {
  return node.querySelectorAll("[id][data-turbo-permanent]")
}

class FormSubmitObserver {
  started = false

  constructor(delegate, eventTarget) {
    this.delegate = delegate;
    this.eventTarget = eventTarget;
  }

  start() {
    if (!this.started) {
      this.eventTarget.addEventListener("submit", this.submitCaptured, true);
      this.started = true;
    }
  }

  stop() {
    if (this.started) {
      this.eventTarget.removeEventListener("submit", this.submitCaptured, true);
      this.started = false;
    }
  }

  submitCaptured = () => {
    this.eventTarget.removeEventListener("submit", this.submitBubbled, false);
    this.eventTarget.addEventListener("submit", this.submitBubbled, false);
  }

  submitBubbled = (event) => {
    if (!event.defaultPrevented) {
      const form = event.target instanceof HTMLFormElement ? event.target : undefined;
      const submitter = event.submitter || undefined;

      if (
        form &&
        submissionDoesNotDismissDialog(form, submitter) &&
        submissionDoesNotTargetIFrame(form, submitter) &&
        this.delegate.willSubmitForm(form, submitter)
      ) {
        event.preventDefault();
        event.stopImmediatePropagation();
        this.delegate.formSubmitted(form, submitter);
      }
    }
  }
}

function submissionDoesNotDismissDialog(form, submitter) {
  const method = submitter?.getAttribute("formmethod") || form.getAttribute("method");

  return method != "dialog"
}

function submissionDoesNotTargetIFrame(form, submitter) {
  const target = submitter?.getAttribute("formtarget") || form.getAttribute("target");

  return doesNotTargetIFrame(target)
}

class View {
  #resolveRenderPromise = (_value) => {}
  #resolveInterceptionPromise = (_value) => {}

  constructor(delegate, element) {
    this.delegate = delegate;
    this.element = element;
  }

  // Scrolling

  scrollToAnchor(anchor) {
    const element = this.snapshot.getElementForAnchor(anchor);
    if (element) {
      this.focusElement(element);
      this.scrollToElement(element);
    } else {
      this.scrollToPosition({ x: 0, y: 0 });
    }
  }

  scrollToAnchorFromLocation(location) {
    this.scrollToAnchor(getAnchor(location));
  }

  scrollToElement(element) {
    element.scrollIntoView();
  }

  focusElement(element) {
    if (element instanceof HTMLElement) {
      if (element.hasAttribute("tabindex")) {
        element.focus();
      } else {
        element.setAttribute("tabindex", "-1");
        element.focus();
        element.removeAttribute("tabindex");
      }
    }
  }

  scrollToPosition({ x, y }) {
    this.scrollRoot.scrollTo(x, y);
  }

  scrollToTop() {
    this.scrollToPosition({ x: 0, y: 0 });
  }

  get scrollRoot() {
    return window
  }

  // Rendering

  async render(renderer) {
    const { isPreview, shouldRender, willRender, newSnapshot: snapshot } = renderer;

    // A workaround to ignore tracked element mismatch reloads when performing
    // a promoted Visit from a frame navigation
    const shouldInvalidate = willRender;

    if (shouldRender) {
      try {
        this.renderPromise = new Promise((resolve) => (this.#resolveRenderPromise = resolve));
        this.renderer = renderer;
        await this.prepareToRenderSnapshot(renderer);

        const renderInterception = new Promise((resolve) => (this.#resolveInterceptionPromise = resolve));
        const options = { resume: this.#resolveInterceptionPromise, render: this.renderer.renderElement, renderMethod: this.renderer.renderMethod };
        const immediateRender = this.delegate.allowsImmediateRender(snapshot, options);
        if (!immediateRender) await renderInterception;

        await this.renderSnapshot(renderer);
        this.delegate.viewRenderedSnapshot(snapshot, isPreview, this.renderer.renderMethod);
        this.delegate.preloadOnLoadLinksForView(this.element);
        this.finishRenderingSnapshot(renderer);
      } finally {
        delete this.renderer;
        this.#resolveRenderPromise(undefined);
        delete this.renderPromise;
      }
    } else if (shouldInvalidate) {
      this.invalidate(renderer.reloadReason);
    }
  }

  invalidate(reason) {
    this.delegate.viewInvalidated(reason);
  }

  async prepareToRenderSnapshot(renderer) {
    this.markAsPreview(renderer.isPreview);
    await renderer.prepareToRender();
  }

  markAsPreview(isPreview) {
    if (isPreview) {
      this.element.setAttribute("data-turbo-preview", "");
    } else {
      this.element.removeAttribute("data-turbo-preview");
    }
  }

  markVisitDirection(direction) {
    this.element.setAttribute("data-turbo-visit-direction", direction);
  }

  unmarkVisitDirection() {
    this.element.removeAttribute("data-turbo-visit-direction");
  }

  async renderSnapshot(renderer) {
    await renderer.render();
  }

  finishRenderingSnapshot(renderer) {
    renderer.finishRendering();
  }
}

class FrameView extends View {
  missing() {
    this.element.innerHTML = `<strong class="turbo-frame-error">Content missing</strong>`;
  }

  get snapshot() {
    return new Snapshot(this.element)
  }
}

class LinkInterceptor {
  constructor(delegate, element) {
    this.delegate = delegate;
    this.element = element;
  }

  start() {
    this.element.addEventListener("click", this.clickBubbled);
    document.addEventListener("turbo:click", this.linkClicked);
    document.addEventListener("turbo:before-visit", this.willVisit);
  }

  stop() {
    this.element.removeEventListener("click", this.clickBubbled);
    document.removeEventListener("turbo:click", this.linkClicked);
    document.removeEventListener("turbo:before-visit", this.willVisit);
  }

  clickBubbled = (event) => {
    if (this.clickEventIsSignificant(event)) {
      this.clickEvent = event;
    } else {
      delete this.clickEvent;
    }
  }

  linkClicked = (event) => {
    if (this.clickEvent && this.clickEventIsSignificant(event)) {
      if (this.delegate.shouldInterceptLinkClick(event.target, event.detail.url, event.detail.originalEvent)) {
        this.clickEvent.preventDefault();
        event.preventDefault();
        this.delegate.linkClickIntercepted(event.target, event.detail.url, event.detail.originalEvent);
      }
    }
    delete this.clickEvent;
  }

  willVisit = (_event) => {
    delete this.clickEvent;
  }

  clickEventIsSignificant(event) {
    const target = event.composed ? event.target?.parentElement : event.target;
    const element = findLinkFromClickTarget(target) || target;

    return element instanceof Element && element.closest("turbo-frame, html") == this.element
  }
}

class LinkClickObserver {
  started = false

  constructor(delegate, eventTarget) {
    this.delegate = delegate;
    this.eventTarget = eventTarget;
  }

  start() {
    if (!this.started) {
      this.eventTarget.addEventListener("click", this.clickCaptured, true);
      this.started = true;
    }
  }

  stop() {
    if (this.started) {
      this.eventTarget.removeEventListener("click", this.clickCaptured, true);
      this.started = false;
    }
  }

  clickCaptured = () => {
    this.eventTarget.removeEventListener("click", this.clickBubbled, false);
    this.eventTarget.addEventListener("click", this.clickBubbled, false);
  }

  clickBubbled = (event) => {
    if (event instanceof MouseEvent && this.clickEventIsSignificant(event)) {
      const target = (event.composedPath && event.composedPath()[0]) || event.target;
      const link = findLinkFromClickTarget(target);
      if (link && doesNotTargetIFrame(link.target)) {
        const location = getLocationForLink(link);
        if (this.delegate.willFollowLinkToLocation(link, location, event)) {
          event.preventDefault();
          this.delegate.followedLinkToLocation(link, location);
        }
      }
    }
  }

  clickEventIsSignificant(event) {
    return !(
      (event.target && event.target.isContentEditable) ||
      event.defaultPrevented ||
      event.which > 1 ||
      event.altKey ||
      event.ctrlKey ||
      event.metaKey ||
      event.shiftKey
    )
  }
}

class FormLinkClickObserver {
  constructor(delegate, element) {
    this.delegate = delegate;
    this.linkInterceptor = new LinkClickObserver(this, element);
  }

  start() {
    this.linkInterceptor.start();
  }

  stop() {
    this.linkInterceptor.stop();
  }

  // Link hover observer delegate

  canPrefetchRequestToLocation(link, location) {
    return false
  }

  prefetchAndCacheRequestToLocation(link, location) {
    return
  }

  // Link click observer delegate

  willFollowLinkToLocation(link, location, originalEvent) {
    return (
      this.delegate.willSubmitFormLinkToLocation(link, location, originalEvent) &&
      (link.hasAttribute("data-turbo-method") || link.hasAttribute("data-turbo-stream"))
    )
  }

  followedLinkToLocation(link, location) {
    const form = document.createElement("form");

    const type = "hidden";
    for (const [name, value] of location.searchParams) {
      form.append(Object.assign(document.createElement("input"), { type, name, value }));
    }

    const action = Object.assign(location, { search: "" });
    form.setAttribute("data-turbo", "true");
    form.setAttribute("action", action.href);
    form.setAttribute("hidden", "");

    const method = link.getAttribute("data-turbo-method");
    if (method) form.setAttribute("method", method);

    const turboFrame = link.getAttribute("data-turbo-frame");
    if (turboFrame) form.setAttribute("data-turbo-frame", turboFrame);

    const turboAction = getVisitAction(link);
    if (turboAction) form.setAttribute("data-turbo-action", turboAction);

    const turboConfirm = link.getAttribute("data-turbo-confirm");
    if (turboConfirm) form.setAttribute("data-turbo-confirm", turboConfirm);

    const turboStream = link.hasAttribute("data-turbo-stream");
    if (turboStream) form.setAttribute("data-turbo-stream", "");

    this.delegate.submittedFormLinkToLocation(link, location, form);

    document.body.appendChild(form);
    form.addEventListener("turbo:submit-end", () => form.remove(), { once: true });
    requestAnimationFrame(() => form.requestSubmit());
  }
}

class Bardo {
  static async preservingPermanentElements(delegate, permanentElementMap, callback) {
    const bardo = new this(delegate, permanentElementMap);
    bardo.enter();
    await callback();
    bardo.leave();
  }

  constructor(delegate, permanentElementMap) {
    this.delegate = delegate;
    this.permanentElementMap = permanentElementMap;
  }

  enter() {
    for (const id in this.permanentElementMap) {
      const [currentPermanentElement, newPermanentElement] = this.permanentElementMap[id];
      this.delegate.enteringBardo(currentPermanentElement, newPermanentElement);
      this.replaceNewPermanentElementWithPlaceholder(newPermanentElement);
    }
  }

  leave() {
    for (const id in this.permanentElementMap) {
      const [currentPermanentElement] = this.permanentElementMap[id];
      this.replaceCurrentPermanentElementWithClone(currentPermanentElement);
      this.replacePlaceholderWithPermanentElement(currentPermanentElement);
      this.delegate.leavingBardo(currentPermanentElement);
    }
  }

  replaceNewPermanentElementWithPlaceholder(permanentElement) {
    const placeholder = createPlaceholderForPermanentElement(permanentElement);
    permanentElement.replaceWith(placeholder);
  }

  replaceCurrentPermanentElementWithClone(permanentElement) {
    const clone = permanentElement.cloneNode(true);
    permanentElement.replaceWith(clone);
  }

  replacePlaceholderWithPermanentElement(permanentElement) {
    const placeholder = this.getPlaceholderById(permanentElement.id);
    placeholder?.replaceWith(permanentElement);
  }

  getPlaceholderById(id) {
    return this.placeholders.find((element) => element.content == id)
  }

  get placeholders() {
    return [...document.querySelectorAll("meta[name=turbo-permanent-placeholder][content]")]
  }
}

function createPlaceholderForPermanentElement(permanentElement) {
  const element = document.createElement("meta");
  element.setAttribute("name", "turbo-permanent-placeholder");
  element.setAttribute("content", permanentElement.id);
  return element
}

class Renderer {
  #activeElement = null

  static renderElement(currentElement, newElement) {
    // Abstract method
  }

  constructor(currentSnapshot, newSnapshot, isPreview, willRender = true) {
    this.currentSnapshot = currentSnapshot;
    this.newSnapshot = newSnapshot;
    this.isPreview = isPreview;
    this.willRender = willRender;
    this.renderElement = this.constructor.renderElement;
    this.promise = new Promise((resolve, reject) => (this.resolvingFunctions = { resolve, reject }));
  }

  get shouldRender() {
    return true
  }

  get shouldAutofocus() {
    return true
  }

  get reloadReason() {
    return
  }

  prepareToRender() {
    return
  }

  render() {
    // Abstract method
  }

  finishRendering() {
    if (this.resolvingFunctions) {
      this.resolvingFunctions.resolve();
      delete this.resolvingFunctions;
    }
  }

  async preservingPermanentElements(callback) {
    await Bardo.preservingPermanentElements(this, this.permanentElementMap, callback);
  }

  focusFirstAutofocusableElement() {
    if (this.shouldAutofocus) {
      const element = this.connectedSnapshot.firstAutofocusableElement;
      if (element) {
        element.focus();
      }
    }
  }

  // Bardo delegate

  enteringBardo(currentPermanentElement) {
    if (this.#activeElement) return

    if (currentPermanentElement.contains(this.currentSnapshot.activeElement)) {
      this.#activeElement = this.currentSnapshot.activeElement;
    }
  }

  leavingBardo(currentPermanentElement) {
    if (currentPermanentElement.contains(this.#activeElement) && this.#activeElement instanceof HTMLElement) {
      this.#activeElement.focus();

      this.#activeElement = null;
    }
  }

  get connectedSnapshot() {
    return this.newSnapshot.isConnected ? this.newSnapshot : this.currentSnapshot
  }

  get currentElement() {
    return this.currentSnapshot.element
  }

  get newElement() {
    return this.newSnapshot.element
  }

  get permanentElementMap() {
    return this.currentSnapshot.getPermanentElementMapForSnapshot(this.newSnapshot)
  }

  get renderMethod() {
    return "replace"
  }
}

class FrameRenderer extends Renderer {
  static renderElement(currentElement, newElement) {
    const destinationRange = document.createRange();
    destinationRange.selectNodeContents(currentElement);
    destinationRange.deleteContents();

    const frameElement = newElement;
    const sourceRange = frameElement.ownerDocument?.createRange();
    if (sourceRange) {
      sourceRange.selectNodeContents(frameElement);
      currentElement.appendChild(sourceRange.extractContents());
    }
  }

  constructor(delegate, currentSnapshot, newSnapshot, renderElement, isPreview, willRender = true) {
    super(currentSnapshot, newSnapshot, renderElement, isPreview, willRender);
    this.delegate = delegate;
  }

  get shouldRender() {
    return true
  }

  async render() {
    await nextRepaint();
    this.preservingPermanentElements(() => {
      this.loadFrameElement();
    });
    this.scrollFrameIntoView();
    await nextRepaint();
    this.focusFirstAutofocusableElement();
    await nextRepaint();
    this.activateScriptElements();
  }

  loadFrameElement() {
    this.delegate.willRenderFrame(this.currentElement, this.newElement);
    this.renderElement(this.currentElement, this.newElement);
  }

  scrollFrameIntoView() {
    if (this.currentElement.autoscroll || this.newElement.autoscroll) {
      const element = this.currentElement.firstElementChild;
      const block = readScrollLogicalPosition(this.currentElement.getAttribute("data-autoscroll-block"), "end");
      const behavior = readScrollBehavior(this.currentElement.getAttribute("data-autoscroll-behavior"), "auto");

      if (element) {
        element.scrollIntoView({ block, behavior });
        return true
      }
    }
    return false
  }

  activateScriptElements() {
    for (const inertScriptElement of this.newScriptElements) {
      const activatedScriptElement = activateScriptElement(inertScriptElement);
      inertScriptElement.replaceWith(activatedScriptElement);
    }
  }

  get newScriptElements() {
    return this.currentElement.querySelectorAll("script")
  }
}

function readScrollLogicalPosition(value, defaultValue) {
  if (value == "end" || value == "start" || value == "center" || value == "nearest") {
    return value
  } else {
    return defaultValue
  }
}

function readScrollBehavior(value, defaultValue) {
  if (value == "auto" || value == "smooth") {
    return value
  } else {
    return defaultValue
  }
}

/**
 * @typedef {object} ConfigHead
 *
 * @property {'merge' | 'append' | 'morph' | 'none'} [style]
 * @property {boolean} [block]
 * @property {boolean} [ignore]
 * @property {function(Element): boolean} [shouldPreserve]
 * @property {function(Element): boolean} [shouldReAppend]
 * @property {function(Element): boolean} [shouldRemove]
 * @property {function(Element, {added: Node[], kept: Element[], removed: Element[]}): void} [afterHeadMorphed]
 */

/**
 * @typedef {object} ConfigCallbacks
 *
 * @property {function(Node): boolean} [beforeNodeAdded]
 * @property {function(Node): void} [afterNodeAdded]
 * @property {function(Element, Node): boolean} [beforeNodeMorphed]
 * @property {function(Element, Node): void} [afterNodeMorphed]
 * @property {function(Element): boolean} [beforeNodeRemoved]
 * @property {function(Element): void} [afterNodeRemoved]
 * @property {function(string, Element, "update" | "remove"): boolean} [beforeAttributeUpdated]
 */

/**
 * @typedef {object} Config
 *
 * @property {'outerHTML' | 'innerHTML'} [morphStyle]
 * @property {boolean} [ignoreActive]
 * @property {boolean} [ignoreActiveValue]
 * @property {boolean} [restoreFocus]
 * @property {ConfigCallbacks} [callbacks]
 * @property {ConfigHead} [head]
 */

/**
 * @typedef {function} NoOp
 *
 * @returns {void}
 */

/**
 * @typedef {object} ConfigHeadInternal
 *
 * @property {'merge' | 'append' | 'morph' | 'none'} style
 * @property {boolean} [block]
 * @property {boolean} [ignore]
 * @property {(function(Element): boolean) | NoOp} shouldPreserve
 * @property {(function(Element): boolean) | NoOp} shouldReAppend
 * @property {(function(Element): boolean) | NoOp} shouldRemove
 * @property {(function(Element, {added: Node[], kept: Element[], removed: Element[]}): void) | NoOp} afterHeadMorphed
 */

/**
 * @typedef {object} ConfigCallbacksInternal
 *
 * @property {(function(Node): boolean) | NoOp} beforeNodeAdded
 * @property {(function(Node): void) | NoOp} afterNodeAdded
 * @property {(function(Node, Node): boolean) | NoOp} beforeNodeMorphed
 * @property {(function(Node, Node): void) | NoOp} afterNodeMorphed
 * @property {(function(Node): boolean) | NoOp} beforeNodeRemoved
 * @property {(function(Node): void) | NoOp} afterNodeRemoved
 * @property {(function(string, Element, "update" | "remove"): boolean) | NoOp} beforeAttributeUpdated
 */

/**
 * @typedef {object} ConfigInternal
 *
 * @property {'outerHTML' | 'innerHTML'} morphStyle
 * @property {boolean} [ignoreActive]
 * @property {boolean} [ignoreActiveValue]
 * @property {boolean} [restoreFocus]
 * @property {ConfigCallbacksInternal} callbacks
 * @property {ConfigHeadInternal} head
 */

/**
 * @typedef {Object} IdSets
 * @property {Set<string>} persistentIds
 * @property {Map<Node, Set<string>>} idMap
 */

/**
 * @typedef {Function} Morph
 *
 * @param {Element | Document} oldNode
 * @param {Element | Node | HTMLCollection | Node[] | string | null} newContent
 * @param {Config} [config]
 * @returns {undefined | Node[]}
 */

// base IIFE to define idiomorph
/**
 *
 * @type {{defaults: ConfigInternal, morph: Morph}}
 */
var Idiomorph = (function () {

  /**
   * @typedef {object} MorphContext
   *
   * @property {Element} target
   * @property {Element} newContent
   * @property {ConfigInternal} config
   * @property {ConfigInternal['morphStyle']} morphStyle
   * @property {ConfigInternal['ignoreActive']} ignoreActive
   * @property {ConfigInternal['ignoreActiveValue']} ignoreActiveValue
   * @property {ConfigInternal['restoreFocus']} restoreFocus
   * @property {Map<Node, Set<string>>} idMap
   * @property {Set<string>} persistentIds
   * @property {ConfigInternal['callbacks']} callbacks
   * @property {ConfigInternal['head']} head
   * @property {HTMLDivElement} pantry
   * @property {Element[]} activeElementAndParents
   */

  //=============================================================================
  // AND NOW IT BEGINS...
  //=============================================================================

  const noOp = () => {};
  /**
   * Default configuration values, updatable by users now
   * @type {ConfigInternal}
   */
  const defaults = {
    morphStyle: "outerHTML",
    callbacks: {
      beforeNodeAdded: noOp,
      afterNodeAdded: noOp,
      beforeNodeMorphed: noOp,
      afterNodeMorphed: noOp,
      beforeNodeRemoved: noOp,
      afterNodeRemoved: noOp,
      beforeAttributeUpdated: noOp,
    },
    head: {
      style: "merge",
      shouldPreserve: (elt) => elt.getAttribute("im-preserve") === "true",
      shouldReAppend: (elt) => elt.getAttribute("im-re-append") === "true",
      shouldRemove: noOp,
      afterHeadMorphed: noOp,
    },
    restoreFocus: true,
  };

  /**
   * Core idiomorph function for morphing one DOM tree to another
   *
   * @param {Element | Document} oldNode
   * @param {Element | Node | HTMLCollection | Node[] | string | null} newContent
   * @param {Config} [config]
   * @returns {Promise<Node[]> | Node[]}
   */
  function morph(oldNode, newContent, config = {}) {
    oldNode = normalizeElement(oldNode);
    const newNode = normalizeParent(newContent);
    const ctx = createMorphContext(oldNode, newNode, config);

    const morphedNodes = saveAndRestoreFocus(ctx, () => {
      return withHeadBlocking(
        ctx,
        oldNode,
        newNode,
        /** @param {MorphContext} ctx */ (ctx) => {
          if (ctx.morphStyle === "innerHTML") {
            morphChildren(ctx, oldNode, newNode);
            return Array.from(oldNode.childNodes);
          } else {
            return morphOuterHTML(ctx, oldNode, newNode);
          }
        },
      );
    });

    ctx.pantry.remove();
    return morphedNodes;
  }

  /**
   * Morph just the outerHTML of the oldNode to the newContent
   * We have to be careful because the oldNode could have siblings which need to be untouched
   * @param {MorphContext} ctx
   * @param {Element} oldNode
   * @param {Element} newNode
   * @returns {Node[]}
   */
  function morphOuterHTML(ctx, oldNode, newNode) {
    const oldParent = normalizeParent(oldNode);
    morphChildren(
      ctx,
      oldParent,
      newNode,
      // these two optional params are the secret sauce
      oldNode, // start point for iteration
      oldNode.nextSibling, // end point for iteration
    );
    // this is safe even with siblings, because normalizeParent returns a SlicedParentNode if needed.
    return Array.from(oldParent.childNodes);
  }

  /**
   * @param {MorphContext} ctx
   * @param {Function} fn
   * @returns {Promise<Node[]> | Node[]}
   */
  function saveAndRestoreFocus(ctx, fn) {
    if (!ctx.config.restoreFocus) return fn();
    let activeElement =
      /** @type {HTMLInputElement|HTMLTextAreaElement|null} */ (
        document.activeElement
      );

    // don't bother if the active element is not an input or textarea
    if (
      !(
        activeElement instanceof HTMLInputElement ||
        activeElement instanceof HTMLTextAreaElement
      )
    ) {
      return fn();
    }

    const { id: activeElementId, selectionStart, selectionEnd } = activeElement;

    const results = fn();

    if (
      activeElementId &&
      activeElementId !== document.activeElement?.getAttribute("id")
    ) {
      activeElement = ctx.target.querySelector(`[id="${activeElementId}"]`);
      activeElement?.focus();
    }
    if (activeElement && !activeElement.selectionEnd && selectionEnd) {
      activeElement.setSelectionRange(selectionStart, selectionEnd);
    }

    return results;
  }

  const morphChildren = (function () {
    /**
     * This is the core algorithm for matching up children.  The idea is to use id sets to try to match up
     * nodes as faithfully as possible.  We greedily match, which allows us to keep the algorithm fast, but
     * by using id sets, we are able to better match up with content deeper in the DOM.
     *
     * Basic algorithm:
     * - for each node in the new content:
     *   - search self and siblings for an id set match, falling back to a soft match
     *   - if match found
     *     - remove any nodes up to the match:
     *       - pantry persistent nodes
     *       - delete the rest
     *     - morph the match
     *   - elsif no match found, and node is persistent
     *     - find its match by querying the old root (future) and pantry (past)
     *     - move it and its children here
     *     - morph it
     *   - else
     *     - create a new node from scratch as a last result
     *
     * @param {MorphContext} ctx the merge context
     * @param {Element} oldParent the old content that we are merging the new content into
     * @param {Element} newParent the parent element of the new content
     * @param {Node|null} [insertionPoint] the point in the DOM we start morphing at (defaults to first child)
     * @param {Node|null} [endPoint] the point in the DOM we stop morphing at (defaults to after last child)
     */
    function morphChildren(
      ctx,
      oldParent,
      newParent,
      insertionPoint = null,
      endPoint = null,
    ) {
      // normalize
      if (
        oldParent instanceof HTMLTemplateElement &&
        newParent instanceof HTMLTemplateElement
      ) {
        // @ts-ignore we can pretend the DocumentFragment is an Element
        oldParent = oldParent.content;
        // @ts-ignore ditto
        newParent = newParent.content;
      }
      insertionPoint ||= oldParent.firstChild;

      // run through all the new content
      for (const newChild of newParent.childNodes) {
        // once we reach the end of the old parent content skip to the end and insert the rest
        if (insertionPoint && insertionPoint != endPoint) {
          const bestMatch = findBestMatch(
            ctx,
            newChild,
            insertionPoint,
            endPoint,
          );
          if (bestMatch) {
            // if the node to morph is not at the insertion point then remove/move up to it
            if (bestMatch !== insertionPoint) {
              removeNodesBetween(ctx, insertionPoint, bestMatch);
            }
            morphNode(bestMatch, newChild, ctx);
            insertionPoint = bestMatch.nextSibling;
            continue;
          }
        }

        // if the matching node is elsewhere in the original content
        if (newChild instanceof Element) {
          // we can pretend the id is non-null because the next `.has` line will reject it if not
          const newChildId = /** @type {String} */ (
            newChild.getAttribute("id")
          );
          if (ctx.persistentIds.has(newChildId)) {
            // move it and all its children here and morph
            const movedChild = moveBeforeById(
              oldParent,
              newChildId,
              insertionPoint,
              ctx,
            );
            morphNode(movedChild, newChild, ctx);
            insertionPoint = movedChild.nextSibling;
            continue;
          }
        }

        // last resort: insert the new node from scratch
        const insertedNode = createNode(
          oldParent,
          newChild,
          insertionPoint,
          ctx,
        );
        // could be null if beforeNodeAdded prevented insertion
        if (insertedNode) {
          insertionPoint = insertedNode.nextSibling;
        }
      }

      // remove any remaining old nodes that didn't match up with new content
      while (insertionPoint && insertionPoint != endPoint) {
        const tempNode = insertionPoint;
        insertionPoint = insertionPoint.nextSibling;
        removeNode(ctx, tempNode);
      }
    }

    /**
     * This performs the action of inserting a new node while handling situations where the node contains
     * elements with persistent ids and possible state info we can still preserve by moving in and then morphing
     *
     * @param {Element} oldParent
     * @param {Node} newChild
     * @param {Node|null} insertionPoint
     * @param {MorphContext} ctx
     * @returns {Node|null}
     */
    function createNode(oldParent, newChild, insertionPoint, ctx) {
      if (ctx.callbacks.beforeNodeAdded(newChild) === false) return null;
      if (ctx.idMap.has(newChild)) {
        // node has children with ids with possible state so create a dummy elt of same type and apply full morph algorithm
        const newEmptyChild = document.createElement(
          /** @type {Element} */ (newChild).tagName,
        );
        oldParent.insertBefore(newEmptyChild, insertionPoint);
        morphNode(newEmptyChild, newChild, ctx);
        ctx.callbacks.afterNodeAdded(newEmptyChild);
        return newEmptyChild;
      } else {
        // optimisation: no id state to preserve so we can just insert a clone of the newChild and its descendants
        const newClonedChild = document.importNode(newChild, true); // importNode to not mutate newParent
        oldParent.insertBefore(newClonedChild, insertionPoint);
        ctx.callbacks.afterNodeAdded(newClonedChild);
        return newClonedChild;
      }
    }

    //=============================================================================
    // Matching Functions
    //=============================================================================
    const findBestMatch = (function () {
      /**
       * Scans forward from the startPoint to the endPoint looking for a match
       * for the node. It looks for an id set match first, then a soft match.
       * We abort softmatching if we find two future soft matches, to reduce churn.
       * @param {Node} node
       * @param {MorphContext} ctx
       * @param {Node | null} startPoint
       * @param {Node | null} endPoint
       * @returns {Node | null}
       */
      function findBestMatch(ctx, node, startPoint, endPoint) {
        let softMatch = null;
        let nextSibling = node.nextSibling;
        let siblingSoftMatchCount = 0;

        let cursor = startPoint;
        while (cursor && cursor != endPoint) {
          // soft matching is a prerequisite for id set matching
          if (isSoftMatch(cursor, node)) {
            if (isIdSetMatch(ctx, cursor, node)) {
              return cursor; // found an id set match, we're done!
            }

            // we haven't yet saved a soft match fallback
            if (softMatch === null) {
              // the current soft match will hard match something else in the future, leave it
              if (!ctx.idMap.has(cursor)) {
                // save this as the fallback if we get through the loop without finding a hard match
                softMatch = cursor;
              }
            }
          }
          if (
            softMatch === null &&
            nextSibling &&
            isSoftMatch(cursor, nextSibling)
          ) {
            // The next new node has a soft match with this node, so
            // increment the count of future soft matches
            siblingSoftMatchCount++;
            nextSibling = nextSibling.nextSibling;

            // If there are two future soft matches, block soft matching for this node to allow
            // future siblings to soft match. This is to reduce churn in the DOM when an element
            // is prepended.
            if (siblingSoftMatchCount >= 2) {
              softMatch = undefined;
            }
          }

          // if the current node contains active element, stop looking for better future matches,
          // because if one is found, this node will be moved to the pantry, reparenting it and thus losing focus
          // @ts-ignore pretend cursor is Element rather than Node, we're just testing for array inclusion
          if (ctx.activeElementAndParents.includes(cursor)) break;

          cursor = cursor.nextSibling;
        }

        return softMatch || null;
      }

      /**
       *
       * @param {MorphContext} ctx
       * @param {Node} oldNode
       * @param {Node} newNode
       * @returns {boolean}
       */
      function isIdSetMatch(ctx, oldNode, newNode) {
        let oldSet = ctx.idMap.get(oldNode);
        let newSet = ctx.idMap.get(newNode);

        if (!newSet || !oldSet) return false;

        for (const id of oldSet) {
          // a potential match is an id in the new and old nodes that
          // has not already been merged into the DOM
          // But the newNode content we call this on has not been
          // merged yet and we don't allow duplicate IDs so it is simple
          if (newSet.has(id)) {
            return true;
          }
        }
        return false;
      }

      /**
       *
       * @param {Node} oldNode
       * @param {Node} newNode
       * @returns {boolean}
       */
      function isSoftMatch(oldNode, newNode) {
        // ok to cast: if one is not element, `id` and `tagName` will be undefined and we'll just compare that.
        const oldElt = /** @type {Element} */ (oldNode);
        const newElt = /** @type {Element} */ (newNode);

        return (
          oldElt.nodeType === newElt.nodeType &&
          oldElt.tagName === newElt.tagName &&
          // If oldElt has an `id` with possible state and it doesn't match newElt.id then avoid morphing.
          // We'll still match an anonymous node with an IDed newElt, though, because if it got this far,
          // its not persistent, and new nodes can't have any hidden state.
          // We can't use .id because of form input shadowing, and we can't count on .getAttribute's presence because it could be a document-fragment
          (!oldElt.getAttribute?.("id") ||
            oldElt.getAttribute?.("id") === newElt.getAttribute?.("id"))
        );
      }

      return findBestMatch;
    })();

    //=============================================================================
    // DOM Manipulation Functions
    //=============================================================================

    /**
     * Gets rid of an unwanted DOM node; strategy depends on nature of its reuse:
     * - Persistent nodes will be moved to the pantry for later reuse
     * - Other nodes will have their hooks called, and then are removed
     * @param {MorphContext} ctx
     * @param {Node} node
     */
    function removeNode(ctx, node) {
      // are we going to id set match this later?
      if (ctx.idMap.has(node)) {
        // skip callbacks and move to pantry
        moveBefore(ctx.pantry, node, null);
      } else {
        // remove for realsies
        if (ctx.callbacks.beforeNodeRemoved(node) === false) return;
        node.parentNode?.removeChild(node);
        ctx.callbacks.afterNodeRemoved(node);
      }
    }

    /**
     * Remove nodes between the start and end nodes
     * @param {MorphContext} ctx
     * @param {Node} startInclusive
     * @param {Node} endExclusive
     * @returns {Node|null}
     */
    function removeNodesBetween(ctx, startInclusive, endExclusive) {
      /** @type {Node | null} */
      let cursor = startInclusive;
      // remove nodes until the endExclusive node
      while (cursor && cursor !== endExclusive) {
        let tempNode = /** @type {Node} */ (cursor);
        cursor = cursor.nextSibling;
        removeNode(ctx, tempNode);
      }
      return cursor;
    }

    /**
     * Search for an element by id within the document and pantry, and move it using moveBefore.
     *
     * @param {Element} parentNode - The parent node to which the element will be moved.
     * @param {string} id - The ID of the element to be moved.
     * @param {Node | null} after - The reference node to insert the element before.
     *                              If `null`, the element is appended as the last child.
     * @param {MorphContext} ctx
     * @returns {Element} The found element
     */
    function moveBeforeById(parentNode, id, after, ctx) {
      const target =
        /** @type {Element} - will always be found */
        (
          // ctx.target.id unsafe because of form input shadowing
          // ctx.target could be a document fragment which doesn't have `getAttribute`
          (ctx.target.getAttribute?.("id") === id && ctx.target) ||
            ctx.target.querySelector(`[id="${id}"]`) ||
            ctx.pantry.querySelector(`[id="${id}"]`)
        );
      removeElementFromAncestorsIdMaps(target, ctx);
      moveBefore(parentNode, target, after);
      return target;
    }

    /**
     * Removes an element from its ancestors' id maps. This is needed when an element is moved from the
     * "future" via `moveBeforeId`. Otherwise, its erstwhile ancestors could be mistakenly moved to the
     * pantry rather than being deleted, preventing their removal hooks from being called.
     *
     * @param {Element} element - element to remove from its ancestors' id maps
     * @param {MorphContext} ctx
     */
    function removeElementFromAncestorsIdMaps(element, ctx) {
      // we know id is non-null String, because this function is only called on elements with ids
      const id = /** @type {String} */ (element.getAttribute("id"));
      /** @ts-ignore - safe to loop in this way **/
      while ((element = element.parentNode)) {
        let idSet = ctx.idMap.get(element);
        if (idSet) {
          idSet.delete(id);
          if (!idSet.size) {
            ctx.idMap.delete(element);
          }
        }
      }
    }

    /**
     * Moves an element before another element within the same parent.
     * Uses the proposed `moveBefore` API if available (and working), otherwise falls back to `insertBefore`.
     * This is essentialy a forward-compat wrapper.
     *
     * @param {Element} parentNode - The parent node containing the after element.
     * @param {Node} element - The element to be moved.
     * @param {Node | null} after - The reference node to insert `element` before.
     *                              If `null`, `element` is appended as the last child.
     */
    function moveBefore(parentNode, element, after) {
      // @ts-ignore - use proposed moveBefore feature
      if (parentNode.moveBefore) {
        try {
          // @ts-ignore - use proposed moveBefore feature
          parentNode.moveBefore(element, after);
        } catch (e) {
          // fall back to insertBefore as some browsers may fail on moveBefore when trying to move Dom disconnected nodes to pantry
          parentNode.insertBefore(element, after);
        }
      } else {
        parentNode.insertBefore(element, after);
      }
    }

    return morphChildren;
  })();

  //=============================================================================
  // Single Node Morphing Code
  //=============================================================================
  const morphNode = (function () {
    /**
     * @param {Node} oldNode root node to merge content into
     * @param {Node} newContent new content to merge
     * @param {MorphContext} ctx the merge context
     * @returns {Node | null} the element that ended up in the DOM
     */
    function morphNode(oldNode, newContent, ctx) {
      if (ctx.ignoreActive && oldNode === document.activeElement) {
        // don't morph focused element
        return null;
      }

      if (ctx.callbacks.beforeNodeMorphed(oldNode, newContent) === false) {
        return oldNode;
      }

      if (oldNode instanceof HTMLHeadElement && ctx.head.ignore) ; else if (
        oldNode instanceof HTMLHeadElement &&
        ctx.head.style !== "morph"
      ) {
        // ok to cast: if newContent wasn't also a <head>, it would've got caught in the `!isSoftMatch` branch above
        handleHeadElement(
          oldNode,
          /** @type {HTMLHeadElement} */ (newContent),
          ctx,
        );
      } else {
        morphAttributes(oldNode, newContent, ctx);
        if (!ignoreValueOfActiveElement(oldNode, ctx)) {
          // @ts-ignore newContent can be a node here because .firstChild will be null
          morphChildren(ctx, oldNode, newContent);
        }
      }
      ctx.callbacks.afterNodeMorphed(oldNode, newContent);
      return oldNode;
    }

    /**
     * syncs the oldNode to the newNode, copying over all attributes and
     * inner element state from the newNode to the oldNode
     *
     * @param {Node} oldNode the node to copy attributes & state to
     * @param {Node} newNode the node to copy attributes & state from
     * @param {MorphContext} ctx the merge context
     */
    function morphAttributes(oldNode, newNode, ctx) {
      let type = newNode.nodeType;

      // if is an element type, sync the attributes from the
      // new node into the new node
      if (type === 1 /* element type */) {
        const oldElt = /** @type {Element} */ (oldNode);
        const newElt = /** @type {Element} */ (newNode);

        const oldAttributes = oldElt.attributes;
        const newAttributes = newElt.attributes;
        for (const newAttribute of newAttributes) {
          if (ignoreAttribute(newAttribute.name, oldElt, "update", ctx)) {
            continue;
          }
          if (oldElt.getAttribute(newAttribute.name) !== newAttribute.value) {
            oldElt.setAttribute(newAttribute.name, newAttribute.value);
          }
        }
        // iterate backwards to avoid skipping over items when a delete occurs
        for (let i = oldAttributes.length - 1; 0 <= i; i--) {
          const oldAttribute = oldAttributes[i];

          // toAttributes is a live NamedNodeMap, so iteration+mutation is unsafe
          // e.g. custom element attribute callbacks can remove other attributes
          if (!oldAttribute) continue;

          if (!newElt.hasAttribute(oldAttribute.name)) {
            if (ignoreAttribute(oldAttribute.name, oldElt, "remove", ctx)) {
              continue;
            }
            oldElt.removeAttribute(oldAttribute.name);
          }
        }

        if (!ignoreValueOfActiveElement(oldElt, ctx)) {
          syncInputValue(oldElt, newElt, ctx);
        }
      }

      // sync text nodes
      if (type === 8 /* comment */ || type === 3 /* text */) {
        if (oldNode.nodeValue !== newNode.nodeValue) {
          oldNode.nodeValue = newNode.nodeValue;
        }
      }
    }

    /**
     * NB: many bothans died to bring us information:
     *
     *  https://github.com/patrick-steele-idem/morphdom/blob/master/src/specialElHandlers.js
     *  https://github.com/choojs/nanomorph/blob/master/lib/morph.jsL113
     *
     * @param {Element} oldElement the element to sync the input value to
     * @param {Element} newElement the element to sync the input value from
     * @param {MorphContext} ctx the merge context
     */
    function syncInputValue(oldElement, newElement, ctx) {
      if (
        oldElement instanceof HTMLInputElement &&
        newElement instanceof HTMLInputElement &&
        newElement.type !== "file"
      ) {
        let newValue = newElement.value;
        let oldValue = oldElement.value;

        // sync boolean attributes
        syncBooleanAttribute(oldElement, newElement, "checked", ctx);
        syncBooleanAttribute(oldElement, newElement, "disabled", ctx);

        if (!newElement.hasAttribute("value")) {
          if (!ignoreAttribute("value", oldElement, "remove", ctx)) {
            oldElement.value = "";
            oldElement.removeAttribute("value");
          }
        } else if (oldValue !== newValue) {
          if (!ignoreAttribute("value", oldElement, "update", ctx)) {
            oldElement.setAttribute("value", newValue);
            oldElement.value = newValue;
          }
        }
        // TODO: QUESTION(1cg): this used to only check `newElement` unlike the other branches -- why?
        // did I break something?
      } else if (
        oldElement instanceof HTMLOptionElement &&
        newElement instanceof HTMLOptionElement
      ) {
        syncBooleanAttribute(oldElement, newElement, "selected", ctx);
      } else if (
        oldElement instanceof HTMLTextAreaElement &&
        newElement instanceof HTMLTextAreaElement
      ) {
        let newValue = newElement.value;
        let oldValue = oldElement.value;
        if (ignoreAttribute("value", oldElement, "update", ctx)) {
          return;
        }
        if (newValue !== oldValue) {
          oldElement.value = newValue;
        }
        if (
          oldElement.firstChild &&
          oldElement.firstChild.nodeValue !== newValue
        ) {
          oldElement.firstChild.nodeValue = newValue;
        }
      }
    }

    /**
     * @param {Element} oldElement element to write the value to
     * @param {Element} newElement element to read the value from
     * @param {string} attributeName the attribute name
     * @param {MorphContext} ctx the merge context
     */
    function syncBooleanAttribute(oldElement, newElement, attributeName, ctx) {
      // @ts-ignore this function is only used on boolean attrs that are reflected as dom properties
      const newLiveValue = newElement[attributeName],
        // @ts-ignore ditto
        oldLiveValue = oldElement[attributeName];
      if (newLiveValue !== oldLiveValue) {
        const ignoreUpdate = ignoreAttribute(
          attributeName,
          oldElement,
          "update",
          ctx,
        );
        if (!ignoreUpdate) {
          // update attribute's associated DOM property
          // @ts-ignore this function is only used on boolean attrs that are reflected as dom properties
          oldElement[attributeName] = newElement[attributeName];
        }
        if (newLiveValue) {
          if (!ignoreUpdate) {
            // https://developer.mozilla.org/en-US/docs/Glossary/Boolean/HTML
            // this is the correct way to set a boolean attribute to "true"
            oldElement.setAttribute(attributeName, "");
          }
        } else {
          if (!ignoreAttribute(attributeName, oldElement, "remove", ctx)) {
            oldElement.removeAttribute(attributeName);
          }
        }
      }
    }

    /**
     * @param {string} attr the attribute to be mutated
     * @param {Element} element the element that is going to be updated
     * @param {"update" | "remove"} updateType
     * @param {MorphContext} ctx the merge context
     * @returns {boolean} true if the attribute should be ignored, false otherwise
     */
    function ignoreAttribute(attr, element, updateType, ctx) {
      if (
        attr === "value" &&
        ctx.ignoreActiveValue &&
        element === document.activeElement
      ) {
        return true;
      }
      return (
        ctx.callbacks.beforeAttributeUpdated(attr, element, updateType) ===
        false
      );
    }

    /**
     * @param {Node} possibleActiveElement
     * @param {MorphContext} ctx
     * @returns {boolean}
     */
    function ignoreValueOfActiveElement(possibleActiveElement, ctx) {
      return (
        !!ctx.ignoreActiveValue &&
        possibleActiveElement === document.activeElement &&
        possibleActiveElement !== document.body
      );
    }

    return morphNode;
  })();

  //=============================================================================
  // Head Management Functions
  //=============================================================================
  /**
   * @param {MorphContext} ctx
   * @param {Element} oldNode
   * @param {Element} newNode
   * @param {function} callback
   * @returns {Node[] | Promise<Node[]>}
   */
  function withHeadBlocking(ctx, oldNode, newNode, callback) {
    if (ctx.head.block) {
      const oldHead = oldNode.querySelector("head");
      const newHead = newNode.querySelector("head");
      if (oldHead && newHead) {
        const promises = handleHeadElement(oldHead, newHead, ctx);
        // when head promises resolve, proceed ignoring the head tag
        return Promise.all(promises).then(() => {
          const newCtx = Object.assign(ctx, {
            head: {
              block: false,
              ignore: true,
            },
          });
          return callback(newCtx);
        });
      }
    }
    // just proceed if we not head blocking
    return callback(ctx);
  }

  /**
   *  The HEAD tag can be handled specially, either w/ a 'merge' or 'append' style
   *
   * @param {Element} oldHead
   * @param {Element} newHead
   * @param {MorphContext} ctx
   * @returns {Promise<void>[]}
   */
  function handleHeadElement(oldHead, newHead, ctx) {
    let added = [];
    let removed = [];
    let preserved = [];
    let nodesToAppend = [];

    // put all new head elements into a Map, by their outerHTML
    let srcToNewHeadNodes = new Map();
    for (const newHeadChild of newHead.children) {
      srcToNewHeadNodes.set(newHeadChild.outerHTML, newHeadChild);
    }

    // for each elt in the current head
    for (const currentHeadElt of oldHead.children) {
      // If the current head element is in the map
      let inNewContent = srcToNewHeadNodes.has(currentHeadElt.outerHTML);
      let isReAppended = ctx.head.shouldReAppend(currentHeadElt);
      let isPreserved = ctx.head.shouldPreserve(currentHeadElt);
      if (inNewContent || isPreserved) {
        if (isReAppended) {
          // remove the current version and let the new version replace it and re-execute
          removed.push(currentHeadElt);
        } else {
          // this element already exists and should not be re-appended, so remove it from
          // the new content map, preserving it in the DOM
          srcToNewHeadNodes.delete(currentHeadElt.outerHTML);
          preserved.push(currentHeadElt);
        }
      } else {
        if (ctx.head.style === "append") {
          // we are appending and this existing element is not new content
          // so if and only if it is marked for re-append do we do anything
          if (isReAppended) {
            removed.push(currentHeadElt);
            nodesToAppend.push(currentHeadElt);
          }
        } else {
          // if this is a merge, we remove this content since it is not in the new head
          if (ctx.head.shouldRemove(currentHeadElt) !== false) {
            removed.push(currentHeadElt);
          }
        }
      }
    }

    // Push the remaining new head elements in the Map into the
    // nodes to append to the head tag
    nodesToAppend.push(...srcToNewHeadNodes.values());

    let promises = [];
    for (const newNode of nodesToAppend) {
      // TODO: This could theoretically be null, based on type
      let newElt = /** @type {ChildNode} */ (
        document.createRange().createContextualFragment(newNode.outerHTML)
          .firstChild
      );
      if (ctx.callbacks.beforeNodeAdded(newElt) !== false) {
        if (
          ("href" in newElt && newElt.href) ||
          ("src" in newElt && newElt.src)
        ) {
          /** @type {(result?: any) => void} */ let resolve;
          let promise = new Promise(function (_resolve) {
            resolve = _resolve;
          });
          newElt.addEventListener("load", function () {
            resolve();
          });
          promises.push(promise);
        }
        oldHead.appendChild(newElt);
        ctx.callbacks.afterNodeAdded(newElt);
        added.push(newElt);
      }
    }

    // remove all removed elements, after we have appended the new elements to avoid
    // additional network requests for things like style sheets
    for (const removedElement of removed) {
      if (ctx.callbacks.beforeNodeRemoved(removedElement) !== false) {
        oldHead.removeChild(removedElement);
        ctx.callbacks.afterNodeRemoved(removedElement);
      }
    }

    ctx.head.afterHeadMorphed(oldHead, {
      added: added,
      kept: preserved,
      removed: removed,
    });
    return promises;
  }

  //=============================================================================
  // Create Morph Context Functions
  //=============================================================================
  const createMorphContext = (function () {
    /**
     *
     * @param {Element} oldNode
     * @param {Element} newContent
     * @param {Config} config
     * @returns {MorphContext}
     */
    function createMorphContext(oldNode, newContent, config) {
      const { persistentIds, idMap } = createIdMaps(oldNode, newContent);

      const mergedConfig = mergeDefaults(config);
      const morphStyle = mergedConfig.morphStyle || "outerHTML";
      if (!["innerHTML", "outerHTML"].includes(morphStyle)) {
        throw `Do not understand how to morph style ${morphStyle}`;
      }

      return {
        target: oldNode,
        newContent: newContent,
        config: mergedConfig,
        morphStyle: morphStyle,
        ignoreActive: mergedConfig.ignoreActive,
        ignoreActiveValue: mergedConfig.ignoreActiveValue,
        restoreFocus: mergedConfig.restoreFocus,
        idMap: idMap,
        persistentIds: persistentIds,
        pantry: createPantry(),
        activeElementAndParents: createActiveElementAndParents(oldNode),
        callbacks: mergedConfig.callbacks,
        head: mergedConfig.head,
      };
    }

    /**
     * Deep merges the config object and the Idiomorph.defaults object to
     * produce a final configuration object
     * @param {Config} config
     * @returns {ConfigInternal}
     */
    function mergeDefaults(config) {
      let finalConfig = Object.assign({}, defaults);

      // copy top level stuff into final config
      Object.assign(finalConfig, config);

      // copy callbacks into final config (do this to deep merge the callbacks)
      finalConfig.callbacks = Object.assign(
        {},
        defaults.callbacks,
        config.callbacks,
      );

      // copy head config into final config  (do this to deep merge the head)
      finalConfig.head = Object.assign({}, defaults.head, config.head);

      return finalConfig;
    }

    /**
     * @returns {HTMLDivElement}
     */
    function createPantry() {
      const pantry = document.createElement("div");
      pantry.hidden = true;
      document.body.insertAdjacentElement("afterend", pantry);
      return pantry;
    }

    /**
     * @param {Element} oldNode
     * @returns {Element[]}
     */
    function createActiveElementAndParents(oldNode) {
      /** @type {Element[]} */
      let activeElementAndParents = [];
      let elt = document.activeElement;
      if (elt?.tagName !== "BODY" && oldNode.contains(elt)) {
        while (elt) {
          activeElementAndParents.push(elt);
          if (elt === oldNode) break;
          elt = elt.parentElement;
        }
      }
      return activeElementAndParents;
    }

    /**
     * Returns all elements with an ID contained within the root element and its descendants
     *
     * @param {Element} root
     * @returns {Element[]}
     */
    function findIdElements(root) {
      let elements = Array.from(root.querySelectorAll("[id]"));
      // root could be a document fragment which doesn't have `getAttribute`
      if (root.getAttribute?.("id")) {
        elements.push(root);
      }
      return elements;
    }

    /**
     * A bottom-up algorithm that populates a map of Element -> IdSet.
     * The idSet for a given element is the set of all IDs contained within its subtree.
     * As an optimzation, we filter these IDs through the given list of persistent IDs,
     * because we don't need to bother considering IDed elements that won't be in the new content.
     *
     * @param {Map<Node, Set<string>>} idMap
     * @param {Set<string>} persistentIds
     * @param {Element} root
     * @param {Element[]} elements
     */
    function populateIdMapWithTree(idMap, persistentIds, root, elements) {
      for (const elt of elements) {
        // we can pretend id is non-null String, because the .has line will reject it immediately if not
        const id = /** @type {String} */ (elt.getAttribute("id"));
        if (persistentIds.has(id)) {
          /** @type {Element|null} */
          let current = elt;
          // walk up the parent hierarchy of that element, adding the id
          // of element to the parent's id set
          while (current) {
            let idSet = idMap.get(current);
            // if the id set doesn't exist, create it and insert it in the map
            if (idSet == null) {
              idSet = new Set();
              idMap.set(current, idSet);
            }
            idSet.add(id);

            if (current === root) break;
            current = current.parentElement;
          }
        }
      }
    }

    /**
     * This function computes a map of nodes to all ids contained within that node (inclusive of the
     * node).  This map can be used to ask if two nodes have intersecting sets of ids, which allows
     * for a looser definition of "matching" than tradition id matching, and allows child nodes
     * to contribute to a parent nodes matching.
     *
     * @param {Element} oldContent  the old content that will be morphed
     * @param {Element} newContent  the new content to morph to
     * @returns {IdSets}
     */
    function createIdMaps(oldContent, newContent) {
      const oldIdElements = findIdElements(oldContent);
      const newIdElements = findIdElements(newContent);

      const persistentIds = createPersistentIds(oldIdElements, newIdElements);

      /** @type {Map<Node, Set<string>>} */
      let idMap = new Map();
      populateIdMapWithTree(idMap, persistentIds, oldContent, oldIdElements);

      /** @ts-ignore - if newContent is a duck-typed parent, pass its single child node as the root to halt upwards iteration */
      const newRoot = newContent.__idiomorphRoot || newContent;
      populateIdMapWithTree(idMap, persistentIds, newRoot, newIdElements);

      return { persistentIds, idMap };
    }

    /**
     * This function computes the set of ids that persist between the two contents excluding duplicates
     *
     * @param {Element[]} oldIdElements
     * @param {Element[]} newIdElements
     * @returns {Set<string>}
     */
    function createPersistentIds(oldIdElements, newIdElements) {
      let duplicateIds = new Set();

      /** @type {Map<string, string>} */
      let oldIdTagNameMap = new Map();
      for (const { id, tagName } of oldIdElements) {
        if (oldIdTagNameMap.has(id)) {
          duplicateIds.add(id);
        } else {
          oldIdTagNameMap.set(id, tagName);
        }
      }

      let persistentIds = new Set();
      for (const { id, tagName } of newIdElements) {
        if (persistentIds.has(id)) {
          duplicateIds.add(id);
        } else if (oldIdTagNameMap.get(id) === tagName) {
          persistentIds.add(id);
        }
        // skip if tag types mismatch because its not possible to morph one tag into another
      }

      for (const id of duplicateIds) {
        persistentIds.delete(id);
      }
      return persistentIds;
    }

    return createMorphContext;
  })();

  //=============================================================================
  // HTML Normalization Functions
  //=============================================================================
  const { normalizeElement, normalizeParent } = (function () {
    /** @type {WeakSet<Node>} */
    const generatedByIdiomorph = new WeakSet();

    /**
     *
     * @param {Element | Document} content
     * @returns {Element}
     */
    function normalizeElement(content) {
      if (content instanceof Document) {
        return content.documentElement;
      } else {
        return content;
      }
    }

    /**
     *
     * @param {null | string | Node | HTMLCollection | Node[] | Document & {generatedByIdiomorph:boolean}} newContent
     * @returns {Element}
     */
    function normalizeParent(newContent) {
      if (newContent == null) {
        return document.createElement("div"); // dummy parent element
      } else if (typeof newContent === "string") {
        return normalizeParent(parseContent(newContent));
      } else if (
        generatedByIdiomorph.has(/** @type {Element} */ (newContent))
      ) {
        // the template tag created by idiomorph parsing can serve as a dummy parent
        return /** @type {Element} */ (newContent);
      } else if (newContent instanceof Node) {
        if (newContent.parentNode) {
          // we can't use the parent directly because newContent may have siblings
          // that we don't want in the morph, and reparenting might be expensive (TODO is it?),
          // so instead we create a fake parent node that only sees a slice of its children.
          /** @type {Element} */
          return /** @type {any} */ (new SlicedParentNode(newContent));
        } else {
          // a single node is added as a child to a dummy parent
          const dummyParent = document.createElement("div");
          dummyParent.append(newContent);
          return dummyParent;
        }
      } else {
        // all nodes in the array or HTMLElement collection are consolidated under
        // a single dummy parent element
        const dummyParent = document.createElement("div");
        for (const elt of [...newContent]) {
          dummyParent.append(elt);
        }
        return dummyParent;
      }
    }

    /**
     * A fake duck-typed parent element to wrap a single node, without actually reparenting it.
     * This is useful because the node may have siblings that we don't want in the morph, and it may also be moved
     * or replaced with one or more elements during the morph. This class effectively allows us a window into
     * a slice of a node's children.
     * "If it walks like a duck, and quacks like a duck, then it must be a duck!" -- James Whitcomb Riley (1849–1916)
     */
    class SlicedParentNode {
      /** @param {Node} node */
      constructor(node) {
        this.originalNode = node;
        this.realParentNode = /** @type {Element} */ (node.parentNode);
        this.previousSibling = node.previousSibling;
        this.nextSibling = node.nextSibling;
      }

      /** @returns {Node[]} */
      get childNodes() {
        // return slice of realParent's current childNodes, based on previousSibling and nextSibling
        const nodes = [];
        let cursor = this.previousSibling
          ? this.previousSibling.nextSibling
          : this.realParentNode.firstChild;
        while (cursor && cursor != this.nextSibling) {
          nodes.push(cursor);
          cursor = cursor.nextSibling;
        }
        return nodes;
      }

      /**
       * @param {string} selector
       * @returns {Element[]}
       */
      querySelectorAll(selector) {
        return this.childNodes.reduce((results, node) => {
          if (node instanceof Element) {
            if (node.matches(selector)) results.push(node);
            const nodeList = node.querySelectorAll(selector);
            for (let i = 0; i < nodeList.length; i++) {
              results.push(nodeList[i]);
            }
          }
          return results;
        }, /** @type {Element[]} */ ([]));
      }

      /**
       * @param {Node} node
       * @param {Node} referenceNode
       * @returns {Node}
       */
      insertBefore(node, referenceNode) {
        return this.realParentNode.insertBefore(node, referenceNode);
      }

      /**
       * @param {Node} node
       * @param {Node} referenceNode
       * @returns {Node}
       */
      moveBefore(node, referenceNode) {
        // @ts-ignore - use new moveBefore feature
        return this.realParentNode.moveBefore(node, referenceNode);
      }

      /**
       * for later use with populateIdMapWithTree to halt upwards iteration
       * @returns {Node}
       */
      get __idiomorphRoot() {
        return this.originalNode;
      }
    }

    /**
     *
     * @param {string} newContent
     * @returns {Node | null | DocumentFragment}
     */
    function parseContent(newContent) {
      let parser = new DOMParser();

      // remove svgs to avoid false-positive matches on head, etc.
      let contentWithSvgsRemoved = newContent.replace(
        /<svg(\s[^>]*>|>)([\s\S]*?)<\/svg>/gim,
        "",
      );

      // if the newContent contains a html, head or body tag, we can simply parse it w/o wrapping
      if (
        contentWithSvgsRemoved.match(/<\/html>/) ||
        contentWithSvgsRemoved.match(/<\/head>/) ||
        contentWithSvgsRemoved.match(/<\/body>/)
      ) {
        let content = parser.parseFromString(newContent, "text/html");
        // if it is a full HTML document, return the document itself as the parent container
        if (contentWithSvgsRemoved.match(/<\/html>/)) {
          generatedByIdiomorph.add(content);
          return content;
        } else {
          // otherwise return the html element as the parent container
          let htmlElement = content.firstChild;
          if (htmlElement) {
            generatedByIdiomorph.add(htmlElement);
          }
          return htmlElement;
        }
      } else {
        // if it is partial HTML, wrap it in a template tag to provide a parent element and also to help
        // deal with touchy tags like tr, tbody, etc.
        let responseDoc = parser.parseFromString(
          "<body><template>" + newContent + "</template></body>",
          "text/html",
        );
        let content = /** @type {HTMLTemplateElement} */ (
          responseDoc.body.querySelector("template")
        ).content;
        generatedByIdiomorph.add(content);
        return content;
      }
    }

    return { normalizeElement, normalizeParent };
  })();

  //=============================================================================
  // This is what ends up becoming the Idiomorph global object
  //=============================================================================
  return {
    morph,
    defaults,
  };
})();

/**
 * Morph the state of the currentElement based on the attributes and contents of
 * the newElement. Morphing may dispatch turbo:before-morph-element,
 * turbo:before-morph-attribute, and turbo:morph-element events.
 *
 * @param currentElement Element destination of morphing changes
 * @param newElement Element source of morphing changes
 */
function morphElements(currentElement, newElement, { callbacks, ...options } = {}) {
  Idiomorph.morph(currentElement, newElement, {
    ...options,
    callbacks: new DefaultIdiomorphCallbacks(callbacks)
  });
}

/**
 * Morph the child elements of the currentElement based on the child elements of
 * the newElement. Morphing children may dispatch turbo:before-morph-element,
 * turbo:before-morph-attribute, and turbo:morph-element events.
 *
 * @param currentElement Element destination of morphing children changes
 * @param newElement Element source of morphing children changes
 */
function morphChildren(currentElement, newElement, options = {}) {
  morphElements(currentElement, newElement.childNodes, {
    ...options,
    morphStyle: "innerHTML"
  });
}

function shouldRefreshFrameWithMorphing(currentFrame, newFrame) {
  return currentFrame instanceof FrameElement &&
    currentFrame.shouldReloadWithMorph && (!newFrame || areFramesCompatibleForRefreshing(currentFrame, newFrame)) &&
    !currentFrame.closest("[data-turbo-permanent]")
}

function areFramesCompatibleForRefreshing(currentFrame, newFrame) {
  // newFrame cannot yet be an instance of FrameElement because custom
  // elements don't get initialized until they're attached to the DOM, so
  // test its Element#nodeName instead
  return newFrame instanceof Element && newFrame.nodeName === "TURBO-FRAME" && currentFrame.id === newFrame.id &&
  (!newFrame.getAttribute("src") || urlsAreEqual(currentFrame.src, newFrame.getAttribute("src")))
}

function closestFrameReloadableWithMorphing(node) {
  return node.parentElement.closest("turbo-frame[src][refresh=morph]")
}

class DefaultIdiomorphCallbacks {
  #beforeNodeMorphed

  constructor({ beforeNodeMorphed } = {}) {
    this.#beforeNodeMorphed = beforeNodeMorphed || (() => true);
  }

  beforeNodeAdded = (node) => {
    return !(node.id && node.hasAttribute("data-turbo-permanent") && document.getElementById(node.id))
  }

  beforeNodeMorphed = (currentElement, newElement) => {
    if (currentElement instanceof Element) {
      if (!currentElement.hasAttribute("data-turbo-permanent") && this.#beforeNodeMorphed(currentElement, newElement)) {
        const event = dispatch("turbo:before-morph-element", {
          cancelable: true,
          target: currentElement,
          detail: { currentElement, newElement }
        });

        return !event.defaultPrevented
      } else {
        return false
      }
    }
  }

  beforeAttributeUpdated = (attributeName, target, mutationType) => {
    const event = dispatch("turbo:before-morph-attribute", {
      cancelable: true,
      target,
      detail: { attributeName, mutationType }
    });

    return !event.defaultPrevented
  }

  beforeNodeRemoved = (node) => {
    return this.beforeNodeMorphed(node)
  }

  afterNodeMorphed = (currentElement, newElement) => {
    if (currentElement instanceof Element) {
      dispatch("turbo:morph-element", {
        target: currentElement,
        detail: { currentElement, newElement }
      });
    }
  }
}

class MorphingFrameRenderer extends FrameRenderer {
  static renderElement(currentElement, newElement) {
    dispatch("turbo:before-frame-morph", {
      target: currentElement,
      detail: { currentElement, newElement }
    });

    morphChildren(currentElement, newElement, {
      callbacks: {
        beforeNodeMorphed: (node, newNode) => {
          if (
            shouldRefreshFrameWithMorphing(node, newNode) &&
              closestFrameReloadableWithMorphing(node) === currentElement
          ) {
            node.reload();
            return false
          }
          return true
        }
      }
    });
  }

  async preservingPermanentElements(callback) {
    return await callback()
  }
}

class ProgressBar {
  static animationDuration = 300 /*ms*/

  static get defaultCSS() {
    return unindent`
      .turbo-progress-bar {
        position: fixed;
        display: block;
        top: 0;
        left: 0;
        height: 3px;
        background: #0076ff;
        z-index: 2147483647;
        transition:
          width ${ProgressBar.animationDuration}ms ease-out,
          opacity ${ProgressBar.animationDuration / 2}ms ${ProgressBar.animationDuration / 2}ms ease-in;
        transform: translate3d(0, 0, 0);
      }
    `
  }

  hiding = false
  value = 0
  visible = false

  constructor() {
    this.stylesheetElement = this.createStylesheetElement();
    this.progressElement = this.createProgressElement();
    this.installStylesheetElement();
    this.setValue(0);
  }

  show() {
    if (!this.visible) {
      this.visible = true;
      this.installProgressElement();
      this.startTrickling();
    }
  }

  hide() {
    if (this.visible && !this.hiding) {
      this.hiding = true;
      this.fadeProgressElement(() => {
        this.uninstallProgressElement();
        this.stopTrickling();
        this.visible = false;
        this.hiding = false;
      });
    }
  }

  setValue(value) {
    this.value = value;
    this.refresh();
  }

  // Private

  installStylesheetElement() {
    document.head.insertBefore(this.stylesheetElement, document.head.firstChild);
  }

  installProgressElement() {
    this.progressElement.style.width = "0";
    this.progressElement.style.opacity = "1";
    document.documentElement.insertBefore(this.progressElement, document.body);
    this.refresh();
  }

  fadeProgressElement(callback) {
    this.progressElement.style.opacity = "0";
    setTimeout(callback, ProgressBar.animationDuration * 1.5);
  }

  uninstallProgressElement() {
    if (this.progressElement.parentNode) {
      document.documentElement.removeChild(this.progressElement);
    }
  }

  startTrickling() {
    if (!this.trickleInterval) {
      this.trickleInterval = window.setInterval(this.trickle, ProgressBar.animationDuration);
    }
  }

  stopTrickling() {
    window.clearInterval(this.trickleInterval);
    delete this.trickleInterval;
  }

  trickle = () => {
    this.setValue(this.value + Math.random() / 100);
  }

  refresh() {
    requestAnimationFrame(() => {
      this.progressElement.style.width = `${10 + this.value * 90}%`;
    });
  }

  createStylesheetElement() {
    const element = document.createElement("style");
    element.type = "text/css";
    element.textContent = ProgressBar.defaultCSS;
    const cspNonce = getCspNonce();
    if (cspNonce) {
      element.nonce = cspNonce;
    }
    return element
  }

  createProgressElement() {
    const element = document.createElement("div");
    element.className = "turbo-progress-bar";
    return element
  }
}

class HeadSnapshot extends Snapshot {
  detailsByOuterHTML = this.children
    .filter((element) => !elementIsNoscript(element))
    .map((element) => elementWithoutNonce(element))
    .reduce((result, element) => {
      const { outerHTML } = element;
      const details =
        outerHTML in result
          ? result[outerHTML]
          : {
              type: elementType(element),
              tracked: elementIsTracked(element),
              elements: []
            };
      return {
        ...result,
        [outerHTML]: {
          ...details,
          elements: [...details.elements, element]
        }
      }
    }, {})

  get trackedElementSignature() {
    return Object.keys(this.detailsByOuterHTML)
      .filter((outerHTML) => this.detailsByOuterHTML[outerHTML].tracked)
      .join("")
  }

  getScriptElementsNotInSnapshot(snapshot) {
    return this.getElementsMatchingTypeNotInSnapshot("script", snapshot)
  }

  getStylesheetElementsNotInSnapshot(snapshot) {
    return this.getElementsMatchingTypeNotInSnapshot("stylesheet", snapshot)
  }

  getElementsMatchingTypeNotInSnapshot(matchedType, snapshot) {
    return Object.keys(this.detailsByOuterHTML)
      .filter((outerHTML) => !(outerHTML in snapshot.detailsByOuterHTML))
      .map((outerHTML) => this.detailsByOuterHTML[outerHTML])
      .filter(({ type }) => type == matchedType)
      .map(({ elements: [element] }) => element)
  }

  get provisionalElements() {
    return Object.keys(this.detailsByOuterHTML).reduce((result, outerHTML) => {
      const { type, tracked, elements } = this.detailsByOuterHTML[outerHTML];
      if (type == null && !tracked) {
        return [...result, ...elements]
      } else if (elements.length > 1) {
        return [...result, ...elements.slice(1)]
      } else {
        return result
      }
    }, [])
  }

  getMetaValue(name) {
    const element = this.findMetaElementByName(name);
    return element ? element.getAttribute("content") : null
  }

  findMetaElementByName(name) {
    return Object.keys(this.detailsByOuterHTML).reduce((result, outerHTML) => {
      const {
        elements: [element]
      } = this.detailsByOuterHTML[outerHTML];
      return elementIsMetaElementWithName(element, name) ? element : result
    }, undefined | undefined)
  }
}

function elementType(element) {
  if (elementIsScript(element)) {
    return "script"
  } else if (elementIsStylesheet(element)) {
    return "stylesheet"
  }
}

function elementIsTracked(element) {
  return element.getAttribute("data-turbo-track") == "reload"
}

function elementIsScript(element) {
  const tagName = element.localName;
  return tagName == "script"
}

function elementIsNoscript(element) {
  const tagName = element.localName;
  return tagName == "noscript"
}

function elementIsStylesheet(element) {
  const tagName = element.localName;
  return tagName == "style" || (tagName == "link" && element.getAttribute("rel") == "stylesheet")
}

function elementIsMetaElementWithName(element, name) {
  const tagName = element.localName;
  return tagName == "meta" && element.getAttribute("name") == name
}

function elementWithoutNonce(element) {
  if (element.hasAttribute("nonce")) {
    element.setAttribute("nonce", "");
  }

  return element
}

class PageSnapshot extends Snapshot {
  static fromHTMLString(html = "") {
    return this.fromDocument(parseHTMLDocument(html))
  }

  static fromElement(element) {
    return this.fromDocument(element.ownerDocument)
  }

  static fromDocument({ documentElement, body, head }) {
    return new this(documentElement, body, new HeadSnapshot(head))
  }

  constructor(documentElement, body, headSnapshot) {
    super(body);
    this.documentElement = documentElement;
    this.headSnapshot = headSnapshot;
  }

  clone() {
    const clonedElement = this.element.cloneNode(true);

    const selectElements = this.element.querySelectorAll("select");
    const clonedSelectElements = clonedElement.querySelectorAll("select");

    for (const [index, source] of selectElements.entries()) {
      const clone = clonedSelectElements[index];
      for (const option of clone.selectedOptions) option.selected = false;
      for (const option of source.selectedOptions) clone.options[option.index].selected = true;
    }

    for (const clonedPasswordInput of clonedElement.querySelectorAll('input[type="password"]')) {
      clonedPasswordInput.value = "";
    }

    return new PageSnapshot(this.documentElement, clonedElement, this.headSnapshot)
  }

  get lang() {
    return this.documentElement.getAttribute("lang")
  }

  get headElement() {
    return this.headSnapshot.element
  }

  get rootLocation() {
    const root = this.getSetting("root") ?? "/";
    return expandURL(root)
  }

  get cacheControlValue() {
    return this.getSetting("cache-control")
  }

  get isPreviewable() {
    return this.cacheControlValue != "no-preview"
  }

  get isCacheable() {
    return this.cacheControlValue != "no-cache"
  }

  get isVisitable() {
    return this.getSetting("visit-control") != "reload"
  }

  get prefersViewTransitions() {
    const viewTransitionEnabled = this.getSetting("view-transition") === "true" || this.headSnapshot.getMetaValue("view-transition") === "same-origin";
    return viewTransitionEnabled && !window.matchMedia("(prefers-reduced-motion: reduce)").matches
  }

  get shouldMorphPage() {
    return this.getSetting("refresh-method") === "morph"
  }

  get shouldPreserveScrollPosition() {
    return this.getSetting("refresh-scroll") === "preserve"
  }

  // Private

  getSetting(name) {
    return this.headSnapshot.getMetaValue(`turbo-${name}`)
  }
}

class ViewTransitioner {
  #viewTransitionStarted = false
  #lastOperation = Promise.resolve()

  renderChange(useViewTransition, render) {
    if (useViewTransition && this.viewTransitionsAvailable && !this.#viewTransitionStarted) {
      this.#viewTransitionStarted = true;
      this.#lastOperation = this.#lastOperation.then(async () => {
        await document.startViewTransition(render).finished;
      });
    } else {
      this.#lastOperation = this.#lastOperation.then(render);
    }

    return this.#lastOperation
  }

  get viewTransitionsAvailable() {
    return document.startViewTransition
  }
}

const defaultOptions = {
  action: "advance",
  historyChanged: false,
  visitCachedSnapshot: () => {},
  willRender: true,
  updateHistory: true,
  shouldCacheSnapshot: true,
  acceptsStreamResponse: false
};

const TimingMetric = {
  visitStart: "visitStart",
  requestStart: "requestStart",
  requestEnd: "requestEnd",
  visitEnd: "visitEnd"
};

const VisitState = {
  initialized: "initialized",
  started: "started",
  canceled: "canceled",
  failed: "failed",
  completed: "completed"
};

const SystemStatusCode = {
  networkFailure: 0,
  timeoutFailure: -1,
  contentTypeMismatch: -2
};

const Direction = {
  advance: "forward",
  restore: "back",
  replace: "none"
};

class Visit {
  identifier = uuid() // Required by turbo-ios
  timingMetrics = {}

  followedRedirect = false
  historyChanged = false
  scrolled = false
  shouldCacheSnapshot = true
  acceptsStreamResponse = false
  snapshotCached = false
  state = VisitState.initialized
  viewTransitioner = new ViewTransitioner()

  constructor(delegate, location, restorationIdentifier, options = {}) {
    this.delegate = delegate;
    this.location = location;
    this.restorationIdentifier = restorationIdentifier || uuid();

    const {
      action,
      historyChanged,
      referrer,
      snapshot,
      snapshotHTML,
      response,
      visitCachedSnapshot,
      willRender,
      updateHistory,
      shouldCacheSnapshot,
      acceptsStreamResponse,
      direction
    } = {
      ...defaultOptions,
      ...options
    };
    this.action = action;
    this.historyChanged = historyChanged;
    this.referrer = referrer;
    this.snapshot = snapshot;
    this.snapshotHTML = snapshotHTML;
    this.response = response;
    this.isSamePage = this.delegate.locationWithActionIsSamePage(this.location, this.action);
    this.isPageRefresh = this.view.isPageRefresh(this);
    this.visitCachedSnapshot = visitCachedSnapshot;
    this.willRender = willRender;
    this.updateHistory = updateHistory;
    this.scrolled = !willRender;
    this.shouldCacheSnapshot = shouldCacheSnapshot;
    this.acceptsStreamResponse = acceptsStreamResponse;
    this.direction = direction || Direction[action];
  }

  get adapter() {
    return this.delegate.adapter
  }

  get view() {
    return this.delegate.view
  }

  get history() {
    return this.delegate.history
  }

  get restorationData() {
    return this.history.getRestorationDataForIdentifier(this.restorationIdentifier)
  }

  get silent() {
    return this.isSamePage
  }

  start() {
    if (this.state == VisitState.initialized) {
      this.recordTimingMetric(TimingMetric.visitStart);
      this.state = VisitState.started;
      this.adapter.visitStarted(this);
      this.delegate.visitStarted(this);
    }
  }

  cancel() {
    if (this.state == VisitState.started) {
      if (this.request) {
        this.request.cancel();
      }
      this.cancelRender();
      this.state = VisitState.canceled;
    }
  }

  complete() {
    if (this.state == VisitState.started) {
      this.recordTimingMetric(TimingMetric.visitEnd);
      this.adapter.visitCompleted(this);
      this.state = VisitState.completed;
      this.followRedirect();

      if (!this.followedRedirect) {
        this.delegate.visitCompleted(this);
      }
    }
  }

  fail() {
    if (this.state == VisitState.started) {
      this.state = VisitState.failed;
      this.adapter.visitFailed(this);
      this.delegate.visitCompleted(this);
    }
  }

  changeHistory() {
    if (!this.historyChanged && this.updateHistory) {
      const actionForHistory = this.location.href === this.referrer?.href ? "replace" : this.action;
      const method = getHistoryMethodForAction(actionForHistory);
      this.history.update(method, this.location, this.restorationIdentifier);
      this.historyChanged = true;
    }
  }

  issueRequest() {
    if (this.hasPreloadedResponse()) {
      this.simulateRequest();
    } else if (this.shouldIssueRequest() && !this.request) {
      this.request = new FetchRequest(this, FetchMethod.get, this.location);
      this.request.perform();
    }
  }

  simulateRequest() {
    if (this.response) {
      this.startRequest();
      this.recordResponse();
      this.finishRequest();
    }
  }

  startRequest() {
    this.recordTimingMetric(TimingMetric.requestStart);
    this.adapter.visitRequestStarted(this);
  }

  recordResponse(response = this.response) {
    this.response = response;
    if (response) {
      const { statusCode } = response;
      if (isSuccessful(statusCode)) {
        this.adapter.visitRequestCompleted(this);
      } else {
        this.adapter.visitRequestFailedWithStatusCode(this, statusCode);
      }
    }
  }

  finishRequest() {
    this.recordTimingMetric(TimingMetric.requestEnd);
    this.adapter.visitRequestFinished(this);
  }

  loadResponse() {
    if (this.response) {
      const { statusCode, responseHTML } = this.response;
      this.render(async () => {
        if (this.shouldCacheSnapshot) this.cacheSnapshot();
        if (this.view.renderPromise) await this.view.renderPromise;

        if (isSuccessful(statusCode) && responseHTML != null) {
          const snapshot = PageSnapshot.fromHTMLString(responseHTML);
          await this.renderPageSnapshot(snapshot, false);

          this.adapter.visitRendered(this);
          this.complete();
        } else {
          await this.view.renderError(PageSnapshot.fromHTMLString(responseHTML), this);
          this.adapter.visitRendered(this);
          this.fail();
        }
      });
    }
  }

  getCachedSnapshot() {
    const snapshot = this.view.getCachedSnapshotForLocation(this.location) || this.getPreloadedSnapshot();

    if (snapshot && (!getAnchor(this.location) || snapshot.hasAnchor(getAnchor(this.location)))) {
      if (this.action == "restore" || snapshot.isPreviewable) {
        return snapshot
      }
    }
  }

  getPreloadedSnapshot() {
    if (this.snapshotHTML) {
      return PageSnapshot.fromHTMLString(this.snapshotHTML)
    }
  }

  hasCachedSnapshot() {
    return this.getCachedSnapshot() != null
  }

  loadCachedSnapshot() {
    const snapshot = this.getCachedSnapshot();
    if (snapshot) {
      const isPreview = this.shouldIssueRequest();
      this.render(async () => {
        this.cacheSnapshot();
        if (this.isSamePage || this.isPageRefresh) {
          this.adapter.visitRendered(this);
        } else {
          if (this.view.renderPromise) await this.view.renderPromise;

          await this.renderPageSnapshot(snapshot, isPreview);

          this.adapter.visitRendered(this);
          if (!isPreview) {
            this.complete();
          }
        }
      });
    }
  }

  followRedirect() {
    if (this.redirectedToLocation && !this.followedRedirect && this.response?.redirected) {
      this.adapter.visitProposedToLocation(this.redirectedToLocation, {
        action: "replace",
        response: this.response,
        shouldCacheSnapshot: false,
        willRender: false
      });
      this.followedRedirect = true;
    }
  }

  goToSamePageAnchor() {
    if (this.isSamePage) {
      this.render(async () => {
        this.cacheSnapshot();
        this.performScroll();
        this.changeHistory();
        this.adapter.visitRendered(this);
      });
    }
  }

  // Fetch request delegate

  prepareRequest(request) {
    if (this.acceptsStreamResponse) {
      request.acceptResponseType(StreamMessage.contentType);
    }
  }

  requestStarted() {
    this.startRequest();
  }

  requestPreventedHandlingResponse(_request, _response) {}

  async requestSucceededWithResponse(request, response) {
    const responseHTML = await response.responseHTML;
    const { redirected, statusCode } = response;
    if (responseHTML == undefined) {
      this.recordResponse({
        statusCode: SystemStatusCode.contentTypeMismatch,
        redirected
      });
    } else {
      this.redirectedToLocation = response.redirected ? response.location : undefined;
      this.recordResponse({ statusCode: statusCode, responseHTML, redirected });
    }
  }

  async requestFailedWithResponse(request, response) {
    const responseHTML = await response.responseHTML;
    const { redirected, statusCode } = response;
    if (responseHTML == undefined) {
      this.recordResponse({
        statusCode: SystemStatusCode.contentTypeMismatch,
        redirected
      });
    } else {
      this.recordResponse({ statusCode: statusCode, responseHTML, redirected });
    }
  }

  requestErrored(_request, _error) {
    this.recordResponse({
      statusCode: SystemStatusCode.networkFailure,
      redirected: false
    });
  }

  requestFinished() {
    this.finishRequest();
  }

  // Scrolling

  performScroll() {
    if (!this.scrolled && !this.view.forceReloaded && !this.view.shouldPreserveScrollPosition(this)) {
      if (this.action == "restore") {
        this.scrollToRestoredPosition() || this.scrollToAnchor() || this.view.scrollToTop();
      } else {
        this.scrollToAnchor() || this.view.scrollToTop();
      }
      if (this.isSamePage) {
        this.delegate.visitScrolledToSamePageLocation(this.view.lastRenderedLocation, this.location);
      }

      this.scrolled = true;
    }
  }

  scrollToRestoredPosition() {
    const { scrollPosition } = this.restorationData;
    if (scrollPosition) {
      this.view.scrollToPosition(scrollPosition);
      return true
    }
  }

  scrollToAnchor() {
    const anchor = getAnchor(this.location);
    if (anchor != null) {
      this.view.scrollToAnchor(anchor);
      return true
    }
  }

  // Instrumentation

  recordTimingMetric(metric) {
    this.timingMetrics[metric] = new Date().getTime();
  }

  getTimingMetrics() {
    return { ...this.timingMetrics }
  }

  // Private

  hasPreloadedResponse() {
    return typeof this.response == "object"
  }

  shouldIssueRequest() {
    if (this.isSamePage) {
      return false
    } else if (this.action == "restore") {
      return !this.hasCachedSnapshot()
    } else {
      return this.willRender
    }
  }

  cacheSnapshot() {
    if (!this.snapshotCached) {
      this.view.cacheSnapshot(this.snapshot).then((snapshot) => snapshot && this.visitCachedSnapshot(snapshot));
      this.snapshotCached = true;
    }
  }

  async render(callback) {
    this.cancelRender();
    await new Promise((resolve) => {
      this.frame =
        document.visibilityState === "hidden" ? setTimeout(() => resolve(), 0) : requestAnimationFrame(() => resolve());
    });
    await callback();
    delete this.frame;
  }

  async renderPageSnapshot(snapshot, isPreview) {
    await this.viewTransitioner.renderChange(this.view.shouldTransitionTo(snapshot), async () => {
      await this.view.renderPage(snapshot, isPreview, this.willRender, this);
      this.performScroll();
    });
  }

  cancelRender() {
    if (this.frame) {
      cancelAnimationFrame(this.frame);
      delete this.frame;
    }
  }
}

function isSuccessful(statusCode) {
  return statusCode >= 200 && statusCode < 300
}

class BrowserAdapter {
  progressBar = new ProgressBar()

  constructor(session) {
    this.session = session;
  }

  visitProposedToLocation(location, options) {
    if (locationIsVisitable(location, this.navigator.rootLocation)) {
      this.navigator.startVisit(location, options?.restorationIdentifier || uuid(), options);
    } else {
      window.location.href = location.toString();
    }
  }

  visitStarted(visit) {
    this.location = visit.location;
    this.redirectedToLocation = null;

    visit.loadCachedSnapshot();
    visit.issueRequest();
    visit.goToSamePageAnchor();
  }

  visitRequestStarted(visit) {
    this.progressBar.setValue(0);
    if (visit.hasCachedSnapshot() || visit.action != "restore") {
      this.showVisitProgressBarAfterDelay();
    } else {
      this.showProgressBar();
    }
  }

  visitRequestCompleted(visit) {
    visit.loadResponse();

    if (visit.response.redirected) {
      this.redirectedToLocation = visit.redirectedToLocation;
    }
  }

  visitRequestFailedWithStatusCode(visit, statusCode) {
    switch (statusCode) {
      case SystemStatusCode.networkFailure:
      case SystemStatusCode.timeoutFailure:
      case SystemStatusCode.contentTypeMismatch:
        return this.reload({
          reason: "request_failed",
          context: {
            statusCode
          }
        })
      default:
        return visit.loadResponse()
    }
  }

  visitRequestFinished(_visit) {}

  visitCompleted(_visit) {
    this.progressBar.setValue(1);
    this.hideVisitProgressBar();
  }

  pageInvalidated(reason) {
    this.reload(reason);
  }

  visitFailed(_visit) {
    this.progressBar.setValue(1);
    this.hideVisitProgressBar();
  }

  visitRendered(_visit) {}

  // Link prefetching

  linkPrefetchingIsEnabledForLocation(location) {
    return true
  }

  // Form Submission Delegate

  formSubmissionStarted(_formSubmission) {
    this.progressBar.setValue(0);
    this.showFormProgressBarAfterDelay();
  }

  formSubmissionFinished(_formSubmission) {
    this.progressBar.setValue(1);
    this.hideFormProgressBar();
  }

  // Private

  showVisitProgressBarAfterDelay() {
    this.visitProgressBarTimeout = window.setTimeout(this.showProgressBar, this.session.progressBarDelay);
  }

  hideVisitProgressBar() {
    this.progressBar.hide();
    if (this.visitProgressBarTimeout != null) {
      window.clearTimeout(this.visitProgressBarTimeout);
      delete this.visitProgressBarTimeout;
    }
  }

  showFormProgressBarAfterDelay() {
    if (this.formProgressBarTimeout == null) {
      this.formProgressBarTimeout = window.setTimeout(this.showProgressBar, this.session.progressBarDelay);
    }
  }

  hideFormProgressBar() {
    this.progressBar.hide();
    if (this.formProgressBarTimeout != null) {
      window.clearTimeout(this.formProgressBarTimeout);
      delete this.formProgressBarTimeout;
    }
  }

  showProgressBar = () => {
    this.progressBar.show();
  }

  reload(reason) {
    dispatch("turbo:reload", { detail: reason });

    window.location.href = (this.redirectedToLocation || this.location)?.toString() || window.location.href;
  }

  get navigator() {
    return this.session.navigator
  }
}

class CacheObserver {
  selector = "[data-turbo-temporary]"
  deprecatedSelector = "[data-turbo-cache=false]"

  started = false

  start() {
    if (!this.started) {
      this.started = true;
      addEventListener("turbo:before-cache", this.removeTemporaryElements, false);
    }
  }

  stop() {
    if (this.started) {
      this.started = false;
      removeEventListener("turbo:before-cache", this.removeTemporaryElements, false);
    }
  }

  removeTemporaryElements = (_event) => {
    for (const element of this.temporaryElements) {
      element.remove();
    }
  }

  get temporaryElements() {
    return [...document.querySelectorAll(this.selector), ...this.temporaryElementsWithDeprecation]
  }

  get temporaryElementsWithDeprecation() {
    const elements = document.querySelectorAll(this.deprecatedSelector);

    if (elements.length) {
      console.warn(
        `The ${this.deprecatedSelector} selector is deprecated and will be removed in a future version. Use ${this.selector} instead.`
      );
    }

    return [...elements]
  }
}

class FrameRedirector {
  constructor(session, element) {
    this.session = session;
    this.element = element;
    this.linkInterceptor = new LinkInterceptor(this, element);
    this.formSubmitObserver = new FormSubmitObserver(this, element);
  }

  start() {
    this.linkInterceptor.start();
    this.formSubmitObserver.start();
  }

  stop() {
    this.linkInterceptor.stop();
    this.formSubmitObserver.stop();
  }

  // Link interceptor delegate

  shouldInterceptLinkClick(element, _location, _event) {
    return this.#shouldRedirect(element)
  }

  linkClickIntercepted(element, url, event) {
    const frame = this.#findFrameElement(element);
    if (frame) {
      frame.delegate.linkClickIntercepted(element, url, event);
    }
  }

  // Form submit observer delegate

  willSubmitForm(element, submitter) {
    return (
      element.closest("turbo-frame") == null &&
      this.#shouldSubmit(element, submitter) &&
      this.#shouldRedirect(element, submitter)
    )
  }

  formSubmitted(element, submitter) {
    const frame = this.#findFrameElement(element, submitter);
    if (frame) {
      frame.delegate.formSubmitted(element, submitter);
    }
  }

  #shouldSubmit(form, submitter) {
    const action = getAction$1(form, submitter);
    const meta = this.element.ownerDocument.querySelector(`meta[name="turbo-root"]`);
    const rootLocation = expandURL(meta?.content ?? "/");

    return this.#shouldRedirect(form, submitter) && locationIsVisitable(action, rootLocation)
  }

  #shouldRedirect(element, submitter) {
    const isNavigatable =
      element instanceof HTMLFormElement
        ? this.session.submissionIsNavigatable(element, submitter)
        : this.session.elementIsNavigatable(element);

    if (isNavigatable) {
      const frame = this.#findFrameElement(element, submitter);
      return frame ? frame != element.closest("turbo-frame") : false
    } else {
      return false
    }
  }

  #findFrameElement(element, submitter) {
    const id = submitter?.getAttribute("data-turbo-frame") || element.getAttribute("data-turbo-frame");
    if (id && id != "_top") {
      const frame = this.element.querySelector(`#${id}:not([disabled])`);
      if (frame instanceof FrameElement) {
        return frame
      }
    }
  }
}

class History {
  location
  restorationIdentifier = uuid()
  restorationData = {}
  started = false
  pageLoaded = false
  currentIndex = 0

  constructor(delegate) {
    this.delegate = delegate;
  }

  start() {
    if (!this.started) {
      addEventListener("popstate", this.onPopState, false);
      addEventListener("load", this.onPageLoad, false);
      this.currentIndex = history.state?.turbo?.restorationIndex || 0;
      this.started = true;
      this.replace(new URL(window.location.href));
    }
  }

  stop() {
    if (this.started) {
      removeEventListener("popstate", this.onPopState, false);
      removeEventListener("load", this.onPageLoad, false);
      this.started = false;
    }
  }

  push(location, restorationIdentifier) {
    this.update(history.pushState, location, restorationIdentifier);
  }

  replace(location, restorationIdentifier) {
    this.update(history.replaceState, location, restorationIdentifier);
  }

  update(method, location, restorationIdentifier = uuid()) {
    if (method === history.pushState) ++this.currentIndex;

    const state = { turbo: { restorationIdentifier, restorationIndex: this.currentIndex } };
    method.call(history, state, "", location.href);
    this.location = location;
    this.restorationIdentifier = restorationIdentifier;
  }

  // Restoration data

  getRestorationDataForIdentifier(restorationIdentifier) {
    return this.restorationData[restorationIdentifier] || {}
  }

  updateRestorationData(additionalData) {
    const { restorationIdentifier } = this;
    const restorationData = this.restorationData[restorationIdentifier];
    this.restorationData[restorationIdentifier] = {
      ...restorationData,
      ...additionalData
    };
  }

  // Scroll restoration

  assumeControlOfScrollRestoration() {
    if (!this.previousScrollRestoration) {
      this.previousScrollRestoration = history.scrollRestoration ?? "auto";
      history.scrollRestoration = "manual";
    }
  }

  relinquishControlOfScrollRestoration() {
    if (this.previousScrollRestoration) {
      history.scrollRestoration = this.previousScrollRestoration;
      delete this.previousScrollRestoration;
    }
  }

  // Event handlers

  onPopState = (event) => {
    if (this.shouldHandlePopState()) {
      const { turbo } = event.state || {};
      if (turbo) {
        this.location = new URL(window.location.href);
        const { restorationIdentifier, restorationIndex } = turbo;
        this.restorationIdentifier = restorationIdentifier;
        const direction = restorationIndex > this.currentIndex ? "forward" : "back";
        this.delegate.historyPoppedToLocationWithRestorationIdentifierAndDirection(this.location, restorationIdentifier, direction);
        this.currentIndex = restorationIndex;
      }
    }
  }

  onPageLoad = async (_event) => {
    await nextMicrotask();
    this.pageLoaded = true;
  }

  // Private

  shouldHandlePopState() {
    // Safari dispatches a popstate event after window's load event, ignore it
    return this.pageIsLoaded()
  }

  pageIsLoaded() {
    return this.pageLoaded || document.readyState == "complete"
  }
}

class LinkPrefetchObserver {
  started = false
  #prefetchedLink = null

  constructor(delegate, eventTarget) {
    this.delegate = delegate;
    this.eventTarget = eventTarget;
  }

  start() {
    if (this.started) return

    if (this.eventTarget.readyState === "loading") {
      this.eventTarget.addEventListener("DOMContentLoaded", this.#enable, { once: true });
    } else {
      this.#enable();
    }
  }

  stop() {
    if (!this.started) return

    this.eventTarget.removeEventListener("mouseenter", this.#tryToPrefetchRequest, {
      capture: true,
      passive: true
    });
    this.eventTarget.removeEventListener("mouseleave", this.#cancelRequestIfObsolete, {
      capture: true,
      passive: true
    });

    this.eventTarget.removeEventListener("turbo:before-fetch-request", this.#tryToUsePrefetchedRequest, true);
    this.started = false;
  }

  #enable = () => {
    this.eventTarget.addEventListener("mouseenter", this.#tryToPrefetchRequest, {
      capture: true,
      passive: true
    });
    this.eventTarget.addEventListener("mouseleave", this.#cancelRequestIfObsolete, {
      capture: true,
      passive: true
    });

    this.eventTarget.addEventListener("turbo:before-fetch-request", this.#tryToUsePrefetchedRequest, true);
    this.started = true;
  }

  #tryToPrefetchRequest = (event) => {
    if (getMetaContent("turbo-prefetch") === "false") return

    const target = event.target;
    const isLink = target.matches && target.matches("a[href]:not([target^=_]):not([download])");

    if (isLink && this.#isPrefetchable(target)) {
      const link = target;
      const location = getLocationForLink(link);

      if (this.delegate.canPrefetchRequestToLocation(link, location)) {
        this.#prefetchedLink = link;

        const fetchRequest = new FetchRequest(
          this,
          FetchMethod.get,
          location,
          new URLSearchParams(),
          target
        );

        fetchRequest.fetchOptions.priority = "low";

        prefetchCache.setLater(location.toString(), fetchRequest, this.#cacheTtl);
      }
    }
  }

  #cancelRequestIfObsolete = (event) => {
    if (event.target === this.#prefetchedLink) this.#cancelPrefetchRequest();
  }

  #cancelPrefetchRequest = () => {
    prefetchCache.clear();
    this.#prefetchedLink = null;
  }

  #tryToUsePrefetchedRequest = (event) => {
    if (event.target.tagName !== "FORM" && event.detail.fetchOptions.method === "GET") {
      const cached = prefetchCache.get(event.detail.url.toString());

      if (cached) {
        // User clicked link, use cache response
        event.detail.fetchRequest = cached;
      }

      prefetchCache.clear();
    }
  }

  prepareRequest(request) {
    const link = request.target;

    request.headers["X-Sec-Purpose"] = "prefetch";

    const turboFrame = link.closest("turbo-frame");
    const turboFrameTarget = link.getAttribute("data-turbo-frame") || turboFrame?.getAttribute("target") || turboFrame?.id;

    if (turboFrameTarget && turboFrameTarget !== "_top") {
      request.headers["Turbo-Frame"] = turboFrameTarget;
    }
  }

  // Fetch request interface

  requestSucceededWithResponse() {}

  requestStarted(fetchRequest) {}

  requestErrored(fetchRequest) {}

  requestFinished(fetchRequest) {}

  requestPreventedHandlingResponse(fetchRequest, fetchResponse) {}

  requestFailedWithResponse(fetchRequest, fetchResponse) {}

  get #cacheTtl() {
    return Number(getMetaContent("turbo-prefetch-cache-time")) || cacheTtl
  }

  #isPrefetchable(link) {
    const href = link.getAttribute("href");

    if (!href) return false

    if (unfetchableLink(link)) return false
    if (linkToTheSamePage(link)) return false
    if (linkOptsOut(link)) return false
    if (nonSafeLink(link)) return false
    if (eventPrevented(link)) return false

    return true
  }
}

const unfetchableLink = (link) => {
  return link.origin !== document.location.origin || !["http:", "https:"].includes(link.protocol) || link.hasAttribute("target")
};

const linkToTheSamePage = (link) => {
  return (link.pathname + link.search === document.location.pathname + document.location.search) || link.href.startsWith("#")
};

const linkOptsOut = (link) => {
  if (link.getAttribute("data-turbo-prefetch") === "false") return true
  if (link.getAttribute("data-turbo") === "false") return true

  const turboPrefetchParent = findClosestRecursively(link, "[data-turbo-prefetch]");
  if (turboPrefetchParent && turboPrefetchParent.getAttribute("data-turbo-prefetch") === "false") return true

  return false
};

const nonSafeLink = (link) => {
  const turboMethod = link.getAttribute("data-turbo-method");
  if (turboMethod && turboMethod.toLowerCase() !== "get") return true

  if (isUJS(link)) return true
  if (link.hasAttribute("data-turbo-confirm")) return true
  if (link.hasAttribute("data-turbo-stream")) return true

  return false
};

const isUJS = (link) => {
  return link.hasAttribute("data-remote") || link.hasAttribute("data-behavior") || link.hasAttribute("data-confirm") || link.hasAttribute("data-method")
};

const eventPrevented = (link) => {
  const event = dispatch("turbo:before-prefetch", { target: link, cancelable: true });
  return event.defaultPrevented
};

class Navigator {
  constructor(delegate) {
    this.delegate = delegate;
  }

  proposeVisit(location, options = {}) {
    if (this.delegate.allowsVisitingLocationWithAction(location, options.action)) {
      this.delegate.visitProposedToLocation(location, options);
    }
  }

  startVisit(locatable, restorationIdentifier, options = {}) {
    this.stop();
    this.currentVisit = new Visit(this, expandURL(locatable), restorationIdentifier, {
      referrer: this.location,
      ...options
    });
    this.currentVisit.start();
  }

  submitForm(form, submitter) {
    this.stop();
    this.formSubmission = new FormSubmission(this, form, submitter, true);

    this.formSubmission.start();
  }

  stop() {
    if (this.formSubmission) {
      this.formSubmission.stop();
      delete this.formSubmission;
    }

    if (this.currentVisit) {
      this.currentVisit.cancel();
      delete this.currentVisit;
    }
  }

  get adapter() {
    return this.delegate.adapter
  }

  get view() {
    return this.delegate.view
  }

  get rootLocation() {
    return this.view.snapshot.rootLocation
  }

  get history() {
    return this.delegate.history
  }

  // Form submission delegate

  formSubmissionStarted(formSubmission) {
    // Not all adapters implement formSubmissionStarted
    if (typeof this.adapter.formSubmissionStarted === "function") {
      this.adapter.formSubmissionStarted(formSubmission);
    }
  }

  async formSubmissionSucceededWithResponse(formSubmission, fetchResponse) {
    if (formSubmission == this.formSubmission) {
      const responseHTML = await fetchResponse.responseHTML;
      if (responseHTML) {
        const shouldCacheSnapshot = formSubmission.isSafe;
        if (!shouldCacheSnapshot) {
          this.view.clearSnapshotCache();
        }

        const { statusCode, redirected } = fetchResponse;
        const action = this.#getActionForFormSubmission(formSubmission, fetchResponse);
        const visitOptions = {
          action,
          shouldCacheSnapshot,
          response: { statusCode, responseHTML, redirected }
        };
        this.proposeVisit(fetchResponse.location, visitOptions);
      }
    }
  }

  async formSubmissionFailedWithResponse(formSubmission, fetchResponse) {
    const responseHTML = await fetchResponse.responseHTML;

    if (responseHTML) {
      const snapshot = PageSnapshot.fromHTMLString(responseHTML);
      if (fetchResponse.serverError) {
        await this.view.renderError(snapshot, this.currentVisit);
      } else {
        await this.view.renderPage(snapshot, false, true, this.currentVisit);
      }
      if(!snapshot.shouldPreserveScrollPosition) {
        this.view.scrollToTop();
      }
      this.view.clearSnapshotCache();
    }
  }

  formSubmissionErrored(formSubmission, error) {
    console.error(error);
  }

  formSubmissionFinished(formSubmission) {
    // Not all adapters implement formSubmissionFinished
    if (typeof this.adapter.formSubmissionFinished === "function") {
      this.adapter.formSubmissionFinished(formSubmission);
    }
  }

  // Link prefetching

  linkPrefetchingIsEnabledForLocation(location) {
    // Not all adapters implement linkPrefetchingIsEnabledForLocation
    if (typeof this.adapter.linkPrefetchingIsEnabledForLocation === "function") {
      return this.adapter.linkPrefetchingIsEnabledForLocation(location)
    }

    return true
  }

  // Visit delegate

  visitStarted(visit) {
    this.delegate.visitStarted(visit);
  }

  visitCompleted(visit) {
    this.delegate.visitCompleted(visit);
    delete this.currentVisit;
  }

  locationWithActionIsSamePage(location, action) {
    const anchor = getAnchor(location);
    const currentAnchor = getAnchor(this.view.lastRenderedLocation);
    const isRestorationToTop = action === "restore" && typeof anchor === "undefined";

    return (
      action !== "replace" &&
      getRequestURL(location) === getRequestURL(this.view.lastRenderedLocation) &&
      (isRestorationToTop || (anchor != null && anchor !== currentAnchor))
    )
  }

  visitScrolledToSamePageLocation(oldURL, newURL) {
    this.delegate.visitScrolledToSamePageLocation(oldURL, newURL);
  }

  // Visits

  get location() {
    return this.history.location
  }

  get restorationIdentifier() {
    return this.history.restorationIdentifier
  }

  #getActionForFormSubmission(formSubmission, fetchResponse) {
    const { submitter, formElement } = formSubmission;
    return getVisitAction(submitter, formElement) || this.#getDefaultAction(fetchResponse)
  }

  #getDefaultAction(fetchResponse) {
    const sameLocationRedirect = fetchResponse.redirected && fetchResponse.location.href === this.location?.href;
    return sameLocationRedirect ? "replace" : "advance"
  }
}

const PageStage = {
  initial: 0,
  loading: 1,
  interactive: 2,
  complete: 3
};

class PageObserver {
  stage = PageStage.initial
  started = false

  constructor(delegate) {
    this.delegate = delegate;
  }

  start() {
    if (!this.started) {
      if (this.stage == PageStage.initial) {
        this.stage = PageStage.loading;
      }
      document.addEventListener("readystatechange", this.interpretReadyState, false);
      addEventListener("pagehide", this.pageWillUnload, false);
      this.started = true;
    }
  }

  stop() {
    if (this.started) {
      document.removeEventListener("readystatechange", this.interpretReadyState, false);
      removeEventListener("pagehide", this.pageWillUnload, false);
      this.started = false;
    }
  }

  interpretReadyState = () => {
    const { readyState } = this;
    if (readyState == "interactive") {
      this.pageIsInteractive();
    } else if (readyState == "complete") {
      this.pageIsComplete();
    }
  }

  pageIsInteractive() {
    if (this.stage == PageStage.loading) {
      this.stage = PageStage.interactive;
      this.delegate.pageBecameInteractive();
    }
  }

  pageIsComplete() {
    this.pageIsInteractive();
    if (this.stage == PageStage.interactive) {
      this.stage = PageStage.complete;
      this.delegate.pageLoaded();
    }
  }

  pageWillUnload = () => {
    this.delegate.pageWillUnload();
  }

  get readyState() {
    return document.readyState
  }
}

class ScrollObserver {
  started = false

  constructor(delegate) {
    this.delegate = delegate;
  }

  start() {
    if (!this.started) {
      addEventListener("scroll", this.onScroll, false);
      this.onScroll();
      this.started = true;
    }
  }

  stop() {
    if (this.started) {
      removeEventListener("scroll", this.onScroll, false);
      this.started = false;
    }
  }

  onScroll = () => {
    this.updatePosition({ x: window.pageXOffset, y: window.pageYOffset });
  }

  // Private

  updatePosition(position) {
    this.delegate.scrollPositionChanged(position);
  }
}

class StreamMessageRenderer {
  render({ fragment }) {
    Bardo.preservingPermanentElements(this, getPermanentElementMapForFragment(fragment), () => {
      withAutofocusFromFragment(fragment, () => {
        withPreservedFocus(() => {
          document.documentElement.appendChild(fragment);
        });
      });
    });
  }

  // Bardo delegate

  enteringBardo(currentPermanentElement, newPermanentElement) {
    newPermanentElement.replaceWith(currentPermanentElement.cloneNode(true));
  }

  leavingBardo() {}
}

function getPermanentElementMapForFragment(fragment) {
  const permanentElementsInDocument = queryPermanentElementsAll(document.documentElement);
  const permanentElementMap = {};
  for (const permanentElementInDocument of permanentElementsInDocument) {
    const { id } = permanentElementInDocument;

    for (const streamElement of fragment.querySelectorAll("turbo-stream")) {
      const elementInStream = getPermanentElementById(streamElement.templateElement.content, id);

      if (elementInStream) {
        permanentElementMap[id] = [permanentElementInDocument, elementInStream];
      }
    }
  }

  return permanentElementMap
}

async function withAutofocusFromFragment(fragment, callback) {
  const generatedID = `turbo-stream-autofocus-${uuid()}`;
  const turboStreams = fragment.querySelectorAll("turbo-stream");
  const elementWithAutofocus = firstAutofocusableElementInStreams(turboStreams);
  let willAutofocusId = null;

  if (elementWithAutofocus) {
    if (elementWithAutofocus.id) {
      willAutofocusId = elementWithAutofocus.id;
    } else {
      willAutofocusId = generatedID;
    }

    elementWithAutofocus.id = willAutofocusId;
  }

  callback();
  await nextRepaint();

  const hasNoActiveElement = document.activeElement == null || document.activeElement == document.body;

  if (hasNoActiveElement && willAutofocusId) {
    const elementToAutofocus = document.getElementById(willAutofocusId);

    if (elementIsFocusable(elementToAutofocus)) {
      elementToAutofocus.focus();
    }
    if (elementToAutofocus && elementToAutofocus.id == generatedID) {
      elementToAutofocus.removeAttribute("id");
    }
  }
}

async function withPreservedFocus(callback) {
  const [activeElementBeforeRender, activeElementAfterRender] = await around(callback, () => document.activeElement);

  const restoreFocusTo = activeElementBeforeRender && activeElementBeforeRender.id;

  if (restoreFocusTo) {
    const elementToFocus = document.getElementById(restoreFocusTo);

    if (elementIsFocusable(elementToFocus) && elementToFocus != activeElementAfterRender) {
      elementToFocus.focus();
    }
  }
}

function firstAutofocusableElementInStreams(nodeListOfStreamElements) {
  for (const streamElement of nodeListOfStreamElements) {
    const elementWithAutofocus = queryAutofocusableElement(streamElement.templateElement.content);

    if (elementWithAutofocus) return elementWithAutofocus
  }

  return null
}

class StreamObserver {
  sources = new Set()
  #started = false

  constructor(delegate) {
    this.delegate = delegate;
  }

  start() {
    if (!this.#started) {
      this.#started = true;
      addEventListener("turbo:before-fetch-response", this.inspectFetchResponse, false);
    }
  }

  stop() {
    if (this.#started) {
      this.#started = false;
      removeEventListener("turbo:before-fetch-response", this.inspectFetchResponse, false);
    }
  }

  connectStreamSource(source) {
    if (!this.streamSourceIsConnected(source)) {
      this.sources.add(source);
      source.addEventListener("message", this.receiveMessageEvent, false);
    }
  }

  disconnectStreamSource(source) {
    if (this.streamSourceIsConnected(source)) {
      this.sources.delete(source);
      source.removeEventListener("message", this.receiveMessageEvent, false);
    }
  }

  streamSourceIsConnected(source) {
    return this.sources.has(source)
  }

  inspectFetchResponse = (event) => {
    const response = fetchResponseFromEvent(event);
    if (response && fetchResponseIsStream(response)) {
      event.preventDefault();
      this.receiveMessageResponse(response);
    }
  }

  receiveMessageEvent = (event) => {
    if (this.#started && typeof event.data == "string") {
      this.receiveMessageHTML(event.data);
    }
  }

  async receiveMessageResponse(response) {
    const html = await response.responseHTML;
    if (html) {
      this.receiveMessageHTML(html);
    }
  }

  receiveMessageHTML(html) {
    this.delegate.receivedMessageFromStream(StreamMessage.wrap(html));
  }
}

function fetchResponseFromEvent(event) {
  const fetchResponse = event.detail?.fetchResponse;
  if (fetchResponse instanceof FetchResponse) {
    return fetchResponse
  }
}

function fetchResponseIsStream(response) {
  const contentType = response.contentType ?? "";
  return contentType.startsWith(StreamMessage.contentType)
}

class ErrorRenderer extends Renderer {
  static renderElement(currentElement, newElement) {
    const { documentElement, body } = document;

    documentElement.replaceChild(newElement, body);
  }

  async render() {
    this.replaceHeadAndBody();
    this.activateScriptElements();
  }

  replaceHeadAndBody() {
    const { documentElement, head } = document;
    documentElement.replaceChild(this.newHead, head);
    this.renderElement(this.currentElement, this.newElement);
  }

  activateScriptElements() {
    for (const replaceableElement of this.scriptElements) {
      const parentNode = replaceableElement.parentNode;
      if (parentNode) {
        const element = activateScriptElement(replaceableElement);
        parentNode.replaceChild(element, replaceableElement);
      }
    }
  }

  get newHead() {
    return this.newSnapshot.headSnapshot.element
  }

  get scriptElements() {
    return document.documentElement.querySelectorAll("script")
  }
}

class PageRenderer extends Renderer {
  static renderElement(currentElement, newElement) {
    if (document.body && newElement instanceof HTMLBodyElement) {
      document.body.replaceWith(newElement);
    } else {
      document.documentElement.appendChild(newElement);
    }
  }

  get shouldRender() {
    return this.newSnapshot.isVisitable && this.trackedElementsAreIdentical
  }

  get reloadReason() {
    if (!this.newSnapshot.isVisitable) {
      return {
        reason: "turbo_visit_control_is_reload"
      }
    }

    if (!this.trackedElementsAreIdentical) {
      return {
        reason: "tracked_element_mismatch"
      }
    }
  }

  async prepareToRender() {
    this.#setLanguage();
    await this.mergeHead();
  }

  async render() {
    if (this.willRender) {
      await this.replaceBody();
    }
  }

  finishRendering() {
    super.finishRendering();
    if (!this.isPreview) {
      this.focusFirstAutofocusableElement();
    }
  }

  get currentHeadSnapshot() {
    return this.currentSnapshot.headSnapshot
  }

  get newHeadSnapshot() {
    return this.newSnapshot.headSnapshot
  }

  get newElement() {
    return this.newSnapshot.element
  }

  #setLanguage() {
    const { documentElement } = this.currentSnapshot;
    const { lang } = this.newSnapshot;

    if (lang) {
      documentElement.setAttribute("lang", lang);
    } else {
      documentElement.removeAttribute("lang");
    }
  }

  async mergeHead() {
    const mergedHeadElements = this.mergeProvisionalElements();
    const newStylesheetElements = this.copyNewHeadStylesheetElements();
    this.copyNewHeadScriptElements();

    await mergedHeadElements;
    await newStylesheetElements;

    if (this.willRender) {
      this.removeUnusedDynamicStylesheetElements();
    }
  }

  async replaceBody() {
    await this.preservingPermanentElements(async () => {
      this.activateNewBody();
      await this.assignNewBody();
    });
  }

  get trackedElementsAreIdentical() {
    return this.currentHeadSnapshot.trackedElementSignature == this.newHeadSnapshot.trackedElementSignature
  }

  async copyNewHeadStylesheetElements() {
    const loadingElements = [];

    for (const element of this.newHeadStylesheetElements) {
      loadingElements.push(waitForLoad(element));

      document.head.appendChild(element);
    }

    await Promise.all(loadingElements);
  }

  copyNewHeadScriptElements() {
    for (const element of this.newHeadScriptElements) {
      document.head.appendChild(activateScriptElement(element));
    }
  }

  removeUnusedDynamicStylesheetElements() {
    for (const element of this.unusedDynamicStylesheetElements) {
      document.head.removeChild(element);
    }
  }

  async mergeProvisionalElements() {
    const newHeadElements = [...this.newHeadProvisionalElements];

    for (const element of this.currentHeadProvisionalElements) {
      if (!this.isCurrentElementInElementList(element, newHeadElements)) {
        document.head.removeChild(element);
      }
    }

    for (const element of newHeadElements) {
      document.head.appendChild(element);
    }
  }

  isCurrentElementInElementList(element, elementList) {
    for (const [index, newElement] of elementList.entries()) {
      // if title element...
      if (element.tagName == "TITLE") {
        if (newElement.tagName != "TITLE") {
          continue
        }
        if (element.innerHTML == newElement.innerHTML) {
          elementList.splice(index, 1);
          return true
        }
      }

      // if any other element...
      if (newElement.isEqualNode(element)) {
        elementList.splice(index, 1);
        return true
      }
    }

    return false
  }

  removeCurrentHeadProvisionalElements() {
    for (const element of this.currentHeadProvisionalElements) {
      document.head.removeChild(element);
    }
  }

  copyNewHeadProvisionalElements() {
    for (const element of this.newHeadProvisionalElements) {
      document.head.appendChild(element);
    }
  }

  activateNewBody() {
    document.adoptNode(this.newElement);
    this.activateNewBodyScriptElements();
  }

  activateNewBodyScriptElements() {
    for (const inertScriptElement of this.newBodyScriptElements) {
      const activatedScriptElement = activateScriptElement(inertScriptElement);
      inertScriptElement.replaceWith(activatedScriptElement);
    }
  }

  async assignNewBody() {
    await this.renderElement(this.currentElement, this.newElement);
  }

  get unusedDynamicStylesheetElements() {
    return this.oldHeadStylesheetElements.filter((element) => {
      return element.getAttribute("data-turbo-track") === "dynamic"
    })
  }

  get oldHeadStylesheetElements() {
    return this.currentHeadSnapshot.getStylesheetElementsNotInSnapshot(this.newHeadSnapshot)
  }

  get newHeadStylesheetElements() {
    return this.newHeadSnapshot.getStylesheetElementsNotInSnapshot(this.currentHeadSnapshot)
  }

  get newHeadScriptElements() {
    return this.newHeadSnapshot.getScriptElementsNotInSnapshot(this.currentHeadSnapshot)
  }

  get currentHeadProvisionalElements() {
    return this.currentHeadSnapshot.provisionalElements
  }

  get newHeadProvisionalElements() {
    return this.newHeadSnapshot.provisionalElements
  }

  get newBodyScriptElements() {
    return this.newElement.querySelectorAll("script")
  }
}

class MorphingPageRenderer extends PageRenderer {
  static renderElement(currentElement, newElement) {
    morphElements(currentElement, newElement, {
      callbacks: {
        beforeNodeMorphed: (node, newNode) => {
          if (
            shouldRefreshFrameWithMorphing(node, newNode) &&
              !closestFrameReloadableWithMorphing(node)
          ) {
            node.reload();
            return false
          }
          return true
        }
      }
    });

    dispatch("turbo:morph", { detail: { currentElement, newElement } });
  }

  async preservingPermanentElements(callback) {
    return await callback()
  }

  get renderMethod() {
    return "morph"
  }

  get shouldAutofocus() {
    return false
  }
}

class SnapshotCache {
  keys = []
  snapshots = {}

  constructor(size) {
    this.size = size;
  }

  has(location) {
    return toCacheKey(location) in this.snapshots
  }

  get(location) {
    if (this.has(location)) {
      const snapshot = this.read(location);
      this.touch(location);
      return snapshot
    }
  }

  put(location, snapshot) {
    this.write(location, snapshot);
    this.touch(location);
    return snapshot
  }

  clear() {
    this.snapshots = {};
  }

  // Private

  read(location) {
    return this.snapshots[toCacheKey(location)]
  }

  write(location, snapshot) {
    this.snapshots[toCacheKey(location)] = snapshot;
  }

  touch(location) {
    const key = toCacheKey(location);
    const index = this.keys.indexOf(key);
    if (index > -1) this.keys.splice(index, 1);
    this.keys.unshift(key);
    this.trim();
  }

  trim() {
    for (const key of this.keys.splice(this.size)) {
      delete this.snapshots[key];
    }
  }
}

class PageView extends View {
  snapshotCache = new SnapshotCache(10)
  lastRenderedLocation = new URL(location.href)
  forceReloaded = false

  shouldTransitionTo(newSnapshot) {
    return this.snapshot.prefersViewTransitions && newSnapshot.prefersViewTransitions
  }

  renderPage(snapshot, isPreview = false, willRender = true, visit) {
    const shouldMorphPage = this.isPageRefresh(visit) && this.snapshot.shouldMorphPage;
    const rendererClass = shouldMorphPage ? MorphingPageRenderer : PageRenderer;

    const renderer = new rendererClass(this.snapshot, snapshot, isPreview, willRender);

    if (!renderer.shouldRender) {
      this.forceReloaded = true;
    } else {
      visit?.changeHistory();
    }

    return this.render(renderer)
  }

  renderError(snapshot, visit) {
    visit?.changeHistory();
    const renderer = new ErrorRenderer(this.snapshot, snapshot, false);
    return this.render(renderer)
  }

  clearSnapshotCache() {
    this.snapshotCache.clear();
  }

  async cacheSnapshot(snapshot = this.snapshot) {
    if (snapshot.isCacheable) {
      this.delegate.viewWillCacheSnapshot();
      const { lastRenderedLocation: location } = this;
      await nextEventLoopTick();
      const cachedSnapshot = snapshot.clone();
      this.snapshotCache.put(location, cachedSnapshot);
      return cachedSnapshot
    }
  }

  getCachedSnapshotForLocation(location) {
    return this.snapshotCache.get(location)
  }

  isPageRefresh(visit) {
    return !visit || (this.lastRenderedLocation.pathname === visit.location.pathname && visit.action === "replace")
  }

  shouldPreserveScrollPosition(visit) {
    return this.isPageRefresh(visit) && this.snapshot.shouldPreserveScrollPosition
  }

  get snapshot() {
    return PageSnapshot.fromElement(this.element)
  }
}

class Preloader {
  selector = "a[data-turbo-preload]"

  constructor(delegate, snapshotCache) {
    this.delegate = delegate;
    this.snapshotCache = snapshotCache;
  }

  start() {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", this.#preloadAll);
    } else {
      this.preloadOnLoadLinksForView(document.body);
    }
  }

  stop() {
    document.removeEventListener("DOMContentLoaded", this.#preloadAll);
  }

  preloadOnLoadLinksForView(element) {
    for (const link of element.querySelectorAll(this.selector)) {
      if (this.delegate.shouldPreloadLink(link)) {
        this.preloadURL(link);
      }
    }
  }

  async preloadURL(link) {
    const location = new URL(link.href);

    if (this.snapshotCache.has(location)) {
      return
    }

    const fetchRequest = new FetchRequest(this, FetchMethod.get, location, new URLSearchParams(), link);
    await fetchRequest.perform();
  }

  // Fetch request delegate

  prepareRequest(fetchRequest) {
    fetchRequest.headers["X-Sec-Purpose"] = "prefetch";
  }

  async requestSucceededWithResponse(fetchRequest, fetchResponse) {
    try {
      const responseHTML = await fetchResponse.responseHTML;
      const snapshot = PageSnapshot.fromHTMLString(responseHTML);

      this.snapshotCache.put(fetchRequest.url, snapshot);
    } catch (_) {
      // If we cannot preload that is ok!
    }
  }

  requestStarted(fetchRequest) {}

  requestErrored(fetchRequest) {}

  requestFinished(fetchRequest) {}

  requestPreventedHandlingResponse(fetchRequest, fetchResponse) {}

  requestFailedWithResponse(fetchRequest, fetchResponse) {}

  #preloadAll = () => {
    this.preloadOnLoadLinksForView(document.body);
  }
}

class Cache {
  constructor(session) {
    this.session = session;
  }

  clear() {
    this.session.clearCache();
  }

  resetCacheControl() {
    this.#setCacheControl("");
  }

  exemptPageFromCache() {
    this.#setCacheControl("no-cache");
  }

  exemptPageFromPreview() {
    this.#setCacheControl("no-preview");
  }

  #setCacheControl(value) {
    setMetaContent("turbo-cache-control", value);
  }
}

class Session {
  navigator = new Navigator(this)
  history = new History(this)
  view = new PageView(this, document.documentElement)
  adapter = new BrowserAdapter(this)

  pageObserver = new PageObserver(this)
  cacheObserver = new CacheObserver()
  linkPrefetchObserver = new LinkPrefetchObserver(this, document)
  linkClickObserver = new LinkClickObserver(this, window)
  formSubmitObserver = new FormSubmitObserver(this, document)
  scrollObserver = new ScrollObserver(this)
  streamObserver = new StreamObserver(this)
  formLinkClickObserver = new FormLinkClickObserver(this, document.documentElement)
  frameRedirector = new FrameRedirector(this, document.documentElement)
  streamMessageRenderer = new StreamMessageRenderer()
  cache = new Cache(this)

  enabled = true
  started = false
  #pageRefreshDebouncePeriod = 150

  constructor(recentRequests) {
    this.recentRequests = recentRequests;
    this.preloader = new Preloader(this, this.view.snapshotCache);
    this.debouncedRefresh = this.refresh;
    this.pageRefreshDebouncePeriod = this.pageRefreshDebouncePeriod;
  }

  start() {
    if (!this.started) {
      this.pageObserver.start();
      this.cacheObserver.start();
      this.linkPrefetchObserver.start();
      this.formLinkClickObserver.start();
      this.linkClickObserver.start();
      this.formSubmitObserver.start();
      this.scrollObserver.start();
      this.streamObserver.start();
      this.frameRedirector.start();
      this.history.start();
      this.preloader.start();
      this.started = true;
      this.enabled = true;
    }
  }

  disable() {
    this.enabled = false;
  }

  stop() {
    if (this.started) {
      this.pageObserver.stop();
      this.cacheObserver.stop();
      this.linkPrefetchObserver.stop();
      this.formLinkClickObserver.stop();
      this.linkClickObserver.stop();
      this.formSubmitObserver.stop();
      this.scrollObserver.stop();
      this.streamObserver.stop();
      this.frameRedirector.stop();
      this.history.stop();
      this.preloader.stop();
      this.started = false;
    }
  }

  registerAdapter(adapter) {
    this.adapter = adapter;
  }

  visit(location, options = {}) {
    const frameElement = options.frame ? document.getElementById(options.frame) : null;

    if (frameElement instanceof FrameElement) {
      const action = options.action || getVisitAction(frameElement);

      frameElement.delegate.proposeVisitIfNavigatedWithAction(frameElement, action);
      frameElement.src = location.toString();
    } else {
      this.navigator.proposeVisit(expandURL(location), options);
    }
  }

  refresh(url, requestId) {
    const isRecentRequest = requestId && this.recentRequests.has(requestId);
    const isCurrentUrl = url === document.baseURI;
    if (!isRecentRequest && !this.navigator.currentVisit && isCurrentUrl) {
      this.visit(url, { action: "replace", shouldCacheSnapshot: false });
    }
  }

  connectStreamSource(source) {
    this.streamObserver.connectStreamSource(source);
  }

  disconnectStreamSource(source) {
    this.streamObserver.disconnectStreamSource(source);
  }

  renderStreamMessage(message) {
    this.streamMessageRenderer.render(StreamMessage.wrap(message));
  }

  clearCache() {
    this.view.clearSnapshotCache();
  }

  setProgressBarDelay(delay) {
    console.warn(
      "Please replace `session.setProgressBarDelay(delay)` with `session.progressBarDelay = delay`. The function is deprecated and will be removed in a future version of Turbo.`"
    );

    this.progressBarDelay = delay;
  }

  set progressBarDelay(delay) {
    config.drive.progressBarDelay = delay;
  }

  get progressBarDelay() {
    return config.drive.progressBarDelay
  }

  set drive(value) {
    config.drive.enabled = value;
  }

  get drive() {
    return config.drive.enabled
  }

  set formMode(value) {
    config.forms.mode = value;
  }

  get formMode() {
    return config.forms.mode
  }

  get location() {
    return this.history.location
  }

  get restorationIdentifier() {
    return this.history.restorationIdentifier
  }

  get pageRefreshDebouncePeriod() {
    return this.#pageRefreshDebouncePeriod
  }

  set pageRefreshDebouncePeriod(value) {
    this.refresh = debounce(this.debouncedRefresh.bind(this), value);
    this.#pageRefreshDebouncePeriod = value;
  }

  // Preloader delegate

  shouldPreloadLink(element) {
    const isUnsafe = element.hasAttribute("data-turbo-method");
    const isStream = element.hasAttribute("data-turbo-stream");
    const frameTarget = element.getAttribute("data-turbo-frame");
    const frame = frameTarget == "_top" ?
      null :
      document.getElementById(frameTarget) || findClosestRecursively(element, "turbo-frame:not([disabled])");

    if (isUnsafe || isStream || frame instanceof FrameElement) {
      return false
    } else {
      const location = new URL(element.href);

      return this.elementIsNavigatable(element) && locationIsVisitable(location, this.snapshot.rootLocation)
    }
  }

  // History delegate

  historyPoppedToLocationWithRestorationIdentifierAndDirection(location, restorationIdentifier, direction) {
    if (this.enabled) {
      this.navigator.startVisit(location, restorationIdentifier, {
        action: "restore",
        historyChanged: true,
        direction
      });
    } else {
      this.adapter.pageInvalidated({
        reason: "turbo_disabled"
      });
    }
  }

  // Scroll observer delegate

  scrollPositionChanged(position) {
    this.history.updateRestorationData({ scrollPosition: position });
  }

  // Form click observer delegate

  willSubmitFormLinkToLocation(link, location) {
    return this.elementIsNavigatable(link) && locationIsVisitable(location, this.snapshot.rootLocation)
  }

  submittedFormLinkToLocation() {}

  // Link hover observer delegate

  canPrefetchRequestToLocation(link, location) {
    return (
      this.elementIsNavigatable(link) &&
      locationIsVisitable(location, this.snapshot.rootLocation) &&
      this.navigator.linkPrefetchingIsEnabledForLocation(location)
    )
  }

  // Link click observer delegate

  willFollowLinkToLocation(link, location, event) {
    return (
      this.elementIsNavigatable(link) &&
      locationIsVisitable(location, this.snapshot.rootLocation) &&
      this.applicationAllowsFollowingLinkToLocation(link, location, event)
    )
  }

  followedLinkToLocation(link, location) {
    const action = this.getActionForLink(link);
    const acceptsStreamResponse = link.hasAttribute("data-turbo-stream");

    this.visit(location.href, { action, acceptsStreamResponse });
  }

  // Navigator delegate

  allowsVisitingLocationWithAction(location, action) {
    return this.locationWithActionIsSamePage(location, action) || this.applicationAllowsVisitingLocation(location)
  }

  visitProposedToLocation(location, options) {
    extendURLWithDeprecatedProperties(location);
    this.adapter.visitProposedToLocation(location, options);
  }

  // Visit delegate

  visitStarted(visit) {
    if (!visit.acceptsStreamResponse) {
      markAsBusy(document.documentElement);
      this.view.markVisitDirection(visit.direction);
    }
    extendURLWithDeprecatedProperties(visit.location);
    if (!visit.silent) {
      this.notifyApplicationAfterVisitingLocation(visit.location, visit.action);
    }
  }

  visitCompleted(visit) {
    this.view.unmarkVisitDirection();
    clearBusyState(document.documentElement);
    this.notifyApplicationAfterPageLoad(visit.getTimingMetrics());
  }

  locationWithActionIsSamePage(location, action) {
    return this.navigator.locationWithActionIsSamePage(location, action)
  }

  visitScrolledToSamePageLocation(oldURL, newURL) {
    this.notifyApplicationAfterVisitingSamePageLocation(oldURL, newURL);
  }

  // Form submit observer delegate

  willSubmitForm(form, submitter) {
    const action = getAction$1(form, submitter);

    return (
      this.submissionIsNavigatable(form, submitter) &&
      locationIsVisitable(expandURL(action), this.snapshot.rootLocation)
    )
  }

  formSubmitted(form, submitter) {
    this.navigator.submitForm(form, submitter);
  }

  // Page observer delegate

  pageBecameInteractive() {
    this.view.lastRenderedLocation = this.location;
    this.notifyApplicationAfterPageLoad();
  }

  pageLoaded() {
    this.history.assumeControlOfScrollRestoration();
  }

  pageWillUnload() {
    this.history.relinquishControlOfScrollRestoration();
  }

  // Stream observer delegate

  receivedMessageFromStream(message) {
    this.renderStreamMessage(message);
  }

  // Page view delegate

  viewWillCacheSnapshot() {
    if (!this.navigator.currentVisit?.silent) {
      this.notifyApplicationBeforeCachingSnapshot();
    }
  }

  allowsImmediateRender({ element }, options) {
    const event = this.notifyApplicationBeforeRender(element, options);
    const {
      defaultPrevented,
      detail: { render }
    } = event;

    if (this.view.renderer && render) {
      this.view.renderer.renderElement = render;
    }

    return !defaultPrevented
  }

  viewRenderedSnapshot(_snapshot, _isPreview, renderMethod) {
    this.view.lastRenderedLocation = this.history.location;
    this.notifyApplicationAfterRender(renderMethod);
  }

  preloadOnLoadLinksForView(element) {
    this.preloader.preloadOnLoadLinksForView(element);
  }

  viewInvalidated(reason) {
    this.adapter.pageInvalidated(reason);
  }

  // Frame element

  frameLoaded(frame) {
    this.notifyApplicationAfterFrameLoad(frame);
  }

  frameRendered(fetchResponse, frame) {
    this.notifyApplicationAfterFrameRender(fetchResponse, frame);
  }

  // Application events

  applicationAllowsFollowingLinkToLocation(link, location, ev) {
    const event = this.notifyApplicationAfterClickingLinkToLocation(link, location, ev);
    return !event.defaultPrevented
  }

  applicationAllowsVisitingLocation(location) {
    const event = this.notifyApplicationBeforeVisitingLocation(location);
    return !event.defaultPrevented
  }

  notifyApplicationAfterClickingLinkToLocation(link, location, event) {
    return dispatch("turbo:click", {
      target: link,
      detail: { url: location.href, originalEvent: event },
      cancelable: true
    })
  }

  notifyApplicationBeforeVisitingLocation(location) {
    return dispatch("turbo:before-visit", {
      detail: { url: location.href },
      cancelable: true
    })
  }

  notifyApplicationAfterVisitingLocation(location, action) {
    return dispatch("turbo:visit", { detail: { url: location.href, action } })
  }

  notifyApplicationBeforeCachingSnapshot() {
    return dispatch("turbo:before-cache")
  }

  notifyApplicationBeforeRender(newBody, options) {
    return dispatch("turbo:before-render", {
      detail: { newBody, ...options },
      cancelable: true
    })
  }

  notifyApplicationAfterRender(renderMethod) {
    return dispatch("turbo:render", { detail: { renderMethod } })
  }

  notifyApplicationAfterPageLoad(timing = {}) {
    return dispatch("turbo:load", {
      detail: { url: this.location.href, timing }
    })
  }

  notifyApplicationAfterVisitingSamePageLocation(oldURL, newURL) {
    dispatchEvent(
      new HashChangeEvent("hashchange", {
        oldURL: oldURL.toString(),
        newURL: newURL.toString()
      })
    );
  }

  notifyApplicationAfterFrameLoad(frame) {
    return dispatch("turbo:frame-load", { target: frame })
  }

  notifyApplicationAfterFrameRender(fetchResponse, frame) {
    return dispatch("turbo:frame-render", {
      detail: { fetchResponse },
      target: frame,
      cancelable: true
    })
  }

  // Helpers

  submissionIsNavigatable(form, submitter) {
    if (config.forms.mode == "off") {
      return false
    } else {
      const submitterIsNavigatable = submitter ? this.elementIsNavigatable(submitter) : true;

      if (config.forms.mode == "optin") {
        return submitterIsNavigatable && form.closest('[data-turbo="true"]') != null
      } else {
        return submitterIsNavigatable && this.elementIsNavigatable(form)
      }
    }
  }

  elementIsNavigatable(element) {
    const container = findClosestRecursively(element, "[data-turbo]");
    const withinFrame = findClosestRecursively(element, "turbo-frame");

    // Check if Drive is enabled on the session or we're within a Frame.
    if (config.drive.enabled || withinFrame) {
      // Element is navigatable by default, unless `data-turbo="false"`.
      if (container) {
        return container.getAttribute("data-turbo") != "false"
      } else {
        return true
      }
    } else {
      // Element isn't navigatable by default, unless `data-turbo="true"`.
      if (container) {
        return container.getAttribute("data-turbo") == "true"
      } else {
        return false
      }
    }
  }

  // Private

  getActionForLink(link) {
    return getVisitAction(link) || "advance"
  }

  get snapshot() {
    return this.view.snapshot
  }
}

// Older versions of the Turbo Native adapters referenced the
// `Location#absoluteURL` property in their implementations of
// the `Adapter#visitProposedToLocation()` and `#visitStarted()`
// methods. The Location class has since been removed in favor
// of the DOM URL API, and accordingly all Adapter methods now
// receive URL objects.
//
// We alias #absoluteURL to #toString() here to avoid crashing
// older adapters which do not expect URL objects. We should
// consider removing this support at some point in the future.

function extendURLWithDeprecatedProperties(url) {
  Object.defineProperties(url, deprecatedLocationPropertyDescriptors);
}

const deprecatedLocationPropertyDescriptors = {
  absoluteURL: {
    get() {
      return this.toString()
    }
  }
};

const session = new Session(recentRequests);
const { cache, navigator: navigator$1 } = session;

/**
 * Starts the main session.
 * This initialises any necessary observers such as those to monitor
 * link interactions.
 */
function start() {
  session.start();
}

/**
 * Registers an adapter for the main session.
 *
 * @param adapter Adapter to register
 */
function registerAdapter(adapter) {
  session.registerAdapter(adapter);
}

/**
 * Performs an application visit to the given location.
 *
 * @param location Location to visit (a URL or path)
 * @param options Options to apply
 * @param options.action Type of history navigation to apply ("restore",
 * "replace" or "advance")
 * @param options.historyChanged Specifies whether the browser history has
 * already been changed for this visit or not
 * @param options.referrer Specifies the referrer of this visit such that
 * navigations to the same page will not result in a new history entry.
 * @param options.snapshotHTML Cached snapshot to render
 * @param options.response Response of the specified location
 */
function visit(location, options) {
  session.visit(location, options);
}

/**
 * Connects a stream source to the main session.
 *
 * @param source Stream source to connect
 */
function connectStreamSource(source) {
  session.connectStreamSource(source);
}

/**
 * Disconnects a stream source from the main session.
 *
 * @param source Stream source to disconnect
 */
function disconnectStreamSource(source) {
  session.disconnectStreamSource(source);
}

/**
 * Renders a stream message to the main session by appending it to the
 * current document.
 *
 * @param message Message to render
 */
function renderStreamMessage(message) {
  session.renderStreamMessage(message);
}

/**
 * Removes all entries from the Turbo Drive page cache.
 * Call this when state has changed on the server that may affect cached pages.
 *
 * @deprecated since version 7.2.0 in favor of `Turbo.cache.clear()`
 */
function clearCache() {
  console.warn(
    "Please replace `Turbo.clearCache()` with `Turbo.cache.clear()`. The top-level function is deprecated and will be removed in a future version of Turbo.`"
  );
  session.clearCache();
}

/**
 * Sets the delay after which the progress bar will appear during navigation.
 *
 * The progress bar appears after 500ms by default.
 *
 * Note that this method has no effect when used with the iOS or Android
 * adapters.
 *
 * @param delay Time to delay in milliseconds
 */
function setProgressBarDelay(delay) {
  console.warn(
    "Please replace `Turbo.setProgressBarDelay(delay)` with `Turbo.config.drive.progressBarDelay = delay`. The top-level function is deprecated and will be removed in a future version of Turbo.`"
  );
  config.drive.progressBarDelay = delay;
}

function setConfirmMethod(confirmMethod) {
  console.warn(
    "Please replace `Turbo.setConfirmMethod(confirmMethod)` with `Turbo.config.forms.confirm = confirmMethod`. The top-level function is deprecated and will be removed in a future version of Turbo.`"
  );
  config.forms.confirm = confirmMethod;
}

function setFormMode(mode) {
  console.warn(
    "Please replace `Turbo.setFormMode(mode)` with `Turbo.config.forms.mode = mode`. The top-level function is deprecated and will be removed in a future version of Turbo.`"
  );
  config.forms.mode = mode;
}

/**
 * Morph the state of the currentBody based on the attributes and contents of
 * the newBody. Morphing body elements may dispatch turbo:morph,
 * turbo:before-morph-element, turbo:before-morph-attribute, and
 * turbo:morph-element events.
 *
 * @param currentBody HTMLBodyElement destination of morphing changes
 * @param newBody HTMLBodyElement source of morphing changes
 */
function morphBodyElements(currentBody, newBody) {
  MorphingPageRenderer.renderElement(currentBody, newBody);
}

/**
 * Morph the child elements of the currentFrame based on the child elements of
 * the newFrame. Morphing turbo-frame elements may dispatch turbo:before-frame-morph,
 * turbo:before-morph-element, turbo:before-morph-attribute, and
 * turbo:morph-element events.
 *
 * @param currentFrame FrameElement destination of morphing children changes
 * @param newFrame FrameElement source of morphing children changes
 */
function morphTurboFrameElements(currentFrame, newFrame) {
  MorphingFrameRenderer.renderElement(currentFrame, newFrame);
}

var Turbo = /*#__PURE__*/Object.freeze({
  __proto__: null,
  navigator: navigator$1,
  session: session,
  cache: cache,
  PageRenderer: PageRenderer,
  PageSnapshot: PageSnapshot,
  FrameRenderer: FrameRenderer,
  fetch: fetchWithTurboHeaders,
  config: config,
  start: start,
  registerAdapter: registerAdapter,
  visit: visit,
  connectStreamSource: connectStreamSource,
  disconnectStreamSource: disconnectStreamSource,
  renderStreamMessage: renderStreamMessage,
  clearCache: clearCache,
  setProgressBarDelay: setProgressBarDelay,
  setConfirmMethod: setConfirmMethod,
  setFormMode: setFormMode,
  morphBodyElements: morphBodyElements,
  morphTurboFrameElements: morphTurboFrameElements,
  morphChildren: morphChildren,
  morphElements: morphElements
});

class TurboFrameMissingError extends Error {}

class FrameController {
  fetchResponseLoaded = (_fetchResponse) => Promise.resolve()
  #currentFetchRequest = null
  #resolveVisitPromise = () => {}
  #connected = false
  #hasBeenLoaded = false
  #ignoredAttributes = new Set()
  #shouldMorphFrame = false
  action = null

  constructor(element) {
    this.element = element;
    this.view = new FrameView(this, this.element);
    this.appearanceObserver = new AppearanceObserver(this, this.element);
    this.formLinkClickObserver = new FormLinkClickObserver(this, this.element);
    this.linkInterceptor = new LinkInterceptor(this, this.element);
    this.restorationIdentifier = uuid();
    this.formSubmitObserver = new FormSubmitObserver(this, this.element);
  }

  // Frame delegate

  connect() {
    if (!this.#connected) {
      this.#connected = true;
      if (this.loadingStyle == FrameLoadingStyle.lazy) {
        this.appearanceObserver.start();
      } else {
        this.#loadSourceURL();
      }
      this.formLinkClickObserver.start();
      this.linkInterceptor.start();
      this.formSubmitObserver.start();
    }
  }

  disconnect() {
    if (this.#connected) {
      this.#connected = false;
      this.appearanceObserver.stop();
      this.formLinkClickObserver.stop();
      this.linkInterceptor.stop();
      this.formSubmitObserver.stop();
    }
  }

  disabledChanged() {
    if (this.loadingStyle == FrameLoadingStyle.eager) {
      this.#loadSourceURL();
    }
  }

  sourceURLChanged() {
    if (this.#isIgnoringChangesTo("src")) return

    if (this.element.isConnected) {
      this.complete = false;
    }

    if (this.loadingStyle == FrameLoadingStyle.eager || this.#hasBeenLoaded) {
      this.#loadSourceURL();
    }
  }

  sourceURLReloaded() {
    const { refresh, src } = this.element;

    this.#shouldMorphFrame = src && refresh === "morph";

    this.element.removeAttribute("complete");
    this.element.src = null;
    this.element.src = src;
    return this.element.loaded
  }

  loadingStyleChanged() {
    if (this.loadingStyle == FrameLoadingStyle.lazy) {
      this.appearanceObserver.start();
    } else {
      this.appearanceObserver.stop();
      this.#loadSourceURL();
    }
  }

  async #loadSourceURL() {
    if (this.enabled && this.isActive && !this.complete && this.sourceURL) {
      this.element.loaded = this.#visit(expandURL(this.sourceURL));
      this.appearanceObserver.stop();
      await this.element.loaded;
      this.#hasBeenLoaded = true;
    }
  }

  async loadResponse(fetchResponse) {
    if (fetchResponse.redirected || (fetchResponse.succeeded && fetchResponse.isHTML)) {
      this.sourceURL = fetchResponse.response.url;
    }

    try {
      const html = await fetchResponse.responseHTML;
      if (html) {
        const document = parseHTMLDocument(html);
        const pageSnapshot = PageSnapshot.fromDocument(document);

        if (pageSnapshot.isVisitable) {
          await this.#loadFrameResponse(fetchResponse, document);
        } else {
          await this.#handleUnvisitableFrameResponse(fetchResponse);
        }
      }
    } finally {
      this.#shouldMorphFrame = false;
      this.fetchResponseLoaded = () => Promise.resolve();
    }
  }

  // Appearance observer delegate

  elementAppearedInViewport(element) {
    this.proposeVisitIfNavigatedWithAction(element, getVisitAction(element));
    this.#loadSourceURL();
  }

  // Form link click observer delegate

  willSubmitFormLinkToLocation(link) {
    return this.#shouldInterceptNavigation(link)
  }

  submittedFormLinkToLocation(link, _location, form) {
    const frame = this.#findFrameElement(link);
    if (frame) form.setAttribute("data-turbo-frame", frame.id);
  }

  // Link interceptor delegate

  shouldInterceptLinkClick(element, _location, _event) {
    return this.#shouldInterceptNavigation(element)
  }

  linkClickIntercepted(element, location) {
    this.#navigateFrame(element, location);
  }

  // Form submit observer delegate

  willSubmitForm(element, submitter) {
    return element.closest("turbo-frame") == this.element && this.#shouldInterceptNavigation(element, submitter)
  }

  formSubmitted(element, submitter) {
    if (this.formSubmission) {
      this.formSubmission.stop();
    }

    this.formSubmission = new FormSubmission(this, element, submitter);
    const { fetchRequest } = this.formSubmission;
    this.prepareRequest(fetchRequest);
    this.formSubmission.start();
  }

  // Fetch request delegate

  prepareRequest(request) {
    request.headers["Turbo-Frame"] = this.id;

    if (this.currentNavigationElement?.hasAttribute("data-turbo-stream")) {
      request.acceptResponseType(StreamMessage.contentType);
    }
  }

  requestStarted(_request) {
    markAsBusy(this.element);
  }

  requestPreventedHandlingResponse(_request, _response) {
    this.#resolveVisitPromise();
  }

  async requestSucceededWithResponse(request, response) {
    await this.loadResponse(response);
    this.#resolveVisitPromise();
  }

  async requestFailedWithResponse(request, response) {
    await this.loadResponse(response);
    this.#resolveVisitPromise();
  }

  requestErrored(request, error) {
    console.error(error);
    this.#resolveVisitPromise();
  }

  requestFinished(_request) {
    clearBusyState(this.element);
  }

  // Form submission delegate

  formSubmissionStarted({ formElement }) {
    markAsBusy(formElement, this.#findFrameElement(formElement));
  }

  formSubmissionSucceededWithResponse(formSubmission, response) {
    const frame = this.#findFrameElement(formSubmission.formElement, formSubmission.submitter);

    frame.delegate.proposeVisitIfNavigatedWithAction(frame, getVisitAction(formSubmission.submitter, formSubmission.formElement, frame));
    frame.delegate.loadResponse(response);

    if (!formSubmission.isSafe) {
      session.clearCache();
    }
  }

  formSubmissionFailedWithResponse(formSubmission, fetchResponse) {
    this.element.delegate.loadResponse(fetchResponse);
    session.clearCache();
  }

  formSubmissionErrored(formSubmission, error) {
    console.error(error);
  }

  formSubmissionFinished({ formElement }) {
    clearBusyState(formElement, this.#findFrameElement(formElement));
  }

  // View delegate

  allowsImmediateRender({ element: newFrame }, options) {
    const event = dispatch("turbo:before-frame-render", {
      target: this.element,
      detail: { newFrame, ...options },
      cancelable: true
    });

    const {
      defaultPrevented,
      detail: { render }
    } = event;

    if (this.view.renderer && render) {
      this.view.renderer.renderElement = render;
    }

    return !defaultPrevented
  }

  viewRenderedSnapshot(_snapshot, _isPreview, _renderMethod) {}

  preloadOnLoadLinksForView(element) {
    session.preloadOnLoadLinksForView(element);
  }

  viewInvalidated() {}

  // Frame renderer delegate

  willRenderFrame(currentElement, _newElement) {
    this.previousFrameElement = currentElement.cloneNode(true);
  }

  visitCachedSnapshot = ({ element }) => {
    const frame = element.querySelector("#" + this.element.id);

    if (frame && this.previousFrameElement) {
      frame.replaceChildren(...this.previousFrameElement.children);
    }

    delete this.previousFrameElement;
  }

  // Private

  async #loadFrameResponse(fetchResponse, document) {
    const newFrameElement = await this.extractForeignFrameElement(document.body);
    const rendererClass = this.#shouldMorphFrame ? MorphingFrameRenderer : FrameRenderer;

    if (newFrameElement) {
      const snapshot = new Snapshot(newFrameElement);
      const renderer = new rendererClass(this, this.view.snapshot, snapshot, false, false);
      if (this.view.renderPromise) await this.view.renderPromise;
      this.changeHistory();

      await this.view.render(renderer);
      this.complete = true;
      session.frameRendered(fetchResponse, this.element);
      session.frameLoaded(this.element);
      await this.fetchResponseLoaded(fetchResponse);
    } else if (this.#willHandleFrameMissingFromResponse(fetchResponse)) {
      this.#handleFrameMissingFromResponse(fetchResponse);
    }
  }

  async #visit(url) {
    const request = new FetchRequest(this, FetchMethod.get, url, new URLSearchParams(), this.element);

    this.#currentFetchRequest?.cancel();
    this.#currentFetchRequest = request;

    return new Promise((resolve) => {
      this.#resolveVisitPromise = () => {
        this.#resolveVisitPromise = () => {};
        this.#currentFetchRequest = null;
        resolve();
      };
      request.perform();
    })
  }

  #navigateFrame(element, url, submitter) {
    const frame = this.#findFrameElement(element, submitter);

    frame.delegate.proposeVisitIfNavigatedWithAction(frame, getVisitAction(submitter, element, frame));

    this.#withCurrentNavigationElement(element, () => {
      frame.src = url;
    });
  }

  proposeVisitIfNavigatedWithAction(frame, action = null) {
    this.action = action;

    if (this.action) {
      const pageSnapshot = PageSnapshot.fromElement(frame).clone();
      const { visitCachedSnapshot } = frame.delegate;

      frame.delegate.fetchResponseLoaded = async (fetchResponse) => {
        if (frame.src) {
          const { statusCode, redirected } = fetchResponse;
          const responseHTML = await fetchResponse.responseHTML;
          const response = { statusCode, redirected, responseHTML };
          const options = {
            response,
            visitCachedSnapshot,
            willRender: false,
            updateHistory: false,
            restorationIdentifier: this.restorationIdentifier,
            snapshot: pageSnapshot
          };

          if (this.action) options.action = this.action;

          session.visit(frame.src, options);
        }
      };
    }
  }

  changeHistory() {
    if (this.action) {
      const method = getHistoryMethodForAction(this.action);
      session.history.update(method, expandURL(this.element.src || ""), this.restorationIdentifier);
    }
  }

  async #handleUnvisitableFrameResponse(fetchResponse) {
    console.warn(
      `The response (${fetchResponse.statusCode}) from <turbo-frame id="${this.element.id}"> is performing a full page visit due to turbo-visit-control.`
    );

    await this.#visitResponse(fetchResponse.response);
  }

  #willHandleFrameMissingFromResponse(fetchResponse) {
    this.element.setAttribute("complete", "");

    const response = fetchResponse.response;
    const visit = async (url, options) => {
      if (url instanceof Response) {
        this.#visitResponse(url);
      } else {
        session.visit(url, options);
      }
    };

    const event = dispatch("turbo:frame-missing", {
      target: this.element,
      detail: { response, visit },
      cancelable: true
    });

    return !event.defaultPrevented
  }

  #handleFrameMissingFromResponse(fetchResponse) {
    this.view.missing();
    this.#throwFrameMissingError(fetchResponse);
  }

  #throwFrameMissingError(fetchResponse) {
    const message = `The response (${fetchResponse.statusCode}) did not contain the expected <turbo-frame id="${this.element.id}"> and will be ignored. To perform a full page visit instead, set turbo-visit-control to reload.`;
    throw new TurboFrameMissingError(message)
  }

  async #visitResponse(response) {
    const wrapped = new FetchResponse(response);
    const responseHTML = await wrapped.responseHTML;
    const { location, redirected, statusCode } = wrapped;

    return session.visit(location, { response: { redirected, statusCode, responseHTML } })
  }

  #findFrameElement(element, submitter) {
    const id = getAttribute("data-turbo-frame", submitter, element) || this.element.getAttribute("target");
    return getFrameElementById(id) ?? this.element
  }

  async extractForeignFrameElement(container) {
    let element;
    const id = CSS.escape(this.id);

    try {
      element = activateElement(container.querySelector(`turbo-frame#${id}`), this.sourceURL);
      if (element) {
        return element
      }

      element = activateElement(container.querySelector(`turbo-frame[src][recurse~=${id}]`), this.sourceURL);
      if (element) {
        await element.loaded;
        return await this.extractForeignFrameElement(element)
      }
    } catch (error) {
      console.error(error);
      return new FrameElement()
    }

    return null
  }

  #formActionIsVisitable(form, submitter) {
    const action = getAction$1(form, submitter);

    return locationIsVisitable(expandURL(action), this.rootLocation)
  }

  #shouldInterceptNavigation(element, submitter) {
    const id = getAttribute("data-turbo-frame", submitter, element) || this.element.getAttribute("target");

    if (element instanceof HTMLFormElement && !this.#formActionIsVisitable(element, submitter)) {
      return false
    }

    if (!this.enabled || id == "_top") {
      return false
    }

    if (id) {
      const frameElement = getFrameElementById(id);
      if (frameElement) {
        return !frameElement.disabled
      }
    }

    if (!session.elementIsNavigatable(element)) {
      return false
    }

    if (submitter && !session.elementIsNavigatable(submitter)) {
      return false
    }

    return true
  }

  // Computed properties

  get id() {
    return this.element.id
  }

  get enabled() {
    return !this.element.disabled
  }

  get sourceURL() {
    if (this.element.src) {
      return this.element.src
    }
  }

  set sourceURL(sourceURL) {
    this.#ignoringChangesToAttribute("src", () => {
      this.element.src = sourceURL ?? null;
    });
  }

  get loadingStyle() {
    return this.element.loading
  }

  get isLoading() {
    return this.formSubmission !== undefined || this.#resolveVisitPromise() !== undefined
  }

  get complete() {
    return this.element.hasAttribute("complete")
  }

  set complete(value) {
    if (value) {
      this.element.setAttribute("complete", "");
    } else {
      this.element.removeAttribute("complete");
    }
  }

  get isActive() {
    return this.element.isActive && this.#connected
  }

  get rootLocation() {
    const meta = this.element.ownerDocument.querySelector(`meta[name="turbo-root"]`);
    const root = meta?.content ?? "/";
    return expandURL(root)
  }

  #isIgnoringChangesTo(attributeName) {
    return this.#ignoredAttributes.has(attributeName)
  }

  #ignoringChangesToAttribute(attributeName, callback) {
    this.#ignoredAttributes.add(attributeName);
    callback();
    this.#ignoredAttributes.delete(attributeName);
  }

  #withCurrentNavigationElement(element, callback) {
    this.currentNavigationElement = element;
    callback();
    delete this.currentNavigationElement;
  }
}

function getFrameElementById(id) {
  if (id != null) {
    const element = document.getElementById(id);
    if (element instanceof FrameElement) {
      return element
    }
  }
}

function activateElement(element, currentURL) {
  if (element) {
    const src = element.getAttribute("src");
    if (src != null && currentURL != null && urlsAreEqual(src, currentURL)) {
      throw new Error(`Matching <turbo-frame id="${element.id}"> element has a source URL which references itself`)
    }
    if (element.ownerDocument !== document) {
      element = document.importNode(element, true);
    }

    if (element instanceof FrameElement) {
      element.connectedCallback();
      element.disconnectedCallback();
      return element
    }
  }
}

const StreamActions = {
  after() {
    this.targetElements.forEach((e) => e.parentElement?.insertBefore(this.templateContent, e.nextSibling));
  },

  append() {
    this.removeDuplicateTargetChildren();
    this.targetElements.forEach((e) => e.append(this.templateContent));
  },

  before() {
    this.targetElements.forEach((e) => e.parentElement?.insertBefore(this.templateContent, e));
  },

  prepend() {
    this.removeDuplicateTargetChildren();
    this.targetElements.forEach((e) => e.prepend(this.templateContent));
  },

  remove() {
    this.targetElements.forEach((e) => e.remove());
  },

  replace() {
    const method = this.getAttribute("method");

    this.targetElements.forEach((targetElement) => {
      if (method === "morph") {
        morphElements(targetElement, this.templateContent);
      } else {
        targetElement.replaceWith(this.templateContent);
      }
    });
  },

  update() {
    const method = this.getAttribute("method");

    this.targetElements.forEach((targetElement) => {
      if (method === "morph") {
        morphChildren(targetElement, this.templateContent);
      } else {
        targetElement.innerHTML = "";
        targetElement.append(this.templateContent);
      }
    });
  },

  refresh() {
    session.refresh(this.baseURI, this.requestId);
  }
};

// <turbo-stream action=replace target=id><template>...

/**
 * Renders updates to the page from a stream of messages.
 *
 * Using the `action` attribute, this can be configured one of eight ways:
 *
 * - `after` - inserts the result after the target
 * - `append` - appends the result to the target
 * - `before` - inserts the result before the target
 * - `prepend` - prepends the result to the target
 * - `refresh` - initiates a page refresh
 * - `remove` - removes the target
 * - `replace` - replaces the outer HTML of the target
 * - `update` - replaces the inner HTML of the target
 *
 * @customElement turbo-stream
 * @example
 *   <turbo-stream action="append" target="dom_id">
 *     <template>
 *       Content to append to target designated with the dom_id.
 *     </template>
 *   </turbo-stream>
 */
class StreamElement extends HTMLElement {
  static async renderElement(newElement) {
    await newElement.performAction();
  }

  async connectedCallback() {
    try {
      await this.render();
    } catch (error) {
      console.error(error);
    } finally {
      this.disconnect();
    }
  }

  async render() {
    return (this.renderPromise ??= (async () => {
      const event = this.beforeRenderEvent;

      if (this.dispatchEvent(event)) {
        await nextRepaint();
        await event.detail.render(this);
      }
    })())
  }

  disconnect() {
    try {
      this.remove();
      // eslint-disable-next-line no-empty
    } catch {}
  }

  /**
   * Removes duplicate children (by ID)
   */
  removeDuplicateTargetChildren() {
    this.duplicateChildren.forEach((c) => c.remove());
  }

  /**
   * Gets the list of duplicate children (i.e. those with the same ID)
   */
  get duplicateChildren() {
    const existingChildren = this.targetElements.flatMap((e) => [...e.children]).filter((c) => !!c.getAttribute("id"));
    const newChildrenIds = [...(this.templateContent?.children || [])].filter((c) => !!c.getAttribute("id")).map((c) => c.getAttribute("id"));

    return existingChildren.filter((c) => newChildrenIds.includes(c.getAttribute("id")))
  }

  /**
   * Gets the action function to be performed.
   */
  get performAction() {
    if (this.action) {
      const actionFunction = StreamActions[this.action];
      if (actionFunction) {
        return actionFunction
      }
      this.#raise("unknown action");
    }
    this.#raise("action attribute is missing");
  }

  /**
   * Gets the target elements which the template will be rendered to.
   */
  get targetElements() {
    if (this.target) {
      return this.targetElementsById
    } else if (this.targets) {
      return this.targetElementsByQuery
    } else {
      this.#raise("target or targets attribute is missing");
    }
  }

  /**
   * Gets the contents of the main `<template>`.
   */
  get templateContent() {
    return this.templateElement.content.cloneNode(true)
  }

  /**
   * Gets the main `<template>` used for rendering
   */
  get templateElement() {
    if (this.firstElementChild === null) {
      const template = this.ownerDocument.createElement("template");
      this.appendChild(template);
      return template
    } else if (this.firstElementChild instanceof HTMLTemplateElement) {
      return this.firstElementChild
    }
    this.#raise("first child element must be a <template> element");
  }

  /**
   * Gets the current action.
   */
  get action() {
    return this.getAttribute("action")
  }

  /**
   * Gets the current target (an element ID) to which the result will
   * be rendered.
   */
  get target() {
    return this.getAttribute("target")
  }

  /**
   * Gets the current "targets" selector (a CSS selector)
   */
  get targets() {
    return this.getAttribute("targets")
  }

  /**
   * Reads the request-id attribute
   */
  get requestId() {
    return this.getAttribute("request-id")
  }

  #raise(message) {
    throw new Error(`${this.description}: ${message}`)
  }

  get description() {
    return (this.outerHTML.match(/<[^>]+>/) ?? [])[0] ?? "<turbo-stream>"
  }

  get beforeRenderEvent() {
    return new CustomEvent("turbo:before-stream-render", {
      bubbles: true,
      cancelable: true,
      detail: { newStream: this, render: StreamElement.renderElement }
    })
  }

  get targetElementsById() {
    const element = this.ownerDocument?.getElementById(this.target);

    if (element !== null) {
      return [element]
    } else {
      return []
    }
  }

  get targetElementsByQuery() {
    const elements = this.ownerDocument?.querySelectorAll(this.targets);

    if (elements.length !== 0) {
      return Array.prototype.slice.call(elements)
    } else {
      return []
    }
  }
}

class StreamSourceElement extends HTMLElement {
  streamSource = null

  connectedCallback() {
    this.streamSource = this.src.match(/^ws{1,2}:/) ? new WebSocket(this.src) : new EventSource(this.src);

    connectStreamSource(this.streamSource);
  }

  disconnectedCallback() {
    if (this.streamSource) {
      this.streamSource.close();

      disconnectStreamSource(this.streamSource);
    }
  }

  get src() {
    return this.getAttribute("src") || ""
  }
}

FrameElement.delegateConstructor = FrameController;

if (customElements.get("turbo-frame") === undefined) {
  customElements.define("turbo-frame", FrameElement);
}

if (customElements.get("turbo-stream") === undefined) {
  customElements.define("turbo-stream", StreamElement);
}

if (customElements.get("turbo-stream-source") === undefined) {
  customElements.define("turbo-stream-source", StreamSourceElement);
}

(() => {
  let element = document.currentScript;
  if (!element) return
  if (element.hasAttribute("data-turbo-suppress-warning")) return

  element = element.parentElement;
  while (element) {
    if (element == document.body) {
      return console.warn(
        unindent`
        You are loading Turbo from a <script> element inside the <body> element. This is probably not what you meant to do!

        Load your application’s JavaScript bundle inside the <head> element instead. <script> elements in <body> are evaluated with each page change.

        For more information, see: https://turbo.hotwired.dev/handbook/building#working-with-script-elements

        ——
        Suppress this warning by adding a "data-turbo-suppress-warning" attribute to: %s
      `,
        element.outerHTML
      )
    }

    element = element.parentElement;
  }
})();

window.Turbo = { ...Turbo, StreamActions };
start();




/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/dist/index.js"
/*!*************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/dist/index.js ***!
  \*************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   startStimulusApp: () => (/* binding */ startStimulusApp)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
/* harmony import */ var _webpack_loader_symfony_stimulus_bridge_controllers_json__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./webpack/loader!@symfony/stimulus-bridge/controllers.json */ "./node_modules/@symfony/stimulus-bridge/dist/webpack/loader.js!./assets/controllers.json");



//#region node_modules/.pnpm/@hotwired+stimulus-webpack-helpers@1.0.1_@hotwired+stimulus@3.2.2/node_modules/@hotwired/stimulus-webpack-helpers/dist/stimulus-webpack-helpers.js
function definitionsFromContext(context) {
	return context.keys().map((key) => definitionForModuleWithContextAndKey(context, key)).filter((value) => value);
}
function definitionForModuleWithContextAndKey(context, key) {
	const identifier = identifierForContextKey(key);
	if (identifier) return definitionForModuleAndIdentifier(context(key), identifier);
}
function definitionForModuleAndIdentifier(module, identifier) {
	const controllerConstructor = module.default;
	if (typeof controllerConstructor == "function") return {
		identifier,
		controllerConstructor
	};
}
function identifierForContextKey(key) {
	const logicalName = (key.match(/^(?:\.\/)?(.+)(?:[_-]controller\..+?)$/) || [])[1];
	if (logicalName) return logicalName.replace(/_/g, "-").replace(/\//g, "--");
}

//#endregion
//#region src/index.ts
function startStimulusApp(context) {
	const application = _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Application.start();
	if (true) application.debug = true;
	if (context) application.load(definitionsFromContext(context));
	for (const controllerName in _webpack_loader_symfony_stimulus_bridge_controllers_json__WEBPACK_IMPORTED_MODULE_1__["default"]) {
		if (!Object.prototype.hasOwnProperty.call(_webpack_loader_symfony_stimulus_bridge_controllers_json__WEBPACK_IMPORTED_MODULE_1__["default"], controllerName)) continue;
		application.register(controllerName, _webpack_loader_symfony_stimulus_bridge_controllers_json__WEBPACK_IMPORTED_MODULE_1__["default"][controllerName]);
	}
	return application;
}

//#endregion


/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/dist/webpack/loader.js!./assets/controllers.json"
/*!************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/dist/webpack/loader.js!./assets/controllers.json ***!
  \************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _symfony_ux_live_component_dist_live_controller_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @symfony/ux-live-component/dist/live_controller.js */ "./node_modules/@symfony/ux-live-component/dist/live_controller.js");
/* harmony import */ var _symfony_ux_live_component_dist_live_min_css__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @symfony/ux-live-component/dist/live.min.css */ "./node_modules/@symfony/ux-live-component/dist/live.min.css");
/* harmony import */ var _symfony_ux_turbo_dist_turbo_controller_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @symfony/ux-turbo/dist/turbo_controller.js */ "./node_modules/@symfony/ux-turbo/dist/turbo_controller.js");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  'live': _symfony_ux_live_component_dist_live_controller_js__WEBPACK_IMPORTED_MODULE_0__["default"],
  'symfony--ux-turbo--turbo-core': _symfony_ux_turbo_dist_turbo_controller_js__WEBPACK_IMPORTED_MODULE_2__["default"],
});

/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/csrf_protection_controller.js"
/*!****************************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/csrf_protection_controller.js ***!
  \****************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ controller)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");

const controller = class extends _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller {
    constructor(context) {
        super(context);
        this.__stimulusLazyController = true;
    }
    initialize() {
        if (this.application.controllers.find((controller) => {
            return controller.identifier === this.identifier && controller.__stimulusLazyController;
        })) {
            return;
        }
        __webpack_require__.e(/*! import() */ "assets_controllers_csrf_protection_controller_js").then(__webpack_require__.bind(__webpack_require__, /*! ./assets/controllers/csrf_protection_controller.js */ "./assets/controllers/csrf_protection_controller.js")).then((controller) => {
            this.application.register(this.identifier, controller.default);
        });
    }
};


/***/ },

/***/ "./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/hello_controller.js"
/*!******************************************************************************************************************!*\
  !*** ./node_modules/@symfony/stimulus-bridge/lazy-controller-loader.js!./assets/controllers/hello_controller.js ***!
  \******************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }


/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="hello" attribute will cause
 * this controller to be executed. The name "hello" comes from the filename:
 * hello_controller.js -> "hello"
 *
 * Delete this file or adapt it for your use!
 */
var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "connect",
    value: function connect() {
      this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.js';
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);


/***/ },

/***/ "./node_modules/@symfony/ux-live-component/dist/live.min.css"
/*!*******************************************************************!*\
  !*** ./node_modules/@symfony/ux-live-component/dist/live.min.css ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./node_modules/@symfony/ux-live-component/dist/live_controller.js"
/*!*************************************************************************!*\
  !*** ./node_modules/@symfony/ux-live-component/dist/live_controller.js ***!
  \*************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Component: () => (/* binding */ Component),
/* harmony export */   "default": () => (/* binding */ LiveControllerDefault),
/* harmony export */   getComponent: () => (/* binding */ getComponent)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
// src/live_controller.ts


// src/Backend/BackendRequest.ts
var BackendRequest_default = class {
  constructor(promise, actions, updateModels) {
    this.isResolved = false;
    this.promise = promise;
    this.promise.then((response) => {
      this.isResolved = true;
      return response;
    });
    this.actions = actions;
    this.updatedModels = updateModels;
  }
  /**
   * Does this BackendRequest contain at least on action in targetedActions?
   */
  containsOneOfActions(targetedActions) {
    return this.actions.filter((action) => targetedActions.includes(action)).length > 0;
  }
  /**
   * Does this BackendRequest includes updates for any of these models?
   */
  areAnyModelsUpdated(targetedModels) {
    return this.updatedModels.filter((model) => targetedModels.includes(model)).length > 0;
  }
};

// src/Backend/RequestBuilder.ts
var RequestBuilder_default = class {
  constructor(url, method = "post") {
    this.url = url;
    this.method = method;
  }
  buildRequest(props, actions, updated, children, updatedPropsFromParent, files) {
    const splitUrl = this.url.split("?");
    let [url] = splitUrl;
    const [, queryString] = splitUrl;
    const params = new URLSearchParams(queryString || "");
    const fetchOptions = {};
    fetchOptions.headers = {
      Accept: "application/vnd.live-component+html",
      "X-Requested-With": "XMLHttpRequest",
      "X-Live-Url": window.location.pathname + window.location.search
    };
    const totalFiles = Object.entries(files).reduce((total, current) => total + current.length, 0);
    const hasFingerprints = Object.keys(children).length > 0;
    if (actions.length === 0 && totalFiles === 0 && this.method === "get" && this.willDataFitInUrl(
      JSON.stringify(props),
      JSON.stringify(updated),
      params,
      JSON.stringify(children),
      JSON.stringify(updatedPropsFromParent)
    )) {
      params.set("props", JSON.stringify(props));
      params.set("updated", JSON.stringify(updated));
      if (Object.keys(updatedPropsFromParent).length > 0) {
        params.set("propsFromParent", JSON.stringify(updatedPropsFromParent));
      }
      if (hasFingerprints) {
        params.set("children", JSON.stringify(children));
      }
      fetchOptions.method = "GET";
    } else {
      fetchOptions.method = "POST";
      const requestData = { props, updated };
      if (Object.keys(updatedPropsFromParent).length > 0) {
        requestData.propsFromParent = updatedPropsFromParent;
      }
      if (hasFingerprints) {
        requestData.children = children;
      }
      if (actions.length > 0) {
        if (actions.length === 1) {
          requestData.args = actions[0].args;
          url += `/${encodeURIComponent(actions[0].name)}`;
        } else {
          url += "/_batch";
          requestData.actions = actions;
        }
      }
      const formData = new FormData();
      formData.append("data", JSON.stringify(requestData));
      for (const [key, value] of Object.entries(files)) {
        const length = value.length;
        for (let i = 0; i < length; ++i) {
          formData.append(key, value[i]);
        }
      }
      fetchOptions.body = formData;
    }
    const paramsString = params.toString();
    return {
      url: `${url}${paramsString.length > 0 ? `?${paramsString}` : ""}`,
      fetchOptions
    };
  }
  willDataFitInUrl(propsJson, updatedJson, params, childrenJson, propsFromParentJson) {
    const urlEncodedJsonData = new URLSearchParams(
      propsJson + updatedJson + childrenJson + propsFromParentJson
    ).toString();
    return (urlEncodedJsonData + params.toString()).length < 1500;
  }
};

// src/Backend/Backend.ts
var Backend_default = class {
  constructor(url, method = "post") {
    this.requestBuilder = new RequestBuilder_default(url, method);
  }
  makeRequest(props, actions, updated, children, updatedPropsFromParent, files) {
    const { url, fetchOptions } = this.requestBuilder.buildRequest(
      props,
      actions,
      updated,
      children,
      updatedPropsFromParent,
      files
    );
    return new BackendRequest_default(
      fetch(url, fetchOptions),
      actions.map((backendAction) => backendAction.name),
      Object.keys(updated)
    );
  }
};

// src/Backend/BackendResponse.ts
var BackendResponse_default = class {
  constructor(response) {
    this.response = response;
  }
  async getBody() {
    if (!this.body) {
      this.body = await this.response.text();
    }
    return this.body;
  }
  getLiveUrl() {
    if (void 0 === this.liveUrl) {
      this.liveUrl = this.response.headers.get("X-Live-Url");
    }
    return this.liveUrl;
  }
};

// src/Util/getElementAsTagText.ts
function getElementAsTagText(element) {
  return element.innerHTML ? element.outerHTML.slice(0, element.outerHTML.indexOf(element.innerHTML)) : element.outerHTML;
}

// src/ComponentRegistry.ts
var componentMapByElement = /* @__PURE__ */ new WeakMap();
var componentMapByComponent = /* @__PURE__ */ new Map();
var registerComponent = (component) => {
  componentMapByElement.set(component.element, component);
  componentMapByComponent.set(component, component.name);
};
var unregisterComponent = (component) => {
  componentMapByElement.delete(component.element);
  componentMapByComponent.delete(component);
};
var getComponent = (element) => new Promise((resolve, reject) => {
  let count = 0;
  const maxCount = 10;
  const interval = setInterval(() => {
    const component = componentMapByElement.get(element);
    if (component) {
      clearInterval(interval);
      resolve(component);
    }
    count++;
    if (count > maxCount) {
      clearInterval(interval);
      reject(new Error(`Component not found for element ${getElementAsTagText(element)}`));
    }
  }, 5);
});
var findComponents = (currentComponent, onlyParents, onlyMatchName) => {
  const components = [];
  componentMapByComponent.forEach((componentName, component) => {
    if (onlyParents && (currentComponent === component || !component.element.contains(currentComponent.element))) {
      return;
    }
    if (onlyMatchName && componentName !== onlyMatchName) {
      return;
    }
    components.push(component);
  });
  return components;
};
var findChildren = (currentComponent) => {
  const children = [];
  componentMapByComponent.forEach((componentName, component) => {
    if (currentComponent === component) {
      return;
    }
    if (!currentComponent.element.contains(component.element)) {
      return;
    }
    let foundChildComponent = false;
    componentMapByComponent.forEach((childComponentName, childComponent) => {
      if (foundChildComponent) {
        return;
      }
      if (childComponent === component) {
        return;
      }
      if (childComponent.element.contains(component.element)) {
        foundChildComponent = true;
      }
    });
    children.push(component);
  });
  return children;
};
var findParent = (currentComponent) => {
  let parentElement = currentComponent.element.parentElement;
  while (parentElement) {
    const component = componentMapByElement.get(parentElement);
    if (component) {
      return component;
    }
    parentElement = parentElement.parentElement;
  }
  return null;
};

// src/Directive/directives_parser.ts
function parseDirectives(content) {
  const directives = [];
  if (!content) {
    return directives;
  }
  let currentActionName = "";
  let currentArgumentValue = "";
  let currentArguments = [];
  let currentModifiers = [];
  let state = "action";
  const getLastActionName = () => {
    if (currentActionName) {
      return currentActionName;
    }
    if (directives.length === 0) {
      throw new Error("Could not find any directives");
    }
    return directives[directives.length - 1].action;
  };
  const pushInstruction = () => {
    directives.push({
      action: currentActionName,
      args: currentArguments,
      modifiers: currentModifiers,
      getString: () => {
        return content;
      }
    });
    currentActionName = "";
    currentArgumentValue = "";
    currentArguments = [];
    currentModifiers = [];
    state = "action";
  };
  const pushArgument = () => {
    currentArguments.push(currentArgumentValue.trim());
    currentArgumentValue = "";
  };
  const pushModifier = () => {
    if (currentArguments.length > 1) {
      throw new Error(`The modifier "${currentActionName}()" does not support multiple arguments.`);
    }
    currentModifiers.push({
      name: currentActionName,
      value: currentArguments.length > 0 ? currentArguments[0] : null
    });
    currentActionName = "";
    currentArguments = [];
    state = "action";
  };
  for (let i = 0; i < content.length; i++) {
    const char = content[i];
    switch (state) {
      case "action":
        if (char === "(") {
          state = "arguments";
          break;
        }
        if (char === " ") {
          if (currentActionName) {
            pushInstruction();
          }
          break;
        }
        if (char === "|") {
          pushModifier();
          break;
        }
        currentActionName += char;
        break;
      case "arguments":
        if (char === ")") {
          pushArgument();
          state = "after_arguments";
          break;
        }
        if (char === ",") {
          pushArgument();
          break;
        }
        currentArgumentValue += char;
        break;
      case "after_arguments":
        if (char === "|") {
          pushModifier();
          break;
        }
        if (char !== " ") {
          throw new Error(`Missing space after ${getLastActionName()}()`);
        }
        pushInstruction();
        break;
    }
  }
  switch (state) {
    case "action":
    case "after_arguments":
      if (currentActionName) {
        pushInstruction();
      }
      break;
    default:
      throw new Error(`Did you forget to add a closing ")" after "${currentActionName}"?`);
  }
  return directives;
}

// src/string_utils.ts
function combineSpacedArray(parts) {
  const finalParts = [];
  parts.forEach((part) => {
    finalParts.push(...trimAll(part).split(" "));
  });
  return finalParts;
}
function trimAll(str) {
  return str.replace(/[\s]+/g, " ").trim();
}
function normalizeModelName(model) {
  return model.replace(/\[]$/, "").split("[").map((s) => s.replace("]", "")).join(".");
}

// src/dom_utils.ts
function getValueFromElement(element, valueStore) {
  if (element instanceof HTMLInputElement) {
    if (element.type === "checkbox") {
      const modelNameData = getModelDirectiveFromElement(element, false);
      if (modelNameData !== null) {
        const modelValue = valueStore.get(modelNameData.action);
        if (Array.isArray(modelValue)) {
          return getMultipleCheckboxValue(element, modelValue);
        }
        if (Object(modelValue) === modelValue) {
          return getMultipleCheckboxValue(element, Object.values(modelValue));
        }
      }
      if (element.hasAttribute("value")) {
        return element.checked ? element.getAttribute("value") : null;
      }
      return element.checked;
    }
    return inputValue(element);
  }
  if (element instanceof HTMLSelectElement) {
    if (element.multiple) {
      return Array.from(element.selectedOptions).map((el) => el.value);
    }
    return element.value;
  }
  if (element.hasAttribute("data-value")) {
    return element.dataset.value;
  }
  if ("value" in element) {
    return element.value;
  }
  if (element.hasAttribute("value")) {
    return element.getAttribute("value");
  }
  return null;
}
function setValueOnElement(element, value) {
  if (element instanceof HTMLInputElement) {
    if (element.type === "file") {
      return;
    }
    if (element.type === "radio") {
      element.checked = element.value == value;
      return;
    }
    if (element.type === "checkbox") {
      if (Array.isArray(value)) {
        element.checked = value.some((val) => val == element.value);
      } else if (element.hasAttribute("value")) {
        element.checked = element.value == value;
      } else {
        element.checked = value;
      }
      return;
    }
  }
  if (element instanceof HTMLSelectElement) {
    const arrayWrappedValue = [].concat(value).map((value2) => {
      return `${value2}`;
    });
    Array.from(element.options).forEach((option) => {
      option.selected = arrayWrappedValue.includes(option.value);
    });
    return;
  }
  value = value === void 0 ? "" : value;
  element.value = value;
}
function getAllModelDirectiveFromElements(element) {
  if (!element.dataset.model) {
    return [];
  }
  const directives = parseDirectives(element.dataset.model);
  directives.forEach((directive) => {
    if (directive.args.length > 0) {
      throw new Error(
        `The data-model="${element.dataset.model}" format is invalid: it does not support passing arguments to the model.`
      );
    }
    directive.action = normalizeModelName(directive.action);
  });
  return directives;
}
function getModelDirectiveFromElement(element, throwOnMissing = true) {
  const dataModelDirectives = getAllModelDirectiveFromElements(element);
  if (dataModelDirectives.length > 0) {
    return dataModelDirectives[0];
  }
  if (element.getAttribute("name")) {
    const formElement = element.closest("form");
    if (formElement && "model" in formElement.dataset) {
      const directives = parseDirectives(formElement.dataset.model || "*");
      const directive = directives[0];
      if (directive.args.length > 0) {
        throw new Error(
          `The data-model="${formElement.dataset.model}" format is invalid: it does not support passing arguments to the model.`
        );
      }
      directive.action = normalizeModelName(element.getAttribute("name"));
      return directive;
    }
  }
  if (!throwOnMissing) {
    return null;
  }
  throw new Error(
    `Cannot determine the model name for "${getElementAsTagText(
      element
    )}": the element must either have a "data-model" (or "name" attribute living inside a <form data-model="*">).`
  );
}
function elementBelongsToThisComponent(element, component) {
  if (component.element === element) {
    return true;
  }
  if (!component.element.contains(element)) {
    return false;
  }
  const closestLiveComponent = element.closest('[data-controller~="live"]');
  return closestLiveComponent === component.element;
}
function cloneHTMLElement(element) {
  const newElement = element.cloneNode(true);
  if (!(newElement instanceof HTMLElement)) {
    throw new Error("Could not clone element");
  }
  return newElement;
}
function htmlToElement(html) {
  const template = document.createElement("template");
  html = html.trim();
  template.innerHTML = html;
  if (template.content.childElementCount > 1) {
    throw new Error(
      `Component HTML contains ${template.content.childElementCount} elements, but only 1 root element is allowed.`
    );
  }
  const child = template.content.firstElementChild;
  if (!child) {
    throw new Error("Child not found");
  }
  if (!(child instanceof HTMLElement)) {
    throw new Error(`Created element is not an HTMLElement: ${html.trim()}`);
  }
  return child;
}
var getMultipleCheckboxValue = (element, currentValues) => {
  const finalValues = [...currentValues];
  const value = inputValue(element);
  const index = currentValues.indexOf(value);
  if (element.checked) {
    if (index === -1) {
      finalValues.push(value);
    }
    return finalValues;
  }
  if (index > -1) {
    finalValues.splice(index, 1);
  }
  return finalValues;
};
var inputValue = (element) => element.dataset.value ? element.dataset.value : element.value;
function isTextualInputElement(el) {
  return el instanceof HTMLInputElement && ["text", "email", "password", "search", "tel", "url"].includes(el.type);
}
function isTextareaElement(el) {
  return el instanceof HTMLTextAreaElement;
}
function isNumericalInputElement(element) {
  return element instanceof HTMLInputElement && ["number", "range"].includes(element.type);
}

// src/HookManager.ts
var HookManager_default = class {
  constructor() {
    this.hooks = /* @__PURE__ */ new Map();
  }
  register(hookName, callback) {
    const hooks = this.hooks.get(hookName) || [];
    hooks.push(callback);
    this.hooks.set(hookName, hooks);
  }
  unregister(hookName, callback) {
    const hooks = this.hooks.get(hookName) || [];
    const index = hooks.indexOf(callback);
    if (index === -1) {
      return;
    }
    hooks.splice(index, 1);
    this.hooks.set(hookName, hooks);
  }
  triggerHook(hookName, ...args) {
    const hooks = this.hooks.get(hookName) || [];
    hooks.forEach((callback) => callback(...args));
  }
};

// ../../../node_modules/.pnpm/idiomorph@0.3.0/node_modules/idiomorph/dist/idiomorph.esm.js
var Idiomorph = function() {
  "use strict";
  let EMPTY_SET = /* @__PURE__ */ new Set();
  let defaults = {
    morphStyle: "outerHTML",
    callbacks: {
      beforeNodeAdded: noOp,
      afterNodeAdded: noOp,
      beforeNodeMorphed: noOp,
      afterNodeMorphed: noOp,
      beforeNodeRemoved: noOp,
      afterNodeRemoved: noOp,
      beforeAttributeUpdated: noOp
    },
    head: {
      style: "merge",
      shouldPreserve: function(elt) {
        return elt.getAttribute("im-preserve") === "true";
      },
      shouldReAppend: function(elt) {
        return elt.getAttribute("im-re-append") === "true";
      },
      shouldRemove: noOp,
      afterHeadMorphed: noOp
    }
  };
  function morph(oldNode, newContent, config = {}) {
    if (oldNode instanceof Document) {
      oldNode = oldNode.documentElement;
    }
    if (typeof newContent === "string") {
      newContent = parseContent(newContent);
    }
    let normalizedContent = normalizeContent(newContent);
    let ctx = createMorphContext(oldNode, normalizedContent, config);
    return morphNormalizedContent(oldNode, normalizedContent, ctx);
  }
  function morphNormalizedContent(oldNode, normalizedNewContent, ctx) {
    if (ctx.head.block) {
      let oldHead = oldNode.querySelector("head");
      let newHead = normalizedNewContent.querySelector("head");
      if (oldHead && newHead) {
        let promises = handleHeadElement(newHead, oldHead, ctx);
        Promise.all(promises).then(function() {
          morphNormalizedContent(oldNode, normalizedNewContent, Object.assign(ctx, {
            head: {
              block: false,
              ignore: true
            }
          }));
        });
        return;
      }
    }
    if (ctx.morphStyle === "innerHTML") {
      morphChildren(normalizedNewContent, oldNode, ctx);
      return oldNode.children;
    } else if (ctx.morphStyle === "outerHTML" || ctx.morphStyle == null) {
      let bestMatch = findBestNodeMatch(normalizedNewContent, oldNode, ctx);
      let previousSibling = bestMatch?.previousSibling;
      let nextSibling = bestMatch?.nextSibling;
      let morphedNode = morphOldNodeTo(oldNode, bestMatch, ctx);
      if (bestMatch) {
        return insertSiblings(previousSibling, morphedNode, nextSibling);
      } else {
        return [];
      }
    } else {
      throw "Do not understand how to morph style " + ctx.morphStyle;
    }
  }
  function ignoreValueOfActiveElement(possibleActiveElement, ctx) {
    return ctx.ignoreActiveValue && possibleActiveElement === document.activeElement;
  }
  function morphOldNodeTo(oldNode, newContent, ctx) {
    if (ctx.ignoreActive && oldNode === document.activeElement) {
    } else if (newContent == null) {
      if (ctx.callbacks.beforeNodeRemoved(oldNode) === false) return oldNode;
      oldNode.remove();
      ctx.callbacks.afterNodeRemoved(oldNode);
      return null;
    } else if (!isSoftMatch(oldNode, newContent)) {
      if (ctx.callbacks.beforeNodeRemoved(oldNode) === false) return oldNode;
      if (ctx.callbacks.beforeNodeAdded(newContent) === false) return oldNode;
      oldNode.parentElement.replaceChild(newContent, oldNode);
      ctx.callbacks.afterNodeAdded(newContent);
      ctx.callbacks.afterNodeRemoved(oldNode);
      return newContent;
    } else {
      if (ctx.callbacks.beforeNodeMorphed(oldNode, newContent) === false) return oldNode;
      if (oldNode instanceof HTMLHeadElement && ctx.head.ignore) {
      } else if (oldNode instanceof HTMLHeadElement && ctx.head.style !== "morph") {
        handleHeadElement(newContent, oldNode, ctx);
      } else {
        syncNodeFrom(newContent, oldNode, ctx);
        if (!ignoreValueOfActiveElement(oldNode, ctx)) {
          morphChildren(newContent, oldNode, ctx);
        }
      }
      ctx.callbacks.afterNodeMorphed(oldNode, newContent);
      return oldNode;
    }
  }
  function morphChildren(newParent, oldParent, ctx) {
    let nextNewChild = newParent.firstChild;
    let insertionPoint = oldParent.firstChild;
    let newChild;
    while (nextNewChild) {
      newChild = nextNewChild;
      nextNewChild = newChild.nextSibling;
      if (insertionPoint == null) {
        if (ctx.callbacks.beforeNodeAdded(newChild) === false) return;
        oldParent.appendChild(newChild);
        ctx.callbacks.afterNodeAdded(newChild);
        removeIdsFromConsideration(ctx, newChild);
        continue;
      }
      if (isIdSetMatch(newChild, insertionPoint, ctx)) {
        morphOldNodeTo(insertionPoint, newChild, ctx);
        insertionPoint = insertionPoint.nextSibling;
        removeIdsFromConsideration(ctx, newChild);
        continue;
      }
      let idSetMatch = findIdSetMatch(newParent, oldParent, newChild, insertionPoint, ctx);
      if (idSetMatch) {
        insertionPoint = removeNodesBetween(insertionPoint, idSetMatch, ctx);
        morphOldNodeTo(idSetMatch, newChild, ctx);
        removeIdsFromConsideration(ctx, newChild);
        continue;
      }
      let softMatch = findSoftMatch(newParent, oldParent, newChild, insertionPoint, ctx);
      if (softMatch) {
        insertionPoint = removeNodesBetween(insertionPoint, softMatch, ctx);
        morphOldNodeTo(softMatch, newChild, ctx);
        removeIdsFromConsideration(ctx, newChild);
        continue;
      }
      if (ctx.callbacks.beforeNodeAdded(newChild) === false) return;
      oldParent.insertBefore(newChild, insertionPoint);
      ctx.callbacks.afterNodeAdded(newChild);
      removeIdsFromConsideration(ctx, newChild);
    }
    while (insertionPoint !== null) {
      let tempNode = insertionPoint;
      insertionPoint = insertionPoint.nextSibling;
      removeNode(tempNode, ctx);
    }
  }
  function ignoreAttribute(attr, to, updateType, ctx) {
    if (attr === "value" && ctx.ignoreActiveValue && to === document.activeElement) {
      return true;
    }
    return ctx.callbacks.beforeAttributeUpdated(attr, to, updateType) === false;
  }
  function syncNodeFrom(from, to, ctx) {
    let type = from.nodeType;
    if (type === 1) {
      const fromAttributes = from.attributes;
      const toAttributes = to.attributes;
      for (const fromAttribute of fromAttributes) {
        if (ignoreAttribute(fromAttribute.name, to, "update", ctx)) {
          continue;
        }
        if (to.getAttribute(fromAttribute.name) !== fromAttribute.value) {
          to.setAttribute(fromAttribute.name, fromAttribute.value);
        }
      }
      for (let i = toAttributes.length - 1; 0 <= i; i--) {
        const toAttribute = toAttributes[i];
        if (ignoreAttribute(toAttribute.name, to, "remove", ctx)) {
          continue;
        }
        if (!from.hasAttribute(toAttribute.name)) {
          to.removeAttribute(toAttribute.name);
        }
      }
    }
    if (type === 8 || type === 3) {
      if (to.nodeValue !== from.nodeValue) {
        to.nodeValue = from.nodeValue;
      }
    }
    if (!ignoreValueOfActiveElement(to, ctx)) {
      syncInputValue(from, to, ctx);
    }
  }
  function syncBooleanAttribute(from, to, attributeName, ctx) {
    if (from[attributeName] !== to[attributeName]) {
      let ignoreUpdate = ignoreAttribute(attributeName, to, "update", ctx);
      if (!ignoreUpdate) {
        to[attributeName] = from[attributeName];
      }
      if (from[attributeName]) {
        if (!ignoreUpdate) {
          to.setAttribute(attributeName, from[attributeName]);
        }
      } else {
        if (!ignoreAttribute(attributeName, to, "remove", ctx)) {
          to.removeAttribute(attributeName);
        }
      }
    }
  }
  function syncInputValue(from, to, ctx) {
    if (from instanceof HTMLInputElement && to instanceof HTMLInputElement && from.type !== "file") {
      let fromValue = from.value;
      let toValue = to.value;
      syncBooleanAttribute(from, to, "checked", ctx);
      syncBooleanAttribute(from, to, "disabled", ctx);
      if (!from.hasAttribute("value")) {
        if (!ignoreAttribute("value", to, "remove", ctx)) {
          to.value = "";
          to.removeAttribute("value");
        }
      } else if (fromValue !== toValue) {
        if (!ignoreAttribute("value", to, "update", ctx)) {
          to.setAttribute("value", fromValue);
          to.value = fromValue;
        }
      }
    } else if (from instanceof HTMLOptionElement) {
      syncBooleanAttribute(from, to, "selected", ctx);
    } else if (from instanceof HTMLTextAreaElement && to instanceof HTMLTextAreaElement) {
      let fromValue = from.value;
      let toValue = to.value;
      if (ignoreAttribute("value", to, "update", ctx)) {
        return;
      }
      if (fromValue !== toValue) {
        to.value = fromValue;
      }
      if (to.firstChild && to.firstChild.nodeValue !== fromValue) {
        to.firstChild.nodeValue = fromValue;
      }
    }
  }
  function handleHeadElement(newHeadTag, currentHead, ctx) {
    let added = [];
    let removed = [];
    let preserved = [];
    let nodesToAppend = [];
    let headMergeStyle = ctx.head.style;
    let srcToNewHeadNodes = /* @__PURE__ */ new Map();
    for (const newHeadChild of newHeadTag.children) {
      srcToNewHeadNodes.set(newHeadChild.outerHTML, newHeadChild);
    }
    for (const currentHeadElt of currentHead.children) {
      let inNewContent = srcToNewHeadNodes.has(currentHeadElt.outerHTML);
      let isReAppended = ctx.head.shouldReAppend(currentHeadElt);
      let isPreserved = ctx.head.shouldPreserve(currentHeadElt);
      if (inNewContent || isPreserved) {
        if (isReAppended) {
          removed.push(currentHeadElt);
        } else {
          srcToNewHeadNodes.delete(currentHeadElt.outerHTML);
          preserved.push(currentHeadElt);
        }
      } else {
        if (headMergeStyle === "append") {
          if (isReAppended) {
            removed.push(currentHeadElt);
            nodesToAppend.push(currentHeadElt);
          }
        } else {
          if (ctx.head.shouldRemove(currentHeadElt) !== false) {
            removed.push(currentHeadElt);
          }
        }
      }
    }
    nodesToAppend.push(...srcToNewHeadNodes.values());
    log("to append: ", nodesToAppend);
    let promises = [];
    for (const newNode of nodesToAppend) {
      log("adding: ", newNode);
      let newElt = document.createRange().createContextualFragment(newNode.outerHTML).firstChild;
      log(newElt);
      if (ctx.callbacks.beforeNodeAdded(newElt) !== false) {
        if (newElt.href || newElt.src) {
          let resolve = null;
          let promise = new Promise(function(_resolve) {
            resolve = _resolve;
          });
          newElt.addEventListener("load", function() {
            resolve();
          });
          promises.push(promise);
        }
        currentHead.appendChild(newElt);
        ctx.callbacks.afterNodeAdded(newElt);
        added.push(newElt);
      }
    }
    for (const removedElement of removed) {
      if (ctx.callbacks.beforeNodeRemoved(removedElement) !== false) {
        currentHead.removeChild(removedElement);
        ctx.callbacks.afterNodeRemoved(removedElement);
      }
    }
    ctx.head.afterHeadMorphed(currentHead, { added, kept: preserved, removed });
    return promises;
  }
  function log() {
  }
  function noOp() {
  }
  function mergeDefaults(config) {
    let finalConfig = {};
    Object.assign(finalConfig, defaults);
    Object.assign(finalConfig, config);
    finalConfig.callbacks = {};
    Object.assign(finalConfig.callbacks, defaults.callbacks);
    Object.assign(finalConfig.callbacks, config.callbacks);
    finalConfig.head = {};
    Object.assign(finalConfig.head, defaults.head);
    Object.assign(finalConfig.head, config.head);
    return finalConfig;
  }
  function createMorphContext(oldNode, newContent, config) {
    config = mergeDefaults(config);
    return {
      target: oldNode,
      newContent,
      config,
      morphStyle: config.morphStyle,
      ignoreActive: config.ignoreActive,
      ignoreActiveValue: config.ignoreActiveValue,
      idMap: createIdMap(oldNode, newContent),
      deadIds: /* @__PURE__ */ new Set(),
      callbacks: config.callbacks,
      head: config.head
    };
  }
  function isIdSetMatch(node1, node2, ctx) {
    if (node1 == null || node2 == null) {
      return false;
    }
    if (node1.nodeType === node2.nodeType && node1.tagName === node2.tagName) {
      if (node1.id !== "" && node1.id === node2.id) {
        return true;
      } else {
        return getIdIntersectionCount(ctx, node1, node2) > 0;
      }
    }
    return false;
  }
  function isSoftMatch(node1, node2) {
    if (node1 == null || node2 == null) {
      return false;
    }
    return node1.nodeType === node2.nodeType && node1.tagName === node2.tagName;
  }
  function removeNodesBetween(startInclusive, endExclusive, ctx) {
    while (startInclusive !== endExclusive) {
      let tempNode = startInclusive;
      startInclusive = startInclusive.nextSibling;
      removeNode(tempNode, ctx);
    }
    removeIdsFromConsideration(ctx, endExclusive);
    return endExclusive.nextSibling;
  }
  function findIdSetMatch(newContent, oldParent, newChild, insertionPoint, ctx) {
    let newChildPotentialIdCount = getIdIntersectionCount(ctx, newChild, oldParent);
    let potentialMatch = null;
    if (newChildPotentialIdCount > 0) {
      let potentialMatch2 = insertionPoint;
      let otherMatchCount = 0;
      while (potentialMatch2 != null) {
        if (isIdSetMatch(newChild, potentialMatch2, ctx)) {
          return potentialMatch2;
        }
        otherMatchCount += getIdIntersectionCount(ctx, potentialMatch2, newContent);
        if (otherMatchCount > newChildPotentialIdCount) {
          return null;
        }
        potentialMatch2 = potentialMatch2.nextSibling;
      }
    }
    return potentialMatch;
  }
  function findSoftMatch(newContent, oldParent, newChild, insertionPoint, ctx) {
    let potentialSoftMatch = insertionPoint;
    let nextSibling = newChild.nextSibling;
    let siblingSoftMatchCount = 0;
    while (potentialSoftMatch != null) {
      if (getIdIntersectionCount(ctx, potentialSoftMatch, newContent) > 0) {
        return null;
      }
      if (isSoftMatch(newChild, potentialSoftMatch)) {
        return potentialSoftMatch;
      }
      if (isSoftMatch(nextSibling, potentialSoftMatch)) {
        siblingSoftMatchCount++;
        nextSibling = nextSibling.nextSibling;
        if (siblingSoftMatchCount >= 2) {
          return null;
        }
      }
      potentialSoftMatch = potentialSoftMatch.nextSibling;
    }
    return potentialSoftMatch;
  }
  function parseContent(newContent) {
    let parser = new DOMParser();
    let contentWithSvgsRemoved = newContent.replace(/<svg(\s[^>]*>|>)([\s\S]*?)<\/svg>/gim, "");
    if (contentWithSvgsRemoved.match(/<\/html>/) || contentWithSvgsRemoved.match(/<\/head>/) || contentWithSvgsRemoved.match(/<\/body>/)) {
      let content = parser.parseFromString(newContent, "text/html");
      if (contentWithSvgsRemoved.match(/<\/html>/)) {
        content.generatedByIdiomorph = true;
        return content;
      } else {
        let htmlElement = content.firstChild;
        if (htmlElement) {
          htmlElement.generatedByIdiomorph = true;
          return htmlElement;
        } else {
          return null;
        }
      }
    } else {
      let responseDoc = parser.parseFromString("<body><template>" + newContent + "</template></body>", "text/html");
      let content = responseDoc.body.querySelector("template").content;
      content.generatedByIdiomorph = true;
      return content;
    }
  }
  function normalizeContent(newContent) {
    if (newContent == null) {
      const dummyParent = document.createElement("div");
      return dummyParent;
    } else if (newContent.generatedByIdiomorph) {
      return newContent;
    } else if (newContent instanceof Node) {
      const dummyParent = document.createElement("div");
      dummyParent.append(newContent);
      return dummyParent;
    } else {
      const dummyParent = document.createElement("div");
      for (const elt of [...newContent]) {
        dummyParent.append(elt);
      }
      return dummyParent;
    }
  }
  function insertSiblings(previousSibling, morphedNode, nextSibling) {
    let stack = [];
    let added = [];
    while (previousSibling != null) {
      stack.push(previousSibling);
      previousSibling = previousSibling.previousSibling;
    }
    while (stack.length > 0) {
      let node = stack.pop();
      added.push(node);
      morphedNode.parentElement.insertBefore(node, morphedNode);
    }
    added.push(morphedNode);
    while (nextSibling != null) {
      stack.push(nextSibling);
      added.push(nextSibling);
      nextSibling = nextSibling.nextSibling;
    }
    while (stack.length > 0) {
      morphedNode.parentElement.insertBefore(stack.pop(), morphedNode.nextSibling);
    }
    return added;
  }
  function findBestNodeMatch(newContent, oldNode, ctx) {
    let currentElement;
    currentElement = newContent.firstChild;
    let bestElement = currentElement;
    let score = 0;
    while (currentElement) {
      let newScore = scoreElement(currentElement, oldNode, ctx);
      if (newScore > score) {
        bestElement = currentElement;
        score = newScore;
      }
      currentElement = currentElement.nextSibling;
    }
    return bestElement;
  }
  function scoreElement(node1, node2, ctx) {
    if (isSoftMatch(node1, node2)) {
      return 0.5 + getIdIntersectionCount(ctx, node1, node2);
    }
    return 0;
  }
  function removeNode(tempNode, ctx) {
    removeIdsFromConsideration(ctx, tempNode);
    if (ctx.callbacks.beforeNodeRemoved(tempNode) === false) return;
    tempNode.remove();
    ctx.callbacks.afterNodeRemoved(tempNode);
  }
  function isIdInConsideration(ctx, id) {
    return !ctx.deadIds.has(id);
  }
  function idIsWithinNode(ctx, id, targetNode) {
    let idSet = ctx.idMap.get(targetNode) || EMPTY_SET;
    return idSet.has(id);
  }
  function removeIdsFromConsideration(ctx, node) {
    let idSet = ctx.idMap.get(node) || EMPTY_SET;
    for (const id of idSet) {
      ctx.deadIds.add(id);
    }
  }
  function getIdIntersectionCount(ctx, node1, node2) {
    let sourceSet = ctx.idMap.get(node1) || EMPTY_SET;
    let matchCount = 0;
    for (const id of sourceSet) {
      if (isIdInConsideration(ctx, id) && idIsWithinNode(ctx, id, node2)) {
        ++matchCount;
      }
    }
    return matchCount;
  }
  function populateIdMapForNode(node, idMap) {
    let nodeParent = node.parentElement;
    let idElements = node.querySelectorAll("[id]");
    for (const elt of idElements) {
      let current = elt;
      while (current !== nodeParent && current != null) {
        let idSet = idMap.get(current);
        if (idSet == null) {
          idSet = /* @__PURE__ */ new Set();
          idMap.set(current, idSet);
        }
        idSet.add(elt.id);
        current = current.parentElement;
      }
    }
  }
  function createIdMap(oldContent, newContent) {
    let idMap = /* @__PURE__ */ new Map();
    populateIdMapForNode(oldContent, idMap);
    populateIdMapForNode(newContent, idMap);
    return idMap;
  }
  return {
    morph,
    defaults
  };
}();

// src/normalize_attributes_for_comparison.ts
function normalizeAttributesForComparison(element) {
  const isFileInput = element instanceof HTMLInputElement && element.type === "file";
  if (!isFileInput) {
    if ("value" in element) {
      element.setAttribute("value", element.value);
    } else if (element.hasAttribute("value")) {
      element.setAttribute("value", "");
    }
  }
  Array.from(element.children).forEach((child) => {
    normalizeAttributesForComparison(child);
  });
}

// src/morphdom.ts
var syncAttributes = (fromEl, toEl) => {
  for (let i = 0; i < fromEl.attributes.length; i++) {
    const attr = fromEl.attributes[i];
    toEl.setAttribute(attr.name, attr.value);
  }
};
function executeMorphdom(rootFromElement, rootToElement, modifiedFieldElements, getElementValue, externalMutationTracker) {
  const originalElementIdsToSwapAfter = [];
  const originalElementsToPreserve = /* @__PURE__ */ new Map();
  const markElementAsNeedingPostMorphSwap = (id, replaceWithClone) => {
    const oldElement = originalElementsToPreserve.get(id);
    if (!(oldElement instanceof HTMLElement)) {
      throw new Error(`Original element with id ${id} not found`);
    }
    originalElementIdsToSwapAfter.push(id);
    if (!replaceWithClone) {
      return null;
    }
    const clonedOldElement = cloneHTMLElement(oldElement);
    oldElement.replaceWith(clonedOldElement);
    return clonedOldElement;
  };
  rootToElement.querySelectorAll("[data-live-preserve]").forEach((newElement) => {
    const id = newElement.id;
    if (!id) {
      throw new Error("The data-live-preserve attribute requires an id attribute to be set on the element");
    }
    const oldElement = rootFromElement.querySelector(`#${id}`);
    if (!(oldElement instanceof HTMLElement)) {
      throw new Error(`The element with id "${id}" was not found in the original HTML`);
    }
    newElement.removeAttribute("data-live-preserve");
    originalElementsToPreserve.set(id, oldElement);
    syncAttributes(newElement, oldElement);
  });
  Idiomorph.morph(rootFromElement, rootToElement, {
    callbacks: {
      beforeNodeMorphed: (fromEl, toEl) => {
        if (!(fromEl instanceof Element) || !(toEl instanceof Element)) {
          return true;
        }
        if (fromEl === rootFromElement) {
          return true;
        }
        if (fromEl.id && originalElementsToPreserve.has(fromEl.id)) {
          if (fromEl.id === toEl.id) {
            return false;
          }
          const clonedFromEl = markElementAsNeedingPostMorphSwap(fromEl.id, true);
          if (!clonedFromEl) {
            throw new Error("missing clone");
          }
          Idiomorph.morph(clonedFromEl, toEl);
          return false;
        }
        if (fromEl instanceof HTMLElement && toEl instanceof HTMLElement) {
          if (typeof fromEl.__x !== "undefined") {
            if (!window.Alpine) {
              throw new Error(
                "Unable to access Alpine.js though the global window.Alpine variable. Please make sure Alpine.js is loaded before Symfony UX LiveComponent."
              );
            }
            if (typeof window.Alpine.morph !== "function") {
              throw new Error(
                "Unable to access Alpine.js morph function. Please make sure the Alpine.js Morph plugin is installed and loaded, see https://alpinejs.dev/plugins/morph for more information."
              );
            }
            window.Alpine.morph(fromEl.__x, toEl);
          }
          if (externalMutationTracker.wasElementAdded(fromEl)) {
            fromEl.insertAdjacentElement("afterend", toEl);
            return false;
          }
          if (modifiedFieldElements.includes(fromEl)) {
            setValueOnElement(toEl, getElementValue(fromEl));
          }
          if (fromEl === document.activeElement && fromEl !== document.body && null !== getModelDirectiveFromElement(fromEl, false)) {
            setValueOnElement(toEl, getElementValue(fromEl));
          }
          const elementChanges = externalMutationTracker.getChangedElement(fromEl);
          if (elementChanges) {
            elementChanges.applyToElement(toEl);
          }
          if (fromEl.nodeName.toUpperCase() !== "OPTION" && fromEl.isEqualNode(toEl)) {
            const normalizedFromEl = cloneHTMLElement(fromEl);
            normalizeAttributesForComparison(normalizedFromEl);
            const normalizedToEl = cloneHTMLElement(toEl);
            normalizeAttributesForComparison(normalizedToEl);
            if (normalizedFromEl.isEqualNode(normalizedToEl)) {
              return false;
            }
          }
        }
        if (fromEl.hasAttribute("data-skip-morph") || fromEl.id && fromEl.id !== toEl.id) {
          fromEl.innerHTML = toEl.innerHTML;
          return true;
        }
        if (fromEl.parentElement?.hasAttribute("data-skip-morph")) {
          return false;
        }
        return !fromEl.hasAttribute("data-live-ignore");
      },
      beforeNodeRemoved(node) {
        if (!(node instanceof HTMLElement)) {
          return true;
        }
        if (node.id && originalElementsToPreserve.has(node.id)) {
          markElementAsNeedingPostMorphSwap(node.id, false);
          return true;
        }
        if (externalMutationTracker.wasElementAdded(node)) {
          return false;
        }
        return !node.hasAttribute("data-live-ignore");
      }
    }
  });
  originalElementIdsToSwapAfter.forEach((id) => {
    const newElement = rootFromElement.querySelector(`#${id}`);
    const originalElement = originalElementsToPreserve.get(id);
    if (!(newElement instanceof HTMLElement) || !(originalElement instanceof HTMLElement)) {
      throw new Error("Missing elements.");
    }
    newElement.replaceWith(originalElement);
  });
}

// src/Rendering/ChangingItemsTracker.ts
var ChangingItemsTracker_default = class {
  constructor() {
    // e.g. a Map with key "color" & value { original: 'previousValue', new: 'newValue' },
    this.changedItems = /* @__PURE__ */ new Map();
    this.removedItems = /* @__PURE__ */ new Map();
  }
  /**
   * A "null" previousValue means the item was NOT previously present.
   */
  setItem(itemName, newValue, previousValue) {
    if (this.removedItems.has(itemName)) {
      const removedRecord = this.removedItems.get(itemName);
      this.removedItems.delete(itemName);
      if (removedRecord.original === newValue) {
        return;
      }
    }
    if (this.changedItems.has(itemName)) {
      const originalRecord = this.changedItems.get(itemName);
      if (originalRecord.original === newValue) {
        this.changedItems.delete(itemName);
        return;
      }
      this.changedItems.set(itemName, { original: originalRecord.original, new: newValue });
      return;
    }
    this.changedItems.set(itemName, { original: previousValue, new: newValue });
  }
  removeItem(itemName, currentValue) {
    let trueOriginalValue = currentValue;
    if (this.changedItems.has(itemName)) {
      const originalRecord = this.changedItems.get(itemName);
      trueOriginalValue = originalRecord.original;
      this.changedItems.delete(itemName);
      if (trueOriginalValue === null) {
        return;
      }
    }
    if (!this.removedItems.has(itemName)) {
      this.removedItems.set(itemName, { original: trueOriginalValue });
    }
  }
  getChangedItems() {
    return Array.from(this.changedItems, ([name, { new: value }]) => ({ name, value }));
  }
  getRemovedItems() {
    return Array.from(this.removedItems.keys());
  }
  isEmpty() {
    return this.changedItems.size === 0 && this.removedItems.size === 0;
  }
};

// src/Rendering/ElementChanges.ts
var ElementChanges = class {
  constructor() {
    this.addedClasses = /* @__PURE__ */ new Set();
    this.removedClasses = /* @__PURE__ */ new Set();
    this.styleChanges = new ChangingItemsTracker_default();
    this.attributeChanges = new ChangingItemsTracker_default();
  }
  addClass(className) {
    if (!this.removedClasses.delete(className)) {
      this.addedClasses.add(className);
    }
  }
  removeClass(className) {
    if (!this.addedClasses.delete(className)) {
      this.removedClasses.add(className);
    }
  }
  addStyle(styleName, newValue, originalValue) {
    this.styleChanges.setItem(styleName, newValue, originalValue);
  }
  removeStyle(styleName, originalValue) {
    this.styleChanges.removeItem(styleName, originalValue);
  }
  addAttribute(attributeName, newValue, originalValue) {
    this.attributeChanges.setItem(attributeName, newValue, originalValue);
  }
  removeAttribute(attributeName, originalValue) {
    this.attributeChanges.removeItem(attributeName, originalValue);
  }
  getAddedClasses() {
    return [...this.addedClasses];
  }
  getRemovedClasses() {
    return [...this.removedClasses];
  }
  getChangedStyles() {
    return this.styleChanges.getChangedItems();
  }
  getRemovedStyles() {
    return this.styleChanges.getRemovedItems();
  }
  getChangedAttributes() {
    return this.attributeChanges.getChangedItems();
  }
  getRemovedAttributes() {
    return this.attributeChanges.getRemovedItems();
  }
  applyToElement(element) {
    element.classList.add(...this.addedClasses);
    element.classList.remove(...this.removedClasses);
    this.styleChanges.getChangedItems().forEach((change) => {
      element.style.setProperty(change.name, change.value);
      return;
    });
    this.styleChanges.getRemovedItems().forEach((styleName) => {
      element.style.removeProperty(styleName);
    });
    this.attributeChanges.getChangedItems().forEach((change) => {
      element.setAttribute(change.name, change.value);
    });
    this.attributeChanges.getRemovedItems().forEach((attributeName) => {
      element.removeAttribute(attributeName);
    });
  }
  isEmpty() {
    return this.addedClasses.size === 0 && this.removedClasses.size === 0 && this.styleChanges.isEmpty() && this.attributeChanges.isEmpty();
  }
};

// src/Rendering/ExternalMutationTracker.ts
var ExternalMutationTracker_default = class {
  constructor(element, shouldTrackChangeCallback) {
    this.changedElements = /* @__PURE__ */ new WeakMap();
    /** For testing */
    this.changedElementsCount = 0;
    this.addedElements = [];
    this.removedElements = [];
    this.isStarted = false;
    this.element = element;
    this.shouldTrackChangeCallback = shouldTrackChangeCallback;
    this.mutationObserver = new MutationObserver(this.onMutations.bind(this));
  }
  start() {
    if (this.isStarted) {
      return;
    }
    this.mutationObserver.observe(this.element, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeOldValue: true
    });
    this.isStarted = true;
  }
  stop() {
    if (this.isStarted) {
      this.mutationObserver.disconnect();
      this.isStarted = false;
    }
  }
  getChangedElement(element) {
    return this.changedElements.has(element) ? this.changedElements.get(element) : null;
  }
  getAddedElements() {
    return this.addedElements;
  }
  wasElementAdded(element) {
    return this.addedElements.includes(element);
  }
  /**
   * Forces any pending mutations to be handled immediately, then clears the queue.
   */
  handlePendingChanges() {
    this.onMutations(this.mutationObserver.takeRecords());
  }
  onMutations(mutations) {
    const handledAttributeMutations = /* @__PURE__ */ new WeakMap();
    for (const mutation of mutations) {
      const element = mutation.target;
      if (!this.shouldTrackChangeCallback(element)) {
        continue;
      }
      if (this.isElementAddedByTranslation(element)) {
        continue;
      }
      let isChangeInAddedElement = false;
      for (const addedElement of this.addedElements) {
        if (addedElement.contains(element)) {
          isChangeInAddedElement = true;
          break;
        }
      }
      if (isChangeInAddedElement) {
        continue;
      }
      switch (mutation.type) {
        case "childList":
          this.handleChildListMutation(mutation);
          break;
        case "attributes":
          if (!handledAttributeMutations.has(element)) {
            handledAttributeMutations.set(element, []);
          }
          if (!handledAttributeMutations.get(element).includes(mutation.attributeName)) {
            this.handleAttributeMutation(mutation);
            handledAttributeMutations.set(element, [
              ...handledAttributeMutations.get(element),
              mutation.attributeName
            ]);
          }
          break;
      }
    }
  }
  handleChildListMutation(mutation) {
    mutation.addedNodes.forEach((node) => {
      if (!(node instanceof Element)) {
        return;
      }
      if (this.removedElements.includes(node)) {
        this.removedElements.splice(this.removedElements.indexOf(node), 1);
        return;
      }
      if (this.isElementAddedByTranslation(node)) {
        return;
      }
      this.addedElements.push(node);
    });
    mutation.removedNodes.forEach((node) => {
      if (!(node instanceof Element)) {
        return;
      }
      if (this.addedElements.includes(node)) {
        this.addedElements.splice(this.addedElements.indexOf(node), 1);
        return;
      }
      this.removedElements.push(node);
    });
  }
  handleAttributeMutation(mutation) {
    const element = mutation.target;
    if (!this.changedElements.has(element)) {
      this.changedElements.set(element, new ElementChanges());
      this.changedElementsCount++;
    }
    const changedElement = this.changedElements.get(element);
    switch (mutation.attributeName) {
      case "class":
        this.handleClassAttributeMutation(mutation, changedElement);
        break;
      case "style":
        this.handleStyleAttributeMutation(mutation, changedElement);
        break;
      default:
        this.handleGenericAttributeMutation(mutation, changedElement);
    }
    if (changedElement.isEmpty()) {
      this.changedElements.delete(element);
      this.changedElementsCount--;
    }
  }
  handleClassAttributeMutation(mutation, elementChanges) {
    const element = mutation.target;
    const previousValue = mutation.oldValue || "";
    const previousValues = previousValue.match(/(\S+)/gu) || [];
    const newValues = [].slice.call(element.classList);
    const addedValues = newValues.filter((value) => !previousValues.includes(value));
    const removedValues = previousValues.filter((value) => !newValues.includes(value));
    addedValues.forEach((value) => {
      elementChanges.addClass(value);
    });
    removedValues.forEach((value) => {
      elementChanges.removeClass(value);
    });
  }
  handleStyleAttributeMutation(mutation, elementChanges) {
    const element = mutation.target;
    const previousValue = mutation.oldValue || "";
    const previousStyles = this.extractStyles(previousValue);
    const newValue = element.getAttribute("style") || "";
    const newStyles = this.extractStyles(newValue);
    const addedOrChangedStyles = Object.keys(newStyles).filter(
      (key) => previousStyles[key] === void 0 || previousStyles[key] !== newStyles[key]
    );
    const removedStyles = Object.keys(previousStyles).filter((key) => !newStyles[key]);
    addedOrChangedStyles.forEach((style) => {
      elementChanges.addStyle(
        style,
        newStyles[style],
        previousStyles[style] === void 0 ? null : previousStyles[style]
      );
    });
    removedStyles.forEach((style) => {
      elementChanges.removeStyle(style, previousStyles[style]);
    });
  }
  handleGenericAttributeMutation(mutation, elementChanges) {
    const attributeName = mutation.attributeName;
    const element = mutation.target;
    let oldValue = mutation.oldValue;
    let newValue = element.getAttribute(attributeName);
    if (oldValue === attributeName) {
      oldValue = "";
    }
    if (newValue === attributeName) {
      newValue = "";
    }
    if (!element.hasAttribute(attributeName)) {
      if (oldValue === null) {
        return;
      }
      elementChanges.removeAttribute(attributeName, mutation.oldValue);
      return;
    }
    if (newValue === oldValue) {
      return;
    }
    elementChanges.addAttribute(attributeName, element.getAttribute(attributeName), mutation.oldValue);
  }
  extractStyles(styles) {
    const styleObject = {};
    styles.split(";").forEach((style) => {
      const parts = style.split(":");
      if (parts.length === 1) {
        return;
      }
      const property = parts[0].trim();
      styleObject[property] = parts.slice(1).join(":").trim();
    });
    return styleObject;
  }
  /**
   * Helps avoid tracking changes by Chrome's translation feature.
   *
   * When Chrome translates, it mutates the dom in a way that triggers MutationObserver.
   * This includes adding new elements wrapped in a <font> tag. This causes live
   * components to incorrectly think that these new elements should persist through
   * re-renders, causing duplicate text.
   */
  isElementAddedByTranslation(element) {
    return element.tagName === "FONT" && element.getAttribute("style") === "vertical-align: inherit;";
  }
};

// src/Component/UnsyncedInputsTracker.ts
var UnsyncedInputsTracker_default = class {
  constructor(component, modelElementResolver) {
    this.elementEventListeners = [
      { event: "input", callback: (event) => this.handleInputEvent(event) }
    ];
    this.component = component;
    this.modelElementResolver = modelElementResolver;
    this.unsyncedInputs = new UnsyncedInputContainer();
  }
  activate() {
    this.elementEventListeners.forEach(({ event, callback }) => {
      this.component.element.addEventListener(event, callback);
    });
  }
  deactivate() {
    this.elementEventListeners.forEach(({ event, callback }) => {
      this.component.element.removeEventListener(event, callback);
    });
  }
  markModelAsSynced(modelName) {
    this.unsyncedInputs.markModelAsSynced(modelName);
  }
  handleInputEvent(event) {
    const target = event.target;
    if (!target) {
      return;
    }
    this.updateModelFromElement(target);
  }
  updateModelFromElement(element) {
    if (!elementBelongsToThisComponent(element, this.component)) {
      return;
    }
    if (!(element instanceof HTMLElement)) {
      throw new Error("Could not update model for non HTMLElement");
    }
    const modelName = this.modelElementResolver.getModelName(element);
    this.unsyncedInputs.add(element, modelName);
  }
  getUnsyncedInputs() {
    return this.unsyncedInputs.allUnsyncedInputs();
  }
  getUnsyncedModels() {
    return Array.from(this.unsyncedInputs.getUnsyncedModelNames());
  }
  resetUnsyncedFields() {
    this.unsyncedInputs.resetUnsyncedFields();
  }
};
var UnsyncedInputContainer = class {
  constructor() {
    this.unsyncedNonModelFields = [];
    this.unsyncedModelNames = [];
    this.unsyncedModelFields = /* @__PURE__ */ new Map();
  }
  add(element, modelName = null) {
    if (modelName) {
      this.unsyncedModelFields.set(modelName, element);
      if (!this.unsyncedModelNames.includes(modelName)) {
        this.unsyncedModelNames.push(modelName);
      }
      return;
    }
    this.unsyncedNonModelFields.push(element);
  }
  /**
   * Mark all fields as synced, except for those not bound to a model or whose
   * values are still dirty.
   */
  resetUnsyncedFields() {
    this.unsyncedModelFields.forEach((value, key) => {
      if (!this.unsyncedModelNames.includes(key)) {
        this.unsyncedModelFields.delete(key);
      }
    });
  }
  allUnsyncedInputs() {
    return [...this.unsyncedNonModelFields, ...this.unsyncedModelFields.values()];
  }
  markModelAsSynced(modelName) {
    const index = this.unsyncedModelNames.indexOf(modelName);
    if (index !== -1) {
      this.unsyncedModelNames.splice(index, 1);
    }
  }
  /**
   * Returns a list of models whose fields have been modified, but whose values
   * have not yet been set onto the data store.
   */
  getUnsyncedModelNames() {
    return this.unsyncedModelNames;
  }
};

// src/data_manipulation_utils.ts
function getDeepData(data, propertyPath) {
  const { currentLevelData, finalKey } = parseDeepData(data, propertyPath);
  if (currentLevelData === void 0) {
    return void 0;
  }
  return currentLevelData[finalKey];
}
var parseDeepData = (data, propertyPath) => {
  const finalData = JSON.parse(JSON.stringify(data));
  let currentLevelData = finalData;
  const parts = propertyPath.split(".");
  for (let i = 0; i < parts.length - 1; i++) {
    currentLevelData = currentLevelData[parts[i]];
  }
  const finalKey = parts[parts.length - 1];
  return {
    currentLevelData,
    finalData,
    finalKey,
    parts
  };
};

// src/Component/ValueStore.ts
var ValueStore_default = class {
  constructor(props) {
    /**
     * Original, read-only props that represent the original component state.
     *
     * @private
     */
    this.props = {};
    /**
     * A list of props that have been "dirty" (changed) since the last request to the server.
     */
    this.dirtyProps = {};
    /**
     * A list of dirty props that were sent to the server, but the response has
     * not yet been received.
     */
    this.pendingProps = {};
    /**
     * A list of props that the parent wants us to update.
     *
     * These will be sent on the next request to the server.
     */
    this.updatedPropsFromParent = {};
    this.props = props;
  }
  /**
   * Returns the props with the given name.
   *
   * This allows for non-normalized model names - e.g.
   * user[firstName] -> user.firstName and also will fetch
   * deeply (fetching the "firstName" sub-key from the "user" key).
   */
  get(name) {
    const normalizedName = normalizeModelName(name);
    if (this.dirtyProps[normalizedName] !== void 0) {
      return this.dirtyProps[normalizedName];
    }
    if (this.pendingProps[normalizedName] !== void 0) {
      return this.pendingProps[normalizedName];
    }
    if (this.props[normalizedName] !== void 0) {
      return this.props[normalizedName];
    }
    return getDeepData(this.props, normalizedName);
  }
  has(name) {
    return this.get(name) !== void 0;
  }
  /**
   * Sets data back onto the value store.
   *
   * The name can be in the non-normalized format.
   *
   * Returns true if the new value is different from the existing value.
   */
  set(name, value) {
    const normalizedName = normalizeModelName(name);
    if (this.get(normalizedName) === value) {
      return false;
    }
    this.dirtyProps[normalizedName] = value;
    return true;
  }
  getOriginalProps() {
    return { ...this.props };
  }
  getDirtyProps() {
    return { ...this.dirtyProps };
  }
  getUpdatedPropsFromParent() {
    return { ...this.updatedPropsFromParent };
  }
  /**
   * Called when an update request begins.
   */
  flushDirtyPropsToPending() {
    this.pendingProps = { ...this.dirtyProps };
    this.dirtyProps = {};
  }
  /**
   * Called when an update request finishes successfully.
   */
  reinitializeAllProps(props) {
    this.props = props;
    this.updatedPropsFromParent = {};
    this.pendingProps = {};
  }
  /**
   * Called after an update request failed.
   */
  pushPendingPropsBackToDirty() {
    this.dirtyProps = { ...this.pendingProps, ...this.dirtyProps };
    this.pendingProps = {};
  }
  /**
   * This is used when a parent component is rendering, and it includes
   * a fresh set of props that should be updated on the child component.
   *
   * The server manages returning only the props that should be updated onto
   * the child, so we don't need to worry about that.
   *
   * The props are stored in a different place, because the existing props
   * have their own checksum and these new props have *their* own checksum.
   * So, on the next render, both need to be sent independently.
   *
   * Returns true if any of the props are different.
   */
  storeNewPropsFromParent(props) {
    let changed = false;
    for (const [key, value] of Object.entries(props)) {
      const currentValue = this.get(key);
      if (currentValue !== value) {
        changed = true;
      }
    }
    if (changed) {
      this.updatedPropsFromParent = props;
    }
    return changed;
  }
};

// src/Component/index.ts
var Component = class {
  /**
   * @param element The root element
   * @param name    The name of the component
   * @param props   Readonly component props
   * @param listeners Array of event -> action listeners
   * @param id      Some unique id to identify this component. Needed to be a child component
   * @param backend Backend instance for updating
   * @param elementDriver Class to get "model" name from any element.
   */
  constructor(element, name, props, listeners, id, backend, elementDriver) {
    /**
     * A fingerprint that identifies the props/input that was used on
     * the server to create this component, especially if it was a
     * child component. This is sent back to the server and can be used
     * to determine if any "input" to the child component changed and thus,
     * if the child component needs to be re-rendered.
     */
    this.fingerprint = "";
    this.defaultDebounce = 150;
    this.backendRequest = null;
    /** Actions that are waiting to be executed */
    this.pendingActions = [];
    /** Files that are waiting to be sent */
    this.pendingFiles = {};
    /** Is a request waiting to be made? */
    this.isRequestPending = false;
    /** Current "timeout" before the pending request should be sent. */
    this.requestDebounceTimeout = null;
    this.element = element;
    this.name = name;
    this.backend = backend;
    this.elementDriver = elementDriver;
    this.id = id;
    this.listeners = /* @__PURE__ */ new Map();
    listeners.forEach((listener) => {
      if (!this.listeners.has(listener.event)) {
        this.listeners.set(listener.event, []);
      }
      this.listeners.get(listener.event)?.push(listener.action);
    });
    this.valueStore = new ValueStore_default(props);
    this.unsyncedInputsTracker = new UnsyncedInputsTracker_default(this, elementDriver);
    this.hooks = new HookManager_default();
    this.resetPromise();
    this.externalMutationTracker = new ExternalMutationTracker_default(
      this.element,
      (element2) => elementBelongsToThisComponent(element2, this)
    );
    this.externalMutationTracker.start();
  }
  addPlugin(plugin) {
    plugin.attachToComponent(this);
  }
  connect() {
    registerComponent(this);
    this.hooks.triggerHook("connect", this);
    this.unsyncedInputsTracker.activate();
    this.externalMutationTracker.start();
  }
  disconnect() {
    unregisterComponent(this);
    this.hooks.triggerHook("disconnect", this);
    this.clearRequestDebounceTimeout();
    this.unsyncedInputsTracker.deactivate();
    this.externalMutationTracker.stop();
  }
  on(hookName, callback) {
    this.hooks.register(hookName, callback);
  }
  off(hookName, callback) {
    this.hooks.unregister(hookName, callback);
  }
  set(model, value, reRender = false, debounce = false) {
    const promise = this.nextRequestPromise;
    const modelName = normalizeModelName(model);
    if (!this.valueStore.has(modelName)) {
      throw new Error(`Invalid model name "${model}".`);
    }
    const isChanged = this.valueStore.set(modelName, value);
    this.hooks.triggerHook("model:set", model, value, this);
    this.unsyncedInputsTracker.markModelAsSynced(modelName);
    if (reRender && isChanged) {
      this.debouncedStartRequest(debounce);
    }
    return promise;
  }
  getData(model) {
    const modelName = normalizeModelName(model);
    if (!this.valueStore.has(modelName)) {
      throw new Error(`Invalid model "${model}".`);
    }
    return this.valueStore.get(modelName);
  }
  action(name, args = {}, debounce = false) {
    const promise = this.nextRequestPromise;
    this.pendingActions.push({
      name,
      args
    });
    this.debouncedStartRequest(debounce);
    return promise;
  }
  files(key, input) {
    this.pendingFiles[key] = input;
  }
  render() {
    const promise = this.nextRequestPromise;
    this.tryStartingRequest();
    return promise;
  }
  /**
   * Returns an array of models the user has modified, but whose model has not
   * yet been updated.
   */
  getUnsyncedModels() {
    return this.unsyncedInputsTracker.getUnsyncedModels();
  }
  emit(name, data, onlyMatchingComponentsNamed = null) {
    this.performEmit(name, data, false, onlyMatchingComponentsNamed);
  }
  emitUp(name, data, onlyMatchingComponentsNamed = null) {
    this.performEmit(name, data, true, onlyMatchingComponentsNamed);
  }
  emitSelf(name, data) {
    this.doEmit(name, data);
  }
  performEmit(name, data, emitUp, matchingName) {
    const components = findComponents(this, emitUp, matchingName);
    components.forEach((component) => {
      component.doEmit(name, data);
    });
  }
  doEmit(name, data) {
    if (!this.listeners.has(name)) {
      return;
    }
    const actions = this.listeners.get(name) || [];
    actions.forEach((action) => {
      this.action(action, data, 1);
    });
  }
  isTurboEnabled() {
    return typeof Turbo !== "undefined" && !this.element.closest('[data-turbo="false"]');
  }
  tryStartingRequest() {
    if (!this.backendRequest) {
      this.performRequest();
      return;
    }
    this.isRequestPending = true;
  }
  performRequest() {
    const thisPromiseResolve = this.nextRequestPromiseResolve;
    this.resetPromise();
    this.unsyncedInputsTracker.resetUnsyncedFields();
    const filesToSend = {};
    for (const [key, value] of Object.entries(this.pendingFiles)) {
      if (value.files) {
        filesToSend[key] = value.files;
      }
    }
    const requestConfig = {
      props: this.valueStore.getOriginalProps(),
      actions: this.pendingActions,
      updated: this.valueStore.getDirtyProps(),
      children: {},
      updatedPropsFromParent: this.valueStore.getUpdatedPropsFromParent(),
      files: filesToSend
    };
    this.hooks.triggerHook("request:started", requestConfig);
    this.backendRequest = this.backend.makeRequest(
      requestConfig.props,
      requestConfig.actions,
      requestConfig.updated,
      requestConfig.children,
      requestConfig.updatedPropsFromParent,
      requestConfig.files
    );
    this.hooks.triggerHook("loading.state:started", this.element, this.backendRequest);
    this.pendingActions = [];
    this.valueStore.flushDirtyPropsToPending();
    this.isRequestPending = false;
    this.backendRequest.promise.then(async (response) => {
      const backendResponse = new BackendResponse_default(response);
      const html = await backendResponse.getBody();
      for (const input of Object.values(this.pendingFiles)) {
        input.value = "";
      }
      const headers = backendResponse.response.headers;
      if (!headers.get("Content-Type")?.includes("application/vnd.live-component+html") && !headers.get("X-Live-Redirect")) {
        const controls = { displayError: true };
        this.valueStore.pushPendingPropsBackToDirty();
        this.hooks.triggerHook("response:error", backendResponse, controls);
        if (controls.displayError) {
          this.renderError(html);
        }
        this.backendRequest = null;
        thisPromiseResolve(backendResponse);
        return response;
      }
      const liveUrl = backendResponse.getLiveUrl();
      if (liveUrl) {
        history.replaceState(
          history.state,
          "",
          new URL(liveUrl + window.location.hash, window.location.origin)
        );
      }
      this.processRerender(html, backendResponse);
      this.backendRequest = null;
      thisPromiseResolve(backendResponse);
      if (this.isRequestPending) {
        this.isRequestPending = false;
        this.performRequest();
      }
      return response;
    });
  }
  processRerender(html, backendResponse) {
    const controls = { shouldRender: true };
    this.hooks.triggerHook("render:started", html, backendResponse, controls);
    if (!controls.shouldRender) {
      return;
    }
    if (backendResponse.response.headers.get("Location")) {
      if (this.isTurboEnabled()) {
        Turbo.visit(backendResponse.response.headers.get("Location"));
      } else {
        window.location.href = backendResponse.response.headers.get("Location") || "";
      }
      return;
    }
    this.hooks.triggerHook("loading.state:finished", this.element);
    const modifiedModelValues = {};
    Object.keys(this.valueStore.getDirtyProps()).forEach((modelName) => {
      modifiedModelValues[modelName] = this.valueStore.get(modelName);
    });
    let newElement;
    try {
      newElement = htmlToElement(html);
      if (!newElement.matches("[data-controller~=live]")) {
        throw new Error("A live component template must contain a single root controller element.");
      }
    } catch (error) {
      console.error(`There was a problem with the '${this.name}' component HTML returned:`, {
        id: this.id
      });
      throw error;
    }
    this.externalMutationTracker.handlePendingChanges();
    this.externalMutationTracker.stop();
    executeMorphdom(
      this.element,
      newElement,
      this.unsyncedInputsTracker.getUnsyncedInputs(),
      (element) => getValueFromElement(element, this.valueStore),
      this.externalMutationTracker
    );
    this.externalMutationTracker.start();
    const newProps = this.elementDriver.getComponentProps();
    this.valueStore.reinitializeAllProps(newProps);
    const eventsToEmit = this.elementDriver.getEventsToEmit();
    const browserEventsToDispatch = this.elementDriver.getBrowserEventsToDispatch();
    Object.keys(modifiedModelValues).forEach((modelName) => {
      this.valueStore.set(modelName, modifiedModelValues[modelName]);
    });
    eventsToEmit.forEach(({ event, data, target, componentName }) => {
      if (target === "up") {
        this.emitUp(event, data, componentName);
        return;
      }
      if (target === "self") {
        this.emitSelf(event, data);
        return;
      }
      this.emit(event, data, componentName);
    });
    browserEventsToDispatch.forEach(({ event, payload }) => {
      this.element.dispatchEvent(
        new CustomEvent(event, {
          detail: payload,
          bubbles: true
        })
      );
    });
    this.hooks.triggerHook("render:finished", this);
  }
  calculateDebounce(debounce) {
    if (debounce === true) {
      return this.defaultDebounce;
    }
    if (debounce === false) {
      return 0;
    }
    return debounce;
  }
  clearRequestDebounceTimeout() {
    if (this.requestDebounceTimeout) {
      clearTimeout(this.requestDebounceTimeout);
      this.requestDebounceTimeout = null;
    }
  }
  debouncedStartRequest(debounce) {
    this.clearRequestDebounceTimeout();
    this.requestDebounceTimeout = window.setTimeout(() => {
      this.render();
    }, this.calculateDebounce(debounce));
  }
  // inspired by Livewire!
  renderError(html) {
    let modal = document.getElementById("live-component-error");
    if (modal) {
      modal.innerHTML = "";
    } else {
      modal = document.createElement("div");
      modal.id = "live-component-error";
      modal.style.padding = "50px";
      modal.style.backgroundColor = "rgba(0, 0, 0, .5)";
      modal.style.zIndex = "100000";
      modal.style.position = "fixed";
      modal.style.top = "0px";
      modal.style.bottom = "0px";
      modal.style.left = "0px";
      modal.style.right = "0px";
      modal.style.display = "flex";
      modal.style.flexDirection = "column";
    }
    const iframe = document.createElement("iframe");
    iframe.style.borderRadius = "5px";
    iframe.style.flexGrow = "1";
    modal.appendChild(iframe);
    document.body.prepend(modal);
    document.body.style.overflow = "hidden";
    if (iframe.contentWindow) {
      iframe.contentWindow.document.open();
      iframe.contentWindow.document.write(html);
      iframe.contentWindow.document.close();
    }
    const closeModal = (modal2) => {
      if (modal2) {
        modal2.outerHTML = "";
      }
      document.body.style.overflow = "visible";
    };
    modal.addEventListener("click", () => closeModal(modal));
    modal.setAttribute("tabindex", "0");
    modal.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        closeModal(modal);
      }
    });
    modal.focus();
  }
  resetPromise() {
    this.nextRequestPromise = new Promise((resolve) => {
      this.nextRequestPromiseResolve = resolve;
    });
  }
  /**
   * Called on a child component after the parent component render has requested
   * that the child component update its props & re-render if necessary.
   */
  _updateFromParentProps(props) {
    const isChanged = this.valueStore.storeNewPropsFromParent(props);
    if (isChanged) {
      this.render();
    }
  }
};
function proxifyComponent(component) {
  return new Proxy(component, {
    get(component2, prop) {
      if (prop in component2 || typeof prop !== "string") {
        if (typeof component2[prop] === "function") {
          const callable = component2[prop];
          return (...args) => {
            return callable.apply(component2, args);
          };
        }
        return Reflect.get(component2, prop);
      }
      if (component2.valueStore.has(prop)) {
        return component2.getData(prop);
      }
      return (args) => {
        return component2.action.apply(component2, [prop, args]);
      };
    },
    set(target, property, value) {
      if (property in target) {
        target[property] = value;
        return true;
      }
      target.set(property, value);
      return true;
    }
  });
}

// src/Component/ElementDriver.ts
var StimulusElementDriver = class {
  constructor(controller) {
    this.controller = controller;
  }
  getModelName(element) {
    const modelDirective = getModelDirectiveFromElement(element, false);
    if (!modelDirective) {
      return null;
    }
    return modelDirective.action;
  }
  getComponentProps() {
    return this.controller.propsValue;
  }
  getEventsToEmit() {
    return this.controller.eventsToEmitValue;
  }
  getBrowserEventsToDispatch() {
    return this.controller.eventsToDispatchValue;
  }
};

// src/Directive/get_model_binding.ts
function get_model_binding_default(modelDirective) {
  let shouldRender = true;
  let targetEventName = null;
  let debounce = false;
  let minLength = null;
  let maxLength = null;
  let minValue = null;
  let maxValue = null;
  modelDirective.modifiers.forEach((modifier) => {
    switch (modifier.name) {
      case "on":
        if (!modifier.value) {
          throw new Error(
            `The "on" modifier in ${modelDirective.getString()} requires a value - e.g. on(change).`
          );
        }
        if (!["input", "change"].includes(modifier.value)) {
          throw new Error(
            `The "on" modifier in ${modelDirective.getString()} only accepts the arguments "input" or "change".`
          );
        }
        targetEventName = modifier.value;
        break;
      case "norender":
        shouldRender = false;
        break;
      case "debounce":
        debounce = modifier.value ? Number.parseInt(modifier.value) : true;
        break;
      case "min_length":
        minLength = modifier.value ? Number.parseInt(modifier.value) : null;
        break;
      case "max_length":
        maxLength = modifier.value ? Number.parseInt(modifier.value) : null;
        break;
      case "min_value":
        minValue = modifier.value ? Number.parseFloat(modifier.value) : null;
        break;
      case "max_value":
        maxValue = modifier.value ? Number.parseFloat(modifier.value) : null;
        break;
      default:
        throw new Error(`Unknown modifier "${modifier.name}" in data-model="${modelDirective.getString()}".`);
    }
  });
  const [modelName, innerModelName] = modelDirective.action.split(":");
  return {
    modelName,
    innerModelName: innerModelName || null,
    shouldRender,
    debounce,
    targetEventName,
    minLength,
    maxLength,
    minValue,
    maxValue
  };
}

// src/Component/plugins/ChildComponentPlugin.ts
var ChildComponentPlugin_default = class {
  constructor(component) {
    this.parentModelBindings = [];
    this.component = component;
    const modelDirectives = getAllModelDirectiveFromElements(this.component.element);
    this.parentModelBindings = modelDirectives.map(get_model_binding_default);
  }
  attachToComponent(component) {
    component.on("request:started", (requestData) => {
      requestData.children = this.getChildrenFingerprints();
    });
    component.on("model:set", (model, value) => {
      this.notifyParentModelChange(model, value);
    });
  }
  getChildrenFingerprints() {
    const fingerprints = {};
    this.getChildren().forEach((child) => {
      if (!child.id) {
        throw new Error("missing id");
      }
      fingerprints[child.id] = {
        fingerprint: child.fingerprint,
        tag: child.element.tagName.toLowerCase()
      };
    });
    return fingerprints;
  }
  /**
   * Notifies parent of a model change if desired.
   *
   * This makes the child "behave" like it's a normal `<input>` element,
   * where, when its value changes, the parent is notified.
   */
  notifyParentModelChange(modelName, value) {
    const parentComponent = findParent(this.component);
    if (!parentComponent) {
      return;
    }
    this.parentModelBindings.forEach((modelBinding) => {
      const childModelName = modelBinding.innerModelName || "value";
      if (childModelName !== modelName) {
        return;
      }
      parentComponent.set(modelBinding.modelName, value, modelBinding.shouldRender, modelBinding.debounce);
    });
  }
  getChildren() {
    return findChildren(this.component);
  }
};

// src/Component/plugins/LazyPlugin.ts
var LazyPlugin_default = class {
  constructor() {
    this.intersectionObserver = null;
  }
  attachToComponent(component) {
    if ("lazy" !== component.element.attributes.getNamedItem("loading")?.value) {
      return;
    }
    component.on("connect", () => {
      this.getObserver().observe(component.element);
    });
    component.on("disconnect", () => {
      this.intersectionObserver?.unobserve(component.element);
    });
  }
  getObserver() {
    if (!this.intersectionObserver) {
      this.intersectionObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.dispatchEvent(new CustomEvent("live:appear"));
            observer.unobserve(entry.target);
          }
        });
      });
    }
    return this.intersectionObserver;
  }
};

// src/Component/plugins/LoadingPlugin.ts
var LoadingPlugin_default = class {
  attachToComponent(component) {
    component.on("loading.state:started", (element, request) => {
      this.startLoading(component, element, request);
    });
    component.on("loading.state:finished", (element) => {
      this.finishLoading(component, element);
    });
    this.finishLoading(component, component.element);
  }
  startLoading(component, targetElement, backendRequest) {
    this.handleLoadingToggle(component, true, targetElement, backendRequest);
  }
  finishLoading(component, targetElement) {
    this.handleLoadingToggle(component, false, targetElement, null);
  }
  handleLoadingToggle(component, isLoading, targetElement, backendRequest) {
    if (isLoading) {
      this.addAttributes(targetElement, ["busy"]);
    } else {
      this.removeAttributes(targetElement, ["busy"]);
    }
    this.getLoadingDirectives(component, targetElement).forEach(({ element, directives }) => {
      if (isLoading) {
        this.addAttributes(element, ["data-live-is-loading"]);
      } else {
        this.removeAttributes(element, ["data-live-is-loading"]);
      }
      directives.forEach((directive) => {
        this.handleLoadingDirective(element, isLoading, directive, backendRequest);
      });
    });
  }
  handleLoadingDirective(element, isLoading, directive, backendRequest) {
    const finalAction = parseLoadingAction(directive.action, isLoading);
    const targetedActions = [];
    const targetedModels = [];
    let delay = 0;
    const validModifiers = /* @__PURE__ */ new Map();
    validModifiers.set("delay", (modifier) => {
      if (!isLoading) {
        return;
      }
      delay = modifier.value ? Number.parseInt(modifier.value) : 200;
    });
    validModifiers.set("action", (modifier) => {
      if (!modifier.value) {
        throw new Error(
          `The "action" in data-loading must have an action name - e.g. action(foo). It's missing for "${directive.getString()}"`
        );
      }
      targetedActions.push(modifier.value);
    });
    validModifiers.set("model", (modifier) => {
      if (!modifier.value) {
        throw new Error(
          `The "model" in data-loading must have an action name - e.g. model(foo). It's missing for "${directive.getString()}"`
        );
      }
      targetedModels.push(modifier.value);
    });
    directive.modifiers.forEach((modifier) => {
      if (validModifiers.has(modifier.name)) {
        const callable = validModifiers.get(modifier.name) ?? (() => {
        });
        callable(modifier);
        return;
      }
      throw new Error(
        `Unknown modifier "${modifier.name}" used in data-loading="${directive.getString()}". Available modifiers are: ${Array.from(validModifiers.keys()).join(", ")}.`
      );
    });
    if (isLoading && targetedActions.length > 0 && backendRequest && !backendRequest.containsOneOfActions(targetedActions)) {
      return;
    }
    if (isLoading && targetedModels.length > 0 && backendRequest && !backendRequest.areAnyModelsUpdated(targetedModels)) {
      return;
    }
    let loadingDirective;
    switch (finalAction) {
      case "show":
        loadingDirective = () => this.showElement(element);
        break;
      case "hide":
        loadingDirective = () => this.hideElement(element);
        break;
      case "addClass":
        loadingDirective = () => this.addClass(element, directive.args);
        break;
      case "removeClass":
        loadingDirective = () => this.removeClass(element, directive.args);
        break;
      case "addAttribute":
        loadingDirective = () => this.addAttributes(element, directive.args);
        break;
      case "removeAttribute":
        loadingDirective = () => this.removeAttributes(element, directive.args);
        break;
      default:
        throw new Error(`Unknown data-loading action "${finalAction}"`);
    }
    if (delay) {
      window.setTimeout(() => {
        if (backendRequest && !backendRequest.isResolved) {
          loadingDirective();
        }
      }, delay);
      return;
    }
    loadingDirective();
  }
  getLoadingDirectives(component, element) {
    const loadingDirectives = [];
    let matchingElements = [...Array.from(element.querySelectorAll("[data-loading]"))];
    matchingElements = matchingElements.filter((elt) => elementBelongsToThisComponent(elt, component));
    if (element.hasAttribute("data-loading")) {
      matchingElements = [element, ...matchingElements];
    }
    matchingElements.forEach((element2) => {
      if (!(element2 instanceof HTMLElement) && !(element2 instanceof SVGElement)) {
        throw new Error("Invalid Element Type");
      }
      const directives = parseDirectives(element2.dataset.loading || "show");
      loadingDirectives.push({
        element: element2,
        directives
      });
    });
    return loadingDirectives;
  }
  showElement(element) {
    element.style.display = "revert";
  }
  hideElement(element) {
    element.style.display = "none";
  }
  addClass(element, classes) {
    element.classList.add(...combineSpacedArray(classes));
  }
  removeClass(element, classes) {
    element.classList.remove(...combineSpacedArray(classes));
    if (element.classList.length === 0) {
      element.removeAttribute("class");
    }
  }
  addAttributes(element, attributes) {
    attributes.forEach((attribute) => {
      element.setAttribute(attribute, "");
    });
  }
  removeAttributes(element, attributes) {
    attributes.forEach((attribute) => {
      element.removeAttribute(attribute);
    });
  }
};
var parseLoadingAction = (action, isLoading) => {
  switch (action) {
    case "show":
      return isLoading ? "show" : "hide";
    case "hide":
      return isLoading ? "hide" : "show";
    case "addClass":
      return isLoading ? "addClass" : "removeClass";
    case "removeClass":
      return isLoading ? "removeClass" : "addClass";
    case "addAttribute":
      return isLoading ? "addAttribute" : "removeAttribute";
    case "removeAttribute":
      return isLoading ? "removeAttribute" : "addAttribute";
  }
  throw new Error(`Unknown data-loading action "${action}"`);
};

// src/Component/plugins/PageUnloadingPlugin.ts
var PageUnloadingPlugin_default = class {
  constructor() {
    this.isConnected = false;
  }
  attachToComponent(component) {
    component.on("render:started", (html, response, controls) => {
      if (!this.isConnected) {
        controls.shouldRender = false;
      }
    });
    component.on("connect", () => {
      this.isConnected = true;
    });
    component.on("disconnect", () => {
      this.isConnected = false;
    });
  }
};

// src/PollingDirector.ts
var PollingDirector_default = class {
  constructor(component) {
    this.isPollingActive = true;
    this.pollingIntervals = [];
    this.component = component;
  }
  addPoll(actionName, duration) {
    this.polls.push({ actionName, duration });
    if (this.isPollingActive) {
      this.initiatePoll(actionName, duration);
    }
  }
  startAllPolling() {
    if (this.isPollingActive) {
      return;
    }
    this.isPollingActive = true;
    this.polls.forEach(({ actionName, duration }) => {
      this.initiatePoll(actionName, duration);
    });
  }
  stopAllPolling() {
    this.isPollingActive = false;
    this.pollingIntervals.forEach((interval) => {
      clearInterval(interval);
    });
  }
  clearPolling() {
    this.stopAllPolling();
    this.polls = [];
    this.startAllPolling();
  }
  initiatePoll(actionName, duration) {
    let callback;
    if (actionName === "$render") {
      callback = () => {
        this.component.render();
      };
    } else {
      callback = () => {
        this.component.action(actionName, {}, 0);
      };
    }
    const timer = window.setInterval(() => {
      callback();
    }, duration);
    this.pollingIntervals.push(timer);
  }
};

// src/Component/plugins/PollingPlugin.ts
var PollingPlugin_default = class {
  attachToComponent(component) {
    this.element = component.element;
    this.pollingDirector = new PollingDirector_default(component);
    this.initializePolling();
    component.on("connect", () => {
      this.pollingDirector.startAllPolling();
    });
    component.on("disconnect", () => {
      this.pollingDirector.stopAllPolling();
    });
    component.on("render:finished", () => {
      this.initializePolling();
    });
  }
  addPoll(actionName, duration) {
    this.pollingDirector.addPoll(actionName, duration);
  }
  clearPolling() {
    this.pollingDirector.clearPolling();
  }
  initializePolling() {
    this.clearPolling();
    if (this.element.dataset.poll === void 0) {
      return;
    }
    const rawPollConfig = this.element.dataset.poll;
    const directives = parseDirectives(rawPollConfig || "$render");
    directives.forEach((directive) => {
      let duration = 2e3;
      directive.modifiers.forEach((modifier) => {
        switch (modifier.name) {
          case "delay":
            if (modifier.value) {
              duration = Number.parseInt(modifier.value);
            }
            break;
          default:
            console.warn(`Unknown modifier "${modifier.name}" in data-poll "${rawPollConfig}".`);
        }
      });
      this.addPoll(directive.action, duration);
    });
  }
};

// src/Component/plugins/SetValueOntoModelFieldsPlugin.ts
var SetValueOntoModelFieldsPlugin_default = class {
  attachToComponent(component) {
    this.synchronizeValueOfModelFields(component);
    component.on("render:finished", () => {
      this.synchronizeValueOfModelFields(component);
    });
  }
  /**
   * Sets the "value" of all model fields to the component data.
   *
   * This is called when the component initializes and after re-render.
   * Take the following element:
   *
   *      <input data-model="firstName">
   *
   * This method will set the "value" of that element to the value of
   * the "firstName" model.
   */
  synchronizeValueOfModelFields(component) {
    component.element.querySelectorAll("[data-model]").forEach((element) => {
      if (!(element instanceof HTMLElement)) {
        throw new Error("Invalid element using data-model.");
      }
      if (element instanceof HTMLFormElement) {
        return;
      }
      if (!elementBelongsToThisComponent(element, component)) {
        return;
      }
      const modelDirective = getModelDirectiveFromElement(element);
      if (!modelDirective) {
        return;
      }
      const modelName = modelDirective.action;
      if (component.getUnsyncedModels().includes(modelName)) {
        return;
      }
      if (component.valueStore.has(modelName)) {
        setValueOnElement(element, component.valueStore.get(modelName));
      }
      if (element instanceof HTMLSelectElement && !element.multiple) {
        component.valueStore.set(modelName, getValueFromElement(element, component.valueStore));
      }
    });
  }
};

// src/Component/plugins/ValidatedFieldsPlugin.ts
var ValidatedFieldsPlugin_default = class {
  attachToComponent(component) {
    component.on("model:set", (modelName) => {
      this.handleModelSet(modelName, component.valueStore);
    });
  }
  handleModelSet(modelName, valueStore) {
    if (valueStore.has("validatedFields")) {
      const validatedFields = [...valueStore.get("validatedFields")];
      if (!validatedFields.includes(modelName)) {
        validatedFields.push(modelName);
      }
      valueStore.set("validatedFields", validatedFields);
    }
  }
};

// src/live_controller.ts
var _LiveControllerDefault = class _LiveControllerDefault extends _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller {
  constructor() {
    super(...arguments);
    this.pendingActionTriggerModelElement = null;
    this.elementEventListeners = [
      { event: "input", callback: (event) => this.handleInputEvent(event) },
      { event: "change", callback: (event) => this.handleChangeEvent(event) }
    ];
    this.pendingFiles = {};
  }
  initialize() {
    this.mutationObserver = new MutationObserver(this.onMutations.bind(this));
    this.createComponent();
  }
  connect() {
    this.connectComponent();
    this.mutationObserver.observe(this.element, {
      attributes: true
    });
  }
  disconnect() {
    this.disconnectComponent();
    this.mutationObserver.disconnect();
  }
  /**
   * Called to update one piece of the model.
   *
   *      <button data-action="live#update" data-model="foo" data-value="5">
   */
  update(event) {
    if (event.type === "input" || event.type === "change") {
      throw new Error(
        `Since LiveComponents 2.3, you no longer need data-action="live#update" on form elements. Found on element: ${getElementAsTagText(
          event.currentTarget
        )}`
      );
    }
    this.updateModelFromElementEvent(event.currentTarget, null);
  }
  action(event) {
    const params = event.params;
    if (!params.action) {
      throw new Error(
        `No action name provided on element: ${getElementAsTagText(
          event.currentTarget
        )}. Did you forget to add the "data-live-action-param" attribute?`
      );
    }
    const rawAction = params.action;
    const actionArgs = { ...params };
    delete actionArgs.action;
    const directives = parseDirectives(rawAction);
    let debounce = false;
    directives.forEach((directive) => {
      let pendingFiles = {};
      const validModifiers = /* @__PURE__ */ new Map();
      validModifiers.set("stop", () => {
        event.stopPropagation();
      });
      validModifiers.set("self", () => {
        if (event.target !== event.currentTarget) {
          return;
        }
      });
      validModifiers.set("debounce", (modifier) => {
        debounce = modifier.value ? Number.parseInt(modifier.value) : true;
      });
      validModifiers.set("files", (modifier) => {
        if (!modifier.value) {
          pendingFiles = this.pendingFiles;
        } else if (this.pendingFiles[modifier.value]) {
          pendingFiles[modifier.value] = this.pendingFiles[modifier.value];
        }
      });
      directive.modifiers.forEach((modifier) => {
        if (validModifiers.has(modifier.name)) {
          const callable = validModifiers.get(modifier.name) ?? (() => {
          });
          callable(modifier);
          return;
        }
        console.warn(
          `Unknown modifier ${modifier.name} in action "${rawAction}". Available modifiers are: ${Array.from(
            validModifiers.keys()
          ).join(", ")}.`
        );
      });
      for (const [key, input] of Object.entries(pendingFiles)) {
        if (input.files) {
          this.component.files(key, input);
        }
        delete this.pendingFiles[key];
      }
      this.component.action(directive.action, actionArgs, debounce);
      if (getModelDirectiveFromElement(event.currentTarget, false)) {
        this.pendingActionTriggerModelElement = event.currentTarget;
      }
    });
  }
  $render() {
    return this.component.render();
  }
  emit(event) {
    this.getEmitDirectives(event).forEach(({ name, data, nameMatch }) => {
      this.component.emit(name, data, nameMatch);
    });
  }
  emitUp(event) {
    this.getEmitDirectives(event).forEach(({ name, data, nameMatch }) => {
      this.component.emitUp(name, data, nameMatch);
    });
  }
  emitSelf(event) {
    this.getEmitDirectives(event).forEach(({ name, data }) => {
      this.component.emitSelf(name, data);
    });
  }
  /**
   * Update a model value.
   *
   * @param {string} model The model to update
   * @param {any} value The new value
   * @param {boolean} shouldRender Whether a re-render should be triggered
   * @param {number|boolean} debounce
   */
  $updateModel(model, value, shouldRender = true, debounce = true) {
    return this.component.set(model, value, shouldRender, debounce);
  }
  propsUpdatedFromParentValueChanged() {
    this.component._updateFromParentProps(this.propsUpdatedFromParentValue);
  }
  fingerprintValueChanged() {
    this.component.fingerprint = this.fingerprintValue;
  }
  getEmitDirectives(event) {
    const params = event.params;
    if (!params.event) {
      throw new Error(
        `No event name provided on element: ${getElementAsTagText(
          event.currentTarget
        )}. Did you forget to add the "data-live-event-param" attribute?`
      );
    }
    const eventInfo = params.event;
    const eventArgs = { ...params };
    delete eventArgs.event;
    const directives = parseDirectives(eventInfo);
    const emits = [];
    directives.forEach((directive) => {
      let nameMatch = null;
      directive.modifiers.forEach((modifier) => {
        switch (modifier.name) {
          case "name":
            nameMatch = modifier.value;
            break;
          default:
            throw new Error(`Unknown modifier ${modifier.name} in event "${eventInfo}".`);
        }
      });
      emits.push({
        name: directive.action,
        data: eventArgs,
        nameMatch
      });
    });
    return emits;
  }
  createComponent() {
    const id = this.element.id || null;
    this.component = new Component(
      this.element,
      this.nameValue,
      this.propsValue,
      this.listenersValue,
      id,
      _LiveControllerDefault.backendFactory(this),
      new StimulusElementDriver(this)
    );
    this.proxiedComponent = proxifyComponent(this.component);
    Object.defineProperty(this.element, "__component", {
      value: this.proxiedComponent,
      writable: true
    });
    if (this.hasDebounceValue) {
      this.component.defaultDebounce = this.debounceValue;
    }
    const plugins = [
      new LoadingPlugin_default(),
      new LazyPlugin_default(),
      new ValidatedFieldsPlugin_default(),
      new PageUnloadingPlugin_default(),
      new PollingPlugin_default(),
      new SetValueOntoModelFieldsPlugin_default(),
      new ChildComponentPlugin_default(this.component)
    ];
    plugins.forEach((plugin) => {
      this.component.addPlugin(plugin);
    });
  }
  connectComponent() {
    this.component.connect();
    this.mutationObserver.observe(this.element, {
      attributes: true
    });
    this.elementEventListeners.forEach(({ event, callback }) => {
      this.component.element.addEventListener(event, callback);
    });
    this.dispatchEvent("connect");
  }
  disconnectComponent() {
    this.component.disconnect();
    this.elementEventListeners.forEach(({ event, callback }) => {
      this.component.element.removeEventListener(event, callback);
    });
    this.dispatchEvent("disconnect");
  }
  handleInputEvent(event) {
    const target = event.target;
    if (!target) {
      return;
    }
    this.updateModelFromElementEvent(target, "input");
  }
  handleChangeEvent(event) {
    const target = event.target;
    if (!target) {
      return;
    }
    this.updateModelFromElementEvent(target, "change");
  }
  /**
   * Sets a model given an element and some event.
   *
   * This parses the "data-model" from the element and takes
   * into account modifiers like "debounce", "norender" and "on()".
   *
   * This is used, for example, the grab the new value from an input
   * on "change" and set that new value onto the model.
   *
   * It's also used to, on click, set the value from a button
   * with data-model="" and data-value"".
   *
   * @param element
   * @param eventName If specified (e.g. "input" or "change"), the model may
   *                  skip updating if the on() modifier is passed (e.g. on(change)).
   *                  If not passed, the model will always be updated.
   */
  updateModelFromElementEvent(element, eventName) {
    if (!elementBelongsToThisComponent(element, this.component)) {
      return;
    }
    if (!(element instanceof HTMLElement)) {
      throw new Error("Could not update model for non HTMLElement");
    }
    if (element instanceof HTMLInputElement && element.type === "file") {
      const key = element.name;
      if (element.files?.length) {
        this.pendingFiles[key] = element;
      } else if (this.pendingFiles[key]) {
        delete this.pendingFiles[key];
      }
    }
    const modelDirective = getModelDirectiveFromElement(element, false);
    if (!modelDirective) {
      return;
    }
    const modelBinding = get_model_binding_default(modelDirective);
    if (!modelBinding.targetEventName) {
      modelBinding.targetEventName = "input";
    }
    if (this.pendingActionTriggerModelElement === element) {
      modelBinding.shouldRender = false;
    }
    if (eventName === "change" && modelBinding.targetEventName === "input") {
      modelBinding.targetEventName = "change";
    }
    if (eventName && modelBinding.targetEventName !== eventName) {
      return;
    }
    if (false === modelBinding.debounce) {
      if (modelBinding.targetEventName === "input") {
        modelBinding.debounce = true;
      } else {
        modelBinding.debounce = 0;
      }
    }
    const finalValue = getValueFromElement(element, this.component.valueStore);
    if (isTextualInputElement(element) || isTextareaElement(element)) {
      if (modelBinding.minLength !== null && typeof finalValue === "string" && finalValue.length < modelBinding.minLength) {
        return;
      }
      if (modelBinding.maxLength !== null && typeof finalValue === "string" && finalValue.length > modelBinding.maxLength) {
        return;
      }
    }
    if (isNumericalInputElement(element)) {
      const numericValue = Number(finalValue);
      if (modelBinding.minValue !== null && numericValue < modelBinding.minValue) {
        return;
      }
      if (modelBinding.maxValue !== null && numericValue > modelBinding.maxValue) {
        return;
      }
    }
    this.component.set(modelBinding.modelName, finalValue, modelBinding.shouldRender, modelBinding.debounce);
  }
  dispatchEvent(name, detail = {}, canBubble = true, cancelable = false) {
    detail.controller = this;
    detail.component = this.proxiedComponent;
    this.dispatch(name, { detail, prefix: "live", cancelable, bubbles: canBubble });
  }
  onMutations(mutations) {
    mutations.forEach((mutation) => {
      if (mutation.type === "attributes" && mutation.attributeName === "id" && this.element.id !== this.component.id) {
        this.disconnectComponent();
        this.createComponent();
        this.connectComponent();
      }
    });
  }
};
_LiveControllerDefault.values = {
  name: String,
  url: String,
  props: { type: Object, default: {} },
  propsUpdatedFromParent: { type: Object, default: {} },
  listeners: { type: Array, default: [] },
  eventsToEmit: { type: Array, default: [] },
  eventsToDispatch: { type: Array, default: [] },
  debounce: { type: Number, default: 150 },
  fingerprint: { type: String, default: "" },
  requestMethod: { type: String, default: "post" }
};
_LiveControllerDefault.backendFactory = (controller) => new Backend_default(controller.urlValue, controller.requestMethodValue);
var LiveControllerDefault = _LiveControllerDefault;



/***/ },

/***/ "./node_modules/@symfony/ux-turbo/dist/turbo_controller.js"
/*!*****************************************************************!*\
  !*** ./node_modules/@symfony/ux-turbo/dist/turbo_controller.js ***!
  \*****************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ turbo_controller_default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
/* harmony import */ var _hotwired_turbo__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @hotwired/turbo */ "./node_modules/@hotwired/turbo/dist/turbo.es2017-esm.js");
// src/turbo_controller.ts


var turbo_controller_default = class extends _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller {
};



/***/ }

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./assets/app.js"));
/******/ }
]);