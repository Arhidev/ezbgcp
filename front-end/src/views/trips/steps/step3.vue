<template>
  <div>
    <v-card color="grey lighten-1" class="mb-12" height="200px"></v-card>
    <v-btn color="primary" @click="finish"> Finish </v-btn>
    <v-btn text @click="back"> Back </v-btn>
  </div>
</template>
<script>
export default {
  props: {
    center: Object,
    zoom: Number,
    apiKey: String,
    markers: Array,
    selected: String,
    enabled: Boolean,
  },
  data() {
    return {
      infoWindowPos: null,
    };
  },
  beforeMount() {
      
  },
  methods: {
    finish(event) {
      this.$emit("finish");
    },
    back(event) {
      this.$emit("back");
    },
  },
};
</script>