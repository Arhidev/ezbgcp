<template>
  <div>
    <div class="avatar">
      <v-avatar rounded size="120" class="me-6">
        <v-img :src="avatarImageUrl"></v-img>
      </v-avatar>
      <v-btn v-if="edit" color="primary" class="me-3 mt-5" @click="$refs.photo.click()">
        <v-icon class="d-sm-none">
          {{ icons.mdiCloudUploadOutline }}
        </v-icon>
        <span class="d-none d-sm-block">Upload new photo</span>
      </v-btn>
      <input
        ref="photo"
        type="file"
        accept=".jpeg,.png,.jpg,GIF"
        :hidden="true"
        @change="updateAvatar()"
      />
      <p v-if="edit" class="text-sm mt-5">Allowed JPG, GIF or PNG. Max size of 800K</p>
    </div>

    <div v-if="showUploadProgress">Uploading: {{ uploadPercent }} %</div>
  </div>
</template>
<script>
import { mdiAlertOutline, mdiCloudUploadOutline } from "@mdi/js";
import { adminProfileStore } from "@/utils/helpers";
import axios from "axios";
import {Keys} from '/src/config.js'

export default {
  components: {
    Keys
  },
  name: "AvatarImageComponent",
  props: ["avatarUrl", "user", "edit"],
  setup() {
    return { adminProfileStore }
  },
  data() {
    return {
      uploadPercent: 0,
      defaultAvatar: "/storage/avatars/1/avatar.png",
      avatarImageUrl: "",
      showUploadProgress: false,
      processingUpload: false,
      icons: {
        mdiAlertOutline,
        mdiCloudUploadOutline,
      },
    };
  },
  mounted() {
    if (this.avatarUrl) 
    {
      this.avatarImageUrl = Keys.VUE_APP_API_URL + this.avatarUrl;
    } else
    {
      this.avatarImageUrl = Keys.VUE_APP_API_URL + this.defaultAvatar;
    }
    console.log(this.avatarImageUrl);
  },
  methods: {
    updateAvatar() {
      if (this.$refs.photo) {
        this.showUploadProgress = true;
        this.processingUpload = true;
        this.uploadPercent = 0;
        const config = {
          headers: { "content-type": "multipart/form-data" },
        };
        let formData = new FormData();
        formData.append("avatar", this.$refs.photo.files[0]);
        axios
          .post("users/upload-avatar", formData, config, {
            onUploadProgress: (progressEvent) => {
              this.uploadPercent = progressEvent.lengthComputable
                ? Math.round((progressEvent.loaded * 100) / progressEvent.total)
                : 0;
            },
          })
          .then((response) => {
            this.avatarImageUrl = Keys.VUE_APP_API_URL + "/" + response.data.avatar_url;
            if(this.user.role == 0)
            {
              this.adminProfileStore.avatar = response.data.avatar_url;
              this.$emit("imageUrl", response.data.secure_url);
            }
            this.showUploadProgress = false;
            this.processingUpload = false;
          })
          .catch((error) => {
            if (error.response) {
              console.log(error.message);
            } else {
              console.log(error);
            }
            this.showUploadProgress = false;
            this.processingUpload = false;
          });
      }
    },
  },
};
</script>
