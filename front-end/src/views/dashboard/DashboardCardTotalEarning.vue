<template>
  <v-card>
    <v-card-title class="align-start">
      <span class="font-weight-semibold text-2xl me-1">Total Earning</span>
      <v-spacer></v-spacer>

    </v-card-title>

    <v-card-text class="py-4">
      <div class="d-flex align-center">
        <h1 class="text-4xl font-weight-semibold">
          {{ amount }}
        </h1>


      </div>

      <h4 class="mt-8 font-weight-medium">
        
      </h4>
    </v-card-text>

  </v-card>
</template>

<script>

export default {

  //amount prop
  props: {
    amount: { type: String, default: '' },
  },
}
</script>
