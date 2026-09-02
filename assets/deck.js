/*
 * The client deck's navigation.
 *
 * Two jobs, and nothing else: scale the 1600 x 900 stage to fit whatever
 * viewport the deck is opened on, and move between sections. Below 900px the
 * deck is a scrolling document rather than a presentation, so both jobs turn
 * themselves off — a whole slide shrunk onto a phone is unreadable, which is
 * the thing this deliberately does not do.
 */
(function () {
  'use strict';

  var STAGE_W = 1600;
  var STAGE_H = 900;
  var NAV_H = 96;
  var FLOW_BELOW = 900;

  var root = document.querySelector('[data-bwd-deck]');
  if (!root) return;

  var slides = Array.prototype.slice.call(root.querySelectorAll('[data-bwd-slide]'));
  var dots = Array.prototype.slice.call(root.querySelectorAll('[data-bwd-go]'));
  var prev = root.querySelector('[data-bwd-prev]');
  var next = root.querySelector('[data-bwd-next]');
  if (!slides.length) return;

  var storageKey = 'bwd-section:' + window.location.pathname;
  var current = 0;

  // Where the reader was last time. A deck gets closed and reopened, and
  // landing back on the cover every time is its own small insult. Wrapped
  // because a private window can throw on the read itself, not just return
  // nothing.
  try {
    var held = parseInt(window.sessionStorage.getItem(storageKey), 10);
    if (!isNaN(held) && held >= 0 && held < slides.length) current = held;
  } catch (e) {
    current = 0;
  }

  function isFlow() {
    return window.innerWidth <= FLOW_BELOW;
  }

  function show(index) {
    current = Math.max(0, Math.min(slides.length - 1, index));

    slides.forEach(function (slide, i) {
      slide.classList.toggle('is-current', i === current);
      slide.setAttribute('aria-hidden', i === current ? 'false' : 'true');
    });
    dots.forEach(function (dot, i) {
      dot.classList.toggle('is-current', i === current);
      dot.setAttribute('aria-current', i === current ? 'true' : 'false');
    });

    if (prev) prev.disabled = current === 0;
    if (next) next.disabled = current === slides.length - 1;

    // Only now: a slide that was display:none measures nothing.
    scaleOne(slides[current]);

    try {
      window.sessionStorage.setItem(storageKey, String(current));
    } catch (e) {
      /* Nothing to do: remembering the place is a convenience, not the deck. */
    }
  }

  function showAll() {
    slides.forEach(function (slide) {
      slide.classList.add('is-current');
      slide.removeAttribute('aria-hidden');
    });
  }

  // A stage is scaled by its own height, not by the nominal 900. A slide is
  // authored at 1600 x 900, but a deck with eleven estimate phases genuinely
  // needs more room, and scaling every slide by 900 would push that one's last
  // line off the bottom. offsetHeight is the unscaled height — a transform
  // does not change layout — so this reads the real size even after a previous
  // pass has already scaled it. A hidden slide measures 0, which is why this
  // only ever runs on the slide that is on screen.
  function scaleOne(slide) {
    var stage = slide && slide.querySelector('.bwd-stage');
    if (!stage) return;

    if (isFlow()) {
      stage.style.setProperty('--bwd-scale', '1');
      return;
    }

    var height = Math.max(STAGE_H, stage.offsetHeight);
    var factor = Math.min(window.innerWidth / STAGE_W, (window.innerHeight - NAV_H) / height);
    stage.style.setProperty('--bwd-scale', String(factor));
  }

  function scale() {
    if (isFlow()) {
      slides.forEach(scaleOne);
      return;
    }
    scaleOne(slides[current]);
  }

  function apply() {
    if (isFlow()) {
      showAll();
      scale();
      return;
    }
    show(current);
  }

  if (prev) prev.addEventListener('click', function () { show(current - 1); });
  if (next) next.addEventListener('click', function () { show(current + 1); });

  dots.forEach(function (dot) {
    dot.addEventListener('click', function () {
      show(parseInt(dot.getAttribute('data-bwd-go'), 10) || 0);
    });
  });

  document.addEventListener('keydown', function (event) {
    if (isFlow()) return;
    var keys = {
      ArrowRight: 1, ArrowDown: 1, PageDown: 1, ' ': 1,
      ArrowLeft: -1, ArrowUp: -1, PageUp: -1
    };
    if (event.key === 'Home') {
      show(0);
    } else if (event.key === 'End') {
      show(slides.length - 1);
    } else if (Object.prototype.hasOwnProperty.call(keys, event.key)) {
      show(current + keys[event.key]);
    } else {
      return;
    }
    event.preventDefault();
  });

  window.addEventListener('resize', apply);
  apply();
})();
