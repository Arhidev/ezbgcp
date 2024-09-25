<template>
  <v-card>
    <v-card-title class="align-start">
      <span class="font-weight-semibold">Statistics</span>
      <v-spacer></v-spacer>
      <v-btn
        icon
        small
        class="me-n3 mt-n2"
      >
      </v-btn>
    </v-card-title>

    <v-card-text>
      <v-row>
        <v-col
          v-for="data in allCounts"
          :key="data.title"
          cols="6"
          md="2"
          class="d-flex align-center"
        >
          <v-avatar
            size="44"
            :color="resolveStatisticsIconVariation (data.title).color"
            rounded
            class="elevation-1"
          >
            <v-icon
              dark
              color="white"
              size="30"
            >
              {{ resolveStatisticsIconVariation (data.title).icon }}
            </v-icon>
          </v-avatar>
          <div class="ms-3">
            <p class="text-xs mb-0">
              {{ data.title }}
            </p>
            <h3 class="text-xl font-weight-semibold">
              {{ data.total }}
            </h3>
          </div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script>
export default {
  //prop all-counts
  props: {
    allCounts: { type: Array, default: () => [] },
  },

  methods: {
    resolveStatisticsIconVariation (title) {
      switch (title) {
        case 'Drivers':
          return {
            icon: 'mdi-account-tie-hat-outline',
            color: 'success',
          }
        case 'Customers':
          return {
            icon: 'mdi-account-group',
            color: 'primary',
          }
        case 'Reservations':
          return {
            icon: 'mdi-poll',
            color: 'info',
          }
        case 'Trips':
          return {
            icon: 'mdi-bus-clock',
            color: 'warning',
          }
        case 'Routes':
          return {
            icon: 'mdi-road-variant',
            color: 'error',
          }
        case 'Stops':
          return {
            icon: 'mdi-bus-stop',
            color: 'success',
          }
        default:
          return {
            icon: 'mdi-account',
            color: 'primary',
          }
      }
    },
  },
}
</script>
