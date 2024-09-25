<template>
  <div>
    <vue-element-loading :active="submiting" />
    <v-card>
      <v-card-title>
        Create new trip
        <v-spacer></v-spacer>
        <v-btn depressed color="secondary" @click="$router.go(-1)" class="mx-1">
          Cancel
          <v-icon right dark> mdi-keyboard-return </v-icon>
        </v-btn>
      </v-card-title>
      <v-card-text>
        <v-stepper v-model="step" vertical elevation="0">
          <v-stepper-step
            :editable="step > 1"
            :complete="step > 1"
            @click="setStep(1)"
            :step="1"
            :rules="[() => step_valid[0]]"
          >
            Trip information
            <small class="my-1">Set route, date, and bus driver</small>
          </v-stepper-step>

          <v-stepper-content step="1">
            <step-1
              @next="next"
              @invalid="invalid"
              :trip="trip"
              :routes="routes"
              :mode="mode"
            ></step-1>
          </v-stepper-content>

          <v-stepper-step :complete="step > 2" step="2">
            Configure time table
            <small class="my-1"
              >Set when the bus will arrive at each stop</small
            >
          </v-stepper-step>

          <v-stepper-content step="2">
            <step-2 v-if="step==2" 
            @finish="finish" 
            @back="back" 
            :trip="trip" 
            :api-key="apiKey"
            :timestep="timestep"></step-2>
          </v-stepper-content>
        </v-stepper>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
import VueElementLoading from "vue-element-loading";
import step1 from "./steps/step1.vue";
import step2 from "./steps/step2.vue";
import step3 from "./steps/step3.vue";
import {Keys} from '/src/config.js'

export default {
  components: {
    step1,
    step2,
    step3,
    VueElementLoading,
    Keys
  },

  data() {
    return {
      apiKey: Keys.GOOGLE_MAPS_API_KEY,
      step: 1,
      timestep: 5,
      step_valid: [true, true, true],
      trip: {
        time:[]
      },
      routes: [],
      valid: true,
      nameRules: [(v) => !!v || ""],
      trip_id: null,
      submiting: false,
      mode: null, //0: create, 1 edit
      action: null, //edit or duplicate
    };
  },
  mounted() {
    this.fetchRoutes();
    if (this.$route.params.trip_id != null) {
      this.trip_id = this.$route.params.trip_id;
      if(this.$route.params.action != null)
      {
        if(this.$route.params.action == "edit")
        {
          this.action = "edit";
          this.mode = 1;
        }
        else if(this.$route.params.action == "duplicate")
        {
          this.action = "duplicate";
          this.mode = 0;
        }
        else{
          this.$router.go(-1);
        }
      }
      this.fetchTrip();
    } else {
      this.mode = 0;
      this.action = "create";
    }
  },
  methods: {
    next(trip) {
      this.step = this.step + 1;
      this.trip = trip;
      console.log(this.trip);
      this.$set(this.step_valid, 0, true);
    },
    invalid() {
      this.$set(this.step_valid, 0, false);
      //this.step_valid[s] = false
      console.log(this.step_valid);
    },
    setStep(s) {
      this.step = s;
    },
    back() {
      this.step = this.step - 1;
    },
    finish() {
      this.saveTrip()
    },
    //API Calls
    saveTrip() {
      if(this.step != 2)
      return;
      this.submiting = true;
      this.trip.route_id = this.trip.route.id;
      axios
        .post("/trips/create-edit", {
          trip: this.trip,
          action: this.action
        })
        .then((response) => {
          this.submiting = false;
          this.$notify({
            title: "Success",
            text: this.mode ==1? "Trip updated!" : "Trip created!",
            type: "success",
          });
          this.$router.replace({ name: "trips" });
        })
        .catch((error) => {
          this.submiting = false;
          this.$notify({
            title: "Error",
            text: "Error creating trip",
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

    fetchRoutes() {
      this.submiting = true;
      axios
        .get("/routes/all")
        .then((response) => {
          this.submiting = false;
          this.routes = response.data;
        })
        .catch((error) => {
          this.submiting = false;
          this.$notify({
            title: "Error",
            text: "Error fetching routes data",
            type: "error",
          });
          console.log(error);
          //this.$router.go(-1);
        });
    },
  },
};
</script>