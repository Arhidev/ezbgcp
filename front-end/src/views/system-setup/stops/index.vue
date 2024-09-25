<template>
  <div>
    <v-card>
      <v-card-title>
      <v-icon color="primary">
        mdi-bus-stop
      </v-icon>
        <span class="pl-2">Stops</span>
        <v-spacer></v-spacer>
        <create-button @create="createStop"></create-button>
        <activation-tool-tip model="stops"></activation-tool-tip>
      </v-card-title>
      <v-data-table
        item-key="id"
        :loading="isLoading"
        loading-text="Loading... Please wait"
        :headers="headers"
        :items="stops"
        :search="search"
      >
        <template v-slot:top>
          <v-text-field
            v-model="search"
            label="Search"
            class="mx-4"
          ></v-text-field>
        </template>

        <template v-slot:item.routes="{ item }">
          <a v-if="item.routes.length" @click.stop="displayRoutes(stops.indexOf(item))">{{ item.routes.length }}</a>
          <span v-else>No routes</span>
        </template>

        <template v-slot:item.created_at="{ item }">
          <small>{{ item.created_at | moment("LL") }}</small> -
          <small class="text-muted">{{ item.created_at | moment("LT") }}</small>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-icon small class="mr-2" @click="viewStop(item)">
            mdi-eye
          </v-icon>
          <v-icon small class="mr-2" @click="editStop(item)">
            mdi-pencil
          </v-icon>
          <v-icon small @click="deleteStop(item, stops.indexOf(item))">
            mdi-delete
          </v-icon>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-if="stops[selectedStop]" v-model="dialog" max-width="290">
      <v-card>
        <v-card-title class="text-h5">
          {{ stops[selectedStop].name }}
        </v-card-title>

        <v-card-text>
          <v-list dense>
            <v-subheader>Routes</v-subheader>
            <v-list-item-group>
              <v-list-item
                v-for="(item, i) in stops[selectedStop].routes"
                :key="i"
              >
                <v-icon small class="mr-2">
                  mdi-eye
                </v-icon>
                <v-list-item-content>
                  <v-list-item-title v-text="item.name" @click="viewRoute(item)"></v-list-item-title>
                </v-list-item-content>
              </v-list-item>
            </v-list-item-group>
          </v-list>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn color="green darken-1" text @click="dialog = false">
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import ActivationToolTip from "@/components/ActivationToolTip";
import CreateButton from "@/components/CreateButton";

export default {
  components: {
    ActivationToolTip,
    CreateButton,
  },
  data() {
    return {
      stops: [],
      dialog: false,
      isLoading: false,
      selectedStop: null,
      search: "",
      headers: [
        { text: "ID", value: "id", align: "start", filterable: false },
        { text: "Name", value: "name" },
        { text: "Address", value: "address", width: 300 },
        { text: "Routes", value: "routes" },
        { text: "Created", value: "created_at" },
        { text: "Actions", value: "actions", sortable: false },
      ],
    };
  },
  mounted() {
    this.loadStops();
  },
  methods: {
    displayRoutes(index) {
      this.selectedStop = index;
      this.dialog = true;
    },
    viewRoute(route) {
      this.$router.push({
        name: "view-route",
        params: { route_id: route.id},
      });
    },
    loadStops() {
      this.isLoading = true;
      this.stops = [];
      axios
        .get(`/stops/all`)
        .then((response) => {
          this.stops = response.data;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving stops",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          this.isLoading = false;
        });
    },
    createStop() {
      this.$router.push({
        name: "create-stop",
      });
    },
    viewStop(stop)
    {
      this.$router.push({
        name: "view-stop",
        params: {
          stop_id: stop.id,
        },
      });
    },
    editStop(stop)
    {
      this.$router.push({
        name: "edit-stop",
        params: {
          stop_id: stop.id,
        },
      });
    },
    deleteStop(stop, index) {
      if(stop.routes.length>0)
      {
        this.$swal
        .fire({
          title: "Can not delete",
          text: "You can not delete the stop ' " + stop.name + " ' because it already exists in a route",
          icon: "error",
        });
      }
      else
      {
        this.$swal
          .fire({
            title: "Delete stop",
            text: "Are you sure to delete the stop ' " + stop.name + " ' ? You won't be able to revert this!",
            icon: "error",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it!",
          })
          .then((result) => {
            if (result.isConfirmed) {
              this.deleteStopServer(stop.id, index);
            }
          });
      }
    },
    deleteStopServer(stop_id, index) {
      axios
        .delete(`/stops/${stop_id}`)
        .then((response) => {
          this.stops.splice(index, 1);
          this.$notify({
            title: "Success",
            text: "Stop deleted!",
            type: "success",
          });
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while deleting stops",
            type: "error",
          });
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          //this.isDeleting = false;
        });
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