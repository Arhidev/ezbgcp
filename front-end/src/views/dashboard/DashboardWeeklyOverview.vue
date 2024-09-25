<template>
  <v-card>
    <v-card-title class="align-start">
      <span>Trips Overview</span>
    </v-card-title>

    <v-card-text v-if="tripCount.length > 0">
      <!-- Chart -->
      <vue-apex-charts
        :options="chartOptions"
        :series="chartData"
        height="300"
      ></vue-apex-charts>

      <div class="d-flex align-center">
        <h3 class="text-2xl font-weight-semibold me-4">
          
        </h3>
      </div>

    </v-card-text>

    <v-card-text v-else>
      <div class="d-flex align-center justify-center flex-column">
        <v-icon size="150" color="grey lighten-2">mdi-emoticon-sad-outline</v-icon>
        <span class="text--secondary text-sm">No enough data found</span>
      </div>
    </v-card-text>

  </v-card>
</template>

<script>
import VueApexCharts from 'vue-apexcharts'
// eslint-disable-next-line object-curly-newline
import { mdiDotsVertical, mdiTrendingUp, mdiCurrencyUsd } from '@mdi/js'
import { getCurrentInstance } from '@vue/composition-api'

export default {
  //props
  props: {
    tripCount: {
      type: Array,
      default: () => [],
    },
    tripDates: {
      type: Array,
      default: () => [],
    },
  },
  components: {
    VueApexCharts,
  },
  computed: {
    chartData () {
      return [
        {
          name: '',
          data: this.tripCount,
        },
      ]
    },
    chartOptions () {
      return {
        colors: ['#5A8DEE', '#FF5B5C', '#FFC542', '#1BC5BD'],
        chart: {
          type: 'bar',
          toolbar: {
            show: false,
          },
          offsetX: -15,
        },
        plotOptions: {
          bar: {
            columnWidth: '40%',
            distributed: true,
            borderRadius: 8,
            startingShape: 'rounded',
            endingShape: 'rounded',
          },
        },
        dataLabels: {
          enabled: false,
        },
        legend: {
          show: false,
        },
        xaxis: {
          categories: this.tripDates,
          axisBorder: {
            show: true,
          },
          axisTicks: {
            show: true,
          },
          tickPlacement: 'on',
          labels: {
            show: true,
            style: {
              fontSize: '12px',
            },
          },
        },
        yaxis: {
          show: true,
          tickAmount: 4,
          labels: {
            offsetY: 3,
            formatter: value => `${value} trips`,
          },
        },
        stroke: {
          width: [2, 2],
        },
        grid: {
          strokeDashArray: 12,
          padding: {
            right: 0,
          },
        },
      }
    },
  },
  setup() {
    return {
      icons: {
        mdiDotsVertical,
        mdiTrendingUp,
        mdiCurrencyUsd,
      },
    }
  },
}
</script>
