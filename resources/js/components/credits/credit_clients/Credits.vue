<template id="main">
  <div class="page">
    <div class="page-header d-flex justify-content-between p-4 border my-2">
      <h3>Creditos</h3>
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#formCreditModal"
        v-if="$root.validatePermission('credit-store')">
        Crear Credito
      </button>
    </div>
    <div class="page-search d-flex justify-content-between p-4 border my-2">
      <div class="form-row w-100">
        <div class="form-group col-md-4 col-lg-3 ">
          <label for="search_client">Cliente</label>
          <input type="text" id="search_client" name="search_client" class="form-control col"
            placeholder="Buscar cliente | Documento" v-model="search_client" />
        </div>
        <div class="form-group col-md-4 col-lg-3 ">
          <label for="search_credit_id">Nro Crédito: </label>
          <input type="number" id="search_credit_id" name="search_credit_id" class="form-control"
            placeholder="Número de crédito" v-model="search_credit_id" />
        </div>
        <div class="form-group col-md-4 col-lg-3">
          <label for="headquarter_id">Sede</label>
          <v-select :options="headquarterList" label="headquarter" aria-logname="{}"
            :reduce="(headquarter) => headquarter.id" v-model="search_headquarter_id" placeholder="Seleccionar sede">
          </v-select>
        </div>
        <div class="form-group col-md-4 col-lg-3 ">
          <label for="status">Estado</label>
          <select name="status" id="status" v-model="status" class="custom-select col">
            <option v-for="(st, index) in creditStatus" :value="index" :key="index">
              {{ st }}
            </option>
          </select>
        </div>
        <div class="form-group col-md-4 col-lg-3 offset-md-8 offset-lg-9">
          <button class="btn btn-primary btn-block" @click="listCredits()">Buscar</button>
        </div>
      </div>
    </div>

    <div class="page-content mt-4" style="width: 100%">
      <section class="table-responsivee ">
        <table class="table table-sm table-bordered bg-light">
          <thead>
            <tr class="text-center">
              <th>ID</th>
              <th>Fecha inicio</th>
              <th>Cliente</th>
              <th>Sede</th>
              <th>Nro. Documento</th>
              <th>Cupo disponible</th>
              <th>Codeudor (es)</th>
              <th>Descripción</th>
              <th>Valor crédito</th>
              <th>Valor Abonado</th>
              <th>Nro Cuotas</th>
              <th>Estado</th>
              <th v-if="$root.validatePermission('credit-index')">
                Ver Cuotas
              </th>
              <th v-if="$root.validatePermission('credit-index')">
                Tabla de <br />
                amortización
              </th>
              <th>Paz y salvo</th>
              <th>Recoger crédito</th>
              <!-- <th>Cobro jurídico</th> -->
              <th v-if="$root.validatePermission('credit-update') ||
          $root.validatePermission('credit-status')
          ">
                Opciones
              </th>
            </tr>
          </thead>
          <!-- <tbody> -->
          <tbody v-if="creditList.data && creditList.data.length > 0">
            <tr v-for="credit in creditList.data" :key="credit.index">
              <td>{{ credit.id }}</td>
              <td>{{ credit.start_date }}</td>
              <td>{{ credit.name }} {{ credit.last_name }}</td>
              <td>{{ credit.headquarter.headquarter }}</td>
              <td>{{ credit.type_document }} {{ credit.document }}</td>
              <td> {{ credit.maximum_credit_allowed }}</td>
              <td class="p-0">
                <table v-if="credit.debtors.length" class="table table-sm">
                  <thead>
                    <tr>
                      <th scope="col">Nombre</th>
                      <th scope="col">Documento</th>
                      <th scope="col">Celular</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="d in credit.debtors" :key="d.id">
                      <td>{{ d.name }} {{ d.last_name }}</td>
                      <td>{{ d.type_document }} {{ d.document }}</td>
                      <td>{{ d.phone_1 }} {{ d.phone_2 }}</td>
                    </tr>
                  </tbody>
                </table>
                <span v-else>No hay codeudores asociados</span>
              </td>
              <td>
                <textarea name="description" id="description" cols="10" rows="4" class="form-control-plaintext" readonly
                  v-model="credit.description"></textarea>
              </td>
              <td class="text-right">{{ credit.credit_value | currency }}</td>
              <td class="text-right">{{ credit.paid_value | currency }}</td>
              <td>{{ credit.number_installments }}</td>
              <td>
                <b class="text-uppercase"> {{ creditStatus[credit.status] }}</b> <br>
                <button @click="openModalChangeStatus(credit)" data-toggle="modal" data-target="#modalChangeStatus"
                  class="btn btn-info btn-sm">
                  <i class="bi bi-arrow-repeat"></i> Cambiar estado
                </button>
              </td>
              <td class="text-center" v-if="$root.validatePermission('credit-index')">
                <button data-toggle="modal" data-target="#cuotasModal" class="btn btn-info"
                  @click="showInstallment(credit)">
                  <i class="bi bi-eye"></i>
                </button>
              </td>
              <td class="text-center" v-if="$root.validatePermission('credit-index')">
                <button class="btn btn-danger" @click="
          printTable(credit.id, credit.name + '_' + credit.last_name)
          ">
                  <i class="bi bi-file-pdf"></i>
                </button>
                <br>
                <a role="button" class="btn btn-primary" v-if="credit.phone_1 != null" target="_blank"
                  :href="`https://wa.me/57${credit.phone_1}?text=Hola!`"><i
                    class="bi bi-whatsapp  "></i> {{ credit.phone_1 }}</a>
                <br />
                <a role="button" class="btn btn-primary" v-if="credit.phone_2 != null" target="_blank"
                  :href="`https://wa.me/57${credit.phone_2}?text=Hola!`"><i
                    class="bi bi-whatsapp "></i> {{ credit.phone_2 }}</a>
              </td>
              <td>
                <div v-if="credit.status == 4">
                  <button type="button" class="btn btn-danger" @click="
          downloadReceiptPDF(
            credit.id,
            credit.name + '_' + credit.last_name
          )
          ">
                    <i class="bi bi-file-earmark-pdf"></i>
                    Descargar
                  </button>
                </div>
                <div v-else>No disponible por el momento</div>
              </td>
              <td>
                <button v-if="credit.status == 1" class="btn btn-success" @click="collectCredit(credit.id)">
                  <i class="bi bi-box-arrow-in-down"></i>
                  Recoger
                </button>
              </td>
              <!-- <td>
                <button v-if="credit.status == 1" class="btn btn-danger" @click="changeStatus(credit.id, 5)">
                  <i class="bi bi-x-circle"></i> Pasar a cobro jurídico
                </button>
                <button v-if="credit.status == 5" class="btn btn-success" @click="changeStatus(credit.id, 1)">
                  <i class="bi bi-check"></i> Deshacer cobro jurídico
                </button>
              </td> -->

              <td class="text-right" v-if="$root.validatePermission('credit-update') ||
          $root.validatePermission('credit-status')
          ">
                <div v-if="$root.validatePermission('credit-update')" class="d-inline">
                  <button
                    v-if="(credit.status == 0 || credit.status == 1 || credit.status == 3) && credit.paid_value <= 1"
                    class="btn btn-sm btn-primary" @click="showData(credit)">
                    <i class="bi bi-pen"></i> Crédito
                  </button>
                  <button v-else class="btn btn-sm btn-secondary" disabled>
                    <i class="bi bi-pen"></i>
                  </button>

                  <button class="btn btn-sm btn-success" @click="updateDate(credit.id)">
                    <i class="bi bi-pen"></i> Fecha
                  </button>
                </div>
                <div v-if="$root.validatePermission('credit-status')" class="d-inline">
                  <!-- <button v-if="credit.status == 0 || credit.status == 3" class="btn btn-danger"
                    @click="changeStatus(credit.id, 2)">
                    <i class="bi bi-x-circle"></i>
                  </button>
                  <button v-if="credit.status == 0 || credit.status == 3" class="btn btn-success"
                    @click="changeStatus(credit.id, 1)">
                    <i class="bi bi-check2-circle"></i>
                  </button> -->

                  <button v-if="credit.status == 0 || credit.status == 3" type="button" class="btn btn-success"
                    data-toggle="modal" data-target="#creditInformationModal" @click="showInformation(credit)">
                    <i class="bi bi-check2-circle"></i>
                    Aprobación de crédito
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr class="text-center">
              <td colspan="11">
                <div class="alert alert-danger text-center" style="margin: 2px auto; width: 30%">
                  <p>No se encontraron clientes con creditos.</p>
                  <p>Crear cliente.</p>
                </div>
                <div class="alert alert-info" style="margin: 2px auto; width: 30%">
                  Crear un nuevo Cliente
                  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#formClientModal">
                    Crear cliente
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <pagination :align="'center'" :data="creditList" :limit="2" @pagination-change-page="listCredits">
          <span slot="prev-nav"><i class="bi bi-chevron-double-left"></i></span>
          <span slot="next-nav"><i class="bi bi-chevron-double-right"></i></span>
        </pagination>
      </section>
    </div>


    <modal-create-edit-client ref="ModalCreateEditClient" @list-clients="listCredits(1)" />
    <create-edit-credit ref="CreateEditCredit" @list-credits="listCredits(1)" />
    <modal-installment ref="ModalInstallment" />
    <credit-information ref="CreditInformation" @list-credits="listCredits(1)" />
    <modal-change-status ref="ModalChangeStatus" @list-credits="listCredits(1)"></modal-change-status>

  </div>
