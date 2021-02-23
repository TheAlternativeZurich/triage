/**
 * Code copied from https://codepen.io/ARS/pen/pjypwd
 * slight adaptations
 */

require('./libs/TweenMax.min.js')

var tl = new TimelineMax()
var path = '.shattering svg *'
var stagger_val = 0.01
var duration = 0.5

$.each($(path), function (i, el) {
  tl.set($(this), {
    x: '+=' + getRandom(-500, 500),
    y: '+=' + getRandom(-500, 500),
    rotation: '+=' + getRandom(-720, 720),
    scale: 0,
    opacity: 0
  })
})

var stagger_opts_to = {
  x: 0,
  y: 0,
  opacity: 1,
  scale: 1,
  rotation: 0,
  ease: Power4.easeOut
}

tl.staggerTo(path, duration, stagger_opts_to, stagger_val)

$('.shattering').addClass('show')

function getRandom (min, max) {
  return Math.random() * (max - min) + min
}
