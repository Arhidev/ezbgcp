<template>
  <div>
    <vue-element-loading :active="submitting" :is-full-screen="true" />
    <v-card class="mx-auto" max-width="500"> </v-card>
    <v-card>
      <!-- Page Heading -->
      <v-card-title>
        <span class="me-3">{{ route_name }}</span>
        <v-spacer></v-spacer>
        <v-btn depressed color="secondary" to="/routes" class="mx-1">
          Cancel
          <v-icon right dark> mdi-keyboard-return </v-icon>
        </v-btn>
        <v-btn depressed color="primary" @click="saveRoute" class="mx-1">
          {{ mode == 1 ? "Update" : "Save" }}
          <v-icon right dark> mdi-content-save </v-icon>
        </v-btn>
      </v-card-title>
      <v-card-text>
        <div class="row">
          <div class="col-md-4">
            <v-list>
              <v-list-group
                :value="false"
                prepend-icon="mdi-map-marker-plus"
                @click="addStopOpen = !addStopOpen"
                @input="addStopOpenClose"
              >
                <template v-slot:activator>
                  <v-list-item-content>
                    <v-list-item-title class="mb-2">
                      Add Stop</v-list-item-title
                    >
                    <v-list-item-subtitle
                      >Total: {{ stops.length }} stops</v-list-item-subtitle
                    >
                  </v-list-item-content>
                </template>
                <v-list-item>
                  <div class="row row--dense justify-space-between">
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
                            ref="gmapAutocomplete"
                            @place_changed="setPlace"
                            placeholder="Enter a location or click on map"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                    <v-btn
                      color="primary"
                      dark
                      small
                      icon
                      rounded
                      @click="addMarker(null)"
                    >
                      <v-icon> mdi-plus-thick </v-icon></v-btn
                    >
                  </div>
                </v-list-item>
                <v-subheader> OR </v-subheader>
                <v-list-item>
                  <div class="row row--dense justify-space-between align-center justify-center">
                    <v-col cols="12" md="10">
                    <v-autocomplete
                      v-model="model"
                      :items="items"
                      :loading="isSearching"
                      :search-input.sync="search"
                      hide-selected
                      item-text="Description"
                      label="Choose from saved stops"
                      placeholder="Start typing to Search"
                      return-object
                      @change="stopSelected"
                    ></v-autocomplete>
                    </v-col>
                    
                    <v-btn
                      color="primary"
                      dark
                      small
                      icon
                      rounded
                      @click="addSavedStop"
                    >
                      <v-icon> mdi-plus-thick </v-icon></v-btn
                    >
                    </div>
                </v-list-item>
              </v-list-group>
            </v-list>
            <v-divider></v-divider>
              <div
                  class="list-group-item pa-2"
                  :class="selectedIdx == index ? 'active-stop' : ''"
                  v-for="(element, index) in stops"
                  :key="element.place_id"
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
                  <div class="text-dark m-1 my-4">
                    <div v-if="index!==0">
                      <v-select
                        v-model="chosen_routes[index-1]"
                        :items=" ordered_directions[index-1] != null ? ordered_directions[index-1] : []"
                        :item-text="item => item.summary"
                        :item-value="item => item.index"
                        label="Route from previous stop"
                        class="mr-2"
                      ></v-select>
                    </div>
                  </div>
                  <div
                    class="d-flex justify-space-between m-1 my-1 align-center"
                  >
                    <div>
                      <span class="font-weight-light mr-2"> Stop</span>
                      <v-badge :content="index + 1" inline> </v-badge>
                    </div>
                    <div>
                      <v-btn icon color="error" @click="deleteMarker(index)">
                        <v-icon dark> mdi-trash-can </v-icon>
                      </v-btn>
                    </div>
                  </div>
                </div>

          </div>
          <div class="col-md-8" id="map">
            <GoogleMapLoader
              :enabled="addStopOpen"
              :center="center"
              :selected="selectedItem"
              :zoom="zoom"
              :apiKey="apiKey"
              :markers="markers"
              @map-click="handleMapClick"
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
import axios from "axios";

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
      descriptionLimit: 60,
      entries: [],
      isSearching: false,
      model: null,
      search: null,
      route_name: null,
      route_id: null,
      markers: [],
      selectedIdx: null,
      currentPlace: null,
      stops: [],
      center: {
        lat: 30,
        lng: 31.2,
      },
      zoom: 12,
      selectedItem: null,
      editable: true,
      isDragging: false,
      delayedDragging: false,
      addStopOpen: false,
      submitting: false,
      mode: null, //0: create, 1 edit
      selectedSavedStop: null,
      ordered_directions:[],
      chosen_routes:[],
      polyline: [],
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
    };
  },
  mounted() {
    this.loadAllStops();
    if (this.$route.params.route_name != null) {
      this.mode = 0;
      this.geolocate();
      this.route_name = this.$route.params.route_name;
    } else if (this.$route.params.route_id != null) {
      this.mode = 1;
      this.route_id = this.$route.params.route_id;
      this.route_name = this.$route.params.new_route_name;
      console.log(this.route_name);
      this.fetchStops();
    }
  },
  watch: {
    search(val) {
      // Items have already been loaded
      if (this.items.length > 0) return;

      // Items have already been requested
      if (this.isSearching) return;
      this.loadAllStops();
    },
    isDragging(newValue) {
      if (newValue) {
        this.delayedDragging = true;
        return;
      }
      this.$nextTick(() => {
        this.delayedDragging = false;
      });
    },
    stops(val) {
      console.log("stops changed");
      //this.redoDirections();
    },
    chosen_routes(val) {
      this.setup_polyline();
    },
  },
  computed: {
    dragOptions() {
      return {
        animation: 0,
        group: "description",
        disabled: !this.editable,
        ghostClass: "ghost",
      };
    },
    fields() {
      if (!this.model) return [];

      return Object.keys(this.model).map((key) => {
        return {
          key,
          value: this.model[key] || "n/a",
        };
      });
    },
    items() {
      return this.entries.map((stop) => {
        const Description = stop.name;
        return Object.assign({}, stop, { Description });
      });
    },
  },
  methods: {
    loadAllStops()
    {
      this.isSearching = true;

      axios
        .get(`/stops/all`)
        .then((response) => {
          this.entries = response.data;
          this.count = this.entries.length;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving stops",
            type: "error",
          });
          console.log(error);
        })
        .then(() => {
          this.isSearching = false
        });
    },
    stopSelected(s)
    {
      s.lat = parseFloat(s.lat);
      s.lng = parseFloat(s.lng);
      this.selectedSavedStop = s;
    },
    addSavedStop()
    {
      if(this.selectedSavedStop != null)
      {
        this.stops.push(this.selectedSavedStop);
        this.addMarker(this.selectedSavedStop);
        this.selectedSavedStop = null;
        if(this.stops.length>1)
        {
          var lastStop = this.stops[this.stops.length-1];
          var beforeLastStop = this.stops[this.stops.length-2];
          this.getAddedStopRoute(lastStop, beforeLastStop);
        }
      }
    },
    addStopOpenClose() {
      this.$nextTick(() => {
        this.$refs.gmapAutocomplete.$el.focus();
        this.$refs.gmapAutocomplete.$el.value = "";
      });
    },
    onMove({ relatedContext, draggedContext }) {
      console.log("onMove")
      this.redoDirections();
      const relatedElement = relatedContext.element;
      const draggedElement = draggedContext.element;
      this.selectedIdx = -1;
      this.selectedItem = null;
      return (
        (!relatedElement || !relatedElement.fixed) && !draggedElement.fixed
      );
    },
    setPlace(place) {
      this.currentPlace = place;
    },
    deleteMarker(index) {
      this.$swal
        .fire({
          title: "Are you sure?",
          text: "You won't be able to revert this!",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Yes, delete it!",
        })
        .then((result) => {
          if (result.isConfirmed) {
            for (let ii = 0; ii < this.markers.length; ii++) {
              if (this.markers[ii].place_id == this.stops[index].place_id) {
                this.markers.splice(ii, 1);
                break;
              }
            }
            this.redoDirections(index);
            this.stops.splice(index, 1);
            this.$notify({
              title: "Success",
              text: "Stop deleted!",
              type: "success",
            });
          }
        });
    },
    handleMapClick(place) {
      this.currentPlace = place;
      console.log(this.currentPlace);
      this.addMarker();
    },
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
          if(this.stops.length>1)
          {
            var lastStop = this.stops[this.stops.length-1];
            var beforeLastStop = this.stops[this.stops.length-2];
            this.getAddedStopRoute(lastStop, beforeLastStop);
          }
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
    getAddedStopRoute(destinationStop, originStop)
    {
      this.submitting = true;
      const directionsService = new window.google.maps.DirectionsService();
      directionsService.route(
          {
            origin: new window.google.maps.LatLng(originStop.lat, originStop.lng),
            destination: new window.google.maps.LatLng(destinationStop.lat, destinationStop.lng),
            travelMode: "DRIVING",
            provideRouteAlternatives: true,
          },
          (response, status) => {
            if(status==="OK"){
              let routes = response.routes.map((route, index) => {
                return {
                  'summary':route.summary,
                  'overview_path':route.overview_path,
                  'index': index,
                }
              });
              this.ordered_directions.push(routes);
              this.chosen_routes.push(0);
              this.setup_polyline();
              this.submitting = false;
            }
            else {
              this.submitting = false;
              this.$swal.fire({
                title: "Error",
                text: "Could not get directions",
                icon: "error",
                confirmButtonText: "OK",
              });
            }
          }
      );
    },
    redoDirections(index) {
      //if first stop, delete first route
      if(index==0)
      {
        this.ordered_directions.splice(index, 1);
        this.chosen_routes.splice(index, 1);
        this.setup_polyline();
      }
      // check if last stop
      else if(index == this.stops.length-1)
      {
        this.ordered_directions.splice(index-1, 1);
        this.chosen_routes.splice(index-1, 1);
        this.setup_polyline();
      }
      // if not last stop, delete route at index +1 and replace the route at index with the new route
      else
      {
        this.ordered_directions.splice(index, 1);
        this.chosen_routes.splice(index, 1);
        var originStop = this.stops[index-1];
        var destinationStop = this.stops[index+1];
        this.submitting = true;
        const directionsService = new window.google.maps.DirectionsService();
        directionsService.route(
            {
              origin: new window.google.maps.LatLng(originStop.lat, originStop.lng),
              destination: new window.google.maps.LatLng(destinationStop.lat, destinationStop.lng),
              travelMode: "DRIVING",
              provideRouteAlternatives: true,
            },
            (response, status) => {
              if(status==="OK"){
                let routes = response.routes.map((route, i) => {
                  return {
                    'summary':route.summary,
                    'overview_path':route.overview_path,
                    'index': i,
                  }
                });
                this.ordered_directions[index-1] = routes;
                this.chosen_routes[index-1] = 0;
                this.setup_polyline();
                this.submitting = false;
              }
              else {
                this.submitting = false;
                this.$swal.fire({
                  title: "Error",
                  text: "Could not get directions",
                  icon: "error",
                  confirmButtonText: "OK",
                });
              }
            }
        );
      }
    },
    randomColor() {
      return this.colors[Math.floor(Math.random() * this.colors.length)];
    },
    setup_polyline() {
      //Loop through val
      this.polyline=[];
      for (let i = 0; i < this.ordered_directions.length; i++) {
        if(this.ordered_directions[i].length > this.chosen_routes[i])
        {
          var path = {
            data: this.ordered_directions[i][this.chosen_routes[i]].overview_path,
            strokeColor: this.randomColor(),
          };
          this.polyline.push(path);
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
        fixed: false,
        new: true,
      };
      return stop;
    },
    getStopId(){
      var max=-1;
      for(var i=0;i<this.stops.length;i++){
        if(this.stops[i].id>max){
          max=this.stops[i].id;
        }
      }
      return max;
    },
    geolocate: function () {
      navigator.geolocation.getCurrentPosition((position) => {
        this.center = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        };
      });
    },
    //API Calls
    saveRoute() {
      this.submitting = true;
      axios
        .post("/routes/create-edit", {
          id: this.route_id,
          route: this.route_name,
          stops: this.stops,
          chosen_routes: this.chosen_routes,
          ordered_directions: this.ordered_directions,
        })
        .then((response) => {
          this.submitting = false;
          this.$notify({
            title: "Success",
            text: "Route created!",
            type: "success",
          });
          this.$router.replace({ name: "routes" });
        })
        .catch((error) => {
          this.submitting = false;
          this.$notify({
            title: "Error",
            text: "Error creating route",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        });
    },
    fetchStops() {
      this.submitting = true;
      axios
        .get(`/routes/${this.route_id}`)
        .then((apiResponse) => {
          this.geolocate();
          //this.route_name = apiResponse.data.name;
          let fetchedStops = apiResponse.data.stops;
          fetchedStops.forEach((stop, index) => {
            stop.lat = parseFloat(stop.lat);
            stop.lng = parseFloat(stop.lng);
          });
          this.stops = fetchedStops;
          for (let index = 0; index < this.stops.length; index++) {
            this.addMarker(this.stops[index]);
          }
          this.ordered_directions = apiResponse.data.directions;
          for (let i = 0; i < this.ordered_directions.length; i++) {
            for (let j = 0; j < this.ordered_directions[i].length; j++) {
              const direction = this.ordered_directions[i][j];
              if (direction.current === 1) {
                this.chosen_routes[i] = j;
                break;
              }
            }
          }
          this.setup_polyline();
          this.submitting = false;
        })
        .catch((error) => {
          this.submitting = false;
          this.$notify({
            title: "Error",
            text: "Error fetching stops of this route",
            type: "error",
          });
          console.log(error);
          this.$router.replace({ name: "routes" });
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