<template>
  <div>
    <v-data-table
      item-key="name"
      :loading="loading"
      loading-text="Loading... Please wait"
      :headers="mode == 1 || mode == 3 ? headers : suspendedHeaders"
      :items="trips"
      :search="search"
    >
      <template v-slot:top>
        <v-text-field
          v-model="search"
          label="Search"
          class="mx-4"
        ></v-text-field>
      </template>

      <template
        v-if="mode == 1 || mode == 3"
        v-slot:item.first_stop_time="{ item }"
      >
        <small>{{ tConvert(item.first_stop_time) }}</small>
      </template>

      <template v-if="mode == 2" v-slot:item.trip.first_stop_time="{ item }">
        <small>{{ tConvert(item.trip.first_stop_time) }}</small>
      </template>

      <template v-slot:item.repetition_period="{ item }">
        <span v-if="item.repetition_period == 0">Only once</span>
        <span v-else-if="item.repetition_period == 1">daily</span>
        <span v-else>every {{ item.repetition_period }} days</span>
      </template>

      <template
        v-if="mode == 1 || mode == 3"
        v-slot:item.last_stop_time="{ item }"
      >
        <small>{{ tConvert(item.last_stop_time) }}</small>
      </template>

      <template v-if="mode == 2" v-slot:item.trip.last_stop_time="{ item }">
        <small>{{ tConvert(item.trip.last_stop_time) }}</small>
      </template>

      <template v-if="mode == 1 || mode == 3" v-slot:item.route.name="{ item }">
        <a v-if="item.route.name" @click.stop="displayRoute(item.route.id)">{{
          item.route.name
        }}</a>
        <span v-else>No route</span>
      </template>

      <template v-if="mode == 2" v-slot:item.trip.route.name="{ item }">
        <a
          v-if="item.trip.route.name"
          @click.stop="displayRoute(item.trip.route.id)"
          >{{ item.trip.route.name }}</a
        >
        <span v-else>No route</span>
      </template>

      <template v-slot:item.driver="{ item }">
        <v-chip :color="getDriverAssignmentColor(item.driver)" dark @click="assignDriver(item)">
          {{ getTripDriver(item.driver) }}
        </v-chip>
      </template>

      <template v-slot:item.actions="{ item }">
        <v-menu offset-y
          right
          nudge-bottom="4">
            <template v-slot:activator="{ on, attrs }">
              <v-btn
                icon
                small
                v-bind="attrs"
                v-on="on"
              >
              <v-icon>mdi-dots-vertical</v-icon>
            </v-btn>
          </template>
          <v-list>
            <v-list-item
              v-for="(itm, index) in actionMenuItems()"
              :key="index"
              :value="index"
            >
            <div
            @click="doMenuAction(item, index)" class="d-flex align-center justify-start w-100">
              <v-list-item-icon>
                <v-icon>{{ itm.icon }}</v-icon>
              </v-list-item-icon>
              <v-list-item-title class="mx-2">{{ itm.title }}</v-list-item-title>
            </div>
            </v-list-item>
          </v-list>
        </v-menu>
      </template>
      <template v-slot:item.delay_report="{ item }">
        <v-chip v-if="item.delay_report" color='error' dark @click="viewDelayReport(item)">
          {{ item.delay_report.average_delay != 0 ? item.delay_report.average_delay + ' min' : 'No delay' }}
        </v-chip>
        <v-btn v-else :class="notEnoughHistoryForTimeLine(item) ? '' : 'error'"
         icon small @click="viewDelayReport(item)">
          <v-icon>mdi-clock-time-four-outline</v-icon>
        </v-btn>
      </template>
      <!-- <template v-slot:item.actions1="{ item }">
        <v-tooltip bottom>
          <template v-slot:activator="{ on, attrs }">
            <v-icon v-if="mode == 1" v-bind="attrs" v-on="on" small class="mr-2" @click="assignDriver(item)">
              mdi-account-tie-hat
            </v-icon>
          </template>
          <span>Assign driver</span>
        </v-tooltip>
        <v-tooltip bottom>
          <template v-slot:activator="{ on, attrs }">
            <v-icon v-bind="attrs" v-on="on" small class="mr-2" @click="viewTrip(item)">
              mdi-eye
            </v-icon>
          </template>
          <span>View</span>
        </v-tooltip>
        <v-tooltip bottom>
          <template v-slot:activator="{ on, attrs }">
            <v-icon v-bind="attrs" v-on="on" small class="mr-2" @click="viewTripCalendar(item)">
              mdi-calendar
            </v-icon>
          </template>
          <span>Calendar</span>
        </v-tooltip>
        <v-tooltip bottom>
          <template v-slot:activator="{ on, attrs }">
            <v-icon
              v-if="mode == 1"
              v-bind="attrs" v-on="on"
              small
              class="mr-2"
              @click="duplicateTrip(item)"
            >
              mdi-content-duplicate
            </v-icon>
          </template>
          <span>Duplicate</span>
        </v-tooltip>
        <v-tooltip bottom>
          <template v-slot:activator="{ on, attrs }">
            <v-icon
              v-bind="attrs" v-on="on"
              small
              v-if="mode == 1 || mode == 3"
              @click="trashRestoreTrip(item, trips.indexOf(item))"
            >
              {{ item.status_id != 1 ? icons.mdiBackupRestore : icons.mdiTrashCan }}
            </v-icon>

            <v-icon
              v-bind="attrs" v-on="on"
              small
              v-if="mode == 2"
              @click="deleteSuspension(item, trips.indexOf(item))"
            >
              {{ icons.mdiBackupRestore }}
            </v-icon>
          </template>
          <span>{{item.status_id != 1 ? 'Restore' : 'Trash'}}</span>
        </v-tooltip>
      </template> -->
    </v-data-table>
    <v-dialog v-if="selectedTrip" v-model="dialog" max-width="390">
      <v-card>
        <div class="text-h5 pa-4"> Select driver for trip of ID '{{ selectedTrip.id }}' on route '{{ selectedTrip.route.name }}' </div>

        <v-card-text>
          <v-list dense>
            <v-subheader>Drivers</v-subheader>
            <v-list-item-group>
              <v-list-item
                v-for="(driver, i) in availableDrivers"
                :key="i"
              >
                    <v-list-item-content @click="assignDriverToTrip(driver)">
                      <div><strong>{{ driver.name }}</strong></div>
                      <div v-if="driver.trip_intersect" class="error--text mt-1">
                          <p>Conflict with the trip {{ driver.trip_intersect.id }} on route ({{ driver.trip_intersect.route.name }})
                            at {{ driver.trip_intersect_date | moment("LL") }}</p>
                            <!-- . <strong>Before selecting this driver, make sure that the driver is available on the planned time of the first stop of this trip.</strong> -->
                      </div>
                    </v-list-item-content>
              </v-list-item>
            </v-list-item-group>
          </v-list>
        </v-card-text>
        <v-container style="height: 400px">
          <v-row
            v-show="loadingDrivers"
            class="fill-height"
            align-content="center"
            justify="center"
          >
            <v-col class="text-subtitle-1 text-center" cols="12">
              Loading drivers
            </v-col>
            <v-col cols="6">
              <v-progress-linear
                :active="loadingDrivers"
                color="primary"
                indeterminate
                rounded
                height="6"
              ></v-progress-linear>
            </v-col>
          </v-row>
        </v-container>
        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn
            color="green darken-1"
            text
            @click="
              dialog = false;
              loadingDrivers = false;
              availableDrivers = [];
            "
          >
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- dialog with trip time line -->
    <v-dialog v-if="selectedTripForTimeLine"
    v-model="timeLineDialog" max-width="400">
      <v-card>
        <v-card-title>
          <v-icon color="primary">
            mdi-clock-time-four-outline
          </v-icon>
          <span class="pl-2">Trip timeline</span>
        </v-card-title>
        <v-card-text v-if="notEnoughHistoryForTimeLine(selectedTripForTimeLine)">
          <div class="text-h6 pa-4"> No enough history for trip of ID '{{ selectedTripForTimeLine.id }}' on route '{{ selectedTripForTimeLine.route.name }}' to display delay report </div>
        </v-card-text>
        <v-card-text>
          <v-timeline align-top>
            <v-timeline-item
              v-for="(detail, index) in selectedTripForTimeLine.stopTimes"
              :key="detail.id"
            >
              <div class="pt-1">
                  <div><strong>{{ detail.stop_name }}</strong></div>
                  <div class="text-caption">
                    {{ detail.planned_time }}
                  </div>
                  <div v-if="detail.count_pre != 0" class="error--text">
                    Early: {{ detail.avg_diff_pre }} min
                  </div>
                  <div v-if="detail.count_post != 0" class="error--text">
                    Late: {{ detail.avg_diff_post }} min
                  </div>
              </div>
            </v-timeline-item>
          </v-timeline>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn color="green darken-1" text @click="timeLineDialog = false">
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import {
  mdiStopCircleOutline,
  mdiPlayCircleOutline,
  mdiTrashCan,
  mdiDeleteRestore,
  mdiAirplane,
  mdiBackupRestore,
} from "@mdi/js";
import moment from 'moment';

