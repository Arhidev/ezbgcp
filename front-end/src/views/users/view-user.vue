<template>
  <div v-if="user">
    <v-card>
      <v-card-title>
      <v-icon color="primary">
        mdi-account
      </v-icon>
        <span class="pl-2">Account Information</span>
        <v-spacer></v-spacer>
        <v-btn depressed color="secondary" @click="$router.go(-1)" class="mx-1">
          Back
          <v-icon right dark> mdi-keyboard-return </v-icon>
        </v-btn>
        <v-btn
          depressed
          color="primary"
          @click="editUser"
          class="mx-1"
        >
          Edit
          <v-icon right dark> mdi-pencil </v-icon>
        </v-btn>
      </v-card-title>
      <v-card-text v-if="user" class="mt-5">
        <!-- display user info -->
        <v-row>
          <v-col cols="12" md="3" v-if="user.avatar">
            <avatar-image-component :edit="false" :avatarUrl="user.avatar" :user="user.id"></avatar-image-component>
          </v-col>
          <v-row class="mx-2">
            <v-col cols="12" md="6">
              <p class = "font-weight-bold">User Name</p>
              <div class="mt-4">
                <p>{{ user.name }}</p>
              </div>
            </v-col>
            <v-col cols="12" md="6">
              <p class = "font-weight-bold">Email</p>
              <div class="mt-4">
                <p>{{ user.email }}</p>
              </div>
            </v-col>
            <v-col cols="12" md="6">
              <p class = "font-weight-bold">Registered</p>
              <div class="mt-4">
                <p>{{ user.created_at | moment("LL") }} - {{ user.created_at | moment("LT") }}</p>
              </div>
            </v-col>
            <v-col cols="12" md="6">
              <p class = "font-weight-bold">Wallet</p>
              <div class="mt-4">
                <p>{{ user.wallet }}</p>
              </div>
            </v-col>
          </v-row>
        </v-row>
        <v-row>
          <v-col cols="12" md="3">
            <p class = "font-weight-bold">Role</p>
            <div class="mt-4">
              <v-chip
                color="primary"
                dark
              >
                {{ getRoleValue(user.role) }}
                <v-icon class="ml-2">
                  {{ getIconOfRole(user.role) }}
                </v-icon>
              </v-chip>
            </div>
          </v-col>
          <v-col cols="12" md="9">
              <v-row>
                <v-col cols="12" md="6">
                  <p class = "font-weight-bold">Status</p>
                  <div class="mt-4">
                    <v-chip
                      :color="getStatusColor(user.status_id)"
                      dark
                    >
                      {{ getStatusValue(user.status_id) }}
                      <v-icon class="ml-2">
                        {{ getIconOfStatus(user.status_id) }}
                      </v-icon>
                    </v-chip>
                  </div>
                </v-col>
                <v-col cols="12" md="6">
                  <p class = "font-weight-bold">Preferred Payment Method</p>
                  <div class="mt-4">
                  <v-chip
                    :color="getRedemptionPreferenceColor(user.redemption_preference)"
                    dark
                  >
                    {{ getRedemptionPreferenceValue(user.redemption_preference) }}
                    <v-icon class="ml-2">
                      {{ getIconOfRedemptionPreference(user.redemption_preference) }}
                    </v-icon>
                  </v-chip>
                  </div>
                </v-col>

              </v-row>
          </v-col>
        </v-row>

      </v-card-text>
    </v-card>
    
    <!-- display driver documents -->
    <v-card v-if="user.role == 2 && user.driver_information" class="mt-5">
      <v-card-title>
        <v-icon color="primary">
          mdi-information
        </v-icon>
        <span class="pl-2">Personal Information</span>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="4">
            <p class = "font-weight-bold">First Name</p>
            <div class="mt-4">
              <p>{{ user.driver_information.first_name }}</p>
            </div>
          </v-col>
          <v-col cols="12" md="4">
            <p class = "font-weight-bold">Last Name</p>
            <div class="mt-4">
              <p>{{ user.driver_information.last_name }}</p>
            </div>
          </v-col>
          <!-- phone_number -->
          <v-col cols="12" md="4">
            <p class = "font-weight-bold">Phone Number</p>
            <div class="mt-4">
              <p>{{ user.driver_information.phone_number }}</p>
            </div>
          </v-col>
          <!-- address -->
          <v-col cols="12" md="4">
            <p class = "font-weight-bold">Address</p>
            <div class="mt-4">
              <p>{{ user.driver_information.address }}</p>
            </div>
          </v-col>
          <!-- email -->
          <v-col cols="12" md="4">
            <p class = "font-weight-bold">Communication Email</p>
            <div class="mt-4">
              <p>{{ user.driver_information.email }}</p>
            </div>
          </v-col>
          <!-- license -->
          <v-col cols="12" md="4">
            <p class = "font-weight-bold">License</p>
            <div class="mt-4">
              <p>{{ user.driver_information.license_number }}</p>
            </div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Driver documents -->
    <v-card v-if="user.role == 2 && user.driver_information != null && user.driver_information.documents != null && user.driver_information.documents.length > 0" class="mt-5">
      <v-card-title>
        <v-icon color="primary">
          mdi-file-document
        </v-icon>
        <span class="pl-2">Driver Documents</span>
      </v-card-title>
      <v-card-text v-for="(document, index) in user.driver_information.documents" :key="document.id">
        <v-row>
          <!-- Document Image -->
          <v-col cols="12" md="3">
            <div class="driver-document">
              <v-avatar rounded size="120" @click="viewDocumentImage(document)">
                <v-img :src="getDocumentImage(document)" alt="Document Image"></v-img>
              </v-avatar>
            </div>
          </v-col>
          <v-col cols="12" md="3">
            <p class = "font-weight-bold">Document Name</p>
            <div class="mt-4">
              <p>{{ document.document_name }}</p>
            </div>
          </v-col>
          <v-col cols="12" md="3">
            <p class = "font-weight-bold">Document Number</p>
            <div class="mt-4">
              <p>{{ document.document_number }}</p>
            </div>
          </v-col>
          <v-col cols="12" md="3">
            <p class = "font-weight-bold">Expiry Date</p>
            <div class="mt-4">
              <p>{{ document.expiry_date | moment("LL") }}</p>
            </div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Approve or Reject Driver -->
    <v-card v-if="user.role == 2 && user.status_id == 4" class="mt-5">
      <v-card-title>
        <v-icon color="primary">
          mdi-account-check
        </v-icon>
        <span class="pl-2">Approve or Reject Driver</span>
      </v-card-title>
      <v-card-text>
        <div class="d-flex justify-space-between mb-6 bg-surface-variant">
            <v-btn
              depressed
              color="success"
              @click="approveDriver"
              class="mx-1"
            >
              Approve
              <v-icon right dark> mdi-check </v-icon>
            </v-btn>

            <v-btn
              depressed
              color="error"
              @click="rejectDriver"
              class="mx-1"
            >
              Reject
              <v-icon right dark> mdi-close </v-icon>
            </v-btn>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>

