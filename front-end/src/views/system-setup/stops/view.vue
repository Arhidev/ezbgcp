<template>
  <div>
    <v-card class="mx-auto" max-width="500"> </v-card>
    <v-card>
      <!-- Page Heading -->
      <v-card-title>
        <span v-if="stop != null" class="me-3">{{ stop.name }}</span>
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
                v-if="stop != null"
                  class="list-group-item pa-2 active-stop"
                  @click="
                    selectedItem = stop.place_id;
                  "
                >
                  <div class="text-dark m-1 my-1">
                    {{ stop.address }}
                  </div>
                  <div
                    class="d-flex justify-space-between m-1 my-1 align-center"
                  >
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
            >
            </GoogleMapLoader>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>

import GoogleMapLoader from "../../../components/GoogleMapLoader.vue";
import {Keys} from '/src/config.js'

export default {
  components: {
    GoogleMapLoader,
    Keys
  },

  data() {
    return {
      apiKey: Keys.GOOGLE_MAPS_API_KEY,
      stop_id: null,
      markers: [],
      selectedIdx: null,
      currentPlace: null,
      stop: null,
      center: {
        lat: 30,
        lng: 31.2,
      },
      zoom: 12,
      selectedItem: null,
    };
  },
  mounted() {
    if (this.$route.params.stop_id != null) {
      this.stop_id = this.$route.params.stop_id;
      console.log(this.stop_id);
      this.fetchStop();
    }
  },
  methods: {
    addStopMarker() {
      if (this.stop) {
        const position = {
          lat: parseFloat(this.stop.lat),
          lng: parseFloat(this.stop.lng),
        };
        let marker = {
          place_id: this.stop.place_id,
          position: position,
          infoText: "<strong>" + this.stop.name + "</strong><br/>" + this.stop.address,
        };
        this.markers.push(marker);
        this.center = position;
      }
    },
    //API Calls
    fetchStop() {
      this.submiting = true;
      axios
        .get(`/stops/${this.stop_id}`)
        .then((response) => {
          this.submiting = false;
          this.stop = response.data;
          this.stop.lat = parseFloat(this.stop.lat);
          this.stop.lng = parseFloat(this.stop.lng);
          this.addStopMarker();
        })
        .catch((error) => {
          this.submiting = false;
          this.$notify({
            title: "Error",
            text: "Error fetching stop data",
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
.gm-style .gm-style-iw-d {
  color: #0d508b !important;
}

.v-application ul {
  padding-left: 12px !important;
}
</style>

<style lang="scss">
.active-stop {
  background: rgba($primary-shade--light, 0.15) !important;
}