</template>

<script>
import CreateEditCredit from "./CreateEditCredit.vue";
import ModalCreateEditClient from "../../clients/ModalCreateEditClient.vue";
import ModalInstallment from "../credit_helpers/ModalInstallment.vue";
import CreditInformation from "../outstanding_credits/CreditInformation.vue";
import ModalChangeStatus from "../credit_helpers/ModalChangeStatus.vue";

export default {
  components: {
    CreateEditCredit,
    ModalCreateEditClient,
    ModalInstallment,
    CreditInformation,
    ModalChangeStatus
  },

  data() {
    return {
      search_client: "",
      search_credit_id: '',
      search_headquarter_id: 'all',
      status: 1,
      creditList: {},
      clientList: {},
      headquarterList: [],
      creditStatus: {
        0: "Pendiente",
        1: "Activo",
        2: "Rechazado",
        3: "Pendiente pago a proveedor",
        4: "Completado",
        5: "Cobro jurídico",
        6: "Eliminado",
        7: 'Pérdida'
      },
      msg_rejected: "",
    };
  },
  created() {
    this.listCredits(1);
    this.listHeadquarters();
  },
  methods: {
    listCredits(page = 1) {
      this.$root.getCurrentBalanceMainBox();
      let me = this;

      let data = {
        page: page,
        status: this.status,
        credit: this.search_client,
        headquarter_id: this.search_headquarter_id,
        search_credit_id: this.search_credit_id
      }

      axios
        .get(
          `api/credits`,
          {
            params: data,
            headers: this.$root.config.headers,
          }
        )
        .then(function (response) {
          me.creditList = response.data;
        });
    },

    listClients(page = 1) {
      let me = this;
      axios
        .get(
          `api/clients?page=${page}&client=${this.search_client}`,
          me.$root.config
        )
        .then(function (response) {
          me.clientList = response.data;
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

    showData: function (credit) {
      this.$refs.CreateEditCredit.showEditCredit(credit);
    },

    showInstallment: function (credit) {
      let credit_id = credit.id;
      let allow_payment = credit.status == 1 ? 1 : 0;
      this.$refs.ModalInstallment.listCreditInstallments(
        credit_id,
        allow_payment,
        credit
      );
    },
    showDataClient: function (client) {
      this.$refs.ModalCreateEditClient.showEditClient(client);
    },
    changeStatus: function (id, status) {
      var data = {
        status: status,
      };
      Swal.fire({
        title: "¿Quieres cambiar el estado del credito?",
        showCancelButton: true,
        cancelButtonText: "Cancelar",
        confirmButtonText: `Aceptar`,
      }).then((result) => {
        if (result.isConfirmed) {
          if (status != 2 && status != 7) {
            this.sendData(id, data);
            Swal.fire("Cambios realizados!", "", "success");
          } else {
            this.msgRejectd(id, status);
          }
        } else {
          Swal.fire("Operación no realizada", "", "info");
        }
      });
    },
    updateDate: async function (id, status) {
      const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          title: "text-primary",
          cancelButton: "btn btn-secondary",
          confirmButton: "btn btn-danger",
        },
        buttonsStyling: false,
      });

      await swalWithBootstrapButtons
        .fire({
          title: "Motivo de rechazo",
          reverseButtons: true,
          input: "text",
          inputLabel: "Realice una descripción del motivo",
          inputPlaceholder: "",
          showCancelButton: true,
          cancelButtonText: "Cancelar",
          confirmButtonText: "Rechazar",
          inputValidator: (value) => {
            if (!value) {
              return "Debes completar este campo!";
            }
          },
        })
        .then((response) => {
          console.log(response.isConfirmed);
          if (response.isConfirmed) {
            var data = {
              status: status,
              description: response.value,
            };
            this.sendData(id, data);
            Swal.fire("Cambios realizados!", "", "success");
          } else {
            Swal.fire("Operación no realizada", "", "info");
          }
        });
    },
    updateDate: function (id) {
      let me = this;
      const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
          title: "text-primary",
          cancelButton: "btn btn-secondary",
          confirmButton: "btn btn-danger",
        },
        buttonsStyling: false,
      });

      swalWithBootstrapButtons
        .fire({
          title: "Cambio de fecha de crédito",
          reverseButtons: true,
          input: "date",
          inputLabel: "Esta operación afectara a todas las cuotas relacionadas",
          showCancelButton: true,
          cancelButtonText: "Cancelar",
          confirmButtonText: "Aceptar",
          inputValidator: (value) => {
            if (!value) {
              return "Debes completar este campo!";
            }
          },
        })
        .then((response) => {
          console.log(response.isConfirmed);
          if (response.isConfirmed) {
            var data = {
              credit_id: id,
              start_date: response.value,
            };
            axios
              .post(`api/installments/payment-date`, data, this.$root.config)
              .then(function () {
               
                me.listCredits(1);
                Swal.fire("Cambios realizados!", "", "success");
              });
          } else {
            Swal.fire("Operación no realizada", "", "info");
          }
        });
    },
    sendData(id, data) {
      let me = this;
      axios
        .post(`api/credits/${id}/change-status`, data, this.$root.config)
        .then(function () {
          me.listCredits(1);
        });
    },
    printTable(credit_id, client) {
      axios
        .get(
          `api/credits/amortization-table?credit_id=${credit_id}`,
          this.$root.config
        )
        .then((response) => {
          const pdf = response.data.pdf;
          var a = document.createElement("a");
          a.href = "data:application/pdf;base64," + pdf;
          a.download = `credit_${credit_id}-${client}-${Date.now()}.pdf`;
          a.click();
        });
    },
    downloadReceiptPDF(credit_id, client) {
      axios
        .get(`api/credits/download-Receipt-PDF/${credit_id}`, this.$root.config)
        .then((response) => {
          const pdf = response.data.pdf;
          var a = document.createElement("a");
          a.href = "data:application/pdf;base64," + pdf;
          a.download = `paz-y-salvo-credito_${credit_id}-${client}.pdf`;
          a.click();
        });
    },
    collectCredit(credit_id) {
      let me = this;

      Swal.fire({
        title: "¿Seguro de recoger el crédito?",
        showDenyButton: true,
        denyButtonText: `Cancelar`,
        confirmButtonText: `Guardar`,
      }).then((result) => {
        if (result.isConfirmed) {
          axios
            .post(
              `api/credits/collect-credit/${credit_id}`,
              null,
              me.$root.config
            )
            .then(function () {
              me.listCredits(1);
            });
          Swal.fire("Cambios realizados!", "", "success");
        } else if (result.isDenied) {
          Swal.fire("Operación no realizada", "", "info");
        }
      });
    },
    showInformation(credit) {
      this.$refs.CreditInformation.showInformation(credit, 0);
    },

    openModalChangeStatus(credit) {
      this.$refs.ModalChangeStatus.openModal(credit)
    }
  },
};
</script>
