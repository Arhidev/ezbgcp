<template>
  <div>
    <GmapMap
      :center="center"
      :zoom="zoom"
      ref="mapp"
      style="width: 100%; height: 400px"
      @click="mark"
    >
      <gmap-info-window
        :options="infoOptions"
        :position="infoWindowPos"
        :opened="infoWinOpen"
        @closeclick="infoWinOpen = false"
      >
      </gmap-info-window>
      <GmapMarker
        :key="index"
        v-for="(m, index) in markers"
        :position="m.position"
      />
      <gmap-polyline
          v-for="(p, index) in polylines"
          :path="p.data"
          :options="{ strokeColor: p.strokeColor, strokeWeight:5 }"></gmap-polyline>
    </GmapMap>
    <v-btn v-if="markers.length > 1" depressed color="success" @click="fitBounds" class="ma-1 float-right my-2">
      Fit
      <v-icon right dark>
        mdi-fit-to-screen-outline
      </v-icon>
    </v-btn>
  </div>
</template>
<script>
import { loadGmapApi, gmapApi } from "vue2-google-maps";

export default {
  props: {
    center: Object,
    zoom: Number,
    apiKey: String,
    markers: Array,
    selected: String,
    enabled: Boolean,
    polylines: []
  },
  data() {
    return {
      infoWindowPos: null,
      infoWinOpen: false,
      currentMidx: null,

      infoOptions: {
        content: "",
        //optional: offset infowindow so it visually sits nicely on top of our marker
        pixelOffset: {
          width: 0,
          height: -35,
        },
      },
    };
  },
  watch: {
    markers: function (newVal, oldVal) {
      this.fitBounds();
    },
    selected: function (newVal, oldVal) {
      let selectedMarkerIndex = -1;
      for (let index = 0; index < this.markers.length; index++) {
        const marker = this.markers[index];
        if (marker.place_id == newVal) {
          selectedMarkerIndex = index;
          break;
        }
      }
      if (selectedMarkerIndex != -1) {
        this.toggleInfoWindow(
          this.markers[selectedMarkerIndex],
          selectedMarkerIndex
        );
      } else {
        this.infoWinOpen = false;
      }
    },
  },
  computed: {
    google: gmapApi,
  },
  beforeMount() {
    if (this.google == null) {
      loadGmapApi({
        key: this.apiKey,
        libraries: "places, geocoding",
      });
    }
  },
  methods: {
    mark(event) {
      if(!this.enabled)
        return;

      const latlng = {
          lat: event.latLng.lat(),
          lng: event.latLng.lng(),
        };

      let geocoder = new google.maps.Geocoder();
      geocoder
        .geocode({ location: latlng })
        .then((response) => {
          if (response.results[0]) {
            this.$emit('map-click', response.results[0])
          } else {
            window.alert("No results found");
          }
        })
        .catch((e) => window.alert("Geocoder failed due to: " + e));
    },
    fitBounds() {
      if(this.markers.length == 1)
      return;
      var b = new google.maps.LatLngBounds();
      if (this.markers.length == 0) return;

      this.markers.forEach((marker) => {
        b.extend({
          lat: marker.position.lat,
          lng: marker.position.lng,
        });
      });
      this.$refs.mapp.fitBounds(b);
    },
    toggleInfoWindow: function (marker, idx) {
      this.infoWindowPos = marker.position;
      this.infoOptions.content = marker.infoText;

      //check if its the same marker that was selected if yes toggle
      if (this.currentMidx == idx) {
        this.infoWinOpen = !this.infoWinOpen;
      }
      //if different marker set infowindow to open and reset current marker index
      else {
        this.infoWinOpen = true;
        this.currentMidx = idx;
      }
    },
  },
};
</script>