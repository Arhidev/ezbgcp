<template>
  <div>
    <v-card>
      <v-card-title>
      <v-icon color="primary">
        mdi-ticket-percent
      </v-icon>
        <span class="pl-2">Coupons</span>
        <v-spacer></v-spacer>
        <create-button @create="showCouponDialog"></create-button>
        <activation-tool-tip model="coupons"></activation-tool-tip>
      </v-card-title>
      <v-data-table
        item-key="id"
        :loading="isLoading"
        loading-text="Loading... Please wait"
        :headers="headers"
        :items="coupons"
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
        <template v-slot:item.discount="{ item }">
            {{ item.discount }}%
        </template>
        <template v-slot:item.limit="{ item }">
            {{ item.limit == 0 ? 'Unlimited' : item.limit }}
        </template>
        <template v-slot:item.max_amount="{ item }">
            {{ item.max_amount == 0 ? 'Unlimited' : item.max_amount }}
        </template>
        <template v-slot:item.expiration_date="{ item }">
          <small>{{ item.expiration_date | moment("LL") }}</small>
        </template>
        <template v-slot:item.created_at="{ item }">
          <small>{{ item.created_at | moment("LL") }}</small> -
          <small class="text-muted">{{ item.created_at | moment("LT") }}</small>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-icon
            small
            class="mr-2"
            @click="editCoupon(item)"
          >
            mdi-pencil
          </v-icon>
          <v-icon
          class="mr-2"
            small
            @click="deleteCoupon(item, coupons.indexOf(item))"
          >
            mdi-delete
          </v-icon>
          <v-icon
            small
            @click="sendNotification(item)"
            >
            mdi-bell
            </v-icon>
        </template>
      </v-data-table>
    </v-card>
    <v-row justify="center">
      <v-dialog
        v-model="couponDialog"
        persistent
        max-width="900px"
      >
        <v-form
          ref="form"
          v-model="valid"
          lazy-validation>
          <v-card>
            <v-card-title>
              <span class="text-h5">Coupon data</span>
            </v-card-title>
            <v-card-text>
              <v-container>
                <v-row>
                  <v-col
                    cols="12"
                    sm="6"
                    md="4"
                  >
                    <v-text-field
                      v-model="code"
                      :rules="codeRules"
                      label="Code*"
                      hint="code of the coupon"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col
                    cols="12"
                    sm="6"
                    md="4"
                  >
                    <v-text-field
                      v-model="discount"
                      :rules="discountRules"
                      label="Discount (%)*"
                      hint="Discount of the coupon"
                      required
                    ></v-text-field>
                  </v-col>
                  <v-col
                    cols="12"
                    sm="6"
                    md="4"
                  >
                    <v-text-field
                      v-model="limit"
                      :rules="limitRules"
                      label="Limit*"
                      hint="Limit of coupon usage per customer. 0 means unlimited"
                      required
                    ></v-text-field>
                  </v-col>
                </v-row>
                <v-row>
                  <v-col
                    cols="12"
                    sm="6"
                    md="4"
                  >
                    <v-text-field
                      v-model="max_amount"
                      :rules="max_amountRules"
                      label="Max amount*"
                      hint="Max amount of saving of the coupon. 0 means unlimited"
                      required
                    ></v-text-field>
                  </v-col>
                    <v-col
                        cols="12"
                        sm="6"
                        md="4"
                    >
                        <v-menu
                            ref="menu"
                            :close-on-content-click="false"
                            :nudge-right="40"
                            transition="scale-transition"
                            offset-y
                            min-width="290px"
                        >
                            <template v-slot:activator="{ on }">
                                <v-text-field
                                    v-model="expiration_date"
                                    :rules="expiration_dateRules"
                                    label="Expiration date*"
                                    hint="Expiration date of the coupon"
                                    readonly
                                    v-on="on"
                                ></v-text-field>
                            </template>
                            <v-date-picker
                                v-model="expiration_date"
                                no-title
                                scrollable
                            ></v-date-picker>
                        </v-menu>
                    </v-col>
                </v-row>
              </v-container>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn
                color="blue darken-1"
                text
                @click="couponDialog = false"
              >
                Close
              </v-btn>
              <v-btn
                color="blue darken-1"
                text
                @click="createCoupon"
              >
                Save
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-form>
      </v-dialog>
    </v-row>
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
      coupons: [],
      isLoading: false,
      search: "",
      couponDialog: false,
      valid: true,
      id: null,
      code: '',
      codeRules: [
        v => !!v || 'Code is required',
        v => (v && v.length <= 15) || 'Code must be less than 15 characters',
      ],
      limit: '1',
      limitRules: [
        v => /^[0-9]+$/.test(v) || 'Limit is not valid',
      ],
      discount: '10',
      discountRules: [
        v => /^[0-9]+$/.test(v) || 'Discount is not valid',
      ],
      max_amount: '1',
      max_amountRules:
      [
        v => /^[0-9]+$/.test(v) || 'Max amount is not valid',
      ],
      expiration_date: '',
      expiration_dateRules: [
        v => !!v || 'Expiration date is required',
      ],
      headers: [
        { text: "ID", value: "id", align: "start", filterable: false },
        { text: "Code", value: "code" },
        { text: "Discount", value: "discount"},
        { text: "Usage limit", value: "limit" },
        { text: "Max Saving", value: "max_amount" },
        { text: "Expiration", value: "expiration_date" },
        { text: "Created", value: "created_at" },
        { text: "Actions", value: "actions", sortable: false },
      ],
    };
  },
  mounted() {
    this.loadCoupones();
  },
  methods: {
    loadCoupones() {
      this.isLoading = true;
      this.coupons = [];
      axios
        .get(`/coupons/all`)
        .then((response) => {
          this.coupons = response.data;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving coupons",
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
    createCoupon() {
      if(this.validate())
      {
        this.isLoading = true;
        this.couponDialog = false;
        axios
          .post(`/coupons/create-edit`, {
            coupon: {
              id: this.id,
                code: this.code,
                discount: this.discount,
                limit: this.limit,
                max_amount: this.max_amount,
                expiration_date: this.expiration_date,
            },
          })
          .then((response) => {
            this.loadCoupones();
            this.$notify({
              title: "Success",
              text: this.id? "Coupon updated!" : "Coupon created!",
              type: 'success'
            });
            this.$swal("Success", "Coupon " + (this.id? "updated" : "created") + " successfully", "success");
          })
          .catch((error) => {
            this.$notify({
              title: "Error",
              text: "Error while creating coupon",
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
    showCouponDialog() {
      this.code = '';
      this.limit = '1';
      this.max_amount = '1';
      this.discount = '10';
      this.expiration_date = new Date().toISOString().substr(0, 10);
      this.id = null;
      this.couponDialog = true;
    },
    editCoupon(coupon) {
      this.id = coupon.id;
      this.code = coupon.code;
      this.discount = coupon.discount;
      this.limit = coupon.limit;
      this.max_amount = coupon.max_amount;
      this.expiration_date = coupon.expiration_date;
      this.couponDialog = true;
    },
    deleteCoupon(coupon, index) {
      this.$swal
        .fire({
          title: "Delete coupon",
          text: "Are you sure to delete the coupon ' " + coupon.code + " ' ? You won't be able to revert this!",
          icon: "error",
          showCancelButton: true,
          confirmButtonText: "Yes, delete it!",
        })
        .then((result) => {
          if (result.isConfirmed) {
            this.deleteCouponServer(coupon.id, index);
          }
        });
    },
    deleteCouponServer(coupon_id, index) {
      axios
        .delete(`/coupons/${coupon_id}`)
        .then((response) => {
          this.coupons.splice(index, 1);
          this.$notify({
            title: "Success",
            text: "Coupon deleted!",
            type: "success",
          });
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while deleting coupons",
            type: 'error'
          });
          this.$swal("Error", error.response.data.message, "error");
        })
        .then(() => {
          //this.isDeleting = false;
        });
    },
    sendNotification(coupon){
      //format date
      let expiration_date = new Date(coupon.expiration_date).toLocaleDateString( "en-US", { year: 'numeric', month: 'long', day: 'numeric' });
      let notification_message = `Use coupon code ${coupon.code} and get a ${coupon.discount}% discount on your next trip. Coupon expires on ${expiration_date}.`;
      if(coupon.limit > 0)
        notification_message += ` Hurry up! The coupon is limited to ${coupon.limit} uses.`;
        //show swal with textarea
        this.$swal({
            input: 'textarea',
            inputPlaceholder: 'Please enter the notification message here',
            inputAttributes: {
            'aria-label': 'Please enter the notification message'
            },
            title: "Send notification about coupon to all customers",
            html: "Please enter the notification message",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "Send notification",
            //form input based on coupon data
            inputValue: notification_message
        }).then((result) => {
            if (result.isConfirmed) {
            axios
                .post(`/coupons/notify`, {
                id: coupon.id,
                message: result.value,
                })
                .then((response) => {
                this.$notify({
                    title: "Success",
                    text: "Notification sent successfully",
                    type: "success",
                });
                if(response.status == 201)
                    this.$swal("Info", response.data.message, "info");
                })
                .catch((error) => {
                this.$notify({
                    title: "Error",
                    text: "Error while sending notification",
                    type: "error",
                });
                console.log(error);
                this.$swal("Error", error.response.data.message, "error");
                });
            }
        });
    },
  },
};
</script>