export default {
  props: {
    trips: Array,
    mode: Number,
    loading: {
      type: Boolean,
      default: false,
    },
  },
  components: {},
  data() {
    return {
      minMinutesForDelay: 5,
      isLoading: false,
      search: "",
      loadingDrivers: false,
      availableDrivers: [],
      dialog: false,
      selectedTrip: null,
      selectedTripForTimeLine: null,
      timeLineDialog: false,
      actionItemsActive: [
        {title: "Edit", icon: 'mdi-pencil' }, // 'mdi-pencil'
        {title: "View", icon: 'mdi-eye' }, // 'mdi-eye'
        {title: "Assign driver", icon: 'mdi-account-tie-hat'}, // 'mdi-account-tie-hat'
        {title: "Calendar", icon: 'mdi-calendar' }, // 'mdi-calendar'
        {title: "Duplicate", icon: 'mdi-content-duplicate' }, // 'mdi-content-duplicate'
        {title: "Trash", icon: 'mdi-trash-can' }, // 'mdi-trash-can'
      ],
      actionItemsSuspended: [
        {title: "View", icon: 'mdi-eye' }, // 'mdi-eye'
        {title: "Calendar", icon: 'mdi-calendar' }, // 'mdi-calendar'
        {title: "Restore", icon: 'mdi-backup-restore' }, // 'mdi-backup-restore'
      ],
      actionItemsTrashed: [
        {title: "View", icon: 'mdi-eye' }, // 'mdi-eye'
        {title: "Calendar", icon: 'mdi-calendar' }, // 'mdi-calendar'
        {title: "Restore", icon: 'mdi-backup-restore' }, // 'mdi-backup-restore'
      ],
      headers: [
        { text: "ID", value: "id", align: "start"},
        { text: "Route", value: "route.name" },
        { text: "First stop", value: "first_stop_time" },
        { text: "Last stop", value: "last_stop_time" },
        { text: "Repeated", value: "repetition_period" },
        { text: "Effective", value: "effective_date" },
        { text: "Driver", value: "driver" },
        { text: "Delay report", value: "delay_report" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      suspendedHeaders: [
        { text: "ID", value: "trip.id", align: "start"},
        { text: "Route", value: "trip.route.name" },
        { text: "First stop", value: "trip.first_stop_time" },
        { text: "Last stop", value: "trip.last_stop_time" },
        { text: "Suspension date", value: "date" },
        { text: "Suspension repetition", value: "repetition_period" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      icons: {
        mdiStopCircleOutline,
        mdiPlayCircleOutline,
        mdiTrashCan,
        mdiDeleteRestore,
        mdiAirplane,
        mdiBackupRestore,
      },
    };
  },
  methods: {
    notEnoughHistoryForTimeLine(trip)
    {
      if(trip && trip.stopTimes)
      {
        var keys = Object.keys(trip.stopTimes);
        for (var i = 0; i < keys.length; i++) {
          var key = keys[i];
          if(trip.stopTimes[key].count == 0)
          {
            return true;
          }
        }
        return !this.isTripTimeNeedsAttention(trip);
      }
      return true;
    },
    isTripTimeNeedsAttention(trip)
    {
      if(trip && trip.stopTimes)
      {
        var keys = Object.keys(trip.stopTimes);
        for (var i = 0; i < keys.length; i++) {
          var key = keys[i];
          if(trip.stopTimes[key].count_pre != 0 && (trip.stopTimes[key].avg_diff_pre > this.minMinutesForDelay || trip.stopTimes[key].avg_diff_pre < -this.minMinutesForDelay))
          {
            return true;
          }
          if(trip.stopTimes[key].count_post != 0 && (trip.stopTimes[key].avg_diff_post > this.minMinutesForDelay || trip.stopTimes[key].avg_diff_post < -this.minMinutesForDelay))
          {
            return true;
          }
        }
        return false;
      }
      return false;
    },
    viewDelayReport(item)
    {
      this.selectedTripForTimeLine = item;
      this.timeLineDialog = true;
    },
    actionMenuItems()
    {
      if(this.mode == 1)
      {
        return this.actionItemsActive;
      }
      else if(this.mode == 2)
      {
        return this.actionItemsSuspended;
      }
      else if(this.mode == 3)
      {
        return this.actionItemsTrashed;
      }
    },
    doMenuAction(item, index) {
      if(this.mode == 1)
      {
        if(index == 0)
        {
          this.editTrip(item);
        }
        else if(index == 1)
        {
          this.viewTrip(item);
        }
        else if(index == 2)
        {
          this.assignDriver(item);
        }
        else if(index == 3)
        {
          this.viewTripCalendar(item);
        }
        else if(index == 4)
        {
          this.duplicateTrip(item);
        }
        else if(index == 5)
        {
          this.trashRestoreTrip(item, this.trips.indexOf(item));
        }
      }
      else if(this.mode == 2 || this.mode == 3)
      {
        if(index == 0)
        {
          this.viewTrip(item);
        }
        else if(index == 1)
        {
          this.viewTripCalendar(item);
        }
        else if(index == 2)
        {
          this.trashRestoreTrip(item, this.trips.indexOf(item));
        }
      }
    },
    getDriverAssignmentColor(driver) {
      if (driver) return "success";
      else return "error";
    },
    getTripDriver(driver) {
      if (driver) return driver.name;
      else return "none";
    },
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
    displayRoute(route_id) {
      this.$router.push({
        name: "view-route",
        params: { route_id: route_id },
      });
    },

    trashRestoreTrip(trip, index) {
      this.$emit("trashRestoreTrip", trip, index);
    },

    deleteSuspension(trip, index) {
      this.$emit("deleteSuspension", trip.id, index);
    },

    viewTripCalendar(item) {
      var trip_id = null;
      var suspension_id = "none";
      if (this.mode == 1 || this.mode == 3) {
        trip_id = item.id;
      } else {
        trip_id = item.trip.id;
        suspension_id = item.id;
      }
      this.$router.push({
        name: "view-calendar",
        params: { trip_id: trip_id, suspension_id: suspension_id },
      });
    },
    viewTrip(item) {
      var trip_id = null;
      if (this.mode == 1 || this.mode == 3) {
        trip_id = item.id;
      } else {
        trip_id = item.trip.id;
      }
      this.$router.push({
        name: "view-trip",
        params: { trip_id: trip_id },
      });
    },
    editTrip(trip) {
      this.$router.push({
        name: "edit-trip",
        params: { trip_id: trip.id, action: "edit" },
      });
    },
    assignDriver(item) {
      var trip_id = null;
      if (this.mode == 1 || this.mode == 3) {
        trip_id = item.id;
        this.selectedTrip = item;
      } else {
        trip_id = item.trip.id;
        this.selectedTrip = item.trip;
      }
      this.dialog = true;
      this.loadAvailableDrivers()
    },
    loadAvailableDrivers() {
      this.loadingDrivers = true;
      let params = {
        trip_id: this.selectedTrip.id,
      };
      axios
        .get(`/drivers/available`, { params: params })
        .then((response) => {
          this.availableDrivers = response.data.availableDrivers;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving trips",
            type: "error",
          });
          console.log(error);
          this.loadingDrivers = false;
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          this.loadingDrivers = false;
        });
    },
    duplicateTrip(trip) {
      this.$router.push({
        name: "edit-trip",
        params: {
          trip_id: trip.id,
          action: "duplicate",
        },
      });
    },
    assignDriverToTripServer(driver)
    {
      this.isLoading = true;
      let idx = this.trips.findIndex((t) => t.id == this.selectedTrip.id);
      axios
        .post(`/trips/assign-driver`, {
          trip_id: this.selectedTrip.id,
          driver_id: driver.id,
        })
        .then((response) => {
          this.$notify({
            title: "Success",
            text: "Driver assigned to trip",
            type: "success",
          });
          this.trips[idx].driver = response.data.driver;
          this.onAssignedFinished();
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while assigning driver to trip",
            type: "error",
          });
          console.log(error);
          this.onAssignedFinished();
          this.$swal("Error", error.response.data.error, "error");
        })
        .then(() => {
          this.isLoading = false;
        });
    },
    assignDriverToTrip(driver) {
      if(driver.trip_intersect)
      {
        this.$swal({
          title: "Warning",
          html: "Driver schedule overlaps with the trip <strong> " + driver.trip_intersect.id + " on route (" + driver.trip_intersect.route.name + ") on " +
          moment(driver.trip_intersect_date).format('LL') + ".</strong> Before selecting this driver, make sure that the driver is available on the planned time of the first stop of this trip. Are you sure you want to assign this driver?",
          icon: "warning",
          buttons: true,
          dangerMode: true,
          showCancelButton: true,
          confirmButtonText: "Yes, assign!",
        }).then((result) => {
          if (result.isConfirmed) {
            this.assignDriverToTripServer(driver);
          }
        });
      }
      else
      {
        this.assignDriverToTripServer(driver);
      }
    },
    onAssignedFinished()
    {
      this.dialog = false;
    }
  },
};
</script>
<style lang="scss">
.theme--light.v-list-item:not(.v-list-item--active):not(.v-list-item--disabled):hover{
  cursor: pointer;
  background: rgba($primary-shade--light, 0.15) !important;
}
</style>
