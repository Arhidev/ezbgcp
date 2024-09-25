<template>
  <div>
    <vue-element-loading :active="submiting" />
    <v-card color="grey lighten-1" class="mb-12" elevation="0">
      <v-card-text>
        <div class="row">
          <div class="col-md-4">
            <div
              class="list-group-item pa-2"
              :class="selectedIdx == index ? 'active-stop' : ''"
              v-for="(element, index) in stops"
              :key="element.order"
              @click="
                selectedItem = element.place_id;
                selectedIdx = index;
              "
            >
              <div class="font-weight-bold text-dark m-1 my-1">
                {{ element.name }}
              </div>
              <div class="text-dark m-1 my-1">
                {{ element.address }}
              </div>
              <div class="font-weight-bold text-dark m-1 my-4">
                <div v-if="index!==0">
                  <div v-for="(direction, j) in directions[index-1]" :key="direction.index">
                    <div v-if="direction.current === 1">
                      Direction: {{ direction.summary }}
                    </div>
                  </div>
                </div>
              </div>
              <div class="d-flex justify-space-between m-1 my-1 align-center">
                <div>
                  <span class="font-weight-light mr-2"> Stop</span>
                  <v-badge :content="index + 1" inline> </v-badge>
                  <div class="d-flex align-center mt-2">
                    <span class="font-weight-bold mr-2"> Arrival time</span>
                    <v-btn
                      v-if="mode!=3 && index>0"
                      class="mx-2"
                      fab
                      outlined
                      x-small
                      color="secondary"
                      @click="decrement(index)"
                    >
                      <v-icon dark>
                        mdi-minus
                      </v-icon>
                    </v-btn>
                    {{time[index]}}
                    <v-btn
                      v-if="mode!=3 && index>0"
                      class="mx-2"
                      fab
                      outlined
                      x-small
                      color="success"
                      @click="increment(index)"
                    >
                      <v-icon dark>
                        mdi-plus
                      </v-icon>
                    </v-btn>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-8" id="map">
            <GoogleMapLoader
              :enabled="false"
              :center="center"
              :selected="selectedItem"
              :zoom="zoom"
              :apiKey="apiKey"
              :markers="markers"
              :polylines="polyline"
            >
            </GoogleMapLoader>
          </div>
        </div>
      </v-card-text>
    </v-card>
    <v-btn v-if="mode!=3" color="primary" @click="finish"> Finish </v-btn>
    <v-btn v-if="mode!=3" text @click="back"> Back </v-btn>
  </div>
</template>
<script>
import GoogleMapLoader from "../../../components/GoogleMapLoader.vue";

import VueElementLoading from "vue-element-loading";

