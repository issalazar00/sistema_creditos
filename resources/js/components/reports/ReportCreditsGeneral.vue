<template>
  <div class="page">
    <div class="page-header">
      <h3>Reporte general de créditos</h3>
    </div>
    <div class="page-search p-4 border my-2">
      <h6 class="text-primary text-uppercase">Filtrar:</h6>
      <form>
        <div class="form-row">
          <div class="form-group col-4 ml-auto">
            <label for="">Fecha de inicio:</label>
            <input type="date" id="search_start_date" name="search_start_date" class="form-control" placeholder="Desde"
              v-model="search_start_date" />
          </div>
          <div class="form-group col-4 mr-auto">
            <label for="">Fecha de finalización:</label>
            <input type="date" id="search_end_date" name="search_end_date" class="form-control" placeholder="Desde"
              v-model="search_end_date" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-4 ml-auto">
            <label for="search_status">Estado:</label>
            <select name="search_status" id="search_status" v-model="search_status" class="custom-select">
              <option value="all">Todos</option>
              <option :value="key" v-for="(status, key) in creditStatus" :key="status">
                {{ status }}
              </option>
            </select>
          </div>
          <div class="form-group col-4">
            <label for="">Desde:</label>
            <input type="date" id="search_from" name="search_from" class="form-control" placeholder="Desde"
              v-model="search_from" :max="now" />
          </div>
          <div class="form-group col-4 mr-auto">
            <label for="">Hasta:</label>
            <input type="date" id="search_to" name="search_to" class="form-control" placeholder="Desde"
              v-model="search_to" :max="now" />
          </div>
        </div>
        <div class="form-row text-right m-auto">
          <div class="form-group ml-md-auto col-md-4">
            <button class="btn btn-success w-100" type="button" @click="listReportGeneralCredits(1)">
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
              <th>Sede</th>
              <th>Valor crédito</th>
              <th>Número de cuotas</th>
              <th>Fecha de inicio</th>
              <th>Fecha finalización</th>
              <th>Estado</th>
              <th>Total abonado</th>
              <th>Abono a capital</th>
              <th>Abono a intereses</th>
            </tr>
          </thead>
          <tbody v-if="ReportGeneralCreditsList.data">
            <tr v-for="report in ReportGeneralCreditsList.data" :key="report.id">
              <td class="text-right">{{ report.id }}</td>
              <td>{{ report.client.name }} {{ report.client.last_name }}</td>
              <td>
                <a v-if="report.client.phone_1 != null" target="_blank"
                  :href="`https://wa.me/57${report.client.phone_1}?text=${infoCompany.whatsapp_msg}`"><i
                    class="bi bi-whatsapp"></i> {{ report.client.phone_1 }}</a>
                <br />
                <a v-if="report.client.phone_2 != null" target="_blank"
                  :href="`https://wa.me/57${report.client.phone_2}?text=${infoCompany.whatsapp_msg}`"><i
                    class="bi bi-whatsapp"></i> {{ report.client.phone_2 }}</a>
              </td>
              <td>
                {{ report.headquarter.headquarter }}
              </td>
              <td class="text-right">{{ report.credit_value | currency }}</td>
              <td class="text-center">{{ report.number_installments }}</td>
              <td> {{ report.start_date }}</td>
              <td> {{ report.end_date }}</td>
              <td>{{ creditStatus[report.status] }}</td>
              <td class="text-right">{{ report.paid_value | currency }}</td>
              <td class="text-right">{{ report.capital_value | currency }}</td>
              <td class="text-right">{{ report.interest_value | currency }}</td>
            </tr>
          </tbody>
        </table>
        <pagination :align="'center'" :data="ReportGeneralCreditsList" :limit="2"
          @pagination-change-page="listReportGeneralCredits">
          <span slot="prev-nav"><i class="bi bi-chevron-double-left"></i></span>
          <span slot="next-nav"><i class="bi bi-chevron-double-right"></i></span>
        </pagination>
      </section>
      <section class="table-responsive">
        <h5>Totalizado:</h5>
        <table class="table table-sm table-bordered">
          <tr class="text-right">
            <th>Total Valor créditos</th>
            <td>{{ ReportTotalValues.credit_value | currency }}</td>
          </tr>
          <tr class="text-right">
            <th>Total abonado</th>
            <td>{{ ReportTotalValues.paid_value | currency }}</td>
          </tr>
          <tr class="text-right">
            <th>Total abono a capital</th>
            <td>{{ ReportTotalValues.capital_value | currency }}</td>
          </tr>
          <tr class="text-right">
            <th>Total abonado a intereses</th>
            <td>{{ ReportTotalValues.interest_value | currency }}</td>
          </tr>
        </table>
      </section>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      ReportGeneralCreditsList: {},
      ReportTotalValues: {},
      infoCompany: {},
      creditStatus: {
        0: "Pendiente",
        1: "Aprobado",
        2: "Rechazado",
        3: "Pendiente pago a proveedor",
        4: "Completado",
        5: "Cobro jurídico",
      },
      now: new Date().toISOString().slice(0, 10),
      search_from: "",
      search_to: "",
      search_status: "all",
      search_start_date: '',
      search_end_date: ''
    };
  },
  methods: {
    listReportGeneralCredits(page = 1) {
      axios
        .get(
          `api/reports/general-credits?page=${page}&from=${this.search_from}&to=${this.search_to}&status=${this.search_status}&start_date=${this.search_start_date}&end_date=${this.search_end_date}`,
          this.$root.config
        )
        .then((response) => {
          this.ReportGeneralCreditsList = response.data.credits;
          this.ReportTotalValues = response.data.total_credits;
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
    this.listReportGeneralCredits();
    this.getCompanyInformation()
  },
};
</script>

<style>
</style>
