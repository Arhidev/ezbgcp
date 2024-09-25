<template>
  <div>
    <v-card>
      <v-card-title>
      <v-icon color="primary">
        mdi-comment-alert
      </v-icon>
        <span class="pl-2">Complaints</span>
      </v-card-title>
      <v-tabs v-model="active_tab" show-arrows class="my-2">
        <v-tab v-for="tab in tabs" :key="tab.idx">
          <v-icon size="20" class="me-3">
            {{ tab.icon }}
          </v-icon>
          <span>{{ tab.title }}</span>
        </v-tab>
      </v-tabs>
    <v-tabs-items v-model="active_tab">
      <!-- active -->
      <v-tab-item>
        <complaints-table :complaints="activeComplaints" :is-loading="isLoading" :is-active="true"
        @view-complaint="viewComplaint"
        @refund-complaint="refundComplaint"
        @cancel-complaint="cancelComplaint"
        ></complaints-table>
      </v-tab-item>

      <!-- completed -->
      <v-tab-item>
        <complaints-table :complaints="completedComplaints" :is-loading="isLoading" :is-active="false"
        @view-complaint="viewComplaint"
        @refund-complaint="refundComplaint"
        @cancel-complaint="cancelComplaint"
        @view-response="viewResponse"
        ></complaints-table>
      </v-tab-item>

    </v-tabs-items>
    </v-card>
  </div>
</template>

<script>

import {
  mdiAccountCheck,
  mdiPlayCircleOutline,
} from "@mdi/js";

import complaintsTable from "./complaints-table.vue";
import auth from '@/services/AuthService';

export default {
  components: {
    complaintsTable
  },
  data() {
    return {
      isLoading: false,
      activeComplaints: [],
      completedComplaints: [],
      tabs: [
        { idx: 0, title: "Active", icon: mdiPlayCircleOutline },
        { idx: 1, title: "Completed", icon: mdiAccountCheck },
      ],
      active_tab: null,
      icons: {
        mdiAccountCheck,
        mdiPlayCircleOutline,
      },
    };
  },
  watch: {
    active_tab: function (newVal, oldVal) {
      localStorage.tabIdxComplaints = newVal;
    },
  },
  mounted() {
    this.active_tab = parseInt(localStorage.tabIdxComplaints);
    this.loadComplaints();
  },
  methods: {
    loadComplaints() {
      this.isLoading = true;
      this.activeComplaints = [];
      this.completedComplaints = [];
      axios
        .get(`/complaints/all`)
        .then((response) => {
          this.activeComplaints = response.data.active;
          this.completedComplaints = response.data.completed;
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while retrieving complaints",
            type: "error",
          });
          console.log(error);
          auth.checkError(error.response.data.message, this.$router, this.$swal);
        })
        .then(() => {
          this.isLoading = false;
        });
    },

    viewComplaint(complaint) {
      //swal with the complaint message
      this.$swal({
        title: "Complaint",
        html: complaint.complaint,
        icon: "info",
        showCancelButton: false,
        confirmButtonText: "Ok",
      });
    },
    viewResponse(complaint) {
      //swal with the complaint message
      this.$swal({
        title: "Response",
        html: complaint.response,
        icon: "info",
        showCancelButton: false,
        confirmButtonText: "Ok",
      });
    },

    cancelComplaint(complaint, index) {
      this.$swal({
        input: 'textarea',
        inputPlaceholder: 'Why are you cancelling this complaint?',
        inputAttributes: {
          'aria-label': 'Why are you cancelling this complaint?'
        },
        title: "Cancel Complaint",
        html: "Are you sure you want to cancel this complaint?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, cancel it!",
      }).then((result) => {
        console.log(result);
        if (result.isConfirmed) {
          this.takeActionOnComplaintServer(complaint.id, result.value, index, "cancel");
        }
      });
    },

    refundComplaint(complaint, index) {
      this.$swal({
        input: 'textarea',
        inputPlaceholder: 'Why are you are approving this refund?',
        inputAttributes: {
          'aria-label': 'Why are you are approving this refund?'
        },
        title: "Refund Complaint",
        html: "Are you sure you want to refund this complaint?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, refund it!",
      }).then((result) => {
        if (result.isConfirmed) {
          this.takeActionOnComplaintServer(complaint.id, result.value, index, "refund");
        }
      });
    },

    takeActionOnComplaintServer(complaint_id, responseText, index, action) {
      this.isLoading = true;
      axios
        .post(`/complaints/take-action`, {
          complaint_id: complaint_id,
          response: responseText,
          action: action,
        })
        .then((response) => {
          this.$notify({
            title: "Success",
            text: "Complaint " + action + "d",
            type: "success",
          });
          let complaintWithAction = this.activeComplaints[index];
          complaintWithAction.response = responseText;
          complaintWithAction.action = action;

          //add to completed in first position
          this.completedComplaints.unshift(complaintWithAction);
          this.activeComplaints.splice(index, 1);
        })
        .catch((error) => {
          this.$notify({
            title: "Error",
            text: "Error while " + action + "ing complaint",
            type: "error",
          });
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
