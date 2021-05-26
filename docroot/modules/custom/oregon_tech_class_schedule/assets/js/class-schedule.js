(function ($, Drupal, drupalSettings, window) {

  "use strict";

  // Windows vars
  let win = $(window);


  let loadingAnimationSvg = `<div class="loading-animation"><svg xmlns="http://www.w3.org/2000/svg" version="1.1">
      <defs>
        <filter id="gooey">
          <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur"></feGaussianBlur>
          <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -7" result="goo"></feColorMatrix>
          <feBlend in="SourceGraphic" in2="goo"></feBlend>
        </filter>
      </defs>
    </svg>
    <div class="blob blob-0"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="blob blob-5"></div></div>`;

  var parseCourseData = function (data) {

    let $resultContainer = $("#block_form_result");
    $resultContainer.html('');

    const distinct = (value, index, self) => {
      return self.indexOf(value) === index;
    }

    //get the list of unique course subject/number pairs
    var results = "";
    let courseDivisions = [];
    data.forEach((item) => {
      courseDivisions.push(`${item.subjectCode}${item.courseNumber}`)
    });

    courseDivisions = courseDivisions.filter(distinct);
    courseDivisions.forEach((item) => {
      let filteredList = data.filter((value, index) => {
        return (item == `${value.subjectCode}${value.courseNumber}`);
      });

      let group_name = item;

      let coursePermutations = [];
      filteredList.forEach((item) => {
        coursePermutations.push(item.title);
      });
      coursePermutations = coursePermutations.filter(distinct);
      coursePermutations.forEach((item) => {
        let filteredSubList = data.filter((value, index) => {
          return (item == `${value.title}`);
        });
        let table = $(`<table class="CourseTable footable">
                  <thead>
                    <tr class="CourseCell">
                      <th colspan="42">${group_name}</th>
                    </tr>
                  </thead>
                  <tbody>
                      <tr class="CourseName group">
                        <td colspan="6">${item}</td>
                        <td colspan="3" class="text-right">
                          <a href="javascript:void(0);" onclick="alert('view description');">View Description</a></td>
                      </tr>
                      <tr class="CourseHeadingRow">
                        <td>CRN</td><td>Sec.</td><td>Credits</td><td>Days</td><td>Time</td><td>Location</td><td>Instructor</td>
                        <td>Status</td><td>Textbook</td>
                      </tr>
                    </tbody>
                  </table>`);
        filteredSubList.forEach((item) => {
          var days = "";
          var times = "";
          var locations = "";
          var first = false;
          item.schedule.forEach((scheduleItem) => {
            if (!first) {
              first = true;
            }
            else {
              days += "<br />";
              times += "<br />";
              locations += "<br />";
            }
            days += scheduleItem.days;
            times += `${scheduleItem.startTime}-${scheduleItem.endTime}`;
            locations += scheduleItem.location;

          });
          let courseRow = `<tr>
                        <td>${item.crn}</td>
                        <td>${item.section}</td>
                        <td>${item.creditHours}</td>
                        <td>${days}</td>
                        <td>${times}</td>
                        <td>${locations}</td>
                        <td>${item.instructor}</td>
                        <td>${item.status} (${item.seatsTaken}/${item.seatsCapacity})</td>
                        <td></td>
                        </tr>`;
          table.find("tbody").append(courseRow)


        });
        $('#block_form_result').append(table)
      });


    });

  }

  /**
   * Drupal Ajax behaviours and ajax prototypes
   * @type {{attach: attach, detach: detach}}
   */
  Drupal.behaviors.classSchedule = {
    attach: function (context, settings) {

      var $searchTrigger = $('.search-class-schedule', context);
      if ($searchTrigger.length) {
        $searchTrigger.once().on('click', function (e) {
          e.preventDefault();

          let term = $('select[name="term"]').val();
          var params = {
            subject: $('select[name="subject"]').val(),
            level: $('select[name="level"]').val(),
            campus: $('select[name="campus"]').val(),
            materialCost: $('select[name="materialCost"]').val(),
            startTime: $('select[name="startTime"]').val(),
            endTime: $('select[name="endTime"]').val(),
            days: $.map($('.week-days[type="checkbox"]:checked'), function (box) {
              return $(box).val();
            }).join(",")
          };

          var esc = encodeURIComponent;
          var query = Object.keys(params)
            .map(k => {
              return (params[k].length > 0 ? esc(k) + '=' + esc(params[k]) : "");
            })
            .join('&');

          // Set loading animation.
          $("#block_form_result").html(loadingAnimationSvg);

          $.get(`https://app-otfacts.azurewebsites.net/api/courses/${term}?${query}`, "json")
            .done(parseCourseData).fail(function (data) {
            $("#block_form_result").html('<h3>No Result Found.</h3>');
          });

        });

      }

    },
    detach: function (context) {
    }
  };

  $(document).ready(function () {
    $('.search-class-schedule').trigger('click');
  });

}(jQuery, Drupal, drupalSettings, window));
