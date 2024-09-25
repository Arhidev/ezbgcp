<template>
  <v-data-table
    item-key="id"
    :loading="isLoading"
    loading-text="Loading... Please wait"
    :headers="isActive ? activeHeaders : completedHeaders"
    :items="complaints"
    :search="search"
  >
    <template v-slot:top>
      <v-text-field
        v-model="search"
        label="Search"
        class="mx-4"
      ></v-text-field>
    </template>


    <template v-slot:item.created_at="{ item }">
      <small>{{ item.created_at | moment("LL") }}</small> -
      <small class="text-muted">{{ item.created_at | moment("LT") }}</small>
    </template>

    <template v-slot:item.planned_time="{ item }">
      <small>{{ item.planned_time | moment("LL") }}</small> -
      <small class="text-muted">{{ item.planned_time | moment("LT") }}</small>
    </template>

    <template v-slot:item.actual_time="{ item }">
      <small>{{ item.actual_time | moment("LL") }}</small> -
      <small class="text-muted">{{ item.actual_time | moment("LT") }}</small>
    </template>

    <template v-slot:item.action="{ item }">
      <v-chip v-if="!isActive"
        :color="getComplaintActionColor(item.action)"
        dark
      >
        {{ getComplaintActionValue(item.action) }}
        <v-icon class="ml-2">
          {{ getIconOfComplaintAction(item.action) }}
        </v-icon>
      </v-chip>
    </template>
    
    <template v-slot:item.actions="{ item }">
      
      <!-- view complaint -->
      <v-btn depressed small density="compact" @click="$emit('view-complaint', item)">
        <v-icon dark> mdi-message-bulleted </v-icon>
      </v-btn>

      <v-btn v-if="!isActive" depressed small color="primary" density="compact" @click="$emit('view-response', item)">
        <v-icon dark> mdi-email-sync-outline </v-icon>
      </v-btn>

      <v-btn v-if="isActive" class="mr-2" depressed small density="compact" color="success" @click="$emit('refund-complaint', item, complaints.indexOf(item))">
        <v-icon dark> mdi-cash-refund </v-icon>
      </v-btn>
      <v-btn v-if="isActive" depressed small density="compact" color="error" @click="$emit('cancel-complaint', item, complaints.indexOf(item))">
        <v-icon dark> mdi-close-octagon-outline </v-icon>
      </v-btn>
    </template>
  </v-data-table>
</template>

<script>
export default {
  props: {
    complaints: Array,
    isLoading: Boolean,
    isActive: Boolean,
  },
  data() {
    return {
      search: "",
      activeHeaders: [
        { text: "Ticket", value: "ticket_number", align: "start" },
        { text: "Customer", value: "user.name" },
        { text: "Stop", value: "stop_name" },
        { text: "Distance To Stop", value: "distanceToStop" },
        { text: "Distance To Bus", value: "distanceToBus" },
        { text: "Planned arrival", value: "planned_time" },
        { text: "Actual arrival", value: "actual_time" },
        { text: "Created", value: "created_at" },
        { text: "", value: "actions", sortable: false},
      ],
      completedHeaders: [
        { text: "Ticket", value: "ticket_number", align: "start" },
        { text: "Customer", value: "user.name" },
        { text: "Stop", value: "stop_name" },
        { text: "Distance To Stop", value: "distanceToStop" },
        { text: "Distance To Bus", value: "distanceToBus" },
        { text: "Planned arrival", value: "planned_time" },
        { text: "Actual arrival", value: "actual_time" },
        { text: "Created", value: "created_at" },
        { text: "Action", value: "action" },
        { text: "", value: "actions", sortable: false },
      ],
    };
  },
  methods: {
    getComplaintActionColor(action) {
      switch (action) {
        case "refund":
          return "success";
        case "cancel":
          return "error";
        default:
          return "primary";
      }
    },
    getComplaintActionValue(action) {
      switch (action) {
        case "refund":
          return "Refunded";
        case "cancel":
          return "Cancelled";
        default:
          return "Pending";
      }
    },
    getIconOfComplaintAction(action) {
      switch (action) {
        case "refund":
          return "mdi-cash-refund";
        case "cancel":
          return "mdi-close-octagon-outline";
        default:
          return "mdi-email-sync-outline";
      }
    },
  },
};
</script>

<style lang="scss">
.theme--light.v-list-item:not(.v-list-item--active):not(.v-list-item--disabled):hover{
  cursor: pointer;
  background: rgba($primary-shade--light, 0.15) !important;
}
</style>