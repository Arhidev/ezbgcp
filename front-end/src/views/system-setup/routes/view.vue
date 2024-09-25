<template>
  <div>
    <vue-element-loading :active="submiting" />
    <v-card>
      <!-- Page Heading -->
      <v-card-title>
        <span class="me-3">{{ route_name }}</span>
        <v-spacer></v-spacer>
        <v-btn depressed color="secondary" @click="$router.go(-1)" class="mx-1">
          Back
          <v-icon right dark> mdi-keyboard-return </v-icon>
        </v-btn>
      </v-card-title>
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
                  <div
                    class="d-flex justify-space-between m-1 my-1 align-center"
                  >
                    <div>
                      <span class="font-weight-light mr-2"> Stop</span>
                      <v-badge :content="index + 1" inline> </v-badge>
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
  </div>
</template>

<script>
$(window).scroll(function () {
  $("#map")
    .stop()
    .animate(
      {
        marginTop: $(window).scrollTop() + "px",
        marginLeft: $(window).scrollLeft() + "px",
      },
      "slow"
    );
});

import GoogleMapLoader from "../../../components/GoogleMapLoader.vue";

import VueElementLoading from "vue-element-loading";
import {Keys} from '/src/config.js'

export default {
  components: {
    GoogleMapLoader,
    VueElementLoading,
    Keys
  },

  data() {
    return {
      apiKey: Keys.GOOGLE_MAPS_API_KEY,
      route_name: null,
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
      mode: null, //0: create, 1 edit
    };
  },
  mounted() {
    if (this.$route.params.route_id != null) {
      this.route_id = this.$route.params.route_id;
      this.fetchStops();
    }
  },
  methods: {
    addMarker(in_stop = null) {
      console.log(in_stop);
      if (in_stop == null) {
        this.$swal
          .fire({
            title: "Enter stop name",
            input: "text",
            inputValue: "Stop " + (this.stops.length + 1),
            showCancelButton: true,
          })
          .then((result) => {
            if (result.isConfirmed) {
              const stop_name = result.value.trim();
              this.addStop(in_stop, stop_name);
            }
          });
      } else {
        this.addStop(in_stop);
      }
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
          infoText: "<strong>" + stop.name + "</strong><br/>" + stop.address,
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
      this.submiting = true;
      axios
        .get(`/routes/${this.route_id}`)
        .then((response) => {
          this.submiting = false;
          this.route_name = response.data.name;
          this.directions = response.data.directions;
          let fetchedStops = response.data.stops;
          fetchedStops.forEach((stop, index) => {
            stop.order = index + 1;
            stop.lat = parseFloat(stop.lat);
            stop.lng = parseFloat(stop.lng);
          });
          this.stops = fetchedStops;
          this.setup_polyline();
          for (let index = 0; index < this.stops.length; index++) {
            this.addMarker(this.stops[index]);
          }
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
.flip-list-move {
  transition: transform 0.5s;
}

.no-move {
  transition: transform 0s;
}

.ghost {
  opacity: 0.5;
  background: #c8ebfb;
}

.list-group {
  min-height: 20px;
}

.list-group-item {
  cursor: pointer;
}

.list-group-item i {
  cursor: pointer;
}

.v-application ul {
  padding-left: 12px !important;
}

.gm-style .gm-style-iw-d {
  color: #0d508b !important;
}

</style>

<style lang="scss">
.active-stop {
  background: rgba($primary-shade--light, 0.15) !important;
}
</style>