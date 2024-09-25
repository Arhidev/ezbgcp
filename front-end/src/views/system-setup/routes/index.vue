<template>
  <div>
    <v-card>
      <v-card-title>
      <v-icon color="primary">
        mdi-road-variant
      </v-icon>
        <span class="pl-2">Routes</span>
        <v-spacer></v-spacer>
        <create-button @create="createRoute"></create-button>
        <activation-tool-tip model="routes"></activation-tool-tip>
      </v-card-title>
      <v-data-table
        item-key="name"
        :loading="isLoading"
        loading-text="Loading... Please wait"
        :headers="headers"
        :items="routes"
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
        <template v-slot:item.actions="{ item }">
          <v-icon small class="mr-2" @click="viewRoute(item)">
            mdi-eye
          </v-icon>
          <v-icon small class="mr-2" @click="editRoute(item)">
            mdi-pencil
          </v-icon>
          <v-icon small @click="deleteRoute(item, routes.indexOf(item))">
            mdi-delete
          </v-icon>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>

<script>
import ActivationToolTip from "@/components/ActivationToolTip";
import CreateButton from "@/components/CreateButton";
import auth from '@/services/AuthService'
export default {
  components: {
    ActivationToolTip,
    CreateButton,
  },
  data() {
    return {
      routes: [],
      isLoading: false,
      search: "",
      headers: [
        { text: "ID", value: "id", align: "start", filterable: false },
        { text: "Name", value: "name" },
        { text: "Stops", value: "stops_count" },
        { text: "Created", value: "created_at" },
        { text: "Actions", value: "actions", sortable: false },
      ],
    };
  },
  mounted() {
    this.loadRoutes();
  },
  methods: {
    loadRoutes() {
      this.isLoading = true;
      this.routes = [];
      axios
        .get(`/routes/all`)
        .then((response) => {
          this.routes = response.data;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving routes",
            type: 'error'
          });
          console.log(error);
          auth.checkError(error.response.data.message, this.$router, this.$swal);
        })
        .then(() => {
          this.isLoading = false;
        });
    },
    createRoute() {
      this.$swal
        .fire({
          title: "Enter route name",
          input: "text",
          inputPlaceholder: "New route",
          showCancelButton: true,
        })
        .then((result) => {
          if (result.isConfirmed) {
            const value = result.value.trim();
            this.$router.push({
              name: "create-route",
              params: { route_name: value ? value : "Untitled" },
            });
          }
        });
    },
    viewRoute(route) {
      this.$router.push({
        name: "view-route",
        params: { route_id: route.id},
      });
    },
    editRoute(route) {
      this.$swal
        .fire({
          title: "Enter route name",
          input: "text",
          inputValue: route.name,
          showCancelButton: true,
        })
        .then((result) => {
          if (result.isConfirmed) {
            const value = result.value.trim();
            // this.$router.push({
            //   name: "create-route",
            //   params: { route_name: value ? value : "Untitled" },
            // });
            this.$router.push({
              name: "edit-route",
              params: { route_id: route.id, new_route_name: value ? value : "Untitled" },
            });
          }
        });

    },
    deleteRoute(route, index) {
      this.$swal
        .fire({
          title: "Delete route",
          text: "Are you sure to delete the route ' " + route.name + " ' ? You won't be able to revert this!",
          icon: "error",
          showCancelButton: true,
          confirmButtonText: "Yes, delete it!",
        })
        .then((result) => {
          if (result.isConfirmed) {
            this.deleteRouteServer(route.id, index);
          }
        });
    },
    deleteRouteServer(route_id, index) {
      axios
        .delete(`/routes/${route_id}`)
        .then((response) => {
          this.routes.splice(index, 1);
          this.$notify({
            title: "Success",
            text: "Route deleted!",
            type: "success",
          });
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while deleting routes",
            type: 'error'
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
