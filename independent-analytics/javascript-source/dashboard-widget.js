import {Application} from "@hotwired/stimulus"
import ChartController from "./controllers/chart"

window.Stimulus = Application.start()

Stimulus.register("chart", ChartController)
