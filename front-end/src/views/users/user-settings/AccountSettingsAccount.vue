<template>
  <v-card v-if="accountData != null" flat class="pa-3 mt-2">
    <v-card-text class="d-flex">
      <!-- upload photo -->
      <div>
        <avatar-image-component :edit="accountData.role == 0" :avatarUrl="accountData.avatar" :user="accountData"></avatar-image-component>
      </div>
    </v-card-text>

    <v-card-text>
      <v-form
        ref="form"
        v-model="valid"
        class="multi-col-validation mt-6"
        lazy-validation
      >
        <v-row>
          <v-col md="6" cols="12">
            <v-text-field
              v-model="accountData.name"
              label="Name"
              dense
              outlined
              required
              :rules="nameRules"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="accountData.email"
              label="E-mail"
              :disabled="accountData.id != 1"
              dense
              outlined
              required
              :rules="emailRules"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="6">
            <v-select
            disabled
              v-model="accountData.role"
              dense
              outlined
              label="Role"
              :items="roles"
              item-text="role"
              item-value="value"
            ></v-select>
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="accountData.status_id"
              :disabled="accountData.id == 1"
              dense
              outlined
              label="Status"
              :items="statuses"
              item-text="status"
              item-value="value"
            ></v-select>
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="accountData.tel_number"
              outlined
              dense
              label="Phone"
              :rules="phoneRules"
            ></v-text-field>
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="accountData.wallet"
              outlined
              dense
              label="Wallet"
            ></v-text-field>
          </v-col>

          <v-col cols="12">
            <v-btn color="primary" class="me-3 mt-4" @click="saveUser">
              Save changes
            </v-btn>
            <v-btn
              color="secondary"
              outlined
              class="mt-4"
              @click="cancel"
            >
              Cancel
            </v-btn>
          </v-col>
        </v-row>
      </v-form>
    </v-card-text>
  </v-card>
</template>

<script>

import AvatarImageComponent from '../../../components/AvatarImageComponent.vue'
import { mdiAlertOutline, mdiCloudUploadOutline } from "@mdi/js";

export default {
  components: {
    AvatarImageComponent
  },
  props: {
    accountData: {
      type: Object,
      default: () => {},
    },
  },
  data() {
    return {
      valid: true,
      nameRules: [(v) => !!v || "Name is required"],
      emailRules: [
        v => !!v || 'E-mail is required',
        v => /.+@.+\..+/.test(v) || 'E-mail must be valid',
      ],
      phoneRules: [
        v => /^(|\d)+$/.test(v) || 'Phone must be valid',
      ],
      statuses: [
        { status: "Active", value: 1 },
        { status: "Pending", value: 2 },
        { status: "Suspended", value: 3 },
      ],
      roles: [
        { role: "Admin", value: 0},
        { role: "Customer", value: 1 },
        { role: "Driver", value: 2 },
      ],
      icons: {
        mdiAlertOutline,
        mdiCloudUploadOutline,
      },
    };
  },
  methods: {
    validate() {
      return this.$refs.form.validate();
    },
    saveUser() {
      if (!this.validate()) return;
      this.$emit("save-user-info");
    },
    cancel()
    {
      this.$router.go(-1);
    }
  },
};
</script>
