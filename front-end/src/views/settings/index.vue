<template>
  <div>
    <v-card>
      <v-card-title>
        <v-icon color="primary"> mdi-cog </v-icon>
        <span class="pl-2">Settings</span>
      </v-card-title>
      <v-card-text>
        <vue-element-loading :active="isLoading" />
        <v-form ref="form" v-if="settings" v-model="valid" lazy-validation class="my-2">
          <v-row>
            <v-col cols="12" md="4" class="my-2">
              <v-autocomplete
                v-model="selectedCurrency"
                :items="currencies"
                dense
                outlined
                item-text="name"
                item-value="id"
                label="Currency"
                :filter="customFilter"
                persistent-hint
                return-object
                required
                :rules="requiredRules"
                :hint="
                  selectedCurrency != null
                    ? selectedCurrency.code + ' (' + selectedCurrency.symbol + ')'
                    : ''
                "
                @change="currencySelected"
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="4" class="my-2">
                <v-text-field
                  v-model="settings.rate_per_km"
                  type="number"
                  outlined
                  dense
                  label="Rate per km"
                  placeholder="Enter rate per km"
                  persistent-hint
                  required
                  :rules="numberRules"
                ></v-text-field>
            </v-col>

            <v-col cols="12" md="4" class="my-2">
                <v-text-field
                  v-model="settings.commission"
                  type="number"
                  outlined
                  dense
                  label="Commission (%)"
                  placeholder="Enter commission percentage"
                  persistent-hint
                  required
                  :rules="percentRules"
                ></v-text-field>
            </v-col>
            <v-col cols="12" md="4" class="my-2">
                <v-text-field
                  v-model="settings.publish_trips_future_days"
                  type="number"
                  outlined
                  dense
                  label="Publish trips in future (days)"
                  placeholder="Enter no. of days in future to publish trips"
                  persistent-hint
                  required
                  :rules="numberRules"
                ></v-text-field>
            </v-col>
            <!-- max_distance_to_stop -->
            <v-col cols="12" md="4" class="my-2">
                <v-text-field
                  v-model="settings.max_distance_to_stop"
                  type="number"
                  outlined
                  dense
                  label="Max distance to stop (km)"
                  placeholder="Enter max distance to stop to be included in search results"
                  persistent-hint
                  required
                  :rules="numberRules"
                ></v-text-field>
            </v-col>
            <!-- distance_to_stop_to_mark_arrived -->
            <v-col cols="12" md="4" class="my-2">
                <v-text-field
                  v-model="settings.distance_to_stop_to_mark_arrived"
                  type="number"
                  outlined
                  dense
                  label="Distance to stop to mark arrived by a driver (meter)"
                  placeholder="Enter distance to stop to mark arrived by driver"
                  persistent-hint
                  required
                  :rules="numberRules"
                ></v-text-field>
            </v-col>
          </v-row>
          <div class="mt-8">
            <label class="text--secondary font-weight-bold">Ads settings</label>
          </div>
          <v-row>
            <v-col cols="12" md="6">
              <v-checkbox
                v-model="settings.allow_ads_in_driver_app"
                label="Allow ads in driver app"
                persistent-hint
              ></v-checkbox>
              <v-checkbox
                v-model="settings.allow_ads_in_customer_app"
                label="Allow ads in customer app"
                persistent-hint
              ></v-checkbox>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>

          <v-btn :disabled="!valid" color="primary" @click="saveSettings">
            Save
            <v-icon right dark> mdi-content-save </v-icon>
          </v-btn>
        </v-card-actions>
    </v-card>
  </div>
</template>

<script>

import VueElementLoading from "vue-element-loading";

export default {
  components: {
    VueElementLoading,
  },
  data() {
    return {
      currencies: [],
      isLoading: false,
      settings: null,
      valid: true,
      requiredRules: [(v) => !!v || "Required."],
      numberRules: [(v) => /^(0*[1-9][0-9]*(\.[0-9]+)?|0+\.[0-9]*[1-9][0-9]*)$/.test(v) || "Must be greater than 0"],
      percentRules: [(v) => /^100$|^[0-9]{1,2}$|^[0-9]{1,2}\,[0-9]{1,3}$/.test(v) || "Must be from 0 to 100"],
      selectedCurrency: null,
    };
  },
  mounted() {
    this.loadSettings();
  },
  methods: {
    customFilter (item, queryText, itemText) {
      const textOne = item.name.toLowerCase()
      const textTwo = item.code.toLowerCase()
      const searchText = queryText.toLowerCase()

      return textOne.indexOf(searchText) > -1 ||
        textTwo.indexOf(searchText) > -1
    },
    //API Calls
    saveSettings() {
      this.submiting = true;
      axios
        .post("/settings/update", this.settings)
        .then((response) => {
          this.submiting = false;
          this.$notify({
            title: "Success",
            text: "Settings updated!",
            type: "success",
          });
        })
        .catch((error) => {
          this.submiting = false;
          this.$notify({
            title: "Error",
            text: "Error updating settings",
            type: "error",
          });
          console.log(error);
        });
    },
    loadSettings() {
      this.isLoading = true;
      this.settings = [];
      axios
        .get(`/settings/all`)
        .then((response) => {
          this.settings = response.data;
          this.loadCurrencies();
          this.selectedCurrency = this.settings.currency;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving settings",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          this.isLoading = false;
        });
    },
    loadCurrencies() {
      this.isLoading = true;
      this.currencies = [];
      axios
        .get(`/currencies/all`)
        .then((response) => {
          this.currencies = response.data;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving currencies",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          this.isLoading = false;
        });
    },
    validate() {
      this.valid = false;
      let v = this.$refs.form.validate();
      if (v) {
        this.valid = true;
        return true;
      }
      return false;
    },
    currencySelected(r) {
      this.selectedCurrency = r;
      this.settings.currency_id = this.selectedCurrency.id;
    },
  },
};
</script>
