import {
  library,
  config,
  dom
} from '@fortawesome/fontawesome-svg-core'
import {
  faPlus,
  faPencil,
  faTrash,
  faUserAlt,
  faPassport,
  faMapMarkerAlt,
  faEnvelopeOpen
} from '@fortawesome/pro-light-svg-icons'
import '@fortawesome/fontawesome-svg-core/styles.css'

// configure fontawesome
config.autoAddCss = false
library.add(
  faPlus,
  faPencil,
  faTrash,
  faUserAlt,
  faPassport,
  faMapMarkerAlt,
  faEnvelopeOpen
)
dom.watch()
