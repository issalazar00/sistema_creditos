<template>
  <div class="page">
    <div class="page-header">
      <h3>Reporte de cartera</h3>
    </div>
    <div class="page-search p-4 border my-2">
      <h6 class="text-primary text-uppercase">Filtrar:</h6>
      <form>
        <div class="form-row">
          <div class="form-group col-4 ml-auto">
            <label for="">Desde:</label>
            <input
              type="date"
              id="search_from"
              name="search_from"
              class="form-control"
              placeholder="Desde"
              v-model="search_from"
              :max="now"
            />
          </div>
          <div class="form-group col-4 mr-auto">
            <label for="">Hasta:</label>
            <input
              type="date"
              id="search_to"
              name="search_to"
              class="form-control"
              placeholder="Desde"
              v-model="search_to"
              :max="now"
            />
          </div>
        </div>
        <div class="form-row text-right m-auto">
          <div class="form-group m-md-auto col-md-4">
            <button class="btn btn-success w-100" type="button" @click="listReportPortfolio(1)">
              <i class="bi bi-search"></i> Buscar
            </button>
          </div>
        </div>
      </form>
    </div>
    <div class="page-content">
      <section class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead>
            <tr class="text-center">
              <th>#</th>
              <th>Cliente</th>
              <th>Contacto <i class="bi bi-telephone"></i></th>
              <th>Valor cuota</th>
              <th>Fecha de pago cuota</th>
              <th>Estado</th>
              <th>Nro. Cuota</th>
            </tr>
          </thead>
          <tbody v-if="ReportPortfolioList.data">
            <tr v-for="report in ReportPortfolioList.data" :key="report.id">
              <td>{{ report.credit_id }}</td>
              <td>{{ report.name }} {{ report.last_name }}</td>
              <td>
                <a
                  v-if="report.phone_1 != null"
                  target="_blank"
                  :href="`https://wa.me/57${report.phone_1}?text=${infoCompany.whatsapp_msg}`"
                  ><i class="bi bi-whatsapp"></i> {{ report.phone_1 }}</a
                >
                <br />
                <a
                  v-if="report.phone_2 != null"
                  target="_blank"
                  :href="`https://wa.me/57${report.phone_2}?text=${infoCompany.whatsapp_msg}`"
                  ><i class="bi bi-whatsapp"></i> {{ report.phone_2 }}</a
                >
              </td>
              <td class="text-right">{{ report.value | currency }}</td>
              <td class="text-center font-weight-bold">
                <span
                  class="badge badge-md badge-pill badge-success"
                  v-if="report.payment_date > now"
                  >{{ report.payment_date }}</span
                >
                <span
                  class="badge badge-md badge-pill badge-warning"
                  v-if="report.payment_date == now"
                  >{{ report.payment_date }}</span
                >
                <span
                  class="badge badge-md badge-pill badge-danger"
                  v-if="report.payment_date < now"
                  >{{ report.payment_date }}</span
                >
              </td>
              <td class="text-center">
                <span
                  class="
                    badge badge-md
                    font-weight-bold
                    badge-pill badge-success
                  "
                  v-if="report.payment_date > now"
                  >Próximo a vencer</span
                >
                <span
                  class="
                    badge badge-md
                    font-weight-bold
                    badge-pill badge-warning
                  "
                  v-if="report.payment_date == now"
                  >Vence hoy</span
                >
                <span
                  class="
                    badge badge-md
                    font-weight-bold
                    badge-pill badge-danger
                  "
                  v-if="report.payment_date < now"
                  >En mora</span
                >
              </td>
              <td>
                {{ report.installment_number }}
              </td>
            </tr>
          </tbody>
        </table>
        <pagination
          :align="'center'"
          :data="ReportPortfolioList"
          :limit="2"
          @pagination-change-page="listReportPortfolio"
        >
          <span slot="prev-nav"><i class="bi bi-chevron-double-left"></i></span>
          <span slot="next-nav"
            ><i class="bi bi-chevron-double-right"></i
          ></span>
        </pagination>
      </section>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      ReportPortfolioList: {},
      now: new Date().toISOString().slice(0, 10),
      infoCompany: {},
      search_from: "",
      search_to: "",
    };
  },
  methods: {
    listReportPortfolio(page = 1) {
      axios
        .get(`api/reports/portfolio?page=${page}&from=${this.search_from}&to=${this.search_to}`, this.$root.config)
        .then((response) => {
          this.ReportPortfolioList = response.data;
        });
    },
    getCompanyInformation() {
      axios.get("api/configurations", this.$root.config).then((response) => {
        if (response.data.company) {
          this.infoCompany = response.data.company;
        }
      });
    },
  },

  mounted() {
    this.listReportPortfolio();
    this.getCompanyInformation();
  },
};
</script>
