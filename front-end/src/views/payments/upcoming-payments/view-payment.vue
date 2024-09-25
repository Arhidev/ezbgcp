<template>
  <div>
    <v-card>
      <v-card-title>
        <span>Upcoming Payment Details of {{ userName }}</span>
        <v-spacer></v-spacer>
        <v-btn depressed color="secondary" @click="$router.go(-1)" class="mx-1">
          Back
          <v-icon right dark> mdi-keyboard-return </v-icon>
        </v-btn>
      </v-card-title>
      <v-data-table
        item-key="id"
        :loading="isLoading"
        loading-text="Loading... Please wait"
        :headers="headers"
        :items="paymentDetail"
        :search="search"
      >
        <template v-slot:top>
          <v-text-field
            v-model="search"
            label="Search"
            class="mx-4"
          ></v-text-field>
        </template>
      </v-data-table>
    </v-card>

  </div>
</template>

<script>
export default {
  components: {},
  data() {
    return {
      paymentDetail: [],
      userName: "",
      user_id: null,
      isLoading: false,
      search: "",
      headers: [
        { text: "ID", value: "id", align: "start", filterable: false },
        { text: "Date", value: "payment_date" },
        { text: "Amount", value: "amount" },
        { text: "Route", value: "reservation.planned_trip.route.name" },
        { text: "From", value: "reservation.first_stop.name" },
        { text: "To", value: "reservation.last_stop.name" },
      ],
    };
  },
  mounted() {
    if (this.$route.params.user_id != null) {
      this.user_id = this.$route.params.user_id;
      this.fetchPayments()
    }
  },
  methods: {
    fetchPayments() {
      this.isLoading = true;
      this.paymentDetail = [];
      axios
        .get(`/users/payment-details`, {
          params: {
            user_id: this.user_id,
          },
        })
        .then((response) => {
          this.paymentDetail = response.data.payment_details;
          this.userName = response.data.user_name;
          console.log(response.data);
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving payment details",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
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