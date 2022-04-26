<template>
  <div class="page">
    <div class="page-header d-flex justify-content-between p-4 border my-2">
      <h3>Creditos pendientes</h3>
      <button
        type="button"
        class="btn btn-primary"
        data-toggle="modal"
        data-target="#formCreditModal"
        v-if="$root.validatePermission('credit-store')"
      >
        Crear Credito
      </button>
    </div>
    <div class="page-search d-flex justify-content-between p-4 border my-2">
      <div class="form-group col-8 m-auto">
        <input
          type="text"
          id="search_client"
          name="search_client"
          class="form-control"
          placeholder="Buscar cliente | Documento"
          v-model="search_client"
        />
      </div>
      <div class="form-group col-8 m-auto">
        <button class="btn btn-primary" @click="listCredits(1)">Buscar</button>
      </div>
    </div>

    <div class="page-content mt-4" style="width: 100%">
      <section class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead>
            <tr class="text-center">
              <th>ID</th>
              <th>Cliente</th>
              <th>Sede</th>
              <th>Nro. Documento</th>
              <th>Valor crédito</th>
              <th>Valor Abonado</th>
              <th>Nro Cuotas</th>
              <th>Estado</th>
              <th v-if="$root.validatePermission('credit-index')">
                Información crédito
              </th>

              <th v-if="$root.validatePermission('credit-status')">Opciones</th>
            </tr>
          </thead>
          <!-- <tbody> -->
          <tbody v-if="creditList.data && creditList.data.length > 0">
            <tr v-for="credit in creditList.data" :key="credit.index">
              <td>{{ credit.id }}</td>
              <td>{{ credit.name }} {{ credit.last_name }}</td>
              <td>{{  credit.headquarter.headquarter }}</td>
              <td>{{ credit.document }}</td>
              <td class="text-right">{{ credit.credit_value | currency }}</td>
              <td class="text-right">{{ credit.paid_value | currency }}</td>
              <td>{{ credit.number_installments }}</td>
              <td>
                {{ creditStatus[credit.status] }}
              </td>
              <td
                class="text-center"
                v-if="$root.validatePermission('credit-index')"
              >
                <button
                  type="button"
                  class="btn btn-info"
                  data-toggle="modal"
                  data-target="#creditInformationModal"
                  @click="showInformation(credit)"
                >
                  <i class="bi bi-eye"></i>
                  Información general
                </button>
              </td>
              <td class="text-left">
                <div v-if="credit.status == 0 || credit.status == 4">
                  <button
                    class="btn btn-success"
                    @click="changeStatus(credit.id, 1)"
                  >
                    <i class="bi bi-check2-circle"></i> Aprobar
                  </button>
                  <button
                    class="btn btn-danger"
                    @click="changeStatus(credit.id, 2)"
                  >
                    <i class="bi bi-x-circle"></i> Rechazar
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr class="text-center">
              <td colspan="11">
                <div
                  class="alert alert-danger text-center"
                  style="margin: 2px auto; width: 30%"
                >
                  <p>No se encontraron clientes con creditos.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <pagination
          :align="'center'"
          :data="creditList"
          :limit="2"
          @pagination-change-page="listCredits"
        >
          <span slot="prev-nav"><i class="bi bi-chevron-double-left"></i></span>
          <span slot="next-nav"
            ><i class="bi bi-chevron-double-right"></i
          ></span>
        </pagination>
      </section>
    </div>

    <credit-information
      ref="CreditInformation"
      @list-credits="listCredits(1)"
    />
    <create-edit-credit ref="CreateEditCredit" @list-credits="listCredits(1)" />
  </div>
</template>
<script>
import CreateEditCredit from "../credit_clients/CreateEditCredit.vue";

import CreditInformation from "./CreditInformation.vue";
export default {
  name: "disbursements",
  components: {
    CreditInformation,
    CreateEditCredit,
  },
  props: {
    installment: {
      type: Object,
    },
  },
  data() {
    return {
      search_client: "",
      status: 0,
      creditList: {},
      clientList: {},
      creditStatus: {
        0: "Pendiente",
        1: "Aprobado",
        2: "Rechazado",
        3: "Pendiente pago a proveedor",
        4: "Completado",
        5: "Cobro jurídico",
      },
    };
  },
  created() {
    this.listCredits(1);
  },
  methods: {
    listCredits(page = 1) {
      let me = this;
      axios
        .get(
          `api/credits?page=${page}&credit=${this.search_client}&status=${this.status}`,
          me.$root.config
        )
        .then(function (response) {
          me.creditList = response.data;
        });
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
          if (status != 2) {
            this.sendData(id, data);
            Swal.fire("Cambios realizados!", "", "success");
          } else {
            this.msgRejectd(id);
          }
        } else {
          Swal.fire("Operación no realizada", "", "info");
        }
      });
    },
    msgRejectd: async function (id) {
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
              status: 2,
              description: response.value,
            };
            this.sendData(id, data);
            Swal.fire("Cambios realizados!", "", "success");
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
    showData: function (credit) {
      this.$refs.CreateEditCredit.showEditCredit(credit);
    },
    showInformation(credit) {
      this.$refs.CreditInformation.showInformation(credit, 0);
    },
  },
};
</script>
