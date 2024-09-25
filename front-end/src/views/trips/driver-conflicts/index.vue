<template>
  <div>
    <v-card>
      <v-card-title>
        <v-icon color="primary"> mdi-alert-circle-outline </v-icon>
        <span class="pl-2">Driver Schedule Conflicts</span>
      </v-card-title>
      <v-data-table
        item-key="id"
        :loading="isLoading"
        loading-text="Loading... Please wait"
        :headers="headers"
        :items="driverConflicts"
        :search="search"
      >
        <template v-slot:top>
          <v-text-field
            v-model="search"
            label="Search"
            class="mx-4"
          ></v-text-field>
        </template>

        <template v-slot:item.driver="{ item }">
          <a @click.stop="displayDriver(item.driver.id)">{{
            item.driver.name
          }}</a>
        </template>

        <template v-slot:item.intersect_date="{ item }">
          <span>{{ item.intersect_date | moment("LL") }}</span>
        </template>

        <template v-slot:item.current_trip="{ item }">
          <a @click.stop="displayTrip(item.current_trip.id)">{{
            item.current_trip.id
          }}
          <div> <strong>Route:</strong> {{ item.current_trip.route.name }} </div>
            <div> <strong>Start:</strong> {{ item.current_trip.first_stop_time | moment("LT") }} </div> 
            <div> <strong>End:</strong> {{ item.current_trip.last_stop_time | moment("LT") }} </div>
          </a>
        </template>

        <template v-slot:item.current_trip_calender="{ item }">
          <v-btn
            depressed
            small
            color="primary"
            @click="viewTripCalendar(item.current_trip)"
          >
            <v-icon dark> mdi-calendar </v-icon>
          </v-btn>
        </template>

        <template v-slot:item.other_trip="{ item }">
          <a @click.stop="displayTrip(item.other_trip.id)">{{
            item.other_trip.id
          }}
          <div> <strong>Route:</strong> {{ item.other_trip.route.name }} </div>
            <div> <strong>Start:</strong> {{ item.other_trip.first_stop_time | moment("LT") }} </div> <div> <strong>End:</strong> {{ item.other_trip.last_stop_time | moment("LT") }} </div>
          </a>
        </template>

        <template v-slot:item.other_trip_calender="{ item }">
          <v-btn
            depressed
            small
            color="error"
            @click="viewTripCalendar(item.other_trip)"
          >
            <v-icon dark> mdi-calendar </v-icon>
          </v-btn>
        </template>

      </v-data-table>
    </v-card>
  </div>
</template>

<script>
import {
  mdiStopCircleOutline,
  mdiPlayCircleOutline,
  mdiTrashCan,
  mdiDeleteRestore,
  mdiAirplane,
  mdiMotionPause
} from "@mdi/js";


export default {
  components: {
    
  },
  data() {
    return {
      driverConflicts: [],
      isLoading: false,
      search: "",
      icons: {
        mdiStopCircleOutline,
        mdiPlayCircleOutline,
        mdiTrashCan,
        mdiDeleteRestore,
        mdiAirplane
      },
      headers: [
        {
          text: "Driver",
          align: "start",
          value: "driver",
        },
        {
          text: "Current Trip",
          sortable: false,
          value: "current_trip",
        },
        {
          text: "Details",
          sortable: false,
          value: "current_trip_calender",
        },
        {
          text: "Conflicting Trip",
          sortable: false,
          value: "other_trip",
        },
        {
          text: "Details",
          sortable: false,
          value: "other_trip_calender",
        },
        {
          text: "Conflict Date",
          value: "intersect_date",
        },
      ],
    };
  },
  mounted() {
    this.loadDriverConflicts();
  },
  methods: {
    displayTrip(trip_id) {
      this.$router.push({
        name: "view-trip",
        params: { trip_id: trip_id },
      });
    },  
    displayDriver(driver_id) {
      this.$router.push({
        name: "view-user",
        params: { user_id: driver_id },
      });
    },  
    viewTripCalendar(trip) {
      this.$router.push({
        name: "view-calendar",
        params: { 
          trip_id: trip.id,
          suspension_id: 'none',
          },
      });
    },

    loadDriverConflicts() {
      this.isLoading = true;
      this.driverConflicts = [];
      axios
        .get(`/drivers/conflicts`)
        .then((response) => {
          this.driverConflicts = response.data.conflicts;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving driver conflicts",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
