((drupalSettings) => {
  const months = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ];

  const doughnutColors = [
    '#F7464A',
    '#46BFBD',
    '#FDB45C',
    '#949FB1',
    '#4D5360',
  ];

  const lineChartData = {
    labels: [],
    datasets: [
      {
        fillColor: 'rgba(251,216,124,1)',
        strokeColor: 'rgba(251,216,124,1)',
        pointColor: 'rgba(220,220,220,1)',
        pointStrokeColor: '#fff',
        data: [],
      },
      {
        fillColor: 'rgba(77,175,246,1)',
        strokeColor: 'rgba(77,175,246,1)',
        pointColor: 'rgba(151,187,205,1)',
        pointStrokeColor: '#fff',
        data: [],
      },
    ],
    options: {
      responsive: false,
      maintainAspectRation: false,
      scales: {
        yAxes: [
          {
            ticks: {
              beginAtZero: true,
            },
          },
        ],
      },
    },
  };

  /**
   * Generate a Doughnut graph.
   *
   * @param {object} pageInsights
   * @param {string} prop
   * @param {string} selector
   */
  const generateDoughnutGraph = (pageInsights, prop, selector) => {
    const doughnutData = [];

    if (!pageInsights) {
      return;
    }

    if (pageInsights.hasOwnProperty(prop)) {
      const data = pageInsights[prop];

      if (data) {
        for (let i = 0; i < data.length; i++) {
          const color =
            doughnutColors[Math.floor(Math.random() * doughnutColors.length)];

          doughnutData.push({
            value: data[i].count,
            color,
          });
        }
      }

      const graphWrapper = document.getElementsByClassName(selector);

      if (graphWrapper) {
        for (let x = 0; x < graphWrapper.length; x++) {
          new Chart(
            graphWrapper[x].querySelector('canvas').getContext('2d')
          ).Doughnut(doughnutData);
        }
      }
    }
  };

  /**
   * Generate a Line graph.
   */
  const generateLineGraph = () => {
    if (drupalSettings.ic.page_insights.total_facebook_followers_raw) {
      const totalFbFollowersRaw =
        drupalSettings.ic.page_insights.total_facebook_followers_raw;

      for (let i = 0; i < totalFbFollowersRaw.length; i++) {
        const date = new Date(totalFbFollowersRaw[i].end_time);
        const foundMonth = months[date.getMonth()];

        if (date.getDate() === 15 || date.getDate() === 30) {
          lineChartData.labels.push(`${foundMonth} ${date.getDate()}`);
          lineChartData.datasets[0].data.push(totalFbFollowersRaw[i].value);
        } else if (date.getDate() === 28 || date.getDate() === 29) {
          if (date.getMonth() + 1 === 2) {
            lineChartData.labels.push(`${foundMonth} ${date.getDate()}`);
            lineChartData.datasets[0].data.push(totalFbFollowersRaw[i].value);
          }
        }
      }
    }

    if (drupalSettings.ic.ig_insights.total_instagram_followers) {
      const totalInstagramFollowers =
        drupalSettings.ic.ig_insights.total_instagram_followers;
      // There is currently no date data to from IG to feed on this graph.
      // So we are doing some approximation here.
      lineChartData.datasets[1].data.push(
        totalInstagramFollowers - Math.floor(Math.random() * 100) + 1
      );
      lineChartData.datasets[1].data.push(totalInstagramFollowers);
    }

    new Chart(document.getElementById('line').getContext('2d')).Line(
      lineChartData
    );
  };

  generateDoughnutGraph(
    drupalSettings.ic.page_insights,
    'gender_age_followers',
    'gender-age-followers'
  );
  generateDoughnutGraph(
    drupalSettings.ic.ig_insights,
    'gender_age_followers',
    'ig-gender-age-followers'
  );
  generateDoughnutGraph(
    drupalSettings.ic.page_insights,
    'location_followers',
    'location-followers'
  );
  generateDoughnutGraph(
    drupalSettings.ic.ig_insights,
    'location_followers',
    'ig-location-followers'
  );

  generateLineGraph();
})(drupalSettings);
