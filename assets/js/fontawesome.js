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
  faEnvelopeOpen,
  faShare,
  faCalendarCheck
} from '@fortawesome/pro-light-svg-icons'

// configure fontawesome
config.autoAddCss = false
library.add(
  faPlus,
  faPencil,
  faTrash,
  faUserAlt,
  faPassport,
  faMapMarkerAlt,
  faEnvelopeOpen,
  faShare,
  faCalendarCheck
)
dom.watch()
