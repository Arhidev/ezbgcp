<template>
  <div>
    <v-card>
      <v-card-title>
      <v-icon color="primary">
        mdi-poll
      </v-icon>
        <span class="pl-2">Reservations</span>
      </v-card-title>
      <v-tabs v-model="active_tab" show-arrows class="my-2">
        <v-tab v-for="tab in tabs" :key="tab.idx">
          <v-icon size="20" class="me-3">
            {{ tab.icon }}
          </v-icon>
          <span>{{ tab.title }}</span>
        </v-tab>
      </v-tabs>
    <v-tabs-items v-model="active_tab">
      <!-- active -->
      <v-tab-item>
        <reservations-table
        :show-cancel="true"
        :loading="isLoading"
        :reservations="activeReservations"
        @cancel-reservation="cancelReservation"
        ></reservations-table>
      </v-tab-item>

      <!-- ride -->
      <v-tab-item>
        <reservations-table
        :show-cancel="true"
        :loading="isLoading"
        @cancel-reservation="cancelReservation"
        :reservations="rideReservations"></reservations-table>
      </v-tab-item>

      <!-- missed -->
      <v-tab-item>
        <reservations-table
        :show-cancel="true"
        :loading="isLoading"
        @cancel-reservation="cancelReservation"
        :reservations="missedReservations"></reservations-table>
      </v-tab-item>

      <!-- completed -->
      <v-tab-item>
        <reservations-table
        :show-cancel="true"
        :loading="isLoading"
        @cancel-reservation="cancelReservation"
        :reservations="completedReservations"></reservations-table>
      </v-tab-item>

      <!-- cancelled -->
      <v-tab-item>
        <reservations-table
        :show-cancel="false"
        :loading="isLoading"
        :reservations="cancelledReservations"></reservations-table>
      </v-tab-item>

    </v-tabs-items>
    </v-card>
  </div>
</template>

<script>

import {
  mdiStopCircleOutline,
  mdiAccountCheck,
  mdiAccountClock,
  mdiAccountOff,
  mdiPlayCircleOutline,
  mdiTrashCan,
  mdiDeleteRestore,
  mdiAirplane,
  mdiMotionPause
} from "@mdi/js";

import reservationsTable from './reservations-table.vue';

export default {
  components: {
    reservationsTable
  },
  data() {
    return {
      activeReservations: [],
      rideReservations: [],
      completedReservations: [],
      missedReservations: [],
      cancelledReservations: [],
      isLoading: false,
      selectedReservation: null,
      search: "",
      tabs: [
        { idx: 0, title: "Upcoming", icon: mdiPlayCircleOutline },
        { idx: 1, title: "Ride", icon: mdiAccountClock },
        { idx: 2, title: "Missed", icon: mdiAccountOff },
        { idx: 3, title: "Completed", icon: mdiAccountCheck },
        { idx: 4, title: "Cancelled", icon: mdiMotionPause },
      ],
      active_tab: null,
      icons: {
        mdiStopCircleOutline,
        mdiAccountCheck,
        mdiAccountOff,
        mdiAccountClock,
        mdiPlayCircleOutline,
        mdiTrashCan,
        mdiDeleteRestore,
        mdiAirplane
      },
    };
  },
  watch: {
    active_tab: function (newVal, oldVal) {
      localStorage.tabIdxReservations = newVal;
    },
  },
  mounted() {
    this.active_tab = parseInt(localStorage.tabIdxReservations);
    this.loadReservations();
  },
  methods: {
    loadReservations() {
      this.isLoading = true;
      this.reservations = [];
      axios
        .get(`/reservations/all`)
        .then((response) => {
          this.activeReservations = response.data.active;
          this.rideReservations = response.data.ride;
          this.completedReservations = response.data.completed;
          this.missedReservations = response.data.missed;
          this.cancelledReservations = response.data.cancelled;

        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving reservations",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          this.isLoading = false;
        });
    },
    cancelReservation(reservation, index) {
      let title = "";
      if(this.active_tab >= 1 && this.active_tab <= 3)
      {
        title = "You are about to cancel this reservation. The customer will get a refund. Please note that the reservation may be redeemed by the admin or the driver. If so, you will be informed by the system and you will have to manually deduct the amount from the payment account.";
      }
      else
      {
        title = "You are about to cancel this reservation. The customer will get a refund.";
      }
      this.$swal({
        title: "Are you sure?",
        text: title,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, cancel it!",
      }).then((result) => {
        if (result.isConfirmed) {
          axios
            .post(`/reservations/cancel`, {
              id: reservation.id,
              reason: "Cancelled by admin",
            })
            .then((response) => {
              this.$notify({
                title: "Success",
                text: "Reservation cancelled successfully",
                type: "success",
              });
              if(response.status == 201)
                this.$swal("Info", response.data.message, "info");
              let reservationToCancel = null
              if(this.active_tab == 0)
              {
                reservationToCancel = this.activeReservations[index];
                this.activeReservations.splice(index, 1);
              }
              else if(this.active_tab == 1)
              {
                reservationToCancel = this.rideReservations[index];
                this.rideReservations.splice(index, 1);
              }
              else if(this.active_tab == 2)
              {
                reservationToCancel = this.missedReservations[index];
                this.missedReservations.splice(index, 1);
              }
              else if(this.active_tab == 3)
              {
                reservationToCancel = this.completedReservations[index];
                this.completedReservations.splice(index, 1);
              }
              this.cancelledReservations.push(reservationToCancel);
            })
            .catch((error) => {
              this.$notify({
                title: "Error",
                text: "Error while cancelling reservation",
                type: "error",
              });
              console.log(error);
              this.$swal("Error", error.response.data.message, "error");
            });
        }
      });
    },
  },
};
</script>
