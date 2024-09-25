<template>
  <div>
    <v-card color="grey lighten-1" class="mb-12" elevation="0">
      <v-form ref="form" v-model="valid" lazy-validation>
        <v-row>
          <v-col cols="12" md="4" class="my-2">
            <v-autocomplete
              v-model="trip.route"
              :items="routes"
              dense
              outlined
              :readonly="mode==3"
              item-text="name"
              item-value="id"
              label="Route"
              persistent-hint
              return-object
              required
              :rules="requiredRules"
              :hint="
                selectedRoute != null
                  ? selectedRoute.stops_count + ' stops'
                  : ''
              "
              @change="routeSelected"
            ></v-autocomplete>
          </v-col>

          <v-col cols="12" md="4" class="my-2">
            <v-dialog
              ref="effective_date"
              :return-value.sync="trip.effective_date"
              v-model="modal"
              :disabled="mode==3"
              persistent
              width="290px"
            >
              <template v-slot:activator="{ on, attrs }">
                <v-text-field
                  v-model="trip.effective_date"
                  label="Effective date"
                  dense
                  outlined
                  readonly
                  required
                  :rules="requiredRules"
                  v-bind="attrs"
                  v-on="on"
                ></v-text-field>
              </template>
              <v-date-picker v-model="trip.effective_date" scrollable>
                <v-spacer></v-spacer>
                <v-btn text color="primary" @click="modal = false">
                  Cancel
                </v-btn>
                <v-btn
                  text
                  color="primary"
                  @click="$refs.effective_date.save(trip.effective_date)"
                >
                  OK
                </v-btn>
              </v-date-picker>
            </v-dialog>
          </v-col>
          <v-col cols="12" md="4" class="my-2">
            <v-text-field
              v-model="trip.repetition_period"
              type="number"
              outlined
              dense
              :readonly="mode==3"
              label="Repeated every (days)"
              placeholder="Enter repetition period (days)"
              hint="0 means no repetition"
              persistent-hint
              required
              :rules="repeatRules"
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="4" class="my-2">
            <v-dialog
              ref="first_stop_time"
              :return-value.sync="trip.first_stop_time"
              v-model="modal2"
              :disabled="mode==3"
              persistent
              width="290px"
            >
              <template v-slot:activator="{ on, attrs }">
                <v-text-field
                  v-model="trip.first_stop_time"
                  outlined
                  dense
                  label="Arrival time at first stop"
                  readonly
                  required
                  :rules="requiredRules"
                  v-bind="attrs"
                  v-on="on"
                ></v-text-field>
              </template>
              <v-time-picker
                v-if="modal2"
                v-model="trip.first_stop_time"
                full-width
              >
                <v-spacer></v-spacer>
                <v-btn text color="primary" @click="modal2 = false">
                  Cancel
                </v-btn>
                <v-btn
                  text
                  color="primary"
                  @click="$refs.first_stop_time.save(trip.first_stop_time)"
                >
                  OK
                </v-btn>
              </v-time-picker>
            </v-dialog>
          </v-col>
          <v-col cols="12" md="4" class="my-2">
            <v-tooltip v-if="mode==1" bottom>
              <template v-slot:activator="{ on, attrs }">
                <v-text-field
                  v-model="trip.stop_to_stop_avg_time"
                  type="number"
                  outlined
                  dense
                  label="Stop-to-stop time (minutes)"
                  placeholder="Enter average stop-to-stop time (min)"
                  hint="Can be changed later"
                  persistent-hint
                  required
                  :rules="timeRules"
                  v-bind="attrs"
                  v-on="on"
                ></v-text-field>
              </template>
              <span>This has no effect if you duplicate a previous trip with the same route</span>
            </v-tooltip>
            <v-text-field v-else
              v-model="trip.stop_to_stop_avg_time"
              type="number"
              outlined
              dense
              :readonly="mode==3"
              label="Stop-to-stop time (minutes)"
              placeholder="Enter average stop-to-stop time (min)"
              hint="Can be changed later"
              persistent-hint
              required
              :rules="timeRules"
            ></v-text-field>
          </v-col>
        </v-row>
      </v-form>
    </v-card>
    <v-btn v-if="mode!=3" color="primary" @click="next"> Continue </v-btn>
  </div>
</template>
<script>
export default {
  props: {
    trip: Object,
    routes: Array,
    mode: Number,
  },
  data() {
    return {
      modal: false,
      modal2: false,
      valid: true,
      requiredRules: [(v) => !!v || "Required."],
      repeatRules: [(v) => /^(0|[1-9]\d*)$/.test(v) || "Must be 0 or greater"],
      timeRules: [(v) => /^[1-9][0-9]*$/.test(v) || "Must be greater than 0"],
      selectedRoute: null,
    };
  },
  beforeMount() {
    this.trip.effective_date = new Date(
      Date.now() - new Date().getTimezoneOffset() * 60000
    )
      .toISOString()
      .substr(0, 10);
  },
  methods: {
    next(event) {
      if (this.validate()) this.$emit("next", this.trip);
      else this.$emit("invalid");
    },
    validate() {
      this.valid = false;
      let v = this.$refs.form.validate();
      if (v) {
        this.valid = true;
        return true;
      }
      return false;
    },
    routeSelected(r) {
      this.selectedRoute = r;
    },
  },
};
</script>