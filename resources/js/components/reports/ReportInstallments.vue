<template>
  <div class="page">
    <div class="page-header row">
      <div class=" col-6 col-sm-12 col-md-6 col-lg-6">
        <h3 class=" h3">Reporte de pago de cuotas</h3>

      </div>
      <ul class="list-group  col-6 col-sm-12 col-md-6 col-lg-6">
        <li class="list-group-item">
          <h5 class="text-dark font-weight-bold">Valor total: {{ ReportPortfolioTotal.paid_balance | currency }}</h5>
        </li>
        <li class="list-group-item">
          <h5 class="text-dark font-weight-bold">Capital:{{ ReportPortfolioTotal.paid_capital | currency }} </h5>
        </li>
        <li class="list-group-item">
          <h5 class="text-dark font-weight-bold">Intereses: {{ (ReportPortfolioTotal.paid_balance -
            ReportPortfolioTotal.paid_capital) | currency }}</h5>
        </li>
      </ul>
    </div>

    <div class="page-search p-4 border my-2">
      <h6 class="text-primary text-uppercase">Filtrar:</h6>
      <form>
        <div class="form-row">

          <div class="form-group col-md-4 col-sm-6 col-xs-6 ">
            <label for="">Desde:</label>
            <input type="date" id="search_from" name="search_from" class="form-control" placeholder="Desde"
              v-model="search_from" :max="now" />
          </div>
          <div class="form-group col-md-4 col-sm-6 col-xs-6 ">
            <label for="">Hasta:</label>
            <input type="date" id="search_to" name="search_to" class="form-control" placeholder="Desde"
              v-model="search_to" :max="now" />
          </div>
          <div class="form-group col-md-4 col-sm-6 col-xs-6 ">
            <label for="">Mostrar {{ search_results }} resultados por página:</label>
            <input type="number" id="search_results" name="search_results" class="form-control" placeholder="Desde"
              v-model="search_results" max="1000" />
          </div>
          <div class="form-group offset-md-4 col-md-4">
            <download-excel class="btn btn-primary w-100  mt-5" :fields="json_fields" :data="ReportInstallmentList.data"
              name="report-portfolio.xls" type="xls">
              <i class="bi bi-file-earmark-arrow-down-fill"></i> Descargar .xls
            </download-excel>
          </div>
          <div class="form-group col-md-4 col-sm-6 col-xs-6 ">
            <button class="btn btn-success w-100 mt-5" type="button" @click="listReportInstallments(1)">
              <i class="bi bi-search"></i> Buscar
            </button>
          </div>
        </div>

      </form>
    </div>
    <div class="text-center" v-if="loading">
      <div class="spinner-border text-primary" role="status" style="width: 5rem; height: 5rem;">
        <span class="sr-only">Loading...</span>
      </div>
    </div>
    <div class="page-content">
      <section class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead>
            <tr class="text-center">
              <th>#</th>
              <th>Sede</th>
              <th>Fecha </th>
              <th>Tipo</th>
              <th>Afectación</th>
              <th>Capital</th>
              <th>Interés</th>
              <th>Interés Cobro & Admin </th>
              <th>Valor</th>
            </tr>
          </thead>
          <tbody v-if="ReportInstallmentList.data">
            <tr v-for="report in ReportInstallmentList.data" :key="report.id">
              <td>{{ report.id }}</td>
              <td class="text-uppercase">{{ report.headquarter }}</td>
              <td>{{ report.payment_register }}</td>
              <td>Pago de cuota</td>
              <td>
                Cliente: {{ report.name + ' ' + report.last_name }}
                Documento: {{ report.type_document }} {{ report.document }}
              </td>
              <td class="text-right">{{ report.paid_capital | currency }}</td>

              <td>
                {{ (report.paid_balance - report.paid_capital - report.additional_interest_paid) | currency }}
              </td>
              <td>
                {{ (report.additional_interest_paid) | currency }}
              </td>
              <td class="text-right">{{ report.paid_balance | currency }}</td>
            </tr>
            <tr>
              <th colspan="5"></th>
              <th> {{ ReportPortfolioTotal.paid_capital | currency }}</th>
              <th> {{ (ReportPortfolioTotal.paid_balance -
                ReportPortfolioTotal.paid_capital - ReportPortfolioTotal.additional_interest_paid) | currency }}</th>
              <th> {{ ReportPortfolioTotal.additional_interest_paid | currency }}</th>
              <th> {{ ReportPortfolioTotal.paid_balance | currency }}</th>
            </tr>
          </tbody>

        </table>
        <pagination :align="'center'" :data="ReportInstallmentList" :limit="2"
          @pagination-change-page="listReportInstallments">
          <span slot="prev-nav"><i class="bi bi-chevron-double-left"></i></span>
          <span slot="next-nav"><i class="bi bi-chevron-double-right"></i></span>
        </pagination>
      </section>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      ReportInstallmentList: {},
      ReportPortfolioTotal: {},
      headquarterList: [],
      now: moment().format('YYYY-MM-DD'),
      infoCompany: {},
      search_from: "",
      search_to: "",
      search_results: 15,
      loading: false,

      json_fields: {
        'ID cuota': {
          field: 'credit_id',
          callback: (value) => {
            return value;
          }
        },
        'Sede': {
          field: 'headquarter',
          callback: (value) => {
            return value;
          }
        },
        'Fecha': {
          field: 'payment_register',
          callback: (value) => {
            return value;
          }
        },
        'tipo': {
          callback: (value) => {
            return "Pago de cuota";
          }
        },
        'Afectación': {
          callback: (value) => {
            let name = value.name;
            let last_name = value.last_name;
            let document = value.document;
            let type_document = value.type_document
            return `Cliente: ${last_name} ${name} Documento: ${type_document} ${document}`;
          }
        },

        'Capital': {
          field: 'paid_capital',
          callback: (value) => value
        },

        'Interés': {
          callback: (value) => {
            let c = value.paid_capital;
            let b = value.paid_balance;
            let d = value.additional_interest_paid;
            let i = b - c - d;
            return Number(i).toFixed(2);
          }
        },
        'Interés Cobro & Admin ': {
          callback: (value) => {
            return Number(value.additional_interest_paid).toFixed(2);
          }
        },
        'Valor': {
          field: 'paid_balance',
          callback: (value) => {
            return value;
          }
        },

      }
    };
  },
  methods: {
    listReportInstallments(page = 1) {
      this.loading = true;
      let data = {
        page: page,
        from: this.search_from,
        to: this.search_to,
        results: this.search_results,
      };

      axios
        .get(`api/reports/installments`, {
          params: data,
          headers: this.$root.config.headers,
        })
        .then((response) => {
          this.ReportInstallmentList = response.data.installments;
          this.ReportPortfolioTotal = response.data.totals;
          this.loading = false;
        }).catch(() => {
          this.loading = false;
        });
    },

    listHeadquarters() {
      let me = this;
      axios
        .get(`api/headquarters/list-all-headquarters`, me.$root.config)
        .then(function (response) {
          me.headquarterList = response.data;
        });
    },

    getCompanyInformation() {
      axios.get("api/configurations", this.$root.config).then((response) => {
        if (response.data.company) {
          this.infoCompany = response.data.company;
        }
      });
    },
    sendPaymentCommitment(quote) {
      console.log(quote)
      let data = {
        'payment_commitment': quote.payment_commitment
      }
      axios
        .post(
          `api/installment/send-payment-commitment/${quote.id}`,
          data,
          this.$root.config
        )
        .then(function (response) {
          // handle success
          Swal.fire({
            icon: "success",
            title: ".",
            text: "La operación se ha realizado correctamente",
          });
        })
        .catch(function (error) {
          // handle error
          if (error) {
            Swal.fire({
              icon: "error",
              title: "Oops...",
              text: "No se pudo realizar esta operación",
            });
          }
        })
        .finally(this.listReportInstallments());
    }
  },

  mounted() {
    this.listReportInstallments();
    this.listHeadquarters();
    this.getCompanyInformation();
  },
};
</script>
