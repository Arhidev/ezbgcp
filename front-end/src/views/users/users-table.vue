<template>
  <div>
    <v-data-table
        item-key="id"
        :loading="isLoading"
        loading-text="Loading... Please wait"
        :headers="userType === 'drivers'? driverHeaders: customersHeaders"
        :items="users"
        :search="search"
      >
        <template v-slot:top>
          <v-text-field
            v-model="search"
            label="Search"
            class="mx-4"
          ></v-text-field>
        </template>

        <template v-slot:item.status_id="{ item }">
          <v-chip
            :color="getStatusColor(item.status_id)"
            dark
          >
            {{ getStatusValue(item.status_id) }}
          </v-chip>
        </template>
    
        <template v-if="userType === 'drivers'" v-slot:item.bus="{ item }">
          <v-chip
            dark
            @click="$emit('assignBus', item)"
            :color="getBusAssignmentColor(item.bus)"
          >
            {{ item.bus != null? item.bus.license : 'No bus' }}
          </v-chip>
        </template>

        <template v-slot:item.created_at="{ item }">
          <small>{{ item.created_at | moment("LL") }}</small> -
          <small class="text-muted">{{ item.created_at | moment("LT") }}</small>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-tooltip v-if="tab==2" bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-btn
                v-bind="attrs"
                v-on="on"
                small
                class="mr-2 primary"
                @click="$emit('view-user', item)"
              >
                <v-icon small class="mr-2">
                  mdi-card-account-mail-outline
                </v-icon>
                Take Action
              </v-btn>
            </template>
            <span>Approve or Reject</span>
          </v-tooltip>
          <v-tooltip v-else bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-bind="attrs" v-on="on" small class="mr-2" @click="$emit('view-user', item)">
                mdi-eye
              </v-icon>
            </template>
            <span>View</span>
          </v-tooltip>
          <v-tooltip v-if="tab!=2"  bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-bind="attrs" v-on="on" small class="mr-2" @click="$emit('edit-user', item)">
                mdi-pencil
              </v-icon>
            </template>
            <span>Edit</span>
          </v-tooltip>
          <v-tooltip v-if="tab!=2" bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-bind="attrs" v-on="on" small class="mr-2" @click="$emit('suspend-user', item, users.indexOf(item))">
               {{item.status_id!=1? icons.mdiAccountCheck: icons.mdiAccountOff}}
              </v-icon>
            </template>
            <span>{{item.status_id!=1? 'Activate' : 'Suspend' }}</span>
          </v-tooltip>
          <v-tooltip v-if="tab!=2" bottom>
            <template v-slot:activator="{ on, attrs }">
              <v-icon v-if="userType === 'drivers' && item.bus" v-bind="attrs" v-on="on" small class="mr-2" @click="$emit('unassign-bus', item)">
                mdi-fridge-industrial-off
              </v-icon>
              <v-icon v-else-if="userType === 'drivers'" v-bind="attrs" v-on="on" small class="mr-2" @click="$emit('assign-bus', item)">
                mdi-bus
              </v-icon>
            </template>
            <span>{{item.bus? 'Un-assign bus' : 'Assign bus' }}</span>
          </v-tooltip>
        </template>
    </v-data-table>
  </div>
</template>

<script>
import {
  mdiAccountCheck,
  mdiAccountOff,
} from "@mdi/js";

export default {
  props: {
    users: Array,
    userType: String,
    tab: Number,
  },
  components: {},
  data() {
    return {
      isLoading: false,
      search: "",
      customersHeaders: [
        { text: "ID", value: "id", align: "start", filterable: false },
        { text: "Name", value: "name" },
        { text: "Email", value: "email" },
        { text: "Status", value: "status_id" },
        { text: "Created", value: "created_at" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      driverHeaders: [
        { text: "ID", value: "id", align: "start", filterable: false },
        { text: "Name", value: "name" },
        { text: "Email", value: "email" },
        { text: "Bus", value: "bus" },
        { text: "Status", value: "status_id" },
        { text: "Created", value: "created_at" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      statuses: [
        { value: "Active", color: "success" },
        { value: "Pending", color: "warning" },
        { value: "Suspended", color: "error" },
        { value: "Under Review", color: "error" },
      ],
      icons: {
        mdiAccountCheck,
        mdiAccountOff,
      },
    };
  },
  methods: {
    getStatusColor(status)
    {
      return this.statuses[status-1].color;
    },
    getStatusValue(status)
    {
      return this.statuses[status-1].value;
    },
    getBusAssignmentColor(bus)
    {
      return bus != null? 'info': 'error';
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