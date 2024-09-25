<template>
  <div>
    <v-card>
      <v-card-title>
        <v-spacer></v-spacer>
        <v-btn depressed color="secondary" @click="$router.go(-1)" class="mx-1">
          Back
          <v-icon right dark> mdi-keyboard-return </v-icon>
        </v-btn>
      </v-card-title>
      <v-card-text v-if="trip">
            <step-1
              :trip="trip"
              :routes="routes"
              :mode="3"
            ></step-1>
            <step-2
            :apiKey="apiKey" :mode="3" :trip="trip" :timestep="timestep"></step-2>

      </v-card-text>
    </v-card>
  </div>
</template>

<script>
import step1 from "./steps/step1.vue";
import step2 from "./steps/step2.vue";
import {Keys} from '/src/config.js'
export default {
  components: {
    step1,
    step2,
    Keys
  },

  data() {
    return {
      apiKey: Keys.GOOGLE_MAPS_API_KEY,
      step: 1,
      timestep: 5,
      step_valid: [true, true, true],
      trip: null,
      routes: [],
      valid: true,
      nameRules: [(v) => !!v || ""],
      trip_id: null,
      submiting: false,
      mode: null, //0: create, 1 edit
    };
  },
  mounted() {
    this.fetchRoutes();
    if (this.$route.params.trip_id != null) {
      this.trip_id = this.$route.params.trip_id;
      this.mode = 1;
      this.fetchTrip();
    } else {
      this.mode = 0;
    }
  },
  methods: {
    next(s) {
      this.step = this.step + 1;
      this.$set(this.step_valid, s, true);
    },
    invalid(s) {
      this.$set(this.step_valid, s, false);
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
          console.log(this.trip)
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
