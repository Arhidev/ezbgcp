<template>
  <v-row class="fill-height">
    <vue-element-loading :active="submiting" />
    <v-col>
      <v-sheet height="64">
        <v-toolbar flat>
          <v-btn outlined class="mr-4" color="grey darken-2" @click="setToday">
            Today
          </v-btn>
          <v-btn fab text small color="grey darken-2" @click="prev">
            <v-icon small> mdi-chevron-left </v-icon>
          </v-btn>
          <v-btn fab text small color="grey darken-2" @click="next">
            <v-icon small> mdi-chevron-right </v-icon>
          </v-btn>
          <v-toolbar-title v-if="$refs.calendar">
            {{ $refs.calendar.title }}
          </v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn
            outlined
            class="mr-4"
            color="grey darken-2"
            @click="setEffective"
          >
            Effective date
          </v-btn>
        </v-toolbar>
      </v-sheet>
      <v-sheet height="600">
        <v-calendar
          ref="calendar"
          v-model="focus"
          color="primary"
          :events="events"
          :event-color="getEventColor"
          :type="type"
          @click:event="showEvent"
          @change="updateRange"
        ></v-calendar>
        <v-menu
          v-model="selectedOpen"
          :close-on-content-click="false"
          :activator="selectedElement"
          offset-x
        >
          <v-card min-width="350px" flat>
            <v-toolbar dark :color="selectedEvent.color">
              <v-toolbar-title
                class="white--text"
                v-html="selectedEvent.details"
              ></v-toolbar-title>
              <v-spacer></v-spacer>
              <div v-if="trip.status_id == 1 
              && selectedEvent.suspension_id == null">
                <v-menu bottom offset-y>
                  <template v-slot:activator="{ on, attrs }">
                    <v-btn
                      v-bind="attrs"
                      v-on="on"
                      outlined
                      v-if="
                        selectedEvent.status != 0 &&
                        suspension_id != null &&
                        suspension_id == 'none'
                      "
                      class="mr-1 error"
                    >
                      Suspend
                      <v-icon right dark> mdi-motion-pause </v-icon>
                    </v-btn>
                  </template>
                  <v-list>
                    <v-list-item
                      v-for="(suspensionPeriod, i) in suspensionPeriods"
                      :key="i"
                      @click="suspend(selectedEvent, suspensionPeriod.value)"
                    >
                      <v-list-item-title>{{ suspensionPeriod.title }}</v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
              </div>
              <v-btn icon @click="selectedOpen = false">
                <v-icon>mdi-close</v-icon>
              </v-btn>
            </v-toolbar>
            <v-card-text>
              <div v-if="selectedEvent.status == 0" class="my-2">
                <strong class="error--text">Suspended</strong>
              </div>
              <div v-if="selectedEvent.driver" class="my-2">
                Driver: <strong>{{ selectedEvent.driver.name }}</strong>
              </div>
              <v-timeline align-top>
                <v-timeline-item
                  v-for="(detail, index) in trip.trip_details"
                  :key="detail.id"
                >
                  <v-row class="pt-1">
                    <v-col>
                      <strong>{{ detail.stop.name }}</strong>
                      <div class="text-caption">
                        {{ detail.planned_timestamp }}
                      </div>
                    </v-col>
                  </v-row>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-card>
        </v-menu>
      </v-sheet>
    </v-col>
  </v-row>
</template>

<script>
import VueElementLoading from "vue-element-loading";

export default {
  components: {
    VueElementLoading,
  },

  props: {
    trip: Object,
    suspension_id: String,
    focusDate: String,
  },
  data: () => ({
    submiting: false,
    focus: "",
    type: "month",
    start: null,
    end: null,
    initLoad: true,
    selectedEvent: {},
    selectedElement: null,
    selectedOpen: false,
    events: [],
    suspensionPeriods: [
      {
        title: "This trip only",
        value: 0,
      },
      {
        title: "Suspend daily",
        value: 1,
      },
      {
        title: "Suspend every 2 days",
        value: 2,
      },
      {
        title: "Suspend every 3 days",
        value: 3,
      },
      {
        title: "Suspend every 4 days",
        value: 4,
      },
      {
        title: "Suspend every 5 days",
        value: 5,
      },
      {
        title: "Suspend every 6 days",
        value: 6,
      },
      {
        title: "Suspend every 7 days",
        value: 7,
      },
    ],
  }),
  watch: {
    focusDate: function (newVal, oldVal) {
      this.focus = newVal;
      this.fetchTrips(this.start.date, this.end.date);
    },
  },
  mounted() {
    this.$refs.calendar.checkChange();
  },
  methods: {
    getEventColor(event) {
      return event.color;
    },
    setToday() {
      this.focus = "";
    },
    setEffective() {
      this.focus = this.trip.effective_date;
    },
    prev() {
      this.$refs.calendar.prev();
    },
    next() {
      this.$refs.calendar.next();
    },
    suspend(event, period) {
      this.$emit("suspend", event, this.trip.id, period, this.onSuccessSuspension);
      this.selectedOpen = false
    },
    onSuccessSuspension(event, suspension_id) {
      console.log("onSuccessSuspension");
      console.log(event);
      let event_idx = this.events.indexOf(event);
      this.events[event_idx].status = 0;
      this.events[event_idx].color = "error";
      this.events[event_idx].suspension_id = suspension_id;
    },
    showEvent({ nativeEvent, event }) {
      console.log("showEvent");
      console.log(event);
      const open = () => {
        this.selectedEvent = event;
        this.selectedEvent.details = this.trip.route.name;
        this.selectedElement = nativeEvent.target;
        requestAnimationFrame(() =>
          requestAnimationFrame(() => (this.selectedOpen = true))
        );
      };

      if (this.selectedOpen) {
        this.selectedOpen = false;
        requestAnimationFrame(() => requestAnimationFrame(() => open()));
      } else {
        open();
      }

      nativeEvent.stopPropagation();
    },
    updateRange({ start, end }) {
      this.start = start;
      this.end = end;
      this.fetchTrips(start.date, end.date);
    },
    fetchTrips(start, end) {
      this.submiting = true;
      var url = "/trips/period";
      var params = {
        start: start,
        end: end,
        trip_id: this.trip.id,
      };
      if (
        this.suspension_id != null &&
        this.suspension_id != "none" &&
        this.trip != null
      ) {
        url = "/trips/suspensions";
        params = {
          start: start,
          end: end,
          suspension_id: parseInt(this.suspension_id),
          trip_id: this.trip.id,
        };
      }
      axios
        .get(url, { params: params })
        .then((response) => {
          this.submiting = false;
          this.events = response.data.events;
          if (response.data.startCal != null)
          {
            if(this.initLoad)
            {
              this.initLoad = false;
              this.focus = response.data.startCal;
            }
          }
        })
        .catch((error) => {
          this.submiting = false;
          this.$notify({
            title: "Error",
            text: "Error fetching stops of this route",
            type: "error",
          });
          //this.$swal("Error", error.response.data.message, "error");
        });
    },
  },
};
</script>