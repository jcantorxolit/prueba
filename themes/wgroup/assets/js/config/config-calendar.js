'use strict';
angular.module('myModule', ['mwl.calendar'])
  .config(function (calendarConfig) {
    calendarConfig.dateFormatter = 'moment'; //use either moment or angular to format dates on the calendar. Default angular. Setting this will override any date formats you have already set.
    calendarConfig.showTimesOnWeekView = false; //Make the week view more like the day view, with the caveat that event end times are ignored.
    calendarConfig.colorTypes = {
      job: "#007AFF",
      jobSec: "rgba(0, 122, 255, 0.3)",
      home: "#804C75",
      homeSec: "rgba(128, 76, 117, 0.3)",
      toDo: "#FF6600",
      toDoSec: "rgba(255, 102, 0, 0.3)",
      cancelled: "#FFB848",
      cancelledSec: "rgba(255, 184, 72, 0.3)",
      generic: "#46b8da",
      genericSec: "rgba(70, 184, 218, 0.3)",
      offSiteWork: "#1FBBA6",
      offSiteWorkSec: "rgba(70, 184, 218, 0.3)"
    }
  });