export default {
  components: {
    GoogleMapLoader,
    VueElementLoading,
  },
  props: {
    trip: Object,
    timestep: Number,
    mode: Number,
    apiKey: String,
  },
  data() {
    return {
      time:[],
      route_id: null,
      markers: [],
      selectedIdx: null,
      currentPlace: null,
      stops: [],
      directions: [],
      polyline:[],
      colors: [
        "#000000",
        "#FF0000",
        "#0000FF",
        "#FF00FF",
        "#808080",
        "#800000",
        "#008000",
        "#800080",
        "#008080",
        "#000080",
      ],
      center: {
        lat: 30,
        lng: 31.2,
      },
      zoom: 12,
      selectedItem: null,
      submiting: false,
    };
  },
  mounted() {
    console.log(this.trip);
    this.route_id = this.trip.route.id;
    this.fetchStops();
  },
  methods: {
    changeMins(t, c)
    {
      t.setMinutes(t.getMinutes() + c);
    },
    adjustTimeFormat(t)
    {
      return t.getHours().toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping:false}) + ":" + t.getMinutes().toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping:false})
    },
    increment(index) {
      this.trip.inter_time[index] = parseInt(this.trip.inter_time[index] + this.timestep);
      this.AdjustTimeForAllStops(index);
    },
    decrement(index) {
      if(this.trip.inter_time[index] > this.timestep)
      {
        this.trip.inter_time[index] = parseInt(this.trip.inter_time[index] - this.timestep);
        this.AdjustTimeForAllStops(index);
      }
    },
    AdjustTimeForAllStops(index)
    {
        for (let ii = index; ii < this.time.length; ii++) {
          let stop_time = this.parseTime(this.time[ii-1]);
          this.changeMins(stop_time, this.trip.inter_time[ii])
          let new_time = this.adjustTimeFormat(stop_time)
          this.$set(this.time, ii, new_time);
        }
    },
    finish(event) {
      this.$emit("finish");
    },
    back(event) {
      this.$emit("back");
    },
    addMarker(in_stop = null) {
      this.addStop(in_stop);
    },
    addStop(in_stop, stop_name) {
      let stop =
        in_stop != null
          ? in_stop
          : this.getStopFromPlace(this.currentPlace, stop_name);
      if (stop) {
        const position = {
          lat: parseFloat(stop.lat),
          lng: parseFloat(stop.lng),
        };
        this.markers.push({
          place_id: stop.place_id,
          position: position,
          infoText: "<div class='active-stop'><strong>" + stop.name + "</strong><br/>" + stop.address + "</div>",
        });
        if (in_stop == null) {
          this.stops.push(stop);
        }

        //this.center = marker;
        this.currentPlace = null;
        if (this.$refs.gmapAutocomplete != null) {
          this.$refs.gmapAutocomplete.$el.value = "";
          this.$nextTick(() => {
            this.$refs.gmapAutocomplete.$el.focus();
          });
        }
      }
    },
    getStopFromPlace(place, stop_name) {
      let stop = {
        name: stop_name,
        place_id: place.place_id,
        address: place.formatted_address,
        lat: place.geometry.location.lat(),
        lng: place.geometry.location.lng(),
        order: this.stops.length + 1,
        fixed: false,
      };
      return stop;
    },
    parseTime(t) {
      var d = new Date();
      var time = t.match(/(\d+)(?::(\d\d))?\s*(p?)/);
      d.setHours(parseInt(time[1]) + (time[3] ? 12 : 0));
      d.setMinutes(parseInt(time[2]) || 0);
      return d;
    },
    randomColor() {
      return this.colors[Math.floor(Math.random() * this.colors.length)];
    },
    setup_polyline() {
      this.polyline=[];
      for (let i = 0; i < this.directions.length; i++) {
        for (let j = 0; j < this.directions[i].length; j++) {
          const direction = this.directions[i][j];
          if (direction.current === 1) {
            this.polyline.push({
              data: direction.overview_path,
              strokeColor: this.randomColor(),
            });
          }
        }
      }
    },
    //API Calls
    fetchStops() {
      let first_stop_time = this.parseTime(this.trip.first_stop_time);

      this.submiting = true;
      axios
        .get(`/routes/${this.route_id}`)
        .then((response) => {
          this.submiting = false;
          let fetchedStops = response.data.stops;
          this.directions = response.data.directions;
          fetchedStops.forEach((stop, index) => {
            stop.order = index + 1;
            stop.lat = parseFloat(stop.lat);
            stop.lng = parseFloat(stop.lng);
          });
          this.stops = fetchedStops;
          let duplicate = false;
          // if(this.trip.trip_details != null)
          // {
          //   let identical = true;
          //   for (let s = 0; s < this.stops.length; s++) {
          //     const stop = this.stops[s];
          //     if(stop.id != this.trip.trip_details[s].stop.id)
          //     {
          //       identical = false;
          //       break;
          //     }
          //   }
          //   duplicate = identical;
          // }
          for (let index = 0; index < this.stops.length; index++) {
            if (index == 0) {
              this.trip.inter_time = [];
              this.time = [];
              let f_stop_time = this.parseTime(this.trip.first_stop_time);
              this.trip.first_stop_time = this.adjustTimeFormat(f_stop_time)
              if(!duplicate)
              {
                this.trip.inter_time.push(0);
                this.time.push(this.trip.first_stop_time);
              }
              else
              {
                this.time.push(this.trip.trip_details[index].planned_timestamp);
                this.trip.inter_time.push(this.trip.trip_details[index].inter_time);
              }
            } else {
              if(!duplicate)
              {
                this.trip.inter_time.push(parseInt(this.trip.stop_to_stop_avg_time));
                first_stop_time.setMinutes(
                  first_stop_time.getMinutes() +
                    parseInt(this.trip.stop_to_stop_avg_time)
                ); // timestamp
                first_stop_time = new Date(first_stop_time); // Date object
                this.time.push(
                  first_stop_time.getHours() + ":" + first_stop_time.getMinutes()
                );
              }
              else
              {
                this.time.push(this.trip.trip_details[index].planned_timestamp);
                this.trip.inter_time.push(this.trip.trip_details[index].inter_time);
              }
            }
            this.addMarker(this.stops[index]);
          }
          this.setup_polyline();
          console.log(this.stops);
        })
        .catch((error) => {
          this.submiting = false;
          this.$notify({
            title: "Error",
            text: "Error fetching stops of this route",
            type: "error",
          });
          console.log(error);
          this.$router.go(-1);
          //this.$swal("Error", error.response.data.message, "error");
        });
    },
  },
};
</script>

<style>

.list-group-item {
  cursor: pointer;
}

.list-group-item i {
  cursor: pointer;
}

.gm-style .gm-style-iw-d {
  color: #0d508b !important;
}

</style>

<style lang="scss">
.active-stop {
  background: rgba($primary-shade--light, 0.15) !important;
}