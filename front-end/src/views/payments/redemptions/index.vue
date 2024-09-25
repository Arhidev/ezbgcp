<template>
  <div>
    <v-card>
      <v-card-title>
      <v-icon color="primary">
        mdi-cash-multiple
      </v-icon>
        <span class="pl-2">Redemptions</span>
      </v-card-title>
      <v-data-table
        item-key="id"
        :loading="isLoading"
        loading-text="Loading... Please wait"
        :headers="headers"
        :items="redemptions"
        :search="search"
      >
        <template v-slot:top>
          <v-text-field
            v-model="search"
            label="Search"
            class="mx-4"
          ></v-text-field>
        </template>

        <template v-slot:item.date="{ item }">
          <small>{{ item.date | moment("LL") }}</small>
        </template>

        <template v-slot:item.user_id="{ item }">
          <a @click.stop="displayUser(item.user_id)">{{
            item.user_name
          }}</a>
        </template>

        <template v-slot:item.redemption_preference="{ item }">
          <v-chip
            :color="getRedemptionPreferenceColor(item.redemption_preference)"
            @click="displayRedemptionPreference(redemptions.indexOf(item))"
            dark
          >
            {{ getRedemptionPreferenceValue(item.redemption_preference) }}
            <v-icon class="ml-2">
              {{ getIconOfRedemptionPreference(item.redemption_preference) }}
            </v-icon>
          </v-chip>
        </template>

      </v-data-table>
    </v-card>

  </div>
</template>

<script>
import auth from '@/services/AuthService'
export default {
  components: {},
  data() {
    return {
      redemptions: [],
      isLoading: false,
      search: "",
      headers: [
        { text: "ID", value: "id", align: "start", filterable: false },
        { text: "User Name", value: "user_id" },
        { text: "Redemption", value: "redemption_preference" },
        { text: "Amount", value: "redemption_amount" },
        { text: "Date", value: "date" },
      ],
    };
  },
  mounted() {
    this.fetchRedemptions()
  },
  methods: {
    getIconOfRedemptionPreference(redemption_preference)
    {
      if(redemption_preference==2)
      {
        return "mdi-bank";
      }
      else if(redemption_preference==3)
      {
        return "mdi-credit-card";
      }
      else if(redemption_preference==4)
      {
        return "mdi-credit-card-multiple";
      }
      else
      {
        return "mdi-cash";
      }
    },
    getRedemptionPreferenceColor(redemption_preference)
    {
      if(redemption_preference==2)
      {
        return "primary";
      }
      else if(redemption_preference==3)
      {
        return "info";
      }
      else if(redemption_preference==4)
      {
        return "secondary";
      }
      else
      {
        return "success";
      }
    },
    getRedemptionPreferenceValue(redemption_preference)
    {
      if(redemption_preference==2)
      {
        return "Bank";
      }
      else if(redemption_preference==3)
      {
        return "PayPal";
      }
      else if(redemption_preference==4)
      {
        return "Mobile Money";
      }
      else
      {
        return "Cash";
      }
    },
    displayRedemptionPreference(index) {
      this.$swal({
        title: this.showRedemptionTitle(index),
        html: this.showRedemptionDetails(index),
        icon: "info",
      });
    },
    showRedemptionTitle(index)
    {
      let redemption_preference = this.redemptions[index].redemption_preference;
      if(redemption_preference==2)
      {
        return '<h2>Bank Account</h2>';
      }
      else if(redemption_preference==3)
      {
        return '<h2>PayPal</h2>';
      }
      else if(redemption_preference==4)
      {
        return '<h2>Mobile Money</h2>';
      }
      else
      {
        return '<h2>Cash</h2>';
      }
    },
    showRedemptionDetails(index)
    {
      let redemption_preference = this.redemptions[index].redemption_preference;
      if(redemption_preference==2)
      {
        return '<ul><li><b>Bank Name:</b> ' + this.redemptions[index].redemption_details.bank_name + '</li>' +
        '<li><b>Account Number:</b> ' + this.redemptions[index].redemption_details.account_number + '</li>' +
        '<li><b>Beneficiary Name:</b> ' + this.redemptions[index].redemption_details.beneficiary_name + '</li>' +
        '<li><b>Beneficiary Address:</b> ' + this.redemptions[index].redemption_details.beneficiary_address + '</li>' +
        (this.redemptions[index].redemption_details.iban != null ? '<li><b>IBAN:</b> ' + this.redemptions[index].redemption_details.iban + '</li>' : '') +
        (this.redemptions[index].redemption_details.swift != null ? '<li><b>Swift:</b> ' + this.redemptions[index].redemption_details.swift + '</li>' : '') +
        (this.redemptions[index].redemption_details.routing_number != null ? '<li><b>Routing Number:</b> ' + this.redemptions[index].redemption_details.routing_number + '</li>' : '') +
        (this.redemptions[index].redemption_details.bic != null ? '<li><b>Bank Identification Code:</b> ' + this.redemptions[index].redemption_details.bic + '</li>' : '') +
        '</ul>';
      }
      else if(redemption_preference==3)
      {
        return "<b>PayPal:</b> " + this.redemptions[index].redemption_details.email;
      }
      else if(redemption_preference==4)
      {
        return '<ul><li><b>Phone Number:</b> ' + this.redemptions[index].redemption_details.phone_number + '</li>' +
        '<li><b>Network:</b> ' + this.redemptions[index].redemption_details.network + '</li>' +
        '<li><b>Name:</b> ' + this.redemptions[index].redemption_details.name + '</li>' +
        '</ul>';
      }
      else
      {
        return "";
      }
    },
    displayUser(user_id) {
      this.$router.push({ name: "view-user", params: { user_id: user_id } });
    },
    fetchRedemptions() {
      this.isLoading = true;
      this.redemptions = [];
      axios
        .get('/users/redemptions')
        .then((response) => {
          this.redemptions = response.data;
          console.log(response.data);
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving payment details",
            type: "error",
          });
          console.log(error);
          auth.checkError(error.response.data.message, this.$router, this.$swal);
        })
        .then(() => {
          this.isLoading = false;
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
