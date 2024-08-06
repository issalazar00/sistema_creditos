<template>
  <div class="page">
    <div class="page-header">
      <h3>Reporte general de créditos</h3>
    </div>
    <div class="page-search border my-2">
      <h6 class="text-primary text-uppercase">Filtrar:</h6>
      <form class="">
        <div class="form-row">
          <div class="form-group col-4 ">
            <label for="search_client">Cliente: </label>
            <input type="text" id="search_client" name="search_client" class="form-control"
              placeholder="Nombres | Documento" v-model="search_client" />
          </div>
          <div class="form-group col-4">
            <label for="search_credit_id">Nro Crédito: </label>
            <input type="number" id="search_credit_id" name="search_credit_id" class="form-control"
              placeholder="Número de crédito" v-model="search_credit_id" />
          </div>
          <div class="form-group col-md-4">
            <label for="search_headquarter_id" class=" w-100 form-label">Sede
            </label>
            <v-select label="headquarter" class="w-100" v-model="search_headquarter_id" :reduce="(option) => option.id"
              :filterable="false" :options="listHeadquarters" @search="onSearchHeadquarter">
              <template slot="no-options">
                Escribe para iniciar la búsqueda
              </template>
              <template slot="option" slot-scope="option">
                <div class="d-center">
                  {{ option.headquarter }}
                </div>
              </template>
              <template slot="selected-option" slot-scope="option">
                <div class="selected d-center">
                  {{ option.headquarter }}
                </div>
              </template>
            </v-select>
          </div>
          <div class="form-group col-4  offset-4">
            <label for="">Fecha de inicio:</label>
            <input type="date" id="search_start_date" name="search_start_date" class="form-control" placeholder="Desde"
              v-model="search_start_date" />
          </div>
          <div class="form-group col-4">
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
        <div class="form-row m-auto">
          <div class="form-group col-md-4 col-sm-6 col-xs-6 ">
            <label for="">Mostrar {{ search_results }} resultados por página:</label>
            <input type="number" id="search_results" name="search_results" class="form-control" placeholder="Desde"
              v-model="search_results" max="1000" />
          </div>
          <div class="form-group col-md-4 col-sm-6 col-xs-6">
            <button class="btn btn-success w-100 mt-5" type="button" @click="listReportGeneralCredits(1)">
              <i class="bi bi-search"></i> Buscar
            </button>
          </div>
          <div class="form-group col-md-4 col-sm-6 col-xs-6">
            <download-excel class="btn btn-primary w-100 mt-5" :fields="json_fields"
              :data="ReportGeneralCreditsList.data" name="report-general-credits.xls" type="xls">
              <i class="bi bi-file-earmark-arrow-down-fill"></i> Descargar .xls
            </download-excel>
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
              <th>Identificación</th>
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
              <th>Abono a Interés Cobro & Admin</th>
              <th>Saldo capital</th>
              <th>Saldo pendiente</th>
            </tr>
          </thead>
          <tbody v-if="ReportGeneralCreditsList.data">
            <tr v-for="report in ReportGeneralCreditsList.data" :key="report.id">
              <td class="text-right">{{ report.id }}</td>
              <td>{{ report.client.name }} {{ report.client.last_name }}</td>
              <td>{{ report.client.type_document }} {{ report.client.document }}</td>
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
              <td class="text-right">{{ report.additional_interest_paid | currency }}</td>
              <td class="text-right">{{ calculateBalanceInstallment(report) | currency }}</td>
              <td class="text-right">{{ report.credit_to_pay | currency }}</td>
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
          <tr class="text-right">
            <th>Total abonado a intereses Cobro & Admin</th>
            <td>{{ ReportTotalValues.additional_interest_paid | currency }}</td>
          </tr>
          <tr class="text-right">
            <th class="font-weight-bold">Total saldo capital</th>
            <td>{{ calculateBalanceInstallment(ReportTotalValues) | currency }}</td>
          </tr>
          <tr class="text-right">
            <th class="font-weight-bold">Total a recaudar </th>
            <td>{{ ReportTotalValues.total_credit_to_pay | currency }}</td>
          </tr>
          <tr class="text-right">
            <th class="font-weight-bold">Total interés a recaudar</th>
            <td>{{ (ReportTotalValues.total_credit_to_pay - calculateBalanceInstallment(ReportTotalValues)) | currency
              }}
            </td>
          </tr>
          <!-- <tr class="text-right">
            <th class="font-weight-bold">Total saldo actual</th>
            <td class="h4">{{ (ReportTotalValues.credit_value - ReportTotalValues.paid_value) | currency }}</td>
          </tr> -->
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
      listHeadquarters: [],
      creditStatus: {
        0: "Pendiente",
        1: "Activos",
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
      search_end_date: '',
      search_client: '',
      search_credit_id: '',
      search_results: 15,
      search_headquarter_id: "",

      json_fields: {
        'Cliente': {
          callback: (value) => {
            let name = value.client.name;
            let last_name = value.client.last_name
            return `${last_name} ${name}`;
          }
        },

        'Identificación': {
          callback: (value) => {
            let type = value.client.type_document;
            let doc = value.client.document
            return `${type} ${doc}`;
          }
        },

        'Sede': {
          field: 'headquarter.headquarter',
          callback: (value) => {
            return value;
          }
        },
        'Contacto 1': {
          field: 'client.phone_1',
          callback: (value) => {
            return value;
          }
        },
        'Contacto 2': {
          field: 'client.phone_2',
          callback: (value) => {
            return value;
          }
        },
        'Valor crédito': {
          field: 'credit_value',
          callback: (value) => {
            return this.$options.filters.currency(value, 'export');
          }
        },
        'Nro. Cuotas': {
          field: 'number_installments',
          callback: (value) => {
            return value;
          }
        },
        'Fecha de inicio': {
          field: 'start_date',
          callback: (value) => {
            return value;
          }
        },
        'Fecha de finalizacion': {
          field: 'end_date',
          callback: (value) => {
            return value;
          }
        },
        'Estado': {
          field: 'status',
          callback: (value) => {
            if (value == 0) {
              return 'Pendiente'
            }
            if (value == 1) {
              return 'Activo'
            }
            if (value == 2) {
              return 'Rechazado'
            }
            if (value == 3) {
              return 'Pendiente pago a proveedor'
            }
            if (value == 4) {
              return 'Completado'
            }
            if (value == 5) {
              return 'Cobro jurídico'
            }
          }
        },
        'Total abonado': {
          field: 'paid_value',
          callback: (value) => {
            return this.$options.filters.currency(value, 'export');
          }
        },
        'Abono a capital': {
          field: 'capital_value',
          callback: (value) => {
            return this.$options.filters.currency(value, 'export');
          }
        },
        'Abono a intereses': {
          field: 'interest_value',
          callback: (value) => {
            return this.$options.filters.currency(value, 'export');
          }
        },
        'Abono a Interés Cobro & Admin': {
          field: 'additional_interest_paid',
          callback: (value) => {
            return this.$options.filters.currency(value, 'export');
          }
        },
        'Saldo capital': {
          callback: (value) => {
            return this.$options.filters.currency(this.calculateBalanceInstallment(value), 'export');
          }
        },
        'Saldo pendiente': {
          field: 'credit_to_pay',
          callback: (value) => {
            return this.$options.filters.currency(value, 'export');
          }
        },
      }
    };
  },
  methods: {
    listReportGeneralCredits(page = 1) {
      let data = {
        page: page,
        from: this.search_from,
        to: this.search_to,
        status: this.search_status,
        start_date: this.search_start_date,
        end_date: this.search_end_date,
        search_client: this.search_client,
        search_credit_id: this.search_credit_id,
        search_headquarter_id: this.search_headquarter_id,
        results: this.search_results
      }
      axios
        .get(
          `api/reports/general-credits`,
          {
            params: data,
            headers: this.$root.config.headers,
          }
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
    calculateBalanceInstallment(data) {
      // console.log("Value saldo: ", (data.credit_value + data.interest_value) - data.paid_value);
      return (data.credit_value + data.interest_value + data.additional_interest_paid) - data.paid_value;
    },
    onSearchHeadquarter(search, loading) {
      if (search.length) {
        loading(true);
        axios.get(`api/headquarters/list-headquarter?headquarter=${search}&page=1`, this.$root.config)
          .then((response) => {
            this.listHeadquarters = (response.data);
            loading(false)
          })
          .catch(e => console.log(e))
      }
    },
  },
  mounted() {
    this.listReportGeneralCredits();
    this.getCompanyInformation()
  },
};
</script>

<style></style>
