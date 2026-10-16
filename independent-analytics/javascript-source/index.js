import {Application} from "@hotwired/stimulus"
import MicroModal from "micromodal"
import * as controllerModules from "./controllers/**/*.js"
import {loadStimulusControllers} from "./utils/stimulus-controllers"

document.addEventListener("DOMContentLoaded", () => MicroModal.init())

document.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
        // The Examiner is loaded using an iframe. This messages lets the iframed element alert
        // the parent that's completely loaded and ready to be shown.
        window.parent.postMessage('iawpPageReady');
    }, 0)
})

window.Stimulus = Application.start()

loadStimulusControllers(Stimulus, controllerModules)
