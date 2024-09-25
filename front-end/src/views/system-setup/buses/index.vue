<template>
  <div>
    <v-card>
      <v-card-title>
      <v-icon color="primary">
        mdi-bus-multiple
      </v-icon>
        <span class="pl-2">Buses</span>
        <v-spacer></v-spacer>
        <create-button @create="showBusDialog"></create-button>
        <activation-tool-tip model="buses"></activation-tool-tip>
      </v-card-title>
      <v-data-table
        item-key="id"
        :loading="isLoading"
        loading-text="Loading... Please wait"
        :headers="headers"
        :items="buses"
        :search="search"
      >
        <template v-slot:top>
          <v-text-field
            v-model="search"
            label="Search"
            class="mx-4"
          ></v-text-field>
        </template>
        <template v-slot:item.driver="{ item }">
          <v-chip :color="getDriverAssignmentColor(item.driver)" dark @click="assignDriver(item)">
            {{ getDriver(item.driver) }}
          </v-chip>
        </template>
        <template v-slot:item.created_at="{ item }">
          <small>{{ item.created_at | moment("LL") }}</small> -
          <small class="text-muted">{{ item.created_at | moment("LT") }}</small>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-icon v-if="item.driver" small class="mr-2" @click="unAssignDriver(item)">
            mdi-account-off
          </v-icon>
          <v-icon v-else small class="mr-2" @click="assignDriver(item)">
            mdi-account-tie-hat
          </v-icon>
          <v-icon small class="mr-2" @click="editBus(item)">
            mdi-pencil
          </v-icon>
          <v-icon small @click="deleteBus(item, buses.indexOf(item))">
            mdi-delete
          </v-icon>
        </template>
      </v-data-table>
    </v-card>
    <v-row justify="center">
      <v-dialog
        v-model="busDialog"
        persistent
        max-width="900px"
      >
        <v-form
          ref="form"
          v-model="valid"
          lazy-validation>
          <v-card>
            <v-card-title>
              <span class="text-h5">Bus data</span>
            </v-card-title>
            <v-card-text>
              <v-container>
                <v-row>
                  <v-col
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <v-text-field
                      v-model="license"
                      :rules="licenseRules"
                      label="License plate*"
                      hint="license plate of the bus"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <v-text-field
                      v-model="capacity"
                      :rules="capacityRules"
                      label="Capacity*"
                      hint="number of seats in the bus"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <v-text-field
                      v-model="price_factor"
                      :rules="priceFactorRules"
                      label="Pricing Factor*"
                      hint="pricing factor of the bus, e.g. 1.5 means 50% more than the normal price"
                      required
                    ></v-text-field>
                  </v-col>
                </v-row>
              </v-container>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn
                color="blue darken-1"
                text
                @click="busDialog = false"
              >
                Close
              </v-btn>
              <v-btn
                color="blue darken-1"
                text
                @click="createBus"
              >
                Save
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-form>
      </v-dialog>
    </v-row>
    <v-dialog v-if="selectedBus" v-model="driversDialog" max-width="390">
      <v-card>
        <v-card-title class="text-h5"> Select driver for '{{ selectedBus.license}}' </v-card-title>

        <v-card-text>
          <v-list dense>
            <v-subheader>Drivers</v-subheader>
            <v-list-item-group>
              <v-list-item
                v-for="(driver, i) in availableDrivers"
                :key="i"
              >
                <v-list-item-content>
                  <v-list-item-title v-text="driver.name" @click="assignDriverToBus(driver)"></v-list-item-title>
                </v-list-item-content>
              </v-list-item>
            </v-list-item-group>
          </v-list>
        </v-card-text>
        <v-container style="height: 400px">
          <v-row
            v-show="loadingDrivers"
            class="fill-height"
            align-content="center"
            justify="center"
          >
            <v-col class="text-subtitle-1 text-center" cols="12">
              Please wait ...
            </v-col>
            <v-col cols="6">
              <v-progress-linear
                :active="loadingDrivers"
                color="primary"
                indeterminate
                rounded
                height="6"
              ></v-progress-linear>
            </v-col>
          </v-row>
        </v-container>
        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn
            color="green darken-1"
            text
            @click="closeDriverDialog"
          >
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
import auth from '@/services/AuthService'
export default {
  components: {
    ActivationToolTip,
    CreateButton,
  },
  data() {
    return {
      buses: [],
      availableDrivers: [],
      isLoading: false,
      search: "",
      busDialog: false,
      driversDialog: false,
      loadingDrivers: false,
      valid: true,
      id: null,
      selectedBus: null,
      license: '',
      licenseRules: [
        v => !!v || 'License plate is required',
        v => (v && v.length <= 15) || 'License plate must be less than 15 characters',
      ],
      capacity: '',
      capacityRules: [
        v => /^[0-9]+$/.test(v) || 'Capacity is not valid',
      ],
      priceFactor: '1',
      priceFactorRules: // price factor must be greater than 0 and can be a decimal number
        [
            v => /^[0-9]+(\.[0-9]+)?$/.test(v) || 'Pricing factor is not valid',
            v => v > 0 || 'Pricing factor must be greater than 0',
        ],
      headers: [
        { text: "ID", value: "id", align: "start", filterable: false },
        { text: "License", value: "license" },
        { text: "Capacity", value: "capacity" },
        { text: "Pricing Factor", value: "price_factor" },
        { text: "Driver", value: "driver" },
        { text: "Created", value: "created_at" },
        { text: "Actions", value: "actions", sortable: false },
      ],
    };
  },
  mounted() {
    this.loadBuses();
  },
  methods: {
    loadBuses() {
      this.isLoading = true;
      this.buses = [];
      axios
        .get(`/buses/all`)
        .then((response) => {
          this.buses = response.data;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving buses",
            type: 'error'
          });
          console.log(error);
          auth.checkError(error.response.data.message, this.$router, this.$swal);
        })
        .then(() => {
          this.isLoading = false;
        });
    },
    validate () {
      return this.$refs.form.validate()
    },
    createBus() {
      if(this.validate())
      {
        this.isLoading = true;
        this.busDialog = false;
        axios
          .post(`/buses/create-edit`, {
            bus: {
              id: this.id,
              license: this.license,
              capacity: this.capacity,
              price_factor: this.price_factor,
            },
          })
          .then((response) => {
            this.loadBuses();
            this.$notify({
              title: "Success",
              text: this.id? "Bus updated!" : "Bus created!",
              type: 'success'
            });
            this.$swal("Success", "Bus " + (this.id? "updated" : "created") + " successfully", "success");
          })
          .catch((error) => {
            this.$notify({
              title: "Error",
              text: "Error while creating bus",
              type: 'error'
            });
            console.log(error);
            this.$swal("Error", error.response.data.message, "error");
          })
          .then(() => {
            this.isLoading = false;
          });
      }
    },
    showBusDialog() {
      this.license = '';
      this.capacity = '';
      this.price_factor = '1';
      this.id = null;
      this.busDialog = true;
    },
    editBus(bus) {
      this.id = bus.id;
      this.license = bus.license;
      this.capacity = bus.capacity;
        this.price_factor = bus.price_factor;
      this.busDialog = true;
    },
    deleteBus(bus, index) {
      this.$swal
        .fire({
          title: "Delete bus",
          text: "Are you sure to delete the bus ' " + bus.license + " ' ? You won't be able to revert this!",
          icon: "error",
          showCancelButton: true,
          confirmButtonText: "Yes, delete it!",
        })
        .then((result) => {
          if (result.isConfirmed) {
            this.deleteBusServer(bus.id, index);
          }
        });
    },
    deleteBusServer(bus_id, index) {
      axios
        .delete(`/buses/${bus_id}`)
        .then((response) => {
          this.buses.splice(index, 1);
          this.$notify({
            title: "Success",
            text: "Bus deleted!",
            type: "success",
          });
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while deleting buses",
            type: 'error'
          });
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          //this.isDeleting = false;
        });
    },
    getDriverAssignmentColor(driver) {
      if (driver) return "success";
      else return "error";
    },
    getDriver(driver) {
      if (driver) return driver.name;
      else return "none";
    },
    assignDriver(item) {
      this.selectedBus = item;
      this.driversDialog = true;
      this.loadAvailableDrivers()
    },
    loadAvailableDrivers() {
      this.loadingDrivers = true;
      this.availableDrivers = [];
      axios
        .get('/buses/available-drivers')
        .then((response) => {
          this.availableDrivers = response.data;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving drivers",
            type: 'error'
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          this.loadingDrivers = false;
        });
    },
    assignDriverToBus(driver) {
      this.loadingDrivers = true;
      axios
        .post(`/buses/assign-driver`, {
          bus_id: this.selectedBus.id,
          driver_id: driver.id,
        })
        .then((response) => {
          this.loadBuses();
          this.$notify({
            title: "Success",
            text: "Driver assigned to bus!",
            type: 'success'
          });
          this.$swal("Success", "Driver assigned to bus successfully", "success");
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while assigning driver to bus",
            type: 'error'
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          this.loadingDrivers = false;
          this.closeDriverDialog();
        });
    },
    unAssignDriver(item)
    {
      this.$swal
        .fire({
          title: "Un-assign driver from bus",
          text: "Are you sure to un-assign the driver ' " + item.driver.name + " ' from the bus '" + item.license + "' ? You won't be able to revert this!",
          icon: "error",
          showCancelButton: true,
          confirmButtonText: "Yes, delete it!",
        })
        .then((result) => {
          if (result.isConfirmed) {
            this.unAssignDriverFromBus(item);
          }
        });
    },
    unAssignDriverFromBus(item) {
      this.isLoading = true;
      axios
        .post(`/buses/unassign-driver`, {
          bus_id: item.id,
        })
        .then((response) => {
          this.loadBuses();
          this.$notify({
            title: "Success",
            text: "Driver unassigned from bus!",
            type: 'success'
          });
          this.$swal("Success", "Driver unassigned from bus successfully", "success");
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while un-assigning driver from bus",
            type: 'error'
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          this.isLoading = false;
        });
    },
    closeDriverDialog() {
      this.driversDialog = false;
      this.loadingDrivers = false;
      this.availableDrivers = [];
    },
  },
};
</script>
