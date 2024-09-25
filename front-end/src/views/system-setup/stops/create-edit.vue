<template>
  <div>
    <vue-element-loading :active="submiting" :is-full-screen="true" />
    <v-card>
      <!-- Page Heading -->
      <v-card-title>
        Create new stop
        <v-spacer></v-spacer>
        <v-btn depressed color="secondary" @click="$router.go(-1)" class="mx-1">
          Cancel
          <v-icon right dark> mdi-keyboard-return </v-icon>
        </v-btn>
        <v-btn depressed color="primary" @click="saveStop" class="mx-1">
          {{ mode == 1 ? "Update" : "Save" }}
          <v-icon right dark> mdi-content-save </v-icon>
        </v-btn>
      </v-card-title>
      <v-card-text>
        <v-form ref="form" v-model="valid" lazy-validation>
          <v-row>
            <v-col cols="12" md="4">
              <v-row>
                <v-col cols="12" md="3">
                  <label for="stop-name">Name</label>
                </v-col>
                <v-col cols="12" md="9">
                  <v-text-field
                    id="stop-name"
                    v-model="stop.name"
                    outlined
                    dense
                    placeholder="Stop Name"
                    required
                    :rules="nameRules"
                  ></v-text-field>
                </v-col>
              </v-row>
              <v-row>
                <v-col cols="12" md="3">
                  <label for="stop-address">Address</label>
                </v-col>
                <v-col cols="12" md="9">
                  <div
                    class="
                      v-input v-input--hide-details v-input--dense
                      theme--light
                      v-text-field
                      v-text-field--is-booted
                      v-text-field--enclosed
                      v-text-field--outlined
                      v-text-field--placeholder
                    "
                    :class="{ 'v-input--has-state error--text': badAddress }"
                  >
                    <div class="v-input__control">
                      <div class="v-input__slot">
                        <fieldset aria-hidden="true">
                          <legend style="width: 0px">
                            <span class="notranslate">​</span>
                          </legend>
                        </fieldset>
                        <div class="v-text-field__slot">
                          <GmapAutocomplete
                            id="stop-address"
                            ref="stopAddress"
                            @place_changed="setPlace"
                            placeholder="Stop Address"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </v-col>
              </v-row>
            </v-col>
            <v-col cols="12" md="8">
              <GoogleMapLoader
                :enabled="true"
                :center="center"
                :zoom="zoom"
                :apiKey="apiKey"
                :markers="markers"
                @map-click="handleMapClick"
              >
              </GoogleMapLoader>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
import GoogleMapLoader from "../../../components/GoogleMapLoader.vue";

import draggable from "vuedraggable";
import VueElementLoading from "vue-element-loading";

import {Keys} from '/src/config.js'

export default {
  components: {
    GoogleMapLoader,
    draggable,
    VueElementLoading,
    Keys
  },

  data() {
    return {
      apiKey: Keys.GOOGLE_MAPS_API_KEY,
      valid: true,
      nameRules: [(v) => !!v || ""],
      stop_id: null,
      markers: [],
      currentPlace: null,
      stop: {
        id:null,
        name: "",
        place_id: "",
        address: "",
        lat: "",
        lng: "",
      },
      center: {
        lat: 30,
        lng: 31.2,
      },
      zoom: 15,
      submiting: false,
      badAddress: false,
      mode: null, //0: create, 1 edit
    };
  },
  mounted() {
    if (this.$route.params.stop_id != null) {
      this.stop_id = this.$route.params.stop_id;
      this.mode = 1;
      this.fetchStop()
    } else {
      this.mode = 0;
    }
    this.geolocate();
  },
  methods: {
    setPlace(place) {
      this.currentPlace = place;
      this.updateStopFromPlace(place);
      this.addStopMarker();
    },
    addStopMarker() {
      if (this.stop) {
        const position = {
          lat: parseFloat(this.stop.lat),
          lng: parseFloat(this.stop.lng),
        };
        let marker = {
          place_id: this.stop.place_id,
          position: position,
          infoText:
            "<strong>" + this.stop.name + "</strong><br/>" + this.stop.address,
        };
        this.markers = [];
        this.markers.push(marker);
        this.center = position;
      }
    },
    handleMapClick(place) {
      this.setPlace(place);
      this.$nextTick(() => {
        this.$refs.stopAddress.$el.value = this.stop.address;
      });
    },
    updateStopFromPlace(place) {
      this.stop.place_id = place.place_id;
      this.stop.address = place.formatted_address;
      this.stop.lat = place.geometry.location.lat();
      this.stop.lng = place.geometry.location.lng();
    },
    geolocate: function () {
      navigator.geolocation.getCurrentPosition((position) => {
        this.center = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        };
      });
    },
    validate() {
      this.badAddress = this.stop.address == ''
      return this.$refs.form.validate();
    },
    //API Calls
    saveStop() {
      if (!this.validate() || this.badAddress) return;
      this.submiting = true;
      axios
        .post("/stops/create-edit", {
          stop: this.stop,
        })
        .then((response) => {
          this.submiting = false;
          this.$notify({
            title: "Success",
            text: this.mode ==1? "Stop updated!" : "Stop created!",
            type: "success",
          });
          this.$router.replace({ name: "stops" });
        })
        .catch((error) => {
          this.submiting = false;
          this.$notify({
            title: "Error",
            text: "Error creating stop",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        });
    },
    fetchStop() {
      this.submiting = true;
      axios
        .get(`/stops/${this.stop_id}`)
        .then((response) => {
          this.submiting = false;
          this.stop = response.data;
          this.stop.lat = parseFloat(this.stop.lat);
          this.stop.lng = parseFloat(this.stop.lng);
          this.$refs.stopAddress.$el.value = this.stop.address;
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
          //this.$router.go(-1);
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
  cursor: move;
}

.list-group-item i {
  cursor: pointer;
}

.v-application ul {
  padding-left: 12px !important;
}

.input--error {
  border-color: red;
}

.gm-style .gm-style-iw-d {
  color: #0d508b !important;
}
</style>

<style lang="scss">
.active-stop {
  background: rgba($primary-shade--light, 0.15) !important;
}