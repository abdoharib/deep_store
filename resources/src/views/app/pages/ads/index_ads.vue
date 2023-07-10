<template>
  <div class="main-content">
    <breadcumb :page="$t('ListAds')" :folder="$t('Ads')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <div v-else>
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="ads"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{
        placeholder: $t('Search_this_table'),
        enabled: true,
      }"
        :select-options="{
          enabled: true ,
          clearSelectionText: '',
        }"
        @on-selected-rows-change="selectionChanged"
        :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
        :styleClass="showDropdown?'tableOne table-hover vgt-table full-height':'tableOne table-hover vgt-table non-height'"
      >
        <div slot="selected-row-actions">
          <!-- <button class="btn btn-danger btn-sm" @click="delete_by_selected()">{{$t('Del')}}</button>
          <button class="btn btn-primary btn-sm" @click="pay_by_selected()">تسوية المبيعات</button> -->

        </div>
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button variant="outline-info ripple m-1" size="sm" v-b-toggle.sidebar-right>
            <i class="i-Filter-2"></i>
            {{ $t("Filter") }}
          </b-button>
          <!-- <b-button @click="Sales_PDF()" size="sm" variant="outline-success ripple m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
          <vue-excel-xlsx
              class="btn btn-sm btn-outline-danger ripple m-1"
              :data="sales"
              :columns="columns"
              :file-name="'sales'"
              :file-type="'xlsx'"
              :sheet-name="'sales'"
              >
              <i class="i-File-Excel"></i> EXCEL
          </vue-excel-xlsx>
          <router-link
            class="btn-sm btn btn-primary ripple btn-icon m-1"
            v-if="currentUserPermissions && currentUserPermissions.includes('Sales_add')"
            to="/app/sales/store"
          >
            <span class="ul-btn__icon">
              <i class="i-Add"></i>
            </span>
            <span class="ul-btn__text ml-1">{{$t('Add')}}</span>
          </router-link> -->
        </div>

            <template slot="table-row" slot-scope="props">

                      <div v-if="props.column.field == 'preformance_status'">

                        <span v-if="props.row.preformance_status == 'success'" class="badge badge-outline-success">ناجح</span>
                        <span v-else-if="props.row.preformance_status == 'average'" class="badge badge-outline-warning">متوسط</span>
                        <span v-else-if="props.row.preformance_status == 'loser'" class="badge badge-outline-danger">خاسر</span>

                      </div>


                    <div v-else-if="props.column.field == 'is_closed'">
                        <span v-if="props.row.is_closed" class="badge badge-outline-danger">تم الأقفال</span>
                        <span v-else> / </span>
                    </div>

              </template>
      </vue-good-table>
    </div>

    <!-- Sidebar Filter -->
    <b-sidebar id="sidebar-right" :title="$t('Filter')" bg-variant="white" right shadow>
      <div class="px-3 py-2">
        <b-row>
          <!-- start_date  -->
          <b-col md="12">
            <b-form-group :label="$t('start_date')">
              <b-form-input type="date" v-model="Filter_start_date"></b-form-input>
            </b-form-group>
          </b-col>

          <b-col md="12">
            <b-form-group :label="$t('end_date')">
              <b-form-input type="date" v-model="Filter_end_date"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- Reference -->
          <b-col md="12">
            <b-form-group :label="$t('ad_ref_id')">
              <b-form-input label="ad_ref_id" :placeholder="$t('ad_ref_id')" v-model="Filter_ad_ref_id"></b-form-input>
            </b-form-group>
          </b-col>


          <!-- warehouse -->
          <b-col md="12">
            <b-form-group :label="$t('warehouse')">
              <v-select
                v-model="Filter_warehouse"
                :reduce="label => label.value"
                :placeholder="$t('Choose_Warehouse')"
                :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))"
              />
            </b-form-group>
          </b-col>

          <!-- Status  -->
          <b-col md="12">
            <b-form-group :label="$t('Status')">
              <v-select
                v-model="Filter_ad_ref_status"
                :reduce="label => label.value"
                :placeholder="$t('Choose_Status')"
                :options="
                      [
                        {label: 'ACTIVE', value: 'ACTIVE'},
                        {label: 'PAUSED', value: 'PAUSED'}
                      ]"
              ></v-select>
            </b-form-group>
          </b-col>


          <b-col md="6" sm="12">
            <b-button
              @click="Get_Ads(serverParams.page)"
              variant="primary btn-block ripple m-1"
              size="sm"
            >
              <i class="i-Filter-2"></i>
              {{ $t("Filter") }}
            </b-button>
          </b-col>
          <b-col md="6" sm="12">
            <b-button @click="Reset_Filter()" variant="danger ripple btn-block m-1" size="sm">
              <i class="i-Power-2"></i>
              {{ $t("Reset") }}
            </b-button>
          </b-col>
        </b-row>
      </div>
    </b-sidebar>

  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";
