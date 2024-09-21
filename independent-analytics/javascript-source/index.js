import {Application} from "@hotwired/stimulus"
import MicroModal from "micromodal"

document.addEventListener("DOMContentLoaded", () => MicroModal.init())

import CampaignBuilderController from "./controllers/campaign_builder_controller"
import ChartController from "./controllers/chart_controller"
import ChartGeoController from "./controllers/chart_geo_controller"
import ChartIntervalController from "./controllers/chart_interval_controller"
import ClipboardController from "./controllers/clipboard_controller"
import CopyReportController from "./controllers/copy_report_controller"
import CreateReportController from "./controllers/create_report_controller"
import DeleteDataController from "./controllers/delete_data_controller"
import DeleteReportController from "./controllers/delete_report_controller"
import EasepickController from "./controllers/easepick_controller"
import ExportReportsController from "./controllers/export_reports_controller"
import FiltersController from "./controllers/filters_controller"
import GroupController from "./controllers/group_controller"
import ImportReportController from "./controllers/import_reports_controller"
import MigrationRedirectController from "./controllers/migration_redirect_controller"
import ModalController from "./controllers/modal_controller"
import PluginGroupOptions from "./controllers/plugin_group_options_controller"
import PrunerController from "./controllers/pruner_controller"
import QuickStatsController from "./controllers/quick_stats_controller"
import RealTimeController from "./controllers/real_time_controller"
import RenameReportController from "./controllers/rename_report_controller"
import ReportController from "./controllers/report_controller"
import ResetAnalyticsController from "./controllers/reset_analytics_controller";
import SaveReportController from "./controllers/save_report_controller";
import SelectInputController from "./controllers/select_input_controller"
import SetFavoriteReportController from "./controllers/set_favorite_report_controller"
import SortController from "./controllers/sort_controller"
import SortableReportsController from "./controllers/sortable_reports_controller"
import TableColumnsController from "./controllers/table_columns_controller"
import WooCommerceSettingsController from "./controllers/woocommerce_settings_controller"

window.Stimulus = Application.start()

Stimulus.register("campaign-builder", CampaignBuilderController)
Stimulus.register("chart", ChartController)
Stimulus.register("chart-geo", ChartGeoController)
Stimulus.register("chart-interval", ChartIntervalController)
Stimulus.register("clipboard", ClipboardController)
Stimulus.register("table-columns", TableColumnsController)
Stimulus.register("copy-report", CopyReportController)
Stimulus.register("delete-data", DeleteDataController)
Stimulus.register('delete-report', DeleteReportController)
Stimulus.register("easepick", EasepickController)
Stimulus.register("export-reports", ExportReportsController)
Stimulus.register("filters", FiltersController)
Stimulus.register("group", GroupController)
Stimulus.register('import-reports', ImportReportController)
Stimulus.register("migration-redirect", MigrationRedirectController)
Stimulus.register("modal", ModalController)
Stimulus.register("plugin-group-options", PluginGroupOptions)
Stimulus.register("pruner", PrunerController)
Stimulus.register("quick-stats", QuickStatsController)
Stimulus.register("create-report", CreateReportController)
Stimulus.register("real-time", RealTimeController)
Stimulus.register("rename-report", RenameReportController)
Stimulus.register("report", ReportController)
Stimulus.register("reset-analytics", ResetAnalyticsController)
Stimulus.register("save-report", SaveReportController)
Stimulus.register("select-input", SelectInputController)
Stimulus.register("set-favorite-report", SetFavoriteReportController)
Stimulus.register("sort", SortController)
Stimulus.register("sortable-reports", SortableReportsController)
Stimulus.register("table-columns", TableColumnsController)
Stimulus.register("woocommerce-settings", WooCommerceSettingsController)