import AvatarImageComponent from '../../components/AvatarImageComponent.vue'
import {Keys} from '/src/config.js'

export default {
  components: {
    AvatarImageComponent,
    Keys
  },

  data() {
    return {
      user: null,
      user_id: null,
      loading: false,
    };
  },
  mounted() {
    if (this.$route.params.user_id != null) {
      this.user_id = this.$route.params.user_id;
      this.fetchUser();
    }
  },
  methods: {
    fetchUser() {
      this.loading = true;
      axios
        .get(`/users/user/${this.user_id}`)
        .then((response) => {
          this.loading = false;
          this.user = response.data;
          console.log(this.user)
        })
        .catch((error) => {
          this.loading = false;
          this.$notify({
            title: "Error",
            text: "Error fetching user data",
            type: "error",
          });
          console.log(error);
          //this.$router.go(-1);
        });
    },
    editUser() {
      this.$router.push({
        name: "edit-user",
        params: { user_id: this.user.id },
      });
    },
    userStatus(status) {
      if (status == 1) {
        return "Active";
      } else if (status == 2) {
        return "Pending";
      } else if (status == 3) {
        return "Suspended";
      } else if (status == 4) {
        return "Under Review";
      } else {
        return "Unknown";
      }
    },
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
    getStatusColor(status) {
      if (status == 1) {
        return "success";
      } else if (status == 2) {
        return "warning";
      } else if (status == 3) {
        return "error";
      } else if (status == 4) {
        return "info";
      }
    },
    getStatusValue(status) {
      if (status == 1) {
        return "Active";
      } else if (status == 2) {
        return "Pending";
      } else if (status == 3) {
        return "Suspended";
      } else if (status == 4) {
        return "Under Review";
      } else {
        return "Unknown";
      }
    },
    getIconOfStatus(status) {
      if (status == 1) {
        return "mdi-check-circle";
      } else if (status == 2) {
        return "mdi-alert-circle";
      } else if (status == 3) {
        return "mdi-close-circle";
      } else if (status == 4) {
        return "mdi-information-outline";
      } else {
        return "mdi-help-circle";
      }
    },
    getRoleValue(role) {
      if (role == 0) {
        return "Admin";
      } else if (role == 1) {
        return "Customer";
      } else if (role == 2) {
        return "Driver";
      } else {
        return "Unknown";
      }
    },
    getIconOfRole(role) {
      if (role == 0) {
        return "mdi-account-lock";
      } else if (role == 1) {
        return "mdi-account";
      } else if (role == 2) {
        return "mdi-account-tie-hat";
      } else {
        return "mdi-account-question";
      }
    },
    getDocumentImage(document)
    {
      return Keys.VUE_APP_API_URL + document.remote_file_path;
    },
    viewDocumentImage(document)
    {
      window.open(Keys.VUE_APP_API_URL + document.remote_file_path, '_blank');
    },
    rejectDriver() {
      this.$swal({
        input: 'textarea',
        inputPlaceholder: 'Why are you rejecting this driver?',
        inputAttributes: {
          'aria-label': 'Why are you rejecting this driver?'
        },
        title: "Reject Driver",
        html: "Are you sure you want to reject this driver?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, reject it!",
      }).then((result) => {
        if (result.isConfirmed) {
          this.takeActionOnDriverServer(this.user.id, result.value, 2);
        }
      });
    },
    approveDriver() {
      this.$swal({
        title: "Approve Driver",
        html: "Are you sure you want to approve this driver?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, approve it!",
      }).then((result) => {
        if (result.isConfirmed) {
          this.takeActionOnDriverServer(this.user.id, "Approved", 1);
        }
      });
    },
    takeActionOnDriverServer(user_id, reason, action)
    {
      this.loading = true;
      axios
        .post('/drivers/take-action', {
          driver_id: user_id,
          reason: reason,
          action: action
        })
        .then((response) => {
          this.loading = false;
          this.$notify({
            title: "Success",
            text: "Driver status updated successfully",
            type: "success",
          });
          this.$router.go(-1);
        })
        .catch((error) => {
          this.loading = false;
          this.$notify({
            title: "Error",
            text: "Error taking action",
            type: "error",
          });
          console.log(error);
        });
    }
  },

};
</script>

<style scoped>
.driver-document {
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
}
</style>