import jsPDF from "jspdf";
import "jspdf-autotable";
import vueEasyPrint from "vue-easy-print";
import VueBarcode from "vue-barcode";
import { loadStripe } from "@stripe/stripe-js";
export default {
  components: {
    vueEasyPrint,
    barcode: VueBarcode
  },
  metaInfo: {
    title: "Sales"
  },
  data() {
    return {
      stripe_key:'',
      stripe: {},
      cardElement: {},
      pos_settings:{},
      paymentProcessing: false,
      Submit_Processing_shipment:false,
      isLoading: true,
      serverParams: {
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      selectedIds: [],
      search: "",
      totalRows: "",
      barcodeFormat: "CODE128",
      showDropdown: false,
      EditPaiementMode: false,

      Filter_end_date: '',
      Filter_start_date:  '',
      Filter_ad_ref_status:  '',
      Filter_preformance_status:  '',

      warehouses: [],
      ads: [],
      due:0,
      invoice_pos: {
        sale: {
          Ref: "",
          client_name: "",
          discount: "",
          taxe: "",
          tax_rate: "",
          shipping: "",
          GrandTotal: "",
          paid_amount:'',
        },
        details: [],
        setting: {
          logo: "",
          CompanyName: "",
          CompanyAdress: "",
          email: "",
          CompanyPhone: ""
        }
      },
      payments: [],
      payment: {},
      Sale_id: "",
      limit: "50",
      sale: {},
      email: {
        to: "",
        subject: "",
        message: "",
        client_name: "",
        Sale_Ref: ""
      },
      emailPayment: {
        id: "",
        to: "",
        subject: "",
        message: "",
        client_name: "",
        Ref: ""
      }
    };
  },
   mounted() {
    this.$root.$on("bv::dropdown::show", bvEvent => {
      this.showDropdown = true;
    });
    this.$root.$on("bv::dropdown::hide", bvEvent => {
      this.showDropdown = false;
    });
  },
  computed: {
    ...mapGetters(["currentUserPermissions", "currentUser"]),
    columns() {
      return [
      {
                    label: this.$t("ad_ref_id"),
                    field: "ad_ref_id",
                    tdClass: "text-left",
                    thClass: "text-left"
                },
        {
                    label: this.$t("product_name"),
                    field: "product_name",
                    tdClass: "text-left",
                    thClass: "text-left"
                },

                {
                    label: this.$t("warehouse_name"),
                    field: "warehouse_name",
                    tdClass: "text-left",
                    thClass: "text-left"
                },
                {
                    label: this.$t("sales"),
                    field: "no_sales",
                    tdClass: "text-left",
                    thClass: "text-left"
                },
                {
                    label: this.$t("completed_sales"),
                    field: "no_completed_sales",
                    tdClass: "text-left",
                    thClass: "text-left"
                },
                {
                    label: this.$t("total_sale_profit"),
                    field: "completed_sales_profit",
                    tdClass: "text-left",
                    thClass: "text-left"
                },
                {
                    label: this.$t("total_spent"),
                    field: "amount_spent",
                    html: true,
                    tdClass: "text-left",
                    thClass: "text-left"
                },
                {
                    label: this.$t("ad_ref_status"),
                    field: "ad_ref_status",
                    tdClass: "text-left",
                    thClass: "text-left",
                    sortable: false
                },
                {
                    label: this.$t("ad_set_ref_status"),
                    field: "ad_set_ref_status",
                    tdClass: "text-left",
                    thClass: "text-left",
                    sortable: false
                },
                {
                    label: this.$t("start_date"),
                    field: "start_date",
                    tdClass: "text-left",
                    thClass: "text-left"
                },
                {
                    label: this.$t("end_date"),
                    field: "end_date",
                    tdClass: "text-left",
                    thClass: "text-left"
                },
                {
                    label: this.$t("is_closed"),
                    field: "is_closed",
                    tdClass: "text-left",
                    thClass: "text-left"
                },
                {
                    label: this.$t("closed_at"),
                    field: "closed_at",
                    tdClass: "text-left",
                    thClass: "text-left"
                },

                {
                    label: this.$t("preformance_status"),
                    field: "preformance_status",
                    tdClass: "text-left",
                    thClass: "text-left",
                    sortable: false
                },
                {
                    label: this.$t("last_ad_update_at"),
                    field: "last_ad_update_at",
                    tdClass: "text-left",
                    thClass: "text-left"
                },
      ];
    }
  },
  methods: {

     async loadStripe_payment() {
      this.stripe = await loadStripe(`${this.stripe_key}`);
      const elements = this.stripe.elements();

      this.cardElement = elements.create("card", {
        classes: {
          base:
            "bg-gray-100 rounded border border-gray-300 focus:border-indigo-500 text-base outline-none text-gray-700 p-3 leading-8 transition-colors duration-200 ease-in-out"
        }
      });

      this.cardElement.mount("#card-element");
    },


    //---------------------- Event Select Payment Method ------------------------------\\
    Selected_PaymentMethod(value) {
      if (value == "credit card") {
        setTimeout(() => {
          this.loadStripe_payment();
        }, 500);
      }
    },

    //------------------------------ Print -------------------------\\
    print_it() {
      var divContents = document.getElementById("invoice-POS").innerHTML;
      var a = window.open("", "", "height=500, width=500");
      a.document.write(
        '<link rel="stylesheet" href="/css/pos_print.css"><html>'
      );
      a.document.write("<body >");
      a.document.write(divContents);
      a.document.write("</body></html>");
      a.document.close();

      setTimeout(() => {
         a.print();
      }, 1000);
    },


    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Ads(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Ads(1);
      }
    },

    //---- Event Select Rows
    selectionChanged({ selectedRows }) {
      this.selectedIds = [];
      selectedRows.forEach((row, index) => {
        this.selectedIds.push(row.id);
      });
    },

    //---- Event Sort change
    onSortChange(params) {
      let field = "";
      if (params[0].field == "client_name") {
        field = "client_id";
      } else if (params[0].field == "warehouse_name") {
        field = "warehouse_id";
      }else if (params[0].field == "created_by") {
        field = "user_id";
      } else {
        field = params[0].field;
      }
      this.updateParams({
        sort: {
          type: params[0].type,
          field: field
        }
      });
      this.Get_Ads(this.serverParams.page);
    },


    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Ads(this.serverParams.page);
    },

     //---------- keyup paid Amount

    Verified_paidAmount() {
      if (isNaN(this.payment.montant)) {
        this.payment.montant = 0;
      } else if (this.payment.montant > this.payment.received_amount) {
        this.makeToast(
          "warning",
          this.$t("Paying_amount_is_greater_than_Received_amount"),
          this.$t("Warning")
        );
        this.payment.montant = 0;
      }
      else if (this.payment.montant > this.due) {
        this.makeToast(
          "warning",
          this.$t("Paying_amount_is_greater_than_Grand_Total"),
          this.$t("Warning")
        );
        this.payment.montant = 0;
      }
    },

    //---------- keyup Received Amount

    Verified_Received_Amount() {
      if (isNaN(this.payment.received_amount)) {
        this.payment.received_amount = 0;
      }
    },


    //------ Validate Form Submit_Payment
    Submit_Payment() {
      this.$refs.Add_payment.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else if (this.payment.montant > this.payment.received_amount) {
          this.makeToast(
            "warning",
            this.$t("Paying_amount_is_greater_than_Received_amount"),
            this.$t("Warning")
          );
          this.payment.received_amount = 0;
        }
        else if (this.payment.montant > this.due) {
          this.makeToast(
            "warning",
            this.$t("Paying_amount_is_greater_than_Grand_Total"),
            this.$t("Warning")
          );
          this.payment.montant = 0;

        }else if (!this.EditPaiementMode) {
            this.Create_Payment();
        } else {
            this.Update_Payment();
        }

      });
    },


    //---Validate State Fields
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },
    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },
    //------ Reset Filter
    Reset_Filter() {
        this.Filter_end_date = "",
        this.Filter_start_date = "",
        this.Filter_ad_ref_status = "",
        this.Filter_preformance_status = "",
        this.search = "";
        this.Get_Ads(this.serverParams.page);
    },
    //------------------------------Formetted Numbers -------------------------\\
    formatNumber(number, dec) {
      const value = (typeof number === "string"
        ? number
        : number.toString()
      ).split(".");
      if (dec <= 0) return value[0];
      let formated = value[1] || "";
      if (formated.length > dec)
        return `${value[0]}.${formated.substr(0, dec)}`;
      while (formated.length < dec) formated += "0";
      return `${value[0]}.${formated}`;
    },
    //----------------------------------- Sales PDF ------------------------------\\
    Sales_PDF() {
      var self = this;
      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "Ref", dataKey: "Ref" },
        { title: "Client", dataKey: "client_name" },
        { title: "Warehouse", dataKey: "warehouse_name" },
        { title: "Created_by", dataKey: "created_by" },
        { title: "Status", dataKey: "statut" },
        { title: "Total", dataKey: "GrandTotal" },
        { title: "Paid", dataKey: "paid_amount" },
        { title: "Due", dataKey: "due" },
        { title: "Status Payment", dataKey: "payment_status" },
        { title: "Shipping Status", dataKey: "shipping_status" }
      ];
      pdf.autoTable(columns, self.sales);
      pdf.text("Sale List", 40, 25);
      pdf.save("Sale_List.pdf");
    },
    //-------------------------------- Invoice POS ------------------------------\\
    Invoice_POS(id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get("sales_print_invoice/" + id)
        .then(response => {
          this.invoice_pos = response.data;
          this.payments = response.data.payments;
          this.pos_settings = response.data.pos_settings;
          setTimeout(() => {
            // Complete the animation of the  progress bar.
            NProgress.done();
            this.$bvModal.show("Show_invoice");
          }, 500);

          if(response.data.pos_settings.is_printable){
            setTimeout(() => this.print_it(), 1000);
          }

        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },

    //-----------------------------  Invoice PDF ------------------------------\\
    Invoice_PDF(sale, id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
       axios
        .get("sale_pdf/" + id, {
          responseType: "blob", // important
          headers: {
            "Content-Type": "application/json"
          }
        })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute("download", "Sale-" + sale.Ref + ".pdf");
          document.body.appendChild(link);
          link.click();
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },
    //------------------------ Payments Sale PDF ------------------------------\\
    Payment_Sale_PDF(payment, id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);

      axios
        .get("payment_sale_pdf/" + id, {
          responseType: "blob", // important
          headers: {
            "Content-Type": "application/json"
          }
        })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute("download", "Payment-" + payment.Ref + ".pdf");
          document.body.appendChild(link);
          link.click();
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },
    //---------------------------------------- Set To Strings-------------------------\\
    setToStrings() {
      // Simply replaces null values with strings=''
      if (this.Filter_Client === null) {
        this.Filter_Client = "";
      } else if (this.Filter_warehouse === null) {
        this.Filter_warehouse = "";
      } else if (this.Filter_status === null) {
        this.Filter_status = "";
      } else if (this.Filter_Payment === null) {
        this.Filter_Payment = "";
      }else if (this.Filter_shipping === null) {
        this.Filter_shipping = "";
      }
    },
    //----------------------------------------- Get all Sales ------------------------------\\
    Get_Ads(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.setToStrings();
      axios
        .get(
          "ads?page=" +
            page +
            "&ad_ref_status=" +
            this.Filter_ad_ref_status +
            "&start_date=" +
            this.Filter_start_date +
            "&end_date=" +
            this.Filter_end_date +
            "&SortField=" +
            this.serverParams.sort.field +
            "&SortType=" +
            this.serverParams.sort.type +
            "&search=" +
            this.search +
            "&limit=" +
            this.limit
        )
        .then(response => {
          this.ads = response.data.ads;
          this.warehouses = response.data.warehouses;
          this.totalRows = response.data.totalRows;
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.isLoading = false;
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },

    //---------SMS notification
     Payment_Sale_SMS(payment) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .post("payment_sale_send_sms", {
          id: payment.id,
        })
        .then(response => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          this.makeToast(
            "success",
            this.$t("Send_SMS"),
            this.$t("Success")
          );
        })
        .catch(error => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          this.makeToast("danger", this.$t("sms_config_invalid"), this.$t("Failed"));
        });
    },


    //--------------------------------------------- Send Payment to Email -------------------------------\\
    EmailPayment(payment, sale) {
      this.emailPayment.id = payment.id;
      this.emailPayment.to = sale.client_email;
      this.emailPayment.Ref = payment.Ref;
      this.emailPayment.client_name = sale.client_name;
      this.Send_Email_Payment();
    },
    Send_Email_Payment() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .post("payment_sale_send_email", {
          id: this.emailPayment.id,
          to: this.emailPayment.to,
          client_name: this.emailPayment.client_name,
          Ref: this.emailPayment.Ref
        })
        .then(response => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          this.makeToast(
            "success",
            this.$t("Send.TitleEmail"),
            this.$t("Success")
          );
        })
        .catch(error => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          this.makeToast("danger", this.$t("SMTPIncorrect"), this.$t("Failed"));
        });
    },
    //--------------------------------- Send Sale in Email ------------------------------\\
    Sale_Email(sale) {
      this.email.to = sale.client_email;
      this.email.Sale_Ref = sale.Ref;
      this.email.client_name = sale.client_name;
      this.Send_Email(sale.id);
    },
    Send_Email(id) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .post("sales_send_email", {
          id: id,
          to: this.email.to,
          client_name: this.email.client_name,
          Ref: this.email.Sale_Ref
        })
        .then(response => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          this.makeToast(
            "success",
            this.$t("Send.TitleEmail"),
            this.$t("Success")
          );
        })
        .catch(error => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          this.makeToast("danger", this.$t("SMTPIncorrect"), this.$t("Failed"));
        });
    },
    Number_Order_Payment() {
      axios
        .get("payment_sale_get_number")
        .then(({ data }) => (this.payment.Ref = data));
    },
    //----------------------------------- New Payment Sale ------------------------------\\
    New_Payment(sale) {
      if (sale.payment_status == "paid") {
        this.$swal({
          icon: "error",
          title: "Oops...",
          text: this.$t("PaymentComplete")
        });
      } else {
        // Start the progress bar.
        NProgress.start();
        NProgress.set(0.1);
        this.reset_form_payment();
        this.EditPaiementMode = false;
        this.sale = sale;
        this.payment.date = new Date().toISOString().slice(0, 10);
        this.Number_Order_Payment();
        this.payment.montant = sale.due;
        this.payment.Reglement = 'Cash';
        this.payment.received_amount = sale.due;
        this.due = parseFloat(sale.due);
        setTimeout(() => {
          // Complete the animation of the  progress bar.
          NProgress.done();
          this.$bvModal.show("Add_Payment");
        }, 500);
      }
    },
    //------------------------------------Edit Payment ------------------------------\\
    Edit_Payment(payment) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.reset_form_payment();
      this.EditPaiementMode = true;

      this.payment.id        = payment.id;
      this.payment.Ref       = payment.Ref;
      this.payment.Reglement = payment.Reglement;
      this.payment.date    = payment.date;
      this.payment.change  = payment.change;
      this.payment.montant = payment.montant;
      this.payment.received_amount = parseFloat(payment.montant + payment.change).toFixed(2);
      this.payment.notes   = payment.notes;

      this.due = parseFloat(this.sale_due) + payment.montant;
      setTimeout(() => {
        // Complete the animation of the  progress bar.
        NProgress.done();
        this.$bvModal.show("Add_Payment");
      }, 1000);
      if (payment.Reglement == "credit card") {
        setTimeout(() => {
          this.loadStripe_payment();
        }, 500);
      }
    },
    //-------------------------------Show All Payment with Sale ---------------------\\
    Show_Payments(id, sale) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.reset_form_payment();
      this.Sale_id = id;
      this.sale = sale;
      this.Get_Payments(id);
    },
    //----------------------------------Process Payment (Mode Create) ------------------------------\\
    async processPayment_Create() {
      const { token, error } = await this.stripe.createToken(
        this.cardElement
      );
      if (error) {
        this.paymentProcessing = false;
        NProgress.done();
        this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
      } else {
        axios
          .post("payment_sale", {
            sale_id: this.sale.id,
            client_email: this.sale.client_email,
            client_id: this.sale.client_id,
            date: this.payment.date,
            montant: parseFloat(this.payment.montant).toFixed(2),
            received_amount: parseFloat(this.payment.received_amount).toFixed(2),
            change: parseFloat(this.payment.received_amount - this.payment.montant).toFixed(2),
            Reglement: this.payment.Reglement,
            notes: this.payment.notes,
            token: token.id
          })
          .then(response => {
            this.paymentProcessing = false;
            Fire.$emit("Create_Facture_sale");
            this.makeToast(
              "success",
              this.$t("Create.TitlePayment"),
              this.$t("Success")
            );
          })
          .catch(error => {
            this.paymentProcessing = false;
            // Complete the animation of the  progress bar.
            NProgress.done();
            this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          });
      }
    },
    //----------------------------------Process Payment (Mode Edit) ------------------------------\\
    async processPayment_Update() {
       const { token, error } = await this.stripe.createToken(
        this.cardElement
      );
      if (error) {
        this.paymentProcessing = false;
        NProgress.done();
        this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
      } else {
        axios
          .put("payment_sale/" + this.payment.id, {
            sale_id: this.sale.id,
            client_email: this.sale.client_email,
            client_id: this.sale.client_id,
            date: this.payment.date,
            montant: parseFloat(this.payment.montant).toFixed(2),
            received_amount: parseFloat(this.payment.received_amount).toFixed(2),
            change: parseFloat(this.payment.received_amount - this.payment.montant).toFixed(2),
            Reglement: this.payment.Reglement,
            notes: this.payment.notes,
            token: token.id
          })
          .then(response => {
            this.paymentProcessing = false;
            Fire.$emit("Update_Facture_sale");
            this.makeToast(
              "success",
              this.$t("Update.TitlePayment"),
              this.$t("Success")
            );
          })
          .catch(error => {
            this.paymentProcessing = false;
            // Complete the animation of the  progress bar.
            NProgress.done();
            this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          });
      }
    },
    //----------------------------------Create Payment sale ------------------------------\\
    Create_Payment() {
      this.paymentProcessing = true;
      NProgress.start();
      NProgress.set(0.1);
       if(this.payment.Reglement  == 'credit card'){
          if(this.stripe_key != ''){
            this.processPayment_Create();
          }else{
            this.makeToast("danger", this.$t("credit_card_account_not_available"), this.$t("Failed"));
            NProgress.done();
            this.paymentProcessing = false;
          }
        }else{
        axios
          .post("payment_sale", {
            sale_id: this.sale.id,
            date: this.payment.date,
            montant: parseFloat(this.payment.montant).toFixed(2),
            received_amount: parseFloat(this.payment.received_amount).toFixed(2),
            change: parseFloat(this.payment.received_amount - this.payment.montant).toFixed(2),
            Reglement: this.payment.Reglement,
            notes: this.payment.notes
          })
          .then(response => {
            this.paymentProcessing = false;
            Fire.$emit("Create_Facture_sale");
            this.makeToast(
              "success",
              this.$t("Create.TitlePayment"),
              this.$t("Success")
            );
          })
          .catch(error => {
            this.paymentProcessing = false;
            NProgress.done();
          });
      }
    },
    //---------------------------------------- Update Payment ------------------------------\\
    Update_Payment() {
      this.paymentProcessing = true;
      NProgress.start();
      NProgress.set(0.1);
       if(this.payment.Reglement  == 'credit card'){
          if(this.stripe_key != ''){
            this.processPayment_Update();
          }else{
            this.makeToast("danger", this.$t("credit_card_account_not_available"), this.$t("Failed"));
            NProgress.done();
            this.paymentProcessing = false;
          }
        }else{
        axios
          .put("payment_sale/" + this.payment.id, {
            sale_id: this.sale.id,
            date: this.payment.date,
            montant: parseFloat(this.payment.montant).toFixed(2),
            received_amount: parseFloat(this.payment.received_amount).toFixed(2),
            change: parseFloat(this.payment.received_amount - this.payment.montant).toFixed(2),
            Reglement: this.payment.Reglement,
            notes: this.payment.notes
          })
          .then(response => {
            this.paymentProcessing = false;
            Fire.$emit("Update_Facture_sale");
            this.makeToast(
              "success",
              this.$t("Update.TitlePayment"),
              this.$t("Success")
            );
          })
          .catch(error => {
            this.paymentProcessing = false;
            NProgress.done();
          });
      }
    },
    //----------------------------------------- Remove Payment ------------------------------\\
    Remove_Payment(id) {
      this.$swal({
        title: this.$t("Delete.Title"),
        text: this.$t("Delete.Text"),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: this.$t("Delete.cancelButtonText"),
        confirmButtonText: this.$t("Delete.confirmButtonText")
      }).then(result => {
        if (result.value) {
          // Start the progress bar.
          NProgress.start();
          NProgress.set(0.1);
          axios
            .delete("payment_sale/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.PaymentDeleted"),
                "success"
              );
              Fire.$emit("Delete_Facture_sale");
            })
            .catch(() => {
              // Complete the animation of the  progress bar.
              setTimeout(() => NProgress.done(), 500);
              this.$swal(
                this.$t("Delete.Failed"),
                this.$t("Delete.Therewassomethingwronge"),
                "warning"
              );
            });
        }
      });
    },
    //----------------------------------------- Get Payments  -------------------------------\\
    Get_Payments(id) {
      axios
        .get("get_payments_by_sale/" + id)
        .then(response => {
          this.payments = response.data.payments;
          this.sale_due = response.data.due;
          setTimeout(() => {
            // Complete the animation of the  progress bar.
            NProgress.done();
            this.$bvModal.show("Show_payment");
          }, 500);
        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },
    //------------------------------------------ Reset Form Payment ------------------------------\\
    reset_form_payment() {
      this.due = 0;
      this.payment = {
        id: "",
        Sale_id: "",
        date: "",
        Ref: "",
        montant: "",
        received_amount: "",
        Reglement: "",
        notes: ""
      };
    },

     //---------------------- Get_Data_Create  ------------------------------\\

      Get_shipment_by_sale(sale_id) {
        axios
            .get("/shipments/" + sale_id)
            .then(response => {
                this.shipment   = response.data.shipment;

                 setTimeout(() => {
                    NProgress.done();
                    this.$bvModal.show("modal_shipment");
                }, 1000);
            })
            .catch(error => {
              NProgress.done();

            });
    },

      //------------- Submit Validation Edit shipment
      Submit_Shipment() {
      this.$refs.shipment_ref.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Update_Shipment();
        }
      });
    },

      //----------------------- Update_Shipment ---------------------------\\
    Update_Shipment() {
      var self = this;
      self.Submit_Processing_shipment = true;
      axios
        .post("shipments", {
          Ref: self.shipment.Ref,
          sale_id: self.shipment.sale_id,
          shipping_address: self.shipment.shipping_address,
          delivered_to: self.shipment.delivered_to,
          shipping_details: self.shipment.shipping_details,
          status: self.shipment.status
        })
        .then(response => {
          this.makeToast(
            "success",
            this.$t("Updated_in_successfully"),
            this.$t("Success")
          );
          Fire.$emit("event_update_shipment");
          self.Submit_Processing_shipment = false;
        })
        .catch(error => {
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          self.Submit_Processing_shipment = false;
        });
    },


     //------------------------------ Show Modal (Edit shipment) -------------------------------\\
    Edit_Shipment(sale_id) {
      NProgress.start();
      NProgress.set(0.1);
      this.reset_Form_shipment();
      this.Get_shipment_by_sale(sale_id);
    },

      //-------------------------------- Reset Form -------------------------------\\
    reset_Form_shipment() {
      this.shipment = {
        id: "",
        date: "",
        Ref: "",
        sale_id: "",
        attachment: "",
        delivered_to: "",
        shipping_address: "",
        status: "",
        shipping_details: ""
      };
    },

    //------------------------------------------ Remove Sale ------------------------------\\
    Remove_Sale(id , sale_has_return) {
      if(sale_has_return == 'yes'){
        this.makeToast("danger", this.$t("Return_exist_for_the_Transaction"), this.$t("Failed"));
      }else{
        this.$swal({
          title: this.$t("Delete.Title"),
          text: this.$t("Delete.Text"),
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          cancelButtonText: this.$t("Delete.cancelButtonText"),
          confirmButtonText: this.$t("Delete.confirmButtonText")
        }).then(result => {
          if (result.value) {
            // Start the progress bar.
            NProgress.start();
            NProgress.set(0.1);
            axios
              .delete("sales/" + id)
              .then(() => {
                this.$swal(
                  this.$t("Delete.Deleted"),
                  this.$t("Delete.SaleDeleted"),
                  "success"
                );
                Fire.$emit("Delete_sale");
              })
              .catch(() => {
                // Complete the animation of the  progress bar.
                setTimeout(() => NProgress.done(), 500);
                this.$swal(
                  this.$t("Delete.Failed"),
                  this.$t("Delete.Therewassomethingwronge"),
                  "warning"
                );
              });
          }
        });
      }
    },
    //---- Delete sales by selection
    delete_by_selected() {
      this.$swal({
        title: this.$t("Delete.Title"),
        text: this.$t("Delete.Text"),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: this.$t("Delete.cancelButtonText"),
        confirmButtonText: this.$t("Delete.confirmButtonText")
      }).then(result => {
        if (result.value) {
          // Start the progress bar.
          NProgress.start();
          NProgress.set(0.1);
          axios
            .post("sales_delete_by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.SaleDeleted"),
                "success"
              );
              Fire.$emit("Delete_sale");
            })
            .catch(() => {
              // Complete the animation of theprogress bar.
              setTimeout(() => NProgress.done(), 500);
              this.$swal(
                this.$t("Delete.Failed"),
                this.$t("Delete.Therewassomethingwronge"),
                "warning"
              );
            });
        }
      });
    },
     //---- Pay sales by selection
     pay_by_selected() {
      this.$swal({
        title: 'دفع المبيعات المختارة',
        text: 'سيتم أنشاء عملية دفع لكل المبيعات الذي تم أختيارها',
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: 'ألغاء',
        confirmButtonText: 'تطبيق'
      }).then(result => {
        if (result.value) {
          // Start the progress bar.
          NProgress.start();
          NProgress.set(0.1);
          axios
            .post("sales_pay_by_selection", {
              sales: this.selectedIds
            })
            .then(() => {
              this.$swal(
                'تم الدفع',
                'تم دفع جميع المبيعات المختارة بنجاح',
                "success"
              );
              Fire.$emit("Delete_sale");
            })
            .catch(() => {
              // Complete the animation of theprogress bar.
              setTimeout(() => NProgress.done(), 500);
              this.$swal(
                this.$t("Delete.Failed"),
                this.$t("Delete.Therewassomethingwronge"),
                "warning"
              );
            });
        }
      });
    }
  },
  //----------------------------- Created function-------------------\\
  created() {
    this.Get_Ads(1);


  }
};
</script>

<style>
  .total{
    font-weight: bold;
    font-size: 14px;
    /* text-transform: uppercase;
    height: 50px; */
  }
</style>
