<template>
  <div>
    <vue-element-loading :active="submiting" />
    <v-card>
      <v-card-title>
        <span v-if="trip">Route: {{ trip.route.name }}</span>
        <v-spacer></v-spacer>
        <v-btn
          v-if="
            suspension_id != null && suspension_id != 'none' && trip != null
          "
          depressed
          color="success"
          @click="deleteSuspension(suspension_id)"
          class="mx-1"
        >
          Continue
          <v-icon right dark>
            mdi-checkbox-multiple-marked-circle-outline
          </v-icon>
        </v-btn>
        <v-btn depressed color="secondary" @click="$router.go(-1)" class="mx-1">
          Back
          <v-icon right dark> mdi-keyboard-return </v-icon>
        </v-btn>
      </v-card-title>
      <v-card-text>
        <calendar
          v-if="trip"
          :trip="trip"
          :focusDate="startDate"
          @suspend="suspend"
          :suspension_id="suspension_id"
        ></calendar>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
import VueElementLoading from "vue-element-loading";
import calendar from "./calendar.vue";

import EventBus from "../eventBus";

export default {
  components: {
    calendar,
    VueElementLoading,
  },

  data() {
    return {
      trip_id: null,
      suspension_id: null,
      trip: null,
      submiting: false,
      suspend_trip_id: null,
      suspend_event: null,
      startDate: "",
      period: null,
    };
  },
  mounted() {
    if (this.$route.params.suspension_id != null) {
      this.suspension_id = this.$route.params.suspension_id;
    }
    if (this.$route.params.trip_id != null) {
      this.trip_id = this.$route.params.trip_id;
      this.fetchTrip();
    }
  },
  methods: {
    suspend(suspend_event, suspend_trip_id, period, done) {
      this.suspend_trip_id = suspend_trip_id;
      this.suspend_event = suspend_event;
      this.period = period;
      this.suspendTrip(done)
    },
    deleteSuspension(suspension, index) {
      EventBus.$emit("DELETE_SUSPENSION", suspension, index);
    },
    //API Calls
    suspendTrip(done) {
      this.submiting = true;
      axios
        .post("/trips/suspend", {
          date: this.suspend_event.start,
          trip_id: this.suspend_trip_id,
          repetition_period: this.period,
        })
        .then((response) => {
          this.submiting = false;
          this.$notify({
            title: "Success",
            text: "Trip suspended!",
            type: "success",
          });
          let suspension_id = response.data.suspension_id;
          this.startDate = this.suspend_event.start;
          this.fetchTrip();
          done(this.suspend_event, suspension_id);
          //this.$router.replace({ name: "trips" });
        })
        .catch((error) => {
          this.submiting = false;
          this.$notify({
            title: "Error",
            text: "Error suspending trip",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        });
    },
    fetchTrip() {
      this.submiting = true;
      axios
        .get(`/trips/trip/${this.trip_id}`)
        .then((response) => {
          this.submiting = false;
          this.trip = response.data;
        })
        .catch((error) => {
          this.submiting = false;
          this.$notify({
            title: "Error",
            text: "Error fetching trip data",
            type: "error",
          });
          console.log(error);
          //this.$router.go(-1);
        });
    },
  },
};
</script>