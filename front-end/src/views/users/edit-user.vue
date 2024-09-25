<template>
<div>
  <vue-element-loading :active="isLoading" :is-full-screen="true" />
  <v-card :loading="isLoading" id="account-setting-card">
    <v-card-title>
      <span v-if="user != null" class="me-3">{{ user.name }}</span>
      <v-spacer></v-spacer>
      <v-btn depressed color="secondary" @click="$router.go(-1)" class="mx-1">
        Back
        <v-icon right dark> mdi-keyboard-return </v-icon>
      </v-btn>
    </v-card-title>
    <!-- tabs -->
    <v-tabs v-model="tab" show-arrows>
      <v-tab v-for="tab in tabs" :key="tab.icon">
        <v-icon size="20" class="me-3">
          {{ tab.icon }}
        </v-icon>
        <span>{{ tab.title }}</span>
      </v-tab>
    </v-tabs>
    <!-- tabs item -->
    <v-tabs-items v-model="tab">
      <v-tab-item>
        <account-settings-account
          :account-data="user"
          @save-user-info="saveUser"
        ></account-settings-account>
      </v-tab-item>

      <v-tab-item>
        <account-settings-security @save-user-password="savePassword"></account-settings-security>
      </v-tab-item>

    </v-tabs-items>
  </v-card>
</div>
</template>

<script>
import {
  mdiAccountOutline,
  mdiLockOpenOutline,
} from "@mdi/js";
import { ref } from '@vue/composition-api'

import AccountSettingsAccount from "./user-settings/AccountSettingsAccount.vue";
import AccountSettingsSecurity from "./user-settings/AccountSettingsSecurity.vue";

import VueElementLoading from "vue-element-loading";
import { adminProfileStore } from "@/utils/helpers";
export default {
  components: {
    AccountSettingsAccount,
    AccountSettingsSecurity,
    VueElementLoading,
  },
  setup() {
    return { adminProfileStore }
  },
  data() {
    return {
      isLoading: false,
      user_id: null,
      user: null,
      edit: false,
      tabs: [
        { title: "Account", icon: mdiAccountOutline },
        { title: "Change password", icon: mdiLockOpenOutline },
      ],
      tab: ref(""),
    };
  },
  watch: {
    $route(to, from) {
      console.log(to);
      this.onRouteChanged();
    },
  },
  mounted() {
    this.onRouteChanged();
  },
  methods: {
    onRouteChanged()
    {
      if (this.$route.params.user_id != null) {
        this.user_id = this.$route.params.user_id;
        this.fetchUser();
      }
    },
    //API Calls
    fetchUser() {
      this.isLoading = true;
      axios
        .get(`/users/user/${this.user_id}`)
        .then((response) => {
          this.isLoading = false;
          this.user = response.data;
        })
        .catch((error) => {
          this.isLoading = false;
          this.$notify({
            title: "Error",
            text: "Error fetching user data",
            type: "error",
          });
          console.log(error);
          this.$router.go(-1);
        });
    },
    saveUser() {
      this.isLoading = true;
      axios
        .post("/users/edit", {
          user: this.user,
        })
        .then((response) => {
          this.adminProfileStore.name = this.user.name;
          this.isLoading = false;
          this.$notify({
            title: "Success",
            text: "User updated!",
            type: "success",
          });
          this.$router.go(-1);
        })
        .catch((error) => {
          this.isLoading = false;
          this.$notify({
            title: "Error",
            text: "Error updating user",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        });
    },
    savePassword(password)
    {
      this.isLoading = true;
      axios
        .post("/users/update-password", {
          user_id: this.user.id,
          password: password,
        })
        .then((response) => {
          this.isLoading = false;
          this.$notify({
            title: "Success",
            text: "User updated!",
            type: "success",
          });
          this.$router.go(-1);
        })
        .catch((error) => {
          this.isLoading = false;
          this.$notify({
            title: "Error",
            text: "Error updating user",
            type: "error",
          });
          console.log(error);
          this.$swal("Error", error.response.data.message, "error");
        });
    },
  },
};
</script>
