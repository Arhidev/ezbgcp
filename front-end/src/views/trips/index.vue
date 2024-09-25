<template>
  <div>
    <v-card>
      <v-card-title>
        <v-icon color="primary"> mdi-bus-clock </v-icon>
        <span class="pl-2">Trips</span>
        <v-spacer></v-spacer>
        <create-button @create="createTrip"></create-button>
        <activation-tool-tip model="trips"></activation-tool-tip>
      </v-card-title>
      <!-- tabs -->
      <v-tabs v-model="active_tab" show-arrows class="my-2">
        <v-tab v-for="tab in tabs" :key="tab.idx">
          <v-icon size="20" class="me-3">
            {{ tab.icon }}
          </v-icon>
          <span>{{ tab.title }}</span>
        </v-tab>
      </v-tabs>
    <!-- tabs item -->
    <v-tabs-items v-model="active_tab">
      <!-- active -->
      <v-tab-item>
        <trips-table
        :loading="isLoading"
        :trips="activeTrips" :mode=1 @trashRestoreTrip="trashRestoreTrip"></trips-table>
      </v-tab-item>

      <!-- suspended -->
      <v-tab-item>
        <trips-table
        :loading="isLoading"
        :trips="suspendedTrips" :mode=2 @deleteSuspension="deleteSuspension"></trips-table>
      </v-tab-item>

      <!-- trashed -->
      <v-tab-item>
        <trips-table
        :loading="isLoading"
        :trips="trashedTrips" :mode=3 @trashRestoreTrip="trashRestoreTrip"></trips-table>
      </v-tab-item>

    </v-tabs-items>
    </v-card>
  </div>
</template>

<script>
import tripsTable from './trips-table.vue';

import EventBus from './eventBus';

import {
  mdiStopCircleOutline,
  mdiPlayCircleOutline,
  mdiTrashCan,
  mdiDeleteRestore,
  mdiAirplane,
  mdiMotionPause
} from "@mdi/js";

import ActivationToolTip from "@/components/ActivationToolTip";
import CreateButton from "@/components/CreateButton";

export default {
  components: {
    tripsTable,
    ActivationToolTip,
    CreateButton,
  },
  data() {
    return {
      activeTrips: [],
      trashedTrips: [],
      suspendedTrips: [],
      isLoading: false,
      search: "",
      tabs: [
        { idx: 0, title: "Active", icon: mdiAirplane },
        { idx: 1, title: "Suspended", icon: mdiMotionPause },
        { idx: 2, title: "Trashed", icon: mdiTrashCan },
      ],
      active_tab: null,
      statuses: [
        { value: "Active", color: "success" },
        { value: "Pending", color: "warning" },
        { value: "Suspended", color: "error" },
      ],
      icons: {
        mdiStopCircleOutline,
        mdiPlayCircleOutline,
        mdiTrashCan,
        mdiDeleteRestore,
        mdiAirplane
      },
    };
  },
  watch: {
    active_tab: function (newVal, oldVal) {
      localStorage.tabIdxTrips = newVal;
    },
  },
  mounted() {
    this.active_tab = parseInt(localStorage.tabIdxTrips);
    this.loadTrips();
  },
  created () {
    var self = this;

    EventBus.$on('DELETE_SUSPENSION', function (suspension, index) {
      self.deleteSuspension(suspension, index)
    });
  },
  methods: {
    tConvert(time) {
      if (time == null) {
        return null;
      }
      // Check correct time format and split into components
      time = time.toString().match(/^([01]\d|2[0-3])(:)([0-5]\d)/) || [time];

      if (time.length > 1) {
        // If time format correct
        time = time.slice(1); // Remove full string match value
        time[5] = +time[0] < 12 ? " AM" : " PM"; // Set AM/PM
        time[0] = +time[0] % 12 || 12; // Adjust hours
      }
      return time.join(""); // return adjusted time or original string
    },
    getStatusColor(status) {
      return this.statuses[status - 1].color;
    },
    getStatusValue(status) {
      return this.statuses[status - 1].value;
    },
    displayRoute(route_id) {
      this.$router.push({
        name: "view-route",
        params: { route_id: route_id },
      });
    },

    deleteSuspension(suspension_id, index) {
      this.$swal
        .fire({
          title: "Remove suspension",
          text:
            "Are you sure to remove this suspension?",
          icon: "success",
          showCancelButton: true,
          confirmButtonText: "Yes",
        })
        .then((result) => {
          if (result.isConfirmed) {
            this.deleteSuspensionServer(suspension_id, index);
          }
        });
    },

    deleteSuspensionServer(suspension_id, index) {
      this.isSubmit = true;
      axios
        .delete(`/trips/remove-suspension/${suspension_id}`)
        .then((response) => {
          this.isSubmit = false;
          if(index != null)
            this.suspendedTrips.splice(index, 1);
          this.$notify({
            title: "Success",
            text: "Suspension removed",
            type: "success",
          });
          if(index == null)
          {
            this.$router.go(-1);
          }
        })
        .catch((error) => {
          this.isSubmit = false;
          this.$notify({
            title: "Error",
            text: "Error",
            type: "error",
          });
          //this.$swal("Error", error.response.data.message, "error");
        });
    },

    trashRestoreTrip(trip, index) {
      this.$swal
        .fire({
          title: (trip.status_id != 1 ? "Restore" : "Trash") + " trip",
          text:
            "Are you sure to " +
            (trip.status_id != 1 ? "restore" : "trash") +
            " this trip?",
          icon: trip.status_id != 1 ? "success" : "error",
          showCancelButton: true,
          confirmButtonText: "Yes",
        })
        .then((result) => {
          if (result.isConfirmed) {
            this.trashRestoreTripServer(trip, index);
          }
        });
    },
    trashRestoreTripServer(trip, index) {
      this.isSubmit = true;
      axios
        .post("/trips/trash-restore", {
          trip_id: trip.id,
        })
        .then((response) => {
          this.isSubmit = false;
          if(trip.status_id == 1)
          {
            trip.status_id == 3;
            this.activeTrips[index].status_id = 3;
            this.activeTrips.splice(index, 1);
            this.trashedTrips.push(trip);
          }
          else
          {
            trip.status_id == 1;
            this.trashedTrips[index].status_id = 1;
            this.activeTrips.push(trip);
            this.trashedTrips.splice(index, 1);
          }
          this.$notify({
            title: "Success",
            text: "Trip " + (trip.status_id != 1 ? "trashed" : "restored"),
            type: "success",
          });
        })
        .catch((error) => {
          this.isSubmit = false;
          this.$notify({
            title: "Error",
            text: "Error",
            type: "error",
          });
          //this.$swal("Error", error.response.data.message, "error");
        });
    },

    loadTrips() {
      this.isLoading = true;
      this.activeTrips = [];
      this.trashedTrips = [];
      this.suspendedTrips = [];
      axios
        .get(`/trips/all`)
        .then((response) => {
          this.activeTrips = response.data.activeTrips;
          this.trashedTrips = response.data.trashedTrips;
          this.suspendedTrips = response.data.suspendedTrips;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving trips",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          this.isLoading = false;
        });
    },
    createTrip() {
      this.$router.push({
        name: "create-trip",
      });
    },
  },
};
</script>
