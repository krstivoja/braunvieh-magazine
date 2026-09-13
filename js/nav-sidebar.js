(function (window) {
    'use strict';

    // Feature detection for CSS 3D transforms
    function supports3DTransforms() {
        var el = document.createElement('div');
        var style = el.style;
        var prop = 'transform';
        var prefixes = ['Webkit', 'Moz', 'ms', 'O'];
        
        // Check standard property
        if (prop in style) return true;
        
        // Check vendor-prefixed properties
        for (var i = 0; i < prefixes.length; i++) {
            if (prefixes[i] + 'Transform' in style) return true;
        }
        
        return false;
    }

    // Modern class management
    function classReg(className) {
        return new RegExp("(^|\\s+)" + className + "(\\s+|$)");
    }

    var classie = {
        has: function(elem, c) {
            return elem.classList ? elem.classList.contains(c) : classReg(c).test(elem.className);
        },
        add: function(elem, c) {
            if (elem.classList) {
                elem.classList.add(c);
            } else if (!this.has(elem, c)) {
                elem.className = elem.className + ' ' + c;
            }
        },
        remove: function(elem, c) {
            if (elem.classList) {
                elem.classList.remove(c);
            } else {
                elem.className = elem.className.replace(classReg(c), ' ');
            }
        },
        toggle: function(elem, c) {
            if (elem.classList) {
                elem.classList.toggle(c);
            } else {
                var fn = this.has(elem, c) ? this.remove : this.add;
                fn(elem, c);
            }
        }
    };

    function extend(a, b) {
        for (var key in b) {
            if (b.hasOwnProperty(key)) {
                a[key] = b[key];
            }
        }
        return a;
    }

    // taken from https://github.com/inuyaksa/jquery.nicescroll/blob/master/jquery.nicescroll.js
    function hasParent(e, id) {
        if (!e) return false;
        var el = e.target || e.srcElement || e || false;
        while (el && el.id != id) {
            el = el.parentNode || false;
        }
        return (el !== false);
    }

    // returns the depth of the element "e" relative to element with id=id
    // for this calculation only parents with classname = waypoint are considered
    function getLevelDepth(e, id, waypoint, cnt) {
        cnt = cnt || 0;
        if (e.id.indexOf(id) >= 0) return cnt;
        if (classie.has(e, waypoint)) {
            ++cnt;
        }
        return e.parentNode && getLevelDepth(e.parentNode, id, waypoint, cnt);
    }

    // Modern mobile detection using matchMedia
    function mobilecheck() {
        return window.matchMedia('(max-width: 768px)').matches || 
               /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    // returns the closest element to 'e' that has class "classname"
    function closest(e, classname) {
        if (classie.has(e, classname)) {
            return e;
        }
        return e.parentNode && closest(e.parentNode, classname);
    }

    function mlPushMenu(el, trigger, options) {
        this.el = el;
        this.trigger = trigger;
        this.options = extend(this.defaults, options);
        this.support = supports3DTransforms();
        
        // Cache DOM elements
        this.wrapper = document.getElementById('mp-pusher');
        this.main = document.querySelector("#main");
        this.levels = Array.prototype.slice.call(this.el.querySelectorAll('div.mp-level'));
        this.menuItems = Array.prototype.slice.call(this.el.querySelectorAll('li'));
        this.levelBack = Array.prototype.slice.call(this.el.querySelectorAll('.' + this.options.backClass));
        
        // Use pointer events if supported
        this.eventtype = window.PointerEvent ? 'pointerdown' : 
                        mobilecheck() ? 'touchstart' : 'click';
                        
        if (this.support) {
            this._init();
        }
    }

    mlPushMenu.prototype = {
        defaults: {
            // overlap: there will be a gap between open levels
            // cover: the open levels will be on top of any previous open level
            type: 'overlap', // overlap || cover
            // space between each overlaped level
            levelSpacing: 40,
            // classname for the element (if any) that when clicked closes the current level
            backClass: 'mp-back'
        },
        _init: function () {
            // if menu is open or not
            this.open = false;
            // level depth
            this.level = 0;
            // save the depth of each of these mp-level elements
            var self = this;
            this.levels.forEach(function (el, i) { 
                el.setAttribute('data-level', getLevelDepth(el, self.el.id, 'mp-level'));
                // Add level class to ul elements
                var uls = el.querySelectorAll('ul');
                uls.forEach(function(ul) {
                    var level = getLevelDepth(ul, self.el.id, 'mp-level');
                    classie.add(ul, 'level-' + level);
                });
            });
            // add the class mp-overlap or mp-cover to the main element depending on options.type
            classie.add(this.el, 'mp-' + this.options.type);
            // initialize / bind the necessary events
            this._initEvents();
        },
        _initEvents: function () {
            var self = this;

            // the menu should close if clicking somewhere on the body
            var bodyClickFn = function (el) {
                self._resetMenu();
                el.removeEventListener(self.eventtype, bodyClickFn);
            };

            // open (or close) the menu
            this.trigger.addEventListener(this.eventtype, function (ev) {
                ev.stopPropagation();
                ev.preventDefault();
                if (self.open) {
                    self._resetMenu();
                }
                else {
                    self._openMenu();
                    // the menu should close if clicking somewhere on the body (excluding clicks on the menu)
                    document.addEventListener(self.eventtype, function (ev) {
                        if (self.open && !hasParent(ev.target, self.el.id)) {
                            bodyClickFn(this);
                        }
                    });
                }
            });

            // opening a sub level menu
            this.menuItems.forEach(function (el, i) {
                // check if it has a sub level
                var subLevel = el.querySelector('div.mp-level');
                if (subLevel) {
                    el.querySelector('a').addEventListener(self.eventtype, function (ev) {
                        ev.preventDefault();
                        var level = closest(el, 'mp-level').getAttribute('data-level');
                        if (self.level <= level) {
                            ev.stopPropagation();
                            classie.add(closest(el, 'mp-level'), 'mp-level-overlay');
                            self._openMenu(subLevel);
                        }
                    });
                }
            });

            // closing the sub levels :
            // by clicking on the visible part of the level element
            this.levels.forEach(function (el, i) {
                el.addEventListener(self.eventtype, function (ev) {
                    ev.stopPropagation();
                    var level = el.getAttribute('data-level');
                    if (self.level > level) {
                        self.level = level;
                        self._closeMenu();
                    }
                });
            });

            // by clicking on a specific element
            this.levelBack.forEach(function (el, i) {
                el.addEventListener(self.eventtype, function (ev) {
                    ev.preventDefault();
                    var level = closest(el, 'mp-level').getAttribute('data-level');
                    if (self.level <= level) {
                        ev.stopPropagation();
                        self.level = closest(el, 'mp-level').getAttribute('data-level') - 1;
                        self.level === 0 ? self._resetMenu() : self._closeMenu();
                    }
                });
            });
        },
        // Modify the _openMenu function to adjust marginLeft based on parent menu width
        _openMenu: function (subLevel) {
            // increment level depth
            ++this.level;

            // Set will-change for better performance
            this.wrapper.style.willChange = 'transform';
            this.main.style.willChange = 'margin-left, opacity';

            // move the main wrapper
            var levelFactor = (this.level - 1) * this.options.levelSpacing,
                translateVal = this.options.type === 'overlap' ? this.el.offsetWidth + levelFactor : this.el.offsetWidth;

            requestAnimationFrame(() => {
                this._setTransform('translate3d(' + translateVal + 'px,0,0)');
                
                if (subLevel) {
                    // reset transform for sublevel
                    this._setTransform('', subLevel);
                    // need to reset the translate value for the level menus that have the same level depth and are not open
                    for (var i = 0, len = this.levels.length; i < len; ++i) {
                        var levelEl = this.levels[i];
                        if (levelEl != subLevel && !classie.has(levelEl, 'mp-level-open')) {
                            this._setTransform('translate3d(-100%,0,0) translate3d(' + -1 * levelFactor + 'px,0,0)', levelEl);
                        }
                    }
                }

                // add class mp-pushed to main wrapper if opening the first time
                if (this.level === 1) {
                    classie.add(this.wrapper, 'mp-pushed');
                    classie.add(document.body, 'menu-open');
                    this.open = true;
                }

                // Apply transition and adjust marginLeft based on menu width
                // this.main.style.transition = "opacity 0.5s ease, margin-left 0.5s ease";
                // this.main.style.opacity = 0.6;

                // Calculate marginLeft based on current level and menu width
                // const marginLeftValue = this.el.offsetWidth + (this.level - 1) * this.options.levelSpacing;
                // this.main.style.marginLeft = marginLeftValue + "px";

                // add class mp-level-open to the opening level element
                classie.add(subLevel || this.levels[0], 'mp-level-open');
            });
        },
        // close the menu
        _resetMenu: function () {
            // Apply the same transition duration and easing for reset as in _openMenu
            const main = document.querySelector("#main");

            // Add the transition effect for marginLeft and opacity
            main.style.transition = "opacity 0.3s ease, margin-left 0.3s ease"; // Same duration and easing as _openMenu

            // Reset the transform to its original state
            this._setTransform('translate3d(0,0,0)');
            this.level = 0;

            // Reset opacity and margin-left to their original values
            main.style.opacity = 1;
            main.style.marginLeft = "0px";

            // Remove class mp-pushed from the main wrapper
            classie.remove(this.wrapper, 'mp-pushed');
            classie.remove(document.body, 'menu-open');

            // Toggle levels to reset the menu
            this._toggleLevels();

            // Set the menu state to closed
            this.open = false;
        },

        // close sub menus
        _closeMenu: function () {
            var translateVal = this.options.type === 'overlap' ? this.el.offsetWidth + (this.level - 1) * this.options.levelSpacing : this.el.offsetWidth;
            this._setTransform('translate3d(' + translateVal + 'px,0,0)');
            this._toggleLevels();
            
            // Get the main content element
            const main = document.querySelector("#main");
            // Get the parent menu's width
            const menuWidth = this.el.offsetWidth;
        
            // Apply transition and adjust marginLeft based on menu width
            // main.style.transition = "opacity 0.5s ease, margin-left 0.5s ease";
            // main.style.opacity = 0.6;
        
            // Calculate marginLeft based on current level and menu width
            // const marginLeftValue = menuWidth + (this.level - 1) * this.options.levelSpacing;
            // main.style.marginLeft = marginLeftValue + "px";
        },
        // translate the el
        _setTransform: function (val, el) {
            el = el || this.wrapper;
            var prefixes = ['', 'Webkit', 'Moz', 'ms', 'O'];
            var prop = 'transform';
            
            // Apply transform with all vendor prefixes
            for (var i = 0; i < prefixes.length; i++) {
                var prefix = prefixes[i];
                var prefixedProp = prefix ? prefix + prop.charAt(0).toUpperCase() + prop.slice(1) : prop;
                el.style[prefixedProp] = val;
            }
        },
        // removes classes mp-level-open from closing levels
        _toggleLevels: function () {
            for (var i = 0, len = this.levels.length; i < len; ++i) {
                var levelEl = this.levels[i];
                if (levelEl.getAttribute('data-level') >= this.level + 1) {
                    classie.remove(levelEl, 'mp-level-open');
                    classie.remove(levelEl, 'mp-level-overlay');
                }
                else if (Number(levelEl.getAttribute('data-level')) == this.level) {
                    classie.remove(levelEl, 'mp-level-overlay');
                }
            }
        }
    }

    // add to global namespace
    window.mlPushMenu = mlPushMenu;

})(window);

new mlPushMenu(document.getElementById('mp-menu'), document.getElementById('trigger'));



document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('search-icon').addEventListener('click', function() {
    //   document.getElementById('search').classList.toggle('search-not-active');
      document.body.classList.toggle('search-active');
    });
});