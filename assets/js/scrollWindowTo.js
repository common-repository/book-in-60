jQuery.scrollWindowTo = function(pos, duration, cb) {
  if (duration == null) {
    duration = 0;
  }
  if (pos === jQuery(window).scrollTop()) {
    jQuery(window).trigger('scroll');
    if (typeof cb === "function") {
      cb();
    }
    return;
  }
  return jQuery('html, body').animate({
    scrollTop: pos
  }, duration, function() {
    return typeof cb === "function" ? cb() : void 0;
  });
};