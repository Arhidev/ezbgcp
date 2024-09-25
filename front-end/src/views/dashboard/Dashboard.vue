<template>
  <v-row v-if="!isLoading">

    <v-col
      cols="12"
      sm="12"
      md="4"
    >
      <dashboard-card-total-earning :amount=totalAdminEarnings></dashboard-card-total-earning>
    </v-col>

    <v-col
      cols="12"
      md="8"
      sm="12"
    >
      <v-row class="match-height">
        <v-col
          cols="12"
          sm="4"
        >
          <statistics-card-vertical
            :change="totalReservations.change"
            :color="totalReservations.color"
            :icon="totalReservations.icon"
            :statistics="totalReservations.amount"
            :stat-title="totalReservations.statTitle"
            :subtitle="totalReservations.subtitle"
          ></statistics-card-vertical>
        </v-col>
        <v-col
          cols="12"
          sm="4"
        >
          <statistics-card-vertical
            :change="totalDriversEarnings.change"
            :color="totalDriversEarnings.color"
            :icon="totalDriversEarnings.icon"
            :statistics="totalDriversEarnings.amount"
            :stat-title="totalDriversEarnings.statTitle"
            :subtitle="totalDriversEarnings.subtitle"
          ></statistics-card-vertical>
        </v-col>
        <v-col
          cols="12"
          sm="4"
        >
          <statistics-card-vertical
            :change="totalRefunds.change"
            :color="totalRefunds.color"
            :icon="totalRefunds.icon"
            :statistics="totalRefunds.amount"
            :stat-title="totalRefunds.statTitle"
            :subtitle="totalRefunds.subtitle"
          ></statistics-card-vertical>
        </v-col>
      </v-row>
    </v-col>

    <v-col
      cols="12"
      md="12"
    >
      <dashboard-statistics-card :all-counts=allCounts></dashboard-statistics-card>
    </v-col>



    <v-col
      cols="12"
      sm="12"
      md="6"
    >
      <dashboard-weekly-overview
      :trip-count=plannedTripsCount
      :trip-dates=plannedTripsDates
      ></dashboard-weekly-overview>
    </v-col>
    <v-col
      cols="12"
      md="6"
    >
      <dashboard-card-sales-by-trips
      :best-trips=bestTrips
      ></dashboard-card-sales-by-trips>
    </v-col>
  </v-row>
  <v-row v-else>
    <v-col
      cols="12"
      sm="6"
      md="6"
    >
      <v-skeleton-loader
        type="card"
        height="200"
      ></v-skeleton-loader>
    </v-col>
    <v-col
      cols="12"
      md="6"
    >
      <v-row class="match-height">
        <v-col
          cols="12"
          sm="6"
        >
          <v-skeleton-loader
            type="card"
            height="200"
          ></v-skeleton-loader>
        </v-col>
        <v-col
          cols="12"
          sm="6"
        >
          <v-skeleton-loader
            type="card"
            height="200"
          ></v-skeleton-loader>
        </v-col>
      </v-row>
    </v-col>

    <v-col
      cols="12"
      md="12"
    >
      <v-skeleton-loader
        type="card"
        height="120"
      ></v-skeleton-loader>
    </v-col>

    <v-col
      cols="12"
      sm="6"
      md="6"
    >
      <v-skeleton-loader
        type="image"
        height="500"
      ></v-skeleton-loader>
    </v-col>

    <v-col
      cols="12"
      md="6"
    >
      <v-skeleton-loader
        type="image"
        height="500"
      ></v-skeleton-loader>
    </v-col>

  </v-row>
</template>

<script>
// eslint-disable-next-line object-curly-newline
import { mdiPoll, mdiCurrencyUsd, mdiCloseOctagonOutline } from '@mdi/js'
import StatisticsCardVertical from '@/components/statistics-card/StatisticsCardVertical.vue'

import auth from '@/services/AuthService'

// demos
import DashboardStatisticsCard from './DashboardStatisticsCard.vue'
import DashboardCardTotalEarning from './DashboardCardTotalEarning.vue'
import DashboardCardSalesByTrips from './DashboardCardSalesByTrips.vue'
import DashboardWeeklyOverview from './DashboardWeeklyOverview.vue'

export default {
  components: {
    StatisticsCardVertical,
    DashboardStatisticsCard,
    DashboardCardTotalEarning,
    DashboardCardSalesByTrips,
    DashboardWeeklyOverview,
  },
  data() {
    return {
      isLoading: false,
      totalReservations: {
        statTitle: 'Total Reservations',
        icon: mdiPoll,
        color: 'success',
        amount: '',
      },
      bestTrips: [],
      plannedTripsCount: [],
      plannedTripsDates: [],
      bestTripsColors: [
        'success', 'error', 'warning', 'secondary', 'error',
      ],
      totalDriversEarnings: {
        statTitle: 'Drivers Earnings',
        icon: mdiCurrencyUsd,
        color: 'info',
        amount: '',
      },
      totalRefunds: {
        statTitle: 'Total Refunds',
        icon: mdiCloseOctagonOutline,
        color: 'error',
        amount: '',
      },
      totalAdminEarnings: null,
      allCounts:
      [
        {
          title: 'Reservations',
          total: '',
        },
        {
          title: 'Customers',
          total: '',
        },
        {
          title: 'Drivers',
          total: '',
        },
        {
          title: 'Routes',
          total: '',
        },
        {
          title: 'Stops',
          total: '',
        },
        {
          title: 'Trips',
          total: '',
        },
      ],
    }
  },
  mounted() {
    this.fetchDashboardStatistics()
  },
  methods: {
    fetchDashboardStatistics() {
      this.isLoading = true;
      this.reservations = [];
      axios
        .get(`/dashboard/all`)
        .then((response) => {
          this.totalReservations.amount = response.data.totalReservations;
          this.totalDriversEarnings.amount = response.data.totalDriversEarnings;
          this.totalRefunds.amount = response.data.totalRefunds;
          this.totalAdminEarnings = response.data.totalAdminEarnings;
          this.allCounts[0].total = response.data.totalReservationsCount;
          this.allCounts[1].total = response.data.totalCustomers;
          this.allCounts[2].total = response.data.totalDrivers;
          this.allCounts[3].total = response.data.totalRoutes;
          this.allCounts[4].total = response.data.totalStops;
          this.allCounts[5].total = response.data.totalTrips;
          this.bestTrips = response.data.bestTrips;
          //merge bestTrips with bestTripsColors
          this.bestTrips.forEach((item, index) => {
            item.color = this.bestTripsColors[index];
          });
          for (var key in response.data.plannedTrips) {
            this.plannedTripsCount.push(response.data.plannedTrips[key]);
            this.plannedTripsDates.push(key);
          }
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving dashboard statistics",
            type: "error",
          });
          console.log(error);
          auth.checkError(error.response.data.message, this.$router, this.$swal);
        })
        .then(() => {
          this.isLoading = false;
        });
    },
  },
}
</script>

<style lang="scss">
.v-skeleton-loader__image {
    height: 400px !important;
}
</style>
