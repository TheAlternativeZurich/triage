import '../css/app.scss'
import './fontawesome'
import './shattering'

const $ = require('jquery')
require('bootstrap')
require('typeface-open-sans')
require('flatpickr')
const Masonry = require('masonry-layout')

// attach jquery to window
window.$ = $

// register some basic usability functionality
$(document)
  .ready(() => {
    // give instant feedback on form submission
    $('form')
      .on('submit', () => {
        const $form = $(this)
        const $buttons = $('.btn', $form)
        if (!$buttons.hasClass('no-disable')) {
          $buttons.addClass('disabled')
        }
      })

    $('[data-toggle="popover"]')
      .popover()

    if ($('.masonry-grid').length) {
      window.setTimeout(function () {
        initializeMasonry()
      }, 100)

      window.setTimeout(function () {
        initializeMasonry()
      }, 1000)
    }

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault()

        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        })
      })
    })

    // force reload on user browser button navigation
    $(window)
      .on('popstate', () => {
        window.location.reload(true)
      })
  })

function initializeMasonry () {
  // eslint-disable-next-line no-new
  new Masonry('.masonry-grid', {
    columnWidth: '.masonry-grid-sizer',
    itemSelector: '.masonry-grid-item',
    percentPosition: true
  })